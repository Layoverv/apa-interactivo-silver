<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $valores = [];

    public static function cargar(array $valores): void
    {
        self::$valores = $valores;
    }

    public static function get(string $clave, mixed $defecto = null): mixed
    {
        $actual = self::$valores;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($actual) || !array_key_exists($parte, $actual)) {
                return $defecto;
            }
            $actual = $actual[$parte];
        }
        return $actual;
    }
}
