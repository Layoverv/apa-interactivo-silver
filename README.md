# APA Interactivo

Aplicación web para aprender las normas APA 7.ª edición, dirigida a estudiantes de la UNIAJC (trabajo de grado, Tecnología en Sistemas de Información). Tiene tres módulos: contenido teórico con ejemplos, práctica guiada con retroalimentación inmediata y evaluación de 10 preguntas al azar.

Arquitectura cliente-servidor en tres capas, como la describe el documento de grado: HTML5, CSS3, JavaScript y Bootstrap 5 en el navegador; PHP 8 con MVC simplificado en Apache; MySQL/MariaDB como base de datos. La versión anterior (Supabase) queda en la etiqueta `version-supabase`.

## Instalar en XAMPP

Requisitos: XAMPP con PHP 8.1 o superior y MariaDB/MySQL (Apache con `mod_rewrite`, que viene activo por defecto).

1. Copia la carpeta del proyecto en `C:\xampp\htdocs\apa-interactivo`.
2. En el panel de XAMPP inicia **Apache** y **MySQL**.
3. Copia `config\config.example.php` como `config\config.php` y revisa la conexión. Con XAMPP recién instalado sirve tal cual (usuario `root` sin contraseña).
4. Abre una consola en la carpeta del proyecto y ejecuta:
   ```
   C:\xampp\php\php.exe database\instalar.php
   ```
   Crea la base `apa_interactivo`, carga los 5 temas, los 45 ejercicios y los recursos, y te pide los datos del administrador inicial.
5. Abre `http://localhost/apa-interactivo/` e inicia sesión con el administrador.

**Instalación manual (sin consola):** en phpMyAdmin crea la base `apa_interactivo` con cotejamiento `utf8mb4_unicode_ci`, importa `database/schema.sql` y luego `database/seed.sql`. Después crea el administrador con `php database\crear_admin.php`. Ese mismo comando sirve para recuperar el acceso si se olvida la contraseña.

## Primeros pasos

- Los estudiantes se registran solos desde **Crear cuenta** y quedan con rol estudiante.
- Desde **Usuarios**, el administrador crea profesores y generadores de contenido con una contraseña temporal. Al ingresar, ellos aceptan la política de datos y la cambian.
- En **Grupos**, el administrador crea los grupos, les asigna un profesor y agrega estudiantes.
- Antes de las pruebas con usuarios, actualiza `contacto_datos` en `config/config.php` con el correo real del equipo; aparece en la política de datos.

## Pruebas automatizadas

```
C:\xampp\php\php.exe tests\ejecutar.php
```

Crean una base de datos aparte (`apa_pruebas`), instalan todo desde cero, levantan el servidor de PHP y recorren la aplicación con 58 pruebas ligadas a los criterios de aceptación de las historias de usuario. Al terminar borran la base de prueba y dejan el informe en `tests/salida/resultados.md`.

## Estructura

| Carpeta | Contenido |
|---|---|
| `public/` | Punto de entrada (`index.php`), CSS, JavaScript y Bootstrap. Es lo único accesible desde el navegador. |
| `app/core/` | Piezas base: enrutador, conexión PDO, sesión segura, token CSRF, autenticación y vistas. |
| `app/controllers/` | Un controlador por módulo: autenticación, contenido, práctica, evaluación, progreso, profesor, administración y gestión. |
| `app/services/` | Reglas del negocio: calificación y tasa de éxito, permisos por rol y gráficas. |
| `app/models/` | Acceso a datos, siempre con consultas preparadas. |
| `app/views/` | Plantillas de cada pantalla, organizadas por rol. |
| `app/rutas.php` | Mapa de rutas y roles autorizados en cada una. |
| `config/` | Configuración local (`config.php` no se sube al repositorio). |
| `database/` | `schema.sql` (14 tablas), `seed.sql` (contenido inicial), instalador y creación de administrador. |
| `tests/` | Pruebas funcionales automatizadas. |
| `docs/` | Diagnóstico inicial y matriz de cumplimiento. |

## Seguridad

Contraseñas con `password_hash`, consultas preparadas con PDO, escape de toda salida, token CSRF en cada envío, roles verificados en el servidor en cada ruta, bloqueo de 15 minutos tras 5 intentos fallidos, sesión nueva al ingresar y cierre tras 30 minutos de inactividad. Además: cabeceras CSP, cookies HttpOnly y SameSite, calificación solo en el servidor y respuestas correctas ocultas hasta que el estudiante responde.
