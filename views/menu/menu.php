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

$modulos = $modulos ?? [];
$nombre  = $nombre  ?? '';
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

.module-card--ventas  i { color: #60a5fa; }
.module-card--compras i { color: #34d399; }
.module-card--stock   i { color: #a78bfa; }
.module-card--tienda  i { color: #fb923c; }
.module-card--admin   i { color: #94a3b8; }

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
</style>

<!-- ── Encabezado del dashboard ──────────────────────────────────────────── -->
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

<!-- ── Acceso a módulos ──────────────────────────────────────────────────── -->
<p class="dash-section-title">Módulos</p>
<div class="dash-modules">
    <?php
    $moduleClass = [
        'ventas'  => 'module-card--ventas',
        'compras' => 'module-card--compras',
        'stock'   => 'module-card--stock',
        'tienda'  => 'module-card--tienda',
        'admin'   => 'module-card--admin',
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
