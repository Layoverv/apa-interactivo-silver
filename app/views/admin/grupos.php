<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">ADMINISTRACIÓN</p>
    <h1 class="h2 mb-0">Grupos</h1>
  </div>
</section>
<section class="container py-4">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card tarjeta"><div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th scope="col">Grupo</th><th scope="col">Periodo</th><th scope="col">Profesor</th><th scope="col">Estudiantes</th><th scope="col">Estado</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead>
          <tbody>
          <?php foreach ($grupos as $g): ?>
            <tr>
              <td><?= e($g['nombre']) ?></td><td><?= e($g['periodo']) ?></td><td><?= e($g['profesor'] ?? 'Sin asignar') ?></td>
              <td><?= (int) $g['estudiantes'] ?></td><td><?= (int) $g['activo'] ? 'Activo' : 'Inactivo' ?></td>
              <td><a href="<?= e(url('/admin/grupos/' . $g['id'])) ?>">Gestionar</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$grupos): ?><tr><td colspan="6" class="text-body-secondary">Aún no hay grupos.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div></div>
    </div>
    <div class="col-lg-4">
      <form class="card tarjeta" method="post" action="<?= e(url('/admin/grupos')) ?>">
        <div class="card-body p-4">
          <h2 class="h5">Crear grupo</h2>
          <?= csrf_campo() ?>
          <div class="mb-3"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" required maxlength="120" placeholder="Ej.: Metodología de la investigación G1" value="<?= viejo($formulario, 'nombre') ?>"></div>
          <div class="mb-3"><label class="form-label" for="periodo">Periodo</label><input class="form-control" id="periodo" name="periodo" required pattern="\d{4}-[12]" placeholder="2026-2" value="<?= viejo($formulario, 'periodo') ?>"></div>
          <div class="mb-3"><label class="form-label" for="profesor_id">Profesor</label>
            <select class="form-select" id="profesor_id" name="profesor_id">
              <option value="">Sin asignar</option>
              <?php foreach ($profesores as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['nombre']) ?></option><?php endforeach; ?>
            </select>
            <?php if (!$profesores): ?><p class="form-text">No hay usuarios con rol profesor. Asigna el rol desde Usuarios.</p><?php endif; ?>
          </div>
          <button class="btn btn-primary" type="submit">Crear grupo</button>
        </div>
      </form>
    </div>
  </div>
</section>
