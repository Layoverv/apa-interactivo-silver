<section class="encabezado-pagina">
  <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <p class="eyebrow">GESTIÓN DE CONTENIDOS</p>
      <h1 class="h2 mb-0">Banco de ejercicios</h1>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/gestion/ejercicios/nuevo', $filtros['tema'] ? ['tema' => $filtros['tema']] : [])) ?>">Crear ejercicio</a>
  </div>
</section>
<section class="container py-4">
  <form class="row g-2 mb-3" method="get" action="<?= e(url('/gestion/ejercicios')) ?>">
    <div class="col-md-5"><label class="visually-hidden" for="f-tema">Tema</label>
      <select class="form-select" id="f-tema" name="tema"><option value="">Todos los temas</option>
        <?php foreach ($temas as $t): ?><option value="<?= (int) $t['id'] ?>" <?= $filtros['tema'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['titulo']) ?> (<?= (int) $t['ejercicios_activos'] ?> activos)</option><?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="visually-hidden" for="f-nivel">Nivel</label>
      <select class="form-select" id="f-nivel" name="nivel"><option value="">Todos los niveles</option>
        <?php foreach ([1, 2, 3] as $n): ?><option value="<?= $n ?>" <?= $filtros['nivel'] === $n ? 'selected' : '' ?>><?= nivel_texto($n) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="visually-hidden" for="f-estado">Estado</label>
      <select class="form-select" id="f-estado" name="estado">
        <?php foreach (['activos' => 'Activos', 'inactivos' => 'Inactivos', 'todos' => 'Todos'] as $v => $t): ?><option value="<?= $v ?>" <?= $filtros['estado'] === $v ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filtrar</button></div>
  </form>
  <p class="small text-body-secondary"><?= count($ejercicios) ?> ejercicios en esta vista.</p>
  <div class="card tarjeta"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th scope="col">#</th><th scope="col">Enunciado</th><th scope="col">Tema</th><th scope="col">Tipo de error</th><th scope="col">Nivel</th><th scope="col">Evaluación</th><th scope="col">Estado</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead>
      <tbody>
      <?php foreach ($ejercicios as $x): ?>
        <tr class="<?= (int) $x['activo'] ? '' : 'text-body-secondary' ?>">
          <td><?= (int) $x['id'] ?></td>
          <td><?= e(mb_strimwidth($x['enunciado'], 0, 90, '…')) ?><br><span class="small text-body-secondary"><?= e(tipo_ejercicio_texto($x['tipo'])) ?> · respondido <?= (int) $x['veces_respondido'] ?> veces</span></td>
          <td><?= e($x['tema']) ?></td>
          <td><?= e($x['tipo_error']) ?></td>
          <td><?= e(nivel_texto((int) $x['dificultad'])) ?></td>
          <td><?= (int) $x['en_evaluacion'] ? 'Sí' : 'No' ?></td>
          <td><?= (int) $x['activo'] ? 'Activo' : 'Inactivo' ?></td>
          <td><a href="<?= e(url('/gestion/ejercicios/' . $x['id'])) ?>">Editar</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$ejercicios): ?><tr><td colspan="8" class="text-body-secondary">No hay ejercicios con estos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
</section>
