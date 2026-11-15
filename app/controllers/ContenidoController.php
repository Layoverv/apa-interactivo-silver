<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Ejemplo;
use App\Models\Recurso;
use App\Models\Tema;

// Módulo 1: contenido teórico (HU-03), ejemplos (HU-04) y recursos (HU-10).
final class ContenidoController
{
    public function lista(): void
    {
        View::render('estudiante/temas', ['titulo' => 'Contenido APA', 'temas' => Tema::publicados()]);
    }

    public function detalle(int $id): void
    {
        $tema = Tema::buscarPublicado($id);
        // El generador y el administrador pueden previsualizar un tema oculto.
        if ($tema === null && Auth::tieneRol('generador', 'administrador')) {
            $tema = Tema::buscar($id);
        }
        if ($tema === null) {
            abortar(404);
        }
        View::render('estudiante/tema', [
            'titulo'    => $tema['titulo'],
            'tema'      => $tema,
            'ejemplos'  => Ejemplo::deTema($id),
            'anterior'  => Tema::anterior($tema),
            'siguiente' => Tema::siguiente($tema),
            'esEstudiante' => Auth::tieneRol('estudiante'),
        ]);
    }

    public function recursos(): void
    {
        View::render('estudiante/recursos', ['titulo' => 'Recursos académicos', 'recursos' => Recurso::publicados()]);
    }
}
