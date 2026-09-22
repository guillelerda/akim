<?php
$movimientos = $movimientos ?? [];
$productos   = $productos   ?? [];
$depositos   = $depositos   ?? [];
$filtros     = $filtros     ?? [];

?><link rel="stylesheet" href="includes/plugins/plugins/select2-4.1.0/css/select2.min.css">
<script src="includes/plugins/plugins/select2-4.1.0/js/select2.min.js"></script><?php

$f = fn(string $k, $d = '') => $filtros[$k] ?? $d;

$tipo_labels = [
    'compra'       => ['label' => 'Compra',       'icon' => 'cart-shopping',        'color' => '#60a5fa'],
    'ajuste_pos'   => ['label' => 'Ajuste +',      'icon' => 'plus',                 'color' => '#86efac'],
    'ajuste_neg'   => ['label' => 'Ajuste −',      'icon' => 'minus',                'color' => '#fca5a5'],
    'transferencia'=> ['label' => 'Transferencia', 'icon' => 'arrow-right-arrow-left','color' => '#c4b5fd'],
    'venta'        => ['label' => 'Venta',         'icon' => 'tag',                  'color' => '#fbbf24'],
];
?>

<style>
.mov-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap; }
.mov-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.mov-filters { display:flex; gap:.6rem; align-items:flex-end; flex-wrap:wrap; margin-bottom:1rem; }
.mov-filters > div { min-width:155px; }
.mov-filters input { min-width:140px; }
.mov-tipo-badge { display:inline-flex; align-items:center; gap:.3rem; font-size:.76rem; font-weight:600;
    padding:.2rem .5rem; border-radius:5px; }
.mov-deposito-arrow { color:var(--text-dim); font-size:.8rem; }

/* ── Select2 (tamaño compacto para filtros) ── */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
    background:rgba(31,41,55,.5); border:1px solid rgba(255,255,255,.11);
    border-radius:8px; height:36px; display:flex; align-items:center;
    padding:0 2.5rem 0 .75rem; cursor:pointer;
    transition:border-color .18s,box-shadow .18s; position:relative;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open  .select2-selection--single {
    border-color:rgba(59,130,246,.55); box-shadow:0 0 0 3px rgba(59,130,246,.12); outline:none;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color:#f9fafb; font-size:.83rem; font-family:'Segoe UI',system-ui,sans-serif;
    line-height:1; padding:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder { color:#4b5563; }
.select2-container--default .select2-selection--single .select2-selection__arrow {
    position:absolute; top:0; right:0; height:100%; width:2.25rem;
    display:flex; align-items:center; justify-content:center;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    display:block; width:14px; height:14px; border:none; margin:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-size:contain; background-repeat:no-repeat; background-position:center;
    transition:transform .18s;
}
.select2-container--default.select2-container--open .select2-selection__arrow b {
    transform:rotate(180deg);
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
}
.select2-dropdown {
    background:#0f172a; border:1px solid rgba(255,255,255,.1); border-radius:10px;
    box-shadow:0 16px 40px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.04);
    overflow:hidden; margin-top:3px; z-index:99999 !important;
}
.select2-container { z-index:99999 !important; }
.select2-container--default .select2-search--dropdown { padding:.5rem .5rem .25rem; background:transparent; }
.select2-container--default .select2-search--dropdown .select2-search__field {
    background:rgba(30,41,59,.8); border:1px solid rgba(255,255,255,.1); border-radius:7px;
    color:#f1f5f9; font-size:.83rem; font-family:'Segoe UI',system-ui,sans-serif;
    padding:.45rem .7rem .45rem 1.85rem; outline:none; width:100%;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:.5rem center; background-size:13px;
}
.select2-container--default .select2-search--dropdown .select2-search__field:focus { border-color:rgba(59,130,246,.5); }
.select2-container--default .select2-search--dropdown .select2-search__field::placeholder { color:#374151; }
.select2-results { padding:.25rem 0; }
.select2-results__options { max-height:240px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,.1) transparent; }
.select2-results__options::-webkit-scrollbar { width:4px; }
.select2-results__options::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }
.select2-container--default .select2-results__option {
    padding:.45rem .85rem; font-size:.83rem; font-family:'Segoe UI',system-ui,sans-serif; color:#94a3b8; cursor:pointer;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] { background:rgba(37,99,235,.18); color:#e2e8f0; }
.select2-container--default .select2-results__option[aria-selected=true] { background:rgba(37,99,235,.12); color:#93c5fd; font-weight:600; }
.select2-container--default .select2-results__option[aria-selected=true]::before { content:'✓ '; color:#60a5fa; font-weight:700; }
.select2-container--default .select2-results__message { color:#4b5563; font-size:.8rem; padding:.55rem .85rem; text-align:center; font-style:italic; }
</style>

<div class="mov-header">
    <h1>Historial de Movimientos</h1>
    <a href="stock" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Stock
    </a>
</div>

<!-- Filtros -->
<form method="GET" action="stk_movimientos" class="mov-filters">
    <div>
        <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.2rem">Tipo</label>
        <select name="tipo" id="fil-tipo">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipo_labels as $val => $info): ?>
            <option value="<?php echo $val; ?>" <?php echo $f('tipo') === $val ? 'selected' : ''; ?>>
                <?php echo $info['label']; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.2rem">Producto</label>
        <select name="producto_id" id="fil-producto">
            <option value="">Todos los productos</option>
            <?php foreach ($productos as $p): ?>
            <option value="<?php echo (int)$p['id']; ?>"
                    data-codigo="<?php echo htmlspecialchars($p['codigo'] ?? '', ENT_QUOTES); ?>"
                    <?php echo (int)$f('producto_id') === (int)$p['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>
                <?php echo $p['codigo'] ? ' — ' . htmlspecialchars($p['codigo'], ENT_QUOTES) : ''; ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.2rem">Depósito</label>
        <select name="deposito_id" id="fil-deposito">
            <option value="">Todos los depósitos</option>
            <?php foreach ($depositos as $d): ?>
            <option value="<?php echo (int)$d['id']; ?>"
                    <?php echo (int)$f('deposito_id') === (int)$d['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($d['nombre'], ENT_QUOTES); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.2rem">Desde</label>
        <input type="date" name="fecha_desde" class="form-control form-control-sm"
               value="<?php echo htmlspecialchars($f('fecha_desde'), ENT_QUOTES); ?>">
    </div>
    <div>
        <label style="font-size:.75rem;color:var(--text-dim);display:block;margin-bottom:.2rem">Hasta</label>
        <input type="date" name="fecha_hasta" class="form-control form-control-sm"
               value="<?php echo htmlspecialchars($f('fecha_hasta'), ENT_QUOTES); ?>">
    </div>
    <div>
        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-magnifying-glass"></i> Filtrar
        </button>
        <a href="stk_movimientos" class="btn btn-secondary btn-sm" style="margin-left:.35rem">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </div>
</form>

<!-- Tabla -->
<div class="prv-table-wrap">
    <?php if (empty($movimientos)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <p>No hay movimientos registrados<?php echo !empty(array_filter($filtros)) ? ' con los filtros seleccionados' : ''; ?>.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:145px">Fecha</th>
                    <th style="width:115px">Tipo</th>
                    <th>Producto</th>
                    <th>Depósito</th>
                    <th style="text-align:right;width:90px">Cantidad</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movimientos as $mov):
                $tipo_info = $tipo_labels[$mov['tipo']] ?? ['label' => $mov['tipo'], 'icon' => 'circle', 'color' => '#9ca3af'];
            ?>
                <tr>
                    <td style="font-size:.8rem;color:var(--text-dim);white-space:nowrap">
                        <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                    </td>
                    <td>
                        <span class="mov-tipo-badge"
                              style="background:<?php echo $tipo_info['color']; ?>22;color:<?php echo $tipo_info['color']; ?>">
                            <i class="fa-solid fa-<?php echo $tipo_info['icon']; ?>" style="font-size:.7rem"></i>
                            <?php echo $tipo_info['label']; ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:600;color:var(--text);font-size:.88rem">
                            <?php echo htmlspecialchars($mov['producto_nombre'], ENT_QUOTES); ?>
                        </span>
                        <?php if ($mov['producto_codigo']): ?>
                        <span style="font-size:.75rem;color:var(--text-dim);margin-left:.3rem">
                            <?php echo htmlspecialchars($mov['producto_codigo'], ENT_QUOTES); ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.83rem">
                        <span style="color:var(--text)">
                            <?php echo htmlspecialchars($mov['deposito_origen'], ENT_QUOTES); ?>
                        </span>
                        <?php if ($mov['deposito_destino']): ?>
                        <span class="mov-deposito-arrow"> → </span>
                        <span style="color:var(--text)">
                            <?php echo htmlspecialchars($mov['deposito_destino'], ENT_QUOTES); ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;font-weight:600;font-variant-numeric:tabular-nums">
                        <?php echo in_array($mov['tipo'], ['ajuste_neg','venta']) ? '−' : '+'; ?>
                        <?php echo number_format((float)$mov['cantidad'], 3, ',', '.'); ?>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-dim)">
                        <?php echo htmlspecialchars($mov['motivo'] ?: '—', ENT_QUOTES); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:.6rem 1rem;font-size:.78rem;color:var(--text-dim)">
        <?php echo count($movimientos); ?> registro<?php echo count($movimientos) !== 1 ? 's' : ''; ?>
        <?php echo count($movimientos) >= 1000 ? ' (mostrando últimos 1000)' : ''; ?>
    </div>
    <?php endif; ?>
</div>

<script>
$(function () {

    var lang = {
        noResults:     function() { return 'Sin resultados'; },
        searching:     function() { return 'Buscando…'; },
        inputTooShort: function() { return 'Escribí al menos 1 carácter'; },
    };

    /* Tipo — sin buscador (pocas opciones fijas) */
    $('#fil-tipo').select2({
        placeholder             : 'Todos los tipos',
        allowClear              : true,
        minimumResultsForSearch : Infinity,
        language                : lang,
        width                   : '100%',
    });

    /* Producto — buscador por nombre y código/barcode */
    $('#fil-producto').select2({
        placeholder : 'Todos los productos',
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

    /* Depósito — buscador si hay 4+ */
    $('#fil-deposito').select2({
        placeholder             : 'Todos los depósitos',
        allowClear              : true,
        minimumResultsForSearch : 4,
        language                : lang,
        width                   : '100%',
    });

    /* Foco automático en campo de búsqueda al abrir producto */
    $('#fil-producto').on('select2:open', function() {
        setTimeout(function() {
            var field = document.querySelector('.select2-search--dropdown .select2-search__field');
            if (field) field.focus();
        }, 50);
    });
});
</script>
