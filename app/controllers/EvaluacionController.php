<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\Sesion;
use App\Core\View;
use App\Models\Ejercicio;
use App\Models\Evaluacion;
use App\Models\Intento;
use App\Models\Respuesta;
use App\Services\Calificador;

// Módulo 3: evaluación de 10 preguntas al azar (HU-07) y resultado detallado (HU-08), CU-06.
final class EvaluacionController
{
    public function portada(): void
    {
        $evaluacion = Evaluacion::activa();
        $historial = Intento::historialEvaluaciones(Auth::id());
        View::render('estudiante/evaluacion_portada', [
            'titulo'      => 'Evaluación',
            'evaluacion'  => $evaluacion,
            'disponibles' => $evaluacion ? Evaluacion::disponibles((int) $evaluacion['id']) : 0,
            'enCurso'     => Intento::evaluacionEnCurso(Auth::id()),
            'historial'   => array_slice(array_reverse($historial), 0, 5),
        ]);
    }

    public function iniciar(): void
    {
        // Si hay una evaluación sin enviar, se retoma la misma: así no se pueden sortear
        // preguntas nuevas hasta obtener unas más fáciles.
        $enCurso = Intento::evaluacionEnCurso(Auth::id());
        if ($enCurso !== null) {
            redirigir('/evaluacion/' . $enCurso['id']);
        }
        $evaluacion = Evaluacion::activa();
        $cantidad = (int) ($evaluacion['num_preguntas'] ?? 0);
        if ($evaluacion === null || Evaluacion::disponibles((int) $evaluacion['id']) < $cantidad) {
            Sesion::flash('warning', 'La evaluación no está disponible: el banco no tiene suficientes ejercicios activos.');
            redirigir('/evaluacion');
        }
        $ids = Evaluacion::sortearEjercicios((int) $evaluacion['id'], $cantidad);
        $intentoId = DB::transaccion(static function () use ($evaluacion, $ids): int {
            $id = Intento::crear(Auth::id(), 'evaluacion', (int) $evaluacion['id'], null, null, count($ids));
            Respuesta::crearFilas($id, $ids);
            return $id;
        });
        redirigir('/evaluacion/' . $intentoId);
    }

    public function presentar(int $id): void
    {
        $intento = Intento::delUsuario($id, Auth::id(), 'evaluacion') ?? abortar(404);
        if ($intento['estado'] === 'finalizado') {
            redirigir('/evaluacion/' . $id . '/resultado');
        }
        $formulario = Sesion::tomarFormulario();
        View::render('estudiante/evaluacion', [
            'titulo'      => 'Evaluación en curso',
            'intento'     => $intento,
            'evaluacion'  => Evaluacion::buscar((int) $intento['evaluacion_id']),
            'ejercicios'  => Ejercicio::paraResolver(Respuesta::idsDeIntento($id)),
            'elegidas'    => $formulario['respuestas'] ?? [],
            'faltantes'   => $formulario['faltantes'] ?? [],
        ]);
    }

    public function enviar(int $id): void
    {
        $intento = Intento::delUsuario($id, Auth::id(), 'evaluacion') ?? abortar(404);
        if ($intento['estado'] === 'finalizado') {
            redirigir('/evaluacion/' . $id . '/resultado');
        }
        $enviadas = [];
        foreach ((array) ($_POST['respuestas'] ?? []) as $ejercicioId => $opcionId) {
            if (ctype_digit((string) $ejercicioId) && ctype_digit((string) $opcionId)) {
                $enviadas[(int) $ejercicioId] = (int) $opcionId;
            }
        }
        try {
            $faltantes = Calificador::calificarEvaluacion($intento, $enviadas);
        } catch (\DomainException) {
            redirigir('/evaluacion/' . $id . '/resultado');
        } catch (\InvalidArgumentException $error) {
            Sesion::flash('danger', $error->getMessage());
            redirigir('/evaluacion/' . $id);
        }
        if ($faltantes !== []) {
            // CU-06, flujo 5a: se señalan las preguntas sin responder y no se envía.
            $n = count($faltantes);
            Sesion::flash('warning', $n === 1 ? 'Falta 1 pregunta por responder. Está señalada en la lista.' : "Faltan $n preguntas por responder. Están señaladas en la lista.");
            $_SESSION['formulario'] = ['respuestas' => $enviadas, 'faltantes' => $faltantes];
            redirigir('/evaluacion/' . $id);
        }
        redirigir('/evaluacion/' . $id . '/resultado');
    }

    public function resultado(int $id): void
    {
        $intento = Intento::delUsuario($id, Auth::id(), 'evaluacion') ?? abortar(404);
        if ($intento['estado'] !== 'finalizado') {
            redirigir('/evaluacion/' . $id);
        }
        $detalle = Respuesta::detalleDeIntento($id);
        View::render('estudiante/evaluacion_resultado', [
            'titulo'   => 'Resultado de la evaluación',
            'intento'  => $intento,
            'detalle'  => $detalle,
            'porTema'  => Calificador::resumenPorTema($detalle),
            'cumple'   => Calificador::cumpleMeta((float) $intento['puntaje']),
        ]);
    }
}
