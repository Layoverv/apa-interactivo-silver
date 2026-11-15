<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\Sesion;
use App\Core\View;
use App\Models\Ejercicio;
use App\Models\Intento;
use App\Models\Respuesta;
use App\Models\Tema;
use App\Services\Calificador;

// Módulo 2: práctica guiada (HU-05) con retroalimentación inmediata en JSON (HU-06, CU-05).
final class PracticaController
{
    public function elegir(): void
    {
        View::render('estudiante/practica_elegir', [
            'titulo'      => 'Práctica guiada',
            'temas'       => Tema::publicados(),
            'conteo'      => Ejercicio::conteoPorTemaYNivel(),
            'temaElegido' => entrada_entero($_GET, 'tema'),
        ]);
    }

    public function iniciar(): void
    {
        $temaId = entrada_entero($_POST, 'tema_id');
        $dificultad = entrada_entero($_POST, 'dificultad');
        if ($dificultad !== null && ($dificultad < 1 || $dificultad > 3)) {
            $dificultad = null;
        }
        $tema = $temaId === null ? null : Tema::buscarPublicado($temaId);
        if ($tema === null) {
            Sesion::flash('danger', 'Elige un tema para practicar.');
            redirigir('/practica');
        }
        $ids = Ejercicio::idsParaPractica($temaId, $dificultad);
        if ($ids === []) {
            Sesion::flash('warning', 'Este tema todavía no tiene ejercicios activos para el nivel elegido. Prueba con otro nivel.');
            redirigir('/practica?tema=' . $temaId);
        }
        $intentoId = DB::transaccion(static function () use ($temaId, $dificultad, $ids): int {
            $id = Intento::crear(Auth::id(), 'practica', null, $temaId, $dificultad, count($ids));
            Respuesta::crearFilas($id, $ids);
            return $id;
        });
        redirigir('/practica/' . $intentoId);
    }

    public function mostrar(int $id): void
    {
        $intento = Intento::delUsuario($id, Auth::id(), 'practica') ?? abortar(404);
        $respuestas = [];
        foreach (Respuesta::deIntento($id) as $r) {
            $respuestas[(int) $r['ejercicio_id']] = $r;
        }
        $ejercicios = [];
        foreach (Ejercicio::paraResolver(array_keys($respuestas)) as $e) {
            $r = $respuestas[(int) $e['id']];
            $e['resultado'] = null;
            // Solo se incluye la retroalimentación de los ejercicios ya respondidos.
            if ($r['opcion_id'] !== null) {
                $verificacion = Ejercicio::verificar((int) $e['id'], (int) $r['opcion_id']);
                $e['resultado'] = ($verificacion ?? []) + ['opcion_id' => (int) $r['opcion_id']];
            }
            $e['nivel'] = nivel_texto((int) $e['dificultad']);
            $e['tipo_texto'] = tipo_ejercicio_texto($e['tipo']);
            $ejercicios[] = $e;
        }
        View::render('estudiante/practica', [
            'titulo'     => 'Práctica: ' . $intento['tema'],
            'intento'    => $intento,
            'ejercicios' => $ejercicios,
        ]);
    }

    /** Recibe una respuesta y devuelve la retroalimentación en JSON, sin recargar (HU-06). */
    public function responder(int $id): void
    {
        $intento = Intento::delUsuario($id, Auth::id(), 'practica');
        if ($intento === null) {
            json_respuesta(['error' => 'Práctica no encontrada.'], 404);
        }
        $datos = json_decode((string) file_get_contents('php://input'), true);
        $datos = is_array($datos) ? $datos : $_POST;
        $ejercicioId = entrada_entero($datos, 'ejercicio_id');
        $opcionId = entrada_entero($datos, 'opcion_id');
        if ($ejercicioId === null || $opcionId === null) {
            json_respuesta(['error' => 'Selecciona una opción antes de responder.'], 422);
        }
        if (!in_array($ejercicioId, Respuesta::idsDeIntento($id), true)) {
            json_respuesta(['error' => 'El ejercicio no pertenece a esta práctica.'], 404);
        }
        $verificacion = Ejercicio::verificar($ejercicioId, $opcionId);
        if ($verificacion === null) {
            json_respuesta(['error' => 'La opción no corresponde al ejercicio.'], 422);
        }
        if (!Respuesta::registrar($id, $ejercicioId, $opcionId, $verificacion['es_correcta'])) {
            json_respuesta(['error' => 'Este ejercicio ya fue respondido.'], 409);
        }
        Intento::actualizarConteo($id);
        $conteo = Intento::conteo($id);
        $terminado = $conteo['respondidas'] >= $conteo['total'];
        if ($terminado) {
            Intento::finalizar($id, $conteo['correctas'], $conteo['total'], Calificador::tasaExito($conteo['correctas'], $conteo['total']));
        }
        json_respuesta($verificacion + [
            'respondidas'  => $conteo['respondidas'],
            'correctas'    => $conteo['correctas'],
            'total'        => $conteo['total'],
            'terminado'    => $terminado,
            'resumen_url'  => url('/practica/' . $id . '/resumen'),
        ]);
    }

    /** Aciertos de la práctica al terminar (HU-05, criterio 4). */
    public function resumen(int $id): void
    {
        $intento = Intento::delUsuario($id, Auth::id(), 'practica') ?? abortar(404);
        $detalle = array_values(array_filter(Respuesta::detalleDeIntento($id), static fn ($f) => $f['es_correcta'] !== null));
        $tema = Tema::buscar((int) $intento['tema_id']);
        View::render('estudiante/practica_resumen', [
            'titulo'    => 'Resumen de la práctica',
            'intento'   => $intento,
            'conteo'    => Intento::conteo($id),
            'detalle'   => $detalle,
            'siguiente' => $tema ? Tema::siguiente($tema) : null,
        ]);
    }
}
