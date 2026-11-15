<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Grupo;
use App\Models\Intento;
use App\Services\Calificador;
use App\Services\Graficas;

// Progreso e historial de intentos del estudiante (HU-09).
final class ProgresoController
{
    public function mostrar(): void
    {
        $id = Auth::id();
        $historial = Intento::historialEvaluaciones($id);
        $puntos = array_map(static fn ($i) => ['valor' => (float) $i['puntaje'], 'etiqueta' => fecha_corta($i['finalizado_en'])], $historial);
        $tasas = array_map(static fn ($i) => (float) $i['puntaje'], $historial);
        View::render('estudiante/progreso', [
            'titulo'     => 'Mi progreso',
            'historial'  => array_reverse($historial),
            'grafica'    => $puntos === [] ? '' : Graficas::evolucion($puntos, 'Evolución de la tasa de éxito por intento'),
            'promedio'   => $tasas === [] ? null : array_sum($tasas) / count($tasas),
            'mejor'      => $tasas === [] ? null : max($tasas),
            'practica'   => Intento::resumenPractica($id),
            'avance'     => Intento::avancePorTema($id),
            'grupos'     => Grupo::deEstudiante($id),
            'meta'       => Calificador::META,
        ]);
    }
}
