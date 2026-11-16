<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Sesion;

/** Escapa un valor para mostrarlo en HTML (prevención de XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ruta base visible de la aplicación. Funciona tanto si Apache apunta a /public como si la
 * carpeta del proyecto está dentro de htdocs (por ejemplo, http://localhost/apa-interactivo/).
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $script = rtrim($script, '/');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($script !== '' && ($uri === $script || str_starts_with($uri, $script . '/'))) {
        $base = $script;
    } elseif (str_ends_with($script, '/public')) {
        $base = substr($script, 0, -7);
    } else {
        $base = $script;
    }
    return $base;
}

/** Ruta solicitada, relativa a la base (por ejemplo, /temas/3). */
function ruta_actual(): string
{
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    if (str_starts_with($uri, '/index.php')) {
        $uri = substr($uri, 10);
    }
    $uri = '/' . trim($uri, '/');
    return $uri;
}

function url(string $ruta = '/', array $consulta = []): string
{
    $url = base_path() . '/' . ltrim($ruta, '/');
    if ($consulta !== []) {
        $url .= '?' . http_build_query($consulta);
    }
    return $url === '' ? '/' : $url;
}

function asset(string $ruta): string
{
    $archivo = APP_ROOT . '/public/assets/' . ltrim($ruta, '/');
    $version = is_file($archivo) ? (string) filemtime($archivo) : '1';
    return url('assets/' . ltrim($ruta, '/')) . '?v=' . $version;
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta), true, 303);
    exit;
}

function json_respuesta(array $datos, int $estado = 200): never
{
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Vuelve al formulario tras un error de validación: muestra los mensajes y conserva lo que
 * el usuario escribió (sin las contraseñas).
 */
function volver_con_errores(array $errores, string $ruta): never
{
    foreach ($errores as $error) {
        Sesion::flash('danger', $error);
    }
    Sesion::guardarFormulario($_POST);
    redirigir($ruta);
}

function abortar(int $estado, string $mensaje = ''): never
{
    throw new HttpException($estado, $mensaje);
}

/** Lee un entero positivo de la petición o devuelve null. */
function entrada_entero(array $origen, string $clave): ?int
{
    $valor = $origen[$clave] ?? null;
    if ($valor === null || $valor === '' || !ctype_digit((string) $valor)) {
        return null;
    }
    return (int) $valor;
}

function entrada_texto(array $origen, string $clave, int $maximo = 5000): string
{
    $valor = trim((string) ($origen[$clave] ?? ''));
    return mb_substr($valor, 0, $maximo);
}

/** Valor anterior de un campo de formulario (tras un error de validación). */
function viejo(array $formulario, string $clave, mixed $defecto = ''): string
{
    return e($formulario[$clave] ?? $defecto);
}

/**
 * Convierte el formato básico de los temas en HTML seguro: primero escapa todo y después
 * aplica párrafos, subtítulos (##), listas (- ), **negrita** y _cursiva_ (HU-15).
 */
function formato_basico(string $texto): string
{
    $bloques = preg_split("/\R{2,}/u", trim(str_replace("\r", '', $texto))) ?: [];
    $html = '';
    foreach ($bloques as $bloque) {
        $lineas = preg_split("/\R/u", $bloque) ?: [];
        $parrafo = [];
        $lista = [];
        $vaciar = static function () use (&$parrafo, &$lista, &$html): void {
            if ($parrafo !== []) {
                $html .= '<p>' . implode('<br>', $parrafo) . '</p>';
                $parrafo = [];
            }
            if ($lista !== []) {
                $html .= '<ul>' . implode('', array_map(static fn ($l) => "<li>$l</li>", $lista)) . '</ul>';
                $lista = [];
            }
        };
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (str_starts_with($linea, '## ')) {
                $vaciar();
                $html .= '<h3 class="h5 mt-4">' . formato_en_linea(substr($linea, 3)) . '</h3>';
            } elseif (str_starts_with($linea, '- ')) {
                if ($parrafo !== []) {
                    $html .= '<p>' . implode('<br>', $parrafo) . '</p>';
                    $parrafo = [];
                }
                $lista[] = formato_en_linea(substr($linea, 2));
            } elseif ($linea !== '') {
                if ($lista !== []) {
                    $vaciar();
                }
                $parrafo[] = formato_en_linea($linea);
            }
        }
        $vaciar();
    }
    return $html;
}

function formato_en_linea(string $texto): string
{
    $seguro = e($texto);
    $seguro = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $seguro) ?? $seguro;
    $seguro = preg_replace('/(?<![\w\/.])_([^_\n]+?)_(?![\w])/u', '<em>$1</em>', $seguro) ?? $seguro;
    return $seguro;
}

function nivel_texto(?int $nivel): string
{
    return [1 => 'Básico', 2 => 'Intermedio', 3 => 'Avanzado'][$nivel ?? 0] ?? 'Todos los niveles';
}

function tipo_ejercicio_texto(string $tipo): string
{
    return [
        'seleccion_multiple' => 'Selección múltiple',
        'verdadero_falso'    => 'Verdadero o falso',
        'completar'          => 'Completar',
    ][$tipo] ?? $tipo;
}

function porcentaje(mixed $valor): string
{
    if ($valor === null || $valor === '') {
        return '—';
    }
    $numero = round((float) $valor, 1);
    return str_replace('.', ',', (string) ($numero == (int) $numero ? (int) $numero : $numero)) . ' %';
}

function fecha_corta(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($fecha));
}

function letra_opcion(int $indice): string
{
    return chr(65 + $indice);
}
