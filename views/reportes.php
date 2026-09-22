<?php
$tab        = $tab        ?? 'resumen';
$desde      = $desde      ?? date('Y-m-01');
$hasta      = $hasta      ?? date('Y-m-t');
$resumen    = $resumen    ?? [];
$rpt_ventas = $rpt_ventas ?? [];
$rpt_compras= $rpt_compras?? [];
$rpt_stock  = $rpt_stock  ?? [];
$rpt_tes    = $rpt_tes    ?? [];

$fmt  = fn(float $v, int $d=2): string => '$ '.number_format($v,$d,',','.');
$fmtN = fn(float $v): string => number_format($v,0,',','.');
$pct  = fn(float $a, float $b): string => $b>0 ? number_format($a/$b*100,1,',','.').'%' : '—';

function mesLabel(string $ym): string {
    $m=['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
    [$y,$mo]=explode('-',$ym); return ($m[$mo]??$mo).' '.substr($y,2);
}
function sparkline(array $rows, string $key, string $color): string {
    if (empty($rows)) return '';
    $vals=array_map(fn($r)=>(float)$r[$key],$rows);
    $max=max($vals)?:1; $n=count($vals); $pts=[];
    foreach($vals as $i=>$v){
        $x=$n>1?round($i/($n-1)*100,2):50;
        $y=round(48-($v/$max)*48*.85-48*.07,2);
        $pts[]="$x,$y";
    }
    $poly=implode(' ',$pts); $fill="0,48 $poly 100,48";
    return "<svg viewBox='0 0 100 48' preserveAspectRatio='none' style='width:100%;height:50px;display:block;margin-bottom:.5rem'>
        <defs><linearGradient id='g$color' x1='0' y1='0' x2='0' y2='1'>
            <stop offset='0%' stop-color='$color' stop-opacity='.25'/>
            <stop offset='100%' stop-color='$color' stop-opacity='0'/>
        </linearGradient></defs>
        <polygon points='".htmlspecialchars($fill)."' fill='url(#g$color)'/>
        <polyline points='".htmlspecialchars($poly)."' fill='none' stroke='$color' stroke-width='2'/>
    </svg>";
}
function barRows(array $rows, string $kLabel, string $kVal, string $color, callable $fmtVal): string {
    if(empty($rows)) return '<p class="rpt-empty">Sin datos en el período</p>';
    $max=max(array_map(fn($r)=>(float)$r[$kVal],$rows))?:1; $out='';
    foreach($rows as $r){
        $pct=round((float)$r[$kVal]/$max*100);
        $out.="<div class='rpt-bar-row'>
            <div class='rpt-bar-label' title='".htmlspecialchars($r[$kLabel],ENT_QUOTES)."'>".htmlspecialchars($r[$kLabel],ENT_QUOTES)."</div>
            <div class='rpt-bar-track'><div class='rpt-bar-fill' style='width:{$pct}%;background:{$color}'></div></div>
            <div class='rpt-bar-val'>".$fmtVal($r)."</div>
        </div>";
    }
    return $out;
}
?>

<style>
/* ── Layout ──────────────────────────────────────────────────────────── */
.rpt-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap}
.rpt-header h1{font-size:1.3rem;font-weight:700;color:var(--text)}

/* ── Tabs ────────────────────────────────────────────────────────────── */
.rpt-tabs{display:flex;gap:.2rem;border-bottom:1px solid var(--border);margin-bottom:1.25rem;flex-wrap:wrap}
.rpt-tab{padding:.5rem 1rem;font-size:.83rem;font-weight:600;color:var(--text-dim);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;border-radius:4px 4px 0 0;text-decoration:none;transition:color .15s,border-color .15s}
.rpt-tab:hover{color:var(--text)}
.rpt-tab.active{color:var(--accent);border-bottom-color:var(--accent)}

/* ── Filtro de fechas ───────────────────────────────────────────────── */
.rpt-filter{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem;padding:.65rem .85rem;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius)}
.rpt-filter label{font-size:.78rem;color:var(--text-dim);font-weight:600}
.rpt-filter input{padding:.3rem .6rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:.83rem}
.rpt-export{display:flex;gap:.4rem;margin-left:auto}
.btn-pdf  {background:rgba(239,68,68,.12);color:#fca5a5;border:1px solid rgba(239,68,68,.25)}
.btn-pdf:hover{background:rgba(239,68,68,.22)}
.btn-xlsx {background:rgba(22,163,74,.12);color:#86efac;border:1px solid rgba(22,163,74,.25)}
.btn-xlsx:hover{background:rgba(22,163,74,.22)}

/* ── KPIs ────────────────────────────────────────────────────────────── */
.rpt-kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.75rem;margin-bottom:1.25rem}
.rpt-kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.9rem 1rem}
.rpt-kpi-label{font-size:.65rem;text-transform:uppercase;letter-spacing:.1em;font-weight:600;color:var(--text-dim);margin-bottom:.3rem}
.rpt-kpi-val{font-size:1.15rem;font-weight:800;color:var(--text)}
.rpt-kpi-sub{font-size:.72rem;color:var(--text-dim);margin-top:.15rem}
.rpt-kpi.alert .rpt-kpi-val{color:#f87171}
.rpt-kpi.ok    .rpt-kpi-val{color:#4ade80}

/* ── Grid de paneles ─────────────────────────────────────────────────── */
.rpt-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
.rpt-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem}
.rpt-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem}
.rpt-panel-title{font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim);margin-bottom:.9rem;display:flex;align-items:center;gap:.4rem}
.rpt-full{grid-column:1/-1}

/* ── Barras ──────────────────────────────────────────────────────────── */
.rpt-bar-row{display:flex;align-items:center;gap:.5rem;margin-bottom:.45rem}
.rpt-bar-label{font-size:.76rem;color:var(--text);width:110px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rpt-bar-track{flex:1;height:8px;background:rgba(255,255,255,.06);border-radius:99px;overflow:hidden}
.rpt-bar-fill{height:100%;border-radius:99px}
.rpt-bar-val{font-size:.73rem;font-weight:600;color:var(--text);min-width:90px;text-align:right}

/* ── Mini tabla ──────────────────────────────────────────────────────── */
.rpt-tbl{width:100%;border-collapse:collapse;font-size:.81rem}
.rpt-tbl th{font-size:.67rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim);font-weight:600;padding:.3rem .4rem;border-bottom:1px solid var(--border);white-space:nowrap}
.rpt-tbl td{padding:.38rem .4rem;border-bottom:1px solid rgba(255,255,255,.04);color:var(--text);vertical-align:middle}
.rpt-tbl tr:last-child td{border-bottom:none}
.rpt-tbl tr:hover td{background:var(--surface-2)}
.rpt-rank{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:4px;font-size:.65rem;font-weight:700;background:rgba(37,99,235,.2);color:#93c5fd}
.rpt-rank-1{background:rgba(251,191,36,.2);color:#fcd34d}
.rpt-rank-2{background:rgba(156,163,175,.15);color:#d1d5db}
.rpt-rank-3{background:rgba(180,83,9,.2);color:#fbbf24}

/* ── Badges ──────────────────────────────────────────────────────────── */
.b-cobrada{background:rgba(22,163,74,.15);color:#4ade80;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:600}
.b-emitida{background:rgba(251,191,36,.15);color:#fcd34d;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:600}
.b-pendiente{background:rgba(239,68,68,.15);color:#fca5a5;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:600}
.b-pagada{background:rgba(22,163,74,.15);color:#4ade80;padding:.1rem .45rem;border-radius:4px;font-size:.72rem;font-weight:600}
.b-ingreso{background:rgba(22,163,74,.12);color:#4ade80;padding:.1rem .4rem;border-radius:4px;font-size:.72rem}
.b-egreso{background:rgba(239,68,68,.12);color:#fca5a5;padding:.1rem .4rem;border-radius:4px;font-size:.72rem}

.rpt-empty{font-size:.82rem;color:var(--text-dim);padding:.5rem 0}

@media(max-width:900px){.rpt-grid,.rpt-grid-3{grid-template-columns:1fr}}
@media(max-width:560px){.rpt-kpis{grid-template-columns:1fr 1fr}}
</style>

<!-- Header -->
<div class="rpt-header">
    <h1><i class="fa-solid fa-chart-line" style="color:var(--accent);margin-right:.45rem"></i>Reportes</h1>
</div>

<!-- Tabs -->
<div class="rpt-tabs">
    <?php foreach(['resumen'=>'<i class="fa-solid fa-gauge"></i> Resumen','ventas'=>'<i class="fa-solid fa-receipt"></i> Ventas','compras'=>'<i class="fa-solid fa-cart-shopping"></i> Compras','stock'=>'<i class="fa-solid fa-boxes-stacked"></i> Stock','tesoreria'=>'<i class="fa-solid fa-coins"></i> Tesorería'] as $k=>$label): ?>
    <a href="reportes?tab=<?php echo $k; ?>&desde=<?php echo urlencode($desde); ?>&hasta=<?php echo urlencode($hasta); ?>"
       class="rpt-tab<?php echo $tab===$k?' active':''; ?>">
        <?php echo $label; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php
// Helper URL exportación
$expUrl = fn(string $fmt): string =>
    'reportes_export?tab=' . urlencode($tab) .
    '&desde=' . urlencode($desde) .
    '&hasta=' . urlencode($hasta) .
    '&format=' . $fmt;
?>

<!-- Filtro fechas (tabs con rango) + botones exportar -->
<?php if ($tab !== 'resumen' && $tab !== 'stock'): ?>
<form method="GET" action="reportes" class="rpt-filter">
    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
    <label>Desde</label>
    <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>">
    <label>Hasta</label>
    <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>">
    <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
    <a href="reportes?tab=<?php echo $tab; ?>&desde=<?php echo date('Y-m-01'); ?>&hasta=<?php echo date('Y-m-t'); ?>" class="btn btn-secondary btn-sm">Mes actual</a>
    <a href="reportes?tab=<?php echo $tab; ?>&desde=<?php echo date('Y-01-01'); ?>&hasta=<?php echo date('Y-12-31'); ?>" class="btn btn-secondary btn-sm">Año <?php echo date('Y'); ?></a>
    <div class="rpt-export">
        <a href="<?php echo $expUrl('pdf'); ?>" class="btn btn-sm btn-pdf" title="Descargar PDF">
            <i class="fa-solid fa-file-pdf"></i> PDF
        </a>
        <a href="<?php echo $expUrl('xlsx'); ?>" class="btn btn-sm btn-xlsx" title="Descargar Excel">
            <i class="fa-solid fa-file-excel"></i> Excel
        </a>
    </div>
</form>
<?php else: ?>
<div style="display:flex;justify-content:flex-end;gap:.4rem;margin-bottom:1.25rem">
    <a href="<?php echo $expUrl('pdf'); ?>" class="btn btn-sm btn-pdf" title="Descargar PDF">
        <i class="fa-solid fa-file-pdf"></i> PDF
    </a>
    <a href="<?php echo $expUrl('xlsx'); ?>" class="btn btn-sm btn-xlsx" title="Descargar Excel">
        <i class="fa-solid fa-file-excel"></i> Excel
    </a>
</div>
<?php endif; ?>


<?php /* ═══════════════ TAB: RESUMEN ═══════════════ */ if ($tab === 'resumen'): ?>

<div class="rpt-kpis">
    <div class="rpt-kpi">
        <div class="rpt-kpi-label"><i class="fa-solid fa-sun"></i> Ventas hoy</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($resumen['hoy']['total']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($resumen['hoy']['cant']??0); ?> operaciones</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label"><i class="fa-solid fa-calendar"></i> Ventas <?php echo date('M'); ?></div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($resumen['mes_actual']['total']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($resumen['mes_actual']['cant']??0); ?> operaciones</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label"><i class="fa-solid fa-calendar-days"></i> Ventas <?php echo date('Y'); ?></div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($resumen['anio_actual']['total']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($resumen['anio_actual']['cant']??0); ?> operaciones</div>
    </div>
    <div class="rpt-kpi<?php echo ($resumen['facturas_pendientes']??0)>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-clock"></i> Facturas a pagar</div>
        <div class="rpt-kpi-val"><?php echo (int)($resumen['facturas_pendientes']??0); ?></div>
        <div class="rpt-kpi-sub">Compras pendientes</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label"><i class="fa-solid fa-users"></i> Clientes activos</div>
        <div class="rpt-kpi-val"><?php echo (int)($resumen['clientes_total']??0); ?></div>
        <div class="rpt-kpi-sub">En cartera</div>
    </div>
    <div class="rpt-kpi<?php echo ($resumen['stock_bajo']??0)>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-triangle-exclamation"></i> Stock bajo</div>
        <div class="rpt-kpi-val"><?php echo (int)($resumen['stock_bajo']??0); ?></div>
        <div class="rpt-kpi-sub">Bajo mínimo</div>
    </div>
</div>

<div class="rpt-grid">
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-receipt"></i> Ventas — últimos 6 meses</div>
        <?php $vm=$resumen['ventas_mes']??[]; echo sparkline($vm,'total','#3b82f6');
        $maxV=max(array_map(fn($r)=>(float)$r['total'],$vm)?:[1])?:1;
        foreach($vm as $r): $pctB=round((float)$r['total']/$maxV*100); ?>
        <div class="rpt-bar-row">
            <div class="rpt-bar-label"><?php echo mesLabel($r['mes']); ?></div>
            <div class="rpt-bar-track"><div class="rpt-bar-fill" style="width:<?php echo $pctB; ?>%;background:#3b82f6"></div></div>
            <div class="rpt-bar-val">$ <?php echo number_format((float)$r['total'],0,',','.'); ?></div>
        </div>
        <?php endforeach; if(empty($vm)) echo '<p class="rpt-empty">Sin datos</p>'; ?>
    </div>
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-cart-shopping"></i> Compras — últimos 6 meses</div>
        <?php $cm=$resumen['compras_mes']??[]; echo sparkline($cm,'total','#10b981');
        $maxC=max(array_map(fn($r)=>(float)$r['total'],$cm)?:[1])?:1;
        foreach($cm as $r): $pctB=round((float)$r['total']/$maxC*100); ?>
        <div class="rpt-bar-row">
            <div class="rpt-bar-label"><?php echo mesLabel($r['mes']); ?></div>
            <div class="rpt-bar-track"><div class="rpt-bar-fill" style="width:<?php echo $pctB; ?>%;background:#10b981"></div></div>
            <div class="rpt-bar-val">$ <?php echo number_format((float)$r['total'],0,',','.'); ?></div>
        </div>
        <?php endforeach; if(empty($cm)) echo '<p class="rpt-empty">Sin datos</p>'; ?>
    </div>
</div>

<div class="rpt-grid">
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-star"></i> Top productos más vendidos</div>
        <?php if(empty($resumen['top_productos'])): ?><p class="rpt-empty">Sin datos</p><?php else: ?>
        <table class="rpt-tbl"><thead><tr><th>#</th><th>Producto</th><th style="text-align:right">Uds.</th><th style="text-align:right">Total</th></tr></thead><tbody>
        <?php foreach($resumen['top_productos'] as $i=>$r): ?>
        <tr>
            <td><span class="rpt-rank <?php echo $i===0?'rpt-rank-1':($i===1?'rpt-rank-2':($i===2?'rpt-rank-3':'')); ?>"><?php echo $i+1; ?></span></td>
            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?></td>
            <td style="text-align:right;color:var(--text-dim)"><?php echo $fmtN((float)$r['unidades']); ?></td>
            <td style="text-align:right;font-weight:600">$ <?php echo number_format((float)$r['total'],0,',','.'); ?></td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-trophy"></i> Top clientes</div>
        <?php if(empty($resumen['top_clientes'])): ?><p class="rpt-empty">Sin datos</p><?php else: ?>
        <table class="rpt-tbl"><thead><tr><th>#</th><th>Cliente</th><th style="text-align:right">Ops.</th><th style="text-align:right">Total</th></tr></thead><tbody>
        <?php foreach($resumen['top_clientes'] as $i=>$r): ?>
        <tr>
            <td><span class="rpt-rank <?php echo $i===0?'rpt-rank-1':($i===1?'rpt-rank-2':($i===2?'rpt-rank-3':'')); ?>"><?php echo $i+1; ?></span></td>
            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['cliente'],ENT_QUOTES); ?></td>
            <td style="text-align:right;color:var(--text-dim)"><?php echo (int)$r['operaciones']; ?></td>
            <td style="text-align:right;font-weight:600">$ <?php echo number_format((float)$r['total'],0,',','.'); ?></td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>
</div>


<?php /* ═══════════════ TAB: VENTAS ═══════════════ */ elseif ($tab === 'ventas'): ?>

<div class="rpt-kpis">
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Total facturado</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_ventas['total']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($rpt_ventas['cant']??0); ?> ventas</div>
    </div>
    <div class="rpt-kpi ok">
        <div class="rpt-kpi-label"><i class="fa-solid fa-check-circle"></i> Cobrado</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_ventas['total_cobrado']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($rpt_ventas['cobradas']??0); ?> ventas cobradas</div>
    </div>
    <div class="rpt-kpi<?php echo ($rpt_ventas['emitidas']??0)>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-clock"></i> Pendiente de cobro</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_ventas['total_pendiente']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($rpt_ventas['emitidas']??0); ?> ventas emitidas</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Ticket promedio</div>
        <?php $cant=(int)($rpt_ventas['cant']??0); $tot=(float)($rpt_ventas['total']??0); ?>
        <div class="rpt-kpi-val"><?php echo $cant>0?$fmt($tot/$cant,0):'—'; ?></div>
        <div class="rpt-kpi-sub">Por operación</div>
    </div>
</div>

<div class="rpt-grid">
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-credit-card"></i> Por condición de pago</div>
        <?php echo barRows($rpt_ventas['por_condicion']??[],'condicion_pago','t','#3b82f6',fn($r)=>'$ '.number_format((float)$r['t'],0,',','.')); ?>
    </div>
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-star"></i> Top productos del período</div>
        <?php if(empty($rpt_ventas['top_productos'])): ?><p class="rpt-empty">Sin datos</p><?php else: ?>
        <table class="rpt-tbl"><thead><tr><th>#</th><th>Producto</th><th style="text-align:right">Uds.</th><th style="text-align:right">Total</th></tr></thead><tbody>
        <?php foreach($rpt_ventas['top_productos'] as $i=>$r): ?>
        <tr>
            <td><span class="rpt-rank <?php echo $i===0?'rpt-rank-1':($i===1?'rpt-rank-2':($i===2?'rpt-rank-3':'')); ?>"><?php echo $i+1; ?></span></td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?></td>
            <td style="text-align:right;color:var(--text-dim)"><?php echo $fmtN((float)$r['unidades']); ?></td>
            <td style="text-align:right;font-weight:600">$ <?php echo number_format((float)$r['total'],0,',','.'); ?></td>
        </tr>
        <?php endforeach; ?></tbody></table><?php endif; ?>
    </div>
</div>

<div class="rpt-panel rpt-full" style="margin-bottom:1rem">
    <div class="rpt-panel-title"><i class="fa-solid fa-table"></i> Detalle de ventas (últimas 50)</div>
    <?php if(empty($rpt_ventas['detalle'])): ?><p class="rpt-empty">Sin ventas en el período</p><?php else: ?>
    <div style="overflow-x:auto">
    <table class="rpt-tbl"><thead><tr>
        <th>Fecha</th><th>N°</th><th>Cliente</th><th>Condición</th><th style="text-align:center">Estado</th><th style="text-align:right">Total</th>
    </tr></thead><tbody>
    <?php foreach($rpt_ventas['detalle'] as $r): ?>
    <tr>
        <td style="color:var(--text-dim)"><?php echo date('d/m/Y',strtotime($r['fecha'])); ?></td>
        <td style="font-family:monospace;font-size:.78rem"><?php echo htmlspecialchars($r['numero']??$r['idx'],ENT_QUOTES); ?></td>
        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['cliente'],ENT_QUOTES); ?></td>
        <td style="color:var(--text-dim)"><?php echo htmlspecialchars($r['condicion_pago']??'',ENT_QUOTES); ?></td>
        <td style="text-align:center"><span class="b-<?php echo $r['estado']; ?>"><?php echo ucfirst($r['estado']); ?></span></td>
        <td style="text-align:right;font-weight:600">$ <?php echo number_format((float)$r['total'],2,',','.'); ?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
    </div><?php endif; ?>
</div>


<?php /* ═══════════════ TAB: COMPRAS ═══════════════ */ elseif ($tab === 'compras'): ?>

<div class="rpt-kpis">
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Total compras</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_compras['total']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($rpt_compras['cant']??0); ?> facturas</div>
    </div>
    <div class="rpt-kpi<?php echo ($rpt_compras['pendientes']??0)>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-clock"></i> Pendiente de pago</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_compras['total_pendiente']??0)); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($rpt_compras['pendientes']??0); ?> facturas</div>
    </div>
    <?php $pagCant=(int)($rpt_compras['cant']??0)-(int)($rpt_compras['pendientes']??0);
          $pagTot=(float)($rpt_compras['total']??0)-(float)($rpt_compras['total_pendiente']??0); ?>
    <div class="rpt-kpi ok">
        <div class="rpt-kpi-label"><i class="fa-solid fa-check"></i> Pagadas</div>
        <div class="rpt-kpi-val"><?php echo $fmt($pagTot); ?></div>
        <div class="rpt-kpi-sub"><?php echo $pagCant; ?> facturas</div>
    </div>
</div>

<div class="rpt-grid">
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-building"></i> Por proveedor</div>
        <?php echo barRows($rpt_compras['por_proveedor']??[],'proveedor','t','#10b981',fn($r)=>'$ '.number_format((float)$r['t'],0,',','.')); ?>
    </div>
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-calendar-day"></i> Por día</div>
        <?php $pd=$rpt_compras['por_dia']??[];
        if(empty($pd)): ?><p class="rpt-empty">Sin datos</p><?php else:
        $maxD=max(array_map(fn($r)=>(float)$r['t'],$pd))?:1;
        foreach($pd as $r): $pb=round((float)$r['t']/$maxD*100); ?>
        <div class="rpt-bar-row">
            <div class="rpt-bar-label"><?php echo date('d/m',strtotime($r['dia'])); ?></div>
            <div class="rpt-bar-track"><div class="rpt-bar-fill" style="width:<?php echo $pb; ?>%;background:#10b981"></div></div>
            <div class="rpt-bar-val">$ <?php echo number_format((float)$r['t'],0,',','.'); ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="rpt-panel rpt-full" style="margin-bottom:1rem">
    <div class="rpt-panel-title"><i class="fa-solid fa-table"></i> Detalle de compras (últimas 50)</div>
    <?php if(empty($rpt_compras['detalle'])): ?><p class="rpt-empty">Sin compras en el período</p><?php else: ?>
    <div style="overflow-x:auto">
    <table class="rpt-tbl"><thead><tr>
        <th>Fecha</th><th>N° Factura</th><th>Proveedor</th><th style="text-align:center">Estado</th><th style="text-align:right">Total</th>
    </tr></thead><tbody>
    <?php foreach($rpt_compras['detalle'] as $r): ?>
    <tr>
        <td style="color:var(--text-dim)"><?php echo date('d/m/Y',strtotime($r['fecha'])); ?></td>
        <td style="font-family:monospace;font-size:.78rem"><?php echo htmlspecialchars($r['numero_factura']??'—',ENT_QUOTES); ?></td>
        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['proveedor'],ENT_QUOTES); ?></td>
        <td style="text-align:center"><span class="b-<?php echo $r['estado']; ?>"><?php echo ucfirst($r['estado']); ?></span></td>
        <td style="text-align:right;font-weight:600">$ <?php echo number_format((float)$r['total'],2,',','.'); ?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
    </div><?php endif; ?>
</div>


<?php /* ═══════════════ TAB: STOCK ═══════════════ */ elseif ($tab === 'stock'): ?>

<div class="rpt-kpis">
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Valor inventario (costo)</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_stock['valor_inventario']??0),0); ?></div>
        <div class="rpt-kpi-sub"><?php echo (int)($rpt_stock['total_productos']??0); ?> productos activos</div>
    </div>
    <div class="rpt-kpi<?php echo ($rpt_stock['bajo_minimo']??0)>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-triangle-exclamation"></i> Bajo mínimo</div>
        <div class="rpt-kpi-val"><?php echo (int)($rpt_stock['bajo_minimo']??0); ?></div>
        <div class="rpt-kpi-sub">Requieren reposición</div>
    </div>
    <div class="rpt-kpi<?php echo ($rpt_stock['sin_stock']??0)>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-ban"></i> Sin stock</div>
        <div class="rpt-kpi-val"><?php echo (int)($rpt_stock['sin_stock']??0); ?></div>
        <div class="rpt-kpi-sub">Stock = 0</div>
    </div>
</div>

<div class="rpt-grid">
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-tag"></i> Valor por categoría</div>
        <?php $pc=$rpt_stock['por_categoria']??[];
        if(empty($pc)): ?><p class="rpt-empty">Sin datos</p><?php else:
        $maxP=max(array_map(fn($r)=>(float)$r['valor'],$pc))?:1;
        foreach($pc as $r): $pb=round((float)$r['valor']/$maxP*100); ?>
        <div class="rpt-bar-row">
            <div class="rpt-bar-label" title="<?php echo htmlspecialchars($r['categoria'],ENT_QUOTES); ?>"><?php echo htmlspecialchars($r['categoria'],ENT_QUOTES); ?></div>
            <div class="rpt-bar-track"><div class="rpt-bar-fill" style="width:<?php echo $pb; ?>%;background:#8b5cf6"></div></div>
            <div class="rpt-bar-val">$ <?php echo number_format((float)$r['valor'],0,',','.'); ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-triangle-exclamation" style="color:#f87171"></i> Bajo mínimo (top 20)</div>
        <?php if(empty($rpt_stock['bajo_minimo_lista'])): ?><p class="rpt-empty">Sin productos bajo mínimo</p><?php else: ?>
        <div style="overflow-x:auto">
        <table class="rpt-tbl"><thead><tr><th>Producto</th><th>Categoría</th><th style="text-align:right">Stock</th><th style="text-align:right">Mín.</th></tr></thead><tbody>
        <?php foreach($rpt_stock['bajo_minimo_lista'] as $r): ?>
        <tr>
            <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?>"><?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?></td>
            <td style="color:var(--text-dim);font-size:.75rem"><?php echo htmlspecialchars($r['categoria'],ENT_QUOTES); ?></td>
            <td style="text-align:right;color:#f87171;font-weight:600"><?php echo $fmtN((float)$r['stock_actual']); ?></td>
            <td style="text-align:right;color:var(--text-dim)"><?php echo $fmtN((float)$r['stock_minimo']); ?></td>
        </tr>
        <?php endforeach; ?></tbody></table>
        </div><?php endif; ?>
    </div>
</div>

<div class="rpt-panel rpt-full" style="margin-bottom:1rem">
    <div class="rpt-panel-title"><i class="fa-solid fa-ban" style="color:#f87171"></i> Sin stock (top 20)</div>
    <?php if(empty($rpt_stock['sin_stock_lista'])): ?><p class="rpt-empty">Todos los productos tienen stock</p><?php else: ?>
    <div style="overflow-x:auto">
    <table class="rpt-tbl"><thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th style="text-align:right">Stock</th><th style="text-align:right">Precio venta</th></tr></thead><tbody>
    <?php foreach($rpt_stock['sin_stock_lista'] as $r): ?>
    <tr>
        <td style="font-family:monospace;font-size:.76rem;color:var(--text-dim)"><?php echo htmlspecialchars($r['codigo']??'—',ENT_QUOTES); ?></td>
        <td><?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?></td>
        <td style="color:var(--text-dim);font-size:.75rem"><?php echo htmlspecialchars($r['categoria'],ENT_QUOTES); ?></td>
        <td style="text-align:right;color:#f87171;font-weight:600"><?php echo $fmtN((float)$r['stock_actual']); ?></td>
        <td style="text-align:right">$ <?php echo number_format((float)$r['precio_venta'],0,',','.'); ?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
    </div><?php endif; ?>
</div>


<?php /* ═══════════════ TAB: TESORERÍA ═══════════════ */ elseif ($tab === 'tesoreria'): ?>

<div class="rpt-kpis">
    <div class="rpt-kpi ok">
        <div class="rpt-kpi-label"><i class="fa-solid fa-arrow-up"></i> Ingresos período</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_tes['ingresos_mes']??0)); ?></div>
        <div class="rpt-kpi-sub">Confirmados</div>
    </div>
    <div class="rpt-kpi alert">
        <div class="rpt-kpi-label"><i class="fa-solid fa-arrow-down"></i> Egresos período</div>
        <div class="rpt-kpi-val"><?php echo $fmt((float)($rpt_tes['egresos_mes']??0)); ?></div>
        <div class="rpt-kpi-sub">Confirmados</div>
    </div>
    <?php $saldo=(float)($rpt_tes['ingresos_mes']??0)-(float)($rpt_tes['egresos_mes']??0); ?>
    <div class="rpt-kpi<?php echo $saldo<0?' alert':' ok'; ?>">
        <div class="rpt-kpi-label">Resultado neto</div>
        <div class="rpt-kpi-val"><?php echo $fmt($saldo); ?></div>
        <div class="rpt-kpi-sub">Ingresos − Egresos</div>
    </div>
    <div class="rpt-kpi<?php echo count($rpt_tes['cheques_pendientes']??[])>0?' alert':''; ?>">
        <div class="rpt-kpi-label"><i class="fa-solid fa-money-check"></i> Cheques pendientes</div>
        <div class="rpt-kpi-val"><?php echo count($rpt_tes['cheques_pendientes']??[]); ?></div>
        <div class="rpt-kpi-sub">A depositar</div>
    </div>
</div>

<div class="rpt-grid">
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-building-columns"></i> Saldo por cuenta</div>
        <?php $cuentas=$rpt_tes['cuentas']??[];
        if(empty($cuentas)): ?><p class="rpt-empty">Sin cuentas registradas</p><?php else:
        $maxC=max(array_map(fn($r)=>abs((float)$r['saldo']),$cuentas))?:1;
        foreach($cuentas as $r): $s=(float)$r['saldo']; $pb=round(abs($s)/$maxC*100); ?>
        <div class="rpt-bar-row">
            <div class="rpt-bar-label" title="<?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?>"><?php echo htmlspecialchars($r['nombre'],ENT_QUOTES); ?></div>
            <div class="rpt-bar-track"><div class="rpt-bar-fill" style="width:<?php echo $pb; ?>%;background:<?php echo $s>=0?'#22c55e':'#f87171'; ?>"></div></div>
            <div class="rpt-bar-val" style="color:<?php echo $s>=0?'#4ade80':'#f87171'; ?>">$ <?php echo number_format($s,0,',','.'); ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <div class="rpt-panel">
        <div class="rpt-panel-title"><i class="fa-solid fa-credit-card"></i> Ingresos por medio de pago</div>
        <?php echo barRows($rpt_tes['por_tipo_ingreso']??[],'medio','t','#22c55e',fn($r)=>'$ '.number_format((float)$r['t'],0,',','.')); ?>
    </div>
</div>

<?php if(!empty($rpt_tes['cheques_pendientes'])): ?>
<div class="rpt-panel rpt-full" style="margin-bottom:1rem">
    <div class="rpt-panel-title"><i class="fa-solid fa-money-check" style="color:#fcd34d"></i> Cheques pendientes de depósito</div>
    <div style="overflow-x:auto">
    <table class="rpt-tbl"><thead><tr><th>Número</th><th>Banco</th><th>Cuenta</th><th style="text-align:right">Monto</th><th>Fecha vencimiento</th></tr></thead><tbody>
    <?php foreach($rpt_tes['cheques_pendientes'] as $r): ?>
    <tr>
        <td style="font-family:monospace"><?php echo htmlspecialchars($r['numero'],ENT_QUOTES); ?></td>
        <td><?php echo htmlspecialchars($r['banco'],ENT_QUOTES); ?></td>
        <td style="color:var(--text-dim)"><?php echo htmlspecialchars($r['cuenta']??'—',ENT_QUOTES); ?></td>
        <td style="text-align:right;font-weight:600">$ <?php echo number_format((float)$r['monto'],2,',','.'); ?></td>
        <td><?php echo $r['fecha_vencimiento']?date('d/m/Y',strtotime($r['fecha_vencimiento'])):'—'; ?></td>
    </tr>
    <?php endforeach; ?></tbody></table>
    </div>
</div>
<?php endif; ?>

<div class="rpt-panel rpt-full" style="margin-bottom:1rem">
    <div class="rpt-panel-title"><i class="fa-solid fa-list"></i> Movimientos del período (últimos 50)</div>
    <?php if(empty($rpt_tes['movimientos'])): ?><p class="rpt-empty">Sin movimientos en el período</p><?php else: ?>
    <div style="overflow-x:auto">
    <table class="rpt-tbl"><thead><tr>
        <th>Fecha</th><th>Concepto</th><th>Cuenta</th><th>Medio</th><th style="text-align:center">Tipo</th><th style="text-align:center">Estado</th><th style="text-align:right">Monto</th>
    </tr></thead><tbody>
    <?php foreach($rpt_tes['movimientos'] as $r): ?>
    <tr>
        <td style="color:var(--text-dim)"><?php echo date('d/m/Y',strtotime($r['fecha'])); ?></td>
        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['concepto']??'—',ENT_QUOTES); ?></td>
        <td style="color:var(--text-dim)"><?php echo htmlspecialchars($r['cuenta']??'—',ENT_QUOTES); ?></td>
        <td style="color:var(--text-dim)"><?php echo htmlspecialchars($r['medio']??'—',ENT_QUOTES); ?></td>
        <td style="text-align:center"><span class="b-<?php echo $r['tipo']; ?>"><?php echo $r['tipo']==='ingreso'?'Ingreso':'Egreso'; ?></span></td>
        <td style="text-align:center"><span class="b-<?php echo $r['estado']; ?>"><?php echo ucfirst($r['estado']); ?></span></td>
        <td style="text-align:right;font-weight:600;color:<?php echo $r['tipo']==='ingreso'?'#4ade80':'#f87171'; ?>">
            <?php echo $r['tipo']==='ingreso'?'+':'−'; ?> $ <?php echo number_format((float)$r['monto'],2,',','.'); ?>
        </td>
    </tr>
    <?php endforeach; ?></tbody></table>
    </div><?php endif; ?>
</div>

<?php endif; ?>
