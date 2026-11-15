<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Grupo
{
    private const BASE = 'SELECT g.*, p.nombre AS profesor,
            (SELECT COUNT(*) FROM usuario_grupo ug WHERE ug.grupo_id = g.id) AS estudiantes
        FROM grupos g LEFT JOIN usuarios p ON p.id = g.profesor_id';

    public static function todos(): array
    {
        return DB::filas(self::BASE . ' ORDER BY g.activo DESC, g.periodo DESC, g.nombre');
    }

    /** El profesor solo ve sus grupos; el administrador ve todos (HU-11). */
    public static function visiblesPara(array $usuario): array
    {
        if ($usuario['rol'] === 'administrador') {
            return self::todos();
        }
        return DB::filas(self::BASE . ' WHERE g.profesor_id = ? ORDER BY g.activo DESC, g.periodo DESC, g.nombre', [$usuario['id']]);
    }

    public static function buscar(int $id): ?array
    {
        return DB::fila(self::BASE . ' WHERE g.id = ?', [$id]);
    }

    public static function visiblePara(int $id, array $usuario): ?array
    {
        $grupo = self::buscar($id);
        if ($grupo === null) {
            return null;
        }
        if ($usuario['rol'] !== 'administrador' && (int) $grupo['profesor_id'] !== (int) $usuario['id']) {
            return null;
        }
        return $grupo;
    }

    public static function existe(string $nombre, string $periodo, int $exceptoId = 0): bool
    {
        return (bool) DB::valor('SELECT 1 FROM grupos WHERE nombre = ? AND periodo = ? AND id <> ?', [$nombre, $periodo, $exceptoId]);
    }

    public static function crear(string $nombre, string $periodo, ?int $profesorId): int
    {
        return DB::insertar('INSERT INTO grupos (nombre, periodo, profesor_id) VALUES (?, ?, ?)', [$nombre, $periodo, $profesorId]);
    }

    public static function actualizar(int $id, string $nombre, string $periodo, ?int $profesorId, bool $activo): void
    {
        DB::consulta('UPDATE grupos SET nombre = ?, periodo = ?, profesor_id = ?, activo = ? WHERE id = ?',
            [$nombre, $periodo, $profesorId, (int) $activo, $id]);
    }

    public static function miembros(int $id): array
    {
        return DB::filas(
            'SELECT u.id, u.nombre, u.correo, u.activo FROM usuario_grupo ug JOIN usuarios u ON u.id = ug.usuario_id
             WHERE ug.grupo_id = ? ORDER BY u.nombre',
            [$id]
        );
    }

    public static function esMiembro(int $grupoId, int $usuarioId): bool
    {
        return (bool) DB::valor('SELECT 1 FROM usuario_grupo WHERE grupo_id = ? AND usuario_id = ?', [$grupoId, $usuarioId]);
    }

    public static function agregarEstudiante(int $grupoId, int $usuarioId): void
    {
        DB::consulta('INSERT IGNORE INTO usuario_grupo (usuario_id, grupo_id) VALUES (?, ?)', [$usuarioId, $grupoId]);
    }

    public static function quitarEstudiante(int $grupoId, int $usuarioId): void
    {
        DB::consulta('DELETE FROM usuario_grupo WHERE grupo_id = ? AND usuario_id = ?', [$grupoId, $usuarioId]);
    }

    public static function deEstudiante(int $usuarioId): array
    {
        return DB::filas(
            'SELECT g.nombre, g.periodo FROM usuario_grupo ug JOIN grupos g ON g.id = ug.grupo_id WHERE ug.usuario_id = ? ORDER BY g.periodo DESC',
            [$usuarioId]
        );
    }
}
