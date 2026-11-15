<?php
declare(strict_types=1);

// Arranque común: autocarga de clases, configuración, manejo de errores y sesión.

define('APP_ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $clase): void {
    // App\Controllers\AuthController -> app/controllers/AuthController.php
    if (strncmp($clase, 'App\\', 4) !== 0) {
        return;
    }
    $partes = explode('\\', substr($clase, 4));
    $nombre = array_pop($partes);
    $carpeta = strtolower(implode('/', $partes));
    $archivo = APP_ROOT . '/app/' . ($carpeta !== '' ? $carpeta . '/' : '') . $nombre . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

require APP_ROOT . '/app/helpers.php';

$rutaConfig = getenv('APA_CONFIG') ?: APP_ROOT . '/config/config.php';
if (!is_file($rutaConfig)) {
    http_response_code(500);
    exit('Falta config/config.php. Copia config/config.example.php como config/config.php y ajusta la conexión.');
}
App\Core\Config::cargar(require $rutaConfig);

$desarrollo = App\Core\Config::get('entorno') === 'desarrollo';
error_reporting(E_ALL);
ini_set('display_errors', $desarrollo ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('America/Bogota');
mb_internal_encoding('UTF-8');
