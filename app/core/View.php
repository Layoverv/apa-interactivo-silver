<?php
declare(strict_types=1);

namespace App\Core;

// Genera las pantallas a partir de las plantillas de app/views. Toda salida de datos
// se escapa con e() (htmlspecialchars) para prevenir XSS (Tabla 21).
final class View
{
    public static function render(string $plantilla, array $datos = [], string $layout = 'layout/principal'): void
    {
        $contenido = self::capturar($plantilla, $datos);
        if ($layout === '') {
            echo $contenido;
            return;
        }
        echo self::capturar($layout, $datos + ['contenido' => $contenido]);
    }

    public static function capturar(string $plantilla, array $datos = []): string
    {
        $archivo = APP_ROOT . '/app/views/' . $plantilla . '.php';
        if (!is_file($archivo)) {
            throw new \RuntimeException("No existe la vista $plantilla");
        }
        extract($datos, EXTR_SKIP);
        ob_start();
        require $archivo;
        return (string) ob_get_clean();
    }
}
