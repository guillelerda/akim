<?php
$ventas          = $ventas          ?? [];
$stats_mes       = $stats_mes       ?? ['cant' => 0, 'total' => 0];
$cuentas_tes     = $cuentas_tes     ?? [];
$tarjetas_tes    = $tarjetas_tes    ?? [];
$arca_habilitado = $arca_habilitado ?? false;
$arca_pv         = $arca_pv         ?? 1;

$cuentas_ef  = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'efectivo');
$cuentas_bco = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'banco');
?>

<link rel="stylesheet" href="includes/plugins/plugins/select2-4.1.0/css/select2.min.css">
<script src="includes/plugins/plugins/select2-4.1.0/js/select2.min.js"></script>

<style>
.vta-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.vta-header-text h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.vta-header-text p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }

.vta-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.85rem; margin-bottom:1.5rem; }
.vta-stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.9rem 1.1rem; display:flex; align-items:center; gap:.85rem; }
.vta-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.vta-stat-icon--blue   { background:rgba(37,99,235,.15);  color:#60a5fa; }
.vta-stat-icon--green  { background:rgba(22,163,74,.15);  color:#4ade80; }
.vta-stat-icon--purple { background:rgba(124,58,237,.15); color:#a78bfa; }
.vta-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.vta-stat-value { font-size:1.15rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }

.vta-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.vta-table-header { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.25rem; border-bottom:1px solid var(--border); gap:.75rem; flex-wrap:wrap; }
.vta-table-title { font-size:.88rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:.45rem; }
.vta-table-title i { color:var(--text-dim); font-size:.8rem; }

.vta-search { display:flex; align-items:center; gap:.5rem; background:rgba(31,41,55,.5); border:1px solid var(--border-med); border-radius:8px; padding:.4rem .8rem; color:var(--text-muted); font-size:.84rem; }
.vta-search input { background:none; border:none; outline:none; color:var(--text); font-size:.84rem; font-family:inherit; width:200px; }
.vta-search input::placeholder { color:var(--text-dim); }

.estado-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .6rem; border-radius:99px; font-size:.7rem; font-weight:600; }
.estado-badge::before { content:''; width:6px; height:6px; border-radius:50%; }
.estado-emitida  { background:rgba(37,99,235,.15);  color:#93c5fd; }
.estado-emitida::before  { background:#60a5fa; }
.estado-cobrada  { background:rgba(22,163,74,.15);  color:#86efac; }
.estado-cobrada::before  { background:#4ade80; }
.estado-anulada  { background:rgba(107,114,128,.12);color:#6b7280; text-decoration:line-through; }
.estado-anulada::before  { background:#6b7280; }

.vta-btn-afip { background:linear-gradient(135deg,#16a34a,#15803d); border-color:#15803d; }
.vta-btn-afip:hover { background:linear-gradient(135deg,#15803d,#166534); }
.vta-afip-nro { display:inline-flex; align-items:baseline; gap:.25rem; color:#4ade80; font-size:.82rem; font-weight:600; }
.vta-afip-nro strong { font-size:.9rem; }
.vta-afip-num-str { font-family:'Courier New',monospace; font-size:.78rem; letter-spacing:.02em; color:#86efac; }

/* Modal */
.akim-modal-overlay { display:none; position:fixed; inset:0; z-index:900; background:rgba(0,0,0,.55); align-items:center; justify-content:center; }
.akim-modal-overlay.open { display:flex; }
.akim-modal { background:var(--surface); border:1px solid var(--border-med); border-radius:14px; padding:1.5rem; width:460px; max-width:95vw; max-height:90vh; overflow-y:auto; }
.akim-modal-title { font-size:1rem; font-weight:700; color:var(--text); margin-bottom:1rem; }
.akim-modal-footer { display:flex; gap:.6rem; justify-content:flex-end; margin-top:1.25rem; }
.cobro-panel { display:none; }

/* ── Select2 en modal cobro ── */
#cobrar-overlay .select2-container { width:100% !important; }
#cobrar-overlay .select2-container--default .select2-selection--single {
    background:rgba(31,41,55,.55); border:1px solid rgba(255,255,255,.13);
    border-radius:var(--radius,8px); height:40px; display:flex; align-items:center;
    padding:0 2.75rem 0 .95rem; cursor:pointer;
    transition:border-color .18s,box-shadow .18s; position:relative;
}
#cobrar-overlay .select2-container--default.select2-container--focus .select2-selection--single,
#cobrar-overlay .select2-container--default.select2-container--open  .select2-selection--single {
    border-color:rgba(59,130,246,.55); box-shadow:0 0 0 3px rgba(59,130,246,.12); outline:none;
}
#cobrar-overlay .select2-container--default .select2-selection--single .select2-selection__rendered {
    color:#f9fafb; font-size:.9rem; font-family:'Segoe UI',system-ui,sans-serif;
    line-height:1; padding:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
}
#cobrar-overlay .select2-container--default .select2-selection--single .select2-selection__placeholder { color:#4b5563; }
#cobrar-overlay .select2-container--default .select2-selection--single .select2-selection__arrow {
    position:absolute; top:0; right:0; height:100%; width:2.5rem;
    display:flex; align-items:center; justify-content:center;
}
#cobrar-overlay .select2-container--default .select2-selection--single .select2-selection__arrow b {
    display:block; width:16px; height:16px; border:none; margin:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-size:contain; background-repeat:no-repeat; background-position:center; transition:transform .18s;
}
#cobrar-overlay .select2-container--default.select2-container--open .select2-selection__arrow b {
    transform:rotate(180deg);
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}

@media (max-width:768px) { .vta-stats { grid-template-columns:1fr 1fr; } }
/* Toast flotante de notificación */
.akim-toast {
    position: fixed; top: 1.5rem; right: 1.5rem; z-index: 999999;
    min-width: 340px; max-width: 480px; padding: 1rem 1.15rem;
    border-radius: 10px; display: flex; align-items: flex-start; gap: .85rem;
    box-shadow: 0 12px 30px rgba(0,0,0,.5);
    animation: toastSlideIn .25s ease-out;
}
@keyframes toastSlideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.akim-toast--error {
    background: #1e131d; border: 1.5px solid #ef4444; color: #fecaca;
}
.akim-toast--error .akim-toast-icon { color: #ef4444; font-size: 1.35rem; flex-shrink: 0; margin-top: 1px; }
.akim-toast--success {
    background: #0f241d; border: 1.5px solid #10b981; color: #a7f3d0;
}
.akim-toast--success .akim-toast-icon { color: #10b981; font-size: 1.35rem; flex-shrink: 0; margin-top: 1px; }
.akim-toast-body { flex: 1; }
.akim-toast-title { font-weight: 700; font-size: .88rem; margin-bottom: .2rem; color: #fff; }
.akim-toast-msg { font-size: .8rem; line-height: 1.4; word-break: break-word; }
.akim-toast-close {
    background: transparent; border: none; color: currentColor; opacity: .7;
    font-size: 1.25rem; cursor: pointer; padding: 0 .2rem; line-height: 1;
}
.akim-toast-close:hover { opacity: 1; }
</style>

<style>
/* Select2 dropdown (appended to body, fuera del modal) */
.cobro-s2-drop.select2-dropdown {
    background:#0f172a; border:1px solid rgba(255,255,255,.1); border-radius:12px;
    box-shadow:0 16px 40px rgba(0,0,0,.6); overflow:hidden; margin-top:4px; z-index:99999 !important;
}
.cobro-s2-drop .select2-results { padding:.3rem 0; }
.cobro-s2-drop .select2-results__options { max-height:220px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,.1) transparent; }
.cobro-s2-drop .select2-results__options::-webkit-scrollbar { width:4px; }
.cobro-s2-drop .select2-results__options::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }
.cobro-s2-drop .select2-container--default .select2-results__option {
    padding:.52rem 1rem; font-size:.87rem; font-family:'Segoe UI',system-ui,sans-serif; color:#94a3b8; cursor:pointer;
}
.cobro-s2-drop .select2-container--default .select2-results__option--highlighted[aria-selected] { background:rgba(37,99,235,.18); color:#e2e8f0; }
.cobro-s2-drop .select2-container--default .select2-results__option[aria-selected=true] { background:rgba(37,99,235,.12); color:#93c5fd; font-weight:600; }
/* Toast flotante de notificación */
.akim-toast {
    position: fixed; top: 1.5rem; right: 1.5rem; z-index: 999999;
    min-width: 340px; max-width: 480px; padding: 1rem 1.15rem;
    border-radius: 10px; display: flex; align-items: flex-start; gap: .85rem;
    box-shadow: 0 12px 30px rgba(0,0,0,.5);
    animation: toastSlideIn .25s ease-out;
}
@keyframes toastSlideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.akim-toast--error {
    background: #1e131d; border: 1.5px solid #ef4444; color: #fecaca;
}
.akim-toast--error .akim-toast-icon { color: #ef4444; font-size: 1.35rem; flex-shrink: 0; margin-top: 1px; }
.akim-toast--success {
    background: #0f241d; border: 1.5px solid #10b981; color: #a7f3d0;
}
.akim-toast--success .akim-toast-icon { color: #10b981; font-size: 1.35rem; flex-shrink: 0; margin-top: 1px; }
.akim-toast-body { flex: 1; }
.akim-toast-title { font-weight: 700; font-size: .88rem; margin-bottom: .2rem; color: #fff; }
.akim-toast-msg { font-size: .8rem; line-height: 1.4; word-break: break-word; }
.akim-toast-close {
    background: transparent; border: none; color: currentColor; opacity: .7;
    font-size: 1.25rem; cursor: pointer; padding: 0 .2rem; line-height: 1;
}
.akim-toast-close:hover { opacity: 1; }
</style>

<!-- Toast flotante de notificación -->
<?php if (!empty($flash_error)): ?>
<div class="akim-toast akim-toast--error" id="vta-toast">
    <div class="akim-toast-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
    <div class="akim-toast-body">
        <div class="akim-toast-title">Atención — Falló emisión AFIP</div>
        <div class="akim-toast-msg"><?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?></div>
    </div>
    <button type="button" class="akim-toast-close" onclick="document.getElementById('vta-toast').remove()">×</button>
</div>
<?php endif; ?>

<?php if (!empty($flash_ok)): ?>
<div class="akim-toast akim-toast--success" id="vta-toast-ok">
    <div class="akim-toast-icon"><i class="fa-solid fa-circle-check"></i></div>
    <div class="akim-toast-body">
        <div class="akim-toast-title">Operación exitosa</div>
        <div class="akim-toast-msg"><?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?></div>
    </div>
    <button type="button" class="akim-toast-close" onclick="document.getElementById('vta-toast-ok').remove()">×</button>
</div>
<?php endif; ?>

<!-- Flash messages inline -->
<?php if (!empty($flash_ok)): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-circle-check"></i>
    <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="vta-header">
    <div class="vta-header-text">
        <h1>Ventas</h1>
        <p>Historial de comprobantes emitidos</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="vta_clientes" class="btn btn-secondary">
            <i class="fa-solid fa-users"></i> Clientes
        </a>
        <?php if ($arca_habilitado): ?>
        <a href="vta_wizard_step1?modo=manual" class="btn btn-secondary">
            <i class="fa-solid fa-file-lines"></i> Ticket / Remito
        </a>
        <a href="vta_wizard_step1?modo=fiscal" class="btn btn-primary vta-btn-afip">
            <i class="fa-solid fa-file-invoice-dollar"></i> Factura AFIP
        </a>
        <?php else: ?>
        <a href="vta_wizard_step1?modo=manual" class="btn btn-primary">
            <i class="fa-solid fa-bolt"></i> Nueva Venta
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats -->
<div class="vta-stats">
    <div class="vta-stat-card">
        <div class="vta-stat-icon vta-stat-icon--blue"><i class="fa-solid fa-receipt"></i></div>
        <div>
            <span class="vta-stat-label">Ventas este mes</span>
            <span class="vta-stat-value"><?php echo (int)$stats_mes['cant']; ?></span>
        </div>
    </div>
    <div class="vta-stat-card">
        <div class="vta-stat-icon vta-stat-icon--green"><i class="fa-solid fa-dollar-sign"></i></div>
        <div>
            <span class="vta-stat-label">Total del mes</span>
            <span class="vta-stat-value">$ <?php echo number_format((float)$stats_mes['total'], 2, ',', '.'); ?></span>
        </div>
    </div>
    <div class="vta-stat-card">
        <div class="vta-stat-icon vta-stat-icon--purple"><i class="fa-solid fa-list"></i></div>
        <div>
            <span class="vta-stat-label">Total registradas</span>
            <span class="vta-stat-value"><?php echo count($ventas); ?></span>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="vta-table-wrap">
    <div class="vta-table-header">
        <span class="vta-table-title">
            <i class="fa-solid fa-clock-rotate-left"></i> Comprobantes emitidos
        </span>
        <?php if (!empty($ventas)): ?>
        <div class="vta-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="vta-filter" placeholder="Buscar cliente, nro...">
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($ventas)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-receipt"></i>
        <p>Todavía no hay ventas registradas.</p>
        <a href="vta_wizard_step1" class="btn btn-primary btn-sm" style="margin-top:.5rem">
            <i class="fa-solid fa-bolt"></i> Hacer primera venta
        </a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl" id="vta-tabla">
            <thead>
                <tr>
                    <th style="width:60px">Nro.</th>
                    <th style="width:90px">Fecha</th>
                    <th>Cliente</th>
                    <th>Condición</th>
                    <th style="text-align:right">Total</th>
                    <th style="text-align:center">Estado</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ventas as $v):
                $estado  = $v['estado'] ?? 'emitida';
                $anulada = $estado === 'anulada';
                $cobrada = $estado === 'cobrada';
            ?>
                <tr style="<?php echo $anulada ? 'opacity:.5' : ''; ?>">
                    <td>
                        <?php if (!empty($v['cae'])): ?>
                        <?php
                            $tipo_letra = match((int)($v['tipo_cbte'] ?? 0)) { 1 => 'A', 11 => 'C', default => 'B' };
                            $pv_fmt  = str_pad($arca_pv, 5, '0', STR_PAD_LEFT);
                            $nro_fmt = str_pad((int)($v['nro_afip'] ?? 0), 8, '0', STR_PAD_LEFT);
                        ?>
                        <span class="vta-afip-nro">
                            F<strong><?php echo $tipo_letra; ?></strong>
                            <span class="vta-afip-num-str"><?php echo "{$pv_fmt}-{$nro_fmt}"; ?></span>
                        </span>
                        <?php else: ?>
                        <span style="font-weight:600;color:var(--text);font-variant-numeric:tabular-nums">
                            #<?php echo str_pad((int)($v['numero'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted);font-variant-numeric:tabular-nums">
                        <?php
                        $d = DateTime::createFromFormat('Y-m-d', $v['fecha'] ?? '');
                        echo $d ? $d->format('d/m/Y') : ($v['fecha'] ?? '');
                        ?>
                    </td>
                    <td style="color:var(--text);font-weight:500">
                        <?php echo htmlspecialchars($v['cliente_nombre'] ?? 'Consumidor final', ENT_QUOTES); ?>
                    </td>
                    <td style="color:var(--text-muted)">
                        <?php echo htmlspecialchars($v['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?>
                    </td>
                    <td style="text-align:right;font-weight:700;color:<?php echo $anulada ? 'var(--text-dim)' : 'var(--green-lt)'; ?>">
                        $ <?php echo number_format((float)($v['total'] ?? 0), 2, ',', '.'); ?>
                    </td>
                    <td style="text-align:center">
                        <?php if (!empty($v['cae'])): ?>
                            <span class="estado-badge estado-cobrada" style="background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3)">
                                <i class="fa-solid fa-circle-check"></i> AFIP OK
                            </span>
                        <?php elseif ($estado === 'rechazada' || ($v['modo_factura'] ?? '') === 'fiscal'): ?>
                            <span class="estado-badge" style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)" title="Comprobante no autorizado por AFIP">
                                <i class="fa-solid fa-triangle-exclamation"></i> Rechazada
                            </span>
                        <?php else: ?>
                            <span class="estado-badge estado-<?php echo htmlspecialchars($estado, ENT_QUOTES); ?>">
                                <?php echo match($estado) { 'cobrada' => 'Cobrada', 'anulada' => 'Anulada', default => 'Emitida' }; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:.3rem;justify-content:center;align-items:center">
                            <a href="vta_comprobante?idx=<?php echo urlencode($v['idx']); ?>"
                               class="btn btn-secondary btn-sm" title="Ver comprobante" target="_blank">
                                <i class="fa-solid fa-file-invoice"></i>
                            </a>
                            <?php if ($estado === 'emitida' && empty($v['cae'])): ?>
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

                            <?php if (empty($v['cae'])): ?>
                            <!-- Botón para eliminar comprobante NO AUTORIZADO / Ticket interno -->
                            <form method="POST" action="ventas" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar definitivamente el comprobante #<?php echo str_pad((int)($v['numero'] ?? 0), 4, '0', STR_PAD_LEFT); ?>?\nSe repondrá el stock de los productos.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="idx" value="<?php echo htmlspecialchars($v['idx'], ENT_QUOTES); ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Eliminar comprobante no autorizado"
                                        style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);padding:.28rem .55rem">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span title="Factura electrónica — solo anulable por Nota de Crédito en AFIP"
                                  style="padding:.28rem .55rem;border-radius:6px;font-size:.75rem;
                                         color:var(--text-dim);border:1px solid var(--border);cursor:default">
                                <i class="fa-solid fa-file-circle-minus"></i> NC
                            </span>
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

<!-- ── Modal de cobro ──────────────────────────────────────────────────────── -->
<div id="cobrar-overlay" class="akim-modal-overlay" onclick="if(event.target===this)cerrarCobrar()">
    <div class="akim-modal">
        <p class="akim-modal-title">
            <i class="fa-solid fa-hand-holding-dollar" style="color:var(--accent)"></i>
            Registrar cobro — Venta <span id="mc-num"></span>
        </p>
        <p style="font-size:.84rem;color:var(--text-muted);margin-bottom:1rem">
            Total a cobrar: <strong id="mc-total" style="color:var(--text)"></strong>
        </p>

        <form method="POST" action="ventas" id="cobrar-form">
            <input type="hidden" name="accion" value="cobrar">
            <input type="hidden" name="idx" id="mc-idx">

            <div class="akim-form-group">
                <label for="mc-fecha">Fecha de cobro</label>
                <input type="date" name="cobro_fecha" id="mc-fecha" class="akim-input"
                       value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="akim-form-group">
                <label for="mc-medio">Medio de cobro</label>
                <select name="cobro_medio" id="mc-medio" class="akim-input">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia bancaria</option>
                    <option value="cheque">Cheque recibido</option>
                    <?php if (!empty($tarjetas_tes)): ?>
                    <option value="tarjeta">Tarjeta</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Efectivo -->
            <div id="mc-ef" class="cobro-panel">
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

            <!-- Transferencia -->
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

            <!-- Cheque -->
            <div id="mc-chq" class="cobro-panel" style="display:none">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div class="akim-form-group">
                        <label>N° de cheque</label>
                        <input type="text" name="cobro_chq_numero" class="akim-input" placeholder="00001234">
                    </div>
                    <div class="akim-form-group">
                        <label>Banco emisor</label>
                        <input type="text" name="cobro_chq_banco" class="akim-input" placeholder="Ej: Galicia">
                    </div>
                    <div class="akim-form-group">
                        <label>Vencimiento</label>
                        <input type="date" name="cobro_chq_fecha_venc" class="akim-input">
                    </div>
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

            <!-- Tarjeta -->
            <?php if (!empty($tarjetas_tes)): ?>
            <div id="mc-tarjeta" class="cobro-panel" style="display:none">
                <div class="akim-form-group">
                    <label>Terminal / Red</label>
                    <select name="cobro_tarjeta_id" class="akim-input">
                        <option value="">— Seleccioná terminal —</option>
                        <?php foreach ($tarjetas_tes as $t): ?>
                        <option value="<?php echo $t['id']; ?>">
                            <?php echo htmlspecialchars($t['nombre'] . ' — ' . $t['red'], ENT_QUOTES); ?>
                            (<?php echo $t['plazo_dias']; ?> días, <?php echo $t['descuento_pct']; ?>% desc.)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div class="akim-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarCobrar()">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Confirmar cobro
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(function () {
    // ── Filtro de tabla ─────────────────────────────────────────────────────
    var fi = document.getElementById('vta-filter');
    if (fi) fi.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#vta-tabla tbody tr').forEach(function (tr) {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // ── Opciones base Select2 ───────────────────────────────────────────────
    var s2Base = {
        minimumResultsForSearch: Infinity,
        width: '100%',
        dropdownCssClass: 'cobro-s2-drop',
        language: { noResults: function () { return 'Sin opciones'; } }
    };

    // Inicializa un select con Select2 solo si aún no fue inicializado
    function s2Init(sel, placeholder) {
        $(sel).not('.select2-hidden-accessible').select2($.extend({}, s2Base, { placeholder: placeholder }));
    }

    // ── Select2: medio de cobro (siempre visible) ───────────────────────────
    s2Init('#mc-medio', 'Seleccioná medio');
    $('#mc-medio').on('change', function () {
        mcToggle($(this).val());
    });

    // ── Toggle de paneles + init diferido de Select2 ────────────────────────
    // Se inicializa cada select recién cuando su panel se hace visible,
    // evitando el bug de Select2 con elementos ocultos (width:0).
    window.mcToggle = function (val) {
        ['mc-ef', 'mc-bco', 'mc-chq', 'mc-tarjeta'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        var map = { efectivo: 'mc-ef', transferencia: 'mc-bco', cheque: 'mc-chq', tarjeta: 'mc-tarjeta' };
        var panelEl = document.getElementById(map[val]);
        if (!panelEl) return;
        panelEl.style.display = 'block';

        // Inicializar Select2 dentro del panel ahora que es visible
        if (val === 'efectivo')      s2Init('select[name="cobro_ef_id"]',      '— Seleccioná cuenta —');
        if (val === 'transferencia') s2Init('select[name="cobro_bco_id"]',      '— Seleccioná cuenta —');
        if (val === 'cheque')        s2Init('select[name="cobro_chq_bco_id"]',  '— Seleccioná cuenta —');
        if (val === 'tarjeta')       s2Init('select[name="cobro_tarjeta_id"]',  '— Seleccioná terminal —');
    };

    // ── Abrir / cerrar modal ────────────────────────────────────────────────
    window.abrirCobrar = function (data) {
        document.getElementById('mc-idx').value         = data.idx;
        document.getElementById('mc-num').textContent   = '#' + String(data.num).padStart(4, '0');
        document.getElementById('mc-total').textContent = '$ ' + data.total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('mc-fecha').value       = data.fecha || '<?php echo date('Y-m-d'); ?>';
        $('#mc-medio').val('efectivo').trigger('change');
        document.getElementById('cobrar-overlay').classList.add('open');
    };

    window.cerrarCobrar = function () {
        document.getElementById('cobrar-overlay').classList.remove('open');
    };

    // ── Validación antes de confirmar cobro ────────────────────────────────
    $('#cobrar-form').on('submit', function (e) {
        var medio = $('#mc-medio').val();
        var err   = '';

        if (medio === 'efectivo'      && !$('select[name="cobro_ef_id"]').val())      err = 'Seleccioná una caja o cuenta de efectivo.';
        if (medio === 'transferencia' && !$('select[name="cobro_bco_id"]').val())     err = 'Seleccioná una cuenta bancaria destino.';
        if (medio === 'cheque'        && !$('select[name="cobro_chq_bco_id"]').val()) err = 'Seleccioná la cuenta bancaria donde se acredita el cheque.';
        if (medio === 'tarjeta'       && !$('select[name="cobro_tarjeta_id"]').val()) err = 'Seleccioná la terminal / red de tarjeta.';

        if (err) {
            e.preventDefault();
            var box = document.getElementById('cobrar-form-err');
            if (!box) {
                box = document.createElement('p');
                box.id = 'cobrar-form-err';
                box.style.cssText = 'color:#f87171;font-size:.82rem;margin:.5rem 0 0;';
                document.getElementById('cobrar-form').querySelector('.akim-modal-footer').before(box);
            }
            box.textContent = err;
        }
    });

    // Panel inicial al cargar la página
    mcToggle('efectivo');
});
</script>
