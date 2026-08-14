<div class="wiz-card">
    <h2 class="wiz-title">¿Qué vendemos?</h2>
    <p class="wiz-subtitle">
        Cliente: <strong><?php echo htmlspecialchars($wizard['cliente_label'] ?? '', ENT_QUOTES); ?></strong>
        &nbsp;·&nbsp; Agregá los productos o servicios.
    </p>

    <!-- Agregar ítem -->
    <form method="POST" action="vta_wizard_step2" id="form-agregar" autocomplete="off">
        <input type="hidden" name="accion"         value="agregar_item">
        <input type="hidden" name="producto_id"    id="producto_id"    value="0">
        <input type="hidden" name="producto_nuevo" id="producto_nuevo" value="0">

        <div class="wiz-grid-items">
            <div class="akim-form-group" style="position:relative;margin:0">
                <label>Producto / Servicio</label>
                <input type="text" id="producto_buscar" name="producto_label"
                       placeholder="Nombre o código..." autocomplete="off"
                       class="akim-input">
                <div id="jit-prod-dropdown" class="jit-dropdown" style="display:none"></div>
            </div>
            <div class="akim-form-group" style="margin:0">
                <label>Cant.</label>
                <input type="number" name="cantidad" id="cant-input"
                       value="1" min="0.001" step="0.001" class="akim-input">
            </div>
            <div class="akim-form-group" style="margin:0">
                <label>Precio unit.</label>
                <input type="number" name="precio" id="precio-unit"
                       value="0" min="0" step="0.01" class="akim-input">
            </div>
            <button type="submit" class="btn-wiz-next" style="height:2.65rem;padding:.6rem 1rem">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
    </form>

    <!-- Lista de ítems -->
    <?php if (!empty($wizard['items'])): ?>
    <table class="akim-table" style="margin-bottom:1rem">
        <thead>
            <tr>
                <th>Producto / Servicio</th>
                <th style="text-align:right">Cant.</th>
                <th style="text-align:right">Precio unit.</th>
                <th style="text-align:right">Subtotal</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($wizard['items'] as $i => $item): ?>
            <tr>
                <td style="color:var(--text)"><?php echo htmlspecialchars($item['producto_label'], ENT_QUOTES); ?></td>
                <td style="text-align:right"><?php echo number_format($item['cantidad'], 2); ?></td>
                <td style="text-align:right">$ <?php echo number_format($item['precio_unit'], 2, ',', '.'); ?></td>
                <td style="text-align:right;font-weight:600;color:var(--text)">
                    $ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?>
                </td>
                <td style="text-align:center">
                    <form method="POST" action="vta_wizard_step2" style="display:inline">
                        <input type="hidden" name="accion"   value="quitar_item">
                        <input type="hidden" name="item_idx" value="<?php echo $i; ?>">
                        <button type="submit" class="btn-delete" title="Quitar ítem">✕</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="3">Total bruto</td>
            <td style="text-align:right">
                $ <?php echo number_format(array_sum(array_column($wizard['items'], 'subtotal')), 2, ',', '.'); ?>
            </td>
            <td></td>
        </tr>
        </tbody>
    </table>
    <?php else: ?>
    <div class="akim-empty-state">
        <i class="fa-solid fa-box-open" style="display:block;font-size:1.5rem;margin-bottom:.5rem;opacity:.3"></i>
        Todavía no hay ítems. Buscá un producto arriba para agregar.
    </div>
    <?php endif; ?>

    <!-- Avanzar -->
    <form method="POST" action="vta_wizard_step2">
        <input type="hidden" name="accion" value="siguiente">
        <div class="wiz-footer">
            <a href="vta_wizard_step1" class="btn-wiz-back">
                <i class="fa-solid fa-arrow-left"></i> Atrás
            </a>
            <button type="submit" class="btn-wiz-next"
                    <?php echo empty($wizard['items']) ? 'disabled title="Agregá al menos un ítem"' : ''; ?>>
                Siguiente <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const input    = document.getElementById('producto_buscar');
    const dropdown = document.getElementById('jit-prod-dropdown');
    const hidId    = document.getElementById('producto_id');
    const hidNuevo = document.getElementById('producto_nuevo');
    const hidPrice = document.getElementById('precio-unit');
    let   timer    = null;

    input.addEventListener('input', function() {
        hidId.value    = '0';
        hidNuevo.value = '0';
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }
        timer = setTimeout(() => {
            fetch('ajax/jit/jit_productos.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(renderDropdown);
        }, 220);
    });

    function renderDropdown(items) {
        dropdown.innerHTML = '';
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'jit-option' + (item.nuevo ? ' jit-option--nuevo' : '');
            if (item.nuevo) {
                div.textContent = '✚ Crear producto: "' + item.nombre + '"';
            } else {
                const cod = item.codigo ? '[' + item.codigo + '] ' : '';
                div.textContent = cod + item.nombre + ' — $ ' + parseFloat(item.precio_venta || 0).toFixed(2);
            }
            div.addEventListener('mousedown', (e) => {
                e.preventDefault();
                input.value           = item.nombre;
                hidId.value           = item.id;
                hidNuevo.value        = item.nuevo ? '1' : '0';
                hidPrice.value        = item.precio_venta || 0;
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(div);
        });
        dropdown.style.display = items.length ? 'block' : 'none';
    }

    input.addEventListener('blur',  () => setTimeout(() => dropdown.style.display = 'none', 150));
    input.addEventListener('focus', () => { if (input.value.length >= 2) input.dispatchEvent(new Event('input')); });
})();
</script>
