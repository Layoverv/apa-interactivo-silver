<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">PANEL DEL ESTUDIANTE</p>
    <h1 class="h2">Hola, <?= e(explode(' ', $usuario['nombre'])[0]) ?></h1>
    <p class="text-body-secondary mb-0">Sigue el recorrido: estudia cada tema, practica con retroalimentación y presenta la evaluación final.</p>
  </div>
</section>

<section class="container py-4">
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card tarjeta h-100"><div class="card-body d-flex flex-column">
        <h2 class="h5">Evaluación final</h2>
        <?php if ($enCurso): ?>
          <p class="text-body-secondary">Tienes una evaluación sin enviar.</p>
          <a class="btn btn-primary mt-auto align-self-start" href="<?= e(url('/evaluacion/' . $enCurso['id'])) ?>">Continuar evaluación</a>
        <?php else: ?>
          <p class="text-body-secondary"><?= e($evaluacion['descripcion'] ?? 'Diez preguntas al azar de todos los temas.') ?></p>
          <a class="btn btn-primary mt-auto align-self-start" href="<?= e(url('/evaluacion')) ?>">Ir a la evaluación</a>
        <?php endif; ?>
      </div></div>
    </div>
    <div class="col-md-6">
      <div class="card tarjeta h-100"><div class="card-body d-flex flex-column">
        <h2 class="h5">Tu último resultado</h2>
        <?php if ($ultima): ?>
          <p class="cifra mb-1"><?= e(porcentaje($ultima['puntaje'])) ?></p>
          <p class="text-body-secondary"><?= (int) $ultima['correctas'] ?> de <?= (int) $ultima['total_preguntas'] ?> correctas · <?= e(fecha_corta($ultima['finalizado_en'])) ?></p>
          <a class="btn btn-outline-primary mt-auto align-self-start" href="<?= e(url('/progreso')) ?>">Ver mi progreso</a>
        <?php else: ?>
          <p class="text-body-secondary">Aún no has presentado la evaluación. Te recomendamos estudiar y practicar primero.</p>
        <?php endif; ?>
      </div></div>
    </div>
  </div>

  <h2 class="h4 mb-3">Temas</h2>
  <?php if (!$temas): ?>
    <p class="text-body-secondary">Todavía no hay temas publicados.</p>
  <?php endif; ?>
  <div class="row g-3">
    <?php foreach ($temas as $i => $t):
        $a = $avance[(int) $t['id']] ?? null;
        $practicados = (int) ($a['practicados'] ?? 0);
        $total = max(1, (int) $t['ejercicios_activos']);
        $pct = (int) round(min(100, $practicados * 100 / $total)); ?>
      <div class="col-md-6 col-xl-4">
        <article class="card tarjeta h-100"><div class="card-body d-flex flex-column">
          <span class="badge text-bg-light align-self-start">TEMA <?= $i + 1 ?></span>
          <h3 class="h5 mt-2"><?= e($t['titulo']) ?></h3>
          <p class="text-body-secondary small"><?= e($t['resumen']) ?></p>
          <div class="mt-auto">
            <div class="d-flex justify-content-between small mb-1"><span>Ejercicios practicados</span><span><?= $practicados ?> de <?= (int) $t['ejercicios_activos'] ?></span></div>
            <progress class="barra-avance" max="100" value="<?= $pct ?>" aria-label="Avance en <?= e($t['titulo']) ?>"><?= $pct ?> %</progress>
            <div class="d-flex flex-wrap gap-2 mt-3">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/temas/' . $t['id'])) ?>">Estudiar</a>
              <a class="btn btn-sm btn-primary" href="<?= e(url('/practica', ['tema' => $t['id']])) ?>">Practicar</a>
            </div>
          </div>
        </div></article>
      </div>
    <?php endforeach; ?>
  </div>
</section>
