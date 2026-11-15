<section class="encabezado-pagina">
  <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <p class="eyebrow">GESTIÓN DE CONTENIDOS</p>
      <h1 class="h2 mb-0">Temas</h1>
      <p class="text-body-secondary mb-0">Los temas se muestran a los estudiantes en este orden. Los cambios se ven de inmediato.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/gestion/temas/nuevo')) ?>">Crear tema</a>
  </div>
</section>
<section class="container py-4">
  <div class="card tarjeta"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th scope="col">Orden</th><th scope="col">Tema</th><th scope="col">Estado</th><th scope="col">Ejemplos</th><th scope="col">Ejercicios activos</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead>
      <tbody>
      <?php foreach ($temas as $i => $t): ?>
        <tr>
          <td class="text-nowrap">
            <span class="me-2"><?= $i + 1 ?></span>
            <form method="post" action="<?= e(url('/gestion/temas/' . $t['id'] . '/mover')) ?>" class="d-inline">
              <?= csrf_campo() ?><input type="hidden" name="direccion" value="arriba">
              <button class="btn btn-sm btn-outline-secondary" type="submit" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Subir <?= e($t['titulo']) ?>">↑</button>
            </form>
            <form method="post" action="<?= e(url('/gestion/temas/' . $t['id'] . '/mover')) ?>" class="d-inline">
              <?= csrf_campo() ?><input type="hidden" name="direccion" value="abajo">
              <button class="btn btn-sm btn-outline-secondary" type="submit" <?= $i === count($temas) - 1 ? 'disabled' : '' ?> aria-label="Bajar <?= e($t['titulo']) ?>">↓</button>
            </form>
          </td>
          <td><strong><?= e($t['titulo']) ?></strong><br><span class="small text-body-secondary"><?= e($t['resumen']) ?></span></td>
          <td><?= (int) $t['publicado'] ? '<span class="text-success">● Publicado</span>' : '<span class="text-body-secondary">○ Oculto</span>' ?></td>
          <td><?= (int) $t['ejemplos'] ?></td>
          <td><?= (int) $t['ejercicios_activos'] ?><?= (int) $t['ejercicios_inactivos'] ? ' <span class="small text-body-secondary">(+' . (int) $t['ejercicios_inactivos'] . ' inactivos)</span>' : '' ?>
            <?php if ((int) $t['ejercicios_activos'] < 6): ?><span class="badge text-bg-warning" title="El documento pide al menos seis ejercicios por tema">menos de 6</span><?php endif; ?></td>
          <td class="text-nowrap">
            <a href="<?= e(url('/gestion/temas/' . $t['id'])) ?>">Editar</a> ·
            <a href="<?= e(url('/gestion/ejercicios', ['tema' => $t['id'], 'estado' => 'todos'])) ?>">Ejercicios</a> ·
            <a href="<?= e(url('/temas/' . $t['id'])) ?>">Vista previa</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
  <p class="small text-body-secondary mt-3">Total de ejercicios activos: <?= array_sum(array_map(static fn ($t) => (int) $t['ejercicios_activos'], $temas)) ?> (meta del proyecto: al menos 30, seis por tema).</p>
</section>
