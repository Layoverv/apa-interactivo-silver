<?php
declare(strict_types=1);

namespace App\Core;

// Enrutador del front controller: asocia método + ruta con un controlador y declara
// qué roles pueden usarla. El control de acceso se hace aquí, en el servidor (Tabla 21, A01).
final class Router
{
    private array $rutas = [];

    public function get(string $patron, array $accion, ?array $roles = null): void
    {
        $this->agregar('GET', $patron, $accion, $roles);
    }

    public function post(string $patron, array $accion, ?array $roles = null): void
    {
        $this->agregar('POST', $patron, $accion, $roles);
    }

    /**
     * @param array|null $roles null = pública; [] = cualquier usuario autenticado;
     *                          ['estudiante', ...] = solo esos roles.
     */
    private function agregar(string $metodo, string $patron, array $accion, ?array $roles): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $patron) . '$#';
        $this->rutas[] = compact('metodo', 'regex', 'accion', 'roles');
    }

    public function despachar(string $metodo, string $ruta): void
    {
        $metodoPermitido = false;
        foreach ($this->rutas as $r) {
            if (!preg_match($r['regex'], $ruta, $m)) {
                continue;
            }
            if ($r['metodo'] !== $metodo) {
                $metodoPermitido = true;
                continue;
            }
            if ($metodo === 'POST' && !Csrf::valido(Csrf::tokenDePeticion())) {
                throw new HttpException(419, 'La página expiró. Recarga e inténtalo de nuevo.');
            }
            if ($r['roles'] !== null) {
                Auth::exigir($r['roles']);
            }
            $parametros = array_map('intval', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
            [$clase, $funcion] = $r['accion'];
            (new $clase())->$funcion(...$parametros);
            return;
        }
        throw new HttpException($metodoPermitido ? 405 : 404);
    }
}
