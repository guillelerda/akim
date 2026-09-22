<?php
$producto   = $producto   ?? null;
$es_nuevo   = $es_nuevo   ?? true;
$categorias = $categorias ?? [];
$errores    = $errores    ?? [];

$v = function(string $campo, $def = '') use ($producto) {
    return htmlspecialchars((string)($producto[$campo] ?? $def), ENT_QUOTES);
};
?>

<style>
.prd-edit-wrap { max-width:780px; }
.prd-edit-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.prd-edit-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

.form-section { margin-bottom:1.25rem; }
.form-section-title {
    font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em;
    color:var(--text-dim); padding-bottom:.5rem; border-bottom:1px solid var(--border);
    margin-bottom:.9rem;
}
.form-grid { display:grid; gap:.75rem; }
.form-grid-2 { grid-template-columns:1fr 1fr; }
.form-grid-3 { grid-template-columns:1fr 1fr 1fr; }
.form-grid-4 { grid-template-columns:1fr 1fr 1fr 1fr; }

.form-label { font-size:.8rem; font-weight:600; color:var(--text-dim); display:block; margin-bottom:.3rem; }
.form-label sup { color:#f87171; }
.markup-display { font-size:.82rem; color:var(--text-dim); margin-top:.35rem; }
.markup-display span { font-weight:700; color:var(--accent); }

.opt-row { display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap; }
.opt-label { display:flex; align-items:center; gap:.5rem; font-size:.88rem; color:var(--text); cursor:pointer; }
.opt-label input[type=checkbox] { width:16px; height:16px; cursor:pointer; }

.prd-edit-actions { display:flex; gap:.6rem; justify-content:flex-end; padding-top:1rem; border-top:1px solid var(--border); margin-top:1.25rem; }

@media(max-width:600px) {
    .form-grid-2, .form-grid-3, .form-grid-4 { grid-template-columns:1fr; }
}
</style>

<div class="prd-edit-wrap">

    <div class="prd-edit-header">
        <h1><?php echo $es_nuevo ? 'Nuevo Producto' : 'Editar Producto'; ?></h1>
        <a href="stk_productos" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php echo htmlspecialchars($errores[0], ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="stk_productos_edit" id="prd-form" enctype="multipart/form-data">

        <!-- Identificación -->
        <div class="form-section">
            <div class="form-section-title">Identificación</div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="form-label" for="codigo">Código / SKU</label>
                    <input type="text" id="codigo" name="codigo" class="form-control"
                           value="<?php echo $v('codigo'); ?>"
                           placeholder="Ej: ABC-001 (opcional)">
                </div>
                <div>
                    <label class="form-label" for="nombre">Nombre <sup>*</sup></label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required autofocus
                           value="<?php echo $v('nombre'); ?>"
                           placeholder="Nombre del artículo">
                </div>
            </div>
            <div class="form-grid" style="margin-top:.75rem">
                <div>
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control"
                              rows="2" placeholder="Descripción opcional del artículo"><?php echo $v('descripcion'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Clasificación -->
        <div class="form-section">
            <div class="form-section-title">Clasificación</div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="form-label" for="categoria_id">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="form-control">
                        <option value="">— Sin categoría —</option>
                        <?php foreach ($categorias as $cat):
                            $sel = ((int)($producto['categoria_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="unidad">Unidad de medida</label>
                    <select id="unidad" name="unidad" class="form-control">
                        <?php
                        $unidades = ['unidad','kg','g','lt','ml','m','cm','m2','caja','paquete','docena','par'];
                        $u_actual = $producto['unidad'] ?? 'unidad';
                        foreach ($unidades as $u):
                        ?>
                        <option value="<?php echo $u; ?>" <?php echo $u === $u_actual ? 'selected' : ''; ?>>
                            <?php echo $u; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Precios -->
        <div class="form-section">
            <div class="form-section-title">Precios</div>
            <div class="form-grid form-grid-4">
                <div>
                    <label class="form-label" for="precio_costo">Precio costo ($)</label>
                    <input type="number" id="precio_costo" name="precio_costo"
                           class="form-control" step="0.01" min="0"
                           value="<?php echo $v('precio_costo', '0'); ?>"
                           oninput="calcMarkup()">
                </div>
                <div>
                    <label class="form-label" for="precio_venta">Precio venta ($)</label>
                    <input type="number" id="precio_venta" name="precio_venta"
                           class="form-control" step="0.01" min="0"
                           value="<?php echo $v('precio_venta', '0'); ?>"
                           oninput="calcMarkup()">
                </div>
                <div>
                    <label class="form-label">Markup</label>
                    <div class="form-control" style="background:var(--bg);cursor:default">
                        <span id="markup-val" style="font-weight:700;color:var(--accent)">—</span>
                    </div>
                    <div class="markup-display">Ganancia sobre costo</div>
                </div>
                <div>
                    <label class="form-label">Margen técnico</label>
                    <div class="form-control" style="background:var(--bg);cursor:default">
                        <span id="margen-val" style="font-weight:700;color:var(--accent)">—</span>
                    </div>
                    <div class="markup-display">(Venta − Costo) / Venta</div>
                </div>
            </div>
        </div>

        <!-- Stock -->
        <div class="form-section">
            <div class="form-section-title">Stock</div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="form-label" for="stock_actual">Stock actual</label>
                    <input type="number" id="stock_actual" name="stock_actual"
                           class="form-control" step="0.001" min="0"
                           value="<?php echo $v('stock_actual', '0'); ?>">
                </div>
                <div>
                    <label class="form-label" for="stock_minimo">Stock mínimo</label>
                    <input type="number" id="stock_minimo" name="stock_minimo"
                           class="form-control" step="0.001" min="0"
                           value="<?php echo $v('stock_minimo', '0'); ?>"
                           placeholder="0 = sin control">
                </div>
            </div>
        </div>

        <!-- Imagen del producto -->
        <div class="form-section">
            <div class="form-section-title">Imagen del producto</div>
            <?php $img_actual = $producto['imagen'] ?? ''; ?>
            <div class="form-grid form-grid-2">
                <div>
                    <?php if ($img_actual): ?>
                    <div style="margin-bottom:.75rem">
                        <img src="<?php echo htmlspecialchars($img_actual, ENT_QUOTES); ?>?v=<?php echo time(); ?>"
                             id="img-current"
                             style="max-height:110px;max-width:100%;border-radius:8px;border:1px solid var(--border);background:var(--bg);padding:4px;display:block"
                             alt="Imagen actual">
                    </div>
                    <?php endif; ?>
                    <label class="form-label" for="producto_imagen">
                        <?php echo $img_actual ? 'Reemplazar imagen' : 'Subir imagen'; ?>
                    </label>
                    <input type="file" id="producto_imagen" name="producto_imagen"
                           class="form-control" accept="image/png,image/jpeg,image/gif,image/webp"
                           onchange="previewImagen(this)">
                    <div class="markup-display">JPG, PNG, WEBP · se guarda en el repositorio de la empresa</div>
                </div>
                <div>
                    <div id="img-preview-wrap" style="display:none">
                        <div class="form-label">Vista previa</div>
                        <img id="img-preview-src"
                             style="max-height:110px;max-width:100%;border-radius:8px;border:1px solid var(--accent);background:var(--bg);padding:4px;display:block"
                             alt="Vista previa">
                    </div>
                </div>
            </div>
        </div>

        <!-- Opciones -->
        <div class="form-section">
            <div class="form-section-title">Opciones</div>
            <div class="opt-row">
                <label class="opt-label">
                    <input type="checkbox" name="habilitado" value="1"
                           <?php echo ($producto['habilitado'] ?? 1) ? 'checked' : ''; ?>>
                    Producto habilitado
                </label>
                <label class="opt-label">
                    <input type="checkbox" name="en_tienda" value="1"
                           <?php echo !empty($producto['en_tienda']) ? 'checked' : ''; ?>>
                    Mostrar en tienda
                </label>
            </div>
        </div>

        <div class="prd-edit-actions">
            <a href="stk_productos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?php echo $es_nuevo ? 'Crear producto' : 'Guardar cambios'; ?>
            </button>
        </div>

    </form>
</div>

<script>
function calcMarkup() {
    const costo  = parseFloat(document.getElementById('precio_costo').value) || 0;
    const venta  = parseFloat(document.getElementById('precio_venta').value) || 0;
    const elMk   = document.getElementById('markup-val');
    const elMg   = document.getElementById('margen-val');

    if (elMk) {
        if (costo <= 0) { elMk.textContent = '—'; elMk.style.color = 'var(--accent)'; }
        else {
            const mk = ((venta - costo) / costo) * 100;
            elMk.textContent = mk.toFixed(1) + '%';
            elMk.style.color = mk < 0 ? '#f87171' : 'var(--accent)';
        }
    }

    if (elMg) {
        if (venta <= 0) { elMg.textContent = '—'; elMg.style.color = 'var(--accent)'; }
        else {
            const mg = ((venta - costo) / venta) * 100;
            elMg.textContent = mg.toFixed(1) + '%';
            elMg.style.color = mg < 0 ? '#f87171' : 'var(--accent)';
        }
    }
}
document.addEventListener('DOMContentLoaded', calcMarkup);

function previewImagen(input) {
    const wrap = document.getElementById('img-preview-wrap');
    const img  = document.getElementById('img-preview-src');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrap.style.display = ''; };
        reader.readAsDataURL(input.files[0]);
    } else {
        wrap.style.display = 'none';
    }
}
</script>
