<?php
$cliente  = $cliente  ?? [];
$es_nuevo = $es_nuevo ?? true;
$errores  = $errores  ?? [];

$v = fn(string $k, string $d = '') => htmlspecialchars($cliente[$k] ?? $d, ENT_QUOTES);

$condiciones_fiscales = ['Consumidor Final', 'Responsable Inscripto', 'Monotributista', 'Exento', 'No Responsable', 'Sujeto No Categorizado'];
$tipos_documento      = ['DNI', 'CUIT', 'CUIL', 'LC', 'LE', 'Pasaporte'];
$condiciones_pago     = ['Contado', '15 días', '30 días', '45 días', '60 días', '90 días'];
$provincias           = ['Buenos Aires', 'CABA', 'Catamarca', 'Chaco', 'Chubut', 'Córdoba',
                         'Corrientes', 'Entre Ríos', 'Formosa', 'Jujuy', 'La Pampa', 'La Rioja',
                         'Mendoza', 'Misiones', 'Neuquén', 'Río Negro', 'Salta', 'San Juan',
                         'San Luis', 'Santa Cruz', 'Santa Fe', 'Santiago del Estero',
                         'Tierra del Fuego', 'Tucumán'];
?>

<style>
.cli-edit-wrap { max-width:680px; }
.cli-edit-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.cli-edit-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; flex:1; }
.section-sep { font-size:.65rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--text-dim); padding:.5rem 0 .25rem; border-bottom:1px solid var(--border); margin:1.25rem 0 .75rem; }
.section-sep i { margin-right:.4rem; }
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.grid-3 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:1rem; }
@media (max-width:580px) { .grid-2, .grid-3 { grid-template-columns:1fr; } }
</style>

<div class="cli-edit-wrap">

    <div class="cli-edit-header">
        <h1>
            <i class="fa-solid <?php echo $es_nuevo ? 'fa-user-plus' : 'fa-user-pen'; ?>"
               style="color:var(--accent);margin-right:.5rem;font-size:1.1rem"></i>
            <?php echo $es_nuevo ? 'Nuevo cliente' : 'Editar cliente'; ?>
        </h1>
        <a href="vta_clientes" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-error" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php foreach ($errores as $e): ?>
        <span><?php echo htmlspecialchars($e, ENT_QUOTES); ?></span><br>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="vta_clientes_edit">
        <?php if (!$es_nuevo): ?>
        <input type="hidden" name="id"  value="<?php echo $v('id'); ?>">
        <input type="hidden" name="idx" value="<?php echo $v('idx'); ?>">
        <?php endif; ?>

        <div class="card">
            <div class="card-body">

                <!-- Datos principales -->
                <div class="section-sep"><i class="fa-solid fa-id-card"></i>Datos principales</div>

                <div class="form-group">
                    <label>Razón social / Nombre <span style="color:#f87171">*</span></label>
                    <input type="text" name="razon_social" class="form-control"
                           value="<?php echo $v('razon_social'); ?>" required autofocus
                           placeholder="Nombre o razón social del cliente">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Condición fiscal</label>
                        <select name="condicion_fiscal" class="form-control">
                            <?php foreach ($condiciones_fiscales as $cf): ?>
                            <option value="<?php echo $cf; ?>"
                                <?php echo ($cliente['condicion_fiscal'] ?? 'Consumidor Final') === $cf ? 'selected' : ''; ?>>
                                <?php echo $cf; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>CUIT</label>
                        <input type="text" name="cuit" class="form-control"
                               value="<?php echo $v('cuit'); ?>"
                               placeholder="20-12345678-9">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Tipo de documento</label>
                        <select name="tipo_documento" class="form-control">
                            <?php foreach ($tipos_documento as $td): ?>
                            <option value="<?php echo $td; ?>"
                                <?php echo ($cliente['tipo_documento'] ?? 'DNI') === $td ? 'selected' : ''; ?>>
                                <?php echo $td; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número de documento</label>
                        <input type="text" name="nro_documento" class="form-control"
                               value="<?php echo $v('nro_documento'); ?>" placeholder="00000000">
                    </div>
                </div>

                <!-- Domicilio -->
                <div class="section-sep"><i class="fa-solid fa-location-dot"></i>Domicilio</div>

                <div class="form-group">
                    <label>Calle y número</label>
                    <input type="text" name="domicilio" class="form-control"
                           value="<?php echo $v('domicilio'); ?>" placeholder="Av. Corrientes 1234">
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Localidad</label>
                        <input type="text" name="localidad" class="form-control"
                               value="<?php echo $v('localidad'); ?>" placeholder="Ciudad">
                    </div>
                    <div class="form-group">
                        <label>Provincia</label>
                        <select name="provincia" class="form-control">
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($provincias as $prov): ?>
                            <option value="<?php echo $prov; ?>"
                                <?php echo ($cliente['provincia'] ?? '') === $prov ? 'selected' : ''; ?>>
                                <?php echo $prov; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Código postal</label>
                        <input type="text" name="cp" class="form-control"
                               value="<?php echo $v('cp'); ?>" placeholder="1234">
                    </div>
                </div>

                <!-- Contacto -->
                <div class="section-sep"><i class="fa-solid fa-phone"></i>Contacto</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="<?php echo $v('telefono'); ?>" placeholder="+54 11 1234-5678">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo $v('email'); ?>" placeholder="cliente@empresa.com">
                    </div>
                </div>

                <!-- Condiciones comerciales -->
                <div class="section-sep"><i class="fa-solid fa-handshake"></i>Condiciones comerciales</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Condición de pago</label>
                        <select name="condicion_pago" class="form-control">
                            <?php foreach ($condiciones_pago as $cp): ?>
                            <option value="<?php echo $cp; ?>"
                                <?php echo ($cliente['condicion_pago'] ?? 'Contado') === $cp ? 'selected' : ''; ?>>
                                <?php echo $cp; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Límite crédito (CC)</label>
                        <input type="number" name="limite_credito" class="form-control"
                               value="<?php echo $v('limite_credito', '0'); ?>"
                               min="0" step="0.01" placeholder="0 = sin límite CC">
                        <p style="font-size:.74rem;color:var(--text-dim);margin-top:.25rem">
                            0 = sin cuenta corriente habilitada
                        </p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"
                              placeholder="Notas internas del cliente…"><?php echo $v('observaciones'); ?></textarea>
                </div>

                <?php if (!$es_nuevo): ?>
                <div class="form-group">
                    <label>Estado</label>
                    <div style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem">
                        <input type="checkbox" name="habilitado" id="chkHab" value="1"
                               <?php echo ($cliente['habilitado'] ?? 1) ? 'checked' : ''; ?>
                               onchange="this.value = this.checked ? 1 : 0">
                        <label for="chkHab" style="margin:0;font-weight:400;cursor:pointer">
                            Cliente habilitado
                        </label>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="habilitado" value="1">
                <?php endif; ?>

            </div>
        </div>

        <div style="display:flex;gap:.6rem;margin-top:1.25rem">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?php echo $es_nuevo ? 'Crear cliente' : 'Guardar cambios'; ?>
            </button>
            <a href="vta_clientes" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

</div>
