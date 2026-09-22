<?php
$productos   = $productos   ?? [];
$depositos   = $depositos   ?? [];
$errores     = $errores     ?? [];
$post_prev   = $post_prev   ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;

$pp = fn(string $k, $d = '') => $post_prev[$k] ?? $d;
?>

<link rel="stylesheet" href="includes/plugins/plugins/select2-4.1.0/css/select2.min.css">
<script src="includes/plugins/plugins/select2-4.1.0/js/select2.min.js"></script>

<style>
.transf-wrap { max-width: 680px; }
.transf-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.transf-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.transf-depositos { display:grid; grid-template-columns:1fr 40px 1fr; gap:.65rem; align-items:end; }
.transf-arrow { display:flex; align-items:center; justify-content:center; padding-bottom:.5rem; color:var(--accent); font-size:1.1rem; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }
@media(max-width:560px) {
    .transf-depositos { grid-template-columns:1fr; }
    .transf-arrow { display:none; }
    .form-grid-2 { grid-template-columns:1fr; }
}

/* ── Select2 ── */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
    background:rgba(31,41,55,.5); border:1px solid rgba(255,255,255,.11);
    border-radius:10px; height:44px; display:flex; align-items:center;
    padding:0 2.75rem 0 .9rem; cursor:pointer;
    transition:border-color .18s,box-shadow .18s; position:relative;
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
    background-size:contain; background-repeat:no-repeat; background-position:center;
    transition:transform .18s,opacity .18s;
}
.select2-container--default.select2-container--open .select2-selection__arrow b {
    transform:rotate(180deg);
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}
.select2-dropdown {
    background:#0f172a; border:1px solid rgba(255,255,255,.1); border-radius:12px;
    box-shadow:0 16px 40px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.04);
    overflow:hidden; margin-top:4px; z-index:99999 !important;
}
.select2-container { z-index:99999 !important; }
.select2-dropdown.select2-dropdown--above { margin-top:0; margin-bottom:4px; }
.select2-container--default .select2-search--dropdown { padding:.6rem .6rem .3rem; background:transparent; }
.select2-container--default .select2-search--dropdown .select2-search__field {
    background:rgba(30,41,59,.8); border:1px solid rgba(255,255,255,.1); border-radius:8px;
    color:#f1f5f9; font-size:.88rem; font-family:'Segoe UI',system-ui,sans-serif;
    padding:.55rem .75rem .55rem 2rem; outline:none; width:100%;
    transition:border-color .15s,box-shadow .15s;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:.55rem center; background-size:14px;
}
.select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color:rgba(59,130,246,.5); box-shadow:0 0 0 2px rgba(59,130,246,.12);
}
.select2-container--default .select2-search--dropdown .select2-search__field::placeholder { color:#374151; }
.select2-results { padding:.3rem 0; }
.select2-results__options { max-height:260px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,.1) transparent; }
.select2-results__options::-webkit-scrollbar { width:4px; }
.select2-results__options::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }
.select2-container--default .select2-results__option {
    padding:.55rem 1rem; font-size:.87rem; font-family:'Segoe UI',system-ui,sans-serif;
    color:#94a3b8; cursor:pointer;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] { background:rgba(37,99,235,.18); color:#e2e8f0; }
.select2-container--default .select2-results__option[aria-selected=true] { background:rgba(37,99,235,.12); color:#93c5fd; font-weight:600; }
.select2-container--default .select2-results__option[aria-selected=true]::before { content:'✓ '; color:#60a5fa; font-weight:700; }
.select2-container--default .select2-results__message { color:#4b5563; font-size:.83rem; padding:.65rem 1rem; text-align:center; font-style:italic; }
</style>

<div class="transf-wrap">

    <div class="transf-header">
        <div>
            <h1>Transferencia entre Depósitos</h1>
            <p style="font-size:.82rem;color:var(--text-dim);margin-top:.2rem">
                Mover stock de un depósito a otro sin afectar el total global
            </p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="stk_movimientos" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-clock-rotate-left"></i> Historial
            </a>
            <a href="stock" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Stock
            </a>
        </div>
    </div>

    <?php if ($flash_ok): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-circle-check"></i>
        <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <?php if (!empty($errores)): ?>
            <div class="alert alert-error" style="margin-bottom:1rem">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php foreach ($errores as $e): ?><div><?php echo htmlspecialchars($e, ENT_QUOTES); ?></div><?php endforeach; ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" action="stk_transferencia">

                <!-- Producto -->
                <div style="margin-bottom:.85rem">
                    <label class="form-label" style="font-size:.8rem;font-weight:600;color:var(--text-dim);display:block;margin-bottom:.3rem">Producto *</label>
                    <select name="producto_id" id="sel-producto" required>
                        <option value="">— Seleccioná un producto —</option>
                        <?php foreach ($productos as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>"
                                data-codigo="<?php echo htmlspecialchars($p['codigo'] ?? '', ENT_QUOTES); ?>"
                                <?php echo (int)$pp('producto_id') === (int)$p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>
                            <?php echo $p['codigo'] ? ' — ' . htmlspecialchars($p['codigo'], ENT_QUOTES) : ''; ?>
                            — Stock: <?php echo number_format((float)$p['stock_actual'], 0, ',', '.'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Depósitos -->
                <div style="margin-bottom:.85rem">
                    <label style="font-size:.8rem;font-weight:600;color:var(--text-dim);display:block;margin-bottom:.5rem">
                        Origen → Destino *
                    </label>
                    <div class="transf-depositos">
                        <div>
                            <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.25rem">Desde</label>
                            <select name="deposito_origen_id" id="sel-origen" required>
                                <option value="">— Origen —</option>
                                <?php foreach ($depositos as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>"
                                        <?php echo (int)$pp('deposito_origen_id') === (int)$d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['nombre'], ENT_QUOTES); ?>
                                    <?php echo $d['es_default'] ? ' ★' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="transf-arrow">
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                        <div>
                            <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.25rem">Hasta</label>
                            <select name="deposito_destino_id" id="sel-destino" required>
                                <option value="">— Destino —</option>
                                <?php foreach ($depositos as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>"
                                        <?php echo (int)$pp('deposito_destino_id') === (int)$d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['nombre'], ENT_QUOTES); ?>
                                    <?php echo $d['es_default'] ? ' ★' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="dep-warn" style="display:none;margin-top:.4rem;font-size:.8rem;color:#fca5a5">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        El origen y destino no pueden ser el mismo depósito.
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:.85rem">
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;color:var(--text-dim);display:block;margin-bottom:.3rem">Cantidad *</label>
                        <input type="number" name="cantidad" class="form-control"
                               step="0.001" min="0.001" required
                               value="<?php echo htmlspecialchars($pp('cantidad', ''), ENT_QUOTES); ?>"
                               placeholder="0.000">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;font-weight:600;color:var(--text-dim);display:block;margin-bottom:.3rem">Motivo</label>
                        <input type="text" name="motivo" class="form-control"
                               value="<?php echo htmlspecialchars($pp('motivo', ''), ENT_QUOTES); ?>"
                               placeholder="Ej: Reposición sucursal...">
                    </div>
                </div>

                <div style="display:flex;gap:.6rem;justify-content:flex-end;padding-top:.75rem;border-top:1px solid var(--border)">
                    <a href="stock" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="btn-submit">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i> Registrar transferencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {

    var lang = {
        noResults:     function() { return 'Sin resultados'; },
        searching:     function() { return 'Buscando…'; },
        inputTooShort: function() { return 'Escribí al menos 1 carácter'; },
    };

    /* Producto — buscador completo (nombre + código/barcode) */
    $('#sel-producto').select2({
        placeholder : '— Seleccioná un producto —',
        allowClear  : true,
        language    : lang,
        width       : '100%',
        matcher: function(params, data) {
            if (!params.term || params.term.trim() === '') return data;
            var q  = params.term.trim().toLowerCase();
            var tx = (data.text || '').toLowerCase();
            var cd = ($(data.element).data('codigo') || '').toLowerCase();
            return (tx.indexOf(q) > -1 || cd.indexOf(q) > -1) ? data : null;
        }
    });

    /* Depósito origen */
    $('#sel-origen').select2({
        placeholder             : '— Origen —',
        allowClear              : false,
        minimumResultsForSearch : 4,
        language                : lang,
        width                   : '100%',
    });

    /* Depósito destino */
    $('#sel-destino').select2({
        placeholder             : '— Destino —',
        allowClear              : false,
        minimumResultsForSearch : 4,
        language                : lang,
        width                   : '100%',
    });

    /* Foco automático en búsqueda de producto al abrir */
    $('#sel-producto').on('select2:open', function() {
        setTimeout(function() {
            var field = document.querySelector('.select2-search--dropdown .select2-search__field');
            if (field) field.focus();
        }, 50);
    });

    /* Validación origen ≠ destino — se dispara con Select2 change */
    function checkDepositos() {
        var o    = $('#sel-origen').val();
        var d    = $('#sel-destino').val();
        var warn = document.getElementById('dep-warn');
        var btn  = document.getElementById('btn-submit');
        var igual = o && d && o === d;
        warn.style.display = igual ? '' : 'none';
        btn.disabled = igual;
    }

    $('#sel-origen, #sel-destino').on('change', checkDepositos);
});
</script>
