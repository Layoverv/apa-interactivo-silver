<article class="container py-4 contenedor-lectura">
  <nav aria-label="Ruta de navegación">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= e(url('/temas')) ?>">Contenido APA</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= e($tema['titulo']) ?></li>
    </ol>
  </nav>
  <?php if (!(int) $tema['publicado']): ?>
    <p class="alert alert-warning">Vista previa: este tema está oculto para los estudiantes.</p>
  <?php endif; ?>
  <p class="eyebrow">TEMA</p>
  <h1 class="h2"><?= e($tema['titulo']) ?></h1>
  <p class="lead text-body-secondary"><?= e($tema['resumen']) ?></p>

  <div class="contenido-tema"><?= formato_basico($tema['contenido']) ?></div>

  <?php if ($ejemplos): ?>
    <section class="mt-5" aria-labelledby="titulo-ejemplos">
      <h2 class="h4" id="titulo-ejemplos">Ejemplos para reconocer errores</h2>
      <?php foreach ($ejemplos as $x): $ok = (int) $x['es_correcto'] === 1; ?>
        <div class="ejemplo <?= $ok ? 'ejemplo-correcto' : 'ejemplo-incorrecto' ?>">
          <p class="ejemplo-etiqueta"><span aria-hidden="true"><?= $ok ? '✓' : '✗' ?></span> <?= $ok ? 'Correcto' : 'Incorrecto' ?></p>
          <div class="ejemplo-texto"><?= formato_basico($x['texto']) ?></div>
          <p class="ejemplo-explicacion mb-0"><?= formato_en_linea($x['explicacion']) ?></p>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <div class="d-flex flex-wrap justify-content-between gap-2 mt-5 pt-4 border-top">
    <div>
      <?php if ($anterior): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('/temas/' . $anterior['id'])) ?>">← <?= e($anterior['titulo']) ?></a>
      <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php if ($esEstudiante): ?>
        <a class="btn btn-primary" href="<?= e(url('/practica', ['tema' => $tema['id']])) ?>">Practicar este tema</a>
      <?php endif; ?>
      <?php if ($siguiente): ?>
        <a class="btn btn-outline-primary" href="<?= e(url('/temas/' . $siguiente['id'])) ?>">Siguiente tema: <?= e($siguiente['titulo']) ?> →</a>
      <?php elseif ($esEstudiante): ?>
        <a class="btn btn-outline-primary" href="<?= e(url('/evaluacion')) ?>">Ir a la evaluación →</a>
      <?php endif; ?>
    </div>
  </div>
</article>
