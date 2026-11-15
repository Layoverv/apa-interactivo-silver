<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Tema
{
    public static function publicados(): array
    {
        return DB::filas(
            'SELECT t.id, t.titulo, t.resumen, t.orden,
                    (SELECT COUNT(*) FROM ejercicios e WHERE e.tema_id = t.id AND e.activo = 1) AS ejercicios_activos
             FROM temas t WHERE t.publicado = 1 ORDER BY t.orden, t.id'
        );
    }

    public static function todos(): array
    {
        return DB::filas(
            'SELECT t.id, t.titulo, t.resumen, t.orden, t.publicado,
                    (SELECT COUNT(*) FROM ejercicios e WHERE e.tema_id = t.id AND e.activo = 1) AS ejercicios_activos,
                    (SELECT COUNT(*) FROM ejercicios e WHERE e.tema_id = t.id AND e.activo = 0) AS ejercicios_inactivos,
                    (SELECT COUNT(*) FROM ejemplos x WHERE x.tema_id = t.id) AS ejemplos
             FROM temas t ORDER BY t.orden, t.id'
        );
    }

    public static function buscar(int $id): ?array
    {
        return DB::fila('SELECT * FROM temas WHERE id = ?', [$id]);
    }

    public static function buscarPublicado(int $id): ?array
    {
        return DB::fila('SELECT * FROM temas WHERE id = ? AND publicado = 1', [$id]);
    }

    /** Tema publicado que sigue a otro en el orden de presentación (HU-03). */
    public static function siguiente(array $tema): ?array
    {
        return DB::fila(
            'SELECT id, titulo FROM temas WHERE publicado = 1 AND (orden > ? OR (orden = ? AND id > ?))
             ORDER BY orden, id LIMIT 1',
            [$tema['orden'], $tema['orden'], $tema['id']]
        );
    }

    public static function anterior(array $tema): ?array
    {
        return DB::fila(
            'SELECT id, titulo FROM temas WHERE publicado = 1 AND (orden < ? OR (orden = ? AND id < ?))
             ORDER BY orden DESC, id DESC LIMIT 1',
            [$tema['orden'], $tema['orden'], $tema['id']]
        );
    }

    public static function crear(string $titulo, string $resumen, string $contenido, bool $publicado, int $autor): int
    {
        $orden = (int) DB::valor('SELECT COALESCE(MAX(orden), 0) + 1 FROM temas');
        return DB::insertar(
            'INSERT INTO temas (titulo, resumen, contenido, orden, publicado, creado_por) VALUES (?, ?, ?, ?, ?, ?)',
            [$titulo, $resumen, $contenido, $orden, (int) $publicado, $autor]
        );
    }

    public static function actualizar(int $id, string $titulo, string $resumen, string $contenido, bool $publicado): void
    {
        DB::consulta('UPDATE temas SET titulo = ?, resumen = ?, contenido = ?, publicado = ? WHERE id = ?',
            [$titulo, $resumen, $contenido, (int) $publicado, $id]);
    }

    /** Intercambia el orden con el tema vecino (arriba o abajo). */
    public static function mover(int $id, string $direccion): void
    {
        DB::transaccion(static function () use ($id, $direccion): void {
            $temas = DB::filas('SELECT id FROM temas ORDER BY orden, id FOR UPDATE');
            $ids = array_map(static fn ($t) => (int) $t['id'], $temas);
            $posicion = array_search($id, $ids, true);
            if ($posicion === false) {
                return;
            }
            $destino = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;
            if ($destino < 0 || $destino >= count($ids)) {
                return;
            }
            [$ids[$posicion], $ids[$destino]] = [$ids[$destino], $ids[$posicion]];
            foreach ($ids as $indice => $temaId) {
                DB::consulta('UPDATE temas SET orden = ? WHERE id = ?', [$indice + 1, $temaId]);
            }
        });
    }

    public static function tieneEjercicios(int $id): bool
    {
        return (bool) DB::valor('SELECT 1 FROM ejercicios WHERE tema_id = ? LIMIT 1', [$id]);
    }

    public static function eliminar(int $id): void
    {
        DB::consulta('DELETE FROM temas WHERE id = ?', [$id]);
    }
}
