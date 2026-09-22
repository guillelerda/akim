<?php
$movimientos = $movimientos ?? [];
$flujo       = $flujo       ?? [];
$saldo_hoy   = (float)($saldo_hoy ?? 0);
$dias        = (int)($dias ?? 90);

$fmt   = fn(float $v): string => '$ ' . number_format(abs($v), 2, ',', '.');
$fdate = fn(string $d): string => date('d/m/Y', strtotime($d));
$fdia  = fn(string $d): string => ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'][date('w', strtotime($d))];

// Para escalar las barras del flujo
$maxAbs = 0.01;
foreach ($flujo as $r) {
    $maxAbs = max($maxAbs, $r['ingresos'], $r['egresos']);
}
?>

<style>
.tmov-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.tmov-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

/* Tabs */
.tmov-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:1.5rem; }
.tmov-tab { padding:.6rem 1.25rem; font-size:.88rem; font-weight:600; color:var(--text-dim); border-bottom:2px solid transparent; cursor:pointer; transition:color .15s, border-color .15s; background:none; border-top:none; border-left:none; border-right:none; }
.tmov-tab.active { color:var(--accent-lt); border-bottom-color:var(--accent); }
.tmov-tab:hover:not(.active) { color:var(--text); }
.tmov-pane { display:none; }
.tmov-pane.active { display:block; }

/* Saldo card */
.flujo-saldo-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.1rem 1.4rem; display:flex; align-items:center; gap:1.25rem; margin-bottom:1.25rem; flex-wrap:wrap; }
.flujo-saldo-num  { font-size:1.55rem; font-weight:800; letter-spacing:-.03em; }
.flujo-saldo-num.pos { color:#4ade80; }
.flujo-saldo-num.neg { color:#f87171; }
.flujo-saldo-label { font-size:.75rem; color:var(--text-dim); text-transform:uppercase; letter-spacing:.07em; font-weight:600; }

/* Range selector */
.flujo-range { display:flex; gap:.45rem; align-items:center; margin-bottom:1.1rem; flex-wrap:wrap; }
.flujo-range a { padding:.35rem .85rem; border-radius:8px; font-size:.8rem; font-weight:600; border:1px solid var(--border-med); color:var(--text-muted); text-decoration:none; transition:all .15s; }
.flujo-range a.active, .flujo-range a:hover { background:var(--accent); color:#fff; border-color:var(--accent); }

/* Flujo tabla */
.flujo-table { width:100%; border-collapse:collapse; font-size:.84rem; }
.flujo-table th { text-align:left; padding:.5rem .7rem; font-size:.67rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--text-dim); border-bottom:1px solid var(--border); }
.flujo-table td { padding:.5rem .7rem; border-bottom:1px solid rgba(255,255,255,.035); vertical-align:middle; }
.flujo-table tr:last-child td { border-bottom:none; }
.flujo-table tr.neg-row td { background:rgba(239,68,68,.04); }
.flujo-table tr.neg-row .saldo-val { color:#f87171; }

/* Mini barras */
.bar-wrap { display:flex; align-items:center; gap:.35rem; min-width:140px; }
.bar-ing, .bar-egr { height:7px; border-radius:4px; min-width:2px; transition:width .3s; }
.bar-ing { background:#4ade80; }
.bar-egr { background:#f87171; }
.bar-zero { width:2px; height:7px; background:var(--border-med); border-radius:2px; }

/* Día name */
.dia-name { font-size:.68rem; color:var(--text-dim); display:block; }

/* Detalle tooltip */
.det-list { font-size:.7rem; color:var(--text-dim); line-height:1.5; }

/* Movimientos table */
.badge-ing  { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-egr  { background:rgba(239,68,68,.15);  color:#fca5a5; }
.badge-pend { background:rgba(245,158,11,.15); color:#fcd34d; }
.badge-conf { background:rgba(22,163,74,.15);  color:#86efac; }

@media(max-width:768px) {
    .bar-wrap { display:none; }
    .flujo-table .col-det { display:none; }
}
</style>

<!-- Header -->
<div class="tmov-header">
    <div>
        <h1><i class="fa-solid fa-chart-line" style="color:var(--accent);margin-right:.4rem"></i>Movimientos y Flujo de Caja</h1>
    </div>
    <a href="tesoreria" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Tesorería
    </a>
</div>

<!-- Tabs -->
<div class="tmov-tabs">
    <button class="tmov-tab active" onclick="cambiarTab('flujo',this)">
        <i class="fa-solid fa-chart-line"></i> Flujo de caja
    </button>
    <button class="tmov-tab" onclick="cambiarTab('movimientos',this)">
        <i class="fa-solid fa-clock-rotate-left"></i> Mayor de movimientos
    </button>
</div>

<!-- ── PANE: Flujo de caja ────────────────────────────────────────────────── -->
<div id="pane-flujo" class="tmov-pane active">

    <!-- Saldo hoy -->
    <div class="flujo-saldo-card">
        <div>
            <span class="flujo-saldo-label">Posición actual confirmada</span>
            <div class="flujo-saldo-num <?php echo $saldo_hoy >= 0 ? 'pos' : 'neg'; ?>">
                <?php echo ($saldo_hoy < 0 ? '−' : '') . $fmt($saldo_hoy); ?>
            </div>
            <span style="font-size:.76rem;color:var(--text-dim)">Suma de todos los movimientos confirmados</span>
        </div>
        <div style="margin-left:auto;text-align:right">
            <span class="flujo-saldo-label">Proyección a <?php echo $dias; ?> días</span>
            <?php if (!empty($flujo)):
                $saldo_final = end($flujo)['saldo'];
            ?>
            <div class="flujo-saldo-num <?php echo $saldo_final >= 0 ? 'pos' : 'neg'; ?>">
                <?php echo ($saldo_final < 0 ? '−' : '') . $fmt($saldo_final); ?>
            </div>
            <?php else: ?>
            <div class="flujo-saldo-num pos"><?php echo $fmt($saldo_hoy); ?></div>
            <span style="font-size:.76rem;color:var(--text-dim)">Sin movimientos pendientes</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Selector de rango -->
    <div class="flujo-range">
        <span style="font-size:.78rem;color:var(--text-dim);font-weight:600">Horizonte:</span>
        <?php foreach ([30 => '30 días', 60 => '60 días', 90 => '90 días'] as $d => $label): ?>
        <a href="?dias=<?php echo $d; ?>#pane-flujo"
           class="<?php echo $dias === $d ? 'active' : ''; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($flujo)): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state" style="padding:1.5rem 0">
            <i class="fa-solid fa-chart-line" style="font-size:1.5rem;opacity:.2;display:block;margin-bottom:.5rem"></i>
            <p>No hay movimientos pendientes en los próximos <?php echo $dias; ?> días.</p>
            <p style="font-size:.8rem;color:var(--text-dim)">
                Los cobros y pagos pendientes (cheques, tarjetas, cuentas corrientes) aparecen aquí.
            </p>
        </div>
    </div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="flujo-table">
            <thead>
                <tr>
                    <th style="width:100px">Fecha</th>
                    <th class="col-det">Detalle</th>
                    <th style="width:110px;text-align:right">Ingresos</th>
                    <th style="width:110px;text-align:right">Egresos</th>
                    <th style="width:150px">Movimiento</th>
                    <th style="width:125px;text-align:right">Saldo proyectado</th>
                </tr>
            </thead>
            <tbody>
            <!-- Fila de saldo inicial -->
            <tr style="background:rgba(37,99,235,.06)">
                <td>
                    <strong style="color:var(--text)">Hoy</strong>
                    <span class="dia-name"><?php echo date('d/m/Y'); ?></span>
                </td>
                <td class="col-det" style="color:var(--text-dim);font-size:.8rem">Posición confirmada actual</td>
                <td></td><td></td>
                <td><div class="bar-wrap"><div class="bar-zero"></div></div></td>
                <td style="text-align:right">
                    <strong class="saldo-val <?php echo $saldo_hoy >= 0 ? '' : 'neg-row'; ?>"
                            style="color:<?php echo $saldo_hoy >= 0 ? '#4ade80' : '#f87171'; ?>">
                        <?php echo ($saldo_hoy < 0 ? '−' : '') . $fmt($saldo_hoy); ?>
                    </strong>
                </td>
            </tr>
            <?php foreach ($flujo as $row):
                $negRow = $row['saldo'] < 0;
                $pctIng = $row['ingresos'] > 0 ? min(100, ($row['ingresos'] / $maxAbs) * 100) : 0;
                $pctEgr = $row['egresos'] > 0 ? min(100, ($row['egresos'] / $maxAbs) * 100) : 0;

                // Armar detalle compacto
                $detalles = [];
                foreach ($row['det'] as $d) {
                    $conceptos = explode('||', $d['conceptos']);
                    $cuentas   = explode('||', $d['cuentas']);
                    foreach ($conceptos as $i => $conc) {
                        $detalles[] = ($d['tipo'] === 'ingreso' ? '↑ ' : '↓ ') . trim($conc);
                    }
                }
                $detalleStr = implode(' · ', array_slice($detalles, 0, 3));
                if (count($detalles) > 3) $detalleStr .= ' + ' . (count($detalles) - 3) . ' más';
            ?>
            <tr class="<?php echo $negRow ? 'neg-row' : ''; ?>">
                <td>
                    <strong style="color:var(--text);font-variant-numeric:tabular-nums">
                        <?php echo $fdate($row['fecha']); ?>
                    </strong>
                    <span class="dia-name"><?php echo $fdia($row['fecha']); ?></span>
                </td>
                <td class="col-det det-list"><?php echo htmlspecialchars($detalleStr, ENT_QUOTES); ?></td>
                <td style="text-align:right;color:#4ade80;font-variant-numeric:tabular-nums">
                    <?php echo $row['ingresos'] > 0 ? $fmt($row['ingresos']) : '<span style="color:var(--text-dim)">—</span>'; ?>
                </td>
                <td style="text-align:right;color:#f87171;font-variant-numeric:tabular-nums">
                    <?php echo $row['egresos'] > 0 ? $fmt($row['egresos']) : '<span style="color:var(--text-dim)">—</span>'; ?>
                </td>
                <td>
                    <div class="bar-wrap">
                        <?php if ($pctIng > 0): ?>
                        <div class="bar-ing" style="width:<?php echo round($pctIng * 0.9); ?>px" title="Ingresos: <?php echo $fmt($row['ingresos']); ?>"></div>
                        <?php endif; ?>
                        <?php if ($pctEgr > 0): ?>
                        <div class="bar-egr" style="width:<?php echo round($pctEgr * 0.9); ?>px" title="Egresos: <?php echo $fmt($row['egresos']); ?>"></div>
                        <?php endif; ?>
                        <?php if ($pctIng === 0 && $pctEgr === 0): ?>
                        <div class="bar-zero"></div>
                        <?php endif; ?>
                        <span style="font-size:.7rem;color:<?php echo $row['neto'] >= 0 ? '#4ade80' : '#f87171'; ?>;margin-left:.2rem;font-weight:600">
                            <?php echo ($row['neto'] >= 0 ? '+' : '−') . $fmt($row['neto']); ?>
                        </span>
                    </div>
                </td>
                <td style="text-align:right">
                    <strong class="saldo-val" style="font-variant-numeric:tabular-nums;font-size:.92rem">
                        <?php echo ($row['saldo'] < 0 ? '−' : '') . $fmt($row['saldo']); ?>
                    </strong>
                    <?php if ($negRow): ?>
                    <span style="display:block;font-size:.65rem;color:#f87171">⚠ Déficit</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── PANE: Mayor de movimientos ────────────────────────────────────────── -->
<div id="pane-movimientos" class="tmov-pane">
    <?php if (empty($movimientos)): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state" style="padding:1.5rem 0">
            <i class="fa-solid fa-clock-rotate-left" style="font-size:1.5rem;opacity:.2;display:block;margin-bottom:.5rem"></i>
            <p>No hay movimientos registrados aún.</p>
        </div>
    </div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:90px">Fecha</th>
                    <th style="width:90px">Acredita</th>
                    <th>Cuenta</th>
                    <th>Concepto</th>
                    <th style="width:80px">Medio</th>
                    <th style="text-align:center;width:75px">Tipo</th>
                    <th style="text-align:center;width:90px">Estado</th>
                    <th style="text-align:right;width:115px">Importe</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movimientos as $m): ?>
            <tr>
                <td style="color:var(--text-dim);font-size:.82rem;font-variant-numeric:tabular-nums;white-space:nowrap">
                    <?php echo $fdate($m['fecha']); ?>
                </td>
                <td style="color:var(--text-dim);font-size:.82rem;font-variant-numeric:tabular-nums;white-space:nowrap">
                    <?php echo $m['fecha_valor'] !== $m['fecha'] ? $fdate($m['fecha_valor']) : '<span style="color:var(--border-med)">—</span>'; ?>
                </td>
                <td style="font-size:.84rem"><?php echo htmlspecialchars($m['cuenta_nombre'], ENT_QUOTES); ?></td>
                <td style="font-size:.84rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?php echo htmlspecialchars($m['concepto'], ENT_QUOTES); ?>
                </td>
                <td style="font-size:.78rem;color:var(--text-dim)">
                    <?php echo htmlspecialchars($m['medio'] ?: '—', ENT_QUOTES); ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?php echo $m['tipo'] === 'ingreso' ? 'badge-ing' : 'badge-egr'; ?>">
                        <?php echo $m['tipo'] === 'ingreso' ? '↑' : '↓'; ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?php echo $m['estado'] === 'confirmado' ? 'badge-conf' : 'badge-pend'; ?>">
                        <?php echo $m['estado'] === 'confirmado' ? 'Confirmado' : 'Pendiente'; ?>
                    </span>
                </td>
                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;
                    color:<?php echo $m['tipo'] === 'ingreso' ? '#4ade80' : '#fca5a5'; ?>">
                    <?php echo ($m['tipo'] === 'egreso' ? '−' : '') . $fmt((float)$m['monto']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function cambiarTab(pane, btn) {
    document.querySelectorAll('.tmov-pane').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tmov-tab').forEach(function(b) { b.classList.remove('active'); });
    document.getElementById('pane-' + pane).classList.add('active');
    btn.classList.add('active');
}

// Restaurar tab si hay hash
(function() {
    if (location.hash === '#pane-flujo') cambiarTab('flujo', document.querySelectorAll('.tmov-tab')[0]);
})();
</script>
