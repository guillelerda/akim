<?php
$lista       = $lista       ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;
?>

<style>
.prv-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.prv-header h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.prv-header p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }

.prv-search {
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
.prv-search input { background:none; border:none; outline:none; color:var(--text); font-size:.84rem; font-family:inherit; width:200px; }
.prv-search input::placeholder { color:var(--text-dim); }

.prv-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.prv-table-head { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.25rem; border-bottom:1px solid var(--border); gap:.75rem; flex-wrap:wrap; }
.prv-table-title { font-size:.88rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:.45rem; }
.prv-table-title i { color:var(--text-dim); font-size:.8rem; }

.badge-on  { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-off { background:rgba(107,114,128,.12); color:#9ca3af; }
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
<div class="prv-header">
    <div>
        <h1>Proveedores</h1>
        <p>Listado de proveedores registrados</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a href="compras" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Compras
        </a>
        <form method="POST" action="cpa_proveedores" style="display:inline">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="idx" value="">
            <button type="submit" class="btn btn-primary btn-sm"
                    onclick="document.querySelector('[name=idx]').value=''">
                <i class="fa-solid fa-plus"></i> Nuevo Proveedor
            </button>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="prv-table-wrap">
    <div class="prv-table-head">
        <span class="prv-table-title">
            <i class="fa-solid fa-truck"></i>
            <?php echo count($lista); ?> proveedor<?php echo count($lista) !== 1 ? 'es' : ''; ?>
        </span>
        <?php if (!empty($lista)): ?>
        <div class="prv-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="prv-filter" placeholder="Buscar proveedor, CUIT...">
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($lista)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-truck"></i>
        <p>No hay proveedores registrados.</p>
        <form method="POST" action="cpa_proveedores" style="margin-top:.5rem">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="idx" value="">
            <button class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Crear primer proveedor
            </button>
        </form>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl" id="prv-tabla">
            <thead>
                <tr>
                    <th>Razón social</th>
                    <th>CUIT</th>
                    <th>Condición fiscal</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Cond. pago</th>
                    <th style="text-align:center">Estado</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $p): ?>
                <tr style="<?php echo !$p['habilitado'] ? 'opacity:.5' : ''; ?>">
                    <td style="font-weight:600;color:var(--text)">
                        <?php echo htmlspecialchars($p['razon_social'], ENT_QUOTES); ?>
                    </td>
                    <td style="font-family:monospace;font-size:.82rem">
                        <?php echo htmlspecialchars($p['cuit'] ?? '', ENT_QUOTES); ?>
                    </td>
                    <td><?php echo htmlspecialchars($p['condicion_fiscal'] ?? '', ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($p['telefono'] ?? '', ENT_QUOTES); ?></td>
                    <td style="font-size:.82rem"><?php echo htmlspecialchars($p['email'] ?? '', ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($p['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?></td>
                    <td style="text-align:center">
                        <span class="badge <?php echo $p['habilitado'] ? 'badge-on' : 'badge-off'; ?>">
                            <?php echo $p['habilitado'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td style="text-align:center;display:flex;gap:.35rem;justify-content:center">
                        <form method="POST" action="cpa_proveedores">
                            <input type="hidden" name="accion" value="editar">
                            <input type="hidden" name="idx" value="<?php echo htmlspecialchars($p['idx'], ENT_QUOTES); ?>">
                            <button class="btn btn-secondary btn-sm" title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </form>
                        <form method="POST" action="cpa_proveedores"
                              onsubmit="return confirm('<?php echo $p['habilitado'] ? '¿Deshabilitar proveedor?' : '¿Habilitar proveedor?'; ?>')">
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="idx" value="<?php echo htmlspecialchars($p['idx'], ENT_QUOTES); ?>">
                            <button class="btn btn-secondary btn-sm" title="<?php echo $p['habilitado'] ? 'Deshabilitar' : 'Habilitar'; ?>">
                                <i class="fa-solid fa-<?php echo $p['habilitado'] ? 'ban' : 'check'; ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
var fi = document.getElementById('prv-filter');
if (fi) {
    fi.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#prv-tabla tbody tr').forEach(function (tr) {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>
