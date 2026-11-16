<?php
declare(strict_types=1);

namespace App\Services;

// Gráficas sencillas generadas en el servidor, sin bibliotecas externas.
// Se usan en el progreso del estudiante (HU-09) y en los reportes de errores (HU-12, HU-17).
final class Graficas
{
    /** Línea de evolución de la tasa de éxito, con la meta del 70 % como referencia. */
    public static function evolucion(array $puntos, string $titulo): string
    {
        $ancho = 480;
        $alto = 240;
        $mi = 52; // margen izquierdo
        $md = 12;
        $ms = 16;
        $mb = 34;
        $w = $ancho - $mi - $md;
        $h = $alto - $ms - $mb;
        $n = count($puntos);
        $x = static fn (int $i): float => $mi + ($n <= 1 ? $w / 2 : $i * $w / ($n - 1));
        $y = static fn (float $v): float => $ms + $h - ($v / 100) * $h;
        // Con muchos intentos se rotula solo uno de cada $paso para que los números no se encimen.
        $paso = max(1, (int) ceil($n / 12));

        $svg = '<div class="grafica-contenedor"><svg class="grafica" viewBox="0 0 ' . $ancho . ' ' . $alto . '" role="img" aria-labelledby="g-titulo g-desc" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<title id="g-titulo">' . e($titulo) . '</title>';
        $desc = [];
        foreach ($puntos as $i => $p) {
            $desc[] = 'Intento ' . ($i + 1) . ': ' . porcentaje($p['valor']);
        }
        $svg .= '<desc id="g-desc">' . e(implode('; ', $desc)) . '</desc>';
        foreach ([0, 25, 50, 75, 100] as $marca) {
            $yy = $y($marca);
            $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="grafica-rejilla"/>', $mi, $yy, $ancho - $md, $yy);
            $svg .= sprintf('<text x="%d" y="%.1f" class="grafica-eje" text-anchor="end">%d %%</text>', $mi - 6, $yy + 5, $marca);
        }
        $ym = $y(Calificador::META);
        $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="grafica-meta"/>', $mi, $ym, $ancho - $md, $ym);
        $svg .= sprintf('<text x="%d" y="%.1f" class="grafica-meta-texto" text-anchor="end">Meta %d %%</text>', $ancho - $md, $ym - 6, (int) Calificador::META);
        if ($n > 1) {
            $ruta = [];
            foreach ($puntos as $i => $p) {
                $ruta[] = sprintf('%.1f,%.1f', $x($i), $y((float) $p['valor']));
            }
            $svg .= '<polyline points="' . implode(' ', $ruta) . '" class="grafica-linea"/>';
        }
        foreach ($puntos as $i => $p) {
            $svg .= sprintf('<circle cx="%.1f" cy="%.1f" r="5" class="grafica-punto"><title>%s</title></circle>',
                $x($i), $y((float) $p['valor']), e($p['etiqueta'] . ': ' . porcentaje($p['valor'])));
            if ($i % $paso === 0 || $i === $n - 1) {
                $svg .= sprintf('<text x="%.1f" y="%d" class="grafica-eje" text-anchor="middle">%d</text>', $x($i), $alto - 10, $i + 1);
            }
        }
        return $svg . '</svg></div>';
    }

    /**
     * Barras horizontales de porcentaje de error, en el orden en que llegan. Se dibujan con
     * texto y <progress> en HTML para que sigan siendo legibles en pantallas angostas.
     */
    public static function barras(array $filas, string $titulo): string
    {
        $html = '<ul class="barras barras-error" aria-label="' . e($titulo) . '">';
        foreach ($filas as $f) {
            $valor = max(0.0, min(100.0, (float) $f['porcentaje']));
            $html .= '<li><div class="d-flex justify-content-between gap-2 small"><span>' . e($f['nombre']) . '</span>'
                . '<strong>' . e(porcentaje($valor)) . '</strong></div>'
                . '<progress class="barra-avance" max="100" value="' . e((string) $valor) . '" aria-label="' . e($f['nombre'] . ': ' . porcentaje($valor) . ' de error') . '"></progress></li>';
        }
        return $html . '</ul>';
    }
}
