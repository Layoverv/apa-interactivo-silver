<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">GESTIÓN DE CONTENIDOS</p>
    <h1 class="h2 mb-0">Recursos recomendados</h1>
    <p class="text-body-secondary mb-0">Enlaces de la sección Recursos (biblioteca virtual, buscadores académicos y gestores bibliográficos).</p>
  </div>
</section>
<section class="container py-4">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php foreach ($recursos as $r): ?>
        <details class="card tarjeta mb-2">
          <summary class="card-body py-3"><strong><?= e($r['titulo']) ?></strong> <span class="small text-body-secondary">· <?= e($r['tipo']) ?> · <?= (int) $r['publicado'] ? 'Publicado' : 'Oculto' ?></span></summary>
          <form class="card-body pt-0" method="post" action="<?= e(url('/gestion/recursos/' . $r['id'])) ?>">
            <?= csrf_campo() ?>
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label small" for="t-<?= (int) $r['id'] ?>">Título</label><input class="form-control" id="t-<?= (int) $r['id'] ?>" name="titulo" required value="<?= e($r['titulo']) ?>"></div>
              <div class="col-md-6"><label class="form-label small" for="ti-<?= (int) $r['id'] ?>">Tipo</label><input class="form-control" id="ti-<?= (int) $r['id'] ?>" name="tipo" value="<?= e($r['tipo']) ?>"></div>
              <div class="col-12"><label class="form-label small" for="u-<?= (int) $r['id'] ?>">Enlace</label><input class="form-control" id="u-<?= (int) $r['id'] ?>" name="url" type="url" required value="<?= e($r['url']) ?>"></div>
              <div class="col-12"><label class="form-label small" for="d-<?= (int) $r['id'] ?>">Descripción breve</label><input class="form-control" id="d-<?= (int) $r['id'] ?>" name="descripcion" required maxlength="255" value="<?= e($r['descripcion']) ?>"></div>
              <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" id="p-<?= (int) $r['id'] ?>" name="publicado" value="1" <?= (int) $r['publicado'] ? 'checked' : '' ?>><label class="form-check-label" for="p-<?= (int) $r['id'] ?>">Publicado</label></div>
            </div>
            <button class="btn btn-sm btn-primary mt-2" type="submit">Guardar</button>
          </form>
        </details>
      <?php endforeach; ?>
    </div>
    <div class="col-lg-4">
      <form class="card tarjeta" method="post" action="<?= e(url('/gestion/recursos')) ?>">
        <div class="card-body p-4">
          <h2 class="h5">Agregar recurso</h2>
          <?= csrf_campo() ?>
          <div class="mb-2"><label class="form-label small" for="titulo">Título</label><input class="form-control" id="titulo" name="titulo" required value="<?= viejo($formulario, 'titulo') ?>"></div>
          <div class="mb-2"><label class="form-label small" for="tipo">Tipo</label><input class="form-control" id="tipo" name="tipo" placeholder="Gestor bibliográfico" value="<?= viejo($formulario, 'tipo') ?>"></div>
          <div class="mb-2"><label class="form-label small" for="url">Enlace</label><input class="form-control" id="url" name="url" type="url" required placeholder="https://" value="<?= viejo($formulario, 'url') ?>"></div>
          <div class="mb-2"><label class="form-label small" for="descripcion">Descripción breve</label><input class="form-control" id="descripcion" name="descripcion" required maxlength="255" value="<?= viejo($formulario, 'descripcion') ?>"></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="publicado" name="publicado" value="1" checked><label class="form-check-label" for="publicado">Publicado</label></div>
          <button class="btn btn-primary" type="submit">Agregar</button>
        </div>
      </form>
    </div>
  </div>
</section>
