<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

// Única puerta de acceso a la base de datos. Todas las consultas usan sentencias
// preparadas con parámetros enlazados (Tabla 21, inyección SQL).
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::get('db.host', '127.0.0.1'),
                (int) Config::get('db.puerto', 3306),
                Config::get('db.nombre')
            );
            self::$pdo = new PDO($dsn, (string) Config::get('db.usuario'), (string) Config::get('db.clave'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec("SET time_zone = '-05:00'");
        }
        return self::$pdo;
    }

    public static function consulta(string $sql, array $parametros = []): PDOStatement
    {
        $sentencia = self::pdo()->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia;
    }

    public static function fila(string $sql, array $parametros = []): ?array
    {
        $fila = self::consulta($sql, $parametros)->fetch();
        return $fila === false ? null : $fila;
    }

    public static function filas(string $sql, array $parametros = []): array
    {
        return self::consulta($sql, $parametros)->fetchAll();
    }

    public static function valor(string $sql, array $parametros = []): mixed
    {
        $valor = self::consulta($sql, $parametros)->fetchColumn();
        return $valor === false ? null : $valor;
    }

    public static function insertar(string $sql, array $parametros = []): int
    {
        self::consulta($sql, $parametros);
        return (int) self::pdo()->lastInsertId();
    }

    /** Ejecuta varias operaciones como una sola: todas o ninguna. */
    public static function transaccion(callable $trabajo): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $resultado = $trabajo();
            $pdo->commit();
            return $resultado;
        } catch (\Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
    }

    /** Genera marcadores ?, ?, ? para una lista IN (...). */
    public static function marcadores(array $valores): string
    {
        return implode(', ', array_fill(0, max(1, count($valores)), '?'));
    }
}
