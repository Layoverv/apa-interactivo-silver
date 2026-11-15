<?php
declare(strict_types=1);

namespace App\Services;

// Control de permisos por rol: qué opciones del menú ve cada rol. La autorización real
// se aplica en el servidor en cada ruta (app/rutas.php); ocultar un enlace no protege nada.
final class Permisos
{
    public const ESTUDIANTE = 'estudiante';
    public const PROFESOR = 'profesor';
    public const GENERADOR = 'generador';
    public const ADMINISTRADOR = 'administrador';

    /** Enlaces del menú principal según el rol. */
    public static function menu(?string $rol): array
    {
        return match ($rol) {
            self::ESTUDIANTE => [
                ['/', 'Mi panel'], ['/temas', 'Contenido APA'], ['/practica', 'Práctica'],
                ['/evaluacion', 'Evaluación'], ['/progreso', 'Mi progreso'], ['/recursos', 'Recursos'],
            ],
            self::PROFESOR => [
                ['/profesor', 'Mis grupos'], ['/temas', 'Contenido APA'], ['/recursos', 'Recursos'],
            ],
            self::GENERADOR => [
                ['/gestion', 'Temas'], ['/gestion/ejercicios', 'Ejercicios'], ['/gestion/recursos', 'Recursos'], ['/temas', 'Vista del estudiante'],
            ],
            self::ADMINISTRADOR => [
                ['/admin', 'Estadísticas'], ['/admin/usuarios', 'Usuarios'], ['/admin/grupos', 'Grupos'],
                ['/profesor', 'Resultados'], ['/gestion', 'Contenidos'],
            ],
            default => [],
        };
    }

    /** Pantalla a la que llega cada rol después de iniciar sesión. */
    public static function inicio(string $rol): string
    {
        return match ($rol) {
            self::PROFESOR => '/profesor',
            self::GENERADOR => '/gestion',
            self::ADMINISTRADOR => '/admin',
            default => '/',
        };
    }
}
