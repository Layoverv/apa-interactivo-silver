<section class="container py-5 contenedor-formulario">
  <div class="card tarjeta">
    <div class="card-body p-4 p-md-5">
      <h1 class="h3">Cambiar contraseña</h1>
      <?php if ($obligatorio): ?>
        <p class="alert alert-info">El administrador te asignó una contraseña temporal. Elige una nueva para continuar.</p>
      <?php endif; ?>
      <form method="post" action="<?= e(url('/cambiar-clave')) ?>">
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label class="form-label" for="clave_actual"><?= $obligatorio ? 'Contraseña temporal' : 'Contraseña actual' ?></label>
          <input class="form-control" id="clave_actual" name="clave_actual" type="password" autocomplete="current-password" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="clave">Nueva contraseña</label>
          <input class="form-control" id="clave" name="clave" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
          <div class="form-text">Mínimo 8 caracteres.</div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="confirmacion">Confirmar nueva contraseña</label>
          <input class="form-control" id="confirmacion" name="confirmacion" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
      </form>
    </div>
  </div>
</section>
