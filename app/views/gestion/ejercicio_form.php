<?php
$editando = $ejercicio !== null;
$hayFormulario = isset($formulario['enunciado']);
$valor = static fn (string $campo) => $formulario[$campo] ?? ($ejercicio[$campo] ?? '');
$opciones = $hayFormulario ? array_values((array) ($formulario['opciones'] ?? [])) : array_column($ejercicio['opciones'] ?? [], 'texto');
$opciones = array_pad($opciones, 4, '');
if ($hayFormulario) {
    $correcta = (int) ($formulario['correcta'] ?? -1);
} else {
    $correcta = -1;
    foreach ($ejercicio['opciones'] ?? [] as $i => $o) {
        if ((int) $o['es_correcta']) {
            $correcta = $i;
        }
    }
}
$enEvaluacion = $hayFormulario ? (($formulario['en_evaluacion'] ?? '') === '1') : ($ejercicio['en_evaluacion'] ?? true);
?>
<section class="container py-4 contenedor-formulario-ancho">
  <nav aria-label="Ruta de navegación"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('/gestion/ejercicios')) ?>">Banco de ejercicios</a></li><li class="breadcrumb-item active" aria-current="page"><?= $editando ? 'Ejercicio #' . (int) $ejercicio['id'] : 'Nuevo ejercicio' ?></li></ol></nav>
  <form class="card tarjeta" method="post" action="<?= e(url($editando ? '/gestion/ejercicios/' . $ejercicio['id'] : '/gestion/ejercicios/nuevo')) ?>">
    <div class="card-body p-4">
      <h1 class="h4"><?= $editando ? 'Editar ejercicio' : 'Crear ejercicio' ?></h1>
      <?php if ($editando && $ejercicio['tiene_respuestas']): ?>
        <p class="alert alert-info">Este ejercicio ya fue respondido por estudiantes. Puedes corregir los textos, pero no cambiar la cantidad de opciones ni la respuesta correcta.</p>
      <?php endif; ?>
      <?= csrf_campo() ?>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label" for="tema_id">Tema</label>
          <select class="form-select" id="tema_id" name="tema_id" required><option value="">Selecciona…</option>
            <?php foreach ($temas as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $valor('tema_id') === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['titulo']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-6"><label class="form-label" for="tipo_error_id">Tipo de error</label>
          <select class="form-select" id="tipo_error_id" name="tipo_error_id" required><option value="">Selecciona…</option>
            <?php foreach ($tiposError as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $valor('tipo_error_id') === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-6"><label class="form-label" for="tipo">Tipo de ejercicio</label>
          <select class="form-select" id="tipo" name="tipo" required>
            <?php foreach (['seleccion_multiple', 'verdadero_falso', 'completar'] as $t): ?><option value="<?= $t ?>" <?= $valor('tipo') === $t ? 'selected' : '' ?>><?= tipo_ejercicio_texto($t) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-6"><label class="form-label" for="dificultad">Nivel</label>
          <select class="form-select" id="dificultad" name="dificultad" required>
            <?php foreach ([1, 2, 3] as $n): ?><option value="<?= $n ?>" <?= (int) ($valor('dificultad') ?: 1) === $n ? 'selected' : '' ?>><?= nivel_texto($n) ?></option><?php endforeach; ?>
          </select></div>
      </div>
      <div class="mb-3"><label class="form-label" for="enunciado">Enunciado</label><textarea class="form-control" id="enunciado" name="enunciado" rows="3" required maxlength="2000"><?= e($valor('enunciado')) ?></textarea>
        <div class="form-text">En los ejercicios de completar, marca el espacio con ____.</div></div>
      <fieldset class="mb-3">
        <legend class="form-label fs-6">Opciones (entre dos y cuatro) y respuesta correcta</legend>
        <?php foreach ($opciones as $i => $texto): ?>
          <div class="input-group mb-2">
            <span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correcta" value="<?= $i ?>" id="correcta-<?= $i ?>" <?= $correcta === $i ? 'checked' : '' ?> aria-label="Marcar la opción <?= letra_opcion($i) ?> como correcta"></span>
            <label class="input-group-text" for="opcion-<?= $i ?>"><?= letra_opcion($i) ?></label>
            <input class="form-control" id="opcion-<?= $i ?>" name="opciones[]" maxlength="500" value="<?= e($texto) ?>" <?= $i < 2 ? 'required' : '' ?>>
          </div>
        <?php endforeach; ?>
        <div class="form-text">Marca el círculo de la opción correcta. Deja vacías las opciones que no uses. En verdadero o falso usa solo A y B.</div>
      </fieldset>
      <div class="mb-3"><label class="form-label" for="explicacion">Explicación de la respuesta</label><textarea class="form-control" id="explicacion" name="explicacion" rows="3" required maxlength="2000"><?= e($valor('explicacion')) ?></textarea>
        <div class="form-text">Se muestra al estudiante después de responder. Es obligatoria.</div></div>
      <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="en_evaluacion" name="en_evaluacion" value="1" <?= $enEvaluacion ? 'checked' : '' ?>><label class="form-check-label" for="en_evaluacion">Puede salir en la evaluación final (además de la práctica)</label></div>
      <button class="btn btn-primary" type="submit"><?= $editando ? 'Guardar ejercicio' : 'Crear ejercicio' ?></button>
    </div>
  </form>
  <?php if ($editando): ?>
    <form method="post" action="<?= e(url('/gestion/ejercicios/' . $ejercicio['id'] . '/estado')) ?>" class="mt-3" data-confirmar="<?= (int) $ejercicio['activo'] ? '¿Desactivar este ejercicio? Dejará de aparecer en prácticas y evaluaciones nuevas; sus respuestas se conservan.' : '¿Activar este ejercicio?' ?>">
      <?= csrf_campo() ?><input type="hidden" name="activo" value="<?= (int) $ejercicio['activo'] ? '0' : '1' ?>">
      <p class="mb-2">Estado: <strong><?= (int) $ejercicio['activo'] ? 'Activo' : 'Inactivo' ?></strong></p>
      <button class="btn btn-sm <?= (int) $ejercicio['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $ejercicio['activo'] ? 'Desactivar ejercicio' : 'Activar ejercicio' ?></button>
    </form>
  <?php endif; ?>
</section>
