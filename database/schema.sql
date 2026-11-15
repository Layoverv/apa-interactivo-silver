-- APA Interactivo — esquema de base de datos para MySQL 5.7+/MariaDB 10.4+ (XAMPP).
-- Corresponde al diccionario de datos de la Tabla 19 del documento de grado.
-- Motor InnoDB (claves foráneas y transacciones) y utf8mb4 (tildes y eñes).
-- Ejecutar sobre una base de datos vacía. Después se ejecuta seed.sql.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- Roles del sistema (Tabla 3). La columna clave es la que usa el código.
CREATE TABLE roles (
  id          TINYINT UNSIGNED NOT NULL,
  clave       VARCHAR(20)  NOT NULL,
  nombre      VARCHAR(40)  NOT NULL,
  descripcion VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personas registradas (HU-01, HU-02, HU-14). Se desactivan, no se eliminan.
CREATE TABLE usuarios (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol_id             TINYINT UNSIGNED NOT NULL,
  nombre             VARCHAR(120) NOT NULL,
  correo             VARCHAR(190) NOT NULL,
  hash_clave         VARCHAR(255) NOT NULL,
  activo             TINYINT(1) NOT NULL DEFAULT 1,
  acepto_politica_en DATETIME NULL,
  version_politica   VARCHAR(20) NULL,
  debe_cambiar_clave TINYINT(1) NOT NULL DEFAULT 0,
  intentos_fallidos  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta    DATETIME NULL,
  ultimo_acceso      DATETIME NULL,
  creado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_correo (correo),
  KEY ix_usuarios_rol (rol_id),
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grupos de clase asignados a un profesor (HU-11, HU-14).
CREATE TABLE grupos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  profesor_id INT UNSIGNED NULL,
  nombre      VARCHAR(120) NOT NULL,
  periodo     VARCHAR(20)  NOT NULL,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_grupos_nombre_periodo (nombre, periodo),
  KEY ix_grupos_profesor (profesor_id),
  CONSTRAINT fk_grupos_profesor FOREIGN KEY (profesor_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estudiantes que pertenecen a cada grupo.
CREATE TABLE usuario_grupo (
  usuario_id  INT UNSIGNED NOT NULL,
  grupo_id    INT UNSIGNED NOT NULL,
  agregado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, grupo_id),
  KEY ix_usuario_grupo_grupo (grupo_id),
  CONSTRAINT fk_ug_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_ug_grupo   FOREIGN KEY (grupo_id)   REFERENCES grupos (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temas del módulo de contenido, en su orden de presentación (HU-03, HU-15).
CREATE TABLE temas (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo         VARCHAR(150) NOT NULL,
  resumen        VARCHAR(255) NOT NULL DEFAULT '',
  contenido      TEXT NOT NULL,
  orden          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  publicado      TINYINT(1) NOT NULL DEFAULT 0,
  creado_por     INT UNSIGNED NULL,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_temas_orden (orden),
  CONSTRAINT fk_temas_creador FOREIGN KEY (creado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejemplos correctos e incorrectos de cada tema (HU-04).
CREATE TABLE ejemplos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tema_id     INT UNSIGNED NOT NULL,
  es_correcto TINYINT(1) NOT NULL,
  texto       TEXT NOT NULL,
  explicacion TEXT NOT NULL,
  orden       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY ix_ejemplos_tema (tema_id, orden),
  CONSTRAINT fk_ejemplos_tema FOREIGN KEY (tema_id) REFERENCES temas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clasificación de los errores frecuentes según el diagnóstico (HU-12, HU-16).
CREATE TABLE tipos_error (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_tipos_error_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Banco de ejercicios (HU-05, HU-16). Se desactivan, no se eliminan.
CREATE TABLE ejercicios (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tema_id        INT UNSIGNED NOT NULL,
  tipo_error_id  INT UNSIGNED NOT NULL,
  tipo           ENUM('seleccion_multiple','verdadero_falso','completar') NOT NULL DEFAULT 'seleccion_multiple',
  dificultad     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  enunciado      TEXT NOT NULL,
  explicacion    TEXT NOT NULL,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  creado_por     INT UNSIGNED NULL,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_ejercicios_tema (tema_id, activo, dificultad),
  KEY ix_ejercicios_tipo_error (tipo_error_id),
  CONSTRAINT ck_ejercicios_dificultad CHECK (dificultad BETWEEN 1 AND 3),
  CONSTRAINT fk_ejercicios_tema       FOREIGN KEY (tema_id)       REFERENCES temas (id),
  CONSTRAINT fk_ejercicios_tipo_error FOREIGN KEY (tipo_error_id) REFERENCES tipos_error (id),
  CONSTRAINT fk_ejercicios_creador    FOREIGN KEY (creado_por)    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opciones de respuesta. La columna generada impide más de una opción correcta por ejercicio.
CREATE TABLE opciones (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ejercicio_id      INT UNSIGNED NOT NULL,
  texto             VARCHAR(500) NOT NULL,
  es_correcta       TINYINT(1) NOT NULL DEFAULT 0,
  orden             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  marca_correcta    TINYINT(1) GENERATED ALWAYS AS (IF(es_correcta = 1, 1, NULL)) STORED,
  PRIMARY KEY (id),
  UNIQUE KEY uq_opciones_una_correcta (ejercicio_id, marca_correcta),
  KEY ix_opciones_ejercicio (ejercicio_id, orden),
  CONSTRAINT fk_opciones_ejercicio FOREIGN KEY (ejercicio_id) REFERENCES ejercicios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración de las evaluaciones (HU-07).
CREATE TABLE evaluaciones (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo        VARCHAR(150) NOT NULL,
  descripcion   VARCHAR(255) NOT NULL DEFAULT '',
  num_preguntas TINYINT UNSIGNED NOT NULL DEFAULT 10,
  activa        TINYINT(1) NOT NULL DEFAULT 1,
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT ck_evaluaciones_num CHECK (num_preguntas BETWEEN 1 AND 50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejercicios que pueden salir en cada evaluación.
CREATE TABLE evaluacion_ejercicio (
  evaluacion_id INT UNSIGNED NOT NULL,
  ejercicio_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (evaluacion_id, ejercicio_id),
  KEY ix_ee_ejercicio (ejercicio_id),
  CONSTRAINT fk_ee_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id) ON DELETE CASCADE,
  CONSTRAINT fk_ee_ejercicio  FOREIGN KEY (ejercicio_id)  REFERENCES ejercicios (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada práctica o evaluación presentada por un estudiante, con su puntaje (tasa de éxito en %).
CREATE TABLE intentos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id      INT UNSIGNED NOT NULL,
  evaluacion_id   INT UNSIGNED NULL,
  tema_id         INT UNSIGNED NULL,
  modo            ENUM('practica','evaluacion') NOT NULL,
  dificultad      TINYINT UNSIGNED NULL,
  estado          ENUM('en_curso','finalizado') NOT NULL DEFAULT 'en_curso',
  total_preguntas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  correctas       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  puntaje         DECIMAL(5,2) NULL,
  iniciado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finalizado_en   DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_intentos_usuario (usuario_id, modo, estado, finalizado_en),
  KEY ix_intentos_evaluacion (evaluacion_id),
  KEY ix_intentos_tema (tema_id),
  CONSTRAINT fk_intentos_usuario    FOREIGN KEY (usuario_id)    REFERENCES usuarios (id),
  CONSTRAINT fk_intentos_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones (id),
  CONSTRAINT fk_intentos_tema       FOREIGN KEY (tema_id)       REFERENCES temas (id),
  CONSTRAINT ck_intentos_modo CHECK ((modo = 'evaluacion' AND evaluacion_id IS NOT NULL) OR (modo = 'practica' AND tema_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respuesta dada a cada ejercicio dentro de un intento. En la evaluación, las filas se crean
-- al iniciar el intento (con opcion_id NULL) para fijar las preguntas sorteadas.
CREATE TABLE respuestas (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  intento_id   INT UNSIGNED NOT NULL,
  ejercicio_id INT UNSIGNED NOT NULL,
  opcion_id    INT UNSIGNED NULL,
  es_correcta  TINYINT(1) NULL,
  orden        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  respondida_en DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_respuestas_intento_ejercicio (intento_id, ejercicio_id),
  KEY ix_respuestas_ejercicio (ejercicio_id),
  KEY ix_respuestas_opcion (opcion_id),
  CONSTRAINT fk_respuestas_intento   FOREIGN KEY (intento_id)   REFERENCES intentos (id)   ON DELETE CASCADE,
  CONSTRAINT fk_respuestas_ejercicio FOREIGN KEY (ejercicio_id) REFERENCES ejercicios (id),
  CONSTRAINT fk_respuestas_opcion    FOREIGN KEY (opcion_id)    REFERENCES opciones (id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enlaces de la sección de recursos recomendados (HU-10).
CREATE TABLE recursos (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo      VARCHAR(150) NOT NULL,
  descripcion VARCHAR(255) NOT NULL DEFAULT '',
  url         VARCHAR(500) NOT NULL,
  tipo        VARCHAR(60)  NOT NULL DEFAULT 'Académico',
  orden       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  publicado   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_recursos_titulo (titulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
