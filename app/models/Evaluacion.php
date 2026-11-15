<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Evaluacion
{
    public static function activa(): ?array
    {
        return DB::fila('SELECT * FROM evaluaciones WHERE activa = 1 ORDER BY id LIMIT 1');
    }

    public static function buscar(int $id): ?array
    {
        return DB::fila('SELECT * FROM evaluaciones WHERE id = ?', [$id]);
    }

    /** Ejercicios activos de temas publicados que pueden salir en la evaluación. */
    public static function disponibles(int $evaluacionId): int
    {
        return (int) DB::valor(
            'SELECT COUNT(*) FROM evaluacion_ejercicio ee JOIN ejercicios e ON e.id = ee.ejercicio_id
             JOIN temas t ON t.id = e.tema_id WHERE ee.evaluacion_id = ? AND e.activo = 1 AND t.publicado = 1',
            [$evaluacionId]
        );
    }

    /**
     * Sortea las preguntas de un intento entre todos los temas (HU-07). Primero toma una
     * pregunta al azar de cada tema, para que la evaluación cubra el temario, y completa
     * el resto al azar entre los ejercicios restantes.
     */
    public static function sortearEjercicios(int $evaluacionId, int $cantidad): array
    {
        $pool = DB::filas(
            'SELECT e.id, e.tema_id FROM evaluacion_ejercicio ee JOIN ejercicios e ON e.id = ee.ejercicio_id
             JOIN temas t ON t.id = e.tema_id WHERE ee.evaluacion_id = ? AND e.activo = 1 AND t.publicado = 1',
            [$evaluacionId]
        );
        shuffle($pool);
        $elegidos = [];
        $temasCubiertos = [];
        foreach ($pool as $fila) {
            if (count($elegidos) >= $cantidad) {
                break;
            }
            if (!isset($temasCubiertos[$fila['tema_id']])) {
                $temasCubiertos[$fila['tema_id']] = true;
                $elegidos[(int) $fila['id']] = true;
            }
        }
        foreach ($pool as $fila) {
            if (count($elegidos) >= $cantidad) {
                break;
            }
            $elegidos[(int) $fila['id']] = true;
        }
        $ids = array_keys($elegidos);
        shuffle($ids);
        return $ids;
    }

    /** Incluye o retira un ejercicio de las evaluaciones activas. */
    public static function sincronizarEjercicio(int $ejercicioId, bool $incluir): void
    {
        if ($incluir) {
            DB::consulta('INSERT IGNORE INTO evaluacion_ejercicio (evaluacion_id, ejercicio_id)
                          SELECT id, ? FROM evaluaciones WHERE activa = 1', [$ejercicioId]);
        } else {
            DB::consulta('DELETE FROM evaluacion_ejercicio WHERE ejercicio_id = ?', [$ejercicioId]);
        }
    }
}
