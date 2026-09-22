<?php
$filas = $filas ?? [];
$flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
$flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

// Acumuladores globales
$total_ventas  = array_sum(array_column($filas, 'ventas_total'));
$total_retiros = array_sum(array_column($filas, 'retiros_total'));
$total_dias    = count($filas);
$dias_cerradas = count(array_filter($filas, fn($r) => $r['estado'] === 'cerrada'));

function _fmt(float $v): string {
    return '$ ' . number_format($v, 2, ',', '.');
}
function _dif(float $dif): string {
    if (abs($dif) < 0.01) return '<span class="hc-dif hc-ok">= 0</span>';
    $sign = $dif > 0 ? '+' : '';
    $cls  = abs($dif) <= 100 ? 'hc-warn' : 'hc-bad';
    return '<span class="hc-dif ' . $cls . '">' . $sign . number_format($dif, 2, ',', '.') . '</span>';
}
?>

<style>
/* ── Historial Caja ─────────────────────────────────────── */
.hc-wrap      { max-width:1200px; margin:0 auto; padding:1.5rem 1rem 3rem; }
.hc-header    { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:1.5rem; }
.hc-title     { font-size:1.35rem; font-weight:700; color:var(--text); }
.hc-back      { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border-radius:var(--radius); background:var(--surface-2); border:1px solid var(--border); color:var(--text-dim); font-size:.82rem; text-decoration:none; transition:background .15s,color .15s; }
.hc-back:hover{ background:var(--surface-3); color:var(--text); }

/* KPIs globales */
.hc-kpis      { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.75rem; margin-bottom:1.5rem; }
.hc-kpi       { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:.9rem 1.1rem; }
.hc-kpi-label { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--text-dim); margin-bottom:.3rem; }
.hc-kpi-val   { font-size:1.25rem; font-weight:700; color:var(--text); }

/* Flash */
.hc-flash-ok  { background:#14532d20; border:1px solid #16a34a40; border-radius:var(--radius); padding:.7rem 1rem; color:#4ade80; margin-bottom:1rem; }
.hc-flash-err { background:#7f1d1d20; border:1px solid #dc262640; border-radius:var(--radius); padding:.7rem 1rem; color:#f87171; margin-bottom:1rem; }

/* Tabla */
.hc-table-wrap { overflow-x:auto; border-radius:var(--radius); border:1px solid var(--border); }
.hc-table      { width:100%; border-collapse:collapse; font-size:.84rem; }
.hc-table thead th {
    background:var(--surface-2); color:var(--text-dim); font-size:.7rem; font-weight:600;
    text-transform:uppercase; letter-spacing:.06em; padding:.65rem .9rem; white-space:nowrap;
    border-bottom:1px solid var(--border);
}
.hc-table tbody tr { border-bottom:1px solid var(--border); transition:background .12s; }
.hc-table tbody tr:last-child { border-bottom:none; }
.hc-table tbody tr:hover { background:var(--surface-2); }
.hc-table td { padding:.6rem .9rem; color:var(--text-dim); white-space:nowrap; }
.hc-table td.tx { color:var(--text); font-weight:500; }
.hc-table td.num { text-align:right; font-variant-numeric:tabular-nums; }

/* Diferencia */
.hc-dif       { font-weight:600; border-radius:4px; padding:.1rem .35rem; font-size:.8rem; }
.hc-ok        { background:#14532d30; color:#4ade80; }
.hc-warn      { background:#78350f30; color:#fbbf24; }
.hc-bad       { background:#7f1d1d30; color:#f87171; }

/* Estado badge */
.badge-abierta { background:#1e3a5f; color:#60a5fa; padding:.15rem .5rem; border-radius:4px; font-size:.75rem; font-weight:600; }
.badge-cerrada { background:#14532d30; color:#4ade80; padding:.15rem .5rem; border-radius:4px; font-size:.75rem; font-weight:600; }

/* Obs truncated */
.hc-obs { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:help; }

/* Empty */
.hc-empty { text-align:center; padding:3rem 1rem; color:var(--text-dim); }
.hc-empty i { font-size:2.5rem; opacity:.2; display:block; margin-bottom:.75rem; }
</style>

<div class="hc-wrap">

    <!-- Header -->
    <div class="hc-header">
        <div>
            <div class="hc-title"><i class="fa-solid fa-clock-rotate-left" style="margin-right:.5rem;color:var(--accent-lt)"></i>Historial de Caja</div>
        </div>
        <a href="menu" class="hc-back"><i class="fa-solid fa-arrow-left"></i> Panel de actividad</a>
    </div>

    <?php if ($flash_ok):  ?><div class="hc-flash-ok"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
    <?php if ($flash_error):?><div class="hc-flash-err"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

    <!-- KPIs globales -->
    <div class="hc-kpis">
        <div class="hc-kpi">
            <div class="hc-kpi-label">Días registrados</div>
            <div class="hc-kpi-val"><?php echo $total_dias; ?></div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-label">Cajas cerradas</div>
            <div class="hc-kpi-val"><?php echo $dias_cerradas; ?></div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-label">Ventas acumuladas</div>
            <div class="hc-kpi-val" style="color:var(--accent-lt)"><?php echo _fmt($total_ventas); ?></div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-label">Retiros acumulados</div>
            <div class="hc-kpi-val"><?php echo _fmt($total_retiros); ?></div>
        </div>
    </div>

    <!-- Tabla -->
    <?php if (empty($filas)): ?>
    <div class="hc-empty">
        <i class="fa-solid fa-inbox"></i>
        No hay cajas registradas todavía.
    </div>
    <?php else: ?>
    <div class="hc-table-wrap">
        <table class="hc-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Apertura</th>
                    <th style="text-align:right">Saldo inicial</th>
                    <th style="text-align:right">Ventas</th>
                    <th style="text-align:right">Retiros</th>
                    <th style="text-align:right">Declarado</th>
                    <th style="text-align:center">Diferencia</th>
                    <th style="text-align:right">Saldo cierre</th>
                    <th>Cierre</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($filas as $f):
                $fecha_fmt   = date('d/m/Y', strtotime($f['fecha']));
                $dia_semana  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'][date('w', strtotime($f['fecha']))];
                $h_apertura  = $f['hora_apertura'] ? date('H:i', strtotime($f['hora_apertura'])) : '—';
                $h_cierre    = $f['hora_cierre']   ? date('H:i', strtotime($f['hora_cierre']))   : '—';
                $usuario_ap  = $f['usuario_apertura'] ?? '—';
                $usuario_ci  = $f['usuario_cierre']  ?? '—';
                $declarado   = $f['saldo_declarado'] !== null ? (float)$f['saldo_declarado'] : null;
                $saldo_ci    = $f['saldo_cierre']    !== null ? (float)$f['saldo_cierre']    : null;
                $ventas      = (float)$f['ventas_total'];
                $retiros_t   = (float)$f['retiros_total'];
                $apertura_s  = (float)$f['saldo_apertura'];
                $saldo_teo   = $apertura_s + $ventas - $retiros_t;
                $diferencia  = $declarado !== null ? round($declarado - $saldo_teo, 2) : null;
                $obs         = $f['obs_cierre'] ?? '';
                $cerrada     = $f['estado'] === 'cerrada';
            ?>
                <tr>
                    <td class="tx">
                        <span style="color:var(--text-dim);font-size:.75rem"><?php echo $dia_semana; ?></span>
                        <br><?php echo $fecha_fmt; ?>
                    </td>
                    <td>
                        <?php if ($cerrada): ?>
                            <span class="badge-cerrada"><i class="fa-solid fa-lock"></i> Cerrada</span>
                        <?php else: ?>
                            <span class="badge-abierta"><i class="fa-solid fa-lock-open"></i> Abierta</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem">
                        <span style="color:var(--text)"><?php echo $h_apertura; ?></span><br>
                        <span style="color:var(--text-dim)"><?php echo htmlspecialchars($usuario_ap, ENT_QUOTES); ?></span>
                    </td>
                    <td class="num"><?php echo _fmt($apertura_s); ?></td>
                    <td class="num" style="color:var(--accent-lt)">
                        <?php echo _fmt($ventas); ?>
                        <?php if ((int)$f['ventas_cant'] > 0): ?>
                        <br><span style="font-size:.73rem;color:var(--text-dim)"><?php echo (int)$f['ventas_cant']; ?> vta<?php echo $f['ventas_cant'] != 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num">
                        <?php echo $retiros_t > 0 ? _fmt($retiros_t) : '<span style="color:var(--text-dim)">—</span>'; ?>
                        <?php if ((int)$f['retiros_cant'] > 0): ?>
                        <br><span style="font-size:.73rem;color:var(--text-dim)"><?php echo (int)$f['retiros_cant']; ?> retiro<?php echo $f['retiros_cant'] != 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="num">
                        <?php echo $declarado !== null ? _fmt($declarado) : '<span style="color:var(--text-dim)">—</span>'; ?>
                    </td>
                    <td style="text-align:center">
                        <?php echo $diferencia !== null ? _dif($diferencia) : '<span style="color:var(--text-dim)">—</span>'; ?>
                    </td>
                    <td class="num">
                        <?php echo $saldo_ci !== null ? _fmt($saldo_ci) : '<span style="color:var(--text-dim)">—</span>'; ?>
                    </td>
                    <td style="font-size:.78rem">
                        <?php if ($cerrada): ?>
                        <span style="color:var(--text)"><?php echo $h_cierre; ?></span><br>
                        <span style="color:var(--text-dim)"><?php echo htmlspecialchars($usuario_ci, ENT_QUOTES); ?></span>
                        <?php else: ?>
                        <span style="color:var(--text-dim)">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($obs): ?>
                        <span class="hc-obs" title="<?php echo htmlspecialchars($obs, ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($obs, ENT_QUOTES); ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text-dim)">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p style="font-size:.75rem;color:var(--text-dim);margin-top:.75rem;text-align:right">
        Mostrando los últimos <?php echo count($filas); ?> días · La diferencia se calcula sobre el saldo teórico (apertura + ventas efectivo − retiros)
    </p>
    <?php endif; ?>

</div>
