<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

// Identidad del usuario en la sesión. En cada petición se vuelve a leer el usuario de la
// base de datos: si fue desactivado o cambió de rol, el cambio se aplica de inmediato.
final class Auth
{
    private static ?array $usuario = null;
    private static bool $cargado = false;

    public static function usuario(): ?array
    {
        if (!self::$cargado) {
            self::$cargado = true;
            $id = $_SESSION['usuario_id'] ?? null;
            if ($id !== null) {
                $usuario = Usuario::buscar((int) $id);
                if ($usuario === null || !(int) $usuario['activo']) {
                    Sesion::destruir();
                    Sesion::iniciar();
                    Sesion::flash('warning', 'Tu cuenta no está activa. Contacta al administrador.');
                    $usuario = null;
                }
                self::$usuario = $usuario;
            }
        }
        return self::$usuario;
    }

    public static function id(): int
    {
        return (int) (self::usuario()['id'] ?? 0);
    }

    public static function rol(): ?string
    {
        return self::usuario()['rol'] ?? null;
    }

    public static function tieneRol(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    public static function iniciar(array $usuario): void
    {
        Sesion::regenerar();
        $_SESSION['usuario_id'] = (int) $usuario['id'];
        unset($_SESSION['csrf']);
        self::$usuario = null;
        self::$cargado = false;
    }

    public static function cerrar(): void
    {
        Sesion::destruir();
        self::$usuario = null;
        self::$cargado = true;
    }

    /** Detiene la petición si no hay sesión o si el rol no está autorizado. */
    public static function exigir(array $roles): void
    {
        $usuario = self::usuario();
        if ($usuario === null) {
            throw new HttpException(401);
        }
        // Antes de usar el sistema, el usuario debe aceptar la política y, si el
        // administrador le asignó una clave temporal, cambiarla.
        $ruta = ruta_actual();
        if ($usuario['acepto_politica_en'] === null && $ruta !== '/aceptar-politica' && $ruta !== '/salir') {
            throw new HttpException(428, '/aceptar-politica');
        }
        if ((int) $usuario['debe_cambiar_clave'] === 1 && $usuario['acepto_politica_en'] !== null
            && $ruta !== '/cambiar-clave' && $ruta !== '/salir') {
            throw new HttpException(428, '/cambiar-clave');
        }
        if ($roles !== [] && !in_array($usuario['rol'], $roles, true)) {
            throw new HttpException(403);
        }
    }
}
