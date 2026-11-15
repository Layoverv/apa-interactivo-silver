<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">MÓDULO 3 · EVALUACIÓN</p>
    <h1 class="h2"><?= e($evaluacion['titulo'] ?? 'Evaluación') ?></h1>
    <p class="text-body-secondary mb-0">Mide tu dominio de las normas APA con tareas de citación y referenciación.</p>
  </div>
</section>
<section class="container py-4">
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card tarjeta"><div class="card-body p-4">
        <h2 class="h5">Antes de empezar</h2>
        <ul>
          <li>Responderás <strong><?= (int) ($evaluacion['num_preguntas'] ?? 10) ?> preguntas</strong> tomadas al azar de todos los temas.</li>
          <li>No verás si acertaste hasta enviar la evaluación.</li>
          <li>Debes responder todas las preguntas para poder enviarla.</li>
          <li>Al terminar verás tu puntaje, la respuesta correcta de cada pregunta y los temas que debes reforzar.</li>
          <li>Puedes presentar la evaluación varias veces; cada intento se guarda por separado.</li>
        </ul>
        <?php if (!$evaluacion || $disponibles < (int) $evaluacion['num_preguntas']): ?>
          <p class="alert alert-warning mb-0">La evaluación no está disponible por ahora: el banco no tiene suficientes ejercicios activos.</p>
        <?php elseif ($enCurso): ?>
          <a class="btn btn-primary" href="<?= e(url('/evaluacion/' . $enCurso['id'])) ?>">Continuar la evaluación sin enviar</a>
        <?php else: ?>
          <form method="post" action="<?= e(url('/evaluacion/iniciar')) ?>">
            <?= csrf_campo() ?>
            <button class="btn btn-primary btn-lg" type="submit">Presentar evaluación</button>
          </form>
        <?php endif; ?>
      </div></div>
    </div>
    <div class="col-lg-5">
      <div class="card tarjeta"><div class="card-body p-4">
        <h2 class="h5">Tus intentos recientes</h2>
        <?php if (!$historial): ?>
          <p class="text-body-secondary mb-0">Aún no has presentado la evaluación.</p>
        <?php else: ?>
          <ul class="list-unstyled mb-3">
            <?php foreach ($historial as $h): ?>
              <li class="d-flex justify-content-between border-bottom py-2">
                <a href="<?= e(url('/evaluacion/' . $h['id'] . '/resultado')) ?>"><?= e(fecha_corta($h['finalizado_en'])) ?></a>
                <strong><?= e(porcentaje($h['puntaje'])) ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= e(url('/progreso')) ?>">Ver todo mi progreso</a>
        <?php endif; ?>
      </div></div>
    </div>
  </div>
</section>
