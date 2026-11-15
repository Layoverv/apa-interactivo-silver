<?php
use App\Core\Auth;

$editando = $usuario !== null;
$valor = static fn (string $campo) => e($formulario[$campo] ?? ($usuario[$campo] ?? ''));
$rolActual = (int) ($formulario['rol_id'] ?? ($usuario['rol_id'] ?? 1));
?>
<section class="container py-4 contenedor-formulario">
  <nav aria-label="Ruta de navegación"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= e(url('/admin/usuarios')) ?>">Usuarios</a></li><li class="breadcrumb-item active" aria-current="page"><?= $editando ? e($usuario['nombre']) : 'Nuevo usuario' ?></li></ol></nav>
  <div class="card tarjeta mb-4"><div class="card-body p-4">
    <h1 class="h4"><?= $editando ? 'Editar usuario' : 'Crear usuario' ?></h1>
    <form method="post" action="<?= e(url($editando ? '/admin/usuarios/' . $usuario['id'] : '/admin/usuarios/nuevo')) ?>">
      <?= csrf_campo() ?>
      <div class="mb-3"><label class="form-label" for="nombre">Nombre completo</label><input class="form-control" id="nombre" name="nombre" required minlength="3" maxlength="120" value="<?= $valor('nombre') ?>"></div>
      <div class="mb-3"><label class="form-label" for="correo">Correo</label><input class="form-control" id="correo" name="correo" type="email" required maxlength="190" value="<?= $valor('correo') ?>"></div>
      <div class="mb-3"><label class="form-label" for="rol_id">Rol</label>
        <select class="form-select" id="rol_id" name="rol_id" required>
          <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>" <?= $rolActual === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <fieldset class="border rounded p-3 mb-3">
        <legend class="h6 float-none w-auto px-1 mb-0"><?= $editando ? 'Restablecer contraseña (opcional)' : 'Contraseña temporal' ?></legend>
        <p class="small text-body-secondary">El usuario deberá cambiarla al ingresar.</p>
        <div class="row g-2">
          <div class="col-md-6"><label class="form-label" for="clave">Contraseña</label><input class="form-control" id="clave" name="clave" type="password" autocomplete="new-password" minlength="8" maxlength="72" <?= $editando ? '' : 'required' ?>></div>
          <div class="col-md-6"><label class="form-label" for="confirmacion">Confirmación</label><input class="form-control" id="confirmacion" name="confirmacion" type="password" autocomplete="new-password" minlength="8" maxlength="72" <?= $editando ? '' : 'required' ?>></div>
        </div>
      </fieldset>
      <button class="btn btn-primary" type="submit"><?= $editando ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </form>
  </div></div>

  <?php if ($editando): ?>
    <div class="card tarjeta"><div class="card-body p-4">
      <h2 class="h5">Estado de la cuenta</h2>
      <p class="mb-2">Estado actual: <strong><?= (int) $usuario['activo'] ? 'Activo' : 'Inactivo' ?></strong>. Registrado el <?= e(fecha_corta($usuario['creado_en'])) ?>.
        Política de datos: <?= $usuario['acepto_politica_en'] ? 'aceptada el ' . e(fecha_corta($usuario['acepto_politica_en'])) . ' (versión ' . e($usuario['version_politica']) . ')' : 'pendiente de aceptar' ?>.</p>
      <?php if (!empty($grupos)): ?><p class="small text-body-secondary">Grupos: <?= e(implode(', ', array_map(static fn ($g) => $g['nombre'] . ' (' . $g['periodo'] . ')', $grupos))) ?></p><?php endif; ?>
      <?php if ((int) $usuario['id'] !== Auth::id()): ?>
        <form method="post" action="<?= e(url('/admin/usuarios/' . $usuario['id'] . '/estado')) ?>" data-confirmar="<?= (int) $usuario['activo'] ? '¿Desactivar este usuario? No podrá iniciar sesión, pero sus resultados se conservan.' : '¿Activar este usuario?' ?>">
          <?= csrf_campo() ?>
          <input type="hidden" name="activo" value="<?= (int) $usuario['activo'] ? '0' : '1' ?>">
          <button class="btn <?= (int) $usuario['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $usuario['activo'] ? 'Desactivar usuario' : 'Activar usuario' ?></button>
        </form>
      <?php else: ?>
        <p class="small text-body-secondary mb-0">Esta es tu cuenta: no puedes desactivarla.</p>
      <?php endif; ?>
    </div></div>
  <?php endif; ?>
</section>
