<?php
declare(strict_types=1);

// Pruebas funcionales automatizadas de APA Interactivo.
//
// Crean una base de datos de prueba separada (apa_pruebas), la instalan desde cero con
// database/schema.sql y database/seed.sql, levantan el servidor de PHP y recorren la
// aplicación por HTTP como lo haría un navegador. Cada prueba se identifica con la
// historia de usuario y el criterio de aceptación (Tabla 13) que verifica.
//
// Uso:  php tests/ejecutar.php            (XAMPP: C:\xampp\php\php.exe tests\ejecutar.php)
// Requiere MySQL/MariaDB en marcha y config/config.php con un usuario que pueda crear bases.

require __DIR__ . '/ClienteHttp.php';

$raiz = dirname(__DIR__);
$configBase = require (getenv('APA_CONFIG') ?: $raiz . '/config/config.php');
$nombreBd = getenv('APA_DB_PRUEBAS') ?: 'apa_pruebas';
$salida = __DIR__ . '/salida';
@mkdir($salida, 0777, true);
$rutaConfig = $salida . '/config.pruebas.php';

function escribirConfig(array $base, string $nombreBd, string $ruta, float $minutosSesion = 30): void
{
    $base['db']['nombre'] = $nombreBd;
    $base['entorno'] = 'desarrollo';
    $base['sesion_minutos'] = $minutosSesion;
    file_put_contents($ruta, '<?php return ' . var_export($base, true) . ';');
}
escribirConfig($configBase, $nombreBd, $rutaConfig);

// ---------- Instalación desde cero ----------
$db = $configBase['db'];
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int) $db['puerto']), $db['usuario'], $db['clave'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec("DROP DATABASE IF EXISTS `$nombreBd`");
$pdo->exec("CREATE DATABASE `$nombreBd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$nombreBd`");
$pdo->exec((string) file_get_contents($raiz . '/database/schema.sql'));
$pdo->exec((string) file_get_contents($raiz . '/database/seed.sql'));
$pdo->prepare("INSERT INTO usuarios (rol_id, nombre, correo, hash_clave, acepto_politica_en, version_politica) VALUES (4, 'Administrador Pruebas', 'admin@pruebas.co', ?, NOW(), 'pruebas')")
    ->execute([password_hash('Admin12345', PASSWORD_DEFAULT)]);

function q(string $sql, array $p = []): array
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchAll();
}
function v(string $sql, array $p = []): mixed
{
    $f = q($sql, $p);
    return $f === [] ? null : array_values($f[0])[0];
}

// ---------- Servidor de PHP ----------
$socket = stream_socket_server('tcp://127.0.0.1:0');
$puerto = (int) explode(':', stream_socket_get_name($socket, false))[1];
fclose($socket);
$entorno = array_merge(getenv(), ['APA_CONFIG' => $rutaConfig]);
$servidor = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', $raiz . '/public', $raiz . '/public/index.php'],
    [0 => ['pipe', 'r'], 1 => ['file', $salida . '/servidor.log', 'w'], 2 => ['file', $salida . '/servidor.log', 'a']],
    $tuberias, $raiz, $entorno
);
$base = "http://127.0.0.1:$puerto";
for ($i = 0; $i < 50 && @file_get_contents($base . '/politica-datos') === false; $i++) {
    usleep(100000);
}
register_shutdown_function(static function () use ($servidor): void {
    proc_terminate($servidor);
});

// ---------- Mini marco de pruebas ----------
$resultados = [];
final class Fallo extends Exception {}
function afirmar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new Fallo($mensaje);
    }
}
function prueba(string $id, string $descripcion, callable $fn): void
{
    global $resultados;
    try {
        $fn();
        $resultados[] = [$id, $descripcion, true, ''];
        echo "  ✓ [$id] $descripcion\n";
    } catch (Throwable $e) {
        $resultados[] = [$id, $descripcion, false, $e->getMessage()];
        echo "  ✗ [$id] $descripcion\n      → " . $e->getMessage() . "\n";
    }
}
function nuevo(): ClienteHttp
{
    global $base;
    return new ClienteHttp($base);
}
function registrar(ClienteHttp $c, string $nombre, string $correo, string $clave = 'Clave12345', bool $acepta = true, ?string $confirmacion = null): ClienteHttp
{
    $c->get('/registro');
    $datos = ['nombre' => $nombre, 'correo' => $correo, 'clave' => $clave, 'confirmacion' => $confirmacion ?? $clave];
    if ($acepta) {
        $datos['acepta_politica'] = '1';
    }
    return $c->post('/registro', $datos);
}
function idIntento(ClienteHttp $c): int
{
    return (int) basename((string) parse_url((string) $c->redireccion, PHP_URL_PATH));
}

echo "\nAPA Interactivo — pruebas funcionales (base de datos: $nombreBd, servidor: $base)\n\n";

// ======================= Instalación y datos =======================
echo "Instalación y base de datos\n";
prueba('BD-01', 'La instalación limpia crea las 14 tablas de la Tabla 19', function () {
    $tablas = array_map(static fn ($f) => array_values($f)[0], q('SHOW TABLES'));
    $esperadas = ['roles', 'usuarios', 'grupos', 'usuario_grupo', 'temas', 'ejemplos', 'tipos_error', 'ejercicios', 'opciones', 'evaluaciones', 'evaluacion_ejercicio', 'intentos', 'respuestas', 'recursos'];
    afirmar(count(array_diff($esperadas, $tablas)) === 0 && count($tablas) === 14, 'Tablas: ' . implode(', ', $tablas));
});
prueba('BD-02', 'Banco ≥ 30 ejercicios, ≥ 6 por tema, con los tres niveles y tipos de error', function () {
    afirmar((int) v('SELECT COUNT(*) FROM ejercicios WHERE activo = 1') >= 30, 'Menos de 30 ejercicios');
    afirmar((int) v('SELECT MIN(n) FROM (SELECT COUNT(*) n FROM ejercicios GROUP BY tema_id) x') >= 6, 'Un tema tiene menos de 6');
    afirmar((int) v('SELECT COUNT(DISTINCT dificultad) FROM ejercicios') === 3, 'Faltan niveles');
    afirmar((int) v('SELECT COUNT(DISTINCT tipo_error_id) FROM ejercicios') >= 5, 'Pocos tipos de error');
});
prueba('BD-03', 'Cada ejercicio tiene entre 2 y 4 opciones y exactamente una correcta', function () {
    afirmar(v('SELECT COUNT(*) FROM (SELECT ejercicio_id, COUNT(*) n, SUM(es_correcta) c FROM opciones GROUP BY ejercicio_id HAVING n < 2 OR n > 4 OR c <> 1) x') == 0, 'Hay ejercicios con opciones inválidas');
    afirmar((int) v('SELECT COUNT(DISTINCT orden) FROM opciones WHERE es_correcta = 1') >= 3, 'La respuesta correcta siempre está en la misma posición');
});
prueba('BD-04', 'La base impide guardar dos opciones correctas en un ejercicio', function () {
    try {
        q('INSERT INTO opciones (ejercicio_id, texto, es_correcta, orden) VALUES (1, ?, 1, 9)', ['duplicada']);
        afirmar(false, 'Se aceptó una segunda opción correcta');
    } catch (PDOException) {
    }
});
prueba('BD-05', 'Los datos iniciales no incluyen usuarios de prueba', function () {
    $sql = file_get_contents(dirname(__DIR__) . '/database/seed.sql');
    afirmar(!preg_match('/INSERT INTO usuarios/i', $sql), 'seed.sql inserta usuarios');
});

// ======================= HU-01 Registro =======================
echo "\nHU-01 Registro y política de datos\n";
$estudiante = nuevo();
prueba('HU-01.2', 'No se crea la cuenta si no se acepta la política de datos', function () {
    registrar(nuevo(), 'Sin Política', 'sinpolitica@pruebas.co', acepta: false);
    afirmar(v('SELECT COUNT(*) FROM usuarios WHERE correo = ?', ['sinpolitica@pruebas.co']) == 0, 'Se creó la cuenta');
});
prueba('HU-01.1', 'Se exige confirmación de contraseña y mínimo 8 caracteres', function () {
    $c = registrar(nuevo(), 'Clave Corta', 'corta@pruebas.co', 'abc', confirmacion: 'abc')->seguir();
    afirmar($c->contiene('al menos 8 caracteres'), 'No avisó la longitud');
    $c = registrar(nuevo(), 'Clave Distinta', 'distinta@pruebas.co', 'Clave12345', confirmacion: 'Otra12345')->seguir();
    afirmar($c->contiene('La confirmación no coincide'), 'No avisó la confirmación');
    afirmar(v("SELECT COUNT(*) FROM usuarios WHERE correo IN ('corta@pruebas.co','distinta@pruebas.co')") == 0, 'Se crearon cuentas inválidas');
});
prueba('HU-01.4', 'La cuenta nueva queda con rol estudiante, contraseña con hash y fecha de aceptación', function () use ($estudiante) {
    registrar($estudiante, 'Ana Estudiante', 'ana@pruebas.co');
    afirmar($estudiante->estado === 303, 'No redirigió tras registrar');
    $u = q('SELECT u.*, r.clave rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE correo = ?', ['ana@pruebas.co'])[0] ?? null;
    afirmar($u !== null && $u['rol'] === 'estudiante', 'Rol incorrecto');
    afirmar(str_starts_with($u['hash_clave'], '$2y$') && password_verify('Clave12345', $u['hash_clave']), 'La contraseña no está con hash');
    afirmar($u['acepto_politica_en'] !== null, 'No se registró la aceptación');
    afirmar($estudiante->seguir()->contiene('PANEL DEL ESTUDIANTE'), 'No llegó a su panel');
});
prueba('HU-01.3', 'No se admite un correo ya registrado', function () {
    $c = registrar(nuevo(), 'Ana Repetida', 'ANA@pruebas.co')->seguir();
    afirmar($c->contiene('Ya existe una cuenta con ese correo'), 'No avisó el duplicado');
    afirmar(v('SELECT COUNT(*) FROM usuarios WHERE correo = ?', ['ana@pruebas.co']) == 1, 'Se duplicó el correo');
});

// ======================= HU-02 Sesión =======================
echo "\nHU-02 Inicio y cierre de sesión\n";
prueba('HU-02.2', 'Con credenciales inválidas se muestra un mensaje genérico (igual si el correo no existe)', function () {
    $a = nuevo()->ingresar('ana@pruebas.co', 'Equivocada1')->seguir();
    $b = nuevo()->ingresar('noexiste@pruebas.co', 'Equivocada1')->seguir();
    afirmar($a->contiene('No fue posible iniciar sesión') && $b->contiene('No fue posible iniciar sesión'), 'Mensaje distinto o ausente');
    q('UPDATE usuarios SET intentos_fallidos = 0 WHERE correo = ?', ['ana@pruebas.co']);
});
prueba('HU-02.1', 'Con credenciales válidas entra al panel de su rol y recibe un identificador de sesión nuevo', function () {
    $c = nuevo();
    $c->get('/ingresar');
    $antes = $c->cookieSesion();
    $c->post('/ingresar', ['correo' => 'ana@pruebas.co', 'clave' => 'Clave12345']);
    afirmar($c->estado === 303 && str_ends_with((string) $c->redireccion, '/'), 'No redirigió al panel');
    afirmar($antes !== null && $c->cookieSesion() !== $antes, 'No se regeneró el identificador de sesión');
    $admin = nuevo()->ingresar('admin@pruebas.co', 'Admin12345');
    afirmar(str_ends_with((string) $admin->redireccion, '/admin'), 'El administrador no llegó a su panel');
});
prueba('HU-02', 'Bloqueo temporal tras cinco intentos fallidos', function () {
    registrar(nuevo(), 'Bloqueo Prueba', 'bloqueo@pruebas.co');
    for ($i = 0; $i < 5; $i++) {
        nuevo()->ingresar('bloqueo@pruebas.co', 'Incorrecta' . $i);
    }
    afirmar(v('SELECT bloqueado_hasta FROM usuarios WHERE correo = ?', ['bloqueo@pruebas.co']) !== null, 'No se bloqueó');
    $c = nuevo()->ingresar('bloqueo@pruebas.co', 'Clave12345');
    afirmar(str_ends_with((string) $c->redireccion, '/ingresar'), 'Entró con la cuenta bloqueada');
});
prueba('HU-02.3/4', 'Cerrar sesión destruye la sesión; las páginas internas no se abren sin sesión', function () {
    $c = nuevo()->ingresar('ana@pruebas.co', 'Clave12345');
    $c->salir();
    $c->get('/progreso');
    afirmar($c->estado === 303 && str_ends_with((string) $c->redireccion, '/ingresar'), 'Abrió una página interna sin sesión');
    $api = nuevo()->postJson('/api/practica/1/responder', ['ejercicio_id' => 1, 'opcion_id' => 1], false);
    afirmar(isset($api['error']), 'La API respondió sin sesión');
});
prueba('HU-02', 'Cierre de sesión por inactividad', function () use ($configBase, $nombreBd, $rutaConfig) {
    $c = nuevo()->ingresar('ana@pruebas.co', 'Clave12345');
    escribirConfig($configBase, $nombreBd, $rutaConfig, 0.03); // ≈ 2 segundos
    sleep(3);
    $c->get('/progreso');
    escribirConfig($configBase, $nombreBd, $rutaConfig);
    afirmar($c->estado === 303 && str_ends_with((string) $c->redireccion, '/ingresar'), 'La sesión siguió activa');
});

// ======================= Seguridad =======================
echo "\nSeguridad (Tabla 21)\n";
prueba('SEG-01', 'Un POST sin token CSRF se rechaza', function () {
    $c = nuevo();
    $c->post('/ingresar', ['correo' => 'ana@pruebas.co', 'clave' => 'Clave12345'], false);
    afirmar($c->estado === 419, 'Estado ' . $c->estado);
});
prueba('SEG-02', 'Intento de inyección SQL en el inicio de sesión no da acceso', function () {
    $c = nuevo()->ingresar("' OR '1'='1' -- ", "' OR '1'='1");
    afirmar(str_ends_with((string) $c->redireccion, '/ingresar'), 'Dio acceso');
});
prueba('SEG-03', 'Los datos del usuario se escapan al mostrarse (XSS)', function () {
    registrar(nuevo(), '<script>alert(1)</script>Eva', 'xss@pruebas.co');
    $admin = nuevo()->ingresar('admin@pruebas.co', 'Admin12345');
    $admin->get('/admin/usuarios');
    afirmar(!str_contains($admin->html, '<script>alert(1)</script>') && str_contains($admin->html, '&lt;script&gt;'), 'El nombre no se escapó');
});
prueba('SEG-04', 'Cabeceras de seguridad (CSP, nosniff, frame) y cookie HttpOnly', function () {
    $c = nuevo()->get('/');
    afirmar(str_contains($c->cabeceras['content-security-policy'] ?? '', "script-src 'self'"), 'Falta CSP');
    afirmar(($c->cabeceras['x-content-type-options'] ?? '') === 'nosniff', 'Falta nosniff');
    afirmar(($c->cabeceras['x-frame-options'] ?? '') === 'DENY', 'Falta X-Frame-Options');
    afirmar(stripos($c->cabeceras['set-cookie'] ?? '', 'httponly') !== false, 'Cookie sin HttpOnly');
});
prueba('SEG-05', 'Control de acceso por rol en el servidor', function () {
    $e = nuevo()->ingresar('ana@pruebas.co', 'Clave12345');
    foreach (['/admin', '/admin/usuarios', '/gestion', '/gestion/ejercicios/1', '/profesor'] as $ruta) {
        afirmar($e->get($ruta)->estado === 403, "El estudiante abrió $ruta");
    }
    $a = nuevo()->ingresar('admin@pruebas.co', 'Admin12345');
    afirmar($a->get('/practica')->estado === 403, 'El administrador abrió la práctica de estudiante');
});

// ======================= HU-03, HU-04, HU-10 =======================
echo "\nMódulo 1: contenido (HU-03, HU-04, HU-10)\n";
$ana = nuevo()->ingresar('ana@pruebas.co', 'Clave12345');
prueba('HU-03.1', 'Los temas se listan en el orden del documento', function () use ($ana) {
    $ana->get('/temas');
    preg_match_all('/<h2 class="h5 mb-1">(.*?)<\/h2>/u', $ana->html, $m);
    $esperado = ['Formato general', 'Citas textuales cortas', 'Citas textuales largas', 'Paráfrasis', 'Lista de referencias'];
    afirmar($m[1] === $esperado, 'Orden: ' . implode(' | ', $m[1]));
});
prueba('HU-03.2/3', 'Cada tema muestra su explicación y permite pasar al siguiente o practicarlo', function () use ($ana) {
    $ana->get('/temas/1');
    afirmar($ana->contiene('2,54 cm'), 'No mostró la explicación');
    afirmar($ana->contiene('Siguiente tema: Citas textuales cortas') && $ana->contiene('Practicar este tema'), 'Faltan los botones');
});
prueba('HU-04.1/2/3', 'Cada tema tiene ejemplo correcto e incorrecto, con ícono y texto, y el incorrecto explica la corrección', function () use ($ana) {
    foreach (q('SELECT id FROM temas WHERE publicado = 1') as $t) {
        $ana->get('/temas/' . $t['id']);
        afirmar($ana->contiene('✓</span> Correcto') && $ana->contiene('✗</span> Incorrecto'), 'Tema ' . $t['id'] . ' sin ambos ejemplos');
        afirmar($ana->contiene('Corrección'), 'Tema ' . $t['id'] . ': el incorrecto no señala la corrección');
    }
});
prueba('HU-10.1/2/3', 'Recursos: biblioteca UNIAJC, Google Académico, Zotero y Mendeley, con descripción y en pestaña nueva', function () use ($ana) {
    $ana->get('/recursos');
    foreach (['Biblioteca UNIAJC', 'Google Académico', 'Zotero', 'Mendeley'] as $r) {
        afirmar($ana->contiene($r), "Falta $r");
    }
    afirmar(substr_count($ana->html, 'target="_blank"') >= 4, 'Los enlaces no abren en pestaña nueva');
});

// ======================= HU-05, HU-06 Práctica =======================
echo "\nMódulo 2: práctica guiada (HU-05, HU-06)\n";
$practicaId = 0;
prueba('HU-05.1/2', 'El estudiante elige tema y nivel y solo recibe ejercicios de ese tema y nivel', function () use ($ana, &$practicaId) {
    $ana->get('/practica');
    $ana->post('/practica/iniciar', ['tema_id' => 2, 'dificultad' => 1]);
    $practicaId = idIntento($ana);
    afirmar($practicaId > 0, 'No creó la práctica');
    $fuera = v('SELECT COUNT(*) FROM respuestas r JOIN ejercicios e ON e.id = r.ejercicio_id WHERE r.intento_id = ? AND (e.tema_id <> 2 OR e.dificultad <> 1)', [$practicaId]);
    afirmar($fuera == 0, 'Hay ejercicios de otro tema o nivel');
    afirmar((int) v('SELECT COUNT(*) FROM respuestas WHERE intento_id = ?', [$practicaId]) === (int) v('SELECT COUNT(*) FROM ejercicios WHERE tema_id = 2 AND dificultad = 1 AND activo = 1'), 'Faltan ejercicios');
});
prueba('HU-06 / SEG', 'Antes de responder, la página no contiene la respuesta correcta ni la explicación', function () use ($ana, &$practicaId) {
    $ana->get('/practica/' . $practicaId);
    preg_match('/<script type="application\/json" id="datos-practica">(.*?)<\/script>/s', $ana->html, $m);
    $datos = json_decode($m[1] ?? '', true);
    afirmar(is_array($datos) && count($datos['ejercicios']) > 0, 'No se cargaron los ejercicios');
    afirmar(!str_contains($m[1], 'es_correcta') && !str_contains($m[1], 'explicacion'), 'La página expone la clave o la explicación');
    afirmar($datos['ejercicios'][0]['resultado'] === null, 'El primer ejercicio aparece resuelto');
});
prueba('HU-06.1/2/3/4', 'Al responder se indica correcto o incorrecto, la explicación y, si falló, la opción correcta; queda registrado', function () use ($ana, &$practicaId) {
    $ejercicioId = (int) v('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? ORDER BY orden LIMIT 1', [$practicaId]);
    $incorrecta = (int) v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = 0 LIMIT 1', [$ejercicioId]);
    $correcta = (int) v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$ejercicioId]);
    $r = $ana->postJson("/api/practica/$practicaId/responder", ['ejercicio_id' => $ejercicioId, 'opcion_id' => $incorrecta]);
    afirmar(($r['es_correcta'] ?? null) === false, 'No indicó incorrecto');
    afirmar(($r['opcion_correcta_id'] ?? 0) === $correcta && ($r['opcion_correcta'] ?? '') !== '', 'No mostró la opción correcta');
    afirmar(($r['explicacion'] ?? '') !== '', 'Sin explicación');
    afirmar((int) v('SELECT opcion_id FROM respuestas WHERE intento_id = ? AND ejercicio_id = ?', [$practicaId, $ejercicioId]) === $incorrecta, 'No se registró');
});
prueba('HU-06', 'Una respuesta ya registrada no se puede cambiar después de ver la retroalimentación', function () use ($ana, &$practicaId) {
    $ejercicioId = (int) v('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? ORDER BY orden LIMIT 1', [$practicaId]);
    $correcta = (int) v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$ejercicioId]);
    $ana->postJson("/api/practica/$practicaId/responder", ['ejercicio_id' => $ejercicioId, 'opcion_id' => $correcta]);
    afirmar($ana->estado === 409, 'Estado ' . $ana->estado);
});
prueba('SEG-06', 'No se aceptan opciones de otro ejercicio, ejercicios ajenos a la práctica ni prácticas de otro estudiante', function () use ($ana, &$practicaId) {
    $pendiente = (int) v('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? AND opcion_id IS NULL ORDER BY orden LIMIT 1', [$practicaId]);
    $ajena = (int) v('SELECT id FROM opciones WHERE ejercicio_id <> ? LIMIT 1', [$pendiente]);
    $ana->postJson("/api/practica/$practicaId/responder", ['ejercicio_id' => $pendiente, 'opcion_id' => $ajena]);
    afirmar($ana->estado === 422, 'Aceptó una opción de otro ejercicio');
    $fuera = (int) v('SELECT id FROM ejercicios WHERE tema_id <> 2 LIMIT 1');
    $ana->postJson("/api/practica/$practicaId/responder", ['ejercicio_id' => $fuera, 'opcion_id' => (int) v('SELECT id FROM opciones WHERE ejercicio_id = ? LIMIT 1', [$fuera])]);
    afirmar($ana->estado === 404, 'Aceptó un ejercicio que no es de la práctica');
    $otro = nuevo();
    registrar($otro, 'Beto Estudiante', 'beto@pruebas.co');
    $otro->get('/practica/' . $practicaId);
    afirmar($otro->estado === 404, 'Otro estudiante abrió la práctica ajena');
});
prueba('HU-05.3/4', 'Uno a la vez con número de orden; al terminar se muestran los aciertos', function () use ($ana, &$practicaId) {
    foreach (q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? AND opcion_id IS NULL', [$practicaId]) as $f) {
        $op = (int) v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$f['ejercicio_id']]);
        $r = $ana->postJson("/api/practica/$practicaId/responder", ['ejercicio_id' => (int) $f['ejercicio_id'], 'opcion_id' => $op]);
    }
    afirmar(($r['terminado'] ?? false) === true, 'No marcó la práctica como terminada');
    $ana->get("/practica/$practicaId/resumen");
    $total = (int) v('SELECT COUNT(*) FROM respuestas WHERE intento_id = ?', [$practicaId]);
    afirmar($ana->contiene(($total - 1) . ' de ' . $total), 'El resumen no muestra los aciertos');
    afirmar(v('SELECT estado FROM intentos WHERE id = ?', [$practicaId]) === 'finalizado', 'El intento no se cerró');
});

// ======================= HU-07, HU-08 Evaluación =======================
echo "\nMódulo 3: evaluación (HU-07, HU-08)\n";
$evaluacionId = 0;
prueba('HU-07.1', 'La evaluación presenta 10 preguntas al azar de todos los temas', function () use ($ana, &$evaluacionId) {
    $ana->get('/evaluacion');
    $ana->post('/evaluacion/iniciar');
    $evaluacionId = idIntento($ana);
    $temas = v('SELECT COUNT(DISTINCT e.tema_id) FROM respuestas r JOIN ejercicios e ON e.id = r.ejercicio_id WHERE r.intento_id = ?', [$evaluacionId]);
    afirmar((int) v('SELECT COUNT(*) FROM respuestas WHERE intento_id = ?', [$evaluacionId]) === 10, 'No son 10 preguntas');
    afirmar((int) $temas === 5, "Cubre $temas temas");
    $otra = nuevo()->ingresar('beto@pruebas.co', 'Clave12345');
    $otra->get('/evaluacion');
    $otra->post('/evaluacion/iniciar');
    $a = array_column(q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? ORDER BY orden', [$evaluacionId]), 'ejercicio_id');
    $b = array_column(q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? ORDER BY orden', [idIntento($otra)]), 'ejercicio_id');
    afirmar($a !== $b, 'Dos estudiantes recibieron exactamente las mismas preguntas en el mismo orden');
});
prueba('HU-07.2', 'No se muestra retroalimentación ni la clave antes de enviar', function () use ($ana, &$evaluacionId) {
    $ana->get('/evaluacion/' . $evaluacionId);
    $explicaciones = array_column(q('SELECT e.explicacion FROM respuestas r JOIN ejercicios e ON e.id = r.ejercicio_id WHERE r.intento_id = ?', [$evaluacionId]), 'explicacion');
    foreach ($explicaciones as $x) {
        afirmar(!$ana->contiene($x), 'La página muestra una explicación');
    }
    afirmar(!str_contains($ana->html, 'es_correcta') && substr_count($ana->html, 'type="radio"') >= 20, 'Formulario incorrecto');
});
prueba('HU-07', 'Iniciar de nuevo con una evaluación sin enviar retoma la misma (no se sortean preguntas nuevas)', function () use ($ana, &$evaluacionId) {
    $ana->get('/evaluacion');
    $ana->post('/evaluacion/iniciar');
    afirmar(idIntento($ana) === $evaluacionId, 'Creó otro intento');
});
prueba('HU-07.3', 'No se puede enviar con preguntas sin responder: se señalan y no se califica', function () use ($ana, &$evaluacionId) {
    $filas = q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? ORDER BY orden', [$evaluacionId]);
    $respuestas = [];
    foreach (array_slice($filas, 0, 7) as $f) {
        $respuestas[$f['ejercicio_id']] = v('SELECT id FROM opciones WHERE ejercicio_id = ? LIMIT 1', [$f['ejercicio_id']]);
    }
    $ana->get('/evaluacion/' . $evaluacionId);
    $ana->post('/evaluacion/' . $evaluacionId . '/enviar', ['respuestas' => $respuestas])->seguir();
    afirmar($ana->contiene('Faltan 3 preguntas por responder'), 'No señaló las faltantes');
    afirmar(substr_count($ana->html, 'pregunta-faltante') === 3, 'No marcó las 3 preguntas');
    afirmar(v('SELECT estado FROM intentos WHERE id = ?', [$evaluacionId]) === 'en_curso', 'Calificó una evaluación incompleta');
});
prueba('HU-07.4', 'Al enviar, el servidor califica (correctas / total × 100) y guarda el intento', function () use ($ana, &$evaluacionId) {
    $filas = q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ? ORDER BY orden', [$evaluacionId]);
    $respuestas = [];
    foreach ($filas as $i => $f) {
        // 6 correctas y 4 incorrectas.
        $respuestas[$f['ejercicio_id']] = v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = ? LIMIT 1', [$f['ejercicio_id'], $i < 6 ? 1 : 0]);
    }
    $ana->get('/evaluacion/' . $evaluacionId);
    $ana->post('/evaluacion/' . $evaluacionId . '/enviar', ['respuestas' => $respuestas]);
    $i = q('SELECT * FROM intentos WHERE id = ?', [$evaluacionId])[0];
    afirmar($i['estado'] === 'finalizado' && (int) $i['correctas'] === 6 && (float) $i['puntaje'] === 60.0, 'Calificación: ' . json_encode($i));
});
prueba('HU-07', 'Un intento enviado no puede volver a enviarse ni modificarse', function () use ($ana, &$evaluacionId) {
    $filas = q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ?', [$evaluacionId]);
    $todas = [];
    foreach ($filas as $f) {
        $todas[$f['ejercicio_id']] = v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$f['ejercicio_id']]);
    }
    $ana->post('/evaluacion/' . $evaluacionId . '/enviar', ['respuestas' => $todas]);
    afirmar((float) v('SELECT puntaje FROM intentos WHERE id = ?', [$evaluacionId]) === 60.0, 'El puntaje cambió');
});
prueba('HU-08.1/2/3', 'Resultado: puntaje y tasa, respuesta dada, correcta y explicación, aciertos por tema y temas para reforzar', function () use ($ana, &$evaluacionId) {
    $ana->get('/evaluacion/' . $evaluacionId . '/resultado');
    afirmar($ana->contiene('6 / 10') && $ana->contiene('60 %'), 'Sin puntaje o tasa');
    afirmar(substr_count($ana->html, 'Respuesta correcta:') === 4, 'No muestra la respuesta correcta de las 4 incorrectas');
    afirmar(substr_count($ana->html, 'Tu respuesta:') === 10, 'No lista las 10 preguntas');
    afirmar($ana->contiene('Aciertos por tema') && $ana->contiene('Reforzar este tema'), 'Sin resumen por tema o recomendación');
    afirmar($ana->contiene('No alcanzada'), 'No compara con la meta del 70 %');
});
prueba('CU-06.3a', 'Una evaluación abandonada queda incompleta y no cuenta para la tasa de éxito', function () {
    afirmar((int) v("SELECT COUNT(*) FROM intentos WHERE modo = 'evaluacion' AND estado = 'en_curso'") >= 1, 'No hay intentos abandonados para comprobar');
    $promedio = (float) v("SELECT AVG(puntaje) FROM intentos WHERE modo = 'evaluacion' AND estado = 'finalizado'");
    $admin = nuevo()->ingresar('admin@pruebas.co', 'Admin12345');
    $admin->get('/admin');
    afirmar($admin->contiene(str_replace('.', ',', (string) round($promedio, 1)) . ' %'), 'El promedio del panel no coincide');
});

// ======================= HU-09 Progreso =======================
echo "\nProgreso (HU-09)\n";
prueba('HU-09.1/2/3', 'Intentos con fecha y tasa, gráfica de evolución y avance por tema', function () use ($ana) {
    $ana->get('/evaluacion');
    $ana->post('/evaluacion/iniciar');
    $id = idIntento($ana);
    $todas = [];
    foreach (q('SELECT ejercicio_id FROM respuestas WHERE intento_id = ?', [$id]) as $f) {
        $todas[$f['ejercicio_id']] = v('SELECT id FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$f['ejercicio_id']]);
    }
    $ana->get('/evaluacion/' . $id);
    $ana->post('/evaluacion/' . $id . '/enviar', ['respuestas' => $todas]);
    $ana->get('/progreso');
    afirmar($ana->contiene('Historial de evaluaciones') && $ana->contiene('100 %') && $ana->contiene('60 %'), 'Historial incompleto');
    afirmar(str_contains($ana->html, '<svg class="grafica"') && str_contains($ana->html, '<polyline'), 'Sin gráfica de evolución');
    afirmar($ana->contiene('Avance por tema') && $ana->contiene('practicados'), 'Sin avance por tema');
});

// ======================= HU-14 Administración =======================
echo "\nAdministración (HU-14)\n";
$admin = nuevo()->ingresar('admin@pruebas.co', 'Admin12345');
prueba('HU-14.1/2', 'El administrador crea usuarios con rol y contraseña temporal', function () use ($admin) {
    foreach ([['Paula Profesora', 'paula@pruebas.co', 2], ['Pedro Profesor', 'pedro@pruebas.co', 2], ['Gina Generadora', 'gina@pruebas.co', 3]] as [$n, $c, $r]) {
        $admin->get('/admin/usuarios/nuevo');
        $admin->post('/admin/usuarios/nuevo', ['nombre' => $n, 'correo' => $c, 'rol_id' => $r, 'clave' => 'Temporal123', 'confirmacion' => 'Temporal123']);
    }
    afirmar((int) v("SELECT COUNT(*) FROM usuarios WHERE correo IN ('paula@pruebas.co','pedro@pruebas.co','gina@pruebas.co') AND debe_cambiar_clave = 1") === 3, 'No se crearon');
});
prueba('HU-14 / HU-01', 'El usuario creado por el administrador acepta la política y cambia la contraseña al ingresar', function () {
    $c = nuevo()->ingresar('paula@pruebas.co', 'Temporal123');
    $c->get('/profesor');
    afirmar(str_ends_with((string) $c->redireccion, '/aceptar-politica'), 'No pidió aceptar la política');
    $c->get('/aceptar-politica');
    $c->post('/aceptar-politica', ['acepta_politica' => '1']);
    $c->get('/profesor');
    afirmar(str_ends_with((string) $c->redireccion, '/cambiar-clave'), 'No pidió cambiar la contraseña');
    $c->get('/cambiar-clave');
    $c->post('/cambiar-clave', ['clave_actual' => 'Temporal123', 'clave' => 'Profesora123', 'confirmacion' => 'Profesora123']);
    afirmar($c->get('/profesor')->estado === 200, 'No pudo entrar a su panel');
    foreach (['pedro@pruebas.co' => 'Profesor123', 'gina@pruebas.co' => 'Generadora123'] as $correo => $clave) {
        q('UPDATE usuarios SET acepto_politica_en = NOW(), debe_cambiar_clave = 0, hash_clave = ? WHERE correo = ?', [password_hash($clave, PASSWORD_DEFAULT), $correo]);
    }
});
$grupoA = 0;
$grupoB = 0;
prueba('HU-14.3', 'Crea grupos, les asigna un profesor y agrega estudiantes', function () use ($admin, &$grupoA, &$grupoB) {
    $paula = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['paula@pruebas.co']);
    $pedro = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['pedro@pruebas.co']);
    $admin->get('/admin/grupos');
    $admin->post('/admin/grupos', ['nombre' => 'Metodología G1', 'periodo' => '2026-2', 'profesor_id' => $paula]);
    $grupoA = idIntento($admin);
    $admin->get('/admin/grupos');
    $admin->post('/admin/grupos', ['nombre' => 'Metodología G2', 'periodo' => '2026-2', 'profesor_id' => $pedro]);
    $grupoB = idIntento($admin);
    $ana = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['ana@pruebas.co']);
    $beto = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['beto@pruebas.co']);
    $admin->get('/admin/grupos/' . $grupoA);
    $admin->post('/admin/grupos/' . $grupoA . '/estudiantes', ['estudiantes' => [$ana]]);
    $admin->get('/admin/grupos/' . $grupoB);
    $admin->post('/admin/grupos/' . $grupoB . '/estudiantes', ['estudiantes' => [$beto]]);
    afirmar((int) v('SELECT COUNT(*) FROM usuario_grupo') === 2, 'No se agregaron los estudiantes');
    afirmar((int) v('SELECT profesor_id FROM grupos WHERE id = ?', [$grupoA]) === $paula, 'No se asignó el profesor');
});
prueba('HU-14', 'Desactivar un usuario cierra su sesión activa e impide el ingreso; sus resultados se conservan', function () use ($admin) {
    $c = nuevo();
    registrar($c, 'Caro Temporal', 'caro@pruebas.co');
    $id = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['caro@pruebas.co']);
    $admin->get('/admin/usuarios/' . $id);
    $admin->post('/admin/usuarios/' . $id . '/estado', ['activo' => '0']);
    $c->get('/progreso');
    afirmar($c->estado === 303 && str_ends_with((string) $c->redireccion, '/ingresar'), 'La sesión siguió activa');
    afirmar(str_ends_with((string) nuevo()->ingresar('caro@pruebas.co', 'Clave12345')->redireccion, '/ingresar'), 'Pudo iniciar sesión');
    afirmar((int) v('SELECT COUNT(*) FROM usuarios WHERE id = ?', [$id]) === 1, 'Se eliminó el usuario');
});
prueba('HU-14', 'El administrador cambia el rol de un usuario', function () use ($admin) {
    $id = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['caro@pruebas.co']);
    $admin->get('/admin/usuarios/' . $id);
    $admin->post('/admin/usuarios/' . $id, ['nombre' => 'Caro Temporal', 'correo' => 'caro@pruebas.co', 'rol_id' => 2]);
    afirmar((int) v('SELECT rol_id FROM usuarios WHERE id = ?', [$id]) === 2, 'No cambió el rol');
});

// ======================= HU-11, HU-12, HU-13 Profesor =======================
echo "\nProfesor (HU-11, HU-12, HU-13)\n";
$paula = nuevo()->ingresar('paula@pruebas.co', 'Profesora123');
prueba('HU-11.1', 'El profesor ve solo sus grupos', function () use ($paula, &$grupoA, &$grupoB) {
    $paula->get('/profesor');
    afirmar($paula->contiene('Metodología G1') && !$paula->contiene('Metodología G2'), 'Ve grupos ajenos');
    afirmar($paula->get('/profesor/grupos/' . $grupoB)->estado === 404, 'Abrió un grupo ajeno');
});
prueba('HU-11.2/3', 'Por grupo: último puntaje, mejor tasa y n.º de intentos por estudiante, y detalle de cada uno', function () use ($paula, &$grupoA, &$grupoB) {
    $paula->get('/profesor/grupos/' . $grupoA);
    afirmar($paula->contiene('Ana Estudiante') && $paula->contiene('Último puntaje') && $paula->contiene('Mejor tasa de éxito'), 'Tabla incompleta');
    afirmar(preg_match('/Ana Estudiante.*?<td>2<\/td>\s*<td>100 %<\/td>\s*<td>100 %/s', html_entity_decode($paula->html)) === 1, 'Datos del estudiante incorrectos');
    $ana = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['ana@pruebas.co']);
    $beto = (int) v('SELECT id FROM usuarios WHERE correo = ?', ['beto@pruebas.co']);
    afirmar($paula->get("/profesor/grupos/$grupoA/estudiantes/$ana")->estado === 200 && $paula->contiene('Intentos de evaluación'), 'Sin detalle');
    afirmar($paula->get("/profesor/grupos/$grupoA/estudiantes/$beto")->estado === 404, 'Abrió el detalle de un estudiante de otro grupo');
});
prueba('HU-12.1/2', 'Porcentaje de error por tema y por tipo de error, ordenado de mayor a menor', function () use ($paula, &$grupoA) {
    $paula->get('/profesor/grupos/' . $grupoA);
    afirmar($paula->contiene('Errores frecuentes por tema') && $paula->contiene('Errores por tipo de error'), 'Faltan las secciones');
    preg_match_all('/<td><strong>([\d,]+) %<\/strong><\/td>/', $paula->html, $m);
    $valores = array_map(static fn ($x) => (float) str_replace(',', '.', $x), $m[1]);
    afirmar(count($valores) >= 2, 'Sin datos de errores');
    $n = (int) (count($valores) / 2);
    $temas = array_slice($valores, 0, (int) v('SELECT COUNT(*) FROM temas'));
    $ordenado = $temas;
    rsort($ordenado);
    afirmar($temas === $ordenado, 'No está ordenado de mayor a menor: ' . implode(', ', $temas));
});
prueba('HU-13 (Won\'t have, conservada)', 'Exportación CSV de resultados del grupo', function () use ($paula, &$grupoA) {
    $paula->get('/profesor/grupos/' . $grupoA . '/exportar');
    afirmar(str_starts_with($paula->html, "\xEF\xBB\xBF") && str_contains($paula->html, 'Ana Estudiante'), 'CSV inválido');
});

// ======================= HU-15, HU-16 Generador =======================
echo "\nGenerador de contenidos (HU-15, HU-16)\n";
$gina = nuevo()->ingresar('gina@pruebas.co', 'Generadora123');
$nuevoTema = 0;
prueba('HU-15.1/3', 'Crear y publicar un tema; el cambio se ve de inmediato en el contenido', function () use ($gina, $ana, &$nuevoTema) {
    $gina->get('/gestion/temas/nuevo');
    $gina->post('/gestion/temas/nuevo', ['titulo' => 'Tema de prueba', 'resumen' => 'Resumen', 'contenido' => "Párrafo con **negrita** y _cursiva_.\n\n- Elemento", 'publicado' => '1']);
    $nuevoTema = idIntento($gina);
    $ana->get('/temas/' . $nuevoTema);
    afirmar($ana->estado === 200 && str_contains($ana->html, '<strong>negrita</strong>') && str_contains($ana->html, '<em>cursiva</em>') && str_contains($ana->html, '<li>Elemento</li>'), 'No se ve el tema con formato básico');
});
prueba('HU-15.1', 'Ordenar y ocultar temas', function () use ($gina, $ana, &$nuevoTema) {
    $gina->get('/gestion');
    $gina->post('/gestion/temas/' . $nuevoTema . '/mover', ['direccion' => 'arriba']);
    afirmar((int) v('SELECT orden FROM temas WHERE id = ?', [$nuevoTema]) === 5, 'No subió');
    $gina->get('/gestion/temas/' . $nuevoTema);
    $gina->post('/gestion/temas/' . $nuevoTema, ['titulo' => 'Tema de prueba', 'resumen' => 'Resumen', 'contenido' => 'Contenido suficiente para el tema.']);
    afirmar($ana->get('/temas/' . $nuevoTema)->estado === 404, 'El estudiante ve un tema oculto');
    $gina->get('/gestion');
    $gina->post('/gestion/temas/' . $nuevoTema . '/mover', ['direccion' => 'abajo']);
});
prueba('HU-04 / HU-15.2', 'Agregar ejemplos a un tema; el incorrecto exige explicación', function () use ($gina, &$nuevoTema) {
    $gina->get('/gestion/temas/' . $nuevoTema);
    $gina->post('/gestion/temas/' . $nuevoTema . '/ejemplos', ['es_correcto' => '0', 'texto' => 'Mal', 'explicacion' => '']);
    afirmar((int) v('SELECT COUNT(*) FROM ejemplos WHERE tema_id = ?', [$nuevoTema]) === 0, 'Aceptó un ejemplo sin explicación');
    $gina->get('/gestion/temas/' . $nuevoTema);
    $gina->post('/gestion/temas/' . $nuevoTema . '/ejemplos', ['es_correcto' => '0', 'texto' => 'Mal', 'explicacion' => 'Error y corrección']);
    afirmar((int) v('SELECT COUNT(*) FROM ejemplos WHERE tema_id = ?', [$nuevoTema]) === 1, 'No se agregó');
});
$nuevoEjercicio = 0;
prueba('HU-16.1/2', 'No se guarda un ejercicio sin respuesta correcta, sin explicación o con menos de dos opciones', function () use ($gina) {
    $base = ['tema_id' => 1, 'tipo_error_id' => 1, 'tipo' => 'seleccion_multiple', 'dificultad' => 2, 'enunciado' => 'Pregunta nueva', 'explicacion' => 'Porque sí', 'opciones' => ['A', 'B', 'C', ''], 'correcta' => 1, 'en_evaluacion' => '1'];
    $antes = (int) v('SELECT COUNT(*) FROM ejercicios');
    foreach ([['correcta' => ''], ['explicacion' => ''], ['opciones' => ['Solo una', '', '', '']], ['tipo' => 'verdadero_falso']] as $cambio) {
        $gina->get('/gestion/ejercicios/nuevo');
        $gina->post('/gestion/ejercicios/nuevo', array_merge($base, $cambio));
    }
    afirmar((int) v('SELECT COUNT(*) FROM ejercicios') === $antes, 'Se guardó un ejercicio inválido');
});
prueba('HU-16.1', 'Un ejercicio válido registra tema, tipo de error, nivel, enunciado, opciones, correcta y explicación', function () use ($gina, &$nuevoEjercicio) {
    $gina->get('/gestion/ejercicios/nuevo');
    $gina->post('/gestion/ejercicios/nuevo', ['tema_id' => 1, 'tipo_error_id' => 1, 'tipo' => 'seleccion_multiple', 'dificultad' => 2, 'enunciado' => '¿Pregunta de prueba?', 'explicacion' => 'Explicación de prueba', 'opciones' => ['Uno', 'Dos', 'Tres', 'Cuatro'], 'correcta' => 2, 'en_evaluacion' => '1']);
    $nuevoEjercicio = idIntento($gina);
    $e = q('SELECT * FROM ejercicios WHERE id = ?', [$nuevoEjercicio])[0] ?? null;
    afirmar($e !== null && (int) $e['dificultad'] === 2 && (int) $e['tipo_error_id'] === 1, 'No se guardó');
    afirmar(v('SELECT texto FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$nuevoEjercicio]) === 'Tres', 'Correcta equivocada');
    afirmar((int) v('SELECT COUNT(*) FROM evaluacion_ejercicio WHERE ejercicio_id = ?', [$nuevoEjercicio]) === 1, 'No quedó en la evaluación');
});
prueba('HU-16.3', 'Los ejercicios se desactivan sin borrarlos y no aparecen en prácticas nuevas', function () use ($gina, $ana, &$nuevoEjercicio) {
    $gina->get('/gestion/ejercicios/' . $nuevoEjercicio);
    $gina->post('/gestion/ejercicios/' . $nuevoEjercicio . '/estado', ['activo' => '0']);
    afirmar((int) v('SELECT activo FROM ejercicios WHERE id = ?', [$nuevoEjercicio]) === 0, 'No se desactivó');
    $ana->get('/practica');
    $ana->post('/practica/iniciar', ['tema_id' => 1, 'dificultad' => 2]);
    afirmar((int) v('SELECT COUNT(*) FROM respuestas WHERE intento_id = ? AND ejercicio_id = ?', [idIntento($ana), $nuevoEjercicio]) === 0, 'Aparece en la práctica');
});
prueba('HU-16', 'No se puede cambiar la respuesta correcta de un ejercicio ya respondido', function () use ($gina) {
    $id = (int) v('SELECT ejercicio_id FROM respuestas WHERE opcion_id IS NOT NULL LIMIT 1');
    $e = q('SELECT * FROM ejercicios WHERE id = ?', [$id])[0];
    $ops = q('SELECT texto, es_correcta FROM opciones WHERE ejercicio_id = ? ORDER BY orden', [$id]);
    $correcta = array_search('1', array_map('strval', array_column($ops, 'es_correcta')), true);
    $gina->get('/gestion/ejercicios/' . $id);
    $gina->post('/gestion/ejercicios/' . $id, ['tema_id' => $e['tema_id'], 'tipo_error_id' => $e['tipo_error_id'], 'tipo' => $e['tipo'], 'dificultad' => $e['dificultad'], 'enunciado' => $e['enunciado'], 'explicacion' => $e['explicacion'], 'opciones' => array_column($ops, 'texto'), 'correcta' => ($correcta + 1) % count($ops), 'en_evaluacion' => '1']);
    afirmar((int) v('SELECT orden FROM opciones WHERE ejercicio_id = ? AND es_correcta = 1', [$id]) === $correcta + 1, 'Cambió la respuesta correcta');
});
prueba('HU-16.4', 'Se muestra el total de ejercicios activos por tema', function () use ($gina) {
    $gina->get('/gestion');
    afirmar($gina->contiene('Ejercicios activos') && $gina->contiene('Total de ejercicios activos: 45'), 'No muestra los totales');
});
prueba('HU-15', 'Un tema con ejercicios no se elimina (se oculta)', function () use ($gina) {
    $gina->get('/gestion/temas/1');
    $gina->post('/gestion/temas/1/eliminar');
    afirmar((int) v('SELECT COUNT(*) FROM temas WHERE id = 1') === 1, 'Se eliminó');
});

// ======================= HU-17 Estadísticas =======================
echo "\nEstadísticas (HU-17)\n";
prueba('HU-17.1/2', 'Usuarios, evaluaciones presentadas y tasa de éxito promedio, con indicación de la meta', function () use ($admin) {
    $admin->get('/admin');
    $evaluaciones = (int) v("SELECT COUNT(*) FROM intentos WHERE modo = 'evaluacion' AND estado = 'finalizado'");
    afirmar($admin->contiene('Evaluaciones presentadas') && str_contains($admin->html, '<p class="cifra mb-1">' . $evaluaciones . '</p>'), 'Número de evaluaciones incorrecto');
    afirmar($admin->contiene('la meta del 70 %'), 'No indica la meta');
});
prueba('HU-17.3', 'Filtro por grupo y por fechas', function () use ($admin, &$grupoB) {
    $admin->get('/admin?grupo=' . $grupoB);
    afirmar(str_contains($admin->html, '<p class="cifra mb-1">0</p>'), 'El filtro por grupo no aplica (el grupo B no tiene evaluaciones)');
    $admin->get('/admin?desde=2000-01-01&hasta=2000-12-31');
    afirmar($admin->contiene('Sin evaluaciones en el filtro elegido'), 'El filtro por fechas no aplica');
});

// ---------- Informe ----------
$total = count($resultados);
$ok = count(array_filter($resultados, static fn ($r) => $r[2]));
echo "\nResultado: $ok de $total pruebas superadas.\n";
$md = "# Resultados de las pruebas automatizadas\n\nEjecutadas el " . date('d/m/Y H:i') . " con PHP " . PHP_VERSION . " y " . v('SELECT VERSION()') . ".\n\n";
$md .= "**$ok de $total pruebas superadas.**\n\n| Requisito | Prueba | Resultado |\n|---|---|---|\n";
foreach ($resultados as [$id, $d, $paso, $error]) {
    $md .= "| $id | $d | " . ($paso ? '✓ Superada' : '✗ Falló: ' . str_replace('|', '/', $error)) . " |\n";
}
file_put_contents($salida . '/resultados.md', $md);
echo "Informe: tests/salida/resultados.md\n";
if (!in_array('--conservar', $argv, true)) {
    $pdo->exec("DROP DATABASE IF EXISTS `$nombreBd`");
}
exit($ok === $total ? 0 : 1);
