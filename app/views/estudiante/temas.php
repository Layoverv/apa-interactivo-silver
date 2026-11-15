<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">MÓDULO 1 · CONTENIDO APA 7.ª EDICIÓN</p>
    <h1 class="h2">Temas para estudiar</h1>
    <p class="text-body-secondary mb-0">Revisa la explicación y los ejemplos de cada tema, en este orden, y luego practica.</p>
  </div>
</section>
<section class="container py-4">
  <?php if (!$temas): ?>
    <p class="text-body-secondary">Todavía no hay temas publicados.</p>
  <?php endif; ?>
  <ol class="list-unstyled row g-3">
    <?php foreach ($temas as $i => $t): ?>
      <li class="col-md-6">
        <a class="card tarjeta tarjeta-enlace h-100 text-decoration-none" href="<?= e(url('/temas/' . $t['id'])) ?>">
          <div class="card-body d-flex gap-3">
            <span class="numero-tema" aria-hidden="true"><?= $i + 1 ?></span>
            <div>
              <h2 class="h5 mb-1"><?= e($t['titulo']) ?></h2>
              <p class="text-body-secondary small mb-0"><?= e($t['resumen']) ?></p>
            </div>
          </div>
        </a>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
