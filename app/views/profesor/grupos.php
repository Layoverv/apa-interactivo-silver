<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">PANEL DEL PROFESOR</p>
    <h1 class="h2">Mis grupos</h1>
    <p class="text-body-secondary mb-0">Consulta los resultados y los errores frecuentes de tus estudiantes.</p>
  </div>
</section>
<section class="container py-4">
  <?php if (!$grupos): ?>
    <div class="alert alert-info">Todavía no tienes grupos asignados. El administrador crea los grupos y los asigna a cada profesor.</div>
  <?php endif; ?>
  <div class="row g-3">
    <?php foreach ($grupos as $g): ?>
      <div class="col-md-6 col-xl-4">
        <a class="card tarjeta tarjeta-enlace h-100 text-decoration-none" href="<?= e(url('/profesor/grupos/' . $g['id'])) ?>">
          <div class="card-body">
            <span class="badge <?= (int) $g['activo'] ? 'text-bg-light' : 'text-bg-secondary' ?>"><?= e($g['periodo']) ?><?= (int) $g['activo'] ? '' : ' · inactivo' ?></span>
            <h2 class="h5 mt-2"><?= e($g['nombre']) ?></h2>
            <p class="text-body-secondary small mb-0"><?= (int) $g['estudiantes'] ?> estudiantes · Profesor: <?= e($g['profesor'] ?? 'sin asignar') ?></p>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
