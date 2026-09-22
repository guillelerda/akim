<?php
$recibidos = $recibidos ?? [];
$emitidos  = $emitidos  ?? [];
$flash_ok  = $flash_ok  ?? null;
$flash_error = $flash_error ?? null;

$fmt = fn(float $v): string => '$ ' . number_format($v, 2, ',', '.');
$fdate = fn(string $d): string => $d ? date('d/m/Y', strtotime($d)) : '—';

$hoy       = new DateTimeImmutable('today');
$diasParaVencer = function(string $fecha) use ($hoy): int {
    $f = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
    return $f ? (int)$hoy->diff($f)->days * ($f >= $hoy ? 1 : -1) : 0;
};

$estadoRecibido = fn(string $e): string => match($e) {
    'cartera'    => '<span class="badge badge-amber">En cartera</span>',
    'depositado' => '<span class="badge badge-green">Depositado</span>',
    'endosado'   => '<span class="badge badge-purple">Endosado</span>',
    default      => '<span class="badge badge-gray">' . ucfirst($e) . '</span>',
};
$estadoEmitido = fn(string $e): string => match($e) {
    'cartera' => '<span class="badge badge-amber">Pendiente</span>',
    default   => '<span class="badge badge-gray">' . ucfirst($e) . '</span>',
};

// Estadísticas rápidas
$totalCartera   = array_sum(array_column(array_filter($recibidos, fn($c) => $c['estado'] === 'cartera'), 'monto'));
$countCartera   = count(array_filter($recibidos, fn($c) => $c['estado'] === 'cartera'));
$vencen7        = array_filter($recibidos, fn($c) => $c['estado'] === 'cartera' && $diasParaVencer($c['fecha_vencimiento']) <= 7 && $diasParaVencer($c['fecha_vencimiento']) >= 0);
$totalEmitidos  = array_sum(array_column($emitidos, 'monto'));
?>

<style>
.chq-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.chq-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

.chq-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.5rem; }
.chq-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.85rem 1rem; display:flex; align-items:center; gap:.75rem; }
.chq-stat.warn { border-color:rgba(239,68,68,.35); background:rgba(239,68,68,.05); }
.chq-stat-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
.chq-stat-label { font-size:.67rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.chq-stat-value { font-size:1rem; font-weight:700; color:var(--text); display:block; }
.chq-stat-sub   { font-size:.7rem; color:var(--text-dim); }

.chq-section { margin-bottom:1.75rem; }
.chq-section-title { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--text-dim); margin-bottom:.75rem; display:flex; align-items:center; gap:.5rem; }

.badge-purple { background:rgba(139,92,246,.15); color:#c4b5fd; }
.badge-amber  { background:rgba(245,158,11,.15); color:#fcd34d; }
.badge-green  { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-gray   { background:rgba(107,114,128,.12);color:#9ca3af; }

.vence-badge { display:inline-block; font-size:.7rem; font-weight:600; padding:.1rem .45rem; border-radius:5px; }
.vence-ok   { background:rgba(22,163,74,.12);  color:#86efac; }
.vence-soon { background:rgba(245,158,11,.15); color:#fcd34d; }
.vence-late { background:rgba(239,68,68,.15);  color:#fca5a5; }

.btn-accion { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; border-radius:7px; font-size:.75rem; font-weight:600; border:none; cursor:pointer; transition:background .15s, color .15s; }
.btn-depositar { background:rgba(22,163,74,.15);  color:#86efac; }
.btn-depositar:hover { background:rgba(22,163,74,.3); }
.btn-rechazar  { background:rgba(239,68,68,.12);  color:#fca5a5; }
.btn-rechazar:hover  { background:rgba(239,68,68,.28); }
.btn-cobrado   { background:rgba(22,163,74,.15);  color:#86efac; }
.btn-cobrado:hover   { background:rgba(22,163,74,.3); }
.btn-devuelto  { background:rgba(107,114,128,.15); color:#9ca3af; }
.btn-devuelto:hover  { background:rgba(107,114,128,.3); }

@media(max-width:768px) { .chq-stats { grid-template-columns:1fr 1fr; } }
</style>

<!-- Flash -->
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
<div class="chq-header">
    <div>
        <h1><i class="fa-solid fa-money-check" style="color:var(--accent);margin-right:.4rem"></i>Gestión de Cheques</h1>
    </div>
    <a href="tesoreria" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Tesorería
    </a>
</div>

<!-- Stats -->
<div class="chq-stats">
    <div class="chq-stat">
        <div class="chq-stat-icon" style="background:rgba(245,158,11,.15);color:#fcd34d">
            <i class="fa-solid fa-inbox"></i>
        </div>
        <div>
            <span class="chq-stat-label">En cartera</span>
            <span class="chq-stat-value"><?php echo $fmt($totalCartera); ?></span>
            <span class="chq-stat-sub"><?php echo $countCartera; ?> cheque<?php echo $countCartera !== 1 ? 's' : ''; ?></span>
        </div>
    </div>
    <div class="chq-stat<?php echo count($vencen7) > 0 ? ' warn' : ''; ?>">
        <div class="chq-stat-icon" style="background:rgba(239,68,68,.15);color:#fca5a5">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <span class="chq-stat-label">Vencen en 7 días</span>
            <span class="chq-stat-value"><?php echo count($vencen7); ?> cheque<?php echo count($vencen7) !== 1 ? 's' : ''; ?></span>
        </div>
    </div>
    <div class="chq-stat">
        <div class="chq-stat-icon" style="background:rgba(37,99,235,.15);color:#60a5fa">
            <i class="fa-solid fa-paper-plane"></i>
        </div>
        <div>
            <span class="chq-stat-label">Emitidos pendientes</span>
            <span class="chq-stat-value"><?php echo $fmt($totalEmitidos); ?></span>
            <span class="chq-stat-sub"><?php echo count($emitidos); ?> cheque<?php echo count($emitidos) !== 1 ? 's' : ''; ?></span>
        </div>
    </div>
    <div class="chq-stat">
        <div class="chq-stat-icon" style="background:rgba(22,163,74,.15);color:#4ade80">
            <i class="fa-solid fa-scale-balanced"></i>
        </div>
        <div>
            <span class="chq-stat-label">Posición cheques</span>
            <span class="chq-stat-value"><?php echo $fmt($totalCartera - $totalEmitidos); ?></span>
            <span class="chq-stat-sub">Cartera menos emitidos</span>
        </div>
    </div>
</div>

<!-- ── Cheques recibidos ──────────────────────────────────────────────────── -->
<div class="chq-section">
    <div class="chq-section-title">
        <i class="fa-solid fa-inbox"></i> Cheques recibidos
        <span style="font-weight:400;text-transform:none;font-size:.75rem;color:var(--text-dim)"> — en cartera y depositados</span>
    </div>

    <?php if (empty($recibidos)): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state" style="padding:1.25rem 0">
            <i class="fa-solid fa-inbox"></i>
            <p>No hay cheques recibidos activos.</p>
        </div>
    </div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>N° Cheque</th>
                    <th>Banco</th>
                    <th style="width:90px">Emisión</th>
                    <th style="width:90px">Vencimiento</th>
                    <th>Cuenta</th>
                    <th>Motivo</th>
                    <th style="text-align:right">Monto</th>
                    <th style="text-align:center">Estado</th>
                    <th style="width:160px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recibidos as $ch):
                $dias = $diasParaVencer($ch['fecha_vencimiento']);
                if ($dias < 0)      $vClass = 'vence-late';
                elseif ($dias <= 7) $vClass = 'vence-soon';
                else                $vClass = 'vence-ok';
            ?>
                <tr>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">
                        <?php echo htmlspecialchars($ch['numero'] ?: '—', ENT_QUOTES); ?>
                    </td>
                    <td style="color:var(--text-muted)"><?php echo htmlspecialchars($ch['banco'] ?: '—', ENT_QUOTES); ?></td>
                    <td style="color:var(--text-dim);font-size:.82rem"><?php echo $fdate($ch['fecha_emision']); ?></td>
                    <td>
                        <span class="vence-badge <?php echo $vClass; ?>"><?php echo $fdate($ch['fecha_vencimiento']); ?></span>
                        <?php if ($ch['estado'] === 'cartera' && $dias < 0): ?>
                        <span style="font-size:.68rem;color:#fca5a5;display:block"><?php echo abs($dias); ?> días vencido</span>
                        <?php elseif ($ch['estado'] === 'cartera' && $dias <= 7): ?>
                        <span style="font-size:.68rem;color:#fcd34d;display:block">Vence en <?php echo $dias; ?> días</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-muted)"><?php echo htmlspecialchars($ch['cuenta_nombre'] ?? '—', ENT_QUOTES); ?></td>
                    <td style="font-size:.82rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($ch['motivo'] ?: '—', ENT_QUOTES); ?></td>
                    <td style="text-align:right;font-weight:700;color:var(--green-lt)"><?php echo $fmt((float)$ch['monto']); ?></td>
                    <td style="text-align:center"><?php echo $estadoRecibido($ch['estado']); ?></td>
                    <td style="text-align:center">
                        <?php if ($ch['estado'] === 'cartera'): ?>
                        <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap">
                            <form method="POST" action="tes_cheques"
                                  onsubmit="return confirm('¿Marcar este cheque como depositado?')">
                                <input type="hidden" name="accion" value="depositar">
                                <input type="hidden" name="id" value="<?php echo $ch['id']; ?>">
                                <button type="submit" class="btn-accion btn-depositar">
                                    <i class="fa-solid fa-building-columns"></i> Depositar
                                </button>
                            </form>
                            <form method="POST" action="tes_cheques"
                                  onsubmit="return confirm('¿Marcar este cheque como rechazado? El ingreso pendiente será anulado.')">
                                <input type="hidden" name="accion" value="rechazar">
                                <input type="hidden" name="id" value="<?php echo $ch['id']; ?>">
                                <button type="submit" class="btn-accion btn-rechazar">
                                    <i class="fa-solid fa-xmark"></i> Rechazar
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Cheques emitidos ───────────────────────────────────────────────────── -->
<div class="chq-section">
    <div class="chq-section-title">
        <i class="fa-solid fa-paper-plane"></i> Cheques emitidos
        <span style="font-weight:400;text-transform:none;font-size:.75rem;color:var(--text-dim)"> — pendientes de acreditación</span>
    </div>

    <?php if (empty($emitidos)): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state" style="padding:1.25rem 0">
            <i class="fa-solid fa-paper-plane"></i>
            <p>No hay cheques emitidos pendientes.</p>
        </div>
    </div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>N° Cheque</th>
                    <th>Banco</th>
                    <th style="width:90px">Emisión</th>
                    <th style="width:90px">Vencimiento</th>
                    <th>Cuenta</th>
                    <th>Motivo</th>
                    <th style="text-align:right">Monto</th>
                    <th style="text-align:center">Estado</th>
                    <th style="width:160px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($emitidos as $ch):
                $dias = $diasParaVencer($ch['fecha_vencimiento']);
                if ($dias < 0)      $vClass = 'vence-late';
                elseif ($dias <= 7) $vClass = 'vence-soon';
                else                $vClass = 'vence-ok';
            ?>
                <tr>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">
                        <?php echo htmlspecialchars($ch['numero'] ?: '—', ENT_QUOTES); ?>
                    </td>
                    <td style="color:var(--text-muted)"><?php echo htmlspecialchars($ch['banco'] ?: '—', ENT_QUOTES); ?></td>
                    <td style="color:var(--text-dim);font-size:.82rem"><?php echo $fdate($ch['fecha_emision']); ?></td>
                    <td>
                        <span class="vence-badge <?php echo $vClass; ?>"><?php echo $fdate($ch['fecha_vencimiento']); ?></span>
                        <?php if ($dias >= 0 && $dias <= 7): ?>
                        <span style="font-size:.68rem;color:#fcd34d;display:block">Vence en <?php echo $dias; ?> días</span>
                        <?php elseif ($dias < 0): ?>
                        <span style="font-size:.68rem;color:#fca5a5;display:block"><?php echo abs($dias); ?> días vencido</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-muted)"><?php echo htmlspecialchars($ch['cuenta_nombre'] ?? '—', ENT_QUOTES); ?></td>
                    <td style="font-size:.82rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($ch['motivo'] ?: '—', ENT_QUOTES); ?></td>
                    <td style="text-align:right;font-weight:700;color:#fca5a5">— <?php echo $fmt((float)$ch['monto']); ?></td>
                    <td style="text-align:center"><?php echo $estadoEmitido($ch['estado']); ?></td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap">
                            <form method="POST" action="tes_cheques"
                                  onsubmit="return confirm('¿Confirmar que este cheque fue cobrado por el proveedor?')">
                                <input type="hidden" name="accion" value="cobrado">
                                <input type="hidden" name="id" value="<?php echo $ch['id']; ?>">
                                <button type="submit" class="btn-accion btn-cobrado">
                                    <i class="fa-solid fa-check"></i> Acreditado
                                </button>
                            </form>
                            <form method="POST" action="tes_cheques"
                                  onsubmit="return confirm('¿Marcar como devuelto? El egreso pendiente será anulado.')">
                                <input type="hidden" name="accion" value="devuelto">
                                <input type="hidden" name="id" value="<?php echo $ch['id']; ?>">
                                <button type="submit" class="btn-accion btn-devuelto">
                                    <i class="fa-solid fa-rotate-left"></i> Devuelto
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
