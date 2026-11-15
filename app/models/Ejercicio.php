<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Ejercicio
{
    /** Ejercicios activos de un tema publicado, opcionalmente de un solo nivel (HU-05). */
    public static function idsParaPractica(int $temaId, ?int $dificultad): array
    {
        $sql = 'SELECT e.id FROM ejercicios e JOIN temas t ON t.id = e.tema_id
                WHERE e.tema_id = ? AND e.activo = 1 AND t.publicado = 1';
        $p = [$temaId];
        if ($dificultad !== null) {
            $sql .= ' AND e.dificultad = ?';
            $p[] = $dificultad;
        }
        return array_map('intval', array_column(DB::filas($sql . ' ORDER BY e.dificultad, e.id', $p), 'id'));
    }

    /** Cantidad de ejercicios activos por tema y nivel, para el formulario de práctica. */
    public static function conteoPorTemaYNivel(): array
    {
        $conteo = [];
        foreach (DB::filas('SELECT tema_id, dificultad, COUNT(*) AS total FROM ejercicios WHERE activo = 1 GROUP BY tema_id, dificultad') as $f) {
            $conteo[(int) $f['tema_id']][(int) $f['dificultad']] = (int) $f['total'];
        }
        return $conteo;
    }

    /**
     * Enunciados y opciones SIN la respuesta correcta ni la explicación: es lo único que
     * se envía al navegador antes de que el estudiante responda.
     */
    public static function paraResolver(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marcadores = DB::marcadores($ids);
        $ejercicios = DB::filas(
            "SELECT e.id, e.tipo, e.dificultad, e.enunciado, t.titulo AS tema, te.nombre AS tipo_error
             FROM ejercicios e JOIN temas t ON t.id = e.tema_id JOIN tipos_error te ON te.id = e.tipo_error_id
             WHERE e.id IN ($marcadores)",
            $ids
        );
        $opciones = DB::filas("SELECT id, ejercicio_id, texto FROM opciones WHERE ejercicio_id IN ($marcadores) ORDER BY orden, id", $ids);
        $porId = [];
        foreach ($ejercicios as $e) {
            $e['opciones'] = [];
            $porId[(int) $e['id']] = $e;
        }
        foreach ($opciones as $o) {
            $porId[(int) $o['ejercicio_id']]['opciones'][] = ['id' => (int) $o['id'], 'texto' => $o['texto']];
        }
        // Se respeta el orden recibido (el del intento).
        $resultado = [];
        foreach ($ids as $id) {
            if (isset($porId[$id])) {
                $resultado[] = $porId[$id];
            }
        }
        return $resultado;
    }

    /**
     * Operación «verificar» del diagrama de clases: determina si una opción es correcta y
     * devuelve la retroalimentación. Devuelve null si la opción no pertenece al ejercicio.
     */
    public static function verificar(int $ejercicioId, int $opcionId): ?array
    {
        $elegida = DB::fila('SELECT id, es_correcta FROM opciones WHERE id = ? AND ejercicio_id = ?', [$opcionId, $ejercicioId]);
        if ($elegida === null) {
            return null;
        }
        $correcta = DB::fila('SELECT id, texto FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$ejercicioId]);
        $explicacion = (string) DB::valor('SELECT explicacion FROM ejercicios WHERE id = ?', [$ejercicioId]);
        return [
            'es_correcta'        => (bool) $elegida['es_correcta'],
            'opcion_correcta_id' => $correcta ? (int) $correcta['id'] : null,
            'opcion_correcta'    => $correcta['texto'] ?? '',
            'explicacion'        => $explicacion,
        ];
    }

    public static function buscar(int $id): ?array
    {
        $ejercicio = DB::fila('SELECT * FROM ejercicios WHERE id = ?', [$id]);
        if ($ejercicio === null) {
            return null;
        }
        $ejercicio['opciones'] = DB::filas('SELECT id, texto, es_correcta, orden FROM opciones WHERE ejercicio_id = ? ORDER BY orden, id', [$id]);
        $ejercicio['en_evaluacion'] = (bool) DB::valor('SELECT 1 FROM evaluacion_ejercicio WHERE ejercicio_id = ? LIMIT 1', [$id]);
        $ejercicio['tiene_respuestas'] = self::tieneRespuestas($id);
        return $ejercicio;
    }

    public static function listar(?int $temaId, ?int $dificultad, ?string $estado): array
    {
        $sql = 'SELECT e.id, e.enunciado, e.dificultad, e.tipo, e.activo, t.titulo AS tema, te.nombre AS tipo_error,
                       (SELECT COUNT(*) FROM evaluacion_ejercicio ee WHERE ee.ejercicio_id = e.id) > 0 AS en_evaluacion,
                       (SELECT COUNT(*) FROM respuestas r WHERE r.ejercicio_id = e.id AND r.opcion_id IS NOT NULL) AS veces_respondido
                FROM ejercicios e JOIN temas t ON t.id = e.tema_id JOIN tipos_error te ON te.id = e.tipo_error_id WHERE 1 = 1';
        $p = [];
        if ($temaId !== null) {
            $sql .= ' AND e.tema_id = ?';
            $p[] = $temaId;
        }
        if ($dificultad !== null) {
            $sql .= ' AND e.dificultad = ?';
            $p[] = $dificultad;
        }
        if ($estado === 'activos') {
            $sql .= ' AND e.activo = 1';
        } elseif ($estado === 'inactivos') {
            $sql .= ' AND e.activo = 0';
        }
        return DB::filas($sql . ' ORDER BY t.orden, e.dificultad, e.id', $p);
    }

    public static function tieneRespuestas(int $id): bool
    {
        return (bool) DB::valor('SELECT 1 FROM respuestas WHERE ejercicio_id = ? LIMIT 1', [$id]);
    }

    /**
     * Crea o actualiza un ejercicio con sus opciones en una sola transacción.
     * Si el ejercicio ya fue respondido, solo se permite corregir los textos: cambiar
     * la cantidad de opciones o la correcta alteraría los resultados ya guardados.
     */
    public static function guardar(?int $id, array $d, array $opciones, int $correcta, bool $enEvaluacion, int $autor): int
    {
        return DB::transaccion(static function () use ($id, $d, $opciones, $correcta, $enEvaluacion, $autor): int {
            if ($id === null) {
                $id = DB::insertar(
                    'INSERT INTO ejercicios (tema_id, tipo_error_id, tipo, dificultad, enunciado, explicacion, activo, creado_por)
                     VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
                    [$d['tema_id'], $d['tipo_error_id'], $d['tipo'], $d['dificultad'], $d['enunciado'], $d['explicacion'], $autor]
                );
                foreach ($opciones as $i => $texto) {
                    DB::consulta('INSERT INTO opciones (ejercicio_id, texto, es_correcta, orden) VALUES (?, ?, ?, ?)',
                        [$id, $texto, (int) ($i === $correcta), $i + 1]);
                }
            } else {
                DB::consulta(
                    'UPDATE ejercicios SET tema_id = ?, tipo_error_id = ?, tipo = ?, dificultad = ?, enunciado = ?, explicacion = ? WHERE id = ?',
                    [$d['tema_id'], $d['tipo_error_id'], $d['tipo'], $d['dificultad'], $d['enunciado'], $d['explicacion'], $id]
                );
                $actuales = DB::filas('SELECT id, es_correcta FROM opciones WHERE ejercicio_id = ? ORDER BY orden, id', [$id]);
                if (self::tieneRespuestas($id)) {
                    $correctaActual = array_search(1, array_map(static fn ($o) => (int) $o['es_correcta'], $actuales), true);
                    if (count($actuales) !== count($opciones) || $correctaActual !== $correcta) {
                        throw new \DomainException('Este ejercicio ya fue respondido: solo puedes corregir los textos. Para cambiar las opciones o la respuesta correcta, desactívalo y crea uno nuevo.');
                    }
                    foreach ($actuales as $i => $opcion) {
                        DB::consulta('UPDATE opciones SET texto = ? WHERE id = ?', [$opciones[$i], $opcion['id']]);
                    }
                } else {
                    DB::consulta('DELETE FROM opciones WHERE ejercicio_id = ?', [$id]);
                    foreach ($opciones as $i => $texto) {
                        DB::consulta('INSERT INTO opciones (ejercicio_id, texto, es_correcta, orden) VALUES (?, ?, ?, ?)',
                            [$id, $texto, (int) ($i === $correcta), $i + 1]);
                    }
                }
            }
            Evaluacion::sincronizarEjercicio($id, $enEvaluacion);
            return $id;
        });
    }

    public static function cambiarEstado(int $id, bool $activo): void
    {
        DB::consulta('UPDATE ejercicios SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }
}
