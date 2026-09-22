<?php
// ── Datos base ────────────────────────────────────────────────────────────────
$venta       = $venta       ?? [];
$items       = $items       ?? [];
$empresa     = $empresa     ?? [];
$afip_qr_url = $afip_qr_url ?? null;
$afip_pv     = $afip_pv     ?? null;

$fecha     = $venta['fecha'] ?? date('Y-m-d');
$fechaF    = date('d/m/Y', strtotime($fecha));
$total     = (float)($venta['total']       ?? 0);
$bruto     = (float)($venta['total_bruto'] ?? $total);
$desc_pct  = (float)($venta['descuento']   ?? 0);
$cond_pago = $venta['condicion_pago'] ?? 'Contado';
$cliente   = $venta['cliente_nombre']  ?? 'Consumidor Final';
$obs       = $venta['observaciones']   ?? '';

// ── Fiscal ────────────────────────────────────────────────────────────────────
$es_fiscal  = !empty($venta['cae']);
$tipo_letra = 'X';
$tipo_cod   = '00';
$afip_num   = null;
$cae_vto_f  = null;

if ($es_fiscal) {
    $tc = (int)($venta['tipo_cbte'] ?? 0);
    $tipo_letra = match($tc) { 1 => 'A', 6 => 'B', 11 => 'C', default => 'B' };
    $tipo_cod   = match($tc) { 1 => '001', 6 => '006', 11 => '011', default => '011' };
    $pv_str     = str_pad((int)$afip_pv, 5, '0', STR_PAD_LEFT);
    $nro_str    = str_pad((int)($venta['nro_afip'] ?? 0), 8, '0', STR_PAD_LEFT);
    $afip_num   = "{$pv_str}-{$nro_str}";
    $cae_vto_f  = !empty($venta['cae_vto'])
        ? date('d/m/Y', strtotime($venta['cae_vto']))
        : null;
}

// ── Emisor ────────────────────────────────────────────────────────────────────
$cuit_raw   = preg_replace('/\D/', '', $empresa['cuit'] ?? '');
$cuit_fmt   = strlen($cuit_raw) === 11
    ? substr($cuit_raw, 0, 2) . '-' . substr($cuit_raw, 2, 8) . '-' . substr($cuit_raw, 10)
    : $cuit_raw;

$cond_label = match($empresa['condicion_fiscal'] ?? '') {
    'RI'  => 'IVA Responsable Inscripto',
    'MT'  => 'Responsable Monotributo',
    'EX'  => 'IVA Sujeto Exento',
    'CF'  => 'Consumidor Final',
    default => $empresa['condicion_fiscal'] ?? ''
};

// ── Receptor ─────────────────────────────────────────────────────────────────
$cli_cuit = $venta['cliente_cuit'] ?? '';
$cli_dom  = $venta['cliente_domicilio'] ?? '';
$cli_cond = match($venta['cliente_condicion_fiscal'] ?? 'CF') {
    'RI'  => 'IVA Responsable Inscripto',
    'MT'  => 'Responsable Monotributo',
    'EX'  => 'Responsable Exento',
    default => 'Consumidor Final'
};

// ── Importes ─────────────────────────────────────────────────────────────────
$neto = 0; $iva_21 = 0; $iva_105 = 0; $no_gravado = 0; $exento = 0;
if ($es_fiscal && $tipo_letra === 'A') {
    $neto   = round($bruto / 1.21, 2);
    $iva_21 = round($bruto - $neto, 2);
} elseif ($es_fiscal && $tipo_letra === 'B') {
    $neto   = round($bruto / 1.21, 2);
    $iva_21 = round($bruto - $neto, 2);
} elseif ($es_fiscal && $tipo_letra === 'C') {
    $no_gravado = $total;
} else {
    $neto = $total;
}

// ── Total en letras ───────────────────────────────────────────────────────────
function _numLetras(float $n): string {
    $entero = (int)floor($n);
    $cents  = (int)round(($n - $entero) * 100);
    $u = ['','uno','dos','tres','cuatro','cinco','seis','siete','ocho','nueve',
          'diez','once','doce','trece','catorce','quince','dieciséis','diecisiete',
          'dieciocho','diecinueve'];
    $d = ['','','veinte','treinta','cuarenta','cincuenta',
          'sesenta','setenta','ochenta','noventa'];
    $c = ['','ciento','doscientos','trescientos','cuatrocientos','quinientos',
          'seiscientos','setecientos','ochocientos','novecientos'];
    $toS = function(int $n) use ($u,$d,$c,&$toS): string {
        if ($n === 0)   return '';
        if ($n === 100) return 'cien';
        if ($n < 20)    return $u[$n];
        if ($n < 100)   return $d[(int)($n/10)] . ($n%10 ? ' y ' . $u[$n%10] : '');
        $r = $c[(int)($n/100)];
        return $n%100 ? $r . ' ' . $toS($n%100) : $r;
    };
    if ($entero === 0) $txt = 'cero';
    elseif ($entero < 1000) $txt = $toS($entero);
    elseif ($entero < 2000) $txt = 'mil' . ($entero%1000 ? ' ' . $toS($entero%1000) : '');
    elseif ($entero < 1000000) {
        $m = (int)($entero/1000);
        $r = $entero%1000;
        $txt = $toS($m) . ' mil' . ($r ? ' ' . $toS($r) : '');
    } else {
        $m = (int)($entero/1000000);
        $r = $entero%1000000;
        $txt = $toS($m) . ($m===1?' millón':' millones') . ($r ? ' ' . _numLetras((float)$r) : '');
    }
    return strtoupper($txt) . ' CON ' . str_pad($cents, 2, '0', STR_PAD_LEFT) . '/100';
}
$total_letras = _numLetras($total);

// ── Logo embebido (para impresión sin requests externos) ──────────────────────
$logo_data = ''; $logo_mime = 'image/png';
$logo_rel  = $empresa['logo'] ?? '';
if ($logo_rel) {
    $logo_abs = dirname(__DIR__, 2) . '/' . ltrim($logo_rel, '/\\');
    if (file_exists($logo_abs)) {
        $logo_data = base64_encode(file_get_contents($logo_abs));
        $ext = strtolower(pathinfo($logo_abs, PATHINFO_EXTENSION));
        $logo_mime = $ext === 'svg' ? 'image/svg+xml' : 'image/' . $ext;
    }
}

$num_interno = '#' . str_pad((int)($venta['numero'] ?? 0), 4, '0', STR_PAD_LEFT);
?>
<style>
/* ── Override layout_print para forzar A4 ── */
@media print {
    @page { size: A4 portrait; margin: 8mm; }
    body  { background: #fff !important; padding: 0 !important; margin: 0 !important; }
    .print-shell  { border: none !important; border-radius: 0 !important;
                    max-width: none !important; box-shadow: none !important; }
    .print-actions { display: none !important; }
    .cmp-page { min-height: auto !important; }
}

/* ── Página A4 en pantalla ── */
.cmp-page {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10.5px;
    color: #111;
    width: 100%;
    min-height: 267mm;       /* 297mm - 2×8mm de margin */
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}
.cmp-page * { box-sizing: border-box; }

/* ══ ENCABEZADO 3 COLUMNAS ══════════════════════════════════════════════════ */
.cmp-enc { display: flex; border: 1.5px solid #000; }

/* Columna IZQUIERDA — emisor */
.cmp-em {
    flex: 1;
    padding: 7px 9px;
    border-right: 1.5px solid #000;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.cmp-em-logo   { max-height: 46px; max-width: 140px; margin-bottom: 5px; display: block; }
.cmp-em-nombre { font-size: 12px; font-weight: 700; margin-bottom: 3px; }
.cmp-em-dato   { font-size: 8.5px; color: #222; line-height: 1.6; }
.cmp-em-cond   { font-size: 8.5px; font-weight: 700; margin-top: 3px; }

/* Columna CENTRO — letra (mínimo 3×3cm ≈ 113×113px @ 96dpi) */
.cmp-ltr {
    width: 90px;
    min-height: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-right: 1.5px solid #000;
    padding: 6px 4px;
    text-align: center;
}
.cmp-ltr-l { font-size: 52px; font-weight: 900; line-height: 1; letter-spacing: -.02em; }
.cmp-ltr-c { font-size: 7.5px; color: #444; margin-top: 4px; letter-spacing: .04em; }

/* Columna DERECHA — datos del comprobante */
.cmp-doc {
    width: 240px;
    padding: 7px 9px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.cmp-doc-tipo   { font-size: 13px; font-weight: 900; text-transform: uppercase;
                  letter-spacing: .04em; margin-bottom: 2px; }
.cmp-doc-orig   { font-size: 7.5px; color: #666; margin-bottom: 6px; }
.cmp-doc-num    { font-size: 12px; font-weight: 700; font-family: 'Courier New', monospace;
                  margin-bottom: 5px; letter-spacing: .03em; }
.cmp-doc-fila   { font-size: 8.5px; line-height: 1.8; }
.cmp-doc-fila strong { font-weight: 700; }

/* ══ RECEPTOR ═══════════════════════════════════════════════════════════════ */
.cmp-rec { border: 1.5px solid #000; border-top: none; padding: 5px 9px; }
.cmp-rec-row { display: flex; flex-wrap: wrap; gap: 6px 18px; }
.cmp-rec-item { display: flex; flex-direction: column; }
.cmp-rec-lbl  { font-size: 7.5px; text-transform: uppercase; letter-spacing: .06em;
                color: #666; }
.cmp-rec-val  { font-size: 9px; font-weight: 700; }
.cmp-rec-nombre { font-size: 10.5px; font-weight: 700; margin-bottom: 4px; }

/* ══ TABLA ÍTEMS ════════════════════════════════════════════════════════════ */
.cmp-body {
    border: 1.5px solid #000;
    border-top: none;
    flex: 1;                 /* crece para llenar la página A4 */
    display: flex;
    flex-direction: column;
}
.cmp-tbl { width: 100%; border-collapse: collapse; }
.cmp-tbl thead th {
    background: #1a1a1a; color: #fff; padding: 3px 5px;
    font-size: 7.5px; text-align: left;
    text-transform: uppercase; letter-spacing: .06em; font-weight: 700;
    white-space: nowrap;
}
.cmp-tbl thead th.r { text-align: right; }
.cmp-tbl tbody td {
    padding: 3px 5px; font-size: 9px;
    border-bottom: 1px solid #eee; vertical-align: top;
}
.cmp-tbl tbody tr:last-child td { border-bottom: none; }
.cmp-tbl tbody tr:nth-child(even) td { background: #f9f9f9; }
.cmp-tbl td.r { text-align: right; font-variant-numeric: tabular-nums; }
.cmp-tbl td.b { font-weight: 700; }

/* Espacio flexible para rellenar hasta el pie */
.cmp-spacer-flex { flex: 1; min-height: 20px; }

/* ══ OBSERVACIONES ══════════════════════════════════════════════════════════ */
.cmp-obs { border-top: 1px dashed #ccc; padding: 3px 9px;
           font-size: 7.5px; color: #666; }

/* ══ TOTALES ════════════════════════════════════════════════════════════════ */
.cmp-tots { border: 1.5px solid #000; border-top: none; }
.cmp-tots-inner { display: flex; }

/* Columna letras (total en palabras) */
.cmp-tots-letras {
    flex: 1; padding: 5px 9px;
    border-right: 1px solid #ccc;
    font-size: 8px; color: #555;
    display: flex; flex-direction: column; justify-content: flex-end;
}
.cmp-tots-letras span { font-style: italic; }

/* Columna números */
.cmp-tots-nums { width: 230px; padding: 3px 0; }
.cmp-trow { display: flex; justify-content: space-between;
            padding: 2px 9px; font-size: 8.5px; border-bottom: 1px solid #eee; }
.cmp-trow:last-child { border-bottom: none; }
.cmp-tl { color: #555; }
.cmp-tv { font-variant-numeric: tabular-nums; font-weight: 600; }
.cmp-trow-total {
    border-top: 1.5px solid #000;
    padding: 4px 9px;
    display: flex; justify-content: space-between;
    font-size: 10.5px; font-weight: 900;
}

/* ══ FOOTER AFIP ════════════════════════════════════════════════════════════ */
.cmp-footer { border: 1.5px solid #000; border-top: none;
              display: flex; align-items: center; gap: 10px; padding: 6px 9px; }

.cmp-qr-wrap { flex-shrink: 0; }
.cmp-qr-wrap canvas { display: block; width: 80px; height: 80px; }

.cmp-afip-brand { flex-shrink: 0; text-align: center; }
.cmp-afip-logo  { font-size: 20px; font-weight: 900; color: #005a8b;
                  letter-spacing: -.03em; line-height: 1; }
.cmp-afip-sub   { font-size: 6.5px; font-weight: 700; font-style: italic;
                  color: #333; margin-top: 3px; line-height: 1.3; }

.cmp-cae-info   { flex: 1; }
.cmp-cae-num    { font-size: 10px; font-weight: 700; margin-bottom: 2px; }
.cmp-cae-vto    { font-size: 8.5px; margin-bottom: 4px; }
.cmp-cae-disc   { font-size: 6.5px; color: #777; line-height: 1.35; }

.cmp-footer-total { text-align: right; flex-shrink: 0; border-left: 1px solid #ddd;
                    padding-left: 10px; }
.cmp-footer-total .ftl { font-size: 7.5px; color: #666; }
.cmp-footer-total .ftv { font-size: 16px; font-weight: 900; }

/* Footer comprobante manual */
.cmp-footer-manual { border: 1.5px solid #000; border-top: none;
                     display: flex; justify-content: space-between;
                     align-items: center; padding: 5px 9px; }
.cmp-footer-manual .ftl { font-size: 7.5px; color: #888; }
.cmp-footer-manual .ftv { font-size: 14px; font-weight: 900; }
</style>

<div class="cmp-page">

    <!-- ════════════════════════════════════════════════════════════════════
         ENCABEZADO
    ════════════════════════════════════════════════════════════════════ -->
    <div class="cmp-enc">

        <!-- IZQUIERDA: emisor -->
        <div class="cmp-em">
            <?php if ($logo_data): ?>
            <img class="cmp-em-logo"
                 src="data:<?php echo $logo_mime; ?>;base64,<?php echo $logo_data; ?>"
                 alt="Logo">
            <?php endif; ?>
            <div class="cmp-em-nombre"><?php echo htmlspecialchars($empresa['razon_social'] ?? '', ENT_QUOTES); ?></div>
            <?php if ($empresa['slogan'] ?? ''): ?>
            <div class="cmp-em-dato" style="font-style:italic;margin-bottom:2px"><?php echo htmlspecialchars($empresa['slogan'], ENT_QUOTES); ?></div>
            <?php endif; ?>
            <div class="cmp-em-dato">
                <?php if ($empresa['domicilio'] ?? ''): ?>
                    <?php echo htmlspecialchars($empresa['domicilio'], ENT_QUOTES); ?><br>
                <?php endif; ?>
                <?php if ($empresa['localidad'] ?? ''): ?>
                    <?php echo htmlspecialchars($empresa['localidad'], ENT_QUOTES); ?><?php if ($empresa['provincia'] ?? ''): ?> — <?php echo htmlspecialchars($empresa['provincia'], ENT_QUOTES); ?><?php endif; ?><br>
                <?php endif; ?>
                <?php if ($empresa['telefono'] ?? ''): ?>Tel: <?php echo htmlspecialchars($empresa['telefono'], ENT_QUOTES); ?><br><?php endif; ?>
                <?php if ($empresa['email'] ?? ''): ?><?php echo htmlspecialchars($empresa['email'], ENT_QUOTES); ?><br><?php endif; ?>
            </div>
            <?php if ($cond_label): ?>
            <div class="cmp-em-cond"><?php echo htmlspecialchars($cond_label, ENT_QUOTES); ?></div>
            <?php endif; ?>
        </div>

        <!-- CENTRO: letra tipo comprobante (≥ 3×3 cm) -->
        <div class="cmp-ltr">
            <div class="cmp-ltr-l"><?php echo htmlspecialchars($tipo_letra, ENT_QUOTES); ?></div>
            <div class="cmp-ltr-c">COD. <?php echo $tipo_cod; ?></div>
        </div>

        <!-- DERECHA: datos del comprobante fiscal -->
        <div class="cmp-doc">
            <div class="cmp-doc-tipo"><?php echo $es_fiscal ? 'Factura' : 'Comprobante'; ?></div>
            <div class="cmp-doc-orig">Original — Pág. 1 de 1</div>

            <?php if ($es_fiscal && $afip_num): ?>
            <div class="cmp-doc-num">Nº <?php echo htmlspecialchars($afip_num, ENT_QUOTES); ?></div>
            <?php else: ?>
            <div class="cmp-doc-num"><?php echo $num_interno; ?></div>
            <?php endif; ?>

            <div class="cmp-doc-fila">
                <strong>Fecha:</strong> <?php echo $fechaF; ?><br>
                <?php if ($cuit_fmt): ?>
                <strong>CUIT:</strong> <?php echo htmlspecialchars($cuit_fmt, ENT_QUOTES); ?><br>
                <?php endif; ?>
                <?php if ($empresa['iibb'] ?? ''): ?>
                <strong>Ing. Brutos:</strong> <?php echo htmlspecialchars($empresa['iibb'], ENT_QUOTES); ?><br>
                <?php endif; ?>
                <?php if ($empresa['inicio_actividad'] ?? ''): ?>
                <strong>Inicio Actividad:</strong> <?php echo htmlspecialchars($empresa['inicio_actividad'], ENT_QUOTES); ?><br>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         RECEPTOR
    ════════════════════════════════════════════════════════════════════ -->
    <div class="cmp-rec">
        <div class="cmp-rec-nombre"><?php echo htmlspecialchars($cliente, ENT_QUOTES); ?></div>
        <div class="cmp-rec-row">
            <?php if ($cli_cuit): ?>
            <div class="cmp-rec-item">
                <span class="cmp-rec-lbl">CUIT</span>
                <span class="cmp-rec-val"><?php echo htmlspecialchars($cli_cuit, ENT_QUOTES); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($cli_dom): ?>
            <div class="cmp-rec-item">
                <span class="cmp-rec-lbl">Domicilio</span>
                <span class="cmp-rec-val"><?php echo htmlspecialchars($cli_dom, ENT_QUOTES); ?></span>
            </div>
            <?php endif; ?>
            <div class="cmp-rec-item">
                <span class="cmp-rec-lbl">Condición IVA</span>
                <span class="cmp-rec-val"><?php echo htmlspecialchars($cli_cond, ENT_QUOTES); ?></span>
            </div>
            <div class="cmp-rec-item">
                <span class="cmp-rec-lbl">Forma de Pago</span>
                <span class="cmp-rec-val"><?php echo htmlspecialchars($cond_pago, ENT_QUOTES); ?></span>
            </div>
            <div class="cmp-rec-item">
                <span class="cmp-rec-lbl">Fecha</span>
                <span class="cmp-rec-val"><?php echo $fechaF; ?></span>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         CUERPO — ÍTEMS (flex:1 para llenar A4)
    ════════════════════════════════════════════════════════════════════ -->
    <div class="cmp-body">
        <table class="cmp-tbl">
            <thead>
                <tr>
                    <th style="width:7%">Código</th>
                    <th style="width:5%">Unid.</th>
                    <th>Descripción</th>
                    <th class="r" style="width:7%">Cant.</th>
                    <th class="r" style="width:6%">Bonif.</th>
                    <th class="r" style="width:11%">Precio Unit.</th>
                    <?php if ($es_fiscal && $tipo_letra === 'A'): ?>
                    <th class="r" style="width:6%">% IVA</th>
                    <?php endif; ?>
                    <th class="r" style="width:12%">Importe</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?php echo htmlspecialchars($it['producto_id'] ?? '', ENT_QUOTES); ?></td>
                    <td>u</td>
                    <td><?php echo htmlspecialchars($it['producto_label'] ?? '', ENT_QUOTES); ?></td>
                    <td class="r"><?php echo number_format((float)($it['cantidad']   ?? 0), 2, ',', '.'); ?></td>
                    <td class="r">—</td>
                    <td class="r"><?php echo number_format((float)($it['precio_unit'] ?? 0), 2, ',', '.'); ?></td>
                    <?php if ($es_fiscal && $tipo_letra === 'A'): ?>
                    <td class="r">21 %</td>
                    <?php endif; ?>
                    <td class="r b"><?php echo number_format((float)($it['subtotal'] ?? 0), 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?php echo ($es_fiscal && $tipo_letra === 'A') ? 8 : 7; ?>"
                        style="text-align:center;color:#aaa;padding:10px">Sin ítems registrados</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Espacio flexible — rellena hasta el pie de página -->
        <div class="cmp-spacer-flex"></div>

        <?php if ($obs): ?>
        <div class="cmp-obs">Obs.: <?php echo htmlspecialchars($obs, ENT_QUOTES); ?></div>
        <?php endif; ?>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         TOTALES
    ════════════════════════════════════════════════════════════════════ -->
    <div class="cmp-tots">
        <div class="cmp-tots-inner">

            <!-- Total en letras -->
            <div class="cmp-tots-letras">
                <div style="font-size:7px;text-transform:uppercase;letter-spacing:.05em;color:#999;margin-bottom:3px">Son pesos</div>
                <span><?php echo $total_letras; ?></span>
            </div>

            <!-- Desglose numérico -->
            <div class="cmp-tots-nums">
                <?php if ($desc_pct > 0): ?>
                <div class="cmp-trow">
                    <span class="cmp-tl">Descuento</span>
                    <span class="cmp-tv"><?php echo number_format($desc_pct, 2, ',', '.'); ?> %</span>
                </div>
                <?php endif; ?>

                <?php if ($no_gravado > 0): ?>
                <div class="cmp-trow">
                    <span class="cmp-tl">No Gravado</span>
                    <span class="cmp-tv">$ <?php echo number_format($no_gravado, 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($exento > 0): ?>
                <div class="cmp-trow">
                    <span class="cmp-tl">Exento</span>
                    <span class="cmp-tv">$ <?php echo number_format($exento, 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($neto > 0): ?>
                <div class="cmp-trow">
                    <span class="cmp-tl">Neto Gravado</span>
                    <span class="cmp-tv">$ <?php echo number_format($neto, 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($iva_21 > 0): ?>
                <div class="cmp-trow">
                    <span class="cmp-tl">IVA 21 %</span>
                    <span class="cmp-tv">$ <?php echo number_format($iva_21, 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($iva_105 > 0): ?>
                <div class="cmp-trow">
                    <span class="cmp-tl">IVA 10,5 %</span>
                    <span class="cmp-tv">$ <?php echo number_format($iva_105, 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <div class="cmp-trow-total">
                    <span>TOTAL</span>
                    <span>$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         PIE DE PÁGINA
    ════════════════════════════════════════════════════════════════════ -->
    <?php if ($es_fiscal): ?>
    <div class="cmp-footer">

        <!-- QR AFIP -->
        <div class="cmp-qr-wrap">
            <canvas id="cmp-qr" width="80" height="80"
                    title="QR AFIP — verificar en afip.gob.ar"></canvas>
        </div>

        <!-- Marca AFIP / ARCA -->
        <div class="cmp-afip-brand">
            <div class="cmp-afip-logo">AFIP</div>
            <div class="cmp-afip-sub">Comprobante<br>Autorizado</div>
        </div>

        <!-- CAE + disclaimer -->
        <div class="cmp-cae-info">
            <div class="cmp-cae-num">C.A.E. Nº: <?php echo htmlspecialchars($venta['cae'] ?? '', ENT_QUOTES); ?></div>
            <div class="cmp-cae-vto">Fecha Vto. CAE: <?php echo $cae_vto_f ?? '—'; ?></div>
            <div class="cmp-cae-disc">
                La Administración Federal no se responsabiliza por los datos ingresados en el detalle de la operación.
            </div>
        </div>

        <!-- Total destacado -->
        <div class="cmp-footer-total">
            <div class="ftl">Importe Total</div>
            <div class="ftv">$ <?php echo number_format($total, 2, ',', '.'); ?></div>
        </div>

    </div>

    <script>
    (function () {
        var qrUrl  = <?php echo json_encode($afip_qr_url); ?>;
        var canvas = document.getElementById('cmp-qr');
        if (!canvas || !qrUrl) return;
        var img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function () {
            canvas.getContext('2d').drawImage(img, 0, 0, 80, 80);
        };
        img.onerror = function () {
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#f3f4f6';
            ctx.fillRect(0, 0, 80, 80);
            ctx.fillStyle = '#999';
            ctx.font = '6px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('QR no disponible', 40, 38);
            ctx.fillText('(sin internet)', 40, 48);
        };
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&ecc=M&data='
                  + encodeURIComponent(qrUrl);
    }());
    </script>

    <?php else: ?>
    <div class="cmp-footer-manual">
        <div class="ftl">Generado por AKIM Gestión Comercial</div>
        <div>
            <div class="ftl">TOTAL</div>
            <div class="ftv">$ <?php echo number_format($total, 2, ',', '.'); ?></div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /cmp-page -->
