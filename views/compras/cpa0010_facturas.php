<?php
$lista       = $lista       ?? [];
$stats_mes   = $stats_mes   ?? ['cant' => 0, 'total' => 0];
$cuentas_tes = $cuentas_tes ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;

$cuentas_ef  = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'efectivo');
$cuentas_bco = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'banco');

$estadoClass = fn(string $e): string => match($e) {
    'pagada'  => 'badge-green',
    'anulada' => 'badge-gray',
    default   => 'badge-amber',
};
?>

<style>
.fac-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.fac-header h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.fac-header p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }
.fac-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.85rem; margin-bottom:1.5rem; }
.fac-stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.9rem 1.1rem; display:flex; align-items:center; gap:.85rem; }
.fac-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.fac-stat-icon--blue  { background:rgba(37,99,235,.15);  color:#60a5fa; }
.fac-stat-icon--green { background:rgba(22,163,74,.15);  color:#4ade80; }
.fac-stat-icon--amber { background:rgba(217,119,6,.15);  color:#fcd34d; }
.fac-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.fac-stat-value { font-size:1.15rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }
.fac-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.fac-table-head { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.25rem; border-bottom:1px solid var(--border); gap:.75rem; flex-wrap:wrap; }
.fac-table-title { font-size:.88rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:.45rem; }
.fac-table-title i { color:var(--text-dim); font-size:.8rem; }
.fac-search { display:flex; align-items:center; gap:.5rem; background:rgba(31,41,55,.5); border:1px solid var(--border-med); border-radius:8px; padding:.4rem .8rem; color:var(--text-muted); font-size:.84rem; }
.fac-search input { background:none; border:none; outline:none; color:var(--text); font-size:.84rem; font-family:inherit; width:200px; }
.fac-search input::placeholder { color:var(--text-dim); }

/* Modal */
.akim-modal-overlay { display:none; position:fixed; inset:0; z-index:900; background:rgba(0,0,0,.55); align-items:center; justify-content:center; }
.akim-modal-overlay.open { display:flex; }
.akim-modal { background:var(--surface); border:1px solid var(--border-med); border-radius:14px; padding:1.5rem; width:460px; max-width:95vw; max-height:90vh; overflow-y:auto; }
.akim-modal-title { font-size:1rem; font-weight:700; color:var(--text); margin-bottom:1rem; }
.akim-modal-footer { display:flex; gap:.6rem; justify-content:flex-end; margin-top:1.25rem; }
.pago-panel { display:none; }

@media (max-width:768px) { .fac-stats { grid-template-columns:1fr 1fr; } }
</style>

<?php if ($flash_ok): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-circle-check"></i>
    <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="fac-header">
    <div>
        <h1>Facturas de Compra</h1>
        <p>Comprobantes de compras registrados</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="compras" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Compras
        </a>
        <a href="cpa_facturas_nueva" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Nueva Factura
        </a>
    </div>
</div>

<!-- Stats -->
<div class="fac-stats">
    <div class="fac-stat-card">
        <div class="fac-stat-icon fac-stat-icon--blue"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <span class="fac-stat-label">Este mes</span>
            <span class="fac-stat-value"><?php echo (int)$stats_mes['cant']; ?></span>
        </div>
    </div>
    <div class="fac-stat-card">
        <div class="fac-stat-icon fac-stat-icon--green"><i class="fa-solid fa-dollar-sign"></i></div>
        <div>
            <span class="fac-stat-label">Total comprado (mes)</span>
            <span class="fac-stat-value">$ <?php echo number_format((float)$stats_mes['total'], 2, ',', '.'); ?></span>
        </div>
    </div>
    <div class="fac-stat-card">
        <div class="fac-stat-icon fac-stat-icon--amber"><i class="fa-solid fa-list"></i></div>
        <div>
            <span class="fac-stat-label">Total registradas</span>
            <span class="fac-stat-value"><?php echo count($lista); ?></span>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="fac-table-wrap">
    <div class="fac-table-head">
        <span class="fac-table-title">
            <i class="fa-solid fa-clock-rotate-left"></i> Comprobantes
        </span>
        <?php if (!empty($lista)): ?>
        <div class="fac-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="fac-filter" placeholder="Buscar proveedor, nro...">
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($lista)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <p>No hay facturas de compra registradas.</p>
        <a href="cpa_facturas_nueva" class="btn btn-primary btn-sm" style="margin-top:.5rem">
            <i class="fa-solid fa-plus"></i> Registrar primera factura
        </a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl" id="fac-tabla">
            <thead>
                <tr>
                    <th style="width:90px">Tipo/Nro.</th>
                    <th style="width:90px">Fecha</th>
                    <th>Proveedor</th>
                    <th>Condición</th>
                    <th style="text-align:right">Neto</th>
                    <th style="text-align:right">Total</th>
                    <th style="text-align:center">Estado</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $f):
                $estado  = $f['estado'] ?? 'pendiente';
                $anulada = $estado === 'anulada';
                $pagada  = $estado === 'pagada';
            ?>
                <tr style="<?php echo $anulada ? 'opacity:.5' : ''; ?>">
                    <td>
                        <span style="font-weight:600;color:var(--accent-lt);font-size:.8rem">
                            <?php echo htmlspecialchars($f['tipo'] ?? 'A', ENT_QUOTES); ?>
                        </span>
                        <span style="color:var(--text);font-weight:600;font-variant-numeric:tabular-nums">
                            &nbsp;<?php echo htmlspecialchars($f['numero'] ?? '—', ENT_QUOTES); ?>
                        </span>
                    </td>
                    <td style="color:var(--text-muted);font-variant-numeric:tabular-nums">
                        <?php
                        $d = DateTime::createFromFormat('Y-m-d', $f['fecha'] ?? '');
                        echo $d ? $d->format('d/m/Y') : ($f['fecha'] ?? '');
                        ?>
                    </td>
                    <td style="color:var(--text);font-weight:500">
                        <?php echo htmlspecialchars($f['prov_nombre'] ?? $f['proveedor_label'] ?? '—', ENT_QUOTES); ?>
                    </td>
                    <td style="color:var(--text-muted)">
                        <?php echo htmlspecialchars($f['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?>
                    </td>
                    <td style="text-align:right;color:var(--text-muted)">
                        $ <?php echo number_format((float)($f['total_neto'] ?? 0), 2, ',', '.'); ?>
                    </td>
                    <td style="text-align:right;font-weight:700;color:<?php echo $anulada ? 'var(--text-dim)' : 'var(--green-lt)'; ?>">
                        $ <?php echo number_format((float)($f['total'] ?? 0), 2, ',', '.'); ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?php echo $estadoClass($estado); ?>">
                            <?php echo match($estado) { 'pagada' => 'Pagada', 'anulada' => 'Anulada', default => 'Pendiente' }; ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:.3rem;justify-content:center">
                            <?php if ($estado === 'pendiente'): ?>
                            <button class="btn btn-success btn-sm" title="Registrar pago"
                                    onclick="abrirPagar(<?php echo htmlspecialchars(json_encode([
                                        'idx'    => $f['idx'],
                                        'num'    => $f['numero'] ?? '—',
                                        'total'  => (float)($f['total'] ?? 0),
                                        'fecha'  => $f['fecha'],
                                    ]), ENT_QUOTES); ?>)">
                                <i class="fa-solid fa-money-bill-wave"></i> Pagar
                            </button>
                            <?php endif; ?>
                            <?php if (!$anulada): ?>
                            <form method="POST" action="cpa_facturas"
                                  onsubmit="return confirm('¿Anular esta factura?')">
                                <input type="hidden" name="accion" value="anular">
                                <input type="hidden" name="idx" value="<?php echo htmlspecialchars($f['idx'], ENT_QUOTES); ?>">
                                <button class="btn btn-danger btn-sm" title="Anular">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" action="cpa_facturas"
                                  onsubmit="return confirm('¿Eliminar este comprobante? Esta acción no se puede deshacer.')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="idx" value="<?php echo htmlspecialchars($f['idx'], ENT_QUOTES); ?>">
                                <button class="btn btn-secondary btn-sm" title="Eliminar" style="color:#fca5a5">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Modal de pago ───────────────────────────────────────────────────────── -->
<div id="pagar-overlay" class="akim-modal-overlay" onclick="if(event.target===this)cerrarPagar()">
    <div class="akim-modal">
        <p class="akim-modal-title">
            <i class="fa-solid fa-money-bill-wave" style="color:var(--accent)"></i>
            Registrar pago — Factura <span id="mp-num"></span>
        </p>
        <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:1rem">
            Total a pagar: <strong id="mp-total" style="color:var(--text)"></strong>
        </p>

        <form method="POST" action="cpa_facturas">
            <input type="hidden" name="accion" value="pagar">
            <input type="hidden" name="idx" id="mp-idx">

            <div class="akim-form-group">
                <label for="mp-fecha">Fecha de pago</label>
                <input type="date" name="pago_fecha" id="mp-fecha" class="akim-input"
                       value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="akim-form-group">
                <label for="mp-medio">Medio de pago</label>
                <select name="pago_medio" id="mp-medio" class="akim-input" onchange="mpToggle(this.value)">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia bancaria</option>
                    <option value="cheque">Cheque emitido</option>
                </select>
            </div>

            <!-- Efectivo -->
            <div id="mp-ef" class="pago-panel">
                <div class="akim-form-group">
                    <label>Caja / Efectivo</label>
                    <select name="pago_ef_id" class="akim-input">
                        <option value="">— Seleccioná cuenta —</option>
                        <?php foreach ($cuentas_ef as $ct): ?>
                        <option value="<?php echo $ct['id']; ?>"><?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Transferencia -->
            <div id="mp-bco" class="pago-panel" style="display:none">
                <div class="akim-form-group">
                    <label>Cuenta bancaria</label>
                    <select name="pago_bco_id" class="akim-input">
                        <option value="">— Seleccioná cuenta —</option>
                        <?php foreach ($cuentas_bco as $ct): ?>
                        <option value="<?php echo $ct['id']; ?>"><?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Cheque emitido -->
            <div id="mp-chq" class="pago-panel" style="display:none">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div class="akim-form-group">
                        <label>N° de cheque</label>
                        <input type="text" name="pago_chq_numero" class="akim-input" placeholder="00001234">
                    </div>
                    <div class="akim-form-group">
                        <label>Banco</label>
                        <input type="text" name="pago_chq_banco" class="akim-input" placeholder="Ej: Galicia">
                    </div>
                    <div class="akim-form-group">
                        <label>Vencimiento</label>
                        <input type="date" name="pago_chq_fecha_venc" class="akim-input">
                    </div>
                    <div class="akim-form-group">
                        <label>Cuenta bancaria</label>
                        <select name="pago_chq_cta_id" class="akim-input">
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($cuentas_bco as $ct): ?>
                            <option value="<?php echo $ct['id']; ?>"><?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="akim-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarPagar()">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Confirmar pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var fi = document.getElementById('fac-filter');
if (fi) fi.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#fac-tabla tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

window.abrirPagar = function(data) {
    document.getElementById('mp-idx').value         = data.idx;
    document.getElementById('mp-num').textContent   = data.num;
    document.getElementById('mp-total').textContent = '$ ' + data.total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    document.getElementById('mp-fecha').value       = data.fecha || '<?php echo date('Y-m-d'); ?>';
    mpToggle(document.getElementById('mp-medio').value);
    document.getElementById('pagar-overlay').classList.add('open');
};

window.cerrarPagar = function() {
    document.getElementById('pagar-overlay').classList.remove('open');
};

window.mpToggle = function(val) {
    ['mp-ef','mp-bco','mp-chq'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
    var map = { efectivo:'mp-ef', transferencia:'mp-bco', cheque:'mp-chq' };
    var el = document.getElementById(map[val]);
    if (el) el.style.display = '';
};

mpToggle('efectivo');
</script>
