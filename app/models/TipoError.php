<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class TipoError
{
    public static function todos(): array
    {
        return DB::filas('SELECT id, nombre, descripcion FROM tipos_error ORDER BY id');
    }

    public static function existe(int $id): bool
    {
        return (bool) DB::valor('SELECT 1 FROM tipos_error WHERE id = ?', [$id]);
    }
}
