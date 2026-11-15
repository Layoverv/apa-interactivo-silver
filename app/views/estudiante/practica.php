<?php
use App\Core\Csrf;

$datos = [
    'endpoint'   => url('/api/practica/' . $intento['id'] . '/responder'),
    'resumen'    => url('/practica/' . $intento['id'] . '/resumen'),
    'token'      => Csrf::token(),
    'ejercicios' => $ejercicios,
];
?>
<section class="container py-4 contenedor-actividad">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
      <p class="eyebrow mb-1">PRÁCTICA GUIADA · <?= e(mb_strtoupper(nivel_texto($intento['dificultad'] === null ? null : (int) $intento['dificultad']))) ?></p>
      <h1 class="h3 mb-0"><?= e($intento['tema']) ?></h1>
    </div>
    <p class="mb-0 text-body-secondary" id="contador" aria-live="polite"></p>
  </div>
  <progress class="barra-avance my-3" id="barra" max="<?= count($ejercicios) ?>" value="0" aria-label="Avance de la práctica"></progress>

  <noscript><p class="alert alert-warning">La práctica necesita JavaScript para mostrar la retroalimentación sin recargar la página.</p></noscript>

  <div id="practica" class="card tarjeta" data-practica>
    <div class="card-body p-4 p-md-5">
      <p class="text-body-secondary mb-0">Cargando ejercicios…</p>
    </div>
  </div>
  <div class="d-flex justify-content-between mt-3">
    <button class="btn btn-outline-secondary" type="button" id="anterior">← Anterior</button>
    <button class="btn btn-outline-primary" type="button" id="siguiente">Siguiente →</button>
  </div>
</section>
<script type="application/json" id="datos-practica"><?= json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(asset('js/practica.js')) ?>"></script>
