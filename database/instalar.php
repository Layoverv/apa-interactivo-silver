<?php
declare(strict_types=1);

// Instalador por consola: crea la base de datos, carga schema.sql y seed.sql y crea el
// administrador inicial. Uso, desde la carpeta del proyecto:
//
//   php database/instalar.php
//
// En XAMPP para Windows: C:\xampp\php\php.exe database\instalar.php
// También se puede instalar a mano desde phpMyAdmin (ver docs/04-instalacion.md).

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este instalador solo se ejecuta desde la consola.');
}

$raiz = dirname(__DIR__);
$rutaConfig = getenv('APA_CONFIG') ?: $raiz . '/config/config.php';
if (!is_file($rutaConfig)) {
    fwrite(STDERR, "No existe config/config.php. Copia config/config.example.php como config/config.php y ajusta la conexión.\n");
    exit(1);
}
$config = require $rutaConfig;
$db = $config['db'];
$opciones = getopt('', ['nombre:', 'correo:', 'clave:', 'sin-admin']);

function preguntar(string $texto, bool $oculto = false): string
{
    echo $texto;
    if ($oculto && DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec')) {
        shell_exec('stty -echo');
        $valor = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        echo "\n";
        return $valor;
    }
    return trim((string) fgets(STDIN));
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int) $db['puerto']),
        $db['usuario'], $db['clave'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "No fue posible conectar con MySQL/MariaDB. ¿Está iniciado en el panel de XAMPP?\n" . $e->getMessage() . "\n");
    exit(1);
}

$nombreBd = $db['nombre'];
if (!preg_match('/^[A-Za-z0-9_]+$/', $nombreBd)) {
    fwrite(STDERR, "El nombre de la base de datos solo puede tener letras, números y guiones bajos.\n");
    exit(1);
}
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$nombreBd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$nombreBd`");

$tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if ($tablas !== []) {
    fwrite(STDERR, "La base de datos '$nombreBd' ya tiene tablas. Para reinstalar, elimínala primero desde phpMyAdmin (se perderán sus datos).\n");
    exit(1);
}

foreach (['schema.sql', 'seed.sql'] as $archivo) {
    echo "Ejecutando $archivo... ";
    $pdo->exec((string) file_get_contents(__DIR__ . '/' . $archivo));
    echo "listo.\n";
}

$resumen = $pdo->query('SELECT (SELECT COUNT(*) FROM temas) temas, (SELECT COUNT(*) FROM ejercicios) ejercicios, (SELECT COUNT(*) FROM recursos) recursos')->fetch(PDO::FETCH_ASSOC);
echo "Contenido cargado: {$resumen['temas']} temas, {$resumen['ejercicios']} ejercicios y {$resumen['recursos']} recursos.\n";

if (isset($opciones['sin-admin'])) {
    exit(0);
}

echo "\nAdministrador inicial\n";
$nombre = $opciones['nombre'] ?? preguntar('Nombre completo: ');
$correo = mb_strtolower($opciones['correo'] ?? preguntar('Correo: '));
$clave = $opciones['clave'] ?? preguntar('Contraseña (mínimo 8 caracteres): ', true);
if (mb_strlen($nombre) < 3 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($clave) < 8) {
    fwrite(STDERR, "Datos no válidos. La base quedó instalada sin administrador; vuelve a crearlo con:\n  php database/crear_admin.php\n");
    exit(1);
}
$version = $config['politica_version'] ?? date('Y-m-d');
$pdo->prepare('INSERT INTO usuarios (rol_id, nombre, correo, hash_clave, acepto_politica_en, version_politica) VALUES (4, ?, ?, ?, NOW(), ?)')
    ->execute([$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT), $version]);
echo "Administrador creado: $correo\nInstalación terminada.\n";
