<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Recurso
{
    public static function publicados(): array
    {
        return DB::filas('SELECT * FROM recursos WHERE publicado = 1 ORDER BY orden, id');
    }

    public static function todos(): array
    {
        return DB::filas('SELECT * FROM recursos ORDER BY orden, id');
    }

    public static function buscar(int $id): ?array
    {
        return DB::fila('SELECT * FROM recursos WHERE id = ?', [$id]);
    }

    public static function existeTitulo(string $titulo, int $exceptoId = 0): bool
    {
        return (bool) DB::valor('SELECT 1 FROM recursos WHERE titulo = ? AND id <> ?', [$titulo, $exceptoId]);
    }

    public static function crear(array $d): int
    {
        $orden = (int) DB::valor('SELECT COALESCE(MAX(orden), 0) + 1 FROM recursos');
        return DB::insertar('INSERT INTO recursos (titulo, descripcion, url, tipo, orden, publicado) VALUES (?, ?, ?, ?, ?, ?)',
            [$d['titulo'], $d['descripcion'], $d['url'], $d['tipo'], $orden, (int) $d['publicado']]);
    }

    public static function actualizar(int $id, array $d): void
    {
        DB::consulta('UPDATE recursos SET titulo = ?, descripcion = ?, url = ?, tipo = ?, publicado = ? WHERE id = ?',
            [$d['titulo'], $d['descripcion'], $d['url'], $d['tipo'], (int) $d['publicado'], $id]);
    }
}
