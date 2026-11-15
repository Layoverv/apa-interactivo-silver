<?php
use App\Core\Auth;
use App\Core\Sesion;
use App\Services\Permisos;

$usuario = Auth::usuario();
$menu = $usuario && $usuario['acepto_politica_en'] !== null && !(int) $usuario['debe_cambiar_clave'] ? Permisos::menu($usuario['rol']) : [];
$rutaActual = ruta_actual();
$flash = Sesion::tomarFlash();
$iconos = ['success' => '✓', 'danger' => '✕', 'warning' => '!', 'info' => 'i'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102a43">
  <title><?= e($titulo ?? 'APA Interactivo') ?> · APA Interactivo</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/icono.svg')) ?>">
  <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<a class="visually-hidden-focusable skip-link" href="#contenido">Saltar al contenido</a>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" aria-label="Navegación principal">
  <div class="container">
    <a class="navbar-brand marca" href="<?= e(url('/')) ?>"><span>APA</span> Interactivo</a>
    <?php if ($usuario): ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menu">
        <ul class="navbar-nav me-auto">
          <?php foreach ($menu as [$ruta, $texto]):
              $activo = $ruta === '/' ? $rutaActual === '/' : ($rutaActual === $ruta || str_starts_with($rutaActual, $ruta . '/'));
              if ($ruta === '/gestion' && str_starts_with($rutaActual, '/gestion/') && !str_starts_with($rutaActual, '/gestion/temas')) { $activo = false; } ?>
            <li class="nav-item"><a class="nav-link<?= $activo ? ' active' : '' ?>" <?= $activo ? 'aria-current="page"' : '' ?> href="<?= e(url($ruta)) ?>"><?= e($texto) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <div class="d-flex align-items-center gap-3 cuenta">
          <div class="text-lg-end small lh-sm">
            <div class="fw-semibold"><?= e($usuario['nombre']) ?></div>
            <div class="text-body-secondary"><?= e($usuario['rol_nombre']) ?></div>
          </div>
          <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/cambiar-clave')) ?>">Contraseña</a>
          <form method="post" action="<?= e(url('/salir')) ?>" class="m-0">
            <?= csrf_campo() ?>
            <button class="btn btn-sm btn-outline-primary" type="submit">Cerrar sesión</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/ingresar')) ?>">Iniciar sesión</a>
        <a class="btn btn-sm btn-primary" href="<?= e(url('/registro')) ?>">Crear cuenta</a>
      </div>
    <?php endif; ?>
  </div>
</nav>

<main id="contenido" tabindex="-1">
  <?php if ($flash): ?>
    <div class="container pt-3" role="status" aria-live="polite">
      <?php foreach ($flash as $m): ?>
        <div class="alert alert-<?= e($m['tipo']) ?> d-flex gap-2 align-items-start mb-2">
          <span class="alerta-icono" aria-hidden="true"><?= e($iconos[$m['tipo']] ?? 'i') ?></span>
          <span><?= e($m['mensaje']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?= $contenido ?>
</main>

<footer class="border-top mt-5 py-4 bg-white">
  <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 small text-body-secondary">
    <span>APA Interactivo · Prototipo académico · UNIAJC, Tecnología en Sistemas de Información</span>
    <a href="<?= e(url('/politica-datos')) ?>">Política de tratamiento de datos</a>
  </div>
</footer>
<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
