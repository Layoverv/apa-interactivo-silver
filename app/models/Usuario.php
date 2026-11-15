<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Usuario
{
    private const CAMPOS = 'u.id, u.rol_id, r.clave AS rol, r.nombre AS rol_nombre, u.nombre, u.correo, u.activo,
        u.acepto_politica_en, u.version_politica, u.debe_cambiar_clave, u.intentos_fallidos, u.bloqueado_hasta,
        u.ultimo_acceso, u.creado_en';

    public static function buscar(int $id): ?array
    {
        return DB::fila('SELECT ' . self::CAMPOS . ' FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?', [$id]);
    }

    /** Incluye el hash de la clave: solo para verificar credenciales. */
    public static function buscarParaIngreso(string $correo): ?array
    {
        return DB::fila('SELECT ' . self::CAMPOS . ', u.hash_clave FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.correo = ?', [$correo]);
    }

    public static function existeCorreo(string $correo, ?int $exceptoId = null): bool
    {
        return (bool) DB::valor('SELECT 1 FROM usuarios WHERE correo = ? AND id <> ?', [$correo, $exceptoId ?? 0]);
    }

    public static function crear(array $d): int
    {
        return DB::insertar(
            'INSERT INTO usuarios (rol_id, nombre, correo, hash_clave, acepto_politica_en, version_politica, debe_cambiar_clave)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$d['rol_id'], $d['nombre'], $d['correo'], password_hash($d['clave'], PASSWORD_DEFAULT),
             $d['acepto_politica_en'] ?? null, $d['version_politica'] ?? null, (int) ($d['debe_cambiar_clave'] ?? 0)]
        );
    }

    public static function actualizar(int $id, string $nombre, string $correo, int $rolId): void
    {
        DB::consulta('UPDATE usuarios SET nombre = ?, correo = ?, rol_id = ? WHERE id = ?', [$nombre, $correo, $rolId, $id]);
    }

    public static function cambiarEstado(int $id, bool $activo): void
    {
        DB::consulta('UPDATE usuarios SET activo = ? WHERE id = ?', [(int) $activo, $id]);
    }

    public static function cambiarClave(int $id, string $clave, bool $temporal): void
    {
        DB::consulta('UPDATE usuarios SET hash_clave = ?, debe_cambiar_clave = ? WHERE id = ?',
            [password_hash($clave, PASSWORD_DEFAULT), (int) $temporal, $id]);
    }

    public static function verificarClave(int $id, string $clave): bool
    {
        $hash = DB::valor('SELECT hash_clave FROM usuarios WHERE id = ?', [$id]);
        return is_string($hash) && password_verify($clave, $hash);
    }

    public static function aceptarPolitica(int $id, string $version): void
    {
        DB::consulta('UPDATE usuarios SET acepto_politica_en = NOW(), version_politica = ? WHERE id = ?', [$version, $id]);
    }

    public static function registrarFallo(int $id, int $maximo, int $minutosBloqueo): void
    {
        DB::consulta(
            'UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1,
                bloqueado_hasta = IF(intentos_fallidos >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), bloqueado_hasta),
                intentos_fallidos = IF(intentos_fallidos >= ?, 0, intentos_fallidos)
             WHERE id = ?',
            [$maximo, $minutosBloqueo, $maximo, $id]
        );
    }

    public static function registrarIngreso(int $id, string $hashActual, string $clave): void
    {
        DB::consulta('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?', [$id]);
        if (password_needs_rehash($hashActual, PASSWORD_DEFAULT)) {
            DB::consulta('UPDATE usuarios SET hash_clave = ? WHERE id = ?', [password_hash($clave, PASSWORD_DEFAULT), $id]);
        }
    }

    public static function estaBloqueado(array $usuario): bool
    {
        return $usuario['bloqueado_hasta'] !== null && strtotime($usuario['bloqueado_hasta']) > time();
    }

    public static function listar(?string $buscar = null, ?string $rol = null): array
    {
        $sql = 'SELECT ' . self::CAMPOS . ' FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE 1 = 1';
        $p = [];
        if ($buscar !== null && $buscar !== '') {
            $sql .= ' AND (u.nombre LIKE ? OR u.correo LIKE ?)';
            $p[] = '%' . $buscar . '%';
            $p[] = '%' . $buscar . '%';
        }
        if ($rol !== null && $rol !== '') {
            $sql .= ' AND r.clave = ?';
            $p[] = $rol;
        }
        return DB::filas($sql . ' ORDER BY u.activo DESC, u.nombre', $p);
    }

    public static function porRol(string $rol, bool $soloActivos = true): array
    {
        return DB::filas(
            'SELECT u.id, u.nombre, u.correo FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE r.clave = ?' . ($soloActivos ? ' AND u.activo = 1' : '') . ' ORDER BY u.nombre',
            [$rol]
        );
    }

    public static function roles(): array
    {
        return DB::filas('SELECT id, clave, nombre FROM roles ORDER BY id');
    }

    public static function rolIdPorClave(string $clave): ?int
    {
        $id = DB::valor('SELECT id FROM roles WHERE clave = ?', [$clave]);
        return $id === null ? null : (int) $id;
    }

    public static function contarActivosPorRol(): array
    {
        return DB::filas(
            'SELECT r.nombre, COUNT(u.id) AS total FROM roles r
             LEFT JOIN usuarios u ON u.rol_id = r.id AND u.activo = 1 GROUP BY r.id ORDER BY r.id'
        );
    }
}
