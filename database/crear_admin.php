<?php
declare(strict_types=1);

// Crea un administrador adicional o recupera el acceso si se perdió la contraseña.
// Uso: php database/crear_admin.php   (C:\xampp\php\php.exe database\crear_admin.php)

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo se ejecuta desde la consola.');
}
$rutaConfig = getenv('APA_CONFIG') ?: dirname(__DIR__) . '/config/config.php';
if (!is_file($rutaConfig)) {
    fwrite(STDERR, "No existe config/config.php. Copia config/config.example.php como config/config.php y ajusta la conexión.\n");
    exit(1);
}
$config = require $rutaConfig;
$db = $config['db'];
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], (int) $db['puerto'], $db['nombre']),
        $db['usuario'], $db['clave'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    fwrite(STDERR, "No fue posible conectar con la base de datos. ¿Está iniciado MySQL en XAMPP y ya se instaló la base?\n" . $e->getMessage() . "\n");
    exit(1);
}

$o = getopt('', ['nombre:', 'correo:', 'clave:']);
echo 'Nombre completo: ';
$nombre = $o['nombre'] ?? trim((string) fgets(STDIN));
if (isset($o['nombre'])) { echo "$nombre\n"; }
echo 'Correo: ';
$correo = mb_strtolower($o['correo'] ?? trim((string) fgets(STDIN)));
if (isset($o['correo'])) { echo "$correo\n"; }
echo 'Contraseña (mínimo 8 caracteres): ';
$clave = $o['clave'] ?? trim((string) fgets(STDIN));
echo "\n";
if (mb_strlen($nombre) < 3 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($clave) < 8) {
    fwrite(STDERR, "Datos no válidos.\n");
    exit(1);
}
$hash = password_hash($clave, PASSWORD_DEFAULT);
$existe = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ?');
$existe->execute([$correo]);
if ($id = $existe->fetchColumn()) {
    $pdo->prepare('UPDATE usuarios SET rol_id = 4, activo = 1, hash_clave = ?, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?')->execute([$hash, $id]);
    echo "El usuario ya existía: ahora es administrador activo y tiene la nueva contraseña.\n";
} else {
    $pdo->prepare('INSERT INTO usuarios (rol_id, nombre, correo, hash_clave, acepto_politica_en, version_politica) VALUES (4, ?, ?, ?, NOW(), ?)')
        ->execute([$nombre, $correo, $hash, $config['politica_version'] ?? date('Y-m-d')]);
    echo "Administrador creado: $correo\n";
}
