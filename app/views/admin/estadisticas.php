<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">ADMINISTRACIÓN</p>
    <h1 class="h2">Estadísticas generales</h1>
    <p class="text-body-secondary mb-0">Fuente de los indicadores del objetivo específico 3: tasa de éxito en tareas de citación (meta ≥ <?= (int) $meta ?> %).</p>
  </div>
</section>
<section class="container py-4">
  <form class="card tarjeta mb-4" method="get" action="<?= e(url('/admin')) ?>">
    <div class="card-body row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="f-grupo">Grupo</label>
        <select class="form-select" id="f-grupo" name="grupo">
          <option value="">Todos los grupos</option>
          <?php foreach ($grupos as $g): ?>
            <option value="<?= (int) $g['id'] ?>" <?= $filtros['grupo'] === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['nombre'] . ' (' . $g['periodo'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-6 col-md-3"><label class="form-label" for="f-desde">Desde</label><input class="form-control" type="date" id="f-desde" name="desde" value="<?= e($filtros['desde']) ?>"></div>
      <div class="col-sm-6 col-md-3"><label class="form-label" for="f-hasta">Hasta</label><input class="form-control" type="date" id="f-hasta" name="hasta" value="<?= e($filtros['hasta']) ?>"></div>
      <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary w-100" type="submit">Filtrar</button></div>
    </div>
  </form>

  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card tarjeta h-100"><div class="card-body">
      <p class="etiqueta-dato">Usuarios activos</p>
      <p class="cifra mb-1"><?= array_sum(array_map(static fn ($u) => (int) $u['total'], $usuarios)) ?></p>
      <p class="small text-body-secondary mb-0"><?= e(implode(' · ', array_map(static fn ($u) => $u['nombre'] . ': ' . $u['total'], $usuarios))) ?></p>
    </div></div></div>
    <div class="col-md-4"><div class="card tarjeta h-100"><div class="card-body">
      <p class="etiqueta-dato">Evaluaciones presentadas</p>
      <p class="cifra mb-1"><?= $estadisticas['evaluaciones'] ?></p>
      <p class="small text-body-secondary mb-0">por <?= $estadisticas['estudiantes'] ?> estudiantes</p>
    </div></div></div>
    <div class="col-md-4"><div class="card tarjeta h-100"><div class="card-body">
      <p class="etiqueta-dato">Tasa de éxito promedio</p>
      <p class="cifra mb-1"><?= e(porcentaje($estadisticas['tasa_promedio'])) ?></p>
      <?php if ($estadisticas['tasa_promedio'] === null): ?>
        <p class="small text-body-secondary mb-0">Sin evaluaciones en el filtro elegido.</p>
      <?php else: ?>
        <p class="small fw-semibold mb-0 <?= $cumple ? 'text-success' : 'text-danger' ?>"><span aria-hidden="true"><?= $cumple ? '✓' : '✗' ?></span> <?= $cumple ? 'Alcanza' : 'No alcanza' ?> la meta del <?= (int) $meta ?> %</p>
      <?php endif; ?>
    </div></div></div>
  </div>

  <section class="card tarjeta"><div class="card-body p-4">
    <h2 class="h5">Porcentaje de error por tema</h2>
    <?= $grafica !== '' ? $grafica : '<p class="text-body-secondary mb-0">Aún no hay evaluaciones en el filtro elegido.</p>' ?>
  </div></section>
</section>
