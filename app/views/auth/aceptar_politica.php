<section class="container py-5 contenedor-formulario">
  <div class="card tarjeta">
    <div class="card-body p-4 p-md-5">
      <h1 class="h3">Autorización para el tratamiento de datos</h1>
      <p>Antes de usar la aplicación necesitamos tu autorización, según la Ley 1581 de 2012.</p>
      <p><a href="<?= e(url('/politica-datos')) ?>" target="_blank" rel="noopener">Leer la política de tratamiento de datos (versión <?= e($version) ?>)</a></p>
      <form method="post" action="<?= e(url('/aceptar-politica')) ?>">
        <?= csrf_campo() ?>
        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="acepta_politica" name="acepta_politica" value="1" required>
          <label class="form-check-label" for="acepta_politica">He leído y acepto la política de tratamiento de datos personales.</label>
        </div>
        <button class="btn btn-primary w-100" type="submit">Aceptar y continuar</button>
      </form>
    </div>
  </div>
</section>
