<section class="encabezado-pagina">
  <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <p class="eyebrow">ADMINISTRACIÓN</p>
      <h1 class="h2 mb-0">Usuarios</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/usuarios/nuevo')) ?>">Crear usuario</a>
  </div>
</section>
<section class="container py-4">
  <form class="row g-2 mb-3" method="get" action="<?= e(url('/admin/usuarios')) ?>" role="search">
    <div class="col-md-6"><label class="visually-hidden" for="q">Buscar</label><input class="form-control" id="q" name="q" type="search" placeholder="Buscar por nombre o correo" value="<?= e($buscar) ?>"></div>
    <div class="col-md-4"><label class="visually-hidden" for="rol">Rol</label>
      <select class="form-select" id="rol" name="rol">
        <option value="">Todos los roles</option>
        <?php foreach ($roles as $r): ?><option value="<?= e($r['clave']) ?>" <?= $rol === $r['clave'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Buscar</button></div>
  </form>
  <div class="card tarjeta"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Último acceso</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= e($u['nombre']) ?></td>
          <td><?= e($u['correo']) ?></td>
          <td><?= e($u['rol_nombre']) ?></td>
          <td><?= (int) $u['activo'] ? '<span class="text-success">● Activo</span>' : '<span class="text-body-secondary">○ Inactivo</span>' ?></td>
          <td><?= e(fecha_corta($u['ultimo_acceso'])) ?></td>
          <td><a href="<?= e(url('/admin/usuarios/' . $u['id'])) ?>">Editar</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$usuarios): ?><tr><td colspan="6" class="text-body-secondary">No hay usuarios con ese criterio.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</section>
