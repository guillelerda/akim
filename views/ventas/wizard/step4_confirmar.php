<div class="wiz-card">
    <h2 class="wiz-title">Confirmá la venta</h2>
    <p class="wiz-subtitle">Revisá los datos antes de registrar el comprobante.</p>

    <!-- Datos de cabecera -->
    <div class="wiz-resumen">
        <div class="wiz-resumen-row">
            <span class="wiz-resumen-label">Cliente</span>
            <span class="wiz-resumen-value" style="color:#60a5fa;font-weight:600">
                <?php echo htmlspecialchars($wizard['cliente_label'] ?? '', ENT_QUOTES); ?>
            </span>
        </div>
        <div class="wiz-resumen-row">
            <span class="wiz-resumen-label">Fecha</span>
            <span class="wiz-resumen-value">
                <?php
                $fd = DateTime::createFromFormat('Y-m-d', $wizard['fecha_venta'] ?? date('Y-m-d'));
                echo $fd ? $fd->format('d/m/Y') : ($wizard['fecha_venta'] ?? '');
                ?>
            </span>
        </div>
        <div class="wiz-resumen-row">
            <span class="wiz-resumen-label">Condición de pago</span>
            <span class="wiz-resumen-value">
                <?php echo htmlspecialchars($wizard['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?>
            </span>
        </div>
        <?php if (($wizard['descuento'] ?? 0) > 0): ?>
        <div class="wiz-resumen-row">
            <span class="wiz-resumen-label">Descuento</span>
            <span class="wiz-resumen-value" style="color:#fbbf24">
                <?php echo (float)$wizard['descuento']; ?>%
            </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($wizard['observaciones'])): ?>
        <div class="wiz-resumen-row">
            <span class="wiz-resumen-label">Observaciones</span>
            <span class="wiz-resumen-value" style="font-style:italic;color:var(--text-muted)">
                <?php echo htmlspecialchars($wizard['observaciones'], ENT_QUOTES); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ítems -->
    <table class="akim-table">
        <thead>
            <tr>
                <th>Producto / Servicio</th>
                <th style="text-align:right">Cant.</th>
                <th style="text-align:right">Precio unit.</th>
                <th style="text-align:right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($wizard['items'] ?? [] as $item): ?>
            <tr>
                <td style="color:var(--text)"><?php echo htmlspecialchars($item['producto_label'], ENT_QUOTES); ?></td>
                <td style="text-align:right"><?php echo number_format($item['cantidad'], 2); ?></td>
                <td style="text-align:right">$ <?php echo number_format($item['precio_unit'], 2, ',', '.'); ?></td>
                <td style="text-align:right">$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php if (($wizard['descuento'] ?? 0) > 0): ?>
            <tr>
                <td colspan="3" style="color:var(--text-muted)">Subtotal bruto</td>
                <td style="text-align:right">$ <?php echo number_format($total_bruto ?? 0, 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td colspan="3" style="color:#fbbf24">
                    Descuento (<?php echo (float)$wizard['descuento']; ?>%)
                </td>
                <td style="text-align:right;color:#fbbf24">
                    − $ <?php echo number_format(($total_bruto ?? 0) * ($wizard['descuento'] / 100), 2, ',', '.'); ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td style="text-align:right">
                    <strong>$ <?php echo number_format($total_neto ?? 0, 2, ',', '.'); ?></strong>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Confirmar -->
    <form method="POST" action="vta_wizard_confirmar">
        <div class="wiz-footer">
            <a href="vta_wizard_step3" class="btn-wiz-back">
                <i class="fa-solid fa-arrow-left"></i> Atrás
            </a>
            <button type="submit" class="btn-wiz-next btn-wiz-confirm">
                <i class="fa-solid fa-check"></i> Confirmar venta
            </button>
        </div>
    </form>
</div>
