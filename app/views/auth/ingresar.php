<section class="container py-5 contenedor-formulario">
  <div class="card tarjeta">
    <div class="card-body p-4 p-md-5">
      <p class="eyebrow">TU ESPACIO DE APRENDIZAJE</p>
      <h1 class="h3">Iniciar sesión</h1>
      <p class="text-body-secondary">Ingresa para continuar con tus actividades.</p>
      <form method="post" action="<?= e(url('/ingresar')) ?>">
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label class="form-label" for="correo">Correo electrónico</label>
          <input class="form-control" id="correo" name="correo" type="email" autocomplete="email" required value="<?= viejo($formulario, 'correo') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label" for="clave">Contraseña</label>
          <input class="form-control" id="clave" name="clave" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Iniciar sesión</button>
      </form>
      <p class="text-center mt-4 mb-0">¿No tienes cuenta? <a href="<?= e(url('/registro')) ?>">Regístrate</a></p>
    </div>
  </div>
</section>
