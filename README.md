# APA Interactivo

Prototipo web responsivo para aprender APA 7, dirigido a estudiantes de la UNIAJC. La aplicación usa Supabase Auth y PostgreSQL con políticas RLS.

El anteproyecto enumera React y Firebase. Esta versión conserva la aplicación web y el proyecto Supabase que ya estaban configurados, y adapta sus funciones al alcance académico descrito.

## Alcance implementado

- Registro de estudiantes con consentimiento de tratamiento de datos; los roles de personal se asignan desde administración.
- Módulos de contenido APA con ejemplos correctos e incorrectos, práctica guiada con retroalimentación inmediata y evaluaciones con puntaje calculado en la base de datos.
- Progreso e historial de evaluaciones del estudiante, además de recursos académicos recomendados.
- Banco inicial de ejercicios, cargado desde `database/seed.sql`.
- Panel docente para consultar errores frecuentes y exportar un archivo Excel (.xlsx) con filtros, encabezados y formato de puntaje.
- Gestión de módulos y ejercicios para el gestor de contenidos.
- Administración de roles, estados de usuarios, cursos, asignaciones y tasa general de éxito con meta de 70 %.

## Configurar y ejecutar

Sigue [`database/README.md`](database/README.md). Copia `supabase-config.example.js` como `supabase-config.js`, completa la URL y clave publicable de tu proyecto Supabase, y luego ejecuta desde esta carpeta `python -m http.server 8000`. Abre `http://localhost:8000`. La aplicación requiere un proyecto Supabase y conexión a internet para cargar su SDK.

La app es web responsiva y puede usarse desde un navegador móvil; no es una aplicación nativa de iOS o Android. Nunca agregues una clave `service_role` al código del navegador.
