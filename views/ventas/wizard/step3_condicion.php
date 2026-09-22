<div class="wiz-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.25rem">
        <h2 class="wiz-title" style="margin:0">¿Cómo se paga?</h2>
        <?php if (($wizard['modo_factura'] ?? 'manual') === 'fiscal'): ?>
        <span class="wiz-modo-badge wiz-modo-fiscal"><i class="fa-solid fa-file-invoice-dollar"></i> Factura AFIP</span>
        <?php else: ?>
        <span class="wiz-modo-badge wiz-modo-manual"><i class="fa-solid fa-file-lines"></i> Ticket / Remito</span>
        <?php endif; ?>
    </div>
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

        <!-- ── Cobro en tesorería — solo visible cuando condicion = Contado ── -->
        <div id="cobro-section" style="display:none">
        <div class="wiz-section-title" style="margin-top:1.5rem;margin-bottom:.75rem;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);">
            Cobro inmediato <span style="font-weight:400;text-transform:none">(opcional)</span>
        </div>

        <?php
        $cobro_prev = $wizard['cobro'] ?? [];
        $cobro_medio_prev = $cobro_prev['medio'] ?? 'sin_cobro';
        $cuentas_ef  = array_filter($cuentas_tes ?? [], fn($c) => $c['tipo'] === 'efectivo');
        $cuentas_bco = array_filter($cuentas_tes ?? [], fn($c) => $c['tipo'] === 'banco');
        ?>

        <div class="akim-form-group">
            <label for="cobro_medio">Medio de cobro</label>
            <select name="cobro_medio" id="cobro_medio" class="akim-input" onchange="akimToggleCobro(this.value)">
                <option value="sin_cobro" <?php echo $cobro_medio_prev === 'sin_cobro' ? 'selected' : ''; ?>>— Sin cobro registrado —</option>
                <option value="efectivo"      <?php echo $cobro_medio_prev === 'efectivo'      ? 'selected' : ''; ?>>Efectivo</option>
                <option value="transferencia" <?php echo $cobro_medio_prev === 'transferencia' ? 'selected' : ''; ?>>Transferencia bancaria</option>
                <option value="cheque"        <?php echo $cobro_medio_prev === 'cheque'        ? 'selected' : ''; ?>>Cheque recibido</option>
                <?php if (!empty($tarjetas_tes)): ?>
                <option value="tarjeta"       <?php echo $cobro_medio_prev === 'tarjeta'       ? 'selected' : ''; ?>>Tarjeta</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Efectivo -->
        <div id="cobro-ef" class="cobro-panel" style="display:none">
            <div class="akim-form-group">
                <label for="cobro_ef_id">Caja / Efectivo</label>
                <select name="cobro_ef_id" id="cobro_ef_id" class="akim-input">
                    <option value="">— Seleccioná cuenta —</option>
                    <?php foreach ($cuentas_ef as $ct): ?>
                        <option value="<?php echo $ct['id']; ?>"
                            <?php echo ((int)($cobro_prev['cuenta_id'] ?? 0) === (int)$ct['id'] && $cobro_medio_prev === 'efectivo') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Transferencia -->
        <div id="cobro-bco" class="cobro-panel" style="display:none">
            <div class="akim-form-group">
                <label for="cobro_bco_id">Cuenta bancaria</label>
                <select name="cobro_bco_id" id="cobro_bco_id" class="akim-input">
                    <option value="">— Seleccioná cuenta —</option>
                    <?php foreach ($cuentas_bco as $ct): ?>
                        <option value="<?php echo $ct['id']; ?>"
                            <?php echo ((int)($cobro_prev['cuenta_id'] ?? 0) === (int)$ct['id'] && $cobro_medio_prev === 'transferencia') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Cheque recibido -->
        <div id="cobro-chq" class="cobro-panel" style="display:none">
            <div class="wiz-grid-2">
                <div class="akim-form-group">
                    <label for="cobro_chq_numero">Número de cheque</label>
                    <input type="text" name="cobro_chq_numero" id="cobro_chq_numero" class="akim-input"
                           value="<?php echo htmlspecialchars($cobro_prev['chq_numero'] ?? '', ENT_QUOTES); ?>"
                           placeholder="00001234">
                </div>
                <div class="akim-form-group">
                    <label for="cobro_chq_banco">Banco emisor</label>
                    <input type="text" name="cobro_chq_banco" id="cobro_chq_banco" class="akim-input"
                           value="<?php echo htmlspecialchars($cobro_prev['chq_banco'] ?? '', ENT_QUOTES); ?>"
                           placeholder="Ej: Galicia, Nación...">
                </div>
                <div class="akim-form-group">
                    <label for="cobro_chq_fecha_venc">Fecha de vencimiento</label>
                    <input type="date" name="cobro_chq_fecha_venc" id="cobro_chq_fecha_venc" class="akim-input"
                           value="<?php echo htmlspecialchars($cobro_prev['chq_fecha_venc'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="akim-form-group">
                    <label for="cobro_chq_bco_id">Cuenta a acreditar</label>
                    <select name="cobro_chq_bco_id" id="cobro_chq_bco_id" class="akim-input">
                        <option value="">— Seleccioná cuenta —</option>
                        <?php foreach ($cuentas_bco as $ct): ?>
                            <option value="<?php echo $ct['id']; ?>"
                                <?php echo ((int)($cobro_prev['cuenta_id'] ?? 0) === (int)$ct['id'] && $cobro_medio_prev === 'cheque') ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ct['nombre'], ENT_QUOTES); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tarjeta -->
        <?php if (!empty($tarjetas_tes)): ?>
        <div id="cobro-tarjeta" class="cobro-panel" style="display:none">
            <div class="akim-form-group">
                <label for="cobro_tarjeta_id">Terminal / Red de tarjeta</label>
                <select name="cobro_tarjeta_id" id="cobro_tarjeta_id" class="akim-input">
                    <option value="">— Seleccioná terminal —</option>
                    <?php foreach ($tarjetas_tes as $t): ?>
                        <option value="<?php echo $t['id']; ?>"
                            data-plazo="<?php echo (int)$t['plazo_dias']; ?>"
                            data-desc="<?php echo (float)$t['descuento_pct']; ?>"
                            <?php echo ((int)($cobro_prev['tarjeta_id'] ?? 0) === (int)$t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nombre'] . ' — ' . $t['red'], ENT_QUOTES); ?>
                            (<?php echo $t['plazo_dias']; ?> días, <?php echo $t['descuento_pct']; ?>% desc.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p id="cobro-tarjeta-info" class="akim-hint" style="display:none;margin-top:.25rem;color:var(--color-info)"></p>
        </div>
        <?php endif; ?>

        </div><!-- /cobro-section -->

        <div id="s3-cobro-err" class="alert alert-error" style="display:none;margin-bottom:.75rem"></div>

        <?php if (!empty($errores)): ?>
        <div class="alert alert-error" style="margin-bottom:.75rem">
            <?php foreach ($errores as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="wiz-footer">
            <a href="vta_wizard_step2" class="btn-wiz-back">
                <i class="fa-solid fa-arrow-left"></i> Atrás
            </a>
            <button type="submit" class="btn-wiz-next">
                Siguiente <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    // Total neto en tiempo real
    const bruto  = <?php echo json_encode((float)($total_bruto ?? 0)); ?>;
    const inputDesc  = document.getElementById('descuento');
    const display= document.getElementById('total-neto');

    function updateNeto() {
        const desc = parseFloat(inputDesc.value) || 0;
        const neto = bruto * (1 - desc / 100);
        display.textContent = '$ ' + neto.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    inputDesc.addEventListener('input', updateNeto);

    // Mostrar/ocultar sección de cobro según condición de pago
    var selCond    = document.getElementById('condicion_pago');
    var cobroSec   = document.getElementById('cobro-section');
    function actualizarCobroSection() {
        if (selCond && cobroSec) {
            cobroSec.style.display = (selCond.value === 'Contado') ? 'block' : 'none';
        }
    }
    if (selCond) selCond.addEventListener('change', actualizarCobroSection);
    actualizarCobroSection();

    // Toggle paneles de cobro
    var _panelMap = { efectivo: 'cobro-ef', transferencia: 'cobro-bco', cheque: 'cobro-chq', tarjeta: 'cobro-tarjeta' };
    window.akimToggleCobro = function(val) {
        document.querySelectorAll('.cobro-panel').forEach(function(p) { p.style.display = 'none'; });
        var pid = _panelMap[val];
        if (pid) { var el = document.getElementById(pid); if (el) el.style.display = 'block'; }
    };

    // Validación client-side al submit
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!selCond || selCond.value !== 'Contado') return;
        var medio = document.getElementById('cobro_medio');
        if (!medio || medio.value === 'sin_cobro') return;
        var checkMap = {
            efectivo:      { id: 'cobro_ef_id',      msg: 'Seleccioná una caja o cuenta de efectivo.' },
            transferencia: { id: 'cobro_bco_id',     msg: 'Seleccioná una cuenta bancaria destino.' },
            cheque:        { id: 'cobro_chq_bco_id', msg: 'Seleccioná la cuenta bancaria para acreditar el cheque.' },
            tarjeta:       { id: 'cobro_tarjeta_id', msg: 'Seleccioná la terminal / red de tarjeta.' }
        };
        var cfg = checkMap[medio.value];
        if (cfg) {
            var sel = document.getElementById(cfg.id);
            if (sel && !sel.value) {
                e.preventDefault();
                var errDiv = document.getElementById('s3-cobro-err');
                if (errDiv) { errDiv.textContent = cfg.msg; errDiv.style.display = 'block'; }
            }
        }
    });

    // Info de tarjeta seleccionada
    const selTarjeta = document.getElementById('cobro_tarjeta_id');
    const infoTarjeta = document.getElementById('cobro-tarjeta-info');
    if (selTarjeta && infoTarjeta) {
        selTarjeta.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const plazo = opt.dataset.plazo;
            const desc  = opt.dataset.desc;
            if (plazo !== undefined && desc !== undefined && this.value) {
                const neto = parseFloat(inputDesc.value) || 0;
                const total = bruto * (1 - neto / 100);
                const monto_net = total * (1 - parseFloat(desc) / 100);
                infoTarjeta.textContent = `Acreditación en ${plazo} días · Monto neto: $${monto_net.toFixed(2).replace('.', ',')}`;
                infoTarjeta.style.display = '';
            } else {
                infoTarjeta.style.display = 'none';
            }
        });
    }

    // Mostrar panel inicial según valor previo
    akimToggleCobro(document.getElementById('cobro_medio').value);
})();
</script>
