<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Sesion;
use App\Core\View;
use App\Models\Usuario;
use App\Services\Permisos;

// HU-01 (registro con política de datos) y HU-02 (inicio y cierre de sesión).
final class AuthController
{
    // Hash de una clave aleatoria: se verifica cuando el correo no existe, para que la
    // respuesta tarde lo mismo y no revele qué correos están registrados.
    private const HASH_FICTICIO = '$2y$10$zCDkcXrqvdSv2GaDbaPs4uGuGsn0FhKY61LmZNk.A1OHIX8kkm66m';

    public function politica(): void
    {
        View::render('auth/politica', [
            'titulo'   => 'Política de tratamiento de datos',
            'version'  => Config::get('politica_version'),
            'contacto' => Config::get('contacto_datos'),
        ]);
    }

    public function formularioRegistro(): void
    {
        if (Auth::usuario()) {
            redirigir('/');
        }
        View::render('auth/registro', ['titulo' => 'Crear cuenta', 'formulario' => Sesion::tomarFormulario()]);
    }

    public function registrar(): void
    {
        $nombre = entrada_texto($_POST, 'nombre', 120);
        $correo = mb_strtolower(entrada_texto($_POST, 'correo', 190));
        $clave = (string) ($_POST['clave'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion'] ?? '');
        $acepta = ($_POST['acepta_politica'] ?? '') === '1';

        $errores = [];
        if (mb_strlen($nombre) < 3) {
            $errores[] = 'Escribe tu nombre completo (mínimo 3 caracteres).';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Escribe un correo electrónico válido.';
        }
        $errores = array_merge($errores, self::validarClave($clave, $confirmacion));
        if (!$acepta) {
            $errores[] = 'Para crear la cuenta debes aceptar la política de tratamiento de datos personales.';
        }
        if ($errores === [] && Usuario::existeCorreo($correo)) {
            $errores[] = 'Ya existe una cuenta con ese correo. Inicia sesión o usa otro correo.';
        }
        if ($errores !== []) {
            volver_con_errores($errores, '/registro');
        }

        // La cuenta nueva siempre queda con rol estudiante (HU-01, criterio 4).
        $id = Usuario::crear([
            'rol_id'             => Usuario::rolIdPorClave(Permisos::ESTUDIANTE),
            'nombre'             => $nombre,
            'correo'             => $correo,
            'clave'              => $clave,
            'acepto_politica_en' => date('Y-m-d H:i:s'),
            'version_politica'   => Config::get('politica_version'),
        ]);
        Auth::iniciar(['id' => $id]);
        Sesion::flash('success', 'Tu cuenta fue creada. ¡Bienvenido(a) a APA Interactivo!');
        redirigir('/');
    }

    public function formularioIngreso(): void
    {
        if (Auth::usuario()) {
            redirigir('/');
        }
        View::render('auth/ingresar', ['titulo' => 'Iniciar sesión', 'formulario' => Sesion::tomarFormulario()]);
    }

    public function ingresar(): void
    {
        $correo = mb_strtolower(entrada_texto($_POST, 'correo', 190));
        $clave = (string) ($_POST['clave'] ?? '');
        $usuario = $correo !== '' ? Usuario::buscarParaIngreso($correo) : null;
        $maximo = (int) Config::get('login_max_intentos', 5);
        $minutos = (int) Config::get('login_bloqueo_minutos', 15);

        $claveCorrecta = password_verify($clave, $usuario['hash_clave'] ?? self::HASH_FICTICIO);
        $permitido = $usuario !== null && $claveCorrecta && (int) $usuario['activo'] === 1 && !Usuario::estaBloqueado($usuario);

        if (!$permitido) {
            if ($usuario !== null && !Usuario::estaBloqueado($usuario) && !$claveCorrecta) {
                Usuario::registrarFallo((int) $usuario['id'], $maximo, $minutos);
            }
            // Mensaje genérico (HU-02, criterio 2): no revela si el correo existe ni la causa.
            Sesion::flash('danger', "No fue posible iniciar sesión. Revisa tu correo y tu contraseña. Después de $maximo intentos fallidos el acceso se bloquea durante $minutos minutos.");
            Sesion::guardarFormulario(['correo' => $correo]);
            redirigir('/ingresar');
        }

        Usuario::registrarIngreso((int) $usuario['id'], $usuario['hash_clave'], $clave);
        Auth::iniciar($usuario);
        redirigir(Permisos::inicio($usuario['rol']));
    }

    public function salir(): void
    {
        Auth::cerrar();
        Sesion::iniciar();
        Sesion::flash('success', 'Cerraste sesión correctamente.');
        redirigir('/');
    }

    /**
     * Mantiene viva la sesión mientras el estudiante responde la evaluación: cada petición
     * renueva el tiempo de inactividad (Sesion::iniciar). Sin sesión responde 401.
     */
    public function mantenerSesion(): void
    {
        json_respuesta(['activa' => true]);
    }

    public function formularioAceptarPolitica(): void
    {
        if (Auth::usuario()['acepto_politica_en'] !== null) {
            redirigir('/');
        }
        View::render('auth/aceptar_politica', ['titulo' => 'Autorización de datos', 'version' => Config::get('politica_version')]);
    }

    public function aceptarPolitica(): void
    {
        if (($_POST['acepta_politica'] ?? '') !== '1') {
            Sesion::flash('danger', 'Para usar la aplicación debes aceptar la política de tratamiento de datos.');
            redirigir('/aceptar-politica');
        }
        Usuario::aceptarPolitica(Auth::id(), (string) Config::get('politica_version'));
        redirigir('/');
    }

    public function formularioCambiarClave(): void
    {
        View::render('auth/cambiar_clave', ['titulo' => 'Cambiar contraseña', 'obligatorio' => (int) Auth::usuario()['debe_cambiar_clave'] === 1]);
    }

    public function cambiarClave(): void
    {
        $actual = (string) ($_POST['clave_actual'] ?? '');
        $nueva = (string) ($_POST['clave'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion'] ?? '');
        $errores = self::validarClave($nueva, $confirmacion);
        if (!Usuario::verificarClave(Auth::id(), $actual)) {
            $errores[] = 'La contraseña actual no es correcta.';
        } elseif ($actual === $nueva) {
            $errores[] = 'La nueva contraseña debe ser distinta de la actual.';
        }
        if ($errores !== []) {
            volver_con_errores($errores, '/cambiar-clave');
        }
        Usuario::cambiarClave(Auth::id(), $nueva, false);
        Sesion::regenerar();
        Sesion::flash('success', 'Tu contraseña se actualizó.');
        redirigir(Permisos::inicio((string) Auth::rol()));
    }

    /** Contraseña de al menos 8 caracteres, con confirmación (HU-01). */
    public static function validarClave(string $clave, string $confirmacion): array
    {
        $errores = [];
        if (mb_strlen($clave) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (mb_strlen($clave) > 72) {
            $errores[] = 'La contraseña no puede superar 72 caracteres.';
        }
        if ($clave !== $confirmacion) {
            $errores[] = 'La confirmación no coincide con la contraseña.';
        }
        return $errores;
    }
}
