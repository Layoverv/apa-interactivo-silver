<?php
declare(strict_types=1);

// Mapa de rutas. El tercer argumento indica quién puede usar cada ruta:
// null = pública, [] = cualquier usuario con sesión, o la lista de roles autorizados.

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ContenidoController;
use App\Controllers\EvaluacionController;
use App\Controllers\GestionController;
use App\Controllers\InicioController;
use App\Controllers\PracticaController;
use App\Controllers\ProfesorController;
use App\Controllers\ProgresoController;

$E = ['estudiante'];
$P = ['profesor', 'administrador'];
$A = ['administrador'];
$G = ['generador', 'administrador'];

// Inicio y autenticación (HU-01, HU-02)
$router->get('/', [InicioController::class, 'inicio']);
$router->get('/politica-datos', [AuthController::class, 'politica']);
$router->get('/registro', [AuthController::class, 'formularioRegistro']);
$router->post('/registro', [AuthController::class, 'registrar']);
$router->get('/ingresar', [AuthController::class, 'formularioIngreso']);
$router->post('/ingresar', [AuthController::class, 'ingresar']);
$router->post('/salir', [AuthController::class, 'salir'], []);
$router->get('/aceptar-politica', [AuthController::class, 'formularioAceptarPolitica'], []);
$router->post('/aceptar-politica', [AuthController::class, 'aceptarPolitica'], []);
$router->get('/cambiar-clave', [AuthController::class, 'formularioCambiarClave'], []);
$router->post('/cambiar-clave', [AuthController::class, 'cambiarClave'], []);
$router->get('/api/sesion', [AuthController::class, 'mantenerSesion'], []);

// Módulo 1: contenido (HU-03, HU-04) y recursos (HU-10)
$router->get('/temas', [ContenidoController::class, 'lista'], []);
$router->get('/temas/{id}', [ContenidoController::class, 'detalle'], []);
$router->get('/recursos', [ContenidoController::class, 'recursos'], []);

// Módulo 2: práctica guiada (HU-05, HU-06)
$router->get('/practica', [PracticaController::class, 'elegir'], $E);
$router->post('/practica/iniciar', [PracticaController::class, 'iniciar'], $E);
$router->get('/practica/{id}', [PracticaController::class, 'mostrar'], $E);
$router->post('/api/practica/{id}/responder', [PracticaController::class, 'responder'], $E);
$router->get('/practica/{id}/resumen', [PracticaController::class, 'resumen'], $E);

// Módulo 3: evaluación (HU-07, HU-08) y progreso (HU-09)
$router->get('/evaluacion', [EvaluacionController::class, 'portada'], $E);
$router->post('/evaluacion/iniciar', [EvaluacionController::class, 'iniciar'], $E);
$router->get('/evaluacion/{id}', [EvaluacionController::class, 'presentar'], $E);
$router->post('/evaluacion/{id}/enviar', [EvaluacionController::class, 'enviar'], $E);
$router->get('/evaluacion/{id}/resultado', [EvaluacionController::class, 'resultado'], $E);
$router->get('/progreso', [ProgresoController::class, 'mostrar'], $E);

// Profesor (HU-11, HU-12, HU-13)
$router->get('/profesor', [ProfesorController::class, 'grupos'], $P);
$router->get('/profesor/grupos/{id}', [ProfesorController::class, 'grupo'], $P);
$router->get('/profesor/grupos/{id}/estudiantes/{uid}', [ProfesorController::class, 'estudiante'], $P);
$router->get('/profesor/grupos/{id}/exportar', [ProfesorController::class, 'exportar'], $P);

// Administración (HU-14, HU-17)
$router->get('/admin', [AdminController::class, 'estadisticas'], $A);
$router->get('/admin/usuarios', [AdminController::class, 'usuarios'], $A);
$router->get('/admin/usuarios/nuevo', [AdminController::class, 'nuevoUsuario'], $A);
$router->post('/admin/usuarios/nuevo', [AdminController::class, 'crearUsuario'], $A);
$router->get('/admin/usuarios/{id}', [AdminController::class, 'editarUsuario'], $A);
$router->post('/admin/usuarios/{id}', [AdminController::class, 'actualizarUsuario'], $A);
$router->post('/admin/usuarios/{id}/estado', [AdminController::class, 'cambiarEstadoUsuario'], $A);
$router->get('/admin/grupos', [AdminController::class, 'grupos'], $A);
$router->post('/admin/grupos', [AdminController::class, 'crearGrupo'], $A);
$router->get('/admin/grupos/{id}', [AdminController::class, 'editarGrupo'], $A);
$router->post('/admin/grupos/{id}', [AdminController::class, 'actualizarGrupo'], $A);
$router->post('/admin/grupos/{id}/estudiantes', [AdminController::class, 'agregarEstudiantes'], $A);
$router->post('/admin/grupos/{id}/estudiantes/{uid}/quitar', [AdminController::class, 'quitarEstudiante'], $A);

// Generador de contenidos (HU-15, HU-16) y recursos (HU-10)
$router->get('/gestion', [GestionController::class, 'temas'], $G);
$router->get('/gestion/temas/nuevo', [GestionController::class, 'nuevoTema'], $G);
$router->post('/gestion/temas/nuevo', [GestionController::class, 'crearTema'], $G);
$router->get('/gestion/temas/{id}', [GestionController::class, 'editarTema'], $G);
$router->post('/gestion/temas/{id}', [GestionController::class, 'actualizarTema'], $G);
$router->post('/gestion/temas/{id}/mover', [GestionController::class, 'moverTema'], $G);
$router->post('/gestion/temas/{id}/eliminar', [GestionController::class, 'eliminarTema'], $G);
$router->post('/gestion/temas/{id}/ejemplos', [GestionController::class, 'crearEjemplo'], $G);
$router->post('/gestion/ejemplos/{id}', [GestionController::class, 'actualizarEjemplo'], $G);
$router->post('/gestion/ejemplos/{id}/eliminar', [GestionController::class, 'eliminarEjemplo'], $G);
$router->get('/gestion/ejercicios', [GestionController::class, 'ejercicios'], $G);
$router->get('/gestion/ejercicios/nuevo', [GestionController::class, 'nuevoEjercicio'], $G);
$router->post('/gestion/ejercicios/nuevo', [GestionController::class, 'crearEjercicio'], $G);
$router->get('/gestion/ejercicios/{id}', [GestionController::class, 'editarEjercicio'], $G);
$router->post('/gestion/ejercicios/{id}', [GestionController::class, 'actualizarEjercicio'], $G);
$router->post('/gestion/ejercicios/{id}/estado', [GestionController::class, 'cambiarEstadoEjercicio'], $G);
$router->get('/gestion/recursos', [GestionController::class, 'recursos'], $G);
$router->post('/gestion/recursos', [GestionController::class, 'crearRecurso'], $G);
$router->post('/gestion/recursos/{id}', [GestionController::class, 'actualizarRecurso'], $G);
