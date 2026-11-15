<?php
$editando = $tema !== null;
$valor = static fn (string $campo) => e($formulario[$campo] ?? ($tema[$campo] ?? ''));
$publicado = isset($formulario['titulo']) ? (($formulario['publicado'] ?? '') === '1') : (bool) ($tema['publicado'] ?? false);
?>
<section class="container py-4">
  <nav aria-label="Ruta de navegación"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('/gestion')) ?>">Temas</a></li><li class="breadcrumb-item active" aria-current="page"><?= $editando ? e($tema['titulo']) : 'Nuevo tema' ?></li></ol></nav>
  <div class="row g-4">
    <div class="col-xl-7">
      <form class="card tarjeta" method="post" action="<?= e(url($editando ? '/gestion/temas/' . $tema['id'] : '/gestion/temas/nuevo')) ?>">
        <div class="card-body p-4">
          <h1 class="h4"><?= $editando ? 'Editar tema' : 'Crear tema' ?></h1>
          <?= csrf_campo() ?>
          <div class="mb-3"><label class="form-label" for="titulo">Título</label><input class="form-control" id="titulo" name="titulo" required maxlength="150" value="<?= $valor('titulo') ?>"></div>
          <div class="mb-3"><label class="form-label" for="resumen">Resumen (una línea)</label><input class="form-control" id="resumen" name="resumen" maxlength="255" value="<?= $valor('resumen') ?>"></div>
          <div class="mb-3">
            <label class="form-label" for="contenido">Explicación del tema</label>
            <textarea class="form-control font-monospace" id="contenido" name="contenido" rows="16" aria-describedby="ayuda-formato"><?= $valor('contenido') ?></textarea>
            <div id="ayuda-formato" class="form-text">Formato básico: deja una línea en blanco entre párrafos; <code>## </code> al inicio para un subtítulo; <code>- </code> para una lista; <code>**texto**</code> para negrita y <code>_texto_</code> para cursiva.</div>
          </div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="publicado" name="publicado" value="1" <?= $publicado ? 'checked' : '' ?>><label class="form-check-label" for="publicado">Publicado (visible para los estudiantes)</label></div>
          <p class="small text-body-secondary">Recuerda: el contenido debe basarse en el manual APA 7.ª edición y revisarlo el asesor antes de publicarlo (riesgo R3).</p>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit"><?= $editando ? 'Guardar tema' : 'Crear tema' ?></button>
            <?php if ($editando): ?><a class="btn btn-outline-secondary" href="<?= e(url('/temas/' . $tema['id'])) ?>">Vista previa</a><?php endif; ?>
          </div>
        </div>
      </form>
      <?php if ($editando): ?>
        <form method="post" action="<?= e(url('/gestion/temas/' . $tema['id'] . '/eliminar')) ?>" class="mt-3" data-confirmar="¿Eliminar este tema? Solo es posible si no tiene ejercicios.">
          <?= csrf_campo() ?><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar tema</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($editando): ?>
      <div class="col-xl-5" id="ejemplos">
        <div class="card tarjeta mb-3"><div class="card-body p-4">
          <h2 class="h5">Ejemplos (<?= count($ejemplos) ?>)</h2>
          <p class="small text-body-secondary">Cada tema necesita al menos un ejemplo correcto y uno incorrecto. En el incorrecto, explica el error y su corrección.</p>
          <?php foreach ($ejemplos as $x): ?>
            <details class="border rounded p-2 mb-2">
              <summary><?= (int) $x['es_correcto'] ? '✓ Correcto' : '✗ Incorrecto' ?>: <?= e(mb_strimwidth($x['texto'], 0, 70, '…')) ?></summary>
              <form method="post" action="<?= e(url('/gestion/ejemplos/' . $x['id'])) ?>" class="mt-2">
                <?= csrf_campo() ?>
                <select class="form-select form-select-sm mb-2" name="es_correcto" aria-label="Tipo de ejemplo"><option value="1" <?= (int) $x['es_correcto'] ? 'selected' : '' ?>>Correcto</option><option value="0" <?= (int) $x['es_correcto'] ? '' : 'selected' ?>>Incorrecto</option></select>
                <label class="form-label small" for="t-<?= (int) $x['id'] ?>">Texto</label>
                <textarea class="form-control form-control-sm mb-2" id="t-<?= (int) $x['id'] ?>" name="texto" rows="3" required><?= e($x['texto']) ?></textarea>
                <label class="form-label small" for="x-<?= (int) $x['id'] ?>">Explicación</label>
                <textarea class="form-control form-control-sm mb-2" id="x-<?= (int) $x['id'] ?>" name="explicacion" rows="3" required><?= e($x['explicacion']) ?></textarea>
                <button class="btn btn-sm btn-primary" type="submit">Guardar ejemplo</button>
              </form>
              <form method="post" action="<?= e(url('/gestion/ejemplos/' . $x['id'] . '/eliminar')) ?>" class="mt-2" data-confirmar="¿Eliminar este ejemplo?">
                <?= csrf_campo() ?><button class="btn btn-sm btn-link text-danger p-0" type="submit">Eliminar ejemplo</button>
              </form>
            </details>
          <?php endforeach; ?>
        </div></div>
        <form class="card tarjeta" method="post" action="<?= e(url('/gestion/temas/' . $tema['id'] . '/ejemplos')) ?>">
          <div class="card-body p-4">
            <h3 class="h6">Agregar ejemplo</h3>
            <?= csrf_campo() ?>
            <div class="mb-2"><label class="form-label small" for="es_correcto">Tipo</label><select class="form-select" id="es_correcto" name="es_correcto"><option value="1">Correcto</option><option value="0">Incorrecto</option></select></div>
            <div class="mb-2"><label class="form-label small" for="texto">Texto del ejemplo</label><textarea class="form-control" id="texto" name="texto" rows="3" required></textarea></div>
            <div class="mb-3"><label class="form-label small" for="explicacion">Explicación (en el incorrecto: error y corrección)</label><textarea class="form-control" id="explicacion" name="explicacion" rows="3" required></textarea></div>
            <button class="btn btn-outline-primary" type="submit">Agregar ejemplo</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
