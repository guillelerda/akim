<?php
$lista       = $lista       ?? [];
$errores     = $errores     ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;
?>

<style>
.cat-wrap { max-width: 680px; }
.cat-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.cat-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.cat-nueva-form { display:flex; gap:.6rem; align-items:flex-end; flex-wrap:wrap; }
.cat-nueva-form input { flex:1; min-width:180px; }
.badge-on  { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-off { background:rgba(107,114,128,.12); color:#9ca3af; }
</style>

<div class="cat-wrap">

    <div class="cat-header">
        <div>
            <h1>Categorías de Stock</h1>
        </div>
        <a href="stock" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Stock
        </a>
    </div>

    <?php if ($flash_ok): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-circle-check"></i>
        <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <!-- Alta rápida -->
    <div class="card" style="margin-bottom:1.25rem">
        <div class="card-body">
            <p style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim);margin-bottom:.75rem">
                Nueva categoría
            </p>
            <?php if (!empty($errores)): ?>
            <div class="alert alert-error" style="margin-bottom:.75rem">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($errores[0], ENT_QUOTES); ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="stk_categorias" class="cat-nueva-form">
                <input type="hidden" name="accion" value="nueva">
                <input type="text" name="nombre" class="form-control"
                       placeholder="Nombre de la categoría" required autofocus>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Agregar
                </button>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="prv-table-wrap">
        <?php if (empty($lista)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-tag"></i>
            <p>No hay categorías registradas.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="text-align:center;width:100px">Estado</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $cat): ?>
                    <tr style="<?php echo !$cat['habilitada'] ? 'opacity:.5' : ''; ?>">
                        <td style="font-weight:600;color:var(--text)">
                            <?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES); ?>
                        </td>
                        <td style="text-align:center">
                            <span class="badge <?php echo $cat['habilitada'] ? 'badge-on' : 'badge-off'; ?>">
                                <?php echo $cat['habilitada'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td style="text-align:center;display:flex;gap:.35rem;justify-content:center">
                            <form method="POST" action="stk_categorias">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                <button class="btn btn-secondary btn-sm"
                                        title="<?php echo $cat['habilitada'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fa-solid fa-<?php echo $cat['habilitada'] ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="stk_categorias"
                                  onsubmit="return confirm('¿Eliminar esta categoría?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                <button class="btn btn-secondary btn-sm" title="Eliminar"
                                        style="color:#fca5a5">
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

</div>
