# APA Interactivo

Aplicación web para aprender las normas APA 7.ª edición, dirigida a los estudiantes de la Institución Universitaria Antonio José Camacho (UNIAJC). Es el trabajo de grado de Tecnología en Sistemas de Información de Deyby Alejandro Rodríguez Castillo, Jose Luis Oviedo Camacho y Juan David Paz Trochez, con la asesoría de Diego Darío López Mera.

---

## 1. Qué queremos lograr

### El problema

Se aplicó una encuesta diagnóstica a 259 estudiantes de la UNIAJC:

| Indicador | Resultado |
|---|---|
| Nunca recibió formación en APA | 55,2 % |
| Domina las normas APA | 0 % |
| No sabe cómo se escribe una cita textual corta | 100 % |
| No sabe qué es un gestor bibliográfico | 44,8 % |
| Quiere aprender con una herramienta interactiva | 89,2 % |

### El objetivo

Entregar un **prototipo funcional** de aplicación web con tres módulos: contenido teórico, práctica con retroalimentación inmediata y evaluación. El prototipo se valida con **al menos 10 estudiantes** de la UNIAJC y debe cumplir dos metas:

| Meta | Cómo se mide |
|---|---|
| **Tasa de éxito ≥ 70 %** en tareas de citación y referenciación | Porcentaje de respuestas correctas en el módulo de evaluación. La aplicación la calcula y la muestra en Estadísticas. |
| **Usabilidad SUS ≥ 68** | Cuestionario SUS de 10 ítems aplicado al final de las sesiones con usuarios. Se hace fuera de la aplicación. |

### El alcance

- **Incluye:** solo estudiantes de la UNIAJC y solo APA 7.ª edición, en una aplicación web adaptable a computador y celular.
- **No incluye:** aplicación móvil nativa, integración con los sistemas institucionales, otros estilos de citación ni mantenimiento después del semestre 2026-2.

---

## 2. Lo que ya tenemos

### Arquitectura

El software sigue la arquitectura del documento de grado: cliente-servidor en tres capas.

| Capa | Tecnología | Responsabilidad |
|---|---|---|
| Presentación | HTML5, CSS3, JavaScript, Bootstrap 5 | Pantallas adaptables a celular y computador. La práctica envía cada respuesta y recibe la retroalimentación en JSON, sin recargar la página. |
| Lógica | PHP 8 en Apache, MVC simplificado | Autenticación, roles, calificación, tasa de éxito y reportes. |
| Datos | MySQL / MariaDB (XAMPP) | Las 14 tablas del diccionario de datos del documento (Tabla 19). |

No depende de ningún servicio externo: funciona sin internet en XAMPP. Bootstrap está incluido en el proyecto, no se carga desde internet.

La primera versión del prototipo usaba Supabase. Se reemplazó para que el código coincida con el documento y queda guardada en la etiqueta git `version-supabase`.

### Usuarios y roles

| Rol | Qué puede hacer |
|---|---|
| **Estudiante** | Se registra solo. Estudia los temas, practica, presenta la evaluación y consulta su progreso. |
| **Profesor** | Ve los resultados y los errores frecuentes de sus grupos, y descarga los resultados en CSV. |
| **Generador de contenidos** | Administra temas, ejemplos, ejercicios y recursos. |
| **Administrador** | Gestiona usuarios, roles y grupos. Consulta las estadísticas generales. |

### Funcionalidades (historias de usuario)

| HU | Funcionalidad | Estado |
|---|---|---|
| HU-01 | Registro con confirmación de contraseña y aceptación de la política de datos (Ley 1581 de 2012). La cuenta nueva queda como estudiante. | ✓ Implementada |
| HU-02 | Inicio y cierre de sesión. Bloqueo de 15 minutos tras 5 intentos fallidos. Cierre automático tras 30 minutos sin actividad. | ✓ Implementada |
| HU-03 | Cinco temas en orden: formato general, citas textuales cortas, citas textuales largas, paráfrasis y lista de referencias. | ✓ Implementada |
| HU-04 | Ejemplos correctos e incorrectos en cada tema. El incorrecto explica el error y su corrección, con ícono y texto. | ✓ Implementada |
| HU-05 | Práctica por tema y nivel (básico, intermedio, avanzado), un ejercicio a la vez, con el resumen de aciertos al final. | ✓ Implementada |
| HU-06 | Retroalimentación inmediata: si la respuesta es correcta, la explicación y, si falló, la opción correcta. | ✓ Implementada |
| HU-07 | Evaluación de 10 preguntas al azar de todos los temas. Sin retroalimentación hasta enviar y sin poder enviarla incompleta. | ✓ Implementada |
| HU-08 | Resultado con puntaje, tasa de éxito, revisión de cada pregunta, aciertos por tema y temas para reforzar. | ✓ Implementada |
| HU-09 | Progreso con historial de intentos, gráfica de evolución y avance por tema. | ✓ Implementada |
| HU-10 | Recursos: bases de datos de la Biblioteca UNIAJC, Google Académico, Zotero y Mendeley. | ✓ Implementada |
| HU-11 | El profesor ve solo sus grupos: último puntaje, mejor tasa y número de intentos por estudiante, con detalle de cada uno. | ✓ Implementada |
| HU-12 | Porcentaje de error por tema y por tipo de error en cada grupo, de mayor a menor, con gráfica. | ✓ Implementada |
| HU-13 | Exportación de resultados en CSV. | ✓ Implementada (el documento la clasifica como *Won't have*; ver pendientes) |
| HU-14 | Crear, editar y desactivar usuarios; asignar roles; crear grupos con profesor y estudiantes. | ✓ Implementada |
| HU-15 | Crear, editar, ordenar, ocultar y eliminar temas, con formato básico y ejemplos. | ✓ Implementada |
| HU-16 | Banco de ejercicios con tema, tipo de error, nivel, entre 2 y 4 opciones, respuesta correcta y explicación. Se desactivan sin borrarse. | ✓ Implementada |
| HU-17 | Estadísticas: usuarios, evaluaciones y tasa de éxito promedio frente a la meta del 70 %, con filtros por grupo y fechas. | ✓ Implementada |

### Contenido cargado

- **5 temas**, cada uno con su explicación y sus ejemplos correctos e incorrectos.
- **45 ejercicios**: Formato general 8, Citas textuales cortas 8, Citas textuales largas 8, Paráfrasis 8 y Lista de referencias 13. Superan la meta de 30, con al menos 6 por tema.
- **Tres tipos de ejercicio:** selección múltiple, verdadero o falso y completar.
- **8 tipos de error**, tomados de las dificultades del diagnóstico.
- **La respuesta correcta varía de posición**; no siempre es la A.
- **1 evaluación final** de 10 preguntas y **4 recursos** recomendados.

### Seguridad

| Riesgo | Cómo se controla |
|---|---|
| Inyección SQL | PDO con consultas preparadas en todos los modelos. |
| XSS | Escape de todo dato que se muestra en pantalla. Política de seguridad de contenido (CSP) sin scripts en línea. |
| Contraseñas | `password_hash` y `password_verify`; nunca se guardan en texto plano. |
| Fuerza bruta | Bloqueo temporal tras 5 intentos y mensajes de error genéricos. |
| Acceso indebido | El rol se verifica en el servidor en cada ruta. Un usuario desactivado pierde la sesión de inmediato. |
| Robo de sesión | Identificador nuevo al ingresar, cookies HttpOnly y SameSite, y cierre por inactividad. |
| Falsificación de peticiones (CSRF) | Token en cada formulario y en cada petición de la práctica. |
| Trampa en evaluaciones | La calificación ocurre solo en el servidor. Las respuestas correctas no llegan al navegador antes de responder, y un intento enviado no se puede modificar. |
| Configuración expuesta | Las credenciales están en `config/config.php`, que no se sube al repositorio. Las carpetas internas no se pueden abrir desde el navegador. |

### Pruebas realizadas

- **58 pruebas automatizadas**, todas superadas, en `tests/ejecutar.php`. Cada una verifica un criterio de aceptación de las historias de usuario. Instalan una base de datos de prueba desde cero, recorren la aplicación como lo haría un navegador y la borran al terminar.
- **Recorrido en Chrome** (registro, práctica, evaluación, progreso y paneles de administración) en pantallas de computador (1280 px), tableta (768 px) y celular (360 px): sin errores en la consola y sin desplazamiento horizontal.
- **Prueba en Apache** con el proyecto en una subcarpeta, igual que en XAMPP. Las carpetas `app`, `config`, `database`, `tests` y `.git` responden con 403, es decir, no se pueden abrir desde el navegador.

Las pruebas se ejecutaron con PHP 8.3 y MariaDB 10.11. Falta correrlas en el XAMPP de cada integrante (ver pendientes).

---

## 3. Lo que falta para lograr el objetivo

| Pendiente | Responsable | Por qué |
|---|---|---|
| Informar al asesor el cambio de Supabase a PHP + MySQL | Equipo | El documento ya describe PHP + MySQL; el código ahora coincide con él. |
| Subir la rama `migracion-php` a GitHub y unirla a `main` | Jose Luis, o el integrante que agregue como colaborador | El repositorio está en la cuenta `Layoverv`. |
| Instalar y correr las pruebas en el XAMPP de cada integrante | Cada integrante | Confirmar que todo funciona en Windows con la versión de XAMPP que tiene cada uno. |
| Revisar el contenido APA de los temas y ejercicios | Asesor | Riesgo R3: errores en el contenido normativo. |
| Cambiar `contacto_datos` en `config/config.php` por el correo real del equipo | Equipo | Aparece en la política de tratamiento de datos. |
| Decidir si se reporta la exportación CSV (HU-13) | Equipo y asesor | El documento la dejó fuera de esta versión, pero el prototipo anterior ya exportaba resultados y se conservó. |
| Aclarar el tipo de ejercicio «completar» | Equipo y asesor | Se resuelve eligiendo la opción que completa la frase, no escribiendo texto libre. |
| Rondas de pruebas con usuarios (Thinking Aloud) | Equipo | La ronda final debe reunir al menos 10 estudiantes y algunos profesores. |
| Medir la tasa de éxito (meta ≥ 70 %) | Equipo | Se lee en el panel de Estadísticas, filtrando por el grupo de validación y las fechas de la prueba. |
| Aplicar y calcular la escala SUS (meta ≥ 68) | Equipo | Se aplica en papel o en un formulario, fuera de la aplicación. |

---

## 4. Cómo instalarlo en XAMPP

Requisitos: XAMPP con PHP 8.1 o superior y MariaDB o MySQL. Apache debe tener activo `mod_rewrite`, que viene activo por defecto.

1. Copia la carpeta del proyecto en `C:\xampp\htdocs\apa-interactivo`.
2. En el panel de XAMPP, inicia **Apache** y **MySQL**.
3. Copia `config\config.example.php` como `config\config.php`. Con un XAMPP recién instalado sirve tal cual (usuario `root`, sin contraseña).
4. Abre una consola en la carpeta del proyecto y ejecuta:
   ```
   C:\xampp\php\php.exe database\instalar.php
   ```
   Este comando crea la base `apa_interactivo`, carga el contenido inicial y te pide los datos del administrador.
5. Abre `http://localhost/apa-interactivo/` e inicia sesión con el administrador.

**Si prefieres no usar la consola:** en phpMyAdmin crea la base `apa_interactivo` con cotejamiento `utf8mb4_unicode_ci`. Importa primero `database/schema.sql` y después `database/seed.sql`. Luego crea el administrador con `C:\xampp\php\php.exe database\crear_admin.php`. Ese mismo comando sirve para recuperar el acceso si se olvida la contraseña.

### Primeros pasos después de instalar

1. Los estudiantes crean su cuenta en **Crear cuenta**.
2. En **Usuarios**, el administrador crea a los profesores y al generador de contenidos con una contraseña temporal. Al entrar por primera vez, ellos aceptan la política de datos y cambian la contraseña.
3. En **Grupos**, el administrador crea cada grupo, le asigna su profesor y agrega a los estudiantes.

### Ejecutar las pruebas

```
C:\xampp\php\php.exe tests\ejecutar.php
```

El resultado aparece en la consola y en `tests/salida/resultados.md`. El usuario de la base de datos configurado debe poder crear bases de datos, porque las pruebas crean y borran `apa_pruebas`.

---

## 5. Estructura del proyecto

```
apa-interactivo/
├── public/              Lo único accesible desde el navegador
│   ├── index.php        Punto de entrada: toda petición pasa por aquí
│   └── assets/          CSS, JavaScript (práctica y evaluación), Bootstrap e ícono
├── app/
│   ├── core/            Enrutador, conexión PDO, sesión, CSRF, autenticación y vistas
│   ├── controllers/     Un controlador por módulo
│   ├── services/        Calificación y tasa de éxito, permisos por rol y gráficas
│   ├── models/          Acceso a la base de datos
│   ├── views/           Pantallas organizadas por rol
│   ├── rutas.php        Rutas y roles autorizados en cada una
│   └── helpers.php      Funciones de apoyo (escape, URLs, formato)
├── config/              config.example.php (config.php no se sube al repositorio)
├── database/            schema.sql, seed.sql, instalador y creación de administrador
└── tests/               Pruebas funcionales automatizadas
```

### Base de datos

| Tabla | Contenido |
|---|---|
| `roles` | Los cuatro roles. |
| `usuarios` | Cuentas, rol, estado, aceptación de la política y control de intentos fallidos. |
| `grupos`, `usuario_grupo` | Grupos con su profesor y sus estudiantes. |
| `temas`, `ejemplos` | Contenido teórico y ejemplos correctos e incorrectos. |
| `tipos_error` | Clasificación de los errores frecuentes. |
| `ejercicios`, `opciones` | Banco de ejercicios. La base impide que un ejercicio tenga dos opciones correctas. |
| `evaluaciones`, `evaluacion_ejercicio` | Evaluación final y los ejercicios que pueden salir en ella. |
| `intentos`, `respuestas` | Cada práctica o evaluación presentada, con sus respuestas y su puntaje. |
| `recursos` | Enlaces de la sección Recursos. |
