<div class="wiz-card">
    <h2 class="wiz-title">¿A quién le vendemos?</h2>
    <p class="wiz-subtitle">Escribí el nombre del cliente. Si no existe, lo creamos en el momento.</p>

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

        <div class="akim-form-group" style="position:relative">
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
            fetch('ajax/jit/jit_clientes.php?q=' + encodeURIComponent(q))
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
</script>
