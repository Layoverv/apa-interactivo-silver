<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Ejercicio;
use App\Models\Intento;
use App\Models\Respuesta;

// Reglas de calificación del proyecto (operaciones «calificar» y «tasaExito» del diagrama
// de clases). Toda la calificación ocurre en el servidor.
final class Calificador
{
    /** Meta de tasa de éxito del proyecto (objetivo general y HU-08, HU-17). */
    public const META = 70.0;

    /** Tasa de éxito = respuestas correctas / total × 100 (HU-07). */
    public static function tasaExito(int $correctas, int $total): float
    {
        return $total > 0 ? round($correctas * 100 / $total, 2) : 0.0;
    }

    public static function cumpleMeta(?float $tasa): bool
    {
        return $tasa !== null && $tasa >= self::META;
    }

    /**
     * Califica un intento de evaluación en una transacción. $enviadas asocia cada
     * ejercicio con la opción elegida. Devuelve la lista de ejercicios sin responder;
     * si no está vacía, no se guarda nada (HU-07: no se envía con preguntas sin responder).
     */
    public static function calificarEvaluacion(array $intento, array $enviadas): array
    {
        $ids = Respuesta::idsDeIntento((int) $intento['id']);
        $faltantes = [];
        foreach ($ids as $id) {
            if (empty($enviadas[$id])) {
                $faltantes[] = $id;
            }
        }
        if ($faltantes !== []) {
            return $faltantes;
        }

        DB::transaccion(static function () use ($intento, $ids, $enviadas): void {
            // Bloquea el intento para que dos envíos simultáneos no lo califiquen dos veces.
            $estado = DB::valor('SELECT estado FROM intentos WHERE id = ? FOR UPDATE', [$intento['id']]);
            if ($estado !== 'en_curso') {
                throw new \DomainException('Esta evaluación ya fue enviada.');
            }
            $correctas = 0;
            foreach ($ids as $ejercicioId) {
                $verificacion = Ejercicio::verificar($ejercicioId, (int) $enviadas[$ejercicioId]);
                if ($verificacion === null) {
                    throw new \InvalidArgumentException('Una de las respuestas no corresponde a su pregunta.');
                }
                Respuesta::registrar((int) $intento['id'], $ejercicioId, (int) $enviadas[$ejercicioId], $verificacion['es_correcta']);
                $correctas += $verificacion['es_correcta'] ? 1 : 0;
            }
            Intento::finalizar((int) $intento['id'], $correctas, count($ids), self::tasaExito($correctas, count($ids)));
        });
        return [];
    }

    /** Aciertos por tema y temas para reforzar (HU-08: aciertos menores del 70 %). */
    public static function resumenPorTema(array $detalle): array
    {
        $temas = [];
        foreach ($detalle as $fila) {
            $id = (int) $fila['tema_id'];
            $temas[$id] ??= ['tema_id' => $id, 'tema' => $fila['tema'], 'total' => 0, 'correctas' => 0];
            $temas[$id]['total']++;
            $temas[$id]['correctas'] += (int) $fila['es_correcta'];
        }
        foreach ($temas as &$t) {
            $t['porcentaje'] = self::tasaExito($t['correctas'], $t['total']);
            $t['reforzar'] = $t['porcentaje'] < self::META;
        }
        unset($t);
        return array_values($temas);
    }
}
