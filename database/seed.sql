-- Carga cinco módulos teóricos y 30 ejercicios de práctica/evaluación.
-- Sustituye ADMIN_USER_ID por el UUID de un perfil con rol admin.
do $$
declare
  admin_id uuid := 'ADMIN_USER_ID';
  module_id uuid;
  question_id uuid;
  item record;
  choice_index integer;
begin
  insert into public.modules (title, description, theory, position, published, created_by) values
    ('Formato general APA 7', 'Márgenes, tipografía, interlineado y estructura del documento.', 'Configura márgenes de 2,54 cm en todos los lados. Usa doble espacio en el documento completo, incluida la lista de referencias, salvo excepciones editoriales específicas. Una fuente legible y permitida puede ser Arial 11, Calibri 11, Georgia 11 o Times New Roman 12; mantén la misma familia tipográfica en todo el trabajo. Alinea el texto a la izquierda, sin justificar, y usa sangría de primera línea de 1,27 cm en los párrafos. La página de título estudiantil incluye título, autor, institución, curso, docente y fecha de entrega. Numera todas las páginas en la esquina superior derecha.', 1, true, admin_id),
    ('Citas textuales', 'Citas cortas, citas en bloque y localización de la fuente.', 'Una cita textual reproduce exactamente las palabras de una fuente e incluye autor, año y localizador (normalmente página). Las citas de menos de 40 palabras se integran en el párrafo entre comillas dobles. Las citas de 40 palabras o más se presentan en un bloque independiente, con sangría de 1,27 cm, doble espacio y sin comillas. En fuentes sin páginas, usa un localizador alternativo claro, como número de párrafo, sección o marca de tiempo. La puntuación final se coloca después de la cita parentética.', 2, true, admin_id),
    ('Paráfrasis y citas en el texto', 'Cita narrativa, parentética y paráfrasis responsable.', 'La paráfrasis expresa con palabras propias una idea de otra fuente y siempre requiere autor y año. La página no es obligatoria en una paráfrasis, aunque se recomienda cuando ayuda a encontrar el pasaje. En una cita narrativa el apellido aparece en la oración: Pérez (2023) explica… En una cita parentética autor y año van entre paréntesis: (Pérez, 2023). Para dos autores, incluye ambos apellidos en cada cita. Para tres o más autores, usa el primer apellido seguido de “et al.” desde la primera cita, salvo que esto genere ambigüedad.', 3, true, admin_id),
    ('Lista de referencias', 'Elementos y formato de libros, artículos y páginas web.', 'La lista de referencias contiene las obras citadas en el texto y permite localizarlas. Ordénala alfabéticamente por el apellido del primer autor. Usa doble espacio y sangría francesa de 1,27 cm. En un libro, presenta autor, año, título en cursiva y editorial; no incluyas ciudad de publicación. En un artículo de revista, añade título del artículo, nombre de la revista y volumen en cursiva, número entre paréntesis, páginas y DOI como enlace cuando esté disponible. Un DOI se escribe como URL https://doi.org/…', 4, true, admin_id),
    ('Fuentes digitales y formato de citas', 'Autores corporativos, fechas, DOI y datos faltantes.', 'Para una página web, incluye autor, fecha, título de la página y nombre del sitio cuando sea distinto del autor, seguido de la URL. Si una fuente no tiene fecha, usa “s. f.” en español. No agregues “Recuperado de” antes de una URL estable; incluye fecha de recuperación solo para contenido diseñado para cambiar con el tiempo. Si autor y nombre del sitio coinciden, omite el nombre del sitio para evitar duplicarlo. Las fuentes citadas en el texto deben aparecer en referencias, y cada entrada de referencias debe estar citada en el trabajo.', 5, true, admin_id)
  on conflict (position) do update set title = excluded.title, description = excluded.description,
    theory = excluded.theory, published = true;

  update public.modules set
    correct_example = case title
      when 'Formato general APA 7' then 'El documento mantiene doble espacio y márgenes de 2,54 cm en los cuatro lados.'
      when 'Citas textuales' then '“La escritura académica requiere precisión” (Gómez, 2023, p. 18).'
      when 'Paráfrasis y citas en el texto' then 'Gómez (2023) explica que la revisión mejora la claridad de un texto.'
      when 'Lista de referencias' then 'Gómez, L. (2023). Escritura académica. Editorial Universitaria.'
      else 'Organización Mundial de la Salud. (2024). Título de la página. https://ejemplo.org'
    end,
    incorrect_example = case title
      when 'Formato general APA 7' then 'Cada página usa una fuente y un interlineado distintos.'
      when 'Citas textuales' then 'La escritura académica requiere precisión. Gómez 2023.'
      when 'Paráfrasis y citas en el texto' then 'La revisión mejora la claridad de un texto. [sin autor ni año]'
      when 'Lista de referencias' then 'Libro de escritura académica, Gómez, 2023, Editorial Universitaria.'
      else 'Recuperado de Google: www.ejemplo.org'
    end,
    example_explanation = 'Compara la forma, los datos de identificación y la puntuación: una cita o referencia debe permitir reconocer y localizar la fuente.';

  for item in
    select * from (values
      ('Formato general APA 7', 1, '¿Qué medida de margen se usa normalmente en cada lado de una página APA?', 'La configuración estándar usa una pulgada, equivalente a 2,54 cm, en los cuatro lados.', array['2,54 cm en todos los lados','1 cm arriba y abajo; 3 cm a los lados','Solo margen izquierdo de 3 cm'], 1),
      ('Formato general APA 7', 2, '¿Cuál es una opción tipográfica aceptada para un trabajo estudiantil APA 7?', 'Arial de 11 puntos es una de las opciones legibles aceptadas. Mantén el tipo y tamaño de letra de forma consistente.', array['Arial 11 puntos','Comic Sans 10 puntos','Cualquier tamaño distinto en cada sección'], 1),
      ('Formato general APA 7', 3, '¿Cómo se alinea normalmente el texto de un trabajo APA?', 'Se recomienda alinear a la izquierda y mantener el margen derecho irregular; no justifiques el texto.', array['A la izquierda, sin justificar','Justificado en ambos márgenes','Centrado en cada párrafo'], 1),
      ('Formato general APA 7', 4, '¿Qué interlineado se utiliza normalmente en el documento?', 'El doble espacio se usa en todo el trabajo, incluida la lista de referencias, salvo excepciones específicas.', array['Doble espacio','Espacio sencillo en todo el documento','1,5 solo en el cuerpo'], 1),
      ('Formato general APA 7', 5, '¿Dónde se coloca el número de página?', 'El número de página aparece en la esquina superior derecha en todas las páginas.', array['Esquina superior derecha','Centro del pie de página','Solo en la portada'], 1),
      ('Formato general APA 7', 6, '¿Cuál es la sangría inicial estándar de un párrafo?', 'Los párrafos del cuerpo usan una sangría de primera línea de 0,5 pulgadas (1,27 cm).', array['1,27 cm en la primera línea','2,54 cm en todas las líneas','Sin sangría en ningún párrafo'], 1),

      ('Citas textuales', 1, 'Una cita textual tiene 25 palabras. ¿Cómo debe presentarse?', 'Las citas de menos de 40 palabras se integran en el texto entre comillas dobles.', array['Dentro del párrafo entre comillas dobles','En un bloque separado sin comillas','En cursiva y sin citar la página'], 1),
      ('Citas textuales', 2, '¿Cuándo se presenta una cita textual en bloque según APA 7?', 'Las citas de 40 palabras o más se presentan en un bloque independiente.', array['Cuando tiene 40 palabras o más','Cuando tiene más de 10 palabras','Solo cuando proviene de un libro'], 1),
      ('Citas textuales', 3, '¿Qué dato localizador suele incluir una cita textual de un libro paginado?', 'Una cita textual debe señalar la página donde aparece el pasaje, además del autor y año.', array['Número de página','Número de edición únicamente','URL de la editorial aunque el libro sea impreso'], 1),
      ('Citas textuales', 4, '¿Cómo se presenta el bloque de una cita de 45 palabras?', 'El bloque va en línea aparte con sangría de 1,27 cm, doble espacio y sin comillas.', array['Con sangría de 1,27 cm y sin comillas','Entre comillas dentro del mismo párrafo','En cursiva y centrado'], 1),
      ('Citas textuales', 5, 'En una fuente web sin páginas, ¿qué puede reemplazar el número de página?', 'Cuando no hay páginas, usa un localizador que ayude a encontrar el pasaje, como el número de párrafo o el encabezado.', array['Número de párrafo o encabezado de sección','El número de resultados de Google','La fecha en que se descargó el archivo'], 1),
      ('Citas textuales', 6, '¿Dónde se coloca el punto cuando una cita breve termina con una cita parentética?', 'En una cita breve integrada, la oración cierra después del paréntesis de la cita.', array['Después del paréntesis de la cita','Antes de las comillas de cierre siempre','Antes del paréntesis, sin excepción'], 1),

      ('Paráfrasis y citas en el texto', 1, '¿Qué información mínima acompaña una paráfrasis?', 'Una paráfrasis requiere autor y año. El número de página es opcional, pero puede facilitar la búsqueda.', array['Autor y año','Solo la URL','Autor, año y página son siempre obligatorios'], 1),
      ('Paráfrasis y citas en el texto', 2, '¿Cuál es un ejemplo de cita narrativa?', 'En una cita narrativa el autor forma parte de la redacción y el año aparece entre paréntesis.', array['Pérez (2023) explica que…','…como se ha explicado (Pérez, 2023).','…como se ha explicado. Pérez 2023'], 1),
      ('Paráfrasis y citas en el texto', 3, '¿Cuál es un ejemplo de cita parentética?', 'En una cita parentética, apellido y año se colocan juntos entre paréntesis.', array['La escritura requiere revisión (Pérez, 2023).','Pérez (2023) sostiene que la escritura requiere revisión.','Pérez sostiene que la escritura requiere revisión, 2023.'], 1),
      ('Paráfrasis y citas en el texto', 4, '¿Cómo se cita una obra de dos autores en el texto?', 'Se incluyen los apellidos de ambos autores en cada cita. En citas parentéticas en español se unen con “&” según el estilo APA.', array['Se incluyen los apellidos de ambos autores','Se escribe solo el primer apellido seguido de et al.','Se reemplazan los autores por el título'], 1),
      ('Paráfrasis y citas en el texto', 5, '¿Cómo se cita normalmente una obra de cuatro autores?', 'Para tres o más autores se usa el primer apellido seguido de “et al.” desde la primera cita, salvo casos de ambigüedad.', array['Primer apellido seguido de et al.','Los cuatro apellidos en todas las citas','Solo el título de la obra'], 1),
      ('Paráfrasis y citas en el texto', 6, '¿Qué debe hacerse al parafrasear una fuente?', 'La paráfrasis debe expresar genuinamente la idea con redacción propia y citar la fuente; cambiar solo algunas palabras no basta.', array['Reformular la idea con palabras propias y citarla','Cambiar dos o tres palabras sin citar','Copiar el texto y quitar las comillas'], 1),

      ('Lista de referencias', 1, '¿Cómo se ordena normalmente la lista de referencias?', 'Las entradas se ordenan alfabéticamente por el apellido del primer autor.', array['Alfabéticamente por el apellido del primer autor','Por orden de aparición en el texto','Por fecha, de la más reciente a la más antigua'], 1),
      ('Lista de referencias', 2, '¿Qué sangría se usa en cada entrada de referencias?', 'La lista usa sangría francesa: la primera línea queda al margen y las siguientes se sangran 1,27 cm.', array['Sangría francesa de 1,27 cm','Primera línea sangrada y las siguientes al margen','Sangría de 2,54 cm en todas las líneas'], 1),
      ('Lista de referencias', 3, '¿Qué elemento suele ir en cursiva en una referencia de libro?', 'El título del libro va en cursiva; la ciudad de publicación ya no se incluye en APA 7.', array['Título del libro','Apellido del autor','Año de publicación'], 1),
      ('Lista de referencias', 4, '¿Cómo se presenta un DOI en una referencia APA 7?', 'El DOI se presenta en formato de enlace URL con el prefijo https://doi.org/.', array['Como enlace https://doi.org/...','Como “doi:” seguido del número, sin enlace','Se omite siempre que el artículo tenga páginas'], 1),
      ('Lista de referencias', 5, '¿Qué debe incluir la referencia de un artículo de revista cuando esos datos están disponibles?', 'Incluye revista, volumen, número, páginas y DOI cuando exista, además de autor, fecha y título del artículo.', array['Revista, volumen, número, páginas y DOI si existe','Solo autor y nombre de la revista','La base de datos donde se encontró, siempre'], 1),
      ('Lista de referencias', 6, '¿Cuál afirmación sobre las referencias es correcta?', 'Debe existir correspondencia entre las obras citadas en el texto y las entradas de la lista de referencias.', array['Cada fuente citada debe tener su referencia correspondiente','La lista puede contener solo fuentes no citadas','Las citas textuales no requieren referencia'], 1),

      ('Fuentes digitales y formato de citas', 1, 'Una página web no muestra fecha de publicación. ¿Qué se indica?', 'Para una fuente sin fecha se usa “s. f.” en español.', array['s. f.','2026 por ser el año de consulta','Se inventa una fecha aproximada'], 1),
      ('Fuentes digitales y formato de citas', 2, '¿Cuándo se incluye una fecha de recuperación?', 'Se incluye cuando el contenido está diseñado para cambiar con el tiempo y no es archivado; no suele hacer falta para páginas estables.', array['Cuando el contenido cambia con el tiempo','En toda referencia que incluya una URL','Nunca, incluso para contenido cambiante'], 1),
      ('Fuentes digitales y formato de citas', 3, 'Si el autor de la página y el nombre del sitio son iguales, ¿qué se hace?', 'Se omite el nombre del sitio para evitar repetir el mismo autor en la referencia.', array['Se omite el nombre del sitio duplicado','Se escribe dos veces para completar la referencia','Se reemplaza por la URL'], 1),
      ('Fuentes digitales y formato de citas', 4, '¿Qué datos básicos se usan para referenciar una página web?', 'Incluye autor, fecha, título de la página, nombre del sitio si es distinto del autor y URL.', array['Autor, fecha, título, sitio si aplica y URL','Solo URL y fecha de consulta','Título del sitio y número de página'], 1),
      ('Fuentes digitales y formato de citas', 5, '¿Qué se debe hacer con una URL estable en una referencia?', 'Incluye la URL directamente; “Recuperado de” no se antepone de manera rutinaria.', array['Incluir la URL directamente','Escribir siempre “Recuperado de” antes de la URL','Sustituirla por una búsqueda en Google'], 1),
      ('Fuentes digitales y formato de citas', 6, '¿Cómo se presenta una organización como autora?', 'Se escribe el nombre completo de la organización como autor corporativo en la referencia.', array['Como autor corporativo con su nombre completo','Se elimina el autor y se empieza por la URL','Se reemplaza por el nombre del país'], 1)
    ) as q(module_title, position, prompt, explanation, options, correct_position)
  loop
    select id into module_id from public.modules where title = item.module_title limit 1;
    insert into public.questions (module_id, prompt, explanation, position)
    values (module_id, item.prompt, item.explanation, item.position)
    on conflict (module_id, position) do update
      set prompt = excluded.prompt, explanation = excluded.explanation
    returning id into question_id;

    for choice_index in 1..array_length(item.options, 1) loop
      insert into public.question_options (question_id, option_text, is_correct, position)
      values (question_id, item.options[choice_index], choice_index = item.correct_position, choice_index)
      on conflict (question_id, position) do update
        set option_text = excluded.option_text, is_correct = excluded.is_correct;
    end loop;
  end loop;

  -- Recursos visibles para los estudiantes (HU-10).
  insert into public.learning_resources (title, description, url, category, position, published, created_by) values
    ('Biblioteca virtual UNIAJC', 'Consulta el catálogo y las colecciones académicas de la institución.', 'https://www.uniajc.edu.co/', 'Biblioteca virtual', 1, true, admin_id),
    ('Google Académico', 'Busca artículos, libros, tesis y citas académicas.', 'https://scholar.google.com/', 'Búsqueda académica', 2, true, admin_id),
    ('Zotero', 'Organiza referencias y genera citas bibliográficas.', 'https://www.zotero.org/', 'Gestor bibliográfico', 3, true, admin_id)
  on conflict do nothing;
end $$;
