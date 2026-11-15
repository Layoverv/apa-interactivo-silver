<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Ejemplo
{
    public static function deTema(int $temaId): array
    {
        return DB::filas('SELECT * FROM ejemplos WHERE tema_id = ? ORDER BY orden, id', [$temaId]);
    }

    public static function buscar(int $id): ?array
    {
        return DB::fila('SELECT * FROM ejemplos WHERE id = ?', [$id]);
    }

    public static function crear(int $temaId, bool $esCorrecto, string $texto, string $explicacion): int
    {
        $orden = (int) DB::valor('SELECT COALESCE(MAX(orden), 0) + 1 FROM ejemplos WHERE tema_id = ?', [$temaId]);
        return DB::insertar('INSERT INTO ejemplos (tema_id, es_correcto, texto, explicacion, orden) VALUES (?, ?, ?, ?, ?)',
            [$temaId, (int) $esCorrecto, $texto, $explicacion, $orden]);
    }

    public static function actualizar(int $id, bool $esCorrecto, string $texto, string $explicacion): void
    {
        DB::consulta('UPDATE ejemplos SET es_correcto = ?, texto = ?, explicacion = ? WHERE id = ?',
            [(int) $esCorrecto, $texto, $explicacion, $id]);
    }

    public static function eliminar(int $id): void
    {
        DB::consulta('DELETE FROM ejemplos WHERE id = ?', [$id]);
    }
}
