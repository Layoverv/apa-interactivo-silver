<section class="container py-4">
  <nav aria-label="Ruta de navegación"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('/admin/grupos')) ?>">Grupos</a></li><li class="breadcrumb-item active" aria-current="page"><?= e($grupo['nombre']) ?></li></ol></nav>
  <div class="row g-4">
    <div class="col-lg-4">
      <form class="card tarjeta" method="post" action="<?= e(url('/admin/grupos/' . $grupo['id'])) ?>">
        <div class="card-body p-4">
          <h1 class="h5">Datos del grupo</h1>
          <?= csrf_campo() ?>
          <div class="mb-3"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" required maxlength="120" value="<?= e($grupo['nombre']) ?>"></div>
          <div class="mb-3"><label class="form-label" for="periodo">Periodo</label><input class="form-control" id="periodo" name="periodo" required pattern="\d{4}-[12]" value="<?= e($grupo['periodo']) ?>"></div>
          <div class="mb-3"><label class="form-label" for="profesor_id">Profesor</label>
            <select class="form-select" id="profesor_id" name="profesor_id">
              <option value="">Sin asignar</option>
              <?php foreach ($profesores as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $grupo['profesor_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= (int) $grupo['activo'] ? 'checked' : '' ?>><label class="form-check-label" for="activo">Grupo activo</label></div>
          <button class="btn btn-primary" type="submit">Guardar</button>
          <a class="btn btn-link" href="<?= e(url('/profesor/grupos/' . $grupo['id'])) ?>">Ver resultados</a>
        </div>
      </form>
    </div>
    <div class="col-lg-8">
      <div class="card tarjeta mb-4"><div class="card-body p-4">
        <h2 class="h5">Estudiantes del grupo (<?= count($miembros) ?>)</h2>
        <?php if (!$miembros): ?><p class="text-body-secondary">Aún no hay estudiantes en este grupo.</p><?php endif; ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($miembros as $m): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2 px-0">
              <span><?= e($m['nombre']) ?> <span class="small text-body-secondary"><?= e($m['correo']) ?></span><?= (int) $m['activo'] ? '' : ' <span class="badge text-bg-secondary">inactivo</span>' ?></span>
              <form method="post" action="<?= e(url('/admin/grupos/' . $grupo['id'] . '/estudiantes/' . $m['id'] . '/quitar')) ?>" data-confirmar="¿Retirar a este estudiante del grupo? Sus resultados se conservan.">
                <?= csrf_campo() ?><button class="btn btn-sm btn-outline-danger" type="submit">Quitar<span class="visually-hidden"> a <?= e($m['nombre']) ?></span></button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </div></div>
      <form class="card tarjeta" method="post" action="<?= e(url('/admin/grupos/' . $grupo['id'] . '/estudiantes')) ?>">
        <div class="card-body p-4">
          <h2 class="h5">Agregar estudiantes</h2>
          <?= csrf_campo() ?>
          <?php if (!$disponibles): ?>
            <p class="text-body-secondary mb-0">No hay más estudiantes activos para agregar.</p>
          <?php else: ?>
            <label class="form-label" for="estudiantes">Selecciona uno o varios (Ctrl o Cmd para elegir varios)</label>
            <select class="form-select mb-3" id="estudiantes" name="estudiantes[]" multiple size="8">
              <?php foreach ($disponibles as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['nombre'] . ' — ' . $d['correo']) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Agregar al grupo</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</section>
