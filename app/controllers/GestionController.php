<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Sesion;
use App\Core\View;
use App\Models\Ejemplo;
use App\Models\Ejercicio;
use App\Models\Recurso;
use App\Models\Tema;
use App\Models\TipoError;

// Generador de contenidos: temas y ejemplos (HU-15), banco de ejercicios (HU-16) y
// recursos recomendados (HU-10, que exige poder actualizarlos desde la base de datos).
final class GestionController
{
    // ---------- Temas ----------

    public function temas(): void
    {
        View::render('gestion/temas', ['titulo' => 'Gestión de temas', 'temas' => Tema::todos()]);
    }

    public function nuevoTema(): void
    {
        View::render('gestion/tema_form', ['titulo' => 'Nuevo tema', 'tema' => null, 'ejemplos' => [], 'formulario' => Sesion::tomarFormulario()]);
    }

    public function crearTema(): void
    {
        [$d, $errores] = self::validarTema();
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/temas/nuevo');
        }
        $id = Tema::crear($d['titulo'], $d['resumen'], $d['contenido'], $d['publicado'], Auth::id());
        Sesion::flash('success', 'Tema creado. Agrega al menos un ejemplo correcto y uno incorrecto.');
        redirigir('/gestion/temas/' . $id);
    }

    public function editarTema(int $id): void
    {
        $tema = Tema::buscar($id) ?? abortar(404);
        View::render('gestion/tema_form', [
            'titulo' => 'Editar tema', 'tema' => $tema, 'ejemplos' => Ejemplo::deTema($id), 'formulario' => Sesion::tomarFormulario(),
        ]);
    }

    public function actualizarTema(int $id): void
    {
        Tema::buscar($id) ?? abortar(404);
        [$d, $errores] = self::validarTema();
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/temas/' . $id);
        }
        Tema::actualizar($id, $d['titulo'], $d['resumen'], $d['contenido'], $d['publicado']);
        Sesion::flash('success', 'Tema guardado. Los cambios ya se ven en el módulo de contenido.');
        redirigir('/gestion/temas/' . $id);
    }

    public function moverTema(int $id): void
    {
        Tema::buscar($id) ?? abortar(404);
        Tema::mover($id, ($_POST['direccion'] ?? '') === 'arriba' ? 'arriba' : 'abajo');
        redirigir('/gestion');
    }

    public function eliminarTema(int $id): void
    {
        $tema = Tema::buscar($id) ?? abortar(404);
        if (Tema::tieneEjercicios($id)) {
            Sesion::flash('warning', 'El tema tiene ejercicios en el banco, por eso no se elimina. Puedes ocultarlo desmarcando «Publicado».');
            redirigir('/gestion/temas/' . $id);
        }
        Tema::eliminar($id);
        Sesion::flash('success', 'Se eliminó el tema «' . $tema['titulo'] . '».');
        redirigir('/gestion');
    }

    public function crearEjemplo(int $id): void
    {
        Tema::buscar($id) ?? abortar(404);
        [$d, $errores] = self::validarEjemplo();
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/temas/' . $id . '#ejemplos');
        }
        Ejemplo::crear($id, $d['es_correcto'], $d['texto'], $d['explicacion']);
        Sesion::flash('success', 'Ejemplo agregado.');
        redirigir('/gestion/temas/' . $id . '#ejemplos');
    }

    public function actualizarEjemplo(int $id): void
    {
        $ejemplo = Ejemplo::buscar($id) ?? abortar(404);
        [$d, $errores] = self::validarEjemplo();
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/temas/' . $ejemplo['tema_id'] . '#ejemplos');
        }
        Ejemplo::actualizar($id, $d['es_correcto'], $d['texto'], $d['explicacion']);
        Sesion::flash('success', 'Ejemplo actualizado.');
        redirigir('/gestion/temas/' . $ejemplo['tema_id'] . '#ejemplos');
    }

    public function eliminarEjemplo(int $id): void
    {
        $ejemplo = Ejemplo::buscar($id) ?? abortar(404);
        Ejemplo::eliminar($id);
        Sesion::flash('success', 'Ejemplo eliminado.');
        redirigir('/gestion/temas/' . $ejemplo['tema_id'] . '#ejemplos');
    }

    // ---------- Banco de ejercicios ----------

    public function ejercicios(): void
    {
        $temaId = entrada_entero($_GET, 'tema');
        $nivel = entrada_entero($_GET, 'nivel');
        $estado = in_array($_GET['estado'] ?? '', ['activos', 'inactivos', 'todos'], true) ? $_GET['estado'] : 'activos';
        View::render('gestion/ejercicios', [
            'titulo'     => 'Banco de ejercicios',
            'ejercicios' => Ejercicio::listar($temaId, $nivel, $estado),
            'temas'      => Tema::todos(),
            'filtros'    => ['tema' => $temaId, 'nivel' => $nivel, 'estado' => $estado],
        ]);
    }

    public function nuevoEjercicio(): void
    {
        View::render('gestion/ejercicio_form', [
            'titulo' => 'Nuevo ejercicio', 'ejercicio' => null, 'temas' => Tema::todos(), 'tiposError' => TipoError::todos(),
            'formulario' => Sesion::tomarFormulario() + ['tema_id' => entrada_entero($_GET, 'tema')],
        ]);
    }

    public function crearEjercicio(): void
    {
        [$d, $opciones, $correcta, $enEvaluacion, $errores] = self::validarEjercicio();
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/ejercicios/nuevo');
        }
        $id = Ejercicio::guardar(null, $d, $opciones, $correcta, $enEvaluacion, Auth::id());
        Sesion::flash('success', 'Ejercicio creado y activo.');
        redirigir('/gestion/ejercicios/' . $id);
    }

    public function editarEjercicio(int $id): void
    {
        $ejercicio = Ejercicio::buscar($id) ?? abortar(404);
        View::render('gestion/ejercicio_form', [
            'titulo' => 'Editar ejercicio', 'ejercicio' => $ejercicio, 'temas' => Tema::todos(),
            'tiposError' => TipoError::todos(), 'formulario' => Sesion::tomarFormulario(),
        ]);
    }

    public function actualizarEjercicio(int $id): void
    {
        Ejercicio::buscar($id) ?? abortar(404);
        [$d, $opciones, $correcta, $enEvaluacion, $errores] = self::validarEjercicio();
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/ejercicios/' . $id);
        }
        try {
            Ejercicio::guardar($id, $d, $opciones, $correcta, $enEvaluacion, Auth::id());
        } catch (\DomainException $error) {
            volver_con_errores([$error->getMessage()], '/gestion/ejercicios/' . $id);
        }
        Sesion::flash('success', 'Ejercicio guardado.');
        redirigir('/gestion/ejercicios/' . $id);
    }

    /** Los ejercicios se desactivan sin borrarlos (HU-16, criterio 3). */
    public function cambiarEstadoEjercicio(int $id): void
    {
        Ejercicio::buscar($id) ?? abortar(404);
        $activar = ($_POST['activo'] ?? '') === '1';
        Ejercicio::cambiarEstado($id, $activar);
        Sesion::flash('success', $activar ? 'Ejercicio activado.' : 'Ejercicio desactivado: ya no aparece en prácticas ni evaluaciones nuevas.');
        redirigir('/gestion/ejercicios/' . $id);
    }

    // ---------- Recursos ----------

    public function recursos(): void
    {
        View::render('gestion/recursos', ['titulo' => 'Recursos recomendados', 'recursos' => Recurso::todos(), 'formulario' => Sesion::tomarFormulario()]);
    }

    public function crearRecurso(): void
    {
        [$d, $errores] = self::validarRecurso(0);
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/recursos');
        }
        Recurso::crear($d);
        Sesion::flash('success', 'Recurso agregado.');
        redirigir('/gestion/recursos');
    }

    public function actualizarRecurso(int $id): void
    {
        Recurso::buscar($id) ?? abortar(404);
        [$d, $errores] = self::validarRecurso($id);
        if ($errores !== []) {
            volver_con_errores($errores, '/gestion/recursos');
        }
        Recurso::actualizar($id, $d);
        Sesion::flash('success', 'Recurso actualizado.');
        redirigir('/gestion/recursos');
    }

    // ---------- Validaciones ----------

    private static function validarTema(): array
    {
        $d = [
            'titulo'    => entrada_texto($_POST, 'titulo', 150),
            'resumen'   => entrada_texto($_POST, 'resumen', 255),
            'contenido' => entrada_texto($_POST, 'contenido', 20000),
            'publicado' => ($_POST['publicado'] ?? '') === '1',
        ];
        $errores = [];
        if ($d['titulo'] === '') {
            $errores[] = 'Escribe el título del tema.';
        }
        if ($d['publicado'] && mb_strlen($d['contenido']) < 20) {
            $errores[] = 'Para publicar el tema, escribe su explicación (al menos 20 caracteres).';
        }
        return [$d, $errores];
    }

    private static function validarEjemplo(): array
    {
        $d = [
            'es_correcto' => ($_POST['es_correcto'] ?? '') === '1',
            'texto'       => entrada_texto($_POST, 'texto', 3000),
            'explicacion' => entrada_texto($_POST, 'explicacion', 2000),
        ];
        $errores = [];
        if ($d['texto'] === '') {
            $errores[] = 'Escribe el texto del ejemplo.';
        }
        if ($d['explicacion'] === '') {
            $errores[] = $d['es_correcto'] ? 'Explica por qué el ejemplo es correcto.' : 'En un ejemplo incorrecto, señala el error y su corrección (HU-04).';
        }
        return [$d, $errores];
    }

    private static function validarEjercicio(): array
    {
        $temaId = entrada_entero($_POST, 'tema_id');
        $tipoErrorId = entrada_entero($_POST, 'tipo_error_id');
        $tipo = (string) ($_POST['tipo'] ?? '');
        $dificultad = entrada_entero($_POST, 'dificultad');
        $d = [
            'tema_id'       => $temaId,
            'tipo_error_id' => $tipoErrorId,
            'tipo'          => $tipo,
            'dificultad'    => $dificultad,
            'enunciado'     => entrada_texto($_POST, 'enunciado', 2000),
            'explicacion'   => entrada_texto($_POST, 'explicacion', 2000),
        ];
        // Se conservan las posiciones: la opción correcta se indica por su número de fila.
        $filas = array_slice(array_values((array) ($_POST['opciones'] ?? [])), 0, 4);
        $correctaFila = entrada_entero($_POST, 'correcta');
        $opciones = [];
        $correcta = -1;
        foreach ($filas as $i => $texto) {
            $texto = mb_substr(trim((string) $texto), 0, 500);
            if ($texto === '') {
                continue;
            }
            if ($correctaFila === $i) {
                $correcta = count($opciones);
            }
            $opciones[] = $texto;
        }
        $enEvaluacion = ($_POST['en_evaluacion'] ?? '') === '1';

        $errores = [];
        if ($temaId === null || Tema::buscar($temaId) === null) {
            $errores[] = 'Selecciona el tema del ejercicio.';
        }
        if ($tipoErrorId === null || !TipoError::existe($tipoErrorId)) {
            $errores[] = 'Selecciona el tipo de error.';
        }
        if (!in_array($tipo, ['seleccion_multiple', 'verdadero_falso', 'completar'], true)) {
            $errores[] = 'Selecciona el tipo de ejercicio.';
        }
        if ($dificultad === null || $dificultad < 1 || $dificultad > 3) {
            $errores[] = 'Selecciona el nivel de dificultad.';
        }
        if ($d['enunciado'] === '') {
            $errores[] = 'Escribe el enunciado.';
        }
        if ($d['explicacion'] === '') {
            $errores[] = 'No se guarda un ejercicio sin explicación (HU-16).';
        }
        if (count($opciones) < 2 || count($opciones) > 4) {
            $errores[] = 'El ejercicio debe tener entre dos y cuatro opciones.';
        } elseif ($tipo === 'verdadero_falso' && count($opciones) !== 2) {
            $errores[] = 'Un ejercicio de verdadero o falso tiene exactamente dos opciones.';
        }
        if ($correcta < 0) {
            $errores[] = 'Marca la respuesta correcta en una opción con texto.';
        }
        if (count(array_unique(array_map('mb_strtolower', $opciones))) !== count($opciones)) {
            $errores[] = 'Las opciones no pueden repetirse.';
        }
        return [$d, $opciones, $correcta, $enEvaluacion, $errores];
    }

    private static function validarRecurso(int $id): array
    {
        $d = [
            'titulo'      => entrada_texto($_POST, 'titulo', 150),
            'descripcion' => entrada_texto($_POST, 'descripcion', 255),
            'url'         => entrada_texto($_POST, 'url', 500),
            'tipo'        => entrada_texto($_POST, 'tipo', 60),
            'publicado'   => ($_POST['publicado'] ?? '') === '1',
        ];
        $errores = [];
        if ($d['titulo'] === '') {
            $errores[] = 'Escribe el título del recurso.';
        } elseif (Recurso::existeTitulo($d['titulo'], $id)) {
            $errores[] = 'Ya existe un recurso con ese título.';
        }
        if (!filter_var($d['url'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $d['url'])) {
            $errores[] = 'La dirección debe ser un enlace web completo que empiece por https://';
        }
        if ($d['descripcion'] === '') {
            $errores[] = 'Escribe una descripción breve del recurso (HU-10).';
        }
        if ($d['tipo'] === '') {
            $d['tipo'] = 'Académico';
        }
        return [$d, $errores];
    }
}
