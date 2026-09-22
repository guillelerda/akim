<?php

namespace Controllers;

use MVC\Router;
use Model\Model_apphelper;
use Model\ActiveDBC;

class Controllers_reportes
{
    // ── EXPORTAR (PDF / XLSX) ─────────────────────────────────────────────────
    public static function export(Router $router): void
    {
        if (empty($_SESSION['usuario'])) { header('Location: login'); exit; }

        $tab    = $_GET['tab']    ?? 'resumen';
        $desde  = $_GET['desde']  ?? date('Y-m-01');
        $hasta  = $_GET['hasta']  ?? date('Y-m-t');
        $format = $_GET['format'] ?? 'pdf';
        $tab    = in_array($tab, ['resumen','ventas','compras','stock','tesoreria']) ? $tab : 'resumen';
        $format = in_array($format, ['pdf','xlsx']) ? $format : 'pdf';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-t');

        $data = match($tab) {
            'ventas'    => self::_getVentas($desde, $hasta),
            'compras'   => self::_getCompras($desde, $hasta),
            'stock'     => self::_getStock(),
            'tesoreria' => self::_getTesoreria($desde, $hasta),
            default     => self::_getResumen(),
        };

        $tabLabels = ['resumen'=>'Resumen','ventas'=>'Ventas','compras'=>'Compras','stock'=>'Stock','tesoreria'=>'Tesorería'];
        $titulo    = 'Reporte ' . ($tabLabels[$tab] ?? $tab);
        $periodo   = $tab === 'stock' ? '' : " | {$desde} al {$hasta}";
        $filename  = 'akim_' . $tab . '_' . date('Ymd');

        if ($format === 'xlsx') {
            self::_exportXlsx($tab, $titulo, $periodo, $data, $filename);
        } else {
            self::_exportPdf($tab, $titulo, $periodo, $data, $filename);
        }
    }

    // ── XLSX ──────────────────────────────────────────────────────────────────
    private static function _exportXlsx(string $tab, string $titulo, string $periodo, array $data, string $filename): void
    {
        $wb = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $wb->removeSheetByIndex(0);

        $sheets = self::_buildSheets($tab, $data);
        foreach ($sheets as [$sheetTitle, $headers, $rows]) {
            self::_addSheet($wb, $sheetTitle, $headers, $rows, $titulo . $periodo);
        }

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($wb))->save('php://output');
        exit;
    }

    private static function _addSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $wb, string $title, array $headers, array $rows, string $subtitle): void
    {
        $ws = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($wb, mb_substr($title, 0, 31));
        $wb->addSheet($ws);

        // Subtítulo
        $ws->setCellValue('A1', $subtitle);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $ws->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(count($headers),1)) . '1');

        // Headers fila 2
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue($col . '2', $h);
            $ws->getStyle($col . '2')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $ws->getStyle($col . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1e3a5f');
        }

        // Datos desde fila 3
        $r = 3;
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $cell) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $ws->setCellValue($col . $r, $cell);
            }
            $r++;
        }

        // Auto-size
        for ($i = 1; $i <= count($headers); $i++) {
            $ws->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    // ── PDF ───────────────────────────────────────────────────────────────────
    private static function _exportPdf(string $tab, string $titulo, string $periodo, array $data, string $filename): void
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AKIM Gestión'); $pdf->SetTitle($titulo);
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10); $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage(); $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 8, $titulo . $periodo, 0, 1);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 5, 'Generado: ' . date('d/m/Y H:i'), 0, 1);
        $pdf->Ln(2);

        $sheets = self::_buildSheets($tab, $data);
        $first  = true;
        foreach ($sheets as [$sheetTitle, $headers, $rows]) {
            if (!$first) { $pdf->Ln(5); }
            $first = false;
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, $sheetTitle, 0, 1);
            $pdf->writeHTML(self::_htmlTable($headers, $rows), true, false, true, false, '');
        }

        while (ob_get_level()) ob_end_clean();
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }

    private static function _htmlTable(array $headers, array $rows): string
    {
        $th = '<table border="0.5" cellpadding="3" style="width:100%;font-size:7.5px;font-family:helvetica"><thead><tr>';
        foreach ($headers as $h) {
            $th .= '<th style="background-color:#1e3a5f;color:#ffffff;font-weight:bold;text-align:left">' . htmlspecialchars($h, ENT_QUOTES) . '</th>';
        }
        $th .= '</tr></thead><tbody>';
        $even = false;
        foreach ($rows as $row) {
            $bg = $even ? 'background-color:#f0f4f8' : '';
            $th .= '<tr>';
            foreach (array_values($row) as $cell) {
                $th .= '<td style="' . $bg . '">' . htmlspecialchars((string)$cell, ENT_QUOTES) . '</td>';
            }
            $th .= '</tr>';
            $even = !$even;
        }
        return $th . '</tbody></table>';
    }

    // ── DATOS POR TAB → SHEETS ────────────────────────────────────────────────
    private static function _buildSheets(string $tab, array $d): array
    {
        $fmtM = fn(float $v): string => '$ ' . number_format($v, 2, ',', '.');
        $fmtN = fn(float $v): string => number_format($v, 0, ',', '.');

        return match($tab) {

        'resumen' => [
            ['KPIs', ['Indicador','Valor','Detalle'], [
                ['Ventas hoy (importe)',       $fmtM((float)($d['hoy']['total']??0)),            ($d['hoy']['cant']??0).' operaciones'],
                ['Ventas mes actual',          $fmtM((float)($d['mes_actual']['total']??0)),      ($d['mes_actual']['cant']??0).' operaciones'],
                ['Ventas año actual',          $fmtM((float)($d['anio_actual']['total']??0)),     ($d['anio_actual']['cant']??0).' operaciones'],
                ['Clientes activos',           (string)($d['clientes_total']??0),                ''],
                ['Facturas de compra pendientes',(string)($d['facturas_pendientes']??0),          ''],
                ['Productos bajo mínimo',      (string)($d['stock_bajo']??0),                    ''],
            ]],
            ['Ventas por mes', ['Mes','Cant.','Total'], array_map(fn($r)=>[
                self::_mesLabel($r['mes']), (int)$r['cant'], $fmtM((float)$r['total'])
            ], $d['ventas_mes']??[])],
            ['Compras por mes', ['Mes','Cant.','Total'], array_map(fn($r)=>[
                self::_mesLabel($r['mes']), (int)$r['cant'], $fmtM((float)$r['total'])
            ], $d['compras_mes']??[])],
            ['Top Productos', ['Producto','Unidades','Total'], array_map(fn($r)=>[
                $r['nombre'], $fmtN((float)$r['unidades']), $fmtM((float)$r['total'])
            ], $d['top_productos']??[])],
            ['Top Clientes', ['Cliente','Operaciones','Total'], array_map(fn($r)=>[
                $r['cliente'], (int)$r['operaciones'], $fmtM((float)$r['total'])
            ], $d['top_clientes']??[])],
        ],

        'ventas' => [
            ['Resumen Ventas', ['Indicador','Valor'], [
                ['Total facturado',     $fmtM((float)($d['total']??0))],
                ['Total cobrado',       $fmtM((float)($d['total_cobrado']??0))],
                ['Pendiente de cobro',  $fmtM((float)($d['total_pendiente']??0))],
                ['Ventas cobradas',     (string)($d['cobradas']??0)],
                ['Ventas emitidas',     (string)($d['emitidas']??0)],
            ]],
            ['Por Condición', ['Condición','Cant.','Total'], array_map(fn($r)=>[
                $r['condicion_pago'], (int)$r['c'], $fmtM((float)$r['t'])
            ], $d['por_condicion']??[])],
            ['Top Productos', ['Producto','Unidades','Total'], array_map(fn($r)=>[
                $r['nombre'], $fmtN((float)$r['unidades']), $fmtM((float)$r['total'])
            ], $d['top_productos']??[])],
            ['Detalle Ventas', ['Fecha','Número','Cliente','Condición','Estado','Total'], array_map(fn($r)=>[
                $r['fecha'], $r['numero']??$r['idx'], $r['cliente'], $r['condicion_pago']??'', ucfirst($r['estado']), $fmtM((float)$r['total'])
            ], $d['detalle']??[])],
        ],

        'compras' => [
            ['Resumen Compras', ['Indicador','Valor'], [
                ['Total compras',        $fmtM((float)($d['total']??0))],
                ['Facturas totales',     (string)($d['cant']??0)],
                ['Pendiente de pago',    $fmtM((float)($d['total_pendiente']??0))],
                ['Facturas pendientes',  (string)($d['pendientes']??0)],
            ]],
            ['Por Proveedor', ['Proveedor','Fact.','Total'], array_map(fn($r)=>[
                $r['proveedor'], (int)$r['c'], $fmtM((float)$r['t'])
            ], $d['por_proveedor']??[])],
            ['Detalle Compras', ['Fecha','N° Factura','Proveedor','Estado','Total'], array_map(fn($r)=>[
                $r['fecha'], $r['numero_factura']??'—', $r['proveedor'], ucfirst($r['estado']), $fmtM((float)$r['total'])
            ], $d['detalle']??[])],
        ],

        'stock' => [
            ['Resumen Stock', ['Indicador','Valor'], [
                ['Valor inventario (costo)', $fmtM((float)($d['valor_inventario']??0))],
                ['Productos activos',        (string)($d['total_productos']??0)],
                ['Bajo mínimo',              (string)($d['bajo_minimo']??0)],
                ['Sin stock',                (string)($d['sin_stock']??0)],
            ]],
            ['Por Categoría', ['Categoría','Productos','Valor Costo'], array_map(fn($r)=>[
                $r['categoria'], (int)$r['cant'], $fmtM((float)$r['valor'])
            ], $d['por_categoria']??[])],
            ['Bajo Mínimo', ['Producto','Código','Categoría','Stock actual','Stock mín.','Precio venta'], array_map(fn($r)=>[
                $r['nombre'], $r['codigo']??'', $r['categoria'], $fmtN((float)$r['stock_actual']), $fmtN((float)$r['stock_minimo']), $fmtM((float)$r['precio_venta'])
            ], $d['bajo_minimo_lista']??[])],
            ['Sin Stock', ['Producto','Código','Categoría','Stock','Precio venta'], array_map(fn($r)=>[
                $r['nombre'], $r['codigo']??'', $r['categoria'], $fmtN((float)$r['stock_actual']), $fmtM((float)$r['precio_venta'])
            ], $d['sin_stock_lista']??[])],
        ],

        'tesoreria' => [
            ['Resumen Tesorería', ['Indicador','Valor'], [
                ['Ingresos período',    $fmtM((float)($d['ingresos_mes']??0))],
                ['Egresos período',     $fmtM((float)($d['egresos_mes']??0))],
                ['Resultado neto',      $fmtM((float)($d['ingresos_mes']??0)-(float)($d['egresos_mes']??0))],
                ['Cheques pendientes',  (string)count($d['cheques_pendientes']??[])],
            ]],
            ['Saldo por Cuenta', ['Cuenta','Tipo','Saldo'], array_map(fn($r)=>[
                $r['nombre'], $r['tipo'], $fmtM((float)$r['saldo'])
            ], $d['cuentas']??[])],
            ['Por Medio de Pago', ['Medio','Total ingresos'], array_map(fn($r)=>[
                $r['medio'], $fmtM((float)$r['t'])
            ], $d['por_tipo_ingreso']??[])],
            ['Cheques Pendientes', ['Número','Banco','Cuenta','Monto','Fecha vencimiento'], array_map(fn($r)=>[
                $r['numero'], $r['banco'], $r['cuenta']??'—', $fmtM((float)$r['monto']), $r['fecha_vencimiento']??'—'
            ], $d['cheques_pendientes']??[])],
            ['Movimientos', ['Fecha','Concepto','Cuenta','Medio','Tipo','Estado','Monto'], array_map(fn($r)=>[
                $r['fecha'], $r['concepto']??'', $r['cuenta']??'', $r['medio']??'', ucfirst($r['tipo']), ucfirst($r['estado']), $fmtM((float)$r['monto'])
            ], $d['movimientos']??[])],
        ],

        default => [],
        };
    }

    private static function _mesLabel(string $ym): string
    {
        $m = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
        [$y, $mo] = explode('-', $ym);
        return ($m[$mo] ?? $mo) . ' ' . substr($y, 2);
    }

    public static function lista(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        // Rango de fechas (GET)
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-t');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-t');

        $tab = $_GET['tab'] ?? 'resumen';
        $tab = in_array($tab, ['resumen','ventas','compras','stock','tesoreria']) ? $tab : 'resumen';

        $router->render('reportes', [
            'protegida'  => true,
            'auth'       => true,
            'page_title' => 'Reportes',
            'username'   => $userName,
            'nombre'     => $nombre,
            'perfil'     => $perfil,
            'tab'        => $tab,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'resumen'    => self::_getResumen(),
            'rpt_ventas' => self::_getVentas($desde, $hasta),
            'rpt_compras'=> self::_getCompras($desde, $hasta),
            'rpt_stock'  => self::_getStock(),
            'rpt_tes'    => self::_getTesoreria($desde, $hasta),
        ]);
    }

    // ── RESUMEN ───────────────────────────────────────────────────────────────
    private static function _getResumen(): array
    {
        $db = ActiveDBC::getDB();
        $d  = [
            'hoy'                 => ['cant' => 0, 'total' => 0.0],
            'mes_actual'          => ['cant' => 0, 'total' => 0.0],
            'anio_actual'         => ['cant' => 0, 'total' => 0.0],
            'ventas_mes'          => [],
            'compras_mes'         => [],
            'top_productos'       => [],
            'top_clientes'        => [],
            'stock_bajo'          => 0,
            'clientes_total'      => 0,
            'facturas_pendientes' => 0,
        ];

        $safe = function(callable $fn) use (&$d, $db) {
            try { $fn($db, $d); } catch (\Throwable $e) {}
        };

        $safe(function($db, &$d) {
            $r = $db->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND DATE(fecha)=CURDATE()");
            if ($r) { $row = $r->fetch(); $d['hoy'] = ['cant'=>(int)$row['c'],'total'=>(float)$row['t']]; }
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND DATE_FORMAT(fecha,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
            if ($r) { $row = $r->fetch(); $d['mes_actual'] = ['cant'=>(int)$row['c'],'total'=>(float)$row['t']]; }
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND YEAR(fecha)=YEAR(CURDATE())");
            if ($r) { $row = $r->fetch(); $d['anio_actual'] = ['cant'=>(int)$row['c'],'total'=>(float)$row['t']]; }
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT DATE_FORMAT(fecha,'%Y-%m') mes, COUNT(*) cant, COALESCE(SUM(total),0) total FROM vta_ventas WHERE estado!='anulada' AND fecha>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes ASC");
            $d['ventas_mes'] = $r ? $r->fetchAll() : [];
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT DATE_FORMAT(fecha,'%Y-%m') mes, COUNT(*) cant, COALESCE(SUM(total),0) total FROM cpa_facturas WHERE estado!='anulada' AND fecha>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes ASC");
            $d['compras_mes'] = $r ? $r->fetchAll() : [];
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT i.producto_label nombre, SUM(i.cantidad) unidades, SUM(i.subtotal) total FROM vta_ventas_items i JOIN vta_ventas v ON v.idx=i.venta_idx WHERE v.estado!='anulada' GROUP BY i.producto_label ORDER BY total DESC LIMIT 8");
            $d['top_productos'] = $r ? $r->fetchAll() : [];
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT COALESCE(c.razon_social,'Consumidor final') cliente, COUNT(*) operaciones, SUM(v.total) total FROM vta_ventas v LEFT JOIN vta_clientes c ON c.id=v.cliente_id WHERE v.estado!='anulada' GROUP BY v.cliente_id ORDER BY total DESC LIMIT 8");
            $d['top_clientes'] = $r ? $r->fetchAll() : [];
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT COUNT(*) c FROM stk_productos WHERE habilitado=1 AND stock_actual IS NOT NULL AND stock_minimo>0 AND stock_actual<=stock_minimo");
            if ($r) $d['stock_bajo'] = (int)$r->fetch()['c'];
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT COUNT(*) c FROM vta_clientes WHERE habilitado=1");
            if ($r) $d['clientes_total'] = (int)$r->fetch()['c'];
        });
        $safe(function($db, &$d) {
            $r = $db->query("SELECT COUNT(*) c FROM cpa_facturas WHERE estado='pendiente'");
            if ($r) $d['facturas_pendientes'] = (int)$r->fetch()['c'];
        });

        return $d;
    }

    // ── VENTAS ────────────────────────────────────────────────────────────────
    private static function _getVentas(string $desde, string $hasta): array
    {
        $db = ActiveDBC::getDB();
        $d  = [
            'total'           => 0.0,
            'cant'            => 0,
            'cobradas'        => 0,
            'emitidas'        => 0,
            'total_cobrado'   => 0.0,
            'total_pendiente' => 0.0,
            'por_condicion'   => [],
            'por_dia'         => [],
            'top_productos'   => [],
            'detalle'         => [],
        ];

        try {
            $stmt = $db->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND fecha BETWEEN ? AND ?");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $row = $stmt->fetch(); $d['total'] = (float)$row['t']; $d['cant'] = (int)$row['c'];
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT estado, COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND fecha BETWEEN ? AND ? GROUP BY estado");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            foreach ($stmt->fetchAll() as $row) {
                if ($row['estado'] === 'cobrada')  { $d['cobradas']      = (int)$row['c']; $d['total_cobrado']   = (float)$row['t']; }
                if ($row['estado'] === 'emitida')  { $d['emitidas']      = (int)$row['c']; $d['total_pendiente'] = (float)$row['t']; }
            }
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT condicion_pago, COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND fecha BETWEEN ? AND ? GROUP BY condicion_pago ORDER BY t DESC");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['por_condicion'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT DATE(fecha) dia, COUNT(*) c, COALESCE(SUM(total),0) t FROM vta_ventas WHERE estado!='anulada' AND fecha BETWEEN ? AND ? GROUP BY DATE(fecha) ORDER BY dia ASC");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['por_dia'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT i.producto_label nombre, SUM(i.cantidad) unidades, SUM(i.subtotal) total FROM vta_ventas_items i JOIN vta_ventas v ON v.idx=i.venta_idx WHERE v.estado!='anulada' AND v.fecha BETWEEN ? AND ? GROUP BY i.producto_label ORDER BY total DESC LIMIT 10");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['top_productos'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT v.idx, v.numero, DATE(v.fecha) fecha, v.total, v.estado, v.condicion_pago, COALESCE(c.razon_social,'Consumidor final') cliente FROM vta_ventas v LEFT JOIN vta_clientes c ON c.id=v.cliente_id WHERE v.estado!='anulada' AND v.fecha BETWEEN ? AND ? ORDER BY v.fecha DESC LIMIT 50");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['detalle'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        return $d;
    }

    // ── COMPRAS ───────────────────────────────────────────────────────────────
    private static function _getCompras(string $desde, string $hasta): array
    {
        $db = ActiveDBC::getDB();
        $d  = [
            'total'          => 0.0,
            'cant'           => 0,
            'pendientes'     => 0,
            'total_pendiente'=> 0.0,
            'por_proveedor'  => [],
            'por_dia'        => [],
            'detalle'        => [],
        ];

        try {
            $stmt = $db->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM cpa_facturas WHERE estado!='anulada' AND fecha BETWEEN ? AND ?");
            $stmt->execute([$desde, $hasta]);
            $row = $stmt->fetch(); $d['total'] = (float)$row['t']; $d['cant'] = (int)$row['c'];
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM cpa_facturas WHERE estado='pendiente' AND fecha BETWEEN ? AND ?");
            $stmt->execute([$desde, $hasta]);
            $row = $stmt->fetch(); $d['pendientes'] = (int)$row['c']; $d['total_pendiente'] = (float)$row['t'];
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT COALESCE(p.razon_social,'Sin proveedor') proveedor, COUNT(*) c, COALESCE(SUM(f.total),0) t FROM cpa_facturas f LEFT JOIN cpa_proveedores p ON p.id=f.proveedor_id WHERE f.estado!='anulada' AND f.fecha BETWEEN ? AND ? GROUP BY f.proveedor_id ORDER BY t DESC LIMIT 10");
            $stmt->execute([$desde, $hasta]);
            $d['por_proveedor'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT DATE(fecha) dia, COUNT(*) c, COALESCE(SUM(total),0) t FROM cpa_facturas WHERE estado!='anulada' AND fecha BETWEEN ? AND ? GROUP BY DATE(fecha) ORDER BY dia ASC");
            $stmt->execute([$desde, $hasta]);
            $d['por_dia'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT f.id, f.numero_factura, DATE(f.fecha) fecha, f.total, f.estado, COALESCE(p.razon_social,'Sin proveedor') proveedor FROM cpa_facturas f LEFT JOIN cpa_proveedores p ON p.id=f.proveedor_id WHERE f.estado!='anulada' AND f.fecha BETWEEN ? AND ? ORDER BY f.fecha DESC LIMIT 50");
            $stmt->execute([$desde, $hasta]);
            $d['detalle'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        return $d;
    }

    // ── STOCK ─────────────────────────────────────────────────────────────────
    private static function _getStock(): array
    {
        $db = ActiveDBC::getDB();
        $d  = [
            'valor_inventario' => 0.0,
            'total_productos'  => 0,
            'sin_stock'        => 0,
            'bajo_minimo'      => 0,
            'por_categoria'    => [],
            'bajo_minimo_lista'=> [],
            'sin_stock_lista'  => [],
        ];

        try {
            $r = $db->query("SELECT COUNT(*) c, COALESCE(SUM(s.stock_actual * p.precio_costo),0) val FROM stk_stock s JOIN stk_productos p ON p.id=s.producto_id WHERE p.habilitado=1");
            if ($r) { $row = $r->fetch(); $d['valor_inventario'] = (float)$row['val']; $d['total_productos'] = (int)$row['c']; }
        } catch (\Throwable $e) {}

        try {
            $r = $db->query("SELECT COUNT(*) c FROM stk_stock s JOIN stk_productos p ON p.id=s.producto_id WHERE p.habilitado=1 AND s.stock_actual<=0");
            if ($r) $d['sin_stock'] = (int)$r->fetch()['c'];
        } catch (\Throwable $e) {}

        try {
            $r = $db->query("SELECT COUNT(*) c FROM stk_productos WHERE habilitado=1 AND stock_minimo>0 AND stock_actual<=stock_minimo");
            if ($r) $d['bajo_minimo'] = (int)$r->fetch()['c'];
        } catch (\Throwable $e) {}

        try {
            $r = $db->query("SELECT COALESCE(cat.nombre,'Sin categoría') categoria, COUNT(p.id) cant, COALESCE(SUM(s.stock_actual * p.precio_costo),0) valor FROM stk_productos p LEFT JOIN stk_categorias cat ON cat.id=p.categoria_id LEFT JOIN stk_stock s ON s.producto_id=p.id WHERE p.habilitado=1 GROUP BY p.categoria_id ORDER BY valor DESC LIMIT 12");
            if ($r) $d['por_categoria'] = $r->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $r = $db->query("SELECT p.nombre, p.codigo, COALESCE(cat.nombre,'—') categoria, p.stock_actual, p.stock_minimo, p.precio_venta FROM stk_productos p LEFT JOIN stk_categorias cat ON cat.id=p.categoria_id WHERE p.habilitado=1 AND p.stock_minimo>0 AND p.stock_actual<=p.stock_minimo ORDER BY (p.stock_actual-p.stock_minimo) ASC LIMIT 20");
            if ($r) $d['bajo_minimo_lista'] = $r->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $r = $db->query("SELECT p.nombre, p.codigo, COALESCE(cat.nombre,'—') categoria, COALESCE(s.stock_actual,0) stock_actual, p.precio_venta FROM stk_productos p LEFT JOIN stk_categorias cat ON cat.id=p.categoria_id LEFT JOIN stk_stock s ON s.producto_id=p.id WHERE p.habilitado=1 AND (s.stock_actual<=0 OR s.stock_actual IS NULL) ORDER BY p.nombre ASC LIMIT 20");
            if ($r) $d['sin_stock_lista'] = $r->fetchAll();
        } catch (\Throwable $e) {}

        return $d;
    }

    // ── TESORERÍA ─────────────────────────────────────────────────────────────
    private static function _getTesoreria(string $desde, string $hasta): array
    {
        $db = ActiveDBC::getDB();
        $d  = [
            'cuentas'         => [],
            'ingresos_mes'    => 0.0,
            'egresos_mes'     => 0.0,
            'cheques_pendientes'  => [],
            'movimientos'     => [],
            'por_tipo_ingreso'=> [],
        ];

        try {
            $r = $db->query("SELECT c.nombre, c.tipo, COALESCE(SUM(CASE WHEN m.tipo='ingreso' THEN m.monto ELSE -m.monto END),0) saldo FROM tes_cuentas c LEFT JOIN tes_movimientos m ON m.cuenta_id=c.id AND m.estado='confirmado' WHERE c.habilitada=1 GROUP BY c.id ORDER BY saldo DESC");
            if ($r) $d['cuentas'] = $r->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT COALESCE(SUM(monto),0) t FROM tes_movimientos WHERE tipo='ingreso' AND estado='confirmado' AND fecha BETWEEN ? AND ?");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['ingresos_mes'] = (float)$stmt->fetch()['t'];
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT COALESCE(SUM(monto),0) t FROM tes_movimientos WHERE tipo='egreso' AND estado='confirmado' AND fecha BETWEEN ? AND ?");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['egresos_mes'] = (float)$stmt->fetch()['t'];
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT m.concepto, m.tipo, m.medio, m.monto, m.estado, DATE(m.fecha) fecha, c.nombre cuenta FROM tes_movimientos m LEFT JOIN tes_cuentas c ON c.id=m.cuenta_id WHERE m.fecha BETWEEN ? AND ? ORDER BY m.fecha DESC LIMIT 50");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['movimientos'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $r = $db->query("SELECT ch.numero, ch.banco, ch.monto, ch.fecha_vencimiento, c.nombre cuenta FROM tes_cheques ch LEFT JOIN tes_cuentas c ON c.id=ch.cuenta_id WHERE ch.estado='cartera' ORDER BY ch.fecha_vencimiento ASC LIMIT 20");
            if ($r) $d['cheques_pendientes'] = $r->fetchAll();
        } catch (\Throwable $e) {}

        try {
            $stmt = $db->prepare("SELECT medio, COALESCE(SUM(monto),0) t FROM tes_movimientos WHERE tipo='ingreso' AND estado='confirmado' AND fecha BETWEEN ? AND ? GROUP BY medio ORDER BY t DESC");
            $stmt->execute([$desde, $hasta . ' 23:59:59']);
            $d['por_tipo_ingreso'] = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        return $d;
    }
}
