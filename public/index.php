<?php
declare(strict_types=1);

// Punto de entrada único (front controller). Toda petición pasa por aquí.

// Con el servidor de desarrollo de PHP (php -S), los archivos estáticos se entregan directo.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Config;
use App\Core\HttpException;
use App\Core\Router;
use App\Core\Sesion;
use App\Core\View;

// Cabeceras de seguridad: sin scripts ni estilos externos o en línea, sin incrustación.
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

Sesion::iniciar();

$router = new Router();
require APP_ROOT . '/app/rutas.php';

$esApi = str_starts_with(ruta_actual(), '/api/');

try {
    $router->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', ruta_actual());
} catch (HttpException $error) {
    $estado = $error->estado;
    if ($esApi) {
        $mensajes = [401 => 'Tu sesión terminó. Vuelve a iniciar sesión.', 403 => 'No tienes permiso para esta acción.',
                     404 => 'No encontrado.', 419 => 'La página expiró. Recarga e inténtalo de nuevo.', 428 => 'Completa el paso pendiente de tu cuenta.'];
        json_respuesta(['error' => $error->getMessage() ?: ($mensajes[$estado] ?? 'Error.')], $estado === 428 ? 403 : $estado);
    }
    if ($estado === 401) {
        Sesion::flash('info', 'Inicia sesión para continuar.');
        redirigir('/ingresar');
    }
    if ($estado === 428) {
        redirigir($error->getMessage());
    }
    http_response_code($estado);
    View::render('errores/error', ['estado' => $estado, 'mensaje' => $error->getMessage(), 'titulo' => 'Error ' . $estado]);
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    if ($esApi) {
        json_respuesta(['error' => 'Ocurrió un error en el servidor.'], 500);
    }
    $detalle = Config::get('entorno') === 'desarrollo' ? $error->getMessage() : '';
    View::render('errores/error', ['estado' => 500, 'mensaje' => $detalle, 'titulo' => 'Error del servidor']);
}
