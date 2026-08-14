<div class="wiz-card">
    <h2 class="wiz-title">¿Cómo se paga?</h2>
    <p class="wiz-subtitle">
        <?php echo count($wizard['items'] ?? []); ?> ítem(s)
        &nbsp;·&nbsp;
        Total bruto: <strong>$ <?php echo number_format($total_bruto ?? 0, 2, ',', '.'); ?></strong>
    </p>

    <form method="POST" action="vta_wizard_step3">

        <div class="wiz-grid-2">
            <div class="akim-form-group">
                <label for="condicion_pago">Condición de pago</label>
                <select name="condicion_pago" id="condicion_pago" class="akim-input">
                    <?php foreach ($condiciones as $c): ?>
                        <option value="<?php echo htmlspecialchars($c, ENT_QUOTES); ?>"
                            <?php echo (($wizard['condicion_pago'] ?? 'Contado') === $c) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c, ENT_QUOTES); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="akim-form-group">
                <label for="fecha_venta">Fecha</label>
                <input type="date" name="fecha_venta" id="fecha_venta" class="akim-input"
                       value="<?php echo htmlspecialchars($wizard['fecha_venta'] ?? date('Y-m-d'), ENT_QUOTES); ?>">
            </div>

            <div class="akim-form-group">
                <label for="descuento">Descuento (%)</label>
                <input type="number" name="descuento" id="descuento" class="akim-input"
                       value="<?php echo (float)($wizard['descuento'] ?? 0); ?>"
                       min="0" max="100" step="0.5">
            </div>

            <div class="akim-form-group">
                <label>Total neto</label>
                <div class="akim-total-display" id="total-neto">
                    $ <?php echo number_format($total_bruto ?? 0, 2, ',', '.'); ?>
                </div>
            </div>
        </div>

        <div class="akim-form-group">
            <label for="observaciones">Observaciones <span style="color:var(--text-dim);font-weight:400;text-transform:none">(opcional)</span></label>
            <textarea name="observaciones" id="observaciones" class="akim-input" rows="2"
                      placeholder="Notas internas sobre esta venta..."><?php echo htmlspecialchars($wizard['observaciones'] ?? '', ENT_QUOTES); ?></textarea>
        </div>

        <div class="wiz-footer">
            <a href="vta_wizard_step2" class="btn-wiz-back">
                <i class="fa-solid fa-arrow-left"></i> Atrás
            </a>
            <button type="submit" class="btn-wiz-next">
                Revisar <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const bruto  = <?php echo json_encode((float)($total_bruto ?? 0)); ?>;
    const input  = document.getElementById('descuento');
    const display= document.getElementById('total-neto');

    function update() {
        const desc = parseFloat(input.value) || 0;
        const neto = bruto * (1 - desc / 100);
        display.textContent = '$ ' + neto.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    input.addEventListener('input', update);
})();
</script>
