<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Respuesta
{
    /** Fija las preguntas de un intento, en orden, antes de que el estudiante responda. */
    public static function crearFilas(int $intentoId, array $ejercicioIds): void
    {
        foreach (array_values($ejercicioIds) as $i => $ejercicioId) {
            DB::consulta('INSERT INTO respuestas (intento_id, ejercicio_id, orden) VALUES (?, ?, ?)', [$intentoId, $ejercicioId, $i + 1]);
        }
    }

    public static function deIntento(int $intentoId): array
    {
        return DB::filas('SELECT * FROM respuestas WHERE intento_id = ? ORDER BY orden, id', [$intentoId]);
    }

    public static function idsDeIntento(int $intentoId): array
    {
        return array_map('intval', array_column(self::deIntento($intentoId), 'ejercicio_id'));
    }

    /**
     * Registra una respuesta. Solo actualiza si la pregunta pertenece al intento y aún no
     * tiene respuesta, para que no pueda cambiarse después de ver la retroalimentación.
     */
    public static function registrar(int $intentoId, int $ejercicioId, int $opcionId, bool $esCorrecta): bool
    {
        $filas = DB::consulta(
            'UPDATE respuestas SET opcion_id = ?, es_correcta = ?, respondida_en = NOW()
             WHERE intento_id = ? AND ejercicio_id = ? AND opcion_id IS NULL',
            [$opcionId, (int) $esCorrecta, $intentoId, $ejercicioId]
        )->rowCount();
        return $filas === 1;
    }

    /** Detalle para la revisión del resultado: respuesta dada, correcta y explicación (HU-08). */
    public static function detalleDeIntento(int $intentoId): array
    {
        return DB::filas(
            'SELECT r.orden, r.es_correcta, e.id AS ejercicio_id, e.enunciado, e.explicacion, e.dificultad,
                    t.id AS tema_id, t.titulo AS tema, te.nombre AS tipo_error,
                    od.texto AS respuesta_dada, oc.texto AS respuesta_correcta
             FROM respuestas r
             JOIN ejercicios e ON e.id = r.ejercicio_id
             JOIN temas t ON t.id = e.tema_id
             JOIN tipos_error te ON te.id = e.tipo_error_id
             LEFT JOIN opciones od ON od.id = r.opcion_id
             LEFT JOIN opciones oc ON oc.ejercicio_id = e.id AND oc.es_correcta = 1
             WHERE r.intento_id = ? ORDER BY r.orden',
            [$intentoId]
        );
    }
}
