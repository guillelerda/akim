<div class="wiz-card">
    <h2 class="wiz-title">Confirmá la venta</h2>
    <p class="wiz-subtitle">Revisá los datos antes de registrar el comprobante.</p>

    <?php if (!empty($flash_error)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

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
    <?php $modo_factura = $wizard['modo_factura'] ?? 'manual'; ?>
    <form method="POST" action="vta_wizard_confirmar" id="form-confirmar">
        <input type="hidden" name="modo_factura" value="<?php echo $modo_factura; ?>">

        <!-- Indicador de tipo de comprobante -->
        <div class="wiz-modo-row <?php echo $modo_factura === 'fiscal' ? 'wiz-modo-row--fiscal' : ''; ?>">
            <span class="wiz-modo-label">Tipo de emisión</span>
            <?php if ($modo_factura === 'fiscal'): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:.5rem">
                <span class="wiz-modo-badge wiz-modo-fiscal">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    Factura Electrónica AFIP (Oficial)
                </span>
                <span style="font-size:.78rem;color:#4ade80;font-weight:600">
                    <i class="fa-solid fa-shield-halved"></i> Se solicitará CAE oficial en AFIP
                </span>
            </div>
            <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:.5rem">
                <span class="wiz-modo-badge wiz-modo-manual">
                    <i class="fa-solid fa-file-lines"></i>
                    Ticket / Remito Interno
                </span>
                <span style="font-size:.78rem;color:var(--text-dim)">
                    Comprobante no fiscal (Letra X)
                </span>
            </div>
            <?php endif; ?>
        </div>

        <div class="wiz-footer">
            <a href="vta_wizard_step3" class="btn-wiz-back" id="btn-atras">
                <i class="fa-solid fa-arrow-left"></i> Atrás
            </a>
            <?php if ($modo_factura === 'fiscal'): ?>
            <button type="submit" class="btn-wiz-next btn-wiz-confirm btn-wiz-fiscal" id="btn-confirmar">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                Confirmar y Emitir en AFIP
            </button>
            <?php else: ?>
            <button type="submit" class="btn-wiz-next btn-wiz-confirm" id="btn-confirmar">
                <i class="fa-solid fa-check"></i>
                Confirmar Venta Interna
            </button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Overlay de espera -->
    <div id="wiz-overlay" style="display:none;position:fixed;inset:0;z-index:9999;
         background:rgba(3,7,18,.82);backdrop-filter:blur(4px);
         align-items:center;justify-content:center;flex-direction:column;gap:1.25rem">
        <div class="wiz-spinner"></div>
        <?php if ($modo_factura === 'fiscal'): ?>
        <p style="color:#f9fafb;font-size:.95rem;font-weight:600;margin:0">Emitiendo factura en AFIP…</p>
        <p style="color:#9ca3af;font-size:.8rem;margin:0">Por favor esperá, esto puede demorar unos segundos.</p>
        <?php else: ?>
        <p style="color:#f9fafb;font-size:.95rem;font-weight:600;margin:0">Registrando venta…</p>
        <?php endif; ?>
    </div>

<style>
.wiz-modo-row   { display:flex; align-items:center; gap:.85rem; margin:1.25rem 0 0; padding:.9rem 1rem; border-radius:var(--radius); border:1px solid var(--border); background:var(--surface-2, rgba(255,255,255,.03)); }
.wiz-modo-label { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--text-dim); flex-shrink:0; }
.wiz-modo-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .75rem; border-radius:99px; font-size:.78rem; font-weight:700; }
.wiz-modo-fiscal { background:rgba(22,163,74,.18); color:#4ade80; border:1px solid rgba(22,163,74,.3); }
.wiz-modo-manual { background:rgba(100,116,139,.15); color:var(--text-dim); border:1px solid var(--border); }
.btn-wiz-fiscal { background:linear-gradient(135deg,#16a34a,#15803d) !important; border-color:#15803d !important; }
.wiz-spinner { width:44px; height:44px; border:4px solid rgba(255,255,255,.15); border-top-color:#4ade80; border-radius:50%; animation:spin .75s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>

<script>
(function () {
    var form     = document.getElementById('form-confirmar');
    var btn      = document.getElementById('btn-confirmar');
    var btnAtras = document.getElementById('btn-atras');
    var overlay  = document.getElementById('wiz-overlay');

    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.style.opacity = '.6';
        btn.style.cursor  = 'not-allowed';
        if (btnAtras) btnAtras.style.pointerEvents = 'none';
        overlay.style.display = 'flex';
    });
})();
</script>
</div>
