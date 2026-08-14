<?php
$lista       = $lista       ?? [];
$stats_mes   = $stats_mes   ?? ['cant' => 0, 'total' => 0];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;

$estadoClass = fn(string $e): string => match($e) {
    'pagada'   => 'badge-green',
    'anulada'  => 'badge-gray',
    default    => 'badge-amber',
};
?>

<style>
.fac-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.fac-header h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.fac-header p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }

.fac-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.85rem; margin-bottom:1.5rem; }
.fac-stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.9rem 1.1rem; display:flex; align-items:center; gap:.85rem; }
.fac-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.fac-stat-icon--blue   { background:rgba(37,99,235,.15);  color:#60a5fa; }
.fac-stat-icon--green  { background:rgba(22,163,74,.15);  color:#4ade80; }
.fac-stat-icon--amber  { background:rgba(217,119,6,.15);  color:#fcd34d; }
.fac-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.fac-stat-value { font-size:1.15rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }

.fac-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.fac-table-head { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.25rem; border-bottom:1px solid var(--border); gap:.75rem; flex-wrap:wrap; }
.fac-table-title { font-size:.88rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:.45rem; }
.fac-table-title i { color:var(--text-dim); font-size:.8rem; }

.fac-search { display:flex; align-items:center; gap:.5rem; background:rgba(31,41,55,.5); border:1px solid var(--border-med); border-radius:8px; padding:.4rem .8rem; color:var(--text-muted); font-size:.84rem; }
.fac-search input { background:none; border:none; outline:none; color:var(--text); font-size:.84rem; font-family:inherit; width:200px; }
.fac-search input::placeholder { color:var(--text-dim); }

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
        <div class="fac-stat-icon fac-stat-icon--blue">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
            <span class="fac-stat-label">Este mes</span>
            <span class="fac-stat-value"><?php echo (int)$stats_mes['cant']; ?></span>
        </div>
    </div>
    <div class="fac-stat-card">
        <div class="fac-stat-icon fac-stat-icon--green">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
        <div>
            <span class="fac-stat-label">Total comprado (mes)</span>
            <span class="fac-stat-value">$ <?php echo number_format((float)$stats_mes['total'], 2, ',', '.'); ?></span>
        </div>
    </div>
    <div class="fac-stat-card">
        <div class="fac-stat-icon fac-stat-icon--amber">
            <i class="fa-solid fa-list"></i>
        </div>
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
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $f):
                $estado  = $f['estado'] ?? 'pendiente';
                $anulada = $estado === 'anulada';
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
                        <?php if (!$anulada): ?>
                        <div style="display:flex;gap:.3rem;justify-content:center">
                            <?php if ($estado === 'pendiente'): ?>
                            <form method="POST" action="cpa_facturas"
                                  onsubmit="return confirm('¿Marcar como pagada?')">
                                <input type="hidden" name="accion" value="pagada">
                                <input type="hidden" name="idx" value="<?php echo htmlspecialchars($f['idx'], ENT_QUOTES); ?>">
                                <button class="btn btn-success btn-sm" title="Marcar pagada">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" action="cpa_facturas"
                                  onsubmit="return confirm('¿Anular esta factura?')">
                                <input type="hidden" name="accion" value="anular">
                                <input type="hidden" name="idx" value="<?php echo htmlspecialchars($f['idx'], ENT_QUOTES); ?>">
                                <button class="btn btn-danger btn-sm" title="Anular">
                                    <i class="fa-solid fa-ban"></i>
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

<script>
var fi = document.getElementById('fac-filter');
if (fi) {
    fi.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#fac-tabla tbody tr').forEach(function (tr) {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>
