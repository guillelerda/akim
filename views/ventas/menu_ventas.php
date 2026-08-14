<?php
$ventas    = $ventas    ?? [];
$stats_mes = $stats_mes ?? ['cant' => 0, 'total' => 0];
?>

<style>
/* ── Ventas list ──────────────────────────────────────────────────────────── */
.vta-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.vta-header-text h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -.025em;
}

.vta-header-text p {
    font-size: .83rem;
    color: var(--text-dim);
    margin-top: .2rem;
}

.vta-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .85rem;
    margin-bottom: 1.5rem;
}

.vta-stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: .9rem 1.1rem;
    display: flex;
    align-items: center;
    gap: .85rem;
}

.vta-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
}

.vta-stat-icon--blue   { background: rgba(37,99,235,.15);  color: #60a5fa; }
.vta-stat-icon--green  { background: rgba(22,163,74,.15);  color: #4ade80; }
.vta-stat-icon--purple { background: rgba(124,58,237,.15); color: #a78bfa; }

.vta-stat-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 600;
    color: var(--text-dim);
    display: block;
}

.vta-stat-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text);
    display: block;
    letter-spacing: -.02em;
}

/* Tabla */
.vta-table-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.vta-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--border);
    gap: .75rem;
    flex-wrap: wrap;
}

.vta-table-title {
    font-size: .88rem;
    font-weight: 600;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: .45rem;
}

.vta-table-title i { color: var(--text-dim); font-size: .8rem; }

.vta-search {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: rgba(31,41,55,.5);
    border: 1px solid var(--border-med);
    border-radius: 8px;
    padding: .4rem .8rem;
    color: var(--text-muted);
    font-size: .84rem;
}

.vta-search input {
    background: none;
    border: none;
    outline: none;
    color: var(--text);
    font-size: .84rem;
    font-family: inherit;
    width: 200px;
}

.vta-search input::placeholder { color: var(--text-dim); }

/* Estado badges */
.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .6rem;
    border-radius: 99px;
    font-size: .7rem;
    font-weight: 600;
}

.estado-badge::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
}

.estado-emitida  { background: rgba(37,99,235,.15);  color: #93c5fd; }
.estado-emitida::before  { background: #60a5fa; }
.estado-pagada   { background: rgba(22,163,74,.15);  color: #86efac; }
.estado-pagada::before   { background: #4ade80; }
.estado-anulada  { background: rgba(107,114,128,.12);color: #6b7280; text-decoration: line-through; }
.estado-anulada::before  { background: #6b7280; }

/* Responsive */
@media (max-width: 768px) {
    .vta-stats { grid-template-columns: 1fr 1fr; }
    .vta-header { flex-direction: column; }
    .vta-header .btn { width: 100%; justify-content: center; }
    .vta-search input { width: 130px; }
}
</style>

<!-- Flash messages -->
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
    <a href="vta_wizard_step1" class="btn btn-primary">
        <i class="fa-solid fa-bolt"></i> Nueva Venta
    </a>
</div>

<!-- Stats del mes -->
<div class="vta-stats">
    <div class="vta-stat-card">
        <div class="vta-stat-icon vta-stat-icon--blue">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
            <span class="vta-stat-label">Ventas este mes</span>
            <span class="vta-stat-value"><?php echo (int)$stats_mes['cant']; ?></span>
        </div>
    </div>
    <div class="vta-stat-card">
        <div class="vta-stat-icon vta-stat-icon--green">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
        <div>
            <span class="vta-stat-label">Total del mes</span>
            <span class="vta-stat-value">
                $ <?php echo number_format((float)$stats_mes['total'], 2, ',', '.'); ?>
            </span>
        </div>
    </div>
    <div class="vta-stat-card">
        <div class="vta-stat-icon vta-stat-icon--purple">
            <i class="fa-solid fa-list"></i>
        </div>
        <div>
            <span class="vta-stat-label">Total registradas</span>
            <span class="vta-stat-value"><?php echo count($ventas); ?></span>
        </div>
    </div>
</div>

<!-- Tabla de ventas -->
<div class="vta-table-wrap">
    <div class="vta-table-header">
        <span class="vta-table-title">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Comprobantes emitidos
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
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ventas as $v):
                $estado = $v['estado'] ?? 'emitida';
                $anulada = $estado === 'anulada';
            ?>
                <tr style="<?php echo $anulada ? 'opacity:.5' : ''; ?>">
                    <td>
                        <span style="font-weight:600;color:var(--text);font-variant-numeric:tabular-nums">
                            #<?php echo str_pad((int)($v['numero'] ?? 0), 4, '0', STR_PAD_LEFT); ?>
                        </span>
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
                    <td>
                        <?php echo htmlspecialchars($v['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?>
                    </td>
                    <td style="text-align:right;font-weight:700;color:<?php echo $anulada ? 'var(--text-dim)' : 'var(--green-lt)'; ?>">
                        $ <?php echo number_format((float)($v['total'] ?? 0), 2, ',', '.'); ?>
                    </td>
                    <td style="text-align:center">
                        <span class="estado-badge estado-<?php echo htmlspecialchars($estado, ENT_QUOTES); ?>">
                            <?php echo ucfirst($estado); ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <?php if (!$anulada): ?>
                        <!-- TODO: ver detalle / anular -->
                        <button class="btn btn-secondary btn-sm" title="Ver detalle" disabled>
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
// Filtro cliente/número en tiempo real
var filterInput = document.getElementById('vta-filter');
if (filterInput) {
    filterInput.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#vta-tabla tbody tr').forEach(function(tr) {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>
