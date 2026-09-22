<?php
$lista       = $lista       ?? [];
$errores     = $errores     ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;
?>

<style>
.dep-wrap { max-width: 720px; }
.dep-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.dep-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.badge-default { background:rgba(234,179,8,.18); color:#fde047; }
.badge-hab  { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-dis  { background:rgba(107,114,128,.12); color:#9ca3af; }
.dep-nueva-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
@media(max-width:540px){ .dep-nueva-grid { grid-template-columns:1fr; } }
</style>

<div class="dep-wrap">

    <div class="dep-header">
        <h1>Depósitos</h1>
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
                Nuevo depósito
            </p>
            <?php if (!empty($errores)): ?>
            <div class="alert alert-error" style="margin-bottom:.75rem">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($errores[0], ENT_QUOTES); ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="stk_depositos">
                <input type="hidden" name="accion" value="nuevo">
                <div class="dep-nueva-grid" style="margin-bottom:.65rem">
                    <div>
                        <label class="form-label" style="font-size:.8rem">Nombre *</label>
                        <input type="text" name="nombre" class="form-control"
                               placeholder="Ej: SUCURSAL NORTE" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Descripción</label>
                        <input type="text" name="descripcion" class="form-control"
                               placeholder="Descripción opcional">
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;cursor:pointer">
                        <input type="checkbox" name="es_default" value="1">
                        Establecer como depósito por defecto
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="prv-table-wrap">
        <?php if (empty($lista)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-warehouse"></i>
            <p>No hay depósitos registrados.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th style="text-align:center;width:110px">Default</th>
                        <th style="text-align:center;width:90px">Estado</th>
                        <th style="width:120px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $dep): ?>
                    <tr style="<?php echo !$dep['habilitado'] ? 'opacity:.5' : ''; ?>">
                        <td style="font-weight:600;color:var(--text)">
                            <?php echo htmlspecialchars($dep['nombre'], ENT_QUOTES); ?>
                        </td>
                        <td style="font-size:.83rem;color:var(--text-dim)">
                            <?php echo htmlspecialchars($dep['descripcion'] ?: '—', ENT_QUOTES); ?>
                        </td>
                        <td style="text-align:center">
                            <?php if ($dep['es_default']): ?>
                            <span class="badge badge-default"><i class="fa-solid fa-star" style="font-size:.65rem"></i> Default</span>
                            <?php else: ?>
                            <form method="POST" action="stk_depositos">
                                <input type="hidden" name="accion" value="setDefault">
                                <input type="hidden" name="id" value="<?php echo (int)$dep['id']; ?>">
                                <button class="btn btn-secondary btn-sm" title="Establecer como default" style="font-size:.72rem">
                                    <i class="fa-regular fa-star"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center">
                            <span class="badge <?php echo $dep['habilitado'] ? 'badge-hab' : 'badge-dis'; ?>">
                                <?php echo $dep['habilitado'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:.3rem;justify-content:center">
                            <?php if (!$dep['es_default']): ?>
                            <form method="POST" action="stk_depositos">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int)$dep['id']; ?>">
                                <button class="btn btn-secondary btn-sm"
                                        title="<?php echo $dep['habilitado'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fa-solid fa-<?php echo $dep['habilitado'] ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="stk_depositos"
                                  onsubmit="return confirm('¿Eliminar este depósito?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int)$dep['id']; ?>">
                                <button class="btn btn-secondary btn-sm" title="Eliminar" style="color:#fca5a5">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span style="font-size:.75rem;color:var(--text-dim);padding:.3rem .5rem">Protegido</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
