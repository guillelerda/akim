<?php
$proveedores    = $proveedores ?? [];
$errores        = $errores     ?? [];
$params         = $params      ?? [];
$condpago_def   = $params['cpa_condicion_pago'] ?? 'Contado';
?>

<style>
.fnv-wrap { max-width: 900px; }
.fnv-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
.fnv-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

.fnv-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.fnv-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; }
.fnv-grid-4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:1rem; }

.section-sep { font-size:.65rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--text-dim); padding:.5rem 0 .25rem; border-bottom:1px solid var(--border); margin:1.25rem 0 .75rem; }

.items-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.items-table th { text-align:left; padding:.5rem .65rem; font-size:.68rem; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--text-dim); border-bottom:1px solid var(--border); }
.items-table td { padding:.45rem .5rem; border-bottom:1px solid rgba(255,255,255,.04); }
.items-table tr:last-child td { border-bottom:none; }
.items-table input { background:rgba(31,41,55,.5); border:1px solid var(--border-med); border-radius:7px; padding:.4rem .65rem; color:var(--text); font-size:.85rem; font-family:inherit; outline:none; width:100%; transition:border-color var(--transition); }
.items-table input:focus { border-color:rgba(59,130,246,.45); }
.items-table .td-cant  { width:90px; }
.items-table .td-precio{ width:130px; }
.items-table .td-sub   { width:130px; }
.items-table .td-del   { width:46px; text-align:center; }

.btn-del-row { background:none; border:none; color:var(--text-dim); cursor:pointer; padding:.25rem .5rem; border-radius:6px; transition:color var(--transition), background var(--transition); }
.btn-del-row:hover { color:#fca5a5; background:rgba(239,68,68,.1); }

.prv-mode-link { font-size:.78rem; color:var(--text-dim); text-decoration:none; display:inline-block; margin-top:.4rem; transition:color .15s; }
.prv-mode-link:hover { color:var(--accent); }
.prv-registrar-box { margin-top:.5rem; padding:.65rem .85rem; background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.2); border-radius:9px; font-size:.83rem; }
.prv-registrar-box label { display:flex; align-items:center; gap:.5rem; cursor:pointer; margin:0; }

.prd-registro-box { margin-top:.85rem; padding:.7rem 1rem; background:rgba(16,185,129,.07); border:1px solid rgba(16,185,129,.2); border-radius:9px; font-size:.83rem; }
.prd-registro-box label { display:flex; align-items:center; gap:.6rem; cursor:pointer; margin:0; color:var(--text); }
.prd-registro-box small { display:block; margin-top:.3rem; color:var(--text-dim); padding-left:1.6rem; }

.fnv-totales { display:flex; flex-direction:column; align-items:flex-end; gap:.4rem; padding:1rem 1.25rem; border-top:1px solid var(--border); }
.fnv-totales-row { display:flex; gap:2rem; font-size:.88rem; color:var(--text-muted); }
.fnv-totales-row.total { font-size:1.1rem; font-weight:700; color:var(--text); border-top:1px solid var(--border-med); padding-top:.4rem; margin-top:.2rem; }
.fnv-totales-row span:first-child { min-width:120px; text-align:right; }
.fnv-totales-row span:last-child  { min-width:110px; text-align:right; font-variant-numeric:tabular-nums; }

@media (max-width:640px) { .fnv-grid-2, .fnv-grid-3, .fnv-grid-4 { grid-template-columns:1fr; } }
</style>

<div class="fnv-wrap">

    <div class="fnv-header">
        <a href="cpa_facturas" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1>Nueva Factura de Compra</h1>
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

    <form method="POST" action="cpa_facturas_nueva" id="fnv-form">
        <input type="hidden" name="items_json" id="items_json">

        <div class="card">
            <div class="card-body">

                <div class="section-sep">Comprobante</div>
                <div class="fnv-grid-4">
                    <div class="form-group" style="grid-column:span 2">
                        <label>Proveedor *</label>

                        <div id="prv-mode-list">
                            <select name="proveedor_id" id="proveedor_id" class="form-control"
                                    onchange="onProveedorChange(this)">
                                <option value="">— Seleccionar proveedor —</option>
                                <?php foreach ($proveedores as $p): ?>
                                <option value="<?php echo $p['id']; ?>"
                                        data-label="<?php echo htmlspecialchars($p['razon_social'], ENT_QUOTES); ?>"
                                        data-condpago="<?php echo htmlspecialchars($p['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($p['razon_social'], ENT_QUOTES); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="#" class="prv-mode-link" onclick="switchPrvMode('free');return false">
                                <i class="fa-solid fa-keyboard"></i> No está en la lista, ingresar manualmente
                            </a>
                        </div>

                        <div id="prv-mode-free" style="display:none">
                            <input type="text" id="proveedor_libre" name="proveedor_libre" class="form-control"
                                   placeholder="Nombre o razón social del proveedor"
                                   oninput="onProveedorLibreInput(this)">
                            <div id="prv-registrar-wrap" class="prv-registrar-box" style="display:none">
                                <label>
                                    <input type="checkbox" name="auto_registrar" id="auto_registrar">
                                    Registrar este proveedor en el sistema automáticamente
                                </label>
                            </div>
                            <a href="#" class="prv-mode-link" onclick="switchPrvMode('list');return false">
                                <i class="fa-solid fa-list"></i> Volver a la lista de proveedores
                            </a>
                        </div>

                        <input type="hidden" name="proveedor_label" id="proveedor_label">
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="X">X (Recibo)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="numero" class="form-control" placeholder="0001-00012345">
                    </div>
                </div>
                <div class="fnv-grid-3">
                    <div class="form-group">
                        <label>Fecha *</label>
                        <input type="date" name="fecha" class="form-control"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Condición de pago</label>
                        <select name="condicion_pago" id="condicion_pago" class="form-control">
                            <?php foreach (['Contado','15 días','30 días','45 días','60 días','90 días'] as $cp): ?>
                            <option value="<?php echo $cp; ?>"
                                    <?php echo $condpago_def === $cp ? 'selected' : ''; ?>>
                                <?php echo $cp; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="section-sep">Ítems</div>
                <div style="overflow-x:auto">
                    <table class="items-table" id="items-table">
                        <thead>
                            <tr>
                                <th>Descripción / Producto</th>
                                <th class="td-cant">Cantidad</th>
                                <th class="td-precio">Precio unit.</th>
                                <th class="td-sub">Subtotal</th>
                                <th class="td-del"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body"></tbody>
                    </table>
                </div>
                <div style="margin-top:.75rem">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">
                        <i class="fa-solid fa-plus"></i> Agregar ítem
                    </button>
                </div>

                <div class="prd-registro-box">
                    <label>
                        <input type="checkbox" name="auto_registrar_productos" id="auto_registrar_productos">
                        <span><i class="fa-solid fa-boxes-stacked" style="color:rgba(16,185,129,.8)"></i>
                        Registrar automáticamente en stock los artículos que no existan</span>
                    </label>
                    <small>Los artículos ingresados que no estén en el maestro de stock serán creados como nuevos productos, con el precio unitario como precio de costo y la cantidad como stock inicial.</small>
                </div>

                <div class="fnv-totales">
                    <div class="form-group" style="display:flex;align-items:center;gap:.75rem;margin-bottom:0">
                        <label style="margin-bottom:0;white-space:nowrap">IVA %</label>
                        <select id="iva_pct_sel" name="iva_pct" class="form-control" style="width:90px"
                                onchange="calcTotales()">
                            <option value="0">0%</option>
                            <option value="10.5">10.5%</option>
                            <option value="21" selected>21%</option>
                            <option value="27">27%</option>
                        </select>
                    </div>
                    <div class="fnv-totales-row">
                        <span>Subtotal neto</span><span id="disp_neto">$ 0,00</span>
                    </div>
                    <div class="fnv-totales-row">
                        <span>IVA</span><span id="disp_iva">$ 0,00</span>
                    </div>
                    <div class="fnv-totales-row total">
                        <span>TOTAL</span><span id="disp_total">$ 0,00</span>
                    </div>
                </div>
                <input type="hidden" name="total_neto" id="total_neto">
                <input type="hidden" name="iva"        id="iva">
                <input type="hidden" name="total"      id="total">

                <div class="section-sep">Observaciones</div>
                <div class="form-group">
                    <textarea name="observaciones" class="form-control" rows="2"
                              placeholder="Notas internas sobre esta factura..."></textarea>
                </div>

                <!-- ── Pago en tesorería — solo visible cuando condicion = Contado ── -->
                <div id="pago-section">
                <?php
                $cuentas_tes = $cuentas_tes ?? [];
                $cuentas_ef  = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'efectivo');
                $cuentas_bco = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'banco');
                ?>
                <div class="section-sep">Medio de pago <span style="font-weight:400;text-transform:none;font-size:.78rem;letter-spacing:0">(opcional)</span></div>

                <div class="form-group">
                    <label for="pago_medio">Medio de pago</label>
                    <select name="pago_medio" id="pago_medio" class="form-control" onchange="fnvTogglePago(this.value)">
                        <option value="sin_pago">— Sin pago registrado —</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia bancaria</option>
                        <option value="cheque">Cheque emitido</option>
                    </select>
                </div>

                <!-- Efectivo -->
                <div id="pago-ef" class="fnv-pago-panel" style="display:none">
                    <div class="form-group">
                        <label for="pago_ef_id">Caja / Efectivo</label>
                        <select name="pago_ef_id" id="pago_ef_id" class="form-control">
                            <option value="">— Seleccioná cuenta —</option>
                            <?php foreach ($cuentas_ef as $ct): ?>
                            <option value="<?php echo $ct['id']; ?>">
                                <?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Transferencia -->
                <div id="pago-bco" class="fnv-pago-panel" style="display:none">
                    <div class="form-group">
                        <label for="pago_bco_id">Cuenta bancaria</label>
                        <select name="pago_bco_id" id="pago_bco_id" class="form-control">
                            <option value="">— Seleccioná cuenta —</option>
                            <?php foreach ($cuentas_bco as $ct): ?>
                            <option value="<?php echo $ct['id']; ?>">
                                <?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Cheque emitido -->
                <div id="pago-chq" class="fnv-pago-panel" style="display:none">
                    <div class="fnv-grid-2">
                        <div class="form-group">
                            <label for="pago_chq_numero">Número de cheque</label>
                            <input type="text" name="pago_chq_numero" id="pago_chq_numero" class="form-control" placeholder="00001234">
                        </div>
                        <div class="form-group">
                            <label for="pago_chq_banco">Banco</label>
                            <input type="text" name="pago_chq_banco" id="pago_chq_banco" class="form-control" placeholder="Ej: Galicia, Nación...">
                        </div>
                        <div class="form-group">
                            <label for="pago_chq_fecha_venc">Fecha de vencimiento</label>
                            <input type="date" name="pago_chq_fecha_venc" id="pago_chq_fecha_venc" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="pago_chq_cta_id">Cuenta bancaria</label>
                            <select name="pago_chq_cta_id" id="pago_chq_cta_id" class="form-control">
                                <option value="">— Seleccioná cuenta —</option>
                                <?php foreach ($cuentas_bco as $ct): ?>
                                <option value="<?php echo $ct['id']; ?>">
                                    <?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                </div><!-- /pago-section -->

            </div>
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1.25rem">
            <button type="submit" class="btn btn-primary" onclick="return prepareSubmit()">
                <i class="fa-solid fa-floppy-disk"></i> Registrar factura
            </button>
            <a href="cpa_facturas" class="btn btn-secondary">Cancelar</a>
        </div>

    </form>
</div>

<script>
var rowCount = 0;
var prvMode  = 'list';

function switchPrvMode(mode) {
    prvMode = mode;
    document.getElementById('prv-mode-list').style.display = mode === 'list' ? '' : 'none';
    document.getElementById('prv-mode-free').style.display = mode === 'free' ? '' : 'none';
    if (mode === 'list') {
        document.getElementById('proveedor_libre').value   = '';
        document.getElementById('auto_registrar').checked = false;
        document.getElementById('prv-registrar-wrap').style.display = 'none';
        document.getElementById('proveedor_label').value  = '';
    } else {
        document.getElementById('proveedor_id').value     = '';
        document.getElementById('proveedor_label').value  = '';
    }
}

function onProveedorLibreInput(input) {
    var val = input.value.trim();
    document.getElementById('prv-registrar-wrap').style.display = val.length > 0 ? '' : 'none';
    document.getElementById('proveedor_label').value = val;
}

function addRow(label, cant, precio) {
    label  = label  || '';
    cant   = (cant !== undefined && cant !== '') ? cant : 1;
    precio = precio || '';
    var tbody = document.getElementById('items-body');
    var i = rowCount++;
    var tr = document.createElement('tr');
    tr.id  = 'row-' + i;
    tr.innerHTML =
        '<td><input type="text" placeholder="Descripción del ítem" value="' + escHtml(label) + '" oninput="calcTotales()"></td>' +
        '<td class="td-cant"><input type="number" placeholder="1" min="0.001" step="0.001" value="' + cant + '" oninput="calcRow(this)"></td>' +
        '<td class="td-precio"><input type="number" placeholder="0.00" min="0" step="0.01" value="' + precio + '" oninput="calcRow(this)"></td>' +
        '<td class="td-sub"><input type="number" readonly tabindex="-1" style="background:rgba(255,255,255,.04);color:var(--text-dim)" value="0.00"></td>' +
        '<td class="td-del"><button type="button" class="btn-del-row" onclick="delRow(' + i + ')" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button></td>';
    tbody.appendChild(tr);
    calcTotales();
}

function delRow(i) {
    var row = document.getElementById('row-' + i);
    if (row) { row.remove(); calcTotales(); }
}

function calcRow(input) {
    var tr   = input.closest('tr');
    var cant = parseFloat(tr.querySelectorAll('input')[1].value) || 0;
    var prec = parseFloat(tr.querySelectorAll('input')[2].value) || 0;
    tr.querySelectorAll('input')[3].value = (cant * prec).toFixed(2);
    calcTotales();
}

function calcTotales() {
    var neto = 0;
    document.querySelectorAll('#items-body tr').forEach(function(tr) {
        neto += parseFloat(tr.querySelectorAll('input')[3].value) || 0;
    });
    var ivaPct = parseFloat(document.getElementById('iva_pct_sel').value) || 0;
    var iva    = neto * ivaPct / 100;
    var total  = neto + iva;
    document.getElementById('disp_neto').textContent  = '$ ' + fmt(neto);
    document.getElementById('disp_iva').textContent   = '$ ' + fmt(iva);
    document.getElementById('disp_total').textContent = '$ ' + fmt(total);
    document.getElementById('total_neto').value = neto.toFixed(2);
    document.getElementById('iva').value         = iva.toFixed(2);
    document.getElementById('total').value       = total.toFixed(2);
}

function fmt(n) {
    return n.toLocaleString('es-AR', { minimumFractionDigits:2, maximumFractionDigits:2 });
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function onProveedorChange(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('proveedor_label').value = opt.dataset.label || '';
    var condPago = opt.dataset.condpago || 'Contado';
    var selCond  = document.getElementById('condicion_pago');
    for (var i = 0; i < selCond.options.length; i++) {
        if (selCond.options[i].value === condPago) { selCond.selectedIndex = i; break; }
    }
}

function prepareSubmit() {
    if (prvMode === 'list') {
        if (!document.getElementById('proveedor_id').value) {
            alert('Debe seleccionar un proveedor de la lista, o usar "ingresar manualmente".');
            return false;
        }
    } else {
        var libre = document.getElementById('proveedor_libre').value.trim();
        if (!libre) { alert('Debe ingresar el nombre del proveedor.'); return false; }
        document.getElementById('proveedor_label').value = libre;
    }

    var items = [];
    document.querySelectorAll('#items-body tr').forEach(function(tr) {
        var inputs = tr.querySelectorAll('input');
        var label  = inputs[0].value.trim();
        if (!label) return;
        items.push({
            producto_label: label,
            cantidad:       parseFloat(inputs[1].value) || 1,
            precio_unit:    parseFloat(inputs[2].value) || 0,
            subtotal:       parseFloat(inputs[3].value) || 0,
        });
    });
    document.getElementById('items_json').value = JSON.stringify(items);
    if (items.length === 0) { alert('Debe agregar al menos un ítem.'); return false; }
    if (parseFloat(document.getElementById('total').value || 0) <= 0) {
        alert('El total debe ser mayor a cero.'); return false;
    }
    return true;
}

addRow();

// Mostrar/ocultar sección de pago según condición de pago
(function() {
    var selCond = document.getElementById('condicion_pago');
    var pagoSec = document.getElementById('pago-section');
    function actualizarPagoSection() {
        if (selCond && pagoSec) {
            pagoSec.style.display = (selCond.value === 'Contado') ? '' : 'none';
        }
    }
    if (selCond) selCond.addEventListener('change', actualizarPagoSection);
    actualizarPagoSection();
})();

function fnvTogglePago(val) {
    document.querySelectorAll('.fnv-pago-panel').forEach(function(p) { p.style.display = 'none'; });
    if (val === 'efectivo')      { var el = document.getElementById('pago-ef');  if(el) el.style.display = ''; }
    if (val === 'transferencia') { var el = document.getElementById('pago-bco'); if(el) el.style.display = ''; }
    if (val === 'cheque')        { var el = document.getElementById('pago-chq'); if(el) el.style.display = ''; }
}
</script>
