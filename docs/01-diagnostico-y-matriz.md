# Diagnóstico inicial, matriz de cumplimiento y plan de corrección

Fecha de corte: 15 de noviembre de 2026.
Fuente de verdad: documento de grado `TG_APA_Interactivo_avance_17nov.docx`.
Código auditado: rama `main` de `Layoverv/apa-interactivo-silver`, commit `8a0410b` (7-oct-2026), etiquetado como `version-supabase`. La copia local del equipo (`Desktop\SILVER`) es idéntica en el código.

## 1. Lo que exige el documento

| Aspecto | Documento |
|---|---|
| Problema | Brecha formativa en APA 7: 100 % de 259 encuestados no sabe escribir una cita textual corta; 89,2 % quiere una herramienta interactiva. |
| Objetivo general | Prototipo web con contenido teórico, práctica y evaluación con retroalimentación inmediata; tasa de éxito ≥ 70 % y SUS ≥ 68 con al menos 10 estudiantes. |
| Objetivos específicos | OE1 requerimientos desde el diagnóstico; OE2 tres módulos con Scrum; OE3 validación SUS y tasa de éxito. |
| Roles (Tabla 3) | Estudiante, Profesor, Generador de contenidos, Administrador. |
| Requerimientos funcionales | 17 historias (Tabla 4) con criterios de aceptación (Tabla 13) y prioridad MoSCoW (Tabla 14). HU-13 (CSV) es *Won't have*. |
| Requerimientos no funcionales | Responsivo (Bootstrap), seguridad OWASP (Tabla 21), Ley 1581 de 2012, accesibilidad (correcto/incorrecto con ícono y texto), botones táctiles. |
| Arquitectura | Cliente-servidor en tres capas: HTML5/CSS3/JS/Bootstrap; PHP con MVC simplificado (controladores, servicios, modelos con PDO); MySQL/MariaDB InnoDB utf8mb4; XAMPP. Práctica por JSON asíncrono; demás pantallas HTML del servidor. |
| Carpetas (Tabla 18) | `/public`, `/app/controllers`, `/app/services`, `/app/models`, `/app/views`, `/config`, `/database`, `/docs`. |
| Base de datos (Tabla 19) | 14 tablas: roles, usuarios, grupos, usuario_grupo, temas, ejemplos, tipos_error, ejercicios, opciones, evaluaciones, evaluacion_ejercicio, intentos, respuestas, recursos. |
| Contenido | Cinco temas en este orden: formato general, citas textuales cortas, citas textuales largas, paráfrasis, lista de referencias. Banco ≥ 30 ejercicios, ≥ 6 por tema, por tipo de error y nivel. |
| Pruebas (Tabla 11) | Funcional por lista de chequeo, Thinking Aloud, SUS, tasa de éxito. |
| Alcance | Solo UNIAJC y APA 7; web adaptable; sin app nativa, sin integración institucional. |

## 2. Diagnóstico del software inicial

**Arquitectura contradictoria con el documento.** El código usa HTML/CSS/JS sin Bootstrap y Supabase (Auth + PostgreSQL con RLS y funciones RPC). No existe PHP, MySQL, MVC ni la estructura de la Tabla 18. Las tablas tampoco siguen la Tabla 19: no existen `evaluaciones`, `evaluacion_ejercicio`, `tipos_error`, `roles` ni `ejemplos`.

**Errores que impiden instalar o usar el sistema (verificados en PostgreSQL 16):**

1. `schema.sql` falla en dos funciones (`admin_overview_stats` declarada `plpgsql` con cuerpo SQL; columna `position` sin comillas en `get_question_answer_key`). En consecuencia falla el `GRANT` final y ninguna función queda autorizada.
2. `seed.sql` falla por variables `module_id`/`question_id` ambiguas con columnas: los 30 ejercicios no se cargan.
3. Las 30 respuestas correctas del banco son la opción A.
4. Un usuario desactivado conserva acceso: la desactivación solo se valida en el navegador; con su sesión puede seguir iniciando evaluaciones (verificado).
5. La explicación de cada ejercicio (que revela la respuesta) puede leerse antes de responder.
6. Todos los ejercicios tienen dificultad 1 y tipo de error «general».
7. Un estudiante recién registrado no ve ningún tema hasta que el administrador lo matricula en un curso con módulos asignados.
8. Archivos ajenos al proyecto: `pregunta1-js/`, `pregunta3-react/` (tareas de otra materia), `scratch/analyze.py` (rutas locales de un integrante) y `database/api_grants.sql` vacío.

**Aspectos bien resueltos que se conservan como criterio:** calificación en el servidor, clave de respuestas oculta al estudiante, roles verificados en el servidor y escape de HTML en todo el render.

## 3. Matriz de cumplimiento (estado inicial)

Estados: **C** cumplido · **P** parcialmente cumplido · **N** no implementado · **NV** no verificable · **X** contradictorio con el documento.

| ID | Requisito (criterios de la Tabla 13) | Estado inicial | Evidencia |
|---|---|---|---|
| Arq. | PHP + MySQL/MariaDB + Apache/XAMPP, MVC, Bootstrap | **X** | Supabase/PostgreSQL, sin PHP ni Bootstrap |
| Tabla 18 | Estructura de carpetas | **X** | Archivos en la raíz |
| Tabla 19 | 14 tablas del diccionario | **X** | 12 tablas con otro modelo; faltan 5 |
| Tabla 21 | Controles de seguridad PHP (PDO, hash, bloqueo, CSRF, sesión) | **X/P** | Equivalentes parciales en Supabase; sin bloqueo propio ni cierre por inactividad |
| HU-01 | Registro con nombre, correo, contraseña y confirmación; política; correo único; rol estudiante | **P** | Falta confirmación de contraseña y enlace a la política |
| HU-02 | Login, mensaje genérico, cierre de sesión, páginas protegidas, bloqueo tras 5 fallos, inactividad 30 min | **P** | Sin bloqueo de 5 intentos ni cierre por inactividad; mensajes de Supabase en inglés |
| HU-03 | Temas en el orden del documento; explicación; ir al siguiente o practicar | **P** | Orden y temas distintos (citas cortas y largas unidas; tema extra «fuentes digitales»); sin «siguiente tema» |
| HU-04 | ≥ 1 ejemplo correcto e incorrecto; el incorrecto señala error y corrección; ícono y texto | **P** | Un par por tema; sin ícono; explicación genérica igual para todos |
| HU-05 | Ejercicios por tema; filtro por nivel; uno a la vez; aciertos al final; ≥ 6 por tema | **P** | Sin filtro de nivel; todos nivel 1; sin resumen de aciertos |
| HU-06 | Retroalimentación sin recargar; explicación; respuesta correcta si falla; se registra | **P** | No muestra la opción correcta; explicación visible antes de responder |
| HU-07 | 10 preguntas al azar de todos los temas; sin retroalimentación previa; no enviar incompleta; guardar | **P** | Usa las 6 preguntas fijas de un tema, siempre en el mismo orden |
| HU-08 | Puntaje y tasa; respuesta dada, correcta y explicación; aciertos por tema; temas a reforzar | **P** | No muestra la respuesta correcta ni aciertos por tema |
| HU-09 | Intentos con fecha y tasa; gráfica de evolución; avance por tema | **P** | Sin gráfica ni avance por tema |
| HU-10 | Biblioteca UNIAJC, Google Académico, Zotero y Mendeley; descripción; pestaña nueva; en BD | **P** | Falta Mendeley; el enlace de biblioteca apunta a la página principal |
| HU-11 | Profesor ve solo sus grupos; por estudiante último puntaje, mejor tasa y n.º de intentos; detalle | **P** | Lista plana de intentos; sin agrupación ni detalle |
| HU-12 | % de error por tema y tipo de error por grupo, ordenado de mayor a menor | **P** | Sin filtro por grupo; ordena por cantidad; tipos de error sin clasificar |
| HU-13 | Exportar CSV (*Won't have*) | **X** | Se implementó una exportación a Excel que el documento deja fuera de esta versión |
| HU-14 | Crear, editar y desactivar usuarios; roles; grupos con profesor y estudiantes | **P** | No crea usuarios; la desactivación no se aplica en el servidor |
| HU-15 | Crear, editar, ordenar y ocultar temas; formato básico y ejemplos; cambios inmediatos | **P** | Sin ordenamiento; un único par de ejemplos |
| HU-16 | Tema, tipo de error, nivel, enunciado, 2–4 opciones, una correcta, explicación; desactivar sin borrar; total activo por tema | **P** | Exactamente 3 opciones; borra en lugar de desactivar; sin totales |
| HU-17 | Usuarios, evaluaciones, tasa de éxito promedio, meta 70 %, filtro por grupo y fechas | **P** | Calcula % de intentos ≥ 70 en lugar del promedio; sin filtros |
| Banco | ≥ 30 ejercicios, ≥ 6 por tema, por tipo de error y nivel | **P** | 30 ejercicios, pero sin niveles ni tipos de error, y no cargaban |
| Responsivo | Adaptable a celular y computador | **C** | CSS con media queries (sin Bootstrap) |
| Ley 1581 | Autorización y fecha de aceptación | **P** | Se registra la aceptación; no hay página de política |
| Validación | SUS ≥ 68 y tasa ≥ 70 % con ≥ 10 estudiantes | **NV** | Depende de las rondas con usuarios (Tablas 23 y 24 sin diligenciar) |

## 4. Decisión de arquitectura

| Alternativa | Ventajas | Consecuencias |
|---|---|---|
| **A. Migrar a PHP + MySQL/MariaDB** (elegida) | El código coincide con el documento aprobado (marco teórico, Tablas 10, 18, 19, 21, riesgo R4, diagramas). Funciona sin internet en XAMPP durante la sustentación. Sin dependencia de un servicio que pausa los proyectos gratuitos tras una semana sin uso. | Hay que reescribir la capa de servidor y la base de datos. Se conserva el recorrido pedagógico, el contenido y las funciones existentes. |
| B. Mantener Supabase | Menos código nuevo. | Habría que reescribir el marco teórico, las tablas 10, 18, 19 y 21 y los diagramas a pocas semanas de la sustentación, con riesgo de incoherencia ante el jurado. |

Decisión tomada por el equipo el 15-nov-2026: **alternativa A**. Queda pendiente informarla al asesor, Diego Darío López Mera, en la próxima asesoría. La versión anterior se conserva en la etiqueta git `version-supabase`.

## 5. Plan de corrección

| Prioridad | Trabajo | Depende de | Riesgo | Archivos |
|---|---|---|---|---|
| 1 | Limpieza: retirar tareas ajenas y archivos de Supabase del árbol (quedan en `version-supabase`) | — | Bajo | raíz, `pregunta*`, `scratch`, `database/*` |
| 2 | Base de datos MySQL según la Tabla 19 + datos iniciales | 1 | Medio | `database/schema.sql`, `database/seed.sql` |
| 3 | Núcleo PHP: enrutador, PDO, sesión segura, CSRF, vistas, autenticación (HU-01, HU-02) | 2 | Alto | `public/`, `app/core`, `app/controllers/AuthController.php` |
| 4 | Módulo de contenido (HU-03, HU-04, HU-10) | 3 | Bajo | `ContenidoController`, `views/estudiante` |
| 5 | Práctica con JSON (HU-05, HU-06) | 3, 4 | Medio | `PracticaController`, `public/assets/js/practica.js` |
| 6 | Evaluación aleatoria, resultado y progreso (HU-07, HU-08, HU-09) | 5 | Alto | `EvaluacionController`, `services/Calificador.php` |
| 7 | Profesor (HU-11, HU-12) y administración (HU-14, HU-17) | 6 | Medio | `ProfesorController`, `AdminController` |
| 8 | Generador de contenidos (HU-15, HU-16) | 4 | Medio | `GestionController` |
| 9 | Banco de 36 ejercicios clasificado; contenido de los cinco temas | 2 | Medio (R3: revisión del asesor) | `database/seed.sql` |
| 10 | Pruebas automatizadas, pruebas en navegador y documentación | 3–9 | Bajo | `tests/`, `docs/` |
