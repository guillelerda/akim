<?php
$lista       = $lista       ?? [];
$cats_map    = $cats_map    ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;
?>

<style>
.prd-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap; }
.prd-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

.prd-toolbar { display:flex; gap:.6rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap; }
.prd-search  { flex:1; min-width:200px; max-width:320px; }

.badge-hab  { background:rgba(22,163,74,.15);   color:#86efac; }
.badge-dis  { background:rgba(107,114,128,.12); color:#9ca3af; }
.badge-tienda { background:rgba(37,99,235,.15); color:#93c5fd; }
.badge-stock-ok  { background:rgba(22,163,74,.12);   color:#86efac; }
.badge-stock-low { background:rgba(239,68,68,.18);   color:#fca5a5; }
.badge-stock-na  { background:rgba(107,114,128,.10); color:#9ca3af; }
</style>

<div class="prd-header">
    <h1>Productos</h1>
    <div style="display:flex;gap:.6rem">
        <a href="stock" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Stock
        </a>
        <form method="POST" action="stk_productos">
            <input type="hidden" name="accion" value="nuevo">
            <button class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Nuevo
            </button>
        </form>
    </div>
</div>

<?php if ($flash_ok): ?>
<div class="alert alert-success" style="margin-bottom:1rem">
    <i class="fa-solid fa-circle-check"></i>
    <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert alert-error" style="margin-bottom:1rem">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<div class="prd-toolbar">
    <input type="text" id="prd-filter" class="form-control prd-search"
           placeholder="Buscar por nombre o código…"
           oninput="filtrarProductos()">

    <?php if (!empty($cats_map)): ?>
    <select id="prd-cat" class="form-control" style="min-width:180px;max-width:260px" onchange="filtrarProductos()">
        <option value="">Todas las categorías</option>
        <?php foreach ($cats_map as $cid => $cnombre): ?>
        <option value="<?php echo (int)$cid; ?>">
            <?php echo htmlspecialchars($cnombre, ENT_QUOTES); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <span id="prd-count" style="font-size:.8rem;color:var(--text-dim)">
        <?php echo count($lista); ?> producto<?php echo count($lista) !== 1 ? 's' : ''; ?>
    </span>
</div>

<div class="prv-table-wrap">
<?php if (empty($lista)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-boxes-stacked"></i>
        <p>No hay productos registrados.</p>
        <form method="POST" action="stk_productos">
            <input type="hidden" name="accion" value="nuevo">
            <button class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Crear primer producto
            </button>
        </form>
    </div>
<?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl" id="prd-table">
            <thead>
                <tr>
                    <th style="width:54px"></th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th style="text-align:right">Costo</th>
                    <th style="text-align:right">Venta</th>
                    <th style="text-align:center">Stock</th>
                    <th style="text-align:center">Estado</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $p):
                $bajo_stock = ($p['stock_minimo'] > 0 && $p['stock_actual'] < $p['stock_minimo']);
                $sin_min    = ($p['stock_minimo'] == 0);
            ?>
                <tr data-search="<?php echo strtolower(htmlspecialchars($p['nombre'].' '.$p['codigo'], ENT_QUOTES)); ?>"
                    data-cat="<?php echo (int)($p['categoria_id'] ?? 0); ?>"
                    style="<?php echo !$p['habilitado'] ? 'opacity:.45' : ''; ?>">
                    <td style="padding:.3rem .5rem;text-align:center">
                        <?php if (!empty($p['imagen'])): ?>
                        <img src="<?php echo htmlspecialchars($p['imagen'], ENT_QUOTES); ?>"
                             loading="lazy"
                             style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid var(--border);display:block">
                        <?php else: ?>
                        <div style="width:44px;height:44px;border-radius:6px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-dim);opacity:.35;font-size:.9rem">
                            <i class="fa-solid fa-image"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-family:monospace;font-size:.82rem;color:var(--text-dim)">
                        <?php echo htmlspecialchars($p['codigo'] ?: '—', ENT_QUOTES); ?>
                    </td>
                    <td style="font-weight:600;color:var(--text)">
                        <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>
                        <?php if ($p['en_tienda']): ?>
                        <span class="badge badge-tienda" style="margin-left:.35rem;font-size:.65rem">Tienda</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.83rem;color:var(--text-dim)">
                        <?php echo htmlspecialchars($p['categoria_nombre'] ?? '—', ENT_QUOTES); ?>
                    </td>
                    <td style="text-align:right;font-size:.88rem">
                        $ <?php echo number_format((float)$p['precio_costo'], 2, ',', '.'); ?>
                    </td>
                    <td style="text-align:right;font-size:.88rem;font-weight:600">
                        $ <?php echo number_format((float)$p['precio_venta'], 2, ',', '.'); ?>
                    </td>
                    <td style="text-align:center">
                        <?php if ($sin_min): ?>
                        <span class="badge badge-stock-na">
                            <?php echo number_format((float)$p['stock_actual'], 0, ',', '.'); ?>
                        </span>
                        <?php elseif ($bajo_stock): ?>
                        <span class="badge badge-stock-low" title="Bajo mínimo (<?php echo $p['stock_minimo']; ?>)">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size:.65rem"></i>
                            <?php echo number_format((float)$p['stock_actual'], 0, ',', '.'); ?>
                        </span>
                        <?php else: ?>
                        <span class="badge badge-stock-ok">
                            <?php echo number_format((float)$p['stock_actual'], 0, ',', '.'); ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?php echo $p['habilitado'] ? 'badge-hab' : 'badge-dis'; ?>">
                            <?php echo $p['habilitado'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td style="display:flex;gap:.3rem;justify-content:center">
                        <form method="POST" action="stk_productos">
                            <input type="hidden" name="accion" value="editar">
                            <input type="hidden" name="idx" value="<?php echo htmlspecialchars($p['idx'], ENT_QUOTES); ?>">
                            <button class="btn btn-secondary btn-sm" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </form>
                        <form method="POST" action="stk_productos">
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="idx" value="<?php echo htmlspecialchars($p['idx'], ENT_QUOTES); ?>">
                            <button class="btn btn-secondary btn-sm"
                                    title="<?php echo $p['habilitado'] ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fa-solid fa-<?php echo $p['habilitado'] ? 'ban' : 'check'; ?>"></i>
                            </button>
                        </form>
                        <form method="POST" action="stk_productos"
                              onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="idx" value="<?php echo htmlspecialchars($p['idx'], ENT_QUOTES); ?>">
                            <button class="btn btn-secondary btn-sm" title="Eliminar" style="color:#fca5a5">
                                <i class="fa-solid fa-trash-can"></i>
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
function filtrarProductos() {
    const q   = (document.getElementById('prd-filter')?.value || '').trim().toLowerCase();
    const cat = (document.getElementById('prd-cat')?.value   || '');
    const rows = document.querySelectorAll('#prd-table tbody tr');
    let visible = 0;
    rows.forEach(r => {
        const matchQ   = !q   || r.dataset.search.includes(q);
        const matchCat = !cat || r.dataset.cat === cat;
        const show = matchQ && matchCat;
        r.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const lbl = document.getElementById('prd-count');
    if (lbl) lbl.textContent = visible + ' producto' + (visible !== 1 ? 's' : '');
}
</script>
