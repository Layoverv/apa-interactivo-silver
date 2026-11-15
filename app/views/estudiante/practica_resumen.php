<section class="container py-4 contenedor-actividad">
  <p class="eyebrow">RESUMEN DE LA PRÁCTICA</p>
  <h1 class="h3"><?= e($intento['tema']) ?></h1>
  <div class="row g-3 my-2">
    <div class="col-sm-4"><div class="card tarjeta"><div class="card-body"><p class="etiqueta-dato">Aciertos</p><p class="cifra mb-0"><?= $conteo['correctas'] ?> de <?= $conteo['respondidas'] ?></p></div></div></div>
    <div class="col-sm-4"><div class="card tarjeta"><div class="card-body"><p class="etiqueta-dato">Porcentaje</p><p class="cifra mb-0"><?= e(porcentaje($conteo['respondidas'] ? $conteo['correctas'] * 100 / $conteo['respondidas'] : null)) ?></p></div></div></div>
    <div class="col-sm-4"><div class="card tarjeta"><div class="card-body"><p class="etiqueta-dato">Respondidos</p><p class="cifra mb-0"><?= $conteo['respondidas'] ?> de <?= $conteo['total'] ?></p></div></div></div>
  </div>
  <?php if ($conteo['respondidas'] < $conteo['total']): ?>
    <p class="alert alert-info">Te faltan ejercicios por responder. <a href="<?= e(url('/practica/' . $intento['id'])) ?>">Continuar la práctica</a></p>
  <?php endif; ?>

  <ul class="list-group my-4">
    <?php foreach ($detalle as $f): $ok = (int) $f['es_correcta'] === 1; ?>
      <li class="list-group-item d-flex gap-3">
        <span class="resultado-icono <?= $ok ? 'text-success' : 'text-danger' ?>" aria-hidden="true"><?= $ok ? '✓' : '✗' ?></span>
        <div>
          <p class="mb-1"><?= e($f['enunciado']) ?></p>
          <p class="small text-body-secondary mb-0"><?= $ok ? 'Correcto' : 'Incorrecto · Respuesta correcta: ' . e($f['respuesta_correcta']) ?></p>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(url('/practica', ['tema' => $intento['tema_id']])) ?>">Practicar de nuevo</a>
    <a class="btn btn-outline-primary" href="<?= e(url('/temas/' . $intento['tema_id'])) ?>">Repasar el tema</a>
    <?php if ($siguiente): ?>
      <a class="btn btn-outline-primary" href="<?= e(url('/temas/' . $siguiente['id'])) ?>">Siguiente tema: <?= e($siguiente['titulo']) ?> →</a>
    <?php else: ?>
      <a class="btn btn-outline-primary" href="<?= e(url('/evaluacion')) ?>">Ir a la evaluación →</a>
    <?php endif; ?>
  </div>
</section>
