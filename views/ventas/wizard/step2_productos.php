<?php
$productos = $productos ?? [];
?>

<link rel="stylesheet" href="includes/plugins/plugins/select2-4.1.0/css/select2.min.css">
<script src="includes/plugins/plugins/select2-4.1.0/js/select2.min.js"></script>

<style>
/* ── Select2 dentro del wizard ── */
.select2-container { width: 100% !important; min-width: 0 !important; max-width: 100%; }
.select2-container--default .select2-selection--single {
    background:rgba(31,41,55,.55); border:1px solid rgba(255,255,255,.13);
    border-radius:var(--radius); height:42px; display:flex; align-items:center;
    padding:0 2.75rem 0 .95rem; cursor:pointer;
    transition:border-color .18s,box-shadow .18s; position:relative;
    min-width:0; overflow:hidden; /* no expander el grid */
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open  .select2-selection--single {
    border-color:rgba(59,130,246,.55); box-shadow:0 0 0 3px rgba(59,130,246,.12); outline:none;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color:#f9fafb; font-size:.9rem; font-family:'Segoe UI',system-ui,sans-serif;
    line-height:1; padding:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder { color:#4b5563; }
.select2-container--default .select2-selection--single .select2-selection__arrow {
    position:absolute; top:0; right:0; height:100%; width:2.5rem;
    display:flex; align-items:center; justify-content:center;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    display:block; width:16px; height:16px; border:none; margin:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-size:contain; background-repeat:no-repeat; background-position:center; transition:transform .18s;
}
.select2-container--default.select2-container--open .select2-selection__arrow b {
    transform:rotate(180deg);
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}
.select2-dropdown {
    background:#0f172a; border:1px solid rgba(255,255,255,.1); border-radius:12px;
    box-shadow:0 16px 40px rgba(0,0,0,.6); overflow:hidden; margin-top:4px; z-index:99999 !important;
}
.select2-container { z-index:99999 !important; }
.select2-container--default .select2-search--dropdown { padding:.6rem .6rem .3rem; background:transparent; }
.select2-container--default .select2-search--dropdown .select2-search__field {
    background:rgba(30,41,59,.8); border:1px solid rgba(255,255,255,.1); border-radius:8px;
    color:#f1f5f9; font-size:.88rem; font-family:'Segoe UI',system-ui,sans-serif;
    padding:.55rem .75rem .55rem 2rem; outline:none; width:100%;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:.55rem center; background-size:14px;
}
.select2-container--default .select2-search--dropdown .select2-search__field:focus { border-color:rgba(59,130,246,.5); }
.select2-container--default .select2-search--dropdown .select2-search__field::placeholder { color:#374151; }
.select2-results { padding:.3rem 0; }
.select2-results__options { max-height:260px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,.1) transparent; }
.select2-results__options::-webkit-scrollbar { width:4px; }
.select2-results__options::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }
.select2-container--default .select2-results__option {
    padding:.55rem 1rem; font-size:.87rem; font-family:'Segoe UI',system-ui,sans-serif; color:#94a3b8; cursor:pointer;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] { background:rgba(37,99,235,.18); color:#e2e8f0; }
.select2-container--default .select2-results__option[aria-selected=true] { background:rgba(37,99,235,.12); color:#93c5fd; font-weight:600; }
.select2-container--default .select2-results__message { color:#4b5563; font-size:.83rem; padding:.65rem 1rem; text-align:center; font-style:italic; }
</style>

<div class="wiz-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.25rem">
        <h2 class="wiz-title" style="margin:0">¿Qué vendemos?</h2>
        <?php if (($wizard['modo_factura'] ?? 'manual') === 'fiscal'): ?>
        <span class="wiz-modo-badge wiz-modo-fiscal"><i class="fa-solid fa-file-invoice-dollar"></i> Factura AFIP</span>
        <?php else: ?>
        <span class="wiz-modo-badge wiz-modo-manual"><i class="fa-solid fa-file-lines"></i> Ticket / Remito</span>
        <?php endif; ?>
    </div>
    <p class="wiz-subtitle">
        Cliente: <strong><?php echo htmlspecialchars($wizard['cliente_label'] ?? '', ENT_QUOTES); ?></strong>
        &nbsp;·&nbsp; Seleccioná productos y completá cantidad y precio.
    </p>

    <!-- Agregar ítem -->
    <form method="POST" action="vta_wizard_step2" id="form-agregar" autocomplete="off">
        <input type="hidden" name="accion" value="agregar_item">

        <div class="wiz-grid-items">
            <div class="akim-form-group" style="margin:0">
                <label>Producto / Servicio</label>
                <select name="producto_id" id="sel-producto">
                    <option value=""></option>
                </select>
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
            <button type="submit" class="btn-wiz-next" style="height:2.65rem;padding:.6rem 1rem" title="Agregar ítem">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <!-- nombre oculto para que el controller lo reciba -->
        <input type="hidden" name="producto_label" id="producto_label" value="">
        <input type="hidden" name="producto_nuevo" id="producto_nuevo_flag" value="0">
    </form>

    <!-- Lista de ítems agregados -->
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
$(function () {

    var lang = {
        noResults    : function() { return 'Sin resultados para esa búsqueda.'; },
        searching    : function() { return 'Buscando…'; },
        inputTooShort: function() { return 'Escribí al menos 1 carácter'; },
    };

    $('#sel-producto').select2({
        placeholder       : 'Buscar por nombre o código…',
        allowClear        : true,
        language          : lang,
        width             : '100%',
        minimumInputLength: 1,

        // ── Fuente de datos: AJAX al endpoint del router ──────────────────────
        ajax: {
            url      : 'jit_productos',
            dataType : 'json',
            delay    : 220,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(p) {
                        return {
                            id      : p.nuevo ? '__nuevo__' : p.id,
                            text    : p.nombre + (p.codigo ? ' — ' + p.codigo : ''),
                            nombre  : p.nombre,
                            codigo  : p.codigo  || '',
                            precio  : parseFloat(p.precio_venta) || 0,
                            isNew   : !!p.nuevo,
                        };
                    })
                };
            },
            cache: false
        },

        // ── Render de cada opción en el dropdown ──────────────────────────────
        templateResult: function(data) {
            if (data.loading) return data.text;
            if (data.isNew) {
                return $('<span style="color:#60a5fa;font-style:italic">'
                    + '<i class="fa-solid fa-plus" style="margin-right:.35rem"></i>'
                    + 'Agregar "<strong>'
                    + $('<span>').text(data.nombre || data.text).html()
                    + '</strong>" como nuevo ítem</span>');
            }
            return data.text;
        },
    });

    // ── Foco automático al abrir ──────────────────────────────────────────────
    $('#sel-producto').on('select2:open', function() {
        setTimeout(function() {
            var field = document.querySelector('.select2-search--dropdown .select2-search__field');
            if (field) field.focus();
        }, 50);
    });

    // ── Al seleccionar un ítem ────────────────────────────────────────────────
    $('#sel-producto').on('change', function() {
        var data     = $(this).select2('data');
        var selected = data && data[0] ? data[0] : null;
        if (!selected || !selected.id) return;

        if (selected.isNew || selected.id === '__nuevo__') {
            $('#producto_label').val(selected.nombre || selected.text);
            $('#producto_nuevo_flag').val('1');
            $('#precio-unit').val('').focus();
        } else {
            $('#precio-unit').val(selected.precio > 0 ? selected.precio.toFixed(2) : '');
            $('#producto_label').val(selected.nombre);
            $('#producto_nuevo_flag').val('0');
        }
    });

    // ── Limpiar al borrar selección ───────────────────────────────────────────
    $('#sel-producto').on('select2:clear', function() {
        $('#precio-unit').val('');
        $('#producto_label').val('');
        $('#producto_nuevo_flag').val('0');
    });

    // ── Validar antes de agregar ítem ─────────────────────────────────────────
    $('#form-agregar').on('submit', function() {
        var selData  = $('#sel-producto').select2('data');
        var selected = selData && selData[0] ? selData[0] : null;
        if (!selected || !selected.id) {
            alert('Buscá y seleccioná un producto antes de agregar.');
            return false;
        }
        var esNuevo = $('#producto_nuevo_flag').val() === '1';
        var precio  = parseFloat($('#precio-unit').val()) || 0;
        var label   = $('#producto_label').val().trim();
        if (esNuevo && (precio <= 0 || label === '')) {
            alert('Completá el precio del nuevo ítem antes de agregar.');
            $('#precio-unit').focus();
            return false;
        }
    });
});
</script>
