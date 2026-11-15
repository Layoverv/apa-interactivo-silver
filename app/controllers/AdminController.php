<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Sesion;
use App\Core\View;
use App\Models\Grupo;
use App\Models\Intento;
use App\Models\Usuario;
use App\Services\Calificador;
use App\Services\Graficas;

// Administración: usuarios, roles y grupos (HU-14) y estadísticas generales (HU-17).
final class AdminController
{
    public function estadisticas(): void
    {
        $grupoId = entrada_entero($_GET, 'grupo');
        $desde = self::fecha($_GET['desde'] ?? null);
        $hasta = self::fecha($_GET['hasta'] ?? null);
        $estadisticas = Intento::estadisticas($grupoId, $desde, $hasta);
        $errores = Intento::erroresGenerales($grupoId, $desde, $hasta);
        View::render('admin/estadisticas', [
            'titulo'       => 'Estadísticas generales',
            'estadisticas' => $estadisticas,
            'usuarios'     => Usuario::contarActivosPorRol(),
            'grupos'       => Grupo::todos(),
            'filtros'      => ['grupo' => $grupoId, 'desde' => $desde, 'hasta' => $hasta],
            'cumple'       => Calificador::cumpleMeta($estadisticas['tasa_promedio']),
            'grafica'      => $errores === [] ? '' : Graficas::barras($errores, 'Porcentaje de error por tema'),
            'meta'         => Calificador::META,
        ]);
    }

    public function usuarios(): void
    {
        $buscar = entrada_texto($_GET, 'q', 100);
        $rol = entrada_texto($_GET, 'rol', 20);
        View::render('admin/usuarios', [
            'titulo'   => 'Usuarios',
            'usuarios' => Usuario::listar($buscar, $rol),
            'roles'    => Usuario::roles(),
            'buscar'   => $buscar,
            'rol'      => $rol,
        ]);
    }

    public function nuevoUsuario(): void
    {
        View::render('admin/usuario_form', [
            'titulo' => 'Nuevo usuario', 'usuario' => null, 'roles' => Usuario::roles(), 'formulario' => Sesion::tomarFormulario(),
        ]);
    }

    public function crearUsuario(): void
    {
        [$datos, $errores] = self::validarUsuario(null);
        $clave = (string) ($_POST['clave'] ?? '');
        $errores = array_merge($errores, AuthController::validarClave($clave, (string) ($_POST['confirmacion'] ?? '')));
        if ($errores !== []) {
            self::volver($errores, '/admin/usuarios/nuevo');
        }
        // La clave es temporal: el usuario debe cambiarla y aceptar la política al ingresar.
        $id = Usuario::crear($datos + ['clave' => $clave, 'debe_cambiar_clave' => 1]);
        Sesion::flash('success', 'Usuario creado. Entrégale la contraseña temporal: deberá cambiarla y aceptar la política de datos al ingresar.');
        redirigir('/admin/usuarios/' . $id);
    }

    public function editarUsuario(int $id): void
    {
        $usuario = Usuario::buscar($id) ?? abortar(404);
        View::render('admin/usuario_form', [
            'titulo' => 'Editar usuario', 'usuario' => $usuario, 'roles' => Usuario::roles(),
            'formulario' => Sesion::tomarFormulario(), 'grupos' => Grupo::deEstudiante($id),
        ]);
    }

    public function actualizarUsuario(int $id): void
    {
        Usuario::buscar($id) ?? abortar(404);
        [$datos, $errores] = self::validarUsuario($id);
        if ($id === Auth::id() && $datos['rol_id'] !== Usuario::rolIdPorClave('administrador')) {
            $errores[] = 'No puedes quitarte a ti mismo el rol de administrador.';
        }
        $clave = (string) ($_POST['clave'] ?? '');
        if ($clave !== '') {
            $errores = array_merge($errores, AuthController::validarClave($clave, (string) ($_POST['confirmacion'] ?? '')));
        }
        if ($errores !== []) {
            self::volver($errores, '/admin/usuarios/' . $id);
        }
        Usuario::actualizar($id, $datos['nombre'], $datos['correo'], $datos['rol_id']);
        if ($clave !== '') {
            Usuario::cambiarClave($id, $clave, true);
        }
        Sesion::flash('success', 'Usuario actualizado.');
        redirigir('/admin/usuarios/' . $id);
    }

    /** Los usuarios se desactivan, no se eliminan, para conservar sus resultados (HU-14). */
    public function cambiarEstadoUsuario(int $id): void
    {
        $usuario = Usuario::buscar($id) ?? abortar(404);
        if ($id === Auth::id()) {
            Sesion::flash('danger', 'No puedes desactivar tu propia cuenta.');
            redirigir('/admin/usuarios/' . $id);
        }
        $activar = ($_POST['activo'] ?? '') === '1';
        Usuario::cambiarEstado($id, $activar);
        Sesion::flash('success', $activar ? 'Usuario activado.' : 'Usuario desactivado. Ya no podrá iniciar sesión; sus resultados se conservan.');
        redirigir('/admin/usuarios/' . $usuario['id']);
    }

    public function grupos(): void
    {
        View::render('admin/grupos', [
            'titulo' => 'Grupos', 'grupos' => Grupo::todos(), 'profesores' => Usuario::porRol('profesor'),
            'formulario' => Sesion::tomarFormulario(),
        ]);
    }

    public function crearGrupo(): void
    {
        [$nombre, $periodo, $profesorId, $errores] = self::validarGrupo(0);
        if ($errores !== []) {
            self::volver($errores, '/admin/grupos');
        }
        $id = Grupo::crear($nombre, $periodo, $profesorId);
        Sesion::flash('success', 'Grupo creado. Ahora agrega sus estudiantes.');
        redirigir('/admin/grupos/' . $id);
    }

    public function editarGrupo(int $id): void
    {
        $grupo = Grupo::buscar($id) ?? abortar(404);
        $miembros = Grupo::miembros($id);
        $idsMiembros = array_column($miembros, 'id');
        $disponibles = array_values(array_filter(Usuario::porRol('estudiante'), static fn ($u) => !in_array($u['id'], $idsMiembros, false)));
        View::render('admin/grupo', [
            'titulo' => 'Grupo ' . $grupo['nombre'], 'grupo' => $grupo, 'miembros' => $miembros,
            'disponibles' => $disponibles, 'profesores' => Usuario::porRol('profesor'),
        ]);
    }

    public function actualizarGrupo(int $id): void
    {
        Grupo::buscar($id) ?? abortar(404);
        [$nombre, $periodo, $profesorId, $errores] = self::validarGrupo($id);
        if ($errores !== []) {
            self::volver($errores, '/admin/grupos/' . $id);
        }
        Grupo::actualizar($id, $nombre, $periodo, $profesorId, ($_POST['activo'] ?? '') === '1');
        Sesion::flash('success', 'Grupo actualizado.');
        redirigir('/admin/grupos/' . $id);
    }

    public function agregarEstudiantes(int $id): void
    {
        Grupo::buscar($id) ?? abortar(404);
        $validos = array_column(Usuario::porRol('estudiante'), 'id');
        $agregados = 0;
        foreach ((array) ($_POST['estudiantes'] ?? []) as $uid) {
            if (ctype_digit((string) $uid) && in_array((int) $uid, array_map('intval', $validos), true)) {
                Grupo::agregarEstudiante($id, (int) $uid);
                $agregados++;
            }
        }
        Sesion::flash($agregados > 0 ? 'success' : 'warning', $agregados > 0 ? "Se agregaron $agregados estudiante(s) al grupo." : 'Selecciona al menos un estudiante.');
        redirigir('/admin/grupos/' . $id);
    }

    public function quitarEstudiante(int $id, int $uid): void
    {
        Grupo::buscar($id) ?? abortar(404);
        Grupo::quitarEstudiante($id, $uid);
        Sesion::flash('success', 'Estudiante retirado del grupo. Sus resultados se conservan.');
        redirigir('/admin/grupos/' . $id);
    }

    private static function validarUsuario(?int $id): array
    {
        $nombre = entrada_texto($_POST, 'nombre', 120);
        $correo = mb_strtolower(entrada_texto($_POST, 'correo', 190));
        $rolId = entrada_entero($_POST, 'rol_id');
        $errores = [];
        if (mb_strlen($nombre) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Escribe un correo electrónico válido.';
        } elseif (Usuario::existeCorreo($correo, $id)) {
            $errores[] = 'Ya existe otro usuario con ese correo.';
        }
        if ($rolId === null || !in_array($rolId, array_map('intval', array_column(Usuario::roles(), 'id')), true)) {
            $errores[] = 'Selecciona un rol válido.';
        }
        return [['nombre' => $nombre, 'correo' => $correo, 'rol_id' => (int) $rolId], $errores];
    }

    private static function validarGrupo(int $id): array
    {
        $nombre = entrada_texto($_POST, 'nombre', 120);
        $periodo = entrada_texto($_POST, 'periodo', 20);
        $profesorId = entrada_entero($_POST, 'profesor_id');
        $errores = [];
        if ($nombre === '') {
            $errores[] = 'Escribe el nombre del grupo.';
        }
        if (!preg_match('/^\d{4}-[12]$/', $periodo)) {
            $errores[] = 'El periodo debe tener el formato AAAA-1 o AAAA-2, por ejemplo 2026-2.';
        }
        if ($profesorId !== null && !in_array($profesorId, array_map('intval', array_column(Usuario::porRol('profesor'), 'id')), true)) {
            $errores[] = 'El profesor seleccionado no es válido.';
        }
        if ($errores === [] && Grupo::existe($nombre, $periodo, $id)) {
            $errores[] = 'Ya existe un grupo con ese nombre en ese periodo.';
        }
        return [$nombre, $periodo, $profesorId, $errores];
    }

    private static function volver(array $errores, string $ruta): never
    {
        foreach ($errores as $error) {
            Sesion::flash('danger', $error);
        }
        Sesion::guardarFormulario($_POST);
        redirigir($ruta);
    }

    private static function fecha(?string $valor): ?string
    {
        if ($valor === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }
        [$a, $m, $d] = array_map('intval', explode('-', $valor));
        return checkdate($m, $d, $a) ? $valor : null;
    }
}
