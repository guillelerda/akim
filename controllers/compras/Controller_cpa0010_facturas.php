<?php

namespace Controllers\Compras;

use MVC\Router;
use Model\Model_apphelper;
use Model\Model_parametros;
use Model\Compras\Model_cpa0010_facturas;
use Model\Compras\Model_cpa0001_proveedores;
use Model\Stock\Model_stk_0001_productos;
use Model\Stock\Model_stk_depositos;
use Model\Tesoreria\Model_tes_cuentas;
use Model\Tesoreria\Model_tes_movimientos;
use Model\Tesoreria\Model_tes_cheques;

class Controller_cpa0010_facturas
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $obj       = new Model_cpa0010_facturas();
        $lista     = $obj->queryAll();
        $stats_mes = $obj->getStatsMes();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $idx    = $_POST['idx']    ?? '';
            if ($idx) {
                if ($accion === 'anular')   { $obj->anular($idx); }
                if ($accion === 'eliminar') { $obj->eliminar($idx); $_SESSION['flash_ok'] = 'Comprobante eliminado.'; }
                if ($accion === 'pagar') {
                    $factura = $obj->queryIdx($idx);
                    if ($factura && $factura['estado'] === 'pendiente') {
                        $pagoData = array_merge($_POST, [
                            'total'           => $factura['total'],
                            'numero'          => $factura['numero']          ?? '',
                            'proveedor_label' => $factura['prov_nombre']     ?? ($factura['proveedor_label'] ?? ''),
                        ]);
                        self::_registrarPagoTesoreria($pagoData, $idx);
                        $obj->marcarPagada($idx);
                        $_SESSION['flash_ok'] = 'Pago registrado correctamente.';
                    }
                }
            }
            echo "<script>location.href='cpa_facturas';</script>";
            return;
        }

        $cuentas_tes = Model_tes_cuentas::queryHabilitadas();

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('compras/cpa0010_facturas', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Facturas de Compra',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $lista,
            'stats_mes'   => $stats_mes,
            'cuentas_tes' => $cuentas_tes,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    public static function nueva(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $errores     = [];
        $proveedores = (new Model_cpa0001_proveedores())->queryAll();
        $params      = Model_parametros::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data                     = $_POST;
            $items                    = json_decode($data['items_json'] ?? '[]', true) ?: [];
            $proveedor_id             = trim($data['proveedor_id']    ?? '');
            $proveedor_libre          = trim($data['proveedor_libre'] ?? '');
            $auto_registrar           = !empty($data['auto_registrar']);
            $auto_registrar_productos = !empty($data['auto_registrar_productos']);

            if (empty($proveedor_id) && empty($proveedor_libre))
                $errores[] = 'Debe seleccionar un proveedor o ingresar el nombre manualmente.';
            if (empty(trim($data['fecha'] ?? '')))  $errores[] = 'La fecha es obligatoria.';
            if (empty($items))                       $errores[] = 'Debe agregar al menos un ítem.';
            if ((float)($data['total'] ?? 0) <= 0)  $errores[] = 'El total debe ser mayor a cero.';

            if (empty($errores)) {

                // ── Auto-registro de proveedor ────────────────────────────────
                if (empty($proveedor_id) && $proveedor_libre && $auto_registrar) {
                    $objP   = new Model_cpa0001_proveedores();
                    $prvIdx = uniqid('prv_');
                    $objP->insertar([
                        'idx'              => $prvIdx,
                        'razon_social'     => $proveedor_libre,
                        'habilitado'       => 1,
                        'condicion_pago'   => $data['condicion_pago'] ?? 'Contado',
                        'cuit'             => '',
                        'condicion_fiscal' => $params['prv_condicion_fiscal'] ?? 'Responsable Inscripto',
                        'domicilio'        => '',
                        'localidad'        => '',
                        'provincia'        => '',
                        'cp'               => '',
                        'telefono'         => '',
                        'email'            => '',
                        'contacto'         => '',
                        'observaciones'    => '',
                    ]);
                    $regPrv = $objP->queryIdx($prvIdx);
                    if ($regPrv) {
                        $data['proveedor_id']    = (string)$regPrv['id'];
                        $data['proveedor_label'] = $proveedor_libre;
                    }
                } elseif (empty($proveedor_id)) {
                    $data['proveedor_label'] = $proveedor_libre;
                }

                // ── Auto-registro de artículos en stock ───────────────────────
                if ($auto_registrar_productos) {
                    $dep_id = (int)($params['stk_deposito_default'] ?? 0);
                    if ($dep_id === 0) $dep_id = Model_stk_depositos::getDefault();
                    foreach ($items as &$item) {
                        $label    = trim($item['producto_label'] ?? '');
                        $precio   = (float)($item['precio_unit'] ?? 0);
                        $cantidad = (float)($item['cantidad']    ?? 1);
                        if ($label !== '') {
                            $item['producto_id'] = Model_stk_0001_productos::registrar_desde_compra($label, $precio, $cantidad, $dep_id);
                        }
                    }
                    unset($item);
                }

                $obj   = new Model_cpa0010_facturas();
                $cpIdx = $obj->insertar($data, $items);
                // Solo registrar pago inmediato si la condición es Contado
                if (($data['condicion_pago'] ?? '') === 'Contado') {
                    self::_registrarPagoTesoreria($data, $cpIdx);
                }
                $_SESSION['flash_ok'] = 'Factura registrada correctamente.';
                echo "<script>location.href='cpa_facturas';</script>";
                return;
            }
        }

        $cuentas_tes = Model_tes_cuentas::queryHabilitadas();

        $router->render('compras/cpa_facturas_nueva', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Nueva Factura de Compra',
            'username'    => $userName,
            'nombre'      => $nombre,
            'proveedores' => $proveedores,
            'errores'     => $errores,
            'params'      => $params,
            'cuentas_tes' => $cuentas_tes,
        ]);
    }

    // ── Tesorería — pago de compra ────────────────────────────────────────────

    private static function _registrarPagoTesoreria(array $data, string $cpIdx): void
    {
        $medio = $data['pago_medio'] ?? 'sin_pago';
        if ($medio === 'sin_pago') return;

        $total     = abs((float)($data['total']      ?? 0));
        $fecha     = $data['pago_fecha'] ?: ($data['fecha'] ?? date('Y-m-d'));
        $numero    = trim($data['numero']            ?? '');
        $proveedor = trim($data['proveedor_label']   ?? ($data['proveedor_id'] ?? ''));
        $concepto  = 'Pago Compra' . ($numero ? " #{$numero}" : '') . ($proveedor ? " — {$proveedor}" : '');

        if ($total <= 0) return;

        try {
            if ($medio === 'efectivo') {
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => (int)($data['pago_ef_id']   ?? 0),
                    'tipo'            => 'egreso',
                    'estado'          => 'confirmado',
                    'concepto'        => $concepto,
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'medio'           => 'efectivo',
                    'referencia_tipo' => 'compra',
                    'referencia_idx'  => $cpIdx,
                ]);

            } elseif ($medio === 'transferencia') {
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => (int)($data['pago_bco_id']  ?? 0),
                    'tipo'            => 'egreso',
                    'estado'          => 'confirmado',
                    'concepto'        => $concepto,
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'medio'           => 'transferencia',
                    'referencia_tipo' => 'compra',
                    'referencia_idx'  => $cpIdx,
                ]);

            } elseif ($medio === 'cheque') {
                $fecha_venc = $data['pago_chq_fecha_venc'] ?: $fecha;
                $chq_id = Model_tes_cheques::insertar([
                    'tipo'              => 'emitido',
                    'numero'            => $data['pago_chq_numero']   ?? '',
                    'banco'             => $data['pago_chq_banco']    ?? '',
                    'fecha_emision'     => $fecha,
                    'fecha_vencimiento' => $fecha_venc,
                    'monto'             => $total,
                    'cuenta_id'         => !empty($data['pago_chq_cta_id']) ? (int)$data['pago_chq_cta_id'] : null,
                    'motivo'            => $concepto,
                    'referencia_tipo'   => 'compra',
                    'referencia_idx'    => $cpIdx,
                ]);
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => !empty($data['pago_chq_cta_id']) ? (int)$data['pago_chq_cta_id'] : 0,
                    'tipo'            => 'egreso',
                    'estado'          => 'pendiente',
                    'concepto'        => $concepto . ' — cheque ' . ($data['pago_chq_numero'] ?? ''),
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'fecha_valor'     => $fecha_venc,
                    'medio'           => 'cheque',
                    'referencia_tipo' => 'compra',
                    'referencia_idx'  => $cpIdx,
                    'cheque_id'       => $chq_id,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[AKIM Tesorería pago] ' . $e->getMessage());
        }
    }
}
