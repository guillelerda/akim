<?php
$proveedor = $proveedor ?? null;
$es_nuevo  = $es_nuevo  ?? true;
$errores   = $errores   ?? [];

$v = function(string $key, string $default = '') use ($proveedor): string {
    if ($proveedor && isset($proveedor[$key])) return htmlspecialchars($proveedor[$key], ENT_QUOTES);
    if (isset($_POST[$key])) return htmlspecialchars($_POST[$key], ENT_QUOTES);
    return htmlspecialchars($default, ENT_QUOTES);
};

$condiciones_fiscales = ['Responsable Inscripto','Monotributista','Exento','Consumidor Final','No Responsable'];
$condiciones_pago     = ['Contado','15 días','30 días','45 días','60 días','90 días'];
?>

<style>
.prv-edit-wrap { max-width: 820px; }
.prv-edit-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
.prv-edit-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.prv-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.prv-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; }
.section-sep { font-size:.65rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--text-dim); padding:.5rem 0 .25rem; border-bottom:1px solid var(--border); margin:1.25rem 0 .75rem; }
@media (max-width:640px) { .prv-grid-2, .prv-grid-3 { grid-template-columns:1fr; } }
</style>

<div class="prv-edit-wrap">

    <!-- Header -->
    <div class="prv-edit-header">
        <a href="cpa_proveedores" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1><?php echo $es_nuevo ? 'Nuevo Proveedor' : 'Editar Proveedor'; ?></h1>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-error" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <?php foreach ($errores as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="cpa_proveedores_edit">
        <?php if (!$es_nuevo): ?>
        <input type="hidden" name="idx" value="<?php echo $v('idx'); ?>">
        <?php endif; ?>

        <div class="card">
            <div class="card-body">

                <!-- Datos principales -->
                <div class="section-sep">Datos principales</div>
                <div class="form-group">
                    <label>Razón social *</label>
                    <input type="text" name="razon_social" class="form-control"
                           value="<?php echo $v('razon_social'); ?>"
                           placeholder="Nombre o razón social del proveedor" required autofocus>
                </div>
                <div class="prv-grid-2">
                    <div class="form-group">
                        <label>CUIT</label>
                        <input type="text" name="cuit" class="form-control"
                               value="<?php echo $v('cuit'); ?>" placeholder="20-12345678-9">
                    </div>
                    <div class="form-group">
                        <label>Condición fiscal</label>
                        <select name="condicion_fiscal" class="form-control">
                            <option value="">— seleccionar —</option>
                            <?php foreach ($condiciones_fiscales as $cf): ?>
                            <option value="<?php echo $cf; ?>"
                                <?php echo $v('condicion_fiscal') === $cf ? 'selected' : ''; ?>>
                                <?php echo $cf; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="prv-grid-2">
                    <div class="form-group">
                        <label>Condición de pago</label>
                        <select name="condicion_pago" class="form-control">
                            <?php foreach ($condiciones_pago as $cp): ?>
                            <option value="<?php echo $cp; ?>"
                                <?php echo $v('condicion_pago', 'Contado') === $cp ? 'selected' : ''; ?>>
                                <?php echo $cp; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contacto</label>
                        <input type="text" name="contacto" class="form-control"
                               value="<?php echo $v('contacto'); ?>" placeholder="Nombre del contacto">
                    </div>
                </div>

                <!-- Contacto -->
                <div class="section-sep">Contacto</div>
                <div class="prv-grid-2">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="<?php echo $v('telefono'); ?>" placeholder="(011) 4xxx-xxxx">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo $v('email'); ?>" placeholder="proveedor@ejemplo.com">
                    </div>
                </div>

                <!-- Domicilio -->
                <div class="section-sep">Domicilio</div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="domicilio" class="form-control"
                           value="<?php echo $v('domicilio'); ?>" placeholder="Calle y número">
                </div>
                <div class="prv-grid-3">
                    <div class="form-group">
                        <label>Localidad</label>
                        <input type="text" name="localidad" class="form-control"
                               value="<?php echo $v('localidad'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Provincia</label>
                        <input type="text" name="provincia" class="form-control"
                               value="<?php echo $v('provincia'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Código postal</label>
                        <input type="text" name="cp" class="form-control"
                               value="<?php echo $v('cp'); ?>">
                    </div>
                </div>

                <!-- Observaciones -->
                <div class="section-sep">Observaciones</div>
                <div class="form-group">
                    <label>Notas internas</label>
                    <textarea name="observaciones" class="form-control" rows="3"
                              placeholder="Notas internas sobre el proveedor..."><?php echo $v('observaciones'); ?></textarea>
                </div>

            </div>
        </div>

        <!-- Acciones -->
        <div style="display:flex;gap:.75rem;margin-top:1.25rem">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?php echo $es_nuevo ? 'Crear proveedor' : 'Guardar cambios'; ?>
            </button>
            <a href="cpa_proveedores" class="btn btn-secondary">Cancelar</a>
        </div>

    </form>
</div>
