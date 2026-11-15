<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">MI PROGRESO</p>
    <h1 class="h2">Tu avance de aprendizaje</h1>
    <p class="text-body-secondary mb-0">Consulta tus evaluaciones y prácticas para saber qué reforzar.<?php if ($grupos): ?> Grupo: <?= e(implode(', ', array_map(static fn ($g) => $g['nombre'] . ' (' . $g['periodo'] . ')', $grupos))) ?>.<?php endif; ?></p>
  </div>
</section>
<section class="container py-4">
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Evaluaciones</p><p class="cifra mb-0"><?= count($historial) ?></p></div></div></div>
    <div class="col-6 col-lg-3"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Tasa promedio</p><p class="cifra mb-0"><?= e(porcentaje($promedio)) ?></p></div></div></div>
    <div class="col-6 col-lg-3"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Mejor tasa</p><p class="cifra mb-0"><?= e(porcentaje($mejor)) ?></p></div></div></div>
    <div class="col-6 col-lg-3"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Práctica</p><p class="cifra mb-0"><?= (int) $practica['correctas'] ?> / <?= (int) $practica['respondidas'] ?></p><p class="small text-body-secondary mb-0">correctas en <?= (int) $practica['sesiones'] ?> sesiones</p></div></div></div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <section class="card tarjeta h-100" aria-labelledby="titulo-evolucion"><div class="card-body p-4">
        <h2 class="h5" id="titulo-evolucion">Evolución de la tasa de éxito</h2>
        <?php if ($grafica === ''): ?>
          <p class="text-body-secondary mb-0">La gráfica aparecerá cuando presentes tu primera evaluación.</p>
        <?php else: ?>
          <?= $grafica ?>
          <p class="small text-body-secondary mb-0">Cada punto es un intento, en orden. La línea punteada marca la meta del <?= (int) $meta ?> %.</p>
        <?php endif; ?>
      </div></section>
    </div>
    <div class="col-lg-5">
      <section class="card tarjeta h-100" aria-labelledby="titulo-avance"><div class="card-body p-4">
        <h2 class="h5" id="titulo-avance">Avance por tema</h2>
        <?php foreach ($avance as $a):
            $total = max(1, (int) $a['ejercicios_activos']);
            $pct = (int) round(min(100, (int) $a['practicados'] * 100 / $total));
            $evalR = (int) $a['evaluacion_respondidas']; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between small"><a href="<?= e(url('/temas/' . $a['id'])) ?>"><?= e($a['titulo']) ?></a><span><?= (int) $a['practicados'] ?> de <?= (int) $a['ejercicios_activos'] ?> practicados</span></div>
            <progress class="barra-avance" max="100" value="<?= $pct ?>" aria-label="Ejercicios practicados de <?= e($a['titulo']) ?>"><?= $pct ?> %</progress>
            <p class="small text-body-secondary mb-0">
              Práctica: <?= e(porcentaje((int) $a['practica_respondidas'] ? (int) $a['practica_correctas'] * 100 / (int) $a['practica_respondidas'] : null)) ?> de aciertos ·
              Evaluaciones: <?= e(porcentaje($evalR ? (int) $a['evaluacion_correctas'] * 100 / $evalR : null)) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div></section>
    </div>
  </div>

  <section class="card tarjeta mt-4" aria-labelledby="titulo-historial"><div class="card-body p-4">
    <h2 class="h5" id="titulo-historial">Historial de evaluaciones</h2>
    <?php if (!$historial): ?>
      <p class="text-body-secondary mb-0">Aún no has finalizado evaluaciones. <a href="<?= e(url('/evaluacion')) ?>">Presentar la evaluación</a></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th scope="col">Intento</th><th scope="col">Fecha</th><th scope="col">Correctas</th><th scope="col">Tasa de éxito</th><th scope="col">Detalle</th></tr></thead>
          <tbody>
          <?php foreach ($historial as $i => $h): ?>
            <tr>
              <td><?= count($historial) - $i ?></td>
              <td><?= e(fecha_corta($h['finalizado_en'])) ?></td>
              <td><?= (int) $h['correctas'] ?> de <?= (int) $h['total_preguntas'] ?></td>
              <td><strong><?= e(porcentaje($h['puntaje'])) ?></strong> <?= (float) $h['puntaje'] >= $meta ? '<span class="text-success" aria-label="meta alcanzada">✓</span>' : '' ?></td>
              <td><a href="<?= e(url('/evaluacion/' . $h['id'] . '/resultado')) ?>">Ver revisión</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div></section>
</section>
