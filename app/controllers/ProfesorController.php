<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Grupo;
use App\Models\Intento;
use App\Models\Usuario;
use App\Services\Calificador;
use App\Services\Graficas;

// Panel del profesor: resultados de sus grupos (HU-11) y errores frecuentes (HU-12).
// El profesor consulta, pero no modifica resultados.
final class ProfesorController
{
    public function grupos(): void
    {
        View::render('profesor/grupos', ['titulo' => 'Mis grupos', 'grupos' => Grupo::visiblesPara(Auth::usuario())]);
    }

    public function grupo(int $id): void
    {
        $grupo = Grupo::visiblePara($id, Auth::usuario()) ?? abortar(404);
        $porTema = Intento::erroresDeGrupo($id, 'tema');
        $porTipo = Intento::erroresDeGrupo($id, 'tipo');
        View::render('profesor/grupo', [
            'titulo'       => 'Grupo ' . $grupo['nombre'],
            'grupo'        => $grupo,
            'estudiantes'  => Intento::estudiantesDeGrupo($id),
            'estadisticas' => Intento::estadisticas($id, null, null),
            'porTema'      => $porTema,
            'porTipo'      => $porTipo,
            'graficaTema'  => $porTema === [] ? '' : Graficas::barras($porTema, 'Porcentaje de error por tema'),
            'graficaTipo'  => $porTipo === [] ? '' : Graficas::barras($porTipo, 'Porcentaje de error por tipo de error'),
            'meta'         => Calificador::META,
        ]);
    }

    public function estudiante(int $id, int $uid): void
    {
        $grupo = Grupo::visiblePara($id, Auth::usuario()) ?? abortar(404);
        if (!Grupo::esMiembro($id, $uid)) {
            abortar(404);
        }
        $historial = Intento::historialEvaluaciones($uid);
        $puntos = array_map(static fn ($i) => ['valor' => (float) $i['puntaje'], 'etiqueta' => fecha_corta($i['finalizado_en'])], $historial);
        View::render('profesor/estudiante', [
            'titulo'     => 'Detalle del estudiante',
            'grupo'      => $grupo,
            'estudiante' => Usuario::buscar($uid),
            'historial'  => array_reverse($historial),
            'grafica'    => $puntos === [] ? '' : Graficas::evolucion($puntos, 'Evolución de la tasa de éxito del estudiante'),
            'avance'     => Intento::avancePorTema($uid),
            'practica'   => Intento::resumenPractica($uid),
        ]);
    }

    /**
     * Exporta los resultados del grupo en CSV. La HU-13 está clasificada como «Won't have»;
     * se conserva porque la versión anterior ya ofrecía una exportación (en Excel).
     */
    public function exportar(int $id): void
    {
        $grupo = Grupo::visiblePara($id, Auth::usuario()) ?? abortar(404);
        $archivo = 'resultados-' . preg_replace('/[^a-z0-9]+/i', '-', $grupo['nombre'] . '-' . $grupo['periodo']) . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel reconozca las tildes.
        fputcsv($salida, ['Estudiante', 'Correo', 'Intentos', 'Último puntaje (%)', 'Mejor tasa de éxito (%)', 'Fecha del último intento'], ';');
        foreach (Intento::estudiantesDeGrupo($id) as $f) {
            fputcsv($salida, [
                self::celdaSegura($f['nombre']), self::celdaSegura($f['correo']), $f['intentos'],
                $f['ultimo'] === null ? '' : str_replace('.', ',', (string) round((float) $f['ultimo'], 1)),
                $f['mejor'] === null ? '' : str_replace('.', ',', (string) round((float) $f['mejor'], 1)),
                $f['ultima_fecha'] ?? '',
            ], ';');
        }
        fclose($salida);
        exit;
    }

    /** Evita que una celda que empieza por =, +, - o @ se ejecute como fórmula en Excel. */
    private static function celdaSegura(string $valor): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valor) ? "'" . $valor : $valor;
    }
}
