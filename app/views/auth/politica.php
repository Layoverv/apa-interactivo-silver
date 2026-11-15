<section class="container py-5 contenedor-lectura">
  <p class="eyebrow">VERSIÓN <?= e($version) ?></p>
  <h1 class="h2">Política de tratamiento de datos personales</h1>
  <p class="text-body-secondary">APA Interactivo es un prototipo académico desarrollado como trabajo de grado del programa de Tecnología en Sistemas de Información de la Institución Universitaria Antonio José Camacho (UNIAJC).</p>

  <h2 class="h5 mt-4">Datos que se recolectan</h2>
  <p>Solo los necesarios para usar la aplicación: nombre, correo electrónico y contraseña (que se guarda cifrada mediante una función de <em>hash</em> y nunca en texto plano), además de las respuestas, puntajes e intentos que generes al practicar y presentar evaluaciones.</p>

  <h2 class="h5 mt-4">Finalidad</h2>
  <ul>
    <li>Crear tu cuenta y permitirte iniciar sesión.</li>
    <li>Guardar tu progreso y mostrarte tus resultados.</li>
    <li>Permitir que el profesor de tu grupo consulte tus resultados para orientar sus clases.</li>
    <li>Calcular estadísticas agregadas para validar el proyecto académico (tasa de éxito y usabilidad). Los resultados que se publiquen en el documento de grado se presentan de forma anónima.</li>
  </ul>

  <h2 class="h5 mt-4">Tus derechos</h2>
  <p>De acuerdo con la Ley 1581 de 2012 y el Decreto 1377 de 2013, puedes conocer, actualizar y rectificar tus datos, solicitar prueba de esta autorización, revocarla y pedir la supresión de tus datos cuando no exista un deber legal o académico de conservarlos.</p>

  <h2 class="h5 mt-4">Contacto</h2>
  <p>Para ejercer tus derechos escribe a <strong><?= e($contacto) ?></strong>.</p>

  <h2 class="h5 mt-4">Vigencia</h2>
  <p>Los datos se conservan mientras dure el proyecto académico (periodo 2026-2). El prototipo no tiene mantenimiento posterior al semestre.</p>
</section>
