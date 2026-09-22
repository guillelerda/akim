<?php
$cuentas          = $cuentas          ?? [];
$movRecientes     = $movRecientes     ?? [];
$chqCartera       = (float)($chqCartera   ?? 0);
$chqCount         = (int)($chqCount       ?? 0);
$total_efectivo   = (float)($total_efectivo   ?? 0);
$total_banco_act  = (float)($total_banco_act  ?? 0);
$total_banco_disp = (float)($total_banco_disp ?? 0);
$caja_hoy         = $caja_hoy         ?? null;
$saldo_sugerido   = (float)($saldo_sugerido   ?? 0);
$saldo_teo        = $saldo_teo        !== null ? (float)$saldo_teo : null;
$flash_ok         = $flash_ok         ?? null;
$flash_error      = $flash_error      ?? null;

$fmt = fn(float $v): string => '$ ' . number_format($v, 2, ',', '.');
?>

<style>
.tes-header { margin-bottom:1.75rem; }
.tes-header h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.tes-header p  { font-size:.85rem; color:var(--text-dim); margin-top:.25rem; }

.tes-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:.85rem; margin-bottom:1.75rem; }
.tes-stat  { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.9rem 1.1rem; display:flex; align-items:center; gap:.85rem; }
.tes-stat.alert { border-color:rgba(245,158,11,.4); background:rgba(245,158,11,.06); }
.tes-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.tes-stat-icon--green  { background:rgba(22,163,74,.15);  color:#4ade80; }
.tes-stat-icon--blue   { background:rgba(37,99,235,.15);  color:#60a5fa; }
.tes-stat-icon--cyan   { background:rgba(6,182,212,.15);  color:#67e8f9; }
.tes-stat-icon--yellow { background:rgba(245,158,11,.15); color:#fcd34d; }
.tes-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.tes-stat-value { font-size:1.05rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }
.tes-stat-sub   { font-size:.72rem; color:var(--text-dim); margin-top:.1rem; display:block; }

.tes-nav { display:grid; grid-template-columns:repeat(4,1fr); gap:.85rem; margin-bottom:1.75rem; }
.tes-nav-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.1rem 1.3rem; text-decoration:none; display:flex; align-items:center; gap:.9rem; transition:border-color .15s, background .15s; }
.tes-nav-card:hover { border-color:var(--accent); background:rgba(37,99,235,.06); }
.tes-nav-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.tes-nav-title { font-size:.92rem; font-weight:600; color:var(--text); display:block; }
.tes-nav-sub   { font-size:.76rem; color:var(--text-dim); display:block; margin-top:.1rem; }

.tes-cuentas-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:.75rem; margin-bottom:1.75rem; }
.tes-cuenta-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1rem 1.2rem; }
.tes-cuenta-card.dis { opacity:.5; }
.tes-cuenta-tipo { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.35rem; }
.tes-cuenta-tipo.efectivo { color:#4ade80; }
.tes-cuenta-tipo.banco    { color:#60a5fa; }
.tes-cuenta-nombre { font-size:.95rem; font-weight:700; color:var(--text); margin-bottom:.6rem; }
.tes-cuenta-balances { display:flex; flex-direction:column; gap:.25rem; }
.tes-cuenta-row { display:flex; justify-content:space-between; align-items:center; }
.tes-cuenta-row-label { font-size:.73rem; color:var(--text-dim); }
.tes-cuenta-row-val { font-size:.85rem; font-weight:600; color:var(--text); }
.tes-cuenta-row-val.disp { color:#4ade80; }
.tes-cuenta-row-val.neg  { color:#f87171; }

.tes-mov-table { margin-top:1.5rem; }
.tes-mov-table h3 { font-size:.88rem; font-weight:700; color:var(--text-dim); text-transform:uppercase; letter-spacing:.07em; margin-bottom:.75rem; }
.badge-ing { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-egr { background:rgba(239,68,68,.15);  color:#fca5a5; }
.badge-pend { background:rgba(245,158,11,.15); color:#fcd34d; }
.badge-conf { background:rgba(22,163,74,.15);  color:#86efac; }

@media(max-width:1024px) { .tes-stats { grid-template-columns:1fr 1fr; } .tes-nav { grid-template-columns:1fr 1fr; } }
@media(max-width:480px)  { .tes-stats, .tes-nav { grid-template-columns:1fr; } }
</style>

<div class="tes-header">
    <h1>Tesorería</h1>
    <p>Cuentas, fondos, cheques y medios de cobro/pago</p>
</div>

<!-- Stats -->
<div class="tes-stats">
    <div class="tes-stat">
        <div class="tes-stat-icon tes-stat-icon--green">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div>
            <span class="tes-stat-label">Efectivo total</span>
            <span class="tes-stat-value"><?php echo $fmt($total_efectivo); ?></span>
        </div>
    </div>
    <div class="tes-stat">
        <div class="tes-stat-icon tes-stat-icon--blue">
            <i class="fa-solid fa-building-columns"></i>
        </div>
        <div>
            <span class="tes-stat-label">Bancos — actual</span>
            <span class="tes-stat-value"><?php echo $fmt($total_banco_act); ?></span>
            <span class="tes-stat-sub">Disponible: <?php echo $fmt($total_banco_disp); ?></span>
        </div>
    </div>
    <div class="tes-stat<?php echo $chqCount > 0 ? ' alert' : ''; ?>">
        <div class="tes-stat-icon tes-stat-icon--yellow">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
            <span class="tes-stat-label">Cheques en cartera</span>
            <span class="tes-stat-value"><?php echo $fmt($chqCartera); ?></span>
            <span class="tes-stat-sub"><?php echo $chqCount; ?> cheque<?php echo $chqCount !== 1 ? 's' : ''; ?></span>
        </div>
    </div>
    <div class="tes-stat">
        <div class="tes-stat-icon tes-stat-icon--cyan">
            <i class="fa-solid fa-coins"></i>
        </div>
        <div>
            <span class="tes-stat-label">Posición total</span>
            <span class="tes-stat-value"><?php echo $fmt($total_efectivo + $total_banco_disp + $chqCartera); ?></span>
            <span class="tes-stat-sub">Efectivo + bancos + cartera</span>
        </div>
    </div>
</div>

<!-- Navegación rápida -->
<div class="tes-nav">
    <a href="tes_cuentas" class="tes-nav-card">
        <div class="tes-nav-icon" style="background:rgba(22,163,74,.15);color:#4ade80">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div>
            <span class="tes-nav-title">Cuentas</span>
            <span class="tes-nav-sub">Caja y cuentas bancarias</span>
        </div>
    </a>
    <a href="tes_tarjetas" class="tes-nav-card">
        <div class="tes-nav-icon" style="background:rgba(139,92,246,.15);color:#c4b5fd">
            <i class="fa-solid fa-credit-card"></i>
        </div>
        <div>
            <span class="tes-nav-title">Tarjetas</span>
            <span class="tes-nav-sub">Terminales y condiciones</span>
        </div>
    </a>
    <a href="tes_cheques" class="tes-nav-card">
        <div class="tes-nav-icon" style="background:rgba(245,158,11,.15);color:#fcd34d">
            <i class="fa-solid fa-money-check"></i>
        </div>
        <div>
            <span class="tes-nav-title">Cheques</span>
            <span class="tes-nav-sub">Cartera y emitidos</span>
        </div>
    </a>
    <a href="tes_movimientos" class="tes-nav-card">
        <div class="tes-nav-icon" style="background:rgba(6,182,212,.15);color:#67e8f9">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div>
            <span class="tes-nav-title">Flujo de caja</span>
            <span class="tes-nav-sub">Proyección y movimientos</span>
        </div>
    </a>
</div>

<!-- Caja del día -->
<?php if (!empty($flash_ok)): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.75rem">
    <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-cash-register" style="margin-right:.45rem"></i>Caja del día — <?php echo date('d/m/Y'); ?></span>
        <a href="caja_historial" class="btn btn-secondary btn-sm"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
    </div>
    <div class="card-body">
    <?php if (!$caja_hoy || ($caja_hoy['estado'] ?? '') !== 'abierta'): ?>
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:.88rem">No hay caja abierta hoy.</p>
        <form method="POST" action="caja_apertura" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin:0">
                <label style="font-size:.78rem;color:var(--text-dim);display:block;margin-bottom:.3rem">Saldo de apertura ($)</label>
                <input type="number" name="saldo_apertura" class="form-control" style="width:160px"
                       value="<?php echo number_format($saldo_sugerido, 2, '.', ''); ?>"
                       min="0" step="0.01">
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-cash-register"></i> Abrir caja
            </button>
        </form>
    <?php else: ?>
        <div style="display:flex;gap:2.5rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:1.1rem">
            <div>
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:.2rem">Apertura</div>
                <div style="font-size:.9rem;font-weight:600;color:var(--text)"><?php echo $fmt((float)($caja_hoy['saldo_apertura'] ?? 0)); ?></div>
                <div style="font-size:.75rem;color:var(--text-dim)"><?php echo $caja_hoy['hora_apertura'] ? date('H:i', strtotime($caja_hoy['hora_apertura'])) : ''; ?></div>
            </div>
            <div>
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);margin-bottom:.2rem">Saldo teórico</div>
                <div style="font-size:1.05rem;font-weight:700;color:#4ade80"><?php echo $fmt($saldo_teo ?? 0); ?></div>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
            <form method="POST" action="caja_retiro" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
                <div class="form-group" style="margin:0">
                    <label style="font-size:.78rem;color:var(--text-dim);display:block;margin-bottom:.3rem">Monto retiro</label>
                    <input type="number" name="retiro_monto" class="form-control" style="width:130px" min="0.01" step="0.01" placeholder="0,00">
                </div>
                <div class="form-group" style="margin:0">
                    <label style="font-size:.78rem;color:var(--text-dim);display:block;margin-bottom:.3rem">Motivo</label>
                    <input type="text" name="retiro_motivo" class="form-control" style="width:190px" placeholder="Gastos, etc.">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Registrar retiro</button>
            </form>
            <a href="caja_cierre" class="btn btn-danger btn-sm" style="margin-left:.5rem">
                <i class="fa-solid fa-lock"></i> Cerrar caja
            </a>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- Cuentas con saldos -->
<?php if (!empty($cuentas)): ?>
<div class="tes-mov-table">
    <h3>Saldos por cuenta</h3>
    <div class="tes-cuentas-grid">
    <?php foreach ($cuentas as $c):
        $sAct  = (float)$c['saldo_actual'];
        $sDisp = (float)$c['saldo_disponible'];
    ?>
        <div class="tes-cuenta-card<?php echo !$c['habilitada'] ? ' dis' : ''; ?>">
            <div class="tes-cuenta-tipo <?php echo $c['tipo']; ?>">
                <?php echo $c['tipo'] === 'efectivo' ? 'Caja efectivo' : 'Cuenta banco'; ?>
            </div>
            <div class="tes-cuenta-nombre">
                <?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>
                <?php if ($c['banco']): ?>
                <span style="font-size:.75rem;color:var(--text-dim);font-weight:400"> — <?php echo htmlspecialchars($c['banco'], ENT_QUOTES); ?></span>
                <?php endif; ?>
            </div>
            <div class="tes-cuenta-balances">
                <div class="tes-cuenta-row">
                    <span class="tes-cuenta-row-label">Saldo actual</span>
                    <span class="tes-cuenta-row-val <?php echo $sAct < 0 ? 'neg' : ''; ?>">
                        <?php echo $fmt($sAct); ?>
                    </span>
                </div>
                <?php if ($c['tipo'] === 'banco'): ?>
                <div class="tes-cuenta-row">
                    <span class="tes-cuenta-row-label">Disponible</span>
                    <span class="tes-cuenta-row-val disp <?php echo $sDisp < 0 ? 'neg' : ''; ?>">
                        <?php echo $fmt($sDisp); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Últimos movimientos -->
<?php if (!empty($movRecientes)): ?>
<div class="tes-mov-table">
    <h3>Últimos movimientos</h3>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cuenta</th>
                    <th>Concepto</th>
                    <th>Medio</th>
                    <th style="text-align:center">Tipo</th>
                    <th style="text-align:center">Estado</th>
                    <th style="text-align:right">Importe</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movRecientes as $m): ?>
                <tr>
                    <td style="white-space:nowrap;color:var(--text-dim);font-size:.82rem">
                        <?php echo date('d/m/Y', strtotime($m['fecha'])); ?>
                    </td>
                    <td style="font-size:.84rem"><?php echo htmlspecialchars($m['cuenta_nombre'], ENT_QUOTES); ?></td>
                    <td style="font-size:.84rem"><?php echo htmlspecialchars($m['concepto'], ENT_QUOTES); ?></td>
                    <td style="font-size:.8rem;color:var(--text-dim)"><?php echo htmlspecialchars($m['medio'] ?: '—', ENT_QUOTES); ?></td>
                    <td style="text-align:center">
                        <span class="badge <?php echo $m['tipo'] === 'ingreso' ? 'badge-ing' : 'badge-egr'; ?>">
                            <?php echo $m['tipo'] === 'ingreso' ? '↑ Ingreso' : '↓ Egreso'; ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?php echo $m['estado'] === 'confirmado' ? 'badge-conf' : 'badge-pend'; ?>">
                            <?php echo ucfirst($m['estado']); ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:600;
                        color:<?php echo $m['tipo'] === 'ingreso' ? '#4ade80' : '#fca5a5'; ?>">
                        <?php echo $fmt((float)$m['monto']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-top:1rem">
    <div class="card-body">
        <div class="empty-state">
            <i class="fa-solid fa-coins" style="display:block;font-size:1.6rem;margin-bottom:.5rem;opacity:.25"></i>
            <p>No hay movimientos registrados aún.</p>
            <p style="font-size:.8rem;color:var(--text-dim)">
                Creá cuentas y comenzá a registrar cobros y pagos.
            </p>
        </div>
    </div>
</div>
<?php endif; ?>
