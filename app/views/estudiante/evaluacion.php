<?php $total = count($ejercicios); ?>
<section class="container py-4 contenedor-actividad">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-2">
    <div>
      <p class="eyebrow mb-1">EVALUACIÓN · SIN RETROALIMENTACIÓN HASTA ENVIAR</p>
      <h1 class="h3 mb-0"><?= e($evaluacion['titulo'] ?? 'Evaluación') ?></h1>
    </div>
    <p class="mb-0 text-body-secondary" id="contador-evaluacion" aria-live="polite"><?= $total ?> preguntas</p>
  </div>
  <progress class="barra-avance my-3" id="barra-evaluacion" max="<?= $total ?>" value="0" aria-label="Preguntas respondidas"></progress>

  <nav class="mapa-preguntas mb-3" aria-label="Preguntas de la evaluación" id="mapa-preguntas">
    <?php foreach ($ejercicios as $i => $e): $falta = in_array((int) $e['id'], $faltantes, true); ?>
      <a href="#pregunta-<?= $i + 1 ?>" class="btn btn-sm <?= $falta ? 'btn-outline-danger' : 'btn-outline-secondary' ?>" data-ir="<?= $i ?>"><?= $i + 1 ?><?= $falta ? '<span class="visually-hidden"> (sin responder)</span>' : '' ?></a>
    <?php endforeach; ?>
  </nav>

  <form method="post" action="<?= e(url('/evaluacion/' . $intento['id'] . '/enviar')) ?>" id="formulario-evaluacion" data-total="<?= $total ?>" data-intento="<?= (int) $intento['id'] ?>" data-sesion="<?= e(url('/api/sesion')) ?>">
    <p class="alert alert-warning d-none" id="aviso-sesion" role="alert"></p>
    <?= csrf_campo() ?>
    <?php foreach ($ejercicios as $i => $e): $falta = in_array((int) $e['id'], $faltantes, true); ?>
      <fieldset class="card tarjeta pregunta mb-3<?= $falta ? ' pregunta-faltante' : '' ?>" id="pregunta-<?= $i + 1 ?>" data-indice="<?= $i ?>">
        <div class="card-body p-4">
          <p class="small text-body-secondary mb-2">Pregunta <?= $i + 1 ?> de <?= $total ?> · <?= e(tipo_ejercicio_texto($e['tipo'])) ?></p>
          <legend class="enunciado"><?= e($e['enunciado']) ?></legend>
          <?php if ($falta): ?><p class="text-danger small fw-semibold" role="alert">✗ Esta pregunta no tiene respuesta.</p><?php endif; ?>
          <?php foreach ($e['opciones'] as $j => $o): $marcada = (int) ($elegidas[$e['id']] ?? 0) === (int) $o['id']; ?>
            <div class="opcion">
              <input class="form-check-input" type="radio" name="respuestas[<?= (int) $e['id'] ?>]" id="r-<?= (int) $o['id'] ?>" value="<?= (int) $o['id'] ?>" <?= $marcada ? 'checked' : '' ?>>
              <label class="form-check-label" for="r-<?= (int) $o['id'] ?>"><b class="me-2"><?= letra_opcion($j) ?>.</b><?= e($o['texto']) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>
    <div class="d-flex flex-wrap justify-content-between gap-2" id="acciones-evaluacion">
      <button class="btn btn-outline-secondary d-none" type="button" id="eval-anterior">← Anterior</button>
      <button class="btn btn-outline-primary d-none" type="button" id="eval-siguiente">Siguiente →</button>
      <button class="btn btn-primary" type="submit" id="eval-enviar">Enviar evaluación</button>
    </div>
  </form>
</section>
<script src="<?= e(asset('js/evaluacion.js')) ?>"></script>
