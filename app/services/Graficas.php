<?php
declare(strict_types=1);

namespace App\Services;

// Gráficas sencillas en SVG generadas en el servidor, sin bibliotecas externas.
// Se usan en el progreso del estudiante (HU-09) y en los reportes de errores (HU-12).
final class Graficas
{
    /** Línea de evolución de la tasa de éxito, con la meta del 70 % como referencia. */
    public static function evolucion(array $puntos, string $titulo): string
    {
        $ancho = 640;
        $alto = 240;
        $mi = 44; // margen izquierdo
        $md = 16;
        $ms = 16;
        $mb = 34;
        $w = $ancho - $mi - $md;
        $h = $alto - $ms - $mb;
        $n = count($puntos);
        $x = static fn (int $i): float => $mi + ($n <= 1 ? $w / 2 : $i * $w / ($n - 1));
        $y = static fn (float $v): float => $ms + $h - ($v / 100) * $h;

        $svg = '<svg class="grafica" viewBox="0 0 ' . $ancho . ' ' . $alto . '" role="img" aria-labelledby="g-titulo g-desc" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<title id="g-titulo">' . e($titulo) . '</title>';
        $desc = [];
        foreach ($puntos as $i => $p) {
            $desc[] = 'Intento ' . ($i + 1) . ': ' . porcentaje($p['valor']);
        }
        $svg .= '<desc id="g-desc">' . e(implode('; ', $desc)) . '</desc>';
        foreach ([0, 25, 50, 75, 100] as $marca) {
            $yy = $y($marca);
            $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="grafica-rejilla"/>', $mi, $yy, $ancho - $md, $yy);
            $svg .= sprintf('<text x="%d" y="%.1f" class="grafica-eje" text-anchor="end">%d %%</text>', $mi - 6, $yy + 4, $marca);
        }
        $ym = $y(Calificador::META);
        $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="grafica-meta"/>', $mi, $ym, $ancho - $md, $ym);
        $svg .= sprintf('<text x="%d" y="%.1f" class="grafica-meta-texto" text-anchor="end">Meta 70 %%</text>', $ancho - $md, $ym - 5);
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
            $svg .= sprintf('<text x="%.1f" y="%d" class="grafica-eje" text-anchor="middle">%d</text>', $x($i), $alto - 12, $i + 1);
        }
        return $svg . '</svg>';
    }

    /** Barras horizontales de porcentaje, ordenadas como llegan. */
    public static function barras(array $filas, string $titulo): string
    {
        $alturaFila = 34;
        $etiqueta = 210;
        $ancho = 640;
        $barraMax = $ancho - $etiqueta - 70;
        $alto = max(1, count($filas)) * $alturaFila + 8;
        $svg = '<svg class="grafica" viewBox="0 0 ' . $ancho . ' ' . $alto . '" role="img" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<title>' . e($titulo) . '</title>';
        foreach (array_values($filas) as $i => $f) {
            $yy = 6 + $i * $alturaFila;
            $valor = (float) $f['porcentaje'];
            $nombre = mb_strlen($f['nombre']) > 30 ? mb_substr($f['nombre'], 0, 29) . '…' : $f['nombre'];
            $svg .= sprintf('<text x="0" y="%d" class="grafica-etiqueta">%s</text>', $yy + 17, e($nombre));
            $svg .= sprintf('<rect x="%d" y="%d" width="%d" height="20" rx="3" class="grafica-fondo"/>', $etiqueta, $yy + 3, $barraMax);
            $svg .= sprintf('<rect x="%d" y="%d" width="%.1f" height="20" rx="3" class="grafica-barra"><title>%s</title></rect>',
                $etiqueta, $yy + 3, $barraMax * $valor / 100, e($f['nombre'] . ': ' . porcentaje($valor)));
            $svg .= sprintf('<text x="%d" y="%d" class="grafica-valor">%s</text>', $etiqueta + $barraMax + 8, $yy + 17, e(porcentaje($valor)));
        }
        return $svg . '</svg>';
    }
}
