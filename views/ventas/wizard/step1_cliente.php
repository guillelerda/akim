<?php
$modo_factura    = $modo_factura ?? 'manual';
$arca_habilitado = $arca_habilitado ?? false;
$arca_pv         = $arca_pv ?? 1;
$arca_entorno    = $arca_entorno ?? 'Producción';
?>
<div class="wiz-card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1.1rem">
        <div>
            <h2 class="wiz-title" style="margin-bottom:.1rem">¿A quién le vendemos?</h2>
            <p class="wiz-subtitle" style="margin:0">Escribí el nombre del cliente. Si no existe, lo creamos en el momento.</p>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="akim-alert akim-alert--error">
        <?php foreach ($errores as $e): ?>
            <p><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="vta_wizard_step1" id="form-step1" autocomplete="off">
        <input type="hidden" name="cliente_id"    id="cliente_id"    value="0">
        <input type="hidden" name="cliente_nuevo" id="cliente_nuevo" value="0">

        <!-- Selector de Tipo de Emisión (Fiscal vs Manual) -->
        <div class="wiz-tipo-emision-group">
            <label class="wiz-tipo-label">Tipo de Comprobante a Emitir</label>
            <div class="wiz-tipo-grid">
                <label class="wiz-tipo-card <?php echo $modo_factura === 'fiscal' ? 'selected' : ''; ?> <?php echo !$arca_habilitado ? 'disabled' : ''; ?>">
                    <input type="radio" name="modo_factura" value="fiscal" <?php echo $modo_factura === 'fiscal' ? 'checked' : ''; ?> <?php echo !$arca_habilitado ? 'disabled' : ''; ?> onchange="cambiarModo(this)">
                    <div class="wiz-tipo-icon wiz-tipo-icon--afip">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div class="wiz-tipo-content">
                        <div class="wiz-tipo-title">
                            Factura Electrónica AFIP
                            <span class="badge-afip-live">OFICIAL</span>
                        </div>
                        <div class="wiz-tipo-desc">
                            <?php if ($arca_habilitado): ?>
                                Punto de Venta #<?php echo (int)$arca_pv; ?> — <?php echo htmlspecialchars($arca_entorno, ENT_QUOTES); ?> (con CAE y QR)
                            <?php else: ?>
                                <span style="color:#ef4444">No configurado en Parámetros</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </label>

                <label class="wiz-tipo-card <?php echo $modo_factura === 'manual' ? 'selected' : ''; ?>">
                    <input type="radio" name="modo_factura" value="manual" <?php echo $modo_factura === 'manual' ? 'checked' : ''; ?> onchange="cambiarModo(this)">
                    <div class="wiz-tipo-icon wiz-tipo-icon--manual">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="wiz-tipo-content">
                        <div class="wiz-tipo-title">Ticket / Remito Interno</div>
                        <div class="wiz-tipo-desc">Comprobante no fiscal para control interno (Letra X)</div>
                    </div>
                </label>
            </div>
        </div>

        <div class="akim-form-group" style="position:relative;margin-top:1.25rem">
            <label for="cliente_buscar">Cliente</label>
            <input
                type="text"
                id="cliente_buscar"
                name="cliente_label"
                placeholder="Escribí razón social o nombre..."
                autocomplete="off"
                class="akim-input akim-input--lg"
                value="<?php echo htmlspecialchars($wizard['cliente_label'] ?? '', ENT_QUOTES); ?>"
                autofocus
            >
            <div id="jit-dropdown" class="jit-dropdown" style="display:none"></div>
        </div>

        <div id="cliente-seleccionado" style="display:none;margin-top:-.35rem;margin-bottom:.75rem">
            <span style="font-size:.78rem;color:#4ade80">
                <i class="fa-solid fa-circle-check"></i>
                <span id="cliente-label-ok"></span>
            </span>
        </div>

        <div class="wiz-footer">
            <a href="ventas" class="btn-wiz-back">
                <i class="fa-solid fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn-wiz-next">
                Siguiente <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const input    = document.getElementById('cliente_buscar');
    const dropdown = document.getElementById('jit-dropdown');
    const hidId    = document.getElementById('cliente_id');
    const hidNuevo = document.getElementById('cliente_nuevo');
    const selBox   = document.getElementById('cliente-seleccionado');
    const selLabel = document.getElementById('cliente-label-ok');
    let   timer    = null;

    input.addEventListener('input', function() {
        // Reset selección al escribir
        hidId.value    = '0';
        hidNuevo.value = '0';
        selBox.style.display = 'none';

        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }

        timer = setTimeout(() => {
            fetch('jit_clientes?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => renderDropdown(data));
        }, 220);
    });

    function renderDropdown(items) {
        dropdown.innerHTML = '';
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'jit-option' + (item.nuevo ? ' jit-option--nuevo' : '');
            div.textContent = item.nuevo
                ? '✚ Crear cliente: "' + item.razon_social + '"'
                : item.razon_social;
            div.addEventListener('mousedown', (e) => {
                e.preventDefault();
                input.value    = item.razon_social;
                hidId.value    = item.id;
                hidNuevo.value = item.nuevo ? '1' : '0';
                dropdown.style.display = 'none';
                selLabel.textContent = item.nuevo
                    ? 'Se creará el cliente al confirmar'
                    : 'Cliente seleccionado';
                selBox.style.display = 'block';
            });
            dropdown.appendChild(div);
        });
        dropdown.style.display = items.length ? 'block' : 'none';
    }

    input.addEventListener('blur',  () => setTimeout(() => dropdown.style.display = 'none', 150));
    input.addEventListener('focus', () => { if (input.value.length >= 2) input.dispatchEvent(new Event('input')); });
})();

function cambiarModo(radio) {
    document.querySelectorAll('.wiz-tipo-card').forEach(c => c.classList.remove('selected'));
    if (radio.checked) {
        radio.closest('.wiz-tipo-card').classList.add('selected');
    }
}
</script>

<style>
.wiz-tipo-emision-group { margin-bottom: 1rem; }
.wiz-tipo-label { display: block; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-dim, #94a3b8); margin-bottom: .5rem; }
.wiz-tipo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: .75rem; }
.wiz-tipo-card {
    display: flex; align-items: center; gap: .85rem; padding: .85rem 1rem;
    border-radius: var(--radius, 8px); border: 1.5px solid var(--border, #334155);
    background: var(--surface, #1e293b); cursor: pointer; transition: all .2s ease;
}
.wiz-tipo-card input[type="radio"] { display: none; }
.wiz-tipo-card:hover { border-color: #3b82f6; transform: translateY(-1px); }
.wiz-tipo-card.selected {
    border-color: #10b981;
    background: rgba(16, 185, 129, .08);
    box-shadow: 0 0 0 1px #10b981;
}
.wiz-tipo-card.disabled { opacity: .5; cursor: not-allowed; pointer-events: none; }
.wiz-tipo-icon {
    width: 42px; height: 42px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; flex-shrink: 0;
}
.wiz-tipo-icon--afip { background: rgba(16, 185, 129, .15); color: #10b981; }
.wiz-tipo-icon--manual { background: rgba(148, 163, 184, .15); color: #94a3b8; }
.wiz-tipo-content { flex: 1; }
.wiz-tipo-title { font-size: .88rem; font-weight: 700; color: var(--text, #f8fafc); display: flex; align-items: center; gap: .4rem; }
.wiz-tipo-desc { font-size: .75rem; color: var(--text-dim, #94a3b8); margin-top: .15rem; }
.badge-afip-live { font-size: .62rem; padding: 2px 6px; border-radius: 4px; background: #10b981; color: #fff; font-weight: 800; letter-spacing: .05em; }
</style>
