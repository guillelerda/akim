<?php
// ── Saludo contextual ─────────────────────────────────────────────────────
$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

// ── Fecha en español ──────────────────────────────────────────────────────
$dias   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$meses  = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaStr = $dias[date('w')] . ', ' . date('j') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

// ── Estadísticas (ventas_hoy_cant, ventas_hoy_total, clientes, productos, stock_bajo)
$stats = $stats ?? [
    'ventas_hoy_cant'  => 0,
    'ventas_hoy_total' => 0,
    'clientes'         => 0,
    'productos'        => 0,
    'stock_bajo'       => 0,
    'ventas_recientes' => [],
    'stock_alertas'    => [],
];

$modulos        = $modulos        ?? [];
$nombre         = $nombre         ?? '';
$caja_hoy       = $caja_hoy       ?? null;
$caja_saldo_teo = $caja_saldo_teo ?? 0.0;
$caja_retiros   = $caja_retiros   ?? [];
$caja_sugerido  = $caja_sugerido  ?? 0.0;

$caja_abierta = $caja_hoy && ($caja_hoy['estado'] ?? '') === 'abierta';

// Tiempo de apertura
$caja_tiempo = '';
if ($caja_abierta && !empty($caja_hoy['hora_apertura'])) {
    $diff = time() - strtotime($caja_hoy['hora_apertura']);
    $h    = floor($diff / 3600);
    $m    = floor(($diff % 3600) / 60);
    $caja_tiempo = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
}

// Total retiros del día
$caja_total_retiros = array_sum(array_column($caja_retiros, 'monto'));

$flash_ok_menu    = $_SESSION['flash_ok']    ?? '';
$flash_error_menu = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
?>

<style>
/* ── Dashboard ───────────────────────────────────────────────────────────── */
.dash-greeting {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
}

.dash-greeting-text h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -.025em;
    line-height: 1.2;
}

.dash-greeting-text .dash-date {
    font-size: .83rem;
    color: var(--text-dim);
    margin-top: .3rem;
}

/* Métricas */
.dash-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.metric-card:hover {
    border-color: var(--border-med);
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
}

.metric-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.metric-icon--blue   { background: rgba(37,99,235,.15);  color: #60a5fa; }
.metric-icon--purple { background: rgba(124,58,237,.15); color: #a78bfa; }
.metric-icon--green  { background: rgba(22,163,74,.15);  color: #4ade80; }
.metric-icon--amber  { background: rgba(217,119,6,.15);  color: #fbbf24; }
.metric-icon--red    { background: rgba(220,38,38,.15);  color: #f87171; }

.metric-data { min-width: 0; }

.metric-label {
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--text-dim);
    display: block;
    margin-bottom: .2rem;
}

.metric-value {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text);
    display: block;
    letter-spacing: -.02em;
    line-height: 1.1;
}

.metric-sub {
    font-size: .72rem;
    color: var(--text-dim);
    display: block;
    margin-top: .15rem;
}

.metric-card--alert .metric-value { color: #fbbf24; }
.metric-card--alert-red .metric-value { color: #f87171; }

/* Módulos */
.dash-section-title {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--text-dim);
    margin-bottom: .85rem;
}

.dash-modules {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: .75rem;
    margin-bottom: 1.75rem;
}

.module-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.25rem .75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .65rem;
    text-align: center;
    color: var(--text-muted);
    font-size: .8rem;
    font-weight: 500;
    transition: background var(--transition), border-color var(--transition), color var(--transition), transform var(--transition);
    cursor: pointer;
}

.module-card:hover {
    background: var(--surface-2);
    border-color: var(--border-med);
    color: var(--text);
    transform: translateY(-2px);
}

.module-card i {
    font-size: 1.4rem;
    opacity: .7;
    transition: opacity var(--transition);
}

.module-card:hover i { opacity: 1; }

.module-card--ventas    i { color: #60a5fa; }
.module-card--compras   i { color: #34d399; }
.module-card--stock     i { color: #a78bfa; }
.module-card--tesoreria i { color: #fcd34d; }
.module-card--tienda    i { color: #fb923c; }
.module-card--admin     i { color: #94a3b8; }

/* Paneles inferiores */
.dash-panels {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1rem;
}

.panel-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .9rem 1.15rem;
    border-bottom: 1px solid var(--border);
}

.panel-title {
    font-size: .85rem;
    font-weight: 600;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: .5rem;
}

.panel-title i { font-size: .8rem; color: var(--text-dim); }

.panel-link {
    font-size: .75rem;
    color: var(--accent-lt);
    transition: color var(--transition);
}

.panel-link:hover { color: #93c5fd; }

.panel-body { padding: .35rem 0; }

/* Últimas ventas */
.recent-venta {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: .65rem 1.15rem;
    border-bottom: 1px solid var(--border);
    transition: background var(--transition);
}

.recent-venta:last-child { border-bottom: none; }
.recent-venta:hover { background: rgba(255,255,255,.025); }

.recent-venta-icon {
    width: 34px; height: 34px;
    border-radius: 9px;
    background: rgba(37,99,235,.12);
    color: #60a5fa;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem;
    flex-shrink: 0;
}

.recent-venta-data { flex: 1; min-width: 0; }

.recent-venta-cliente {
    font-size: .83rem;
    font-weight: 500;
    color: var(--text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.recent-venta-fecha {
    font-size: .7rem;
    color: var(--text-dim);
    margin-top: 2px;
}

.recent-venta-total {
    font-size: .88rem;
    font-weight: 600;
    color: var(--green-lt);
    flex-shrink: 0;
}

/* Alertas de stock */
.stock-alert-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .6rem 1.15rem;
    border-bottom: 1px solid var(--border);
    transition: background var(--transition);
}

.stock-alert-item:last-child { border-bottom: none; }
.stock-alert-item:hover { background: rgba(255,255,255,.025); }

.stock-alert-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--amber);
    flex-shrink: 0;
    box-shadow: 0 0 6px rgba(217,119,6,.5);
}

.stock-alert-dot--red {
    background: var(--red);
    box-shadow: 0 0 6px rgba(220,38,38,.5);
}

.stock-alert-nombre {
    flex: 1;
    font-size: .82rem;
    color: var(--text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.stock-alert-cant {
    font-size: .78rem;
    font-weight: 600;
    color: #fbbf24;
    flex-shrink: 0;
}

/* Placeholder vacío */
.dash-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    padding: 2rem 1rem;
    color: var(--text-dim);
    text-align: center;
}

.dash-empty i { font-size: 1.5rem; opacity: .3; }
.dash-empty p { font-size: .8rem; }

/* Responsive */
@media (max-width: 1200px) {
    .dash-metrics  { grid-template-columns: repeat(2, 1fr); }
    .dash-modules  { grid-template-columns: repeat(3, 1fr); }
    .dash-panels   { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
    .dash-metrics  { grid-template-columns: 1fr 1fr; }
    .dash-modules  { grid-template-columns: repeat(2, 1fr); }
    .dash-greeting { flex-direction: column; }
    .dash-greeting .btn { width: 100%; justify-content: center; }
}

/* ── Widget POS ───────────────────────────────────────────────────────────── */
.pos-widget {
    border-radius: var(--radius-lg);
    margin-bottom: 1.5rem;
    overflow: hidden;
    border: 1px solid var(--border);
}

/* Estado: CERRADA */
.pos-closed {
    background: linear-gradient(135deg, rgba(30,15,0,.6) 0%, rgba(15,10,0,.4) 100%);
    border-color: rgba(245,158,11,.3);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.25rem 1.5rem;
    flex-wrap: wrap;
}
.pos-closed-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(245,158,11,.12);
    border: 1px solid rgba(245,158,11,.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: #fbbf24; flex-shrink: 0;
}
.pos-closed-text { flex: 1; min-width: 0; }
.pos-closed-text h3 { font-size: .95rem; font-weight: 700; color: #fde68a; margin-bottom: .15rem; }
.pos-closed-text p  { font-size: .78rem; color: #92400e; color: rgba(253,230,138,.6); }
.pos-closed-actions { display: flex; gap: .6rem; flex-wrap: wrap; }

/* Estado: ABIERTA */
.pos-open { background: var(--surface); }

.pos-open-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .85rem 1.25rem;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap; gap: .75rem;
}
.pos-status-badge {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .3rem .8rem;
    border-radius: 20px;
    background: rgba(22,163,74,.12);
    border: 1px solid rgba(22,163,74,.25);
    font-size: .72rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #4ade80;
}
.pos-status-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34,197,94,.2);
    animation: pos-pulse 2s infinite;
}
@keyframes pos-pulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(34,197,94,.2); }
    50%       { box-shadow: 0 0 0 5px rgba(34,197,94,.08); }
}
.pos-tiempo { font-size: .75rem; color: var(--text-dim); }
.pos-open-actions { display: flex; gap: .5rem; }

.pos-open-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
}
.pos-kpi {
    padding: 1rem 1.25rem;
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column; gap: .2rem;
}
.pos-kpi:last-child { border-right: none; }
.pos-kpi-label { font-size: .68rem; font-weight: 600; text-transform: uppercase;
                 letter-spacing: .07em; color: var(--text-dim); }
.pos-kpi-value { font-size: 1.3rem; font-weight: 700; color: var(--text);
                 letter-spacing: -.02em; line-height: 1.1; }
.pos-kpi-sub   { font-size: .7rem; color: var(--text-dim); }
.pos-kpi--green .pos-kpi-value { color: #4ade80; }
.pos-kpi--amber .pos-kpi-value { color: #fbbf24; }
.pos-kpi--red   .pos-kpi-value { color: #f87171; }

/* Modal de apertura y retiro */
.pos-modal-bg {
    display: none; position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,.72); backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
}
.pos-modal-bg.open { display: flex; }
.pos-modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.75rem;
    width: 100%; max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
}
.pos-modal h3 { font-size: 1.05rem; font-weight: 700; color: var(--text);
                margin-bottom: .35rem; }
.pos-modal p  { font-size: .8rem; color: var(--text-dim); margin-bottom: 1.25rem; }
.pos-modal-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.25rem; }

.pos-input-money {
    position: relative;
}
.pos-input-money span {
    position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
    font-size: 1rem; font-weight: 700; color: var(--text-dim);
}
.pos-input-money input {
    width: 100%; padding: .7rem .85rem .7rem 1.75rem;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text);
    font-size: 1.25rem; font-weight: 700; font-family: inherit;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.pos-input-money input:focus {
    border-color: var(--accent-lt);
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}
.pos-input-label {
    display: block; font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .07em;
    color: var(--text-dim); margin-bottom: .4rem;
}

@media (max-width: 900px) {
    .pos-open-kpis { grid-template-columns: 1fr 1fr; }
    .pos-kpi:nth-child(2) { border-right: none; }
    .pos-kpi:nth-child(1),
    .pos-kpi:nth-child(2) { border-bottom: 1px solid var(--border); }
}
@media (max-width: 640px) {
    .pos-closed { flex-direction: column; align-items: flex-start; }
    .pos-open-kpis { grid-template-columns: 1fr 1fr; }
    .pos-open-header { flex-direction: column; align-items: flex-start; }
}
</style>

<!-- ── Flash messages ────────────────────────────────────────────────────── -->
<?php if ($flash_ok_menu): ?>
<div class="alert alert-success" style="margin-bottom:1rem">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash_ok_menu, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if ($flash_error_menu): ?>
<div class="alert alert-error" style="margin-bottom:1rem">
    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error_menu, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════
     WIDGET POS — ESTADO DE CAJA
════════════════════════════════════════════════════════ -->
<?php if (!$caja_abierta): ?>

<!-- CAJA CERRADA / SIN APERTURA -->
<div class="pos-widget pos-closed">
    <div class="pos-closed-icon">
        <i class="fa-solid fa-cash-register"></i>
    </div>
    <div class="pos-closed-text">
        <h3>Caja sin abrir</h3>
        <p>Para operar hoy, primero realizá la apertura de caja e ingresá el saldo inicial en efectivo.</p>
    </div>
    <div class="pos-closed-actions" style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem">
        <button class="btn btn-primary" onclick="posModal('apertura')">
            <i class="fa-solid fa-door-open"></i>
            Abrir Caja
        </button>
        <a href="caja_historial" style="font-size:.78rem;color:var(--text-dim);text-decoration:none;display:inline-flex;align-items:center;gap:.3rem">
            <i class="fa-solid fa-clock-rotate-left"></i> Ver historial
        </a>
    </div>
</div>

<?php else: ?>

<!-- CAJA ABIERTA -->
<div class="pos-widget pos-open">
    <div class="pos-open-header">
        <div style="display:flex;align-items:center;gap:.85rem;flex-wrap:wrap">
            <span class="pos-status-badge">
                <span class="pos-status-dot"></span>
                Caja Abierta
            </span>
            <?php if ($caja_tiempo): ?>
            <span class="pos-tiempo">
                <i class="fa-regular fa-clock"></i>
                Apertura hace <?php echo $caja_tiempo; ?>
                — <?php echo date('H:i', strtotime($caja_hoy['hora_apertura'])); ?>hs
            </span>
            <?php endif; ?>
        </div>
        <div class="pos-open-actions">
            <button class="btn btn-secondary btn-sm" onclick="posModal('retiro')">
                <i class="fa-solid fa-money-bill-transfer"></i>
                Retiro
            </button>
            <a href="caja_cierre" class="btn btn-sm"
               style="background:rgba(220,38,38,.12);color:#f87171;border:1px solid rgba(220,38,38,.25)">
                <i class="fa-solid fa-lock"></i>
                Cerrar Caja
            </a>
        </div>
    </div>

    <div class="pos-open-kpis">
        <!-- KPI 1: Saldo efectivo teórico -->
        <div class="pos-kpi pos-kpi--green">
            <span class="pos-kpi-label"><i class="fa-solid fa-coins"></i> Efectivo en caja</span>
            <span class="pos-kpi-value">$<?php echo number_format($caja_saldo_teo, 2, ',', '.'); ?></span>
            <span class="pos-kpi-sub">apertura $<?php echo number_format((float)$caja_hoy['saldo_apertura'], 2, ',', '.'); ?> + cobros efectivo</span>
        </div>

        <!-- KPI 2: Ventas del día -->
        <div class="pos-kpi">
            <span class="pos-kpi-label"><i class="fa-solid fa-receipt"></i> Ventas del día</span>
            <span class="pos-kpi-value">$<?php echo number_format((float)$stats['ventas_hoy_total'], 2, ',', '.'); ?></span>
            <span class="pos-kpi-sub"><?php echo (int)$stats['ventas_hoy_cant']; ?> operaciones registradas</span>
        </div>

        <!-- KPI 3: Retiros del día -->
        <div class="pos-kpi <?php echo $caja_total_retiros > 0 ? 'pos-kpi--amber' : ''; ?>">
            <span class="pos-kpi-label"><i class="fa-solid fa-money-bill-transfer"></i> Retiros del día</span>
            <span class="pos-kpi-value">$<?php echo number_format($caja_total_retiros, 2, ',', '.'); ?></span>
            <span class="pos-kpi-sub"><?php echo count($caja_retiros); ?> extracción<?php echo count($caja_retiros) !== 1 ? 'es' : ''; ?> registrada<?php echo count($caja_retiros) !== 1 ? 's' : ''; ?></span>
        </div>

        <!-- KPI 4: Alertas de stock -->
        <div class="pos-kpi <?php echo (int)$stats['stock_bajo'] > 0 ? 'pos-kpi--amber' : ''; ?>">
            <span class="pos-kpi-label"><i class="fa-solid fa-boxes-stacked"></i> Alertas stock</span>
            <span class="pos-kpi-value"><?php echo (int)$stats['stock_bajo']; ?></span>
            <span class="pos-kpi-sub"><?php echo (int)$stats['stock_bajo'] === 0 ? 'Sin alertas de stock' : 'productos bajo mínimo'; ?></span>
        </div>
    </div>
    <div style="padding:.5rem 1.25rem .85rem;text-align:right">
        <a href="caja_historial" style="font-size:.78rem;color:var(--text-dim);text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;transition:color .15s"
           onmouseover="this.style.color='var(--accent-lt)'" onmouseout="this.style.color='var(--text-dim)'">
            <i class="fa-solid fa-clock-rotate-left"></i> Ver historial de cierres
        </a>
    </div>
</div>

<?php endif; ?>

<!-- ════════════════════════════════════════════════════════
     MODAL: APERTURA DE CAJA
════════════════════════════════════════════════════════ -->
<div class="pos-modal-bg" id="modal-apertura">
    <div class="pos-modal">
        <h3><i class="fa-solid fa-door-open" style="color:#fbbf24"></i> Apertura de Caja</h3>
        <p>
            Ingresá el saldo inicial en efectivo disponible.
            <?php if ($caja_sugerido > 0): ?>
            El cierre anterior fue de <strong>$<?php echo number_format($caja_sugerido, 2, ',', '.'); ?></strong>.
            <?php else: ?>
            Si es el primer día, podés ingresar $0,00.
            <?php endif; ?>
        </p>
        <form method="POST" action="caja_apertura">
            <label class="pos-input-label">Saldo inicial en efectivo</label>
            <div class="pos-input-money">
                <span>$</span>
                <input type="number" name="saldo_apertura" step="0.01" min="0"
                       value="<?php echo number_format($caja_sugerido, 2, '.', ''); ?>"
                       placeholder="0.00" autofocus>
            </div>
            <div class="pos-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="posModalClose()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-door-open"></i> Confirmar apertura
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════
     MODAL: RETIRO DE EFECTIVO
════════════════════════════════════════════════════════ -->
<div class="pos-modal-bg" id="modal-retiro">
    <div class="pos-modal">
        <h3><i class="fa-solid fa-money-bill-transfer" style="color:#f97316"></i> Retiro de Efectivo</h3>
        <p>
            Registrá la extracción parcial de efectivo. La caja continuará abierta.<br>
            <?php if ($caja_abierta): ?>
            Saldo disponible: <strong style="color:#4ade80">$<?php echo number_format($caja_saldo_teo, 2, ',', '.'); ?></strong>
            <?php endif; ?>
        </p>
        <form method="POST" action="caja_retiro">
            <div style="margin-bottom:.9rem">
                <label class="pos-input-label">Monto a retirar</label>
                <div class="pos-input-money">
                    <span>$</span>
                    <input type="number" name="retiro_monto" step="0.01" min="0.01"
                           max="<?php echo number_format($caja_saldo_teo, 2, '.', ''); ?>"
                           placeholder="0.00" required>
                </div>
            </div>
            <div>
                <label class="pos-input-label">Motivo del retiro</label>
                <input type="text" name="retiro_motivo"
                       style="width:100%;padding:.55rem .8rem;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);color:var(--text);font-size:.875rem;font-family:inherit;outline:none"
                       placeholder="Ej: Pago a proveedor, depósito bancario..."
                       required maxlength="255">
            </div>
            <div class="pos-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="posModalClose()">Cancelar</button>
                <button type="submit" class="btn btn-primary"
                        style="background:rgba(234,88,12,.85);border-color:rgba(234,88,12,.5)">
                    <i class="fa-solid fa-money-bill-transfer"></i> Confirmar retiro
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function posModal(id) {
    document.getElementById('modal-' + id).classList.add('open');
    var first = document.querySelector('#modal-' + id + ' input');
    if (first) setTimeout(function(){ first.focus(); }, 60);
}
function posModalClose() {
    document.querySelectorAll('.pos-modal-bg').forEach(function(m){ m.classList.remove('open'); });
}
document.querySelectorAll('.pos-modal-bg').forEach(function(bg) {
    bg.addEventListener('click', function(e) { if (e.target === bg) posModalClose(); });
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') posModalClose(); });
</script>

<!-- ── Encabezado del dashboard ──────────────────────────────────────────── -->
<?php
$emp_nombre = $_SESSION['empresa_nombre'] ?? '';
$emp_slogan = $_SESSION['empresa_slogan'] ?? '';
?>
<?php if ($emp_nombre): ?>
<div style="display:flex;align-items:center;gap:.85rem;margin-bottom:1.25rem;
            padding:.85rem 1.15rem;
            background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div style="width:40px;height:40px;border-radius:10px;
                background:linear-gradient(135deg,#1e3a8a,#1e40af);
                display:flex;align-items:center;justify-content:center;
                font-size:.95rem;font-weight:700;color:#93c5fd;flex-shrink:0;letter-spacing:-.02em">
        <?= htmlspecialchars(mb_strtoupper(mb_substr($emp_nombre, 0, 2)), ENT_QUOTES) ?>
    </div>
    <div style="min-width:0">
        <div style="font-size:.95rem;font-weight:700;color:var(--text);
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($emp_nombre, ENT_QUOTES) ?>
        </div>
        <?php if ($emp_slogan): ?>
        <div style="font-size:.75rem;color:var(--text-dim);margin-top:.1rem;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($emp_slogan, ENT_QUOTES) ?>
        </div>
        <?php endif; ?>
    </div>
    <a href="cambiarEmpresa" style="margin-left:auto;font-size:.73rem;color:var(--text-dim);
        text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;
        white-space:nowrap;transition:color .15s;flex-shrink:0"
       onmouseover="this.style.color='var(--accent-lt)'" onmouseout="this.style.color='var(--text-dim)'">
        <i class="fa-solid fa-right-left"></i> Cambiar
    </a>
</div>
<?php endif; ?>

<div class="dash-greeting">
    <div class="dash-greeting-text">
        <h1><?php echo $saludo; ?>, <?php echo htmlspecialchars($nombre, ENT_QUOTES); ?></h1>
        <p class="dash-date"><?php echo $fechaStr; ?></p>
    </div>
    <a href="vta_wizard_step1" class="btn btn-primary">
        <i class="fa-solid fa-bolt"></i>
        Nueva Venta
    </a>
</div>

<!-- ── Métricas ──────────────────────────────────────────────────────────── -->
<div class="dash-metrics">

    <div class="metric-card">
        <div class="metric-icon metric-icon--blue">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="metric-data">
            <span class="metric-label">Ventas hoy</span>
            <span class="metric-value">
                $<?php echo number_format((float)$stats['ventas_hoy_total'], 2, ',', '.'); ?>
            </span>
            <span class="metric-sub"><?php echo (int)$stats['ventas_hoy_cant']; ?> operaciones</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon metric-icon--purple">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="metric-data">
            <span class="metric-label">Clientes</span>
            <span class="metric-value"><?php echo number_format((int)$stats['clientes'], 0, ',', '.'); ?></span>
            <span class="metric-sub">clientes activos</span>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon metric-icon--green">
            <i class="fa-solid fa-box"></i>
        </div>
        <div class="metric-data">
            <span class="metric-label">Productos</span>
            <span class="metric-value"><?php echo number_format((int)$stats['productos'], 0, ',', '.'); ?></span>
            <span class="metric-sub">ítems en catálogo</span>
        </div>
    </div>

    <?php
    $alerta_cnt  = (int)$stats['stock_bajo'];
    $alert_class = $alerta_cnt > 5 ? 'metric-card--alert-red' : ($alerta_cnt > 0 ? 'metric-card--alert' : '');
    $alert_icon  = $alerta_cnt > 0 ? 'metric-icon--amber' : 'metric-icon--green';
    ?>
    <div class="metric-card <?php echo $alert_class; ?>">
        <div class="metric-icon <?php echo $alert_icon; ?>">
            <i class="fa-solid fa-<?php echo $alerta_cnt > 0 ? 'triangle-exclamation' : 'check-circle'; ?>"></i>
        </div>
        <div class="metric-data">
            <span class="metric-label">Stock bajo</span>
            <span class="metric-value"><?php echo $alerta_cnt; ?></span>
            <span class="metric-sub"><?php echo $alerta_cnt === 1 ? 'ítem por reponer' : 'ítems por reponer'; ?></span>
        </div>
    </div>

</div>

<!-- ── CxC / CxP ──────────────────────────────────────────────────────── -->
<?php
$cxc_cant  = (int)($stats['cxc_cant']  ?? 0);
$cxc_total = (float)($stats['cxc_total'] ?? 0);
$cxp_cant  = (int)($stats['cxp_cant']  ?? 0);
$cxp_total = (float)($stats['cxp_total'] ?? 0);
if ($cxc_cant > 0 || $cxp_cant > 0):
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:1.5rem">

    <?php if ($cxc_cant > 0): ?>
    <a href="ventas" style="display:flex;align-items:center;gap:1rem;
        background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.25);
        border-radius:var(--radius-lg);padding:1rem 1.2rem;text-decoration:none;
        transition:background .15s,border-color .15s"
       onmouseover="this.style.background='rgba(37,99,235,.14)'"
       onmouseout="this.style.background='rgba(37,99,235,.08)'">
        <div style="width:44px;height:44px;border-radius:11px;background:rgba(37,99,235,.15);color:#60a5fa;
                    display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div>
            <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#93c5fd;margin-bottom:.2rem">
                CxC — Cobrar
            </div>
            <div style="font-size:1.2rem;font-weight:700;color:var(--text);letter-spacing:-.02em">
                $<?php echo number_format($cxc_total, 2, ',', '.'); ?>
            </div>
            <div style="font-size:.72rem;color:var(--text-dim);margin-top:.1rem">
                <?php echo $cxc_cant; ?> comprobante<?php echo $cxc_cant !== 1 ? 's' : ''; ?> pendiente<?php echo $cxc_cant !== 1 ? 's' : ''; ?> de cobro
            </div>
        </div>
    </a>
    <?php else: ?>
    <div style="display:flex;align-items:center;gap:1rem;
        background:rgba(22,163,74,.06);border:1px solid rgba(22,163,74,.15);
        border-radius:var(--radius-lg);padding:1rem 1.2rem">
        <div style="width:44px;height:44px;border-radius:11px;background:rgba(22,163,74,.1);color:#4ade80;
                    display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#86efac;margin-bottom:.2rem">CxC — Cobrar</div>
            <div style="font-size:.88rem;color:var(--text-dim)">Sin pendientes de cobro</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($cxp_cant > 0): ?>
    <a href="cpa_facturas" style="display:flex;align-items:center;gap:1rem;
        background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.22);
        border-radius:var(--radius-lg);padding:1rem 1.2rem;text-decoration:none;
        transition:background .15s,border-color .15s"
       onmouseover="this.style.background='rgba(220,38,38,.14)'"
       onmouseout="this.style.background='rgba(220,38,38,.08)'">
        <div style="width:44px;height:44px;border-radius:11px;background:rgba(220,38,38,.12);color:#f87171;
                    display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
            <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#fca5a5;margin-bottom:.2rem">
                CxP — Pagar
            </div>
            <div style="font-size:1.2rem;font-weight:700;color:var(--text);letter-spacing:-.02em">
                $<?php echo number_format($cxp_total, 2, ',', '.'); ?>
            </div>
            <div style="font-size:.72rem;color:var(--text-dim);margin-top:.1rem">
                <?php echo $cxp_cant; ?> factura<?php echo $cxp_cant !== 1 ? 's' : ''; ?> pendiente<?php echo $cxp_cant !== 1 ? 's' : ''; ?> de pago
            </div>
        </div>
    </a>
    <?php else: ?>
    <div style="display:flex;align-items:center;gap:1rem;
        background:rgba(22,163,74,.06);border:1px solid rgba(22,163,74,.15);
        border-radius:var(--radius-lg);padding:1rem 1.2rem">
        <div style="width:44px;height:44px;border-radius:11px;background:rgba(22,163,74,.1);color:#4ade80;
                    display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div style="font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#86efac;margin-bottom:.2rem">CxP — Pagar</div>
            <div style="font-size:.88rem;color:var(--text-dim)">Sin facturas pendientes de pago</div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<!-- ── Acceso a módulos ──────────────────────────────────────────────────── -->
<p class="dash-section-title">Módulos</p>
<div class="dash-modules">
    <?php
    $moduleClass = [
        'ventas'    => 'module-card--ventas',
        'compras'   => 'module-card--compras',
        'stock'     => 'module-card--stock',
        'tesoreria' => 'module-card--tesoreria',
        'tienda'    => 'module-card--tienda',
        'admin'     => 'module-card--admin',
    ];
    foreach ($modulos as $mod):
        $mc = $moduleClass[$mod['url']] ?? '';
    ?>
    <a href="<?php echo htmlspecialchars($mod['url'], ENT_QUOTES); ?>" class="module-card <?php echo $mc; ?>">
        <i class="fa-solid <?php echo htmlspecialchars($mod['icono'], ENT_QUOTES); ?>"></i>
        <span><?php echo htmlspecialchars($mod['nombre'], ENT_QUOTES); ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Paneles inferiores ────────────────────────────────────────────────── -->
<div class="dash-panels">

    <!-- Últimas ventas -->
    <div class="panel-card">
        <div class="panel-header">
            <span class="panel-title">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Últimas ventas
            </span>
            <a href="ventas" class="panel-link">Ver todas →</a>
        </div>
        <div class="panel-body">
            <?php if (empty($stats['ventas_recientes'])): ?>
            <div class="dash-empty">
                <i class="fa-solid fa-receipt"></i>
                <p>Todavía no hay ventas registradas.</p>
                <a href="vta_wizard_step1" class="btn btn-primary btn-sm" style="margin-top:.35rem">
                    <i class="fa-solid fa-plus"></i> Primera venta
                </a>
            </div>
            <?php else: ?>
                <?php foreach ($stats['ventas_recientes'] as $v): ?>
                <div class="recent-venta">
                    <div class="recent-venta-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="recent-venta-data">
                        <div class="recent-venta-cliente">
                            <?php echo htmlspecialchars($v['razon_social'] ?? 'Consumidor final', ENT_QUOTES); ?>
                        </div>
                        <div class="recent-venta-fecha">
                            <?php echo htmlspecialchars($v['fecha'] ?? '', ENT_QUOTES); ?>
                        </div>
                    </div>
                    <div class="recent-venta-total">
                        $<?php echo number_format((float)($v['total'] ?? 0), 2, ',', '.'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas de stock -->
    <div class="panel-card">
        <div class="panel-header">
            <span class="panel-title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Alertas de stock
            </span>
            <a href="stock" class="panel-link">Ver stock →</a>
        </div>
        <div class="panel-body">
            <?php if (empty($stats['stock_alertas'])): ?>
            <div class="dash-empty">
                <i class="fa-solid fa-box-open"></i>
                <p>Sin alertas de stock.</p>
            </div>
            <?php else: ?>
                <?php foreach ($stats['stock_alertas'] as $p): ?>
                <?php $dot = ($p['stock_actual'] <= 0) ? 'stock-alert-dot--red' : ''; ?>
                <div class="stock-alert-item">
                    <div class="stock-alert-dot <?php echo $dot; ?>"></div>
                    <div class="stock-alert-nombre">
                        <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>
                    </div>
                    <div class="stock-alert-cant">
                        <?php echo (int)$p['stock_actual']; ?> u.
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
