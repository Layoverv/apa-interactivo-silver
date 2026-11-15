<section class="container py-5 contenedor-formulario">
  <div class="card tarjeta">
    <div class="card-body p-4 p-md-5">
      <p class="eyebrow">TU ESPACIO DE APRENDIZAJE</p>
      <h1 class="h3">Crear cuenta</h1>
      <p class="text-body-secondary">Tu cuenta tendrá el rol de estudiante. Los demás roles los asigna el administrador.</p>
      <form method="post" action="<?= e(url('/registro')) ?>">
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label class="form-label" for="nombre">Nombre completo</label>
          <input class="form-control" id="nombre" name="nombre" autocomplete="name" minlength="3" maxlength="120" required value="<?= viejo($formulario, 'nombre') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="correo">Correo electrónico</label>
          <input class="form-control" id="correo" name="correo" type="email" autocomplete="email" maxlength="190" required value="<?= viejo($formulario, 'correo') ?>">
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label" for="clave">Contraseña</label>
            <input class="form-control" id="clave" name="clave" type="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="ayuda-clave">
            <div id="ayuda-clave" class="form-text">Mínimo 8 caracteres.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="confirmacion">Confirmar contraseña</label>
            <input class="form-control" id="confirmacion" name="confirmacion" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
          </div>
        </div>
        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="acepta_politica" name="acepta_politica" value="1" required <?= ($formulario['acepta_politica'] ?? '') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="acepta_politica">
            He leído y acepto la <a href="<?= e(url('/politica-datos')) ?>" target="_blank" rel="noopener">política de tratamiento de datos personales</a> (Ley 1581 de 2012).
          </label>
        </div>
        <button class="btn btn-primary w-100" type="submit">Crear cuenta</button>
      </form>
      <p class="text-center mt-4 mb-0">¿Ya tienes cuenta? <a href="<?= e(url('/ingresar')) ?>">Inicia sesión</a></p>
    </div>
  </div>
</section>
