<?php
// Copia este archivo como config/config.php y ajusta los valores.
// config.php está excluido del repositorio (.gitignore) porque contiene credenciales.

return [
    // 'desarrollo' muestra los errores detallados; 'produccion' los oculta (Tabla 21, A05).
    'entorno' => 'desarrollo',

    // Conexión a MySQL/MariaDB. En XAMPP el usuario por defecto es root sin contraseña;
    // para las pruebas con usuarios se recomienda crear un usuario propio con contraseña.
    'db' => [
        'host'     => '127.0.0.1',
        'puerto'   => 3306,
        'nombre'   => 'apa_interactivo',
        'usuario'  => 'root',
        'clave'    => '',
    ],

    // Minutos de inactividad antes de cerrar la sesión (HU-02).
    'sesion_minutos' => 30,

    // Bloqueo temporal tras intentos fallidos de inicio de sesión (HU-02).
    'login_max_intentos'   => 5,
    'login_bloqueo_minutos' => 15,

    // Versión vigente de la política de tratamiento de datos (Ley 1581 de 2012).
    'politica_version' => '2026-11-15',

    // Correo de contacto del equipo para consultas sobre datos personales.
    'contacto_datos' => 'apainteractivo@ejemplo.edu.co',
];
