<?php
declare(strict_types=1);

namespace App\Core;

// Protección contra falsificación de peticiones (Tabla 21): token aleatorio guardado en
// la sesión y verificado en cada petición POST, ya sea de formulario o asíncrona.
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function valido(?string $recibido): bool
    {
        return is_string($recibido) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $recibido);
    }

    public static function tokenDePeticion(): ?string
    {
        return $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    }
}
