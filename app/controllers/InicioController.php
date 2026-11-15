<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Evaluacion;
use App\Models\Intento;
use App\Models\Tema;
use App\Services\Permisos;

final class InicioController
{
    /** Página pública de presentación o panel del estudiante (Tabla 20). */
    public function inicio(): void
    {
        $usuario = Auth::usuario();
        if ($usuario === null) {
            View::render('auth/portada', ['titulo' => 'Aprende APA 7']);
            return;
        }
        Auth::exigir([]);
        if ($usuario['rol'] !== Permisos::ESTUDIANTE) {
            redirigir(Permisos::inicio($usuario['rol']));
        }

        $avance = [];
        foreach (Intento::avancePorTema($usuario['id']) as $fila) {
            $avance[(int) $fila['id']] = $fila;
        }
        $historial = Intento::historialEvaluaciones($usuario['id']);
        View::render('estudiante/panel', [
            'titulo'     => 'Mi panel',
            'usuario'    => $usuario,
            'temas'      => Tema::publicados(),
            'avance'     => $avance,
            'ultima'     => $historial === [] ? null : end($historial),
            'enCurso'    => Intento::evaluacionEnCurso($usuario['id']),
            'evaluacion' => Evaluacion::activa(),
        ]);
    }
}
