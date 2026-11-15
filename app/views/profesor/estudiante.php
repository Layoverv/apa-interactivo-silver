<section class="encabezado-pagina">
  <div class="container">
    <nav aria-label="Ruta de navegación"><ol class="breadcrumb small mb-2">
      <li class="breadcrumb-item"><a href="<?= e(url('/profesor')) ?>">Mis grupos</a></li>
      <li class="breadcrumb-item"><a href="<?= e(url('/profesor/grupos/' . $grupo['id'])) ?>"><?= e($grupo['nombre']) ?></a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= e($estudiante['nombre']) ?></li>
    </ol></nav>
    <h1 class="h2 mb-0"><?= e($estudiante['nombre']) ?></h1>
    <p class="text-body-secondary mb-0"><?= e($estudiante['correo']) ?></p>
  </div>
</section>
<section class="container py-4">
  <div class="row g-4">
    <div class="col-lg-7">
      <section class="card tarjeta h-100"><div class="card-body p-4">
        <h2 class="h5">Evolución de la tasa de éxito</h2>
        <?= $grafica !== '' ? $grafica : '<p class="text-body-secondary mb-0">El estudiante no ha presentado evaluaciones.</p>' ?>
      </div></section>
    </div>
    <div class="col-lg-5">
      <section class="card tarjeta h-100"><div class="card-body p-4">
        <h2 class="h5">Avance por tema</h2>
        <table class="table table-sm mb-2">
          <thead><tr><th scope="col">Tema</th><th scope="col">Practicados</th><th scope="col">Aciertos en evaluación</th></tr></thead>
          <tbody>
          <?php foreach ($avance as $a): $r = (int) $a['evaluacion_respondidas']; ?>
            <tr><td><?= e($a['titulo']) ?></td><td><?= (int) $a['practicados'] ?> / <?= (int) $a['ejercicios_activos'] ?></td><td><?= e(porcentaje($r ? (int) $a['evaluacion_correctas'] * 100 / $r : null)) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <p class="small text-body-secondary mb-0">Práctica: <?= (int) $practica['correctas'] ?> correctas de <?= (int) $practica['respondidas'] ?> respondidas.</p>
      </div></section>
    </div>
  </div>
  <section class="card tarjeta mt-4"><div class="card-body p-4">
    <h2 class="h5">Intentos de evaluación</h2>
    <?php if (!$historial): ?>
      <p class="text-body-secondary mb-0">Sin intentos finalizados.</p>
    <?php else: ?>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th scope="col">Fecha</th><th scope="col">Correctas</th><th scope="col">Tasa de éxito</th></tr></thead>
        <tbody>
        <?php foreach ($historial as $h): ?>
          <tr><td><?= e(fecha_corta($h['finalizado_en'])) ?></td><td><?= (int) $h['correctas'] ?> de <?= (int) $h['total_preguntas'] ?></td><td><strong><?= e(porcentaje($h['puntaje'])) ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div></section>
</section>
