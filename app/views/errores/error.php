<?php
$textos = [
    403 => ['No tienes permiso', 'Esta sección no está disponible para tu rol.'],
    404 => ['Página no encontrada', 'La página que buscas no existe o ya no está disponible.'],
    405 => ['Acción no permitida', 'La acción que intentaste no está permitida en esta dirección.'],
    419 => ['La página expiró', 'Por seguridad, recarga la página e inténtalo de nuevo.'],
    500 => ['Ocurrió un error', 'No fue posible completar la acción. Inténtalo de nuevo en unos minutos.'],
];
[$encabezado, $descripcion] = $textos[$estado] ?? ['Error', 'No fue posible completar la acción.'];
?>
<section class="container py-5 text-center contenedor-estrecho">
  <p class="eyebrow">ERROR <?= e($estado) ?></p>
  <h1 class="h2"><?= e($encabezado) ?></h1>
  <p class="text-body-secondary"><?= e($mensaje !== '' ? $mensaje : $descripcion) ?></p>
  <a class="btn btn-primary mt-2" href="<?= e(url('/')) ?>">Volver al inicio</a>
</section>
