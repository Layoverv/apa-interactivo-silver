<?php
declare(strict_types=1);

namespace App\Core;

// Manejo seguro de sesiones (Tabla 21): cookies HttpOnly y SameSite, identificador
// nuevo al iniciar sesión y cierre automático por inactividad (HU-02).
final class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_name('APASESION');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => self::rutaCookie(),
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        $limite = (int) round((float) Config::get('sesion_minutos', 30) * 60);
        $ahora = time();
        if (isset($_SESSION['ultima_actividad']) && ($ahora - (int) $_SESSION['ultima_actividad']) > $limite) {
            $teniaUsuario = isset($_SESSION['usuario_id']);
            self::destruir();
            session_start();
            if ($teniaUsuario) {
                self::flash('info', 'Tu sesión se cerró por inactividad. Vuelve a iniciar sesión.');
            }
        }
        $_SESSION['ultima_actividad'] = $ahora;
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function tomarFlash(): array
    {
        $mensajes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $mensajes;
    }

    /** Guarda los datos de un formulario para volver a mostrarlos tras un error. */
    public static function guardarFormulario(array $datos): void
    {
        unset($datos['clave'], $datos['confirmacion'], $datos['clave_actual'], $datos['_token']);
        $_SESSION['formulario'] = $datos;
    }

    public static function tomarFormulario(): array
    {
        $datos = $_SESSION['formulario'] ?? [];
        unset($_SESSION['formulario']);
        return $datos;
    }

    private static function rutaCookie(): string
    {
        $base = base_path();
        return $base === '' ? '/' : $base . '/';
    }
}
