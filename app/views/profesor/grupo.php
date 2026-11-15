<section class="encabezado-pagina">
  <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <nav aria-label="Ruta de navegación"><ol class="breadcrumb small mb-2"><li class="breadcrumb-item"><a href="<?= e(url('/profesor')) ?>">Mis grupos</a></li><li class="breadcrumb-item active" aria-current="page"><?= e($grupo['nombre']) ?></li></ol></nav>
      <h1 class="h2 mb-0"><?= e($grupo['nombre']) ?> <span class="text-body-secondary h5">· <?= e($grupo['periodo']) ?></span></h1>
    </div>
    <a class="btn btn-outline-primary" href="<?= e(url('/profesor/grupos/' . $grupo['id'] . '/exportar')) ?>">Descargar resultados (CSV)</a>
  </div>
</section>
<section class="container py-4">
  <div class="row g-3 mb-4">
    <div class="col-sm-4"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Estudiantes con intentos</p><p class="cifra mb-0"><?= $estadisticas['estudiantes'] ?> de <?= count($estudiantes) ?></p></div></div></div>
    <div class="col-sm-4"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Evaluaciones presentadas</p><p class="cifra mb-0"><?= $estadisticas['evaluaciones'] ?></p></div></div></div>
    <div class="col-sm-4"><div class="card tarjeta h-100"><div class="card-body"><p class="etiqueta-dato">Tasa de éxito promedio</p><p class="cifra mb-0"><?= e(porcentaje($estadisticas['tasa_promedio'])) ?></p></div></div></div>
  </div>

  <section class="card tarjeta mb-4" aria-labelledby="titulo-estudiantes"><div class="card-body p-4">
    <h2 class="h5" id="titulo-estudiantes">Resultados por estudiante</h2>
    <?php if (!$estudiantes): ?>
      <p class="text-body-secondary mb-0">El grupo no tiene estudiantes. El administrador los agrega desde Grupos.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead><tr><th scope="col">Estudiante</th><th scope="col">Intentos</th><th scope="col">Último puntaje</th><th scope="col">Mejor tasa de éxito</th><th scope="col">Último intento</th><th scope="col"><span class="visually-hidden">Detalle</span></th></tr></thead>
          <tbody>
          <?php foreach ($estudiantes as $s): ?>
            <tr>
              <td><strong><?= e($s['nombre']) ?></strong><br><span class="small text-body-secondary"><?= e($s['correo']) ?></span><?= (int) $s['activo'] ? '' : ' <span class="badge text-bg-secondary">inactivo</span>' ?></td>
              <td><?= (int) $s['intentos'] ?></td>
              <td><?= e(porcentaje($s['ultimo'])) ?></td>
              <td><?= e(porcentaje($s['mejor'])) ?> <?= $s['mejor'] !== null && (float) $s['mejor'] >= $meta ? '<span class="text-success" aria-label="meta alcanzada">✓</span>' : '' ?></td>
              <td><?= e(fecha_corta($s['ultima_fecha'])) ?></td>
              <td><a href="<?= e(url('/profesor/grupos/' . $grupo['id'] . '/estudiantes/' . $s['id'])) ?>">Ver detalle</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div></section>

  <div class="row g-4">
    <?php foreach ([['Errores frecuentes por tema', $porTema, $graficaTema, 'Tema'], ['Errores por tipo de error', $porTipo, $graficaTipo, 'Tipo de error']] as $i => [$tituloSeccion, $filas, $grafica, $columna]): ?>
      <div class="col-xl-6">
        <section class="card tarjeta h-100" aria-labelledby="titulo-errores-<?= $i ?>"><div class="card-body p-4">
          <h2 class="h5" id="titulo-errores-<?= $i ?>"><?= e($tituloSeccion) ?></h2>
          <?php if (!$filas): ?>
            <p class="text-body-secondary mb-0">Aún no hay evaluaciones presentadas en este grupo.</p>
          <?php else: ?>
            <?= $grafica ?>
            <div class="table-responsive mt-3">
              <table class="table table-sm mb-0">
                <thead><tr><th scope="col"><?= e($columna) ?></th><th scope="col">Errores</th><th scope="col">% de error</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $f): ?>
                  <tr><td><?= e($f['nombre']) ?></td><td><?= (int) $f['errores'] ?> de <?= (int) $f['respuestas'] ?></td><td><strong><?= e(porcentaje($f['porcentaje'])) ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div></section>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="small text-body-secondary mt-3">Los porcentajes se calculan con las respuestas de las evaluaciones finalizadas y se ordenan de mayor a menor error.</p>
</section>
