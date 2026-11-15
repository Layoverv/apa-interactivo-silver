<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">RECURSOS ACADÉMICOS</p>
    <h1 class="h2">Consulta fuentes confiables</h1>
    <p class="text-body-secondary mb-0">Busca información académica y organiza tus referencias con estas herramientas.</p>
  </div>
</section>
<section class="container py-4">
  <?php if (!$recursos): ?>
    <p class="text-body-secondary">No hay recursos publicados todavía.</p>
  <?php endif; ?>
  <div class="row g-3">
    <?php foreach ($recursos as $r): ?>
      <div class="col-md-6 col-xl-3">
        <article class="card tarjeta h-100"><div class="card-body d-flex flex-column">
          <span class="badge text-bg-light align-self-start"><?= e($r['tipo']) ?></span>
          <h2 class="h5 mt-2"><?= e($r['titulo']) ?></h2>
          <p class="text-body-secondary small"><?= e($r['descripcion']) ?></p>
          <a class="btn btn-outline-primary mt-auto align-self-start" href="<?= e($r['url']) ?>" target="_blank" rel="noopener noreferrer">
            Abrir recurso <span aria-hidden="true">↗</span><span class="visually-hidden"> (se abre en una pestaña nueva)</span>
          </a>
        </div></article>
      </div>
    <?php endforeach; ?>
  </div>
</section>
