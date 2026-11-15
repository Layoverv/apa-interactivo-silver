<?php $reforzar = array_filter($porTema, static fn ($t) => $t['reforzar']); ?>
<section class="container py-4 contenedor-actividad">
  <p class="eyebrow">RESULTADO DE LA EVALUACIÓN · <?= e(fecha_corta($intento['finalizado_en'])) ?></p>
  <h1 class="h3">Tu resultado</h1>
  <div class="row g-3 my-2">
    <div class="col-sm-4"><div class="card tarjeta h-100"><div class="card-body">
      <p class="etiqueta-dato">Puntaje</p>
      <p class="cifra mb-0"><?= (int) $intento['correctas'] ?> / <?= (int) $intento['total_preguntas'] ?></p>
      <p class="small text-body-secondary mb-0">respuestas correctas</p>
    </div></div></div>
    <div class="col-sm-4"><div class="card tarjeta h-100"><div class="card-body">
      <p class="etiqueta-dato">Tasa de éxito</p>
      <p class="cifra mb-0"><?= e(porcentaje($intento['puntaje'])) ?></p>
    </div></div></div>
    <div class="col-sm-4"><div class="card tarjeta h-100"><div class="card-body">
      <p class="etiqueta-dato">Meta del 70 %</p>
      <p class="cifra mb-0 <?= $cumple ? 'text-success' : 'text-danger' ?>"><span aria-hidden="true"><?= $cumple ? '✓' : '✗' ?></span> <?= $cumple ? 'Alcanzada' : 'No alcanzada' ?></p>
    </div></div></div>
  </div>

  <section class="card tarjeta my-4" aria-labelledby="titulo-temas"><div class="card-body p-4">
    <h2 class="h5" id="titulo-temas">Aciertos por tema</h2>
    <div class="table-responsive">
      <table class="table align-middle mb-2">
        <thead><tr><th scope="col">Tema</th><th scope="col">Aciertos</th><th scope="col">Porcentaje</th><th scope="col">Recomendación</th></tr></thead>
        <tbody>
        <?php foreach ($porTema as $t): ?>
          <tr>
            <td><?= e($t['tema']) ?></td>
            <td><?= $t['correctas'] ?> de <?= $t['total'] ?></td>
            <td><?= e(porcentaje($t['porcentaje'])) ?></td>
            <td><?php if ($t['reforzar']): ?><a href="<?= e(url('/temas/' . $t['tema_id'])) ?>"><span aria-hidden="true">!</span> Reforzar este tema</a><?php else: ?><span class="text-success"><span aria-hidden="true">✓</span> Bien</span><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($reforzar): ?>
      <p class="mb-0">Te sugerimos repasar y practicar: <?= e(implode(', ', array_column($reforzar, 'tema'))) ?>.</p>
    <?php else: ?>
      <p class="mb-0 text-success">Alcanzaste al menos el 70 % de aciertos en todos los temas evaluados.</p>
    <?php endif; ?>
  </div></section>

  <h2 class="h5">Revisión de cada pregunta</h2>
  <ol class="list-unstyled">
    <?php foreach ($detalle as $f): $ok = (int) $f['es_correcta'] === 1; ?>
      <li class="card tarjeta mb-3 revision <?= $ok ? 'revision-correcta' : 'revision-incorrecta' ?>"><div class="card-body p-4">
        <p class="small text-body-secondary mb-1">Pregunta <?= (int) $f['orden'] ?> · <?= e($f['tema']) ?> · <?= e($f['tipo_error']) ?></p>
        <p class="fw-semibold"><?= e($f['enunciado']) ?></p>
        <p class="mb-1"><span class="<?= $ok ? 'text-success' : 'text-danger' ?> fw-semibold"><span aria-hidden="true"><?= $ok ? '✓' : '✗' ?></span> <?= $ok ? 'Correcta' : 'Incorrecta' ?>.</span> Tu respuesta: <?= e($f['respuesta_dada']) ?></p>
        <?php if (!$ok): ?><p class="mb-1">Respuesta correcta: <strong><?= e($f['respuesta_correcta']) ?></strong></p><?php endif; ?>
        <p class="mb-0 text-body-secondary"><?= e($f['explicacion']) ?></p>
      </div></li>
    <?php endforeach; ?>
  </ol>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(url('/progreso')) ?>">Ver mi progreso</a>
    <a class="btn btn-outline-primary" href="<?= e(url('/evaluacion')) ?>">Volver a la evaluación</a>
  </div>
</section>
