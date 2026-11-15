-- APA Interactivo — datos iniciales del sistema.
-- Contiene roles, tipos de error, los cinco temas del alcance funcional (HU-03) con sus
-- ejemplos (HU-04), el banco de ejercicios (HU-05, HU-16), la evaluación final (HU-07)
-- y los recursos recomendados (HU-10). No contiene usuarios: el administrador inicial
-- se crea con database/instalar.php o con el procedimiento del manual de instalación.
-- El contenido normativo debe revisarlo el asesor antes de publicarse (riesgo R3).

SET NAMES utf8mb4;

INSERT INTO roles (id, clave, nombre, descripcion) VALUES
  (1, 'estudiante',    'Estudiante',              'Consulta contenido, practica, presenta evaluaciones y ve su progreso.'),
  (2, 'profesor',      'Profesor',                'Consulta resultados y errores frecuentes de sus grupos.'),
  (3, 'generador',     'Generador de contenidos', 'Elabora temas, ejemplos, ejercicios y recursos.'),
  (4, 'administrador', 'Administrador',           'Gestiona usuarios, roles y grupos, y consulta estadísticas.');

INSERT INTO tipos_error (id, nombre, descripcion) VALUES
  (1, 'Formato del documento',               'Márgenes, tipografía, interlineado, alineación, sangría, numeración y títulos.'),
  (2, 'Cita textual corta',                  'Comillas, página y puntuación en citas de menos de 40 palabras.'),
  (3, 'Cita textual en bloque',              'Presentación de citas de 40 palabras o más.'),
  (4, 'Paráfrasis sin atribución',           'Ideas ajenas sin autor y año, o copia con pocos cambios.'),
  (5, 'Cita narrativa o parentética',        'Construcción de la cita en el texto: autores, et al. y año.'),
  (6, 'Elementos de la referencia',          'Autor, fecha, título y fuente incompletos o en desorden.'),
  (7, 'Formato de la lista de referencias',  'Orden alfabético, sangría francesa y correspondencia con el texto.'),
  (8, 'Fuentes digitales',                   'DOI, URL, fechas y sitios web.');

-- Temas en el orden exigido por la HU-03. Formato básico: párrafos separados por una línea
-- en blanco, "## " para subtítulos, "- " para listas, **negrita** y _cursiva_.
INSERT INTO temas (id, titulo, resumen, contenido, orden, publicado) VALUES
(1, 'Formato general', 'Márgenes, tipografía, interlineado, sangría, numeración y títulos del trabajo.',
'Las normas APA 7.ª edición definen cómo se presenta un trabajo académico para que sea fácil de leer y tenga un aspecto uniforme. Estas son las reglas básicas para un trabajo de estudiante.

## Página y texto
- **Márgenes:** 2,54 cm (una pulgada) en los cuatro lados de cada página.
- **Tipografía:** una fuente legible usada en todo el trabajo, por ejemplo Calibri 11, Arial 11, Lucida Sans Unicode 10, Times New Roman 12 o Georgia 11.
- **Interlineado:** doble espacio en todo el documento, incluida la lista de referencias.
- **Alineación:** a la izquierda, con el margen derecho irregular. No se justifica el texto.
- **Sangría:** la primera línea de cada párrafo lleva una sangría de 1,27 cm.
- **Número de página:** en la esquina superior derecha de todas las páginas, desde la portada.

## Portada del trabajo de estudiante
Incluye el título, el nombre de los autores, la afiliación (programa e institución), el curso, el nombre del profesor y la fecha de entrega. En los trabajos de estudiante no se exige el titulillo (_running head_).

## Títulos
APA usa cinco niveles de título. El nivel 1 va centrado y en negrita; el nivel 2, alineado a la izquierda y en negrita. Los niveles se usan en orden, sin saltarse ninguno.', 1, 1),

(2, 'Citas textuales cortas', 'Cómo reproducir literalmente un fragmento de menos de 40 palabras.',
'Una cita textual reproduce exactamente las palabras de otro autor. Cuando el fragmento tiene **menos de 40 palabras** es una cita textual corta.

## Cómo se escribe
- Va **dentro del párrafo**, entre **comillas dobles**.
- Incluye el **apellido del autor**, el **año** y la **página** (p. 18) o el rango de páginas (pp. 18-19).
- Si la fuente no tiene páginas, se usa otro localizador: el número de párrafo (párr. 4) o el nombre de la sección.

## Dos formas de citar
- **Parentética:** “La escritura académica requiere precisión” (Gómez, 2023, p. 18).
- **Narrativa:** Gómez (2023) afirma que “la escritura académica requiere precisión” (p. 18).

## Puntuación
En la cita parentética, el punto final va **después** del paréntesis, no antes de cerrar las comillas.', 2, 1),

(3, 'Citas textuales largas', 'Citas en bloque de 40 palabras o más.',
'Cuando el fragmento que se reproduce tiene **40 palabras o más**, se presenta como una cita en bloque.

## Cómo se escribe
- Comienza en una **línea nueva**, separada del párrafo.
- Todo el bloque lleva una **sangría de 1,27 cm** desde el margen izquierdo.
- **No lleva comillas.**
- Mantiene el **doble espacio**, igual que el resto del documento.
- La cita (autor, año y página) va **después del punto final** del bloque, y después de ella no se escribe otro punto.
- Si el bloque tiene un segundo párrafo, la primera línea de ese párrafo lleva una sangría adicional de 1,27 cm.

## Cuándo usarla
APA recomienda usar las citas textuales con moderación y preferir la paráfrasis. Una cita en bloque se justifica cuando la redacción exacta del autor es importante.', 3, 1),

(4, 'Paráfrasis', 'Expresar con palabras propias las ideas de otro autor y citarlo.',
'Parafrasear es explicar con **palabras propias** una idea de otro autor. La idea sigue siendo ajena, por eso **siempre se cita** con apellido y año. La paráfrasis **no lleva comillas**.

## Reglas
- Incluye el **apellido del autor** y el **año**. La página no es obligatoria, pero se recomienda cuando ayuda a ubicar la idea en un texto largo.
- Cambiar dos o tres palabras de la frase original **no es parafrasear**: hay que reformular la idea y la estructura.

## Cita narrativa y parentética
- **Narrativa:** el autor forma parte de la oración. Ejemplo: Bonwell y Eison (1991) proponen el aprendizaje activo.
- **Parentética:** autor y año van entre paréntesis. Ejemplo: El aprendizaje activo mejora la participación (Bonwell y Eison, 1991).

## Número de autores
- **Un autor:** apellido y año en todas las citas.
- **Dos autores:** los dos apellidos en todas las citas.
- **Tres o más autores:** el apellido del primero seguido de **et al.** desde la primera cita, por ejemplo (Anaya-Figueroa et al., 2023).', 4, 1),

(5, 'Lista de referencias', 'Elementos, orden y formato de las referencias, incluidas las fuentes digitales.',
'La lista de referencias aparece al final del trabajo y reúne **todas las fuentes citadas** en el texto. Cada cita del texto debe tener su referencia, y cada referencia debe estar citada en el texto (las comunicaciones personales son la excepción).

## Elementos de una referencia
Cada entrada tiene cuatro elementos, en este orden: **autor, fecha, título y fuente**.
- **Libro:** Gómez, L. (2023). _Escritura académica_. Editorial Universitaria.
- **Artículo de revista:** Pérez, A. y Ruiz, M. (2022). Título del artículo. _Nombre de la Revista_, _12_(3), 45-60. https://doi.org/10.xxxx/xxxxx
- En APA 7 ya **no se escribe la ciudad** de la editorial.

## Formato de la lista
- Orden **alfabético** por el apellido del primer autor.
- **Sangría francesa** de 1,27 cm: la primera línea al margen y las siguientes con sangría.
- Doble espacio. Los títulos de obras independientes (libros, informes) van en **cursiva**.

## Fuentes digitales
- El **DOI** se escribe como enlace: https://doi.org/...
- La URL se escribe directamente; no se antepone “Recuperado de”, salvo cuando se indica una fecha de consulta.
- La **fecha de consulta** solo se incluye si el contenido cambia con el tiempo y no está archivado.
- Si no hay fecha de publicación se escribe **s. f.**
- Si el autor y el nombre del sitio web son iguales, se omite el nombre del sitio.', 5, 1);

INSERT INTO ejemplos (tema_id, es_correcto, texto, explicacion, orden) VALUES
(1, 1, 'Documento con márgenes de 2,54 cm, Times New Roman de 12 puntos, doble espacio, texto alineado a la izquierda y número de página en la esquina superior derecha.',
 'Cumple las reglas básicas de formato y las aplica de forma uniforme en todo el trabajo.', 1),
(1, 0, 'Texto justificado, interlineado sencillo y una fuente distinta en cada sección; los números de página aparecen centrados al pie.',
 'Error: el formato no es uniforme y no sigue APA 7. Corrección: alinear a la izquierda sin justificar, usar doble espacio, una sola fuente en todo el documento y el número de página arriba a la derecha.', 2),
(2, 1, 'Según Gómez (2023), “la escritura académica requiere precisión” (p. 18).',
 'Cita narrativa: el apellido y el año están en la oración, el texto literal va entre comillas y la página al final.', 1),
(2, 1, '“La escritura académica requiere precisión” (Gómez, 2023, p. 18).',
 'Cita parentética: comillas dobles, apellido, año y página entre paréntesis, y el punto después del paréntesis.', 2),
(2, 0, 'La escritura académica requiere precisión. Gómez 2023.',
 'Error: el texto copiado no lleva comillas, falta el paréntesis y falta la página. Corrección: “La escritura académica requiere precisión” (Gómez, 2023, p. 18).', 3),
(3, 1, 'Pérez (2021) describe así el proceso de revisión:\n\nRevisar un texto académico no consiste solo en corregir la ortografía. Supone leer de nuevo cada párrafo con la pregunta del trabajo en mente, comprobar que cada afirmación tenga una fuente y eliminar todo lo que no aporte a la idea central del documento. (p. 34)',
 'El fragmento tiene más de 40 palabras: va en párrafo aparte, con sangría de 1,27 cm, sin comillas y con la página después del punto final.', 1),
(3, 0, 'Pérez (2021) describe así el proceso de revisión: “Revisar un texto académico no consiste solo en corregir la ortografía. Supone leer de nuevo cada párrafo con la pregunta del trabajo en mente, comprobar que cada afirmación tenga una fuente y eliminar todo lo que no aporte a la idea central del documento” (p. 34).',
 'Error: una cita de 40 palabras o más se dejó dentro del párrafo y entre comillas. Corrección: presentarla como bloque aparte, con sangría de 1,27 cm, sin comillas y con (p. 34) después del punto final.', 2),
(4, 1, 'Pérez (2023) explica que leer el borrador varias veces es la estrategia que más ayuda a que un texto sea claro.',
 'Reformula la idea con palabras propias, sin comillas, e indica el autor y el año.', 1),
(4, 0, 'La revisión repetida del borrador es la estrategia que más mejora la claridad.',
 'Error: copia casi literal de la fuente sin autor ni año, lo que constituye plagio. Corrección: reformular la idea con palabras propias y citar: Pérez (2023) explica que…', 2),
(5, 1, 'Gómez, L. (2023). _Escritura académica_. Editorial Universitaria.',
 'Sigue el orden autor, fecha, título y fuente; el título del libro va en cursiva y no se incluye la ciudad.', 1),
(5, 0, 'Libro de escritura académica, Laura Gómez, 2023, Cali: Editorial Universitaria.',
 'Error: los elementos están en desorden, el autor no aparece como «Apellido, inicial.», la fecha no va entre paréntesis y se incluye la ciudad. Corrección: Gómez, L. (2023). _Escritura académica_. Editorial Universitaria.', 2),
(5, 0, 'Recuperado de Google: www.ejemplo.org',
 'Error: un buscador no es la fuente, falta el autor, la fecha y el título, y se antepone «Recuperado de» sin necesidad. Corrección: Autor. (Año). _Título de la página_. Nombre del sitio. https://www.ejemplo.org/pagina', 3);

-- Banco de ejercicios: 45 ejercicios, al menos 8 por tema, con tipo de error y nivel
-- (1 = básico, 2 = intermedio, 3 = avanzado). La opción correcta cambia de posición.
INSERT INTO ejercicios (id, tema_id, tipo_error_id, tipo, dificultad, enunciado, explicacion) VALUES
-- Tema 1. Formato general
(1, 1, 1, 'seleccion_multiple', 1, '¿Qué margen se usa en cada lado de una página en APA 7?', 'APA 7 usa márgenes de 2,54 cm (una pulgada) en los cuatro lados de la página.'),
(2, 1, 1, 'seleccion_multiple', 1, '¿Cuál de estas fuentes es una opción aceptada en APA 7?', 'APA 7 admite varias fuentes legibles, como Times New Roman 12, Calibri 11 o Arial 11. Lo importante es usar la misma en todo el trabajo.'),
(3, 1, 1, 'verdadero_falso', 1, 'En APA 7 el texto del trabajo debe ir justificado en ambos márgenes.', 'Falso. El texto se alinea a la izquierda y el margen derecho queda irregular; no se justifica.'),
(4, 1, 1, 'seleccion_multiple', 1, '¿Qué interlineado se usa en el cuerpo del trabajo?', 'Se usa doble espacio en todo el documento, incluida la lista de referencias.'),
(5, 1, 1, 'seleccion_multiple', 2, '¿Dónde se ubica el número de página?', 'El número de página va en la esquina superior derecha de todas las páginas, incluida la portada.'),
(6, 1, 1, 'completar', 2, 'Complete: la primera línea de cada párrafo lleva una sangría de ____.', 'La sangría de primera línea es de 1,27 cm (media pulgada).'),
(7, 1, 1, 'seleccion_multiple', 3, 'En la portada de un trabajo de estudiante en APA 7, ¿qué elemento NO es obligatorio?', 'En los trabajos de estudiante no se exige el titulillo (running head). Sí se incluyen el título, los autores, la afiliación, el curso, el profesor, la fecha y el número de página.'),
(8, 1, 1, 'seleccion_multiple', 3, '¿Cómo se presenta un título de nivel 1?', 'El nivel 1 va centrado y en negrita. El nivel 2 va alineado a la izquierda y en negrita.'),
-- Tema 2. Citas textuales cortas
(9,  2, 2, 'seleccion_multiple', 1, 'Una cita textual tiene 25 palabras. ¿Cómo debe presentarse?', 'Las citas de menos de 40 palabras se integran en el párrafo entre comillas dobles.'),
(10, 2, 2, 'seleccion_multiple', 1, 'Además del autor y el año, ¿qué dato incluye una cita textual de un libro con páginas?', 'La cita textual indica la página donde aparece el fragmento, por ejemplo (Gómez, 2023, p. 18).'),
(11, 2, 2, 'verdadero_falso', 1, 'Una cita textual corta se escribe en cursiva y sin comillas.', 'Falso. La cita textual corta va entre comillas dobles; la cursiva no reemplaza las comillas.'),
(12, 2, 2, 'completar', 1, 'Complete la cita: “La escritura académica requiere precisión” (Gómez, 2023, ____).', 'La página se abrevia «p.» seguida del número: (Gómez, 2023, p. 18). Para varias páginas se usa «pp.».'),
(13, 2, 2, 'seleccion_multiple', 2, '¿Dónde va el punto final cuando una cita textual corta termina con una cita parentética?', 'El punto va después del paréntesis: “…requiere precisión” (Gómez, 2023, p. 18).'),
(14, 2, 2, 'seleccion_multiple', 2, 'La fuente es una página web sin números de página. ¿Qué localizador se usa en la cita textual?', 'Cuando no hay páginas se usa otro localizador, como el número de párrafo (párr. 4) o el nombre de la sección.'),
(15, 2, 2, 'seleccion_multiple', 2, '¿Cuál es una cita narrativa textual correcta?', 'En la cita narrativa el apellido y el año van en la oración, el texto literal entre comillas y la página entre paréntesis al final.'),
(16, 2, 2, 'seleccion_multiple', 3, '¿Qué extensión define a una cita textual corta?', 'Una cita textual es corta si tiene menos de 40 palabras. Con 40 palabras o más se presenta en bloque.'),
-- Tema 3. Citas textuales largas
(17, 3, 3, 'seleccion_multiple', 1, '¿Cuándo se presenta una cita textual en bloque?', 'Las citas de 40 palabras o más se presentan en bloque.'),
(18, 3, 3, 'seleccion_multiple', 1, '¿Qué sangría lleva una cita en bloque?', 'Todo el bloque lleva una sangría de 1,27 cm desde el margen izquierdo.'),
(19, 3, 3, 'verdadero_falso', 1, 'Una cita en bloque se escribe entre comillas dobles.', 'Falso. La cita en bloque no lleva comillas: el formato de bloque ya indica que es un texto literal.'),
(20, 3, 3, 'seleccion_multiple', 2, 'En una cita en bloque, ¿dónde va la cita parentética con la página?', 'En el bloque, la cita va después del punto final del texto citado y no se agrega otro punto después de ella.'),
(21, 3, 3, 'seleccion_multiple', 2, '¿Qué interlineado tiene una cita en bloque?', 'El bloque mantiene el doble espacio, igual que el resto del documento.'),
(22, 3, 3, 'seleccion_multiple', 2, 'Una cita de 45 palabras se introduce con «Pérez (2021) señala:». ¿Cómo se completa?', 'Con autor y año en la oración, el bloque va aparte, con sangría y sin comillas, y al final solo se indica la página (p. 34) después del punto.'),
(23, 3, 3, 'completar', 3, 'Complete: si una cita en bloque tiene un segundo párrafo, la primera línea de ese párrafo lleva una sangría adicional de ____.', 'Los párrafos siguientes dentro del bloque llevan una sangría adicional de 1,27 cm en su primera línea.'),
(24, 3, 3, 'seleccion_multiple', 3, '¿Qué recomienda APA sobre el uso de citas textuales largas?', 'APA recomienda usar las citas textuales con moderación y preferir la paráfrasis; la cita en bloque se justifica cuando la redacción exacta es importante.'),
-- Tema 4. Paráfrasis
(25, 4, 4, 'seleccion_multiple', 1, '¿Qué información mínima acompaña una paráfrasis?', 'Una paráfrasis requiere autor y año. La página es opcional, aunque se recomienda cuando ayuda a ubicar la idea.'),
(26, 4, 5, 'seleccion_multiple', 1, '¿Cuál es un ejemplo de cita narrativa?', 'En la cita narrativa el autor forma parte de la oración y el año va entre paréntesis: Pérez (2023) explica que…'),
(27, 4, 5, 'seleccion_multiple', 1, '¿Cuál es un ejemplo de cita parentética?', 'En la cita parentética el apellido y el año van juntos entre paréntesis al final de la idea.'),
(28, 4, 4, 'verdadero_falso', 1, 'La paráfrasis se escribe entre comillas dobles.', 'Falso. La paráfrasis expresa la idea con palabras propias, por eso no lleva comillas; sí lleva autor y año.'),
(29, 4, 5, 'seleccion_multiple', 2, '¿Cómo se cita en el texto una obra de dos autores?', 'En una obra de dos autores se escriben los dos apellidos en todas las citas.'),
(30, 4, 5, 'seleccion_multiple', 2, '¿Cómo se cita en el texto una obra de cuatro autores?', 'Para tres o más autores se escribe el primer apellido seguido de «et al.» desde la primera cita.'),
(31, 4, 4, 'seleccion_multiple', 2, '¿Qué debe hacerse al parafrasear una fuente?', 'Parafrasear es reformular la idea y su estructura con palabras propias, y citar la fuente. Cambiar algunas palabras no es suficiente.'),
(32, 4, 4, 'seleccion_multiple', 3, '¿Es obligatorio el número de página en una paráfrasis?', 'No es obligatorio, pero APA recomienda incluirlo cuando ayuda al lector a ubicar la idea, por ejemplo en un libro extenso.'),
-- Tema 5. Lista de referencias
(33, 5, 7, 'seleccion_multiple', 1, '¿Cómo se ordena la lista de referencias?', 'Las entradas se ordenan alfabéticamente por el apellido del primer autor.'),
(34, 5, 7, 'seleccion_multiple', 1, '¿Qué sangría se usa en cada entrada de la lista de referencias?', 'Se usa sangría francesa de 1,27 cm: la primera línea al margen y las siguientes con sangría.'),
(35, 5, 6, 'seleccion_multiple', 1, '¿Qué elemento va en cursiva en la referencia de un libro?', 'El título del libro va en cursiva. En APA 7 ya no se incluye la ciudad de publicación.'),
(36, 5, 7, 'verdadero_falso', 1, 'Toda fuente citada en el texto debe tener su entrada en la lista de referencias, salvo las comunicaciones personales.', 'Verdadero. Debe haber correspondencia entre las citas del texto y la lista de referencias; las comunicaciones personales solo se citan en el texto.'),
(37, 5, 8, 'seleccion_multiple', 1, 'Una página web no indica fecha de publicación. ¿Qué se escribe en la fecha?', 'Para una fuente sin fecha se escribe «s. f.» (sin fecha).'),
(38, 5, 8, 'seleccion_multiple', 2, '¿Cómo se presenta el DOI en una referencia APA 7?', 'El DOI se presenta como enlace, con el prefijo https://doi.org/.'),
(39, 5, 6, 'seleccion_multiple', 2, '¿Qué datos incluye la referencia de un artículo de revista, cuando están disponibles?', 'Además de autor, fecha y título, se incluyen el nombre de la revista, el volumen, el número, las páginas y el DOI.'),
(40, 5, 8, 'seleccion_multiple', 2, '¿Cuándo se incluye la fecha de consulta en una referencia?', 'Solo cuando el contenido está diseñado para cambiar con el tiempo y no está archivado; las páginas estables no la necesitan.'),
(41, 5, 8, 'seleccion_multiple', 2, 'El autor de una página web y el nombre del sitio son iguales. ¿Qué se hace?', 'Se omite el nombre del sitio para no repetir el mismo dato.'),
(42, 5, 8, 'seleccion_multiple', 2, '¿Qué datos se usan para referenciar una página web?', 'Se incluyen autor, fecha, título de la página, nombre del sitio (si es distinto del autor) y URL.'),
(43, 5, 8, 'seleccion_multiple', 2, '¿Qué se hace con la URL de una página estable?', 'La URL se escribe directamente; no se antepone «Recuperado de» de manera rutinaria.'),
(44, 5, 6, 'seleccion_multiple', 3, '¿Cómo se presenta una organización como autora en la referencia?', 'Se escribe el nombre completo de la organización como autor corporativo.'),
(45, 5, 6, 'completar', 3, 'Complete la referencia del libro: ____ (2023). Escritura académica. Editorial Universitaria.', 'El autor se escribe con el apellido seguido de la inicial del nombre: «Gómez, L.». Luego van el año entre paréntesis, el título en cursiva y la editorial, sin ciudad.');

INSERT INTO opciones (ejercicio_id, texto, es_correcta, orden) VALUES
(1, '2,54 cm en los cuatro lados', 1, 1), (1, '3 cm a la izquierda y 2 cm en los demás lados', 0, 2), (1, '1,5 cm en los cuatro lados', 0, 3),
(2, 'Comic Sans de 12 puntos', 0, 1), (2, 'Times New Roman de 12 puntos', 1, 2), (2, 'Arial de 8 puntos', 0, 3),
(3, 'Verdadero', 0, 1), (3, 'Falso', 1, 2),
(4, 'Sencillo', 0, 1), (4, '1,5 líneas', 0, 2), (4, 'Doble', 1, 3),
(5, 'Centrado en el pie de página', 0, 1), (5, 'Esquina superior derecha de todas las páginas', 1, 2), (5, 'Solo a partir de la introducción', 0, 3),
(6, '0,5 cm', 0, 1), (6, '2,54 cm', 0, 2), (6, '1,27 cm', 1, 3),
(7, 'El titulillo (running head)', 1, 1), (7, 'El título del trabajo', 0, 2), (7, 'El número de página', 0, 3), (7, 'El nombre del profesor', 0, 4),
(8, 'Alineado a la izquierda y en cursiva', 0, 1), (8, 'Centrado y en negrita', 1, 2), (8, 'Centrado, en mayúsculas sostenidas y subrayado', 0, 3),

(9, 'En un bloque aparte, sin comillas', 0, 1), (9, 'Dentro del párrafo, entre comillas dobles', 1, 2), (9, 'En cursiva y sin indicar la página', 0, 3),
(10, 'El número de edición', 0, 1), (10, 'La URL de la editorial', 0, 2), (10, 'El número de página', 1, 3),
(11, 'Verdadero', 0, 1), (11, 'Falso', 1, 2),
(12, 'p. 18', 1, 1), (12, 'pág. 18', 0, 2), (12, 'página dieciocho', 0, 3),
(13, 'Antes de cerrar las comillas', 0, 1), (13, 'Después del paréntesis de la cita', 1, 2), (13, 'No se escribe punto', 0, 3),
(14, 'El número de párrafo o el nombre de la sección', 1, 1), (14, 'El número 1, por ser la primera página', 0, 2), (14, 'No se cita porque no tiene páginas', 0, 3),
(15, 'Gómez afirma que “la escritura académica requiere precisión” (2023).', 0, 1), (15, '“La escritura académica requiere precisión”, Gómez, 2023, 18.', 0, 2), (15, 'Gómez (2023) afirma que “la escritura académica requiere precisión” (p. 18).', 1, 3),
(16, 'Menos de 60 palabras', 0, 1), (16, 'Menos de 40 palabras', 1, 2), (16, 'Menos de tres líneas', 0, 3),

(17, 'Cuando tiene más de 10 palabras', 0, 1), (17, 'Solo cuando proviene de un libro', 0, 2), (17, 'Cuando tiene 40 palabras o más', 1, 3),
(18, '1,27 cm desde el margen izquierdo', 1, 1), (18, 'Ninguna, va centrada', 0, 2), (18, '5 cm a ambos lados', 0, 3),
(19, 'Verdadero', 0, 1), (19, 'Falso', 1, 2),
(20, 'Antes del punto final del bloque', 0, 1), (20, 'Después del punto final del bloque, sin otro punto', 1, 2), (20, 'En una nota al pie de página', 0, 3),
(21, 'Doble, igual que el resto del documento', 1, 1), (21, 'Sencillo, para diferenciarla', 0, 2), (21, '1,5 líneas', 0, 3),
(22, 'Bloque aparte con sangría, sin comillas y con (p. 34) al final', 1, 1), (22, 'Entre comillas dentro del párrafo, con (Pérez, 2021) al final', 0, 2), (22, 'En cursiva y centrado, sin página', 0, 3),
(23, '0,5 cm', 0, 1), (23, '1,27 cm', 1, 2), (23, '2,54 cm', 0, 3),
(24, 'Usar una cita en bloque en cada página', 0, 1), (24, 'Copiar el texto completo para no cambiar la idea', 0, 2), (24, 'Usarlas con moderación y preferir la paráfrasis', 1, 3),

(25, 'Solo la URL', 0, 1), (25, 'Autor y año', 1, 2), (25, 'Autor, año y página, siempre', 0, 3),
(26, 'Pérez (2023) explica que…', 1, 1), (26, '…como se ha explicado (Pérez, 2023).', 0, 2), (26, '…como se ha explicado. Pérez 2023.', 0, 3),
(27, 'Pérez (2023) sostiene que la escritura requiere revisión.', 0, 1), (27, 'Pérez sostiene que la escritura requiere revisión (2023, Pérez).', 0, 2), (27, 'La escritura requiere revisión (Pérez, 2023).', 1, 3),
(28, 'Verdadero', 0, 1), (28, 'Falso', 1, 2),
(29, 'Solo el apellido del primer autor', 0, 1), (29, 'Los apellidos de los dos autores en todas las citas', 1, 2), (29, 'El primer apellido seguido de et al.', 0, 3),
(30, 'El primer apellido seguido de et al.', 1, 1), (30, 'Los cuatro apellidos en todas las citas', 0, 2), (30, 'Solo el año, sin autores', 0, 3),
(31, 'Cambiar dos o tres palabras del original', 0, 1), (31, 'Copiar la frase y poner el año al final', 0, 2), (31, 'Reformular la idea con palabras propias y citar la fuente', 1, 3),
(32, 'Sí, siempre es obligatorio', 0, 1), (32, 'No es obligatorio, pero se recomienda cuando ayuda a ubicar la idea', 1, 2), (32, 'Nunca debe incluirse', 0, 3),

(33, 'Por orden de aparición en el texto', 0, 1), (33, 'Por fecha, de la más reciente a la más antigua', 0, 2), (33, 'Alfabéticamente por el apellido del primer autor', 1, 3),
(34, 'Sangría francesa de 1,27 cm', 1, 1), (34, 'Primera línea sangrada y las demás al margen', 0, 2), (34, 'Sin sangría', 0, 3),
(35, 'El apellido del autor', 0, 1), (35, 'El título del libro', 1, 2), (35, 'El año de publicación', 0, 3),
(36, 'Verdadero', 1, 1), (36, 'Falso', 0, 2),
(37, 's. f.', 1, 1), (37, 'El año de consulta', 0, 2), (37, 'Una fecha aproximada', 0, 3),
(38, 'Como «doi:» seguido del número, sin enlace', 0, 1), (38, 'Como enlace https://doi.org/…', 1, 2), (38, 'Se omite si el artículo está en internet', 0, 3),
(39, 'Solo el título y el año', 0, 1), (39, 'La ciudad de la revista y el nombre del editor', 0, 2), (39, 'Revista, volumen, número, páginas y DOI', 1, 3),
(40, 'Cuando el contenido cambia con el tiempo y no está archivado', 1, 1), (40, 'En todas las páginas web', 0, 2), (40, 'Nunca', 0, 3),
(41, 'Se escribe dos veces para completar los datos', 0, 1), (41, 'Se omite el nombre del sitio', 1, 2), (41, 'Se reemplaza el autor por la URL', 0, 3),
(42, 'Solo la URL y la fecha de consulta', 0, 1), (42, 'Título, buscador usado y URL', 0, 2), (42, 'Autor, fecha, título, sitio (si es distinto del autor) y URL', 1, 3),
(43, 'Incluir la URL directamente', 1, 1), (43, 'Escribir siempre «Recuperado de» antes de la URL', 0, 2), (43, 'Reemplazarla por el nombre del buscador', 0, 3),
(44, 'Como autor corporativo, con su nombre completo', 1, 1), (44, 'Se omite el autor y se empieza por la URL', 0, 2), (44, 'Se escribe «Anónimo»', 0, 3),
(45, 'Laura Gómez,', 0, 1), (45, 'Gómez, L.', 1, 2), (45, 'GÓMEZ LAURA', 0, 3), (45, 'L. Gómez', 0, 4);

INSERT INTO evaluaciones (id, titulo, descripcion, num_preguntas, activa) VALUES
(1, 'Evaluación final APA 7.ª edición', 'Diez preguntas tomadas al azar de todos los temas.', 10, 1);

INSERT INTO evaluacion_ejercicio (evaluacion_id, ejercicio_id)
SELECT 1, id FROM ejercicios;

INSERT INTO recursos (titulo, descripcion, url, tipo, orden, publicado) VALUES
('Bases de datos de la Biblioteca UNIAJC', 'Libros electrónicos y artículos de todas las áreas, con usuario y contraseña institucional, disponibles 24/7.', 'https://www.uniajc.edu.co/biblioteca/bases-de-datos/', 'Biblioteca virtual', 1, 1),
('Google Académico', 'Buscador de artículos, libros, tesis y documentos académicos.', 'https://scholar.google.com/', 'Búsqueda académica', 2, 1),
('Zotero', 'Gestor bibliográfico gratuito para guardar fuentes y generar citas y referencias en APA 7.', 'https://www.zotero.org/', 'Gestor bibliográfico', 3, 1),
('Mendeley', 'Gestor bibliográfico para organizar referencias y documentos PDF.', 'https://www.mendeley.com/', 'Gestor bibliográfico', 4, 1);
