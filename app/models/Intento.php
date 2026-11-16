<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Intento
{
    public static function crear(int $usuarioId, string $modo, ?int $evaluacionId, ?int $temaId, ?int $dificultad, int $total): int
    {
        return DB::insertar(
            'INSERT INTO intentos (usuario_id, evaluacion_id, tema_id, modo, dificultad, total_preguntas) VALUES (?, ?, ?, ?, ?, ?)',
            [$usuarioId, $evaluacionId, $temaId, $modo, $dificultad, $total]
        );
    }

    /** Busca un intento solo si pertenece al usuario: nadie consulta intentos ajenos. */
    public static function delUsuario(int $id, int $usuarioId, string $modo): ?array
    {
        return DB::fila(
            'SELECT i.*, t.titulo AS tema FROM intentos i LEFT JOIN temas t ON t.id = i.tema_id
             WHERE i.id = ? AND i.usuario_id = ? AND i.modo = ?',
            [$id, $usuarioId, $modo]
        );
    }

    public static function evaluacionEnCurso(int $usuarioId): ?array
    {
        return DB::fila(
            "SELECT * FROM intentos WHERE usuario_id = ? AND modo = 'evaluacion' AND estado = 'en_curso' ORDER BY id DESC LIMIT 1",
            [$usuarioId]
        );
    }

    /** Cierra el intento con su puntaje. La condición de estado evita calificarlo dos veces. */
    public static function finalizar(int $id, int $correctas, int $total, float $tasa): bool
    {
        return DB::consulta(
            "UPDATE intentos SET estado = 'finalizado', correctas = ?, total_preguntas = ?, puntaje = ?, finalizado_en = NOW()
             WHERE id = ? AND estado = 'en_curso'",
            [$correctas, $total, $tasa, $id]
        )->rowCount() === 1;
    }

    public static function actualizarConteo(int $id): void
    {
        DB::consulta(
            'UPDATE intentos i SET correctas = (SELECT COUNT(*) FROM respuestas r WHERE r.intento_id = i.id AND r.es_correcta = 1)
             WHERE i.id = ?',
            [$id]
        );
    }

    public static function conteo(int $id): array
    {
        $fila = DB::fila(
            'SELECT COUNT(*) AS total, SUM(opcion_id IS NOT NULL) AS respondidas, SUM(es_correcta = 1) AS correctas
             FROM respuestas WHERE intento_id = ?',
            [$id]
        );
        return ['total' => (int) $fila['total'], 'respondidas' => (int) $fila['respondidas'], 'correctas' => (int) $fila['correctas']];
    }

    // ---- Progreso del estudiante (HU-09) ----

    public static function historialEvaluaciones(int $usuarioId): array
    {
        return DB::filas(
            "SELECT id, correctas, total_preguntas, puntaje, iniciado_en, finalizado_en
             FROM intentos WHERE usuario_id = ? AND modo = 'evaluacion' AND estado = 'finalizado'
             ORDER BY finalizado_en, id",
            [$usuarioId]
        );
    }

    public static function resumenPractica(int $usuarioId): array
    {
        return DB::fila(
            "SELECT COUNT(DISTINCT CASE WHEN r.opcion_id IS NOT NULL THEN i.id END) AS sesiones, COUNT(r.opcion_id) AS respondidas, COALESCE(SUM(r.es_correcta = 1), 0) AS correctas
             FROM intentos i LEFT JOIN respuestas r ON r.intento_id = i.id
             WHERE i.usuario_id = ? AND i.modo = 'practica'",
            [$usuarioId]
        ) ?? ['sesiones' => 0, 'respondidas' => 0, 'correctas' => 0];
    }

    /** Avance por tema: aciertos en práctica y en evaluaciones finalizadas. */
    public static function avancePorTema(int $usuarioId): array
    {
        return DB::filas(
            "SELECT t.id, t.titulo,
                    (SELECT COUNT(*) FROM ejercicios e WHERE e.tema_id = t.id AND e.activo = 1) AS ejercicios_activos,
                    COUNT(DISTINCT CASE WHEN i.modo = 'practica' AND r.opcion_id IS NOT NULL THEN r.ejercicio_id END) AS practicados,
                    SUM(i.modo = 'practica' AND r.opcion_id IS NOT NULL) AS practica_respondidas,
                    SUM(i.modo = 'practica' AND r.es_correcta = 1) AS practica_correctas,
                    SUM(i.modo = 'evaluacion' AND i.estado = 'finalizado') AS evaluacion_respondidas,
                    SUM(i.modo = 'evaluacion' AND i.estado = 'finalizado' AND r.es_correcta = 1) AS evaluacion_correctas
             FROM temas t
             LEFT JOIN ejercicios e ON e.tema_id = t.id
             LEFT JOIN (respuestas r JOIN intentos i ON i.id = r.intento_id AND i.usuario_id = ?)
                    ON r.ejercicio_id = e.id
             WHERE t.publicado = 1
             GROUP BY t.id ORDER BY t.orden, t.id",
            [$usuarioId]
        );
    }

    // ---- Reportes del profesor (HU-11, HU-12) ----

    /** Por estudiante del grupo: último puntaje, mejor tasa de éxito y número de intentos. */
    public static function estudiantesDeGrupo(int $grupoId): array
    {
        return DB::filas(
            "SELECT u.id, u.nombre, u.correo, u.activo,
                    COUNT(i.id) AS intentos,
                    MAX(i.puntaje) AS mejor,
                    (SELECT i2.puntaje FROM intentos i2 WHERE i2.usuario_id = u.id AND i2.modo = 'evaluacion'
                       AND i2.estado = 'finalizado' ORDER BY i2.finalizado_en DESC, i2.id DESC LIMIT 1) AS ultimo,
                    MAX(i.finalizado_en) AS ultima_fecha
             FROM usuario_grupo ug
             JOIN usuarios u ON u.id = ug.usuario_id
             LEFT JOIN intentos i ON i.usuario_id = u.id AND i.modo = 'evaluacion' AND i.estado = 'finalizado'
             WHERE ug.grupo_id = ?
             GROUP BY u.id ORDER BY u.nombre",
            [$grupoId]
        );
    }

    /** Porcentaje de error por tema o por tipo de error en las evaluaciones del grupo (HU-12). */
    public static function erroresDeGrupo(int $grupoId, string $agrupar): array
    {
        $columna = $agrupar === 'tipo' ? 'te.nombre' : 't.titulo';
        $agrupacion = $agrupar === 'tipo' ? 'te.id' : 't.id';
        return DB::filas(
            "SELECT $columna AS nombre, COUNT(*) AS respuestas, SUM(r.es_correcta = 0) AS errores,
                    ROUND(100 * SUM(r.es_correcta = 0) / COUNT(*), 1) AS porcentaje
             FROM usuario_grupo ug
             JOIN intentos i ON i.usuario_id = ug.usuario_id AND i.modo = 'evaluacion' AND i.estado = 'finalizado'
             JOIN respuestas r ON r.intento_id = i.id
             JOIN ejercicios e ON e.id = r.ejercicio_id
             JOIN temas t ON t.id = e.tema_id
             JOIN tipos_error te ON te.id = e.tipo_error_id
             WHERE ug.grupo_id = ?
             GROUP BY $agrupacion ORDER BY porcentaje DESC, errores DESC, nombre",
            [$grupoId]
        );
    }

    // ---- Estadísticas generales (HU-17) ----

    public static function estadisticas(?int $grupoId, ?string $desde, ?string $hasta): array
    {
        [$filtro, $p] = self::filtroEvaluaciones($grupoId, $desde, $hasta);
        $fila = DB::fila(
            "SELECT COUNT(*) AS evaluaciones, COUNT(DISTINCT i.usuario_id) AS estudiantes, ROUND(AVG(i.puntaje), 1) AS tasa_promedio
             FROM intentos i WHERE $filtro",
            $p
        );
        return [
            'evaluaciones'  => (int) $fila['evaluaciones'],
            'estudiantes'   => (int) $fila['estudiantes'],
            'tasa_promedio' => $fila['tasa_promedio'] === null ? null : (float) $fila['tasa_promedio'],
        ];
    }

    public static function erroresGenerales(?int $grupoId, ?string $desde, ?string $hasta): array
    {
        [$filtro, $p] = self::filtroEvaluaciones($grupoId, $desde, $hasta);
        return DB::filas(
            "SELECT t.titulo AS nombre, COUNT(*) AS respuestas, SUM(r.es_correcta = 0) AS errores,
                    ROUND(100 * SUM(r.es_correcta = 0) / COUNT(*), 1) AS porcentaje
             FROM intentos i JOIN respuestas r ON r.intento_id = i.id
             JOIN ejercicios e ON e.id = r.ejercicio_id JOIN temas t ON t.id = e.tema_id
             WHERE $filtro GROUP BY t.id ORDER BY porcentaje DESC",
            $p
        );
    }

    private static function filtroEvaluaciones(?int $grupoId, ?string $desde, ?string $hasta): array
    {
        $filtro = "i.modo = 'evaluacion' AND i.estado = 'finalizado'";
        $p = [];
        if ($grupoId !== null) {
            $filtro .= ' AND i.usuario_id IN (SELECT usuario_id FROM usuario_grupo WHERE grupo_id = ?)';
            $p[] = $grupoId;
        }
        if ($desde !== null) {
            $filtro .= ' AND i.finalizado_en >= ?';
            $p[] = $desde . ' 00:00:00';
        }
        if ($hasta !== null) {
            $filtro .= ' AND i.finalizado_en <= ?';
            $p[] = $hasta . ' 23:59:59';
        }
        return [$filtro, $p];
    }
}
