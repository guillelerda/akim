<?php
$cliente         = $cliente         ?? [];
$ventas          = $ventas          ?? [];
$cuentas_tes     = $cuentas_tes     ?? [];
$tarjetas_tes    = $tarjetas_tes    ?? [];
$total_facturado = (float)($total_facturado ?? 0);
$total_cobrado   = (float)($total_cobrado   ?? 0);
$total_pendiente = (float)($total_pendiente ?? 0);
$flash_ok        = $flash_ok   ?? '';
$flash_error     = $flash_error ?? '';

$cuentas_ef  = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'efectivo');
$cuentas_bco = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'banco');

$nombre_cliente = $cliente['razon_social'] ?? 'Cliente';
$fmt = fn(float $v): string => '$ ' . number_format($v, 2, ',', '.');
?>

<style>
/* ── Estado de cuenta cliente ──────────────────────────── */
.cc-wrap   { max-width:1100px; margin:0 auto; padding:1.5rem 1rem 3rem; }

.cc-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:1.5rem; }
.cc-header-left h1 { font-size:1.3rem; font-weight:700; color:var(--text); margin:0 0 .2rem; }
.cc-header-left p  { font-size:.82rem; color:var(--text-dim); margin:0; }
.cc-back { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border-radius:var(--radius); background:var(--surface-2); border:1px solid var(--border); color:var(--text-dim); font-size:.82rem; text-decoration:none; transition:background .15s,color .15s; }
.cc-back:hover { background:var(--surface-3); color:var(--text); }

/* Info rápida del cliente */
.cc-info { display:flex; gap:1.5rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.cc-info-item { font-size:.8rem; color:var(--text-dim); }
.cc-info-item strong { color:var(--text); }

/* KPIs */
.cc-kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; margin-bottom:1.5rem; }
.cc-kpi  { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:.9rem 1.1rem; }
.cc-kpi-label { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--text-dim); margin-bottom:.3rem; }
.cc-kpi-val   { font-size:1.25rem; font-weight:700; color:var(--text); }
.cc-kpi-val.green  { color:#4ade80; }
.cc-kpi-val.amber  { color:#fbbf24; }
.cc-kpi-val.blue   { color:var(--accent-lt); }

/* Flash */
.cc-flash-ok  { background:#14532d20; border:1px solid #16a34a40; border-radius:var(--radius); padding:.7rem 1rem; color:#4ade80; margin-bottom:1rem; }
.cc-flash-err { background:#7f1d1d20; border:1px solid #dc262640; border-radius:var(--radius); padding:.7rem 1rem; color:#f87171; margin-bottom:1rem; }

/* Tabla */
.cc-table-wrap { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.cc-table-head { display:flex; align-items:center; justify-content:space-between; padding:.85rem 1.1rem; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:.5rem; }
.cc-table-title { font-size:.85rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:.4rem; }
.cc-search { display:flex; align-items:center; gap:.5rem; background:rgba(31,41,55,.5); border:1px solid var(--border); border-radius:8px; padding:.38rem .75rem; }
.cc-search i { color:var(--text-dim); font-size:.8rem; }
.cc-search input { background:none; border:none; outline:none; color:var(--text); font-size:.83rem; font-family:inherit; width:160px; }

.cc-tbl { width:100%; border-collapse:collapse; font-size:.84rem; }
.cc-tbl thead th { background:var(--surface-2); color:var(--text-dim); font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; padding:.6rem .9rem; white-space:nowrap; border-bottom:1px solid var(--border); }
.cc-tbl tbody tr { border-bottom:1px solid var(--border); transition:background .1s; }
.cc-tbl tbody tr:last-child { border-bottom:none; }
.cc-tbl tbody tr:hover { background:rgba(255,255,255,.02); }
.cc-tbl td { padding:.55rem .9rem; color:var(--text-dim); }
.cc-tbl td.tx { color:var(--text); }

/* Estado badges */
.badge-cc { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .5rem; border-radius:99px; font-size:.68rem; font-weight:600; }
.badge-cc::before { content:''; width:5px; height:5px; border-radius:50%; }
.badge-emitida  { background:rgba(37,99,235,.12);  color:#93c5fd; }
.badge-emitida::before  { background:#60a5fa; }
.badge-cobrada  { background:rgba(22,163,74,.12);  color:#86efac; }
.badge-cobrada::before  { background:#4ade80; }
.badge-anulada  { background:rgba(107,114,128,.1); color:#6b7280; text-decoration:line-through; }
.badge-anulada::before  { background:#6b7280; }

.cc-empty { text-align:center; padding:3rem 1rem; color:var(--text-dim); }
.cc-empty i { font-size:2rem; opacity:.2; display:block; margin-bottom:.6rem; }

/* Modal cobro (reutilizado de menu_ventas) */
.akim-modal-overlay { display:none; position:fixed; inset:0; z-index:900; background:rgba(0,0,0,.55); align-items:center; justify-content:center; }
.akim-modal-overlay.open { display:flex; }
.akim-modal { background:var(--surface); border:1px solid var(--border-med); border-radius:14px; padding:1.5rem; width:460px; max-width:95vw; max-height:90vh; overflow-y:auto; }
.akim-modal-title { font-size:1rem; font-weight:700; color:var(--text); margin-bottom:1rem; }
.akim-modal-footer { display:flex; gap:.6rem; justify-content:flex-end; margin-top:1.25rem; }
.cobro-panel { display:none; }

@media (max-width:640px) { .cc-kpis { grid-template-columns:1fr; } }
</style>

<div class="cc-wrap">

    <!-- Header -->
    <div class="cc-header">
        <div class="cc-header-left">
            <h1><i class="fa-solid fa-address-card" style="color:var(--accent-lt);margin-right:.45rem;font-size:1.05rem"></i>
                <?php echo htmlspecialchars($nombre_cliente, ENT_QUOTES); ?>
            </h1>
            <p>Estado de cuenta del cliente</p>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <a href="vta_clientes_edit" onclick="sessionStorage.setItem('cc_back','1')"
               class="cc-back">
                <i class="fa-solid fa-pen"></i> Editar
            </a>
            <a href="vta_clientes" class="cc-back">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- Info rápida -->
    <?php
    $info_items = [];
    if (!empty($cliente['cuit']))            $info_items[] = ['CUIT', $cliente['cuit']];
    if (!empty($cliente['condicion_fiscal'])) $info_items[] = ['Condición IVA', $cliente['condicion_fiscal']];
    if (!empty($cliente['condicion_pago']))   $info_items[] = ['Condición pago', $cliente['condicion_pago']];
    if (!empty($cliente['telefono']))         $info_items[] = ['Tel.', $cliente['telefono']];
    if (!empty($cliente['email']))            $info_items[] = ['Email', $cliente['email']];
    if (!empty($cliente['localidad']))        $info_items[] = ['Localidad', $cliente['localidad'] . (!empty($cliente['provincia']) ? ', ' . $cliente['provincia'] : '')];
    if ($info_items): ?>
    <div class="cc-info">
        <?php foreach ($info_items as [$label, $val]): ?>
        <div class="cc-info-item"><strong><?php echo $label; ?>:</strong> <?php echo htmlspecialchars($val, ENT_QUOTES); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($flash_ok):  ?><div class="cc-flash-ok"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
    <?php if ($flash_error):?><div class="cc-flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

    <!-- KPIs -->
    <div class="cc-kpis">
        <div class="cc-kpi">
            <div class="cc-kpi-label">Total facturado</div>
            <div class="cc-kpi-val blue"><?php echo $fmt($total_facturado); ?></div>
        </div>
        <div class="cc-kpi">
            <div class="cc-kpi-label">Total cobrado</div>
            <div class="cc-kpi-val green"><?php echo $fmt($total_cobrado); ?></div>
        </div>
        <div class="cc-kpi">
            <div class="cc-kpi-label">Saldo pendiente</div>
            <div class="cc-kpi-val<?php echo $total_pendiente > 0 ? ' amber' : ''; ?>">
                <?php echo $fmt($total_pendiente); ?>
            </div>
        </div>
    </div>

    <!-- Tabla de comprobantes -->
    <div class="cc-table-wrap">
        <div class="cc-table-head">
            <span class="cc-table-title">
                <i class="fa-solid fa-receipt"></i> Comprobantes
                <span style="font-weight:400;color:var(--text-dim);font-size:.78rem">(<?php echo count($ventas); ?> registros)</span>
            </span>
            <?php if (!empty($ventas)): ?>
            <div class="cc-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="cc-filter" placeholder="Filtrar...">
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($ventas)): ?>
        <div class="cc-empty">
            <i class="fa-solid fa-receipt"></i>
            Este cliente no tiene comprobantes registrados.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="cc-tbl" id="cc-tabla">
                <thead>
                    <tr>
                        <th>Nro.</th>
                        <th>Fecha</th>
                        <th>Condición</th>
                        <th style="text-align:right">Total</th>
                        <th style="text-align:center">Estado</th>
                        <th style="text-align:center"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ventas as $v):
                    $estado  = $v['estado'] ?? 'emitida';
                    $anulada = $estado === 'anulada';
                ?>
                    <tr style="<?php echo $anulada ? 'opacity:.45' : ''; ?>">
                        <td class="tx" style="font-variant-numeric:tabular-nums;font-weight:600">
                            #<?php echo str_pad((int)($v['numero'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td style="white-space:nowrap;font-size:.81rem">
                            <?php
                            $d = \DateTime::createFromFormat('Y-m-d', $v['fecha'] ?? '');
                            echo $d ? $d->format('d/m/Y') : ($v['fecha'] ?? '');
                            ?>
                        </td>
                        <td style="font-size:.81rem"><?php echo htmlspecialchars($v['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?></td>
                        <td style="text-align:right;font-weight:700;color:<?php echo $anulada ? 'var(--text-dim)' : '#4ade80'; ?>">
                            <?php echo $fmt((float)($v['total'] ?? 0)); ?>
                        </td>
                        <td style="text-align:center">
                            <span class="badge-cc badge-<?php echo htmlspecialchars($estado, ENT_QUOTES); ?>">
                                <?php echo match($estado) { 'cobrada' => 'Cobrada', 'anulada' => 'Anulada', default => 'Pendiente' }; ?>
                            </span>
                        </td>
                        <td style="text-align:center">
                            <div style="display:flex;gap:.3rem;justify-content:center">
                                <a href="vta_comprobante?idx=<?php echo urlencode($v['idx']); ?>"
                                   class="btn btn-secondary btn-sm" title="Ver comprobante" target="_blank">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>
                                <?php if ($estado === 'emitida'): ?>
                                <button class="btn btn-success btn-sm" title="Registrar cobro"
                                        onclick="abrirCobrar(<?php echo htmlspecialchars(json_encode([
                                            'idx'   => $v['idx'],
                                            'num'   => (int)$v['numero'],
                                            'total' => (float)$v['total'],
                                            'fecha' => $v['fecha'],
                                        ]), ENT_QUOTES); ?>)">
                                    <i class="fa-solid fa-hand-holding-dollar"></i> Cobrar
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Modal cobro (idéntico al de menu_ventas) ───────────────────────── -->
<div id="cobrar-overlay" class="akim-modal-overlay" onclick="if(event.target===this)cerrarCobrar()">
    <div class="akim-modal">
        <p class="akim-modal-title">
            <i class="fa-solid fa-hand-holding-dollar" style="color:var(--accent)"></i>
            Registrar cobro — Venta <span id="mc-num"></span>
        </p>
        <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:1rem">
            Total: <strong id="mc-total" style="color:var(--text)"></strong>
        </p>

        <form method="POST" action="ventas" id="cobrar-form">
            <input type="hidden" name="accion" value="cobrar">
            <input type="hidden" name="idx" id="mc-idx">

            <div class="akim-form-group">
                <label>Fecha de cobro</label>
                <input type="date" name="cobro_fecha" id="mc-fecha" class="akim-input"
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="akim-form-group">
                <label>Medio de cobro</label>
                <select name="cobro_medio" id="mc-medio" class="akim-input" onchange="mcToggle(this.value)">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="cheque">Cheque recibido</option>
                    <?php if (!empty($tarjetas_tes)): ?><option value="tarjeta">Tarjeta</option><?php endif; ?>
                </select>
            </div>

            <div id="mc-ef" class="cobro-panel" style="display:block">
                <div class="akim-form-group">
                    <label>Caja / Efectivo</label>
                    <select name="cobro_ef_id" class="akim-input">
                        <option value="">— Seleccioná cuenta —</option>
                        <?php foreach ($cuentas_ef as $ct): ?>
                        <option value="<?php echo $ct['id']; ?>"><?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="mc-bco" class="cobro-panel" style="display:none">
                <div class="akim-form-group">
                    <label>Cuenta bancaria</label>
                    <select name="cobro_bco_id" class="akim-input">
                        <option value="">— Seleccioná cuenta —</option>
                        <?php foreach ($cuentas_bco as $ct): ?>
                        <option value="<?php echo $ct['id']; ?>"><?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="mc-chq" class="cobro-panel" style="display:none">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div class="akim-form-group"><label>N° cheque</label><input type="text" name="cobro_chq_numero" class="akim-input"></div>
                    <div class="akim-form-group"><label>Banco emisor</label><input type="text" name="cobro_chq_banco" class="akim-input"></div>
                    <div class="akim-form-group"><label>Vencimiento</label><input type="date" name="cobro_chq_fecha_venc" class="akim-input"></div>
                    <div class="akim-form-group">
                        <label>Cuenta a acreditar</label>
                        <select name="cobro_chq_bco_id" class="akim-input">
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($cuentas_bco as $ct): ?>
                            <option value="<?php echo $ct['id']; ?>"><?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php if (!empty($tarjetas_tes)): ?>
            <div id="mc-tarjeta" class="cobro-panel" style="display:none">
                <div class="akim-form-group">
                    <label>Terminal / Red</label>
                    <select name="cobro_tarjeta_id" class="akim-input">
                        <option value="">— Seleccioná —</option>
                        <?php foreach ($tarjetas_tes as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nombre'].' — '.$t['red'], ENT_QUOTES); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div class="akim-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarCobrar()">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Confirmar cobro</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    // Filtro tabla
    var fi = document.getElementById('cc-filter');
    if (fi) fi.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#cc-tabla tbody tr').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Modal cobro
    window.abrirCobrar = function(data) {
        document.getElementById('mc-idx').value = data.idx;
        document.getElementById('mc-num').textContent = '#' + String(data.num).padStart(4,'0');
        document.getElementById('mc-total').textContent = '$ ' + data.total.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
        document.getElementById('mc-fecha').value = data.fecha || '<?php echo date('Y-m-d'); ?>';
        mcToggle('efectivo');
        document.getElementById('cobrar-overlay').classList.add('open');
    };
    window.cerrarCobrar = function() { document.getElementById('cobrar-overlay').classList.remove('open'); };
    window.mcToggle = function(val) {
        ['mc-ef','mc-bco','mc-chq','mc-tarjeta'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        var map = {efectivo:'mc-ef', transferencia:'mc-bco', cheque:'mc-chq', tarjeta:'mc-tarjeta'};
        var el = document.getElementById(map[val]);
        if (el) el.style.display = '';
    };
    mcToggle('efectivo');
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarCobrar(); });
})();
</script>
