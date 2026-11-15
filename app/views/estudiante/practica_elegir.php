<section class="encabezado-pagina">
  <div class="container">
    <p class="eyebrow">MÓDULO 2 · PRÁCTICA GUIADA</p>
    <h1 class="h2">Elige qué practicar</h1>
    <p class="text-body-secondary mb-0">Responde un ejercicio a la vez. Al responder sabrás si acertaste, cuál es la respuesta correcta y por qué.</p>
  </div>
</section>
<section class="container py-4 contenedor-formulario">
  <form method="post" action="<?= e(url('/practica/iniciar')) ?>" class="card tarjeta">
    <div class="card-body p-4">
      <?= csrf_campo() ?>
      <fieldset class="mb-4">
        <legend class="h6">1. Tema</legend>
        <?php foreach ($temas as $i => $t): ?>
          <div class="form-check opcion-lista">
            <input class="form-check-input" type="radio" name="tema_id" id="tema-<?= (int) $t['id'] ?>" value="<?= (int) $t['id'] ?>" required <?= ($temaElegido === (int) $t['id'] || ($temaElegido === null && $i === 0)) ? 'checked' : '' ?>>
            <label class="form-check-label" for="tema-<?= (int) $t['id'] ?>">
              <?= e($t['titulo']) ?>
              <span class="text-body-secondary small">· <?= (int) $t['ejercicios_activos'] ?> ejercicios
                (<?php $c = $conteo[(int) $t['id']] ?? []; ?>básico <?= $c[1] ?? 0 ?>, intermedio <?= $c[2] ?? 0 ?>, avanzado <?= $c[3] ?? 0 ?>)</span>
            </label>
          </div>
        <?php endforeach; ?>
      </fieldset>
      <fieldset class="mb-4">
        <legend class="h6">2. Nivel de dificultad</legend>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach (['' => 'Todos los niveles', '1' => 'Básico', '2' => 'Intermedio', '3' => 'Avanzado'] as $valor => $texto): ?>
            <input type="radio" class="btn-check" name="dificultad" id="nivel-<?= $valor === '' ? 'todos' : $valor ?>" value="<?= $valor ?>" <?= $valor === '' ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary" for="nivel-<?= $valor === '' ? 'todos' : $valor ?>"><?= $texto ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <button class="btn btn-primary" type="submit">Comenzar práctica</button>
    </div>
  </form>
</section>
