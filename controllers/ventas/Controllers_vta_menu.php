<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Ventas\Model_vta_ventas;
use Model\Tesoreria\Model_tes_cuentas;
use Model\Tesoreria\Model_tes_tarjetas;
use Model\Tesoreria\Model_tes_movimientos;
use Model\Tesoreria\Model_tes_cheques;
use Model\Arca\Model_arca_service;
use Model\Model_parametros;

class Controllers_vta_menu
{
    public static function menu(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        Model_apphelper::requerirAccesoModulo('VTA');
        $init    = Model_apphelper::inicialize();
        [$em_nombre] = Model_apphelper::getLicenciaAutenticada($init['licencia']);
        $_SESSION['moduloActivo'] = 'VTA';

        // ── Acciones POST ──────────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $idx    = trim($_POST['idx'] ?? '');

            if ($idx) {
                if ($accion === 'anular') {
                    $vAnular = Model_vta_ventas::getByIdx($idx);
                    if (!empty($vAnular['cae'])) {
                        $_SESSION['flash_error'] = 'La factura electrónica Nº ' . ($vAnular['nro_afip'] ?? '') . ' no puede anularse directamente. Debe emitirse una Nota de Crédito electrónica referenciando este comprobante.';
                    } else {
                        Model_vta_ventas::anular($idx);
                        $_SESSION['flash_ok'] = 'Venta anulada.';
                    }

                } elseif ($accion === 'cobrar') {
                    $venta = Model_vta_ventas::getByIdx($idx);
                    if ($venta && $venta['estado'] === 'emitida') {
                        $errVal = self::_validarCobro($_POST);
                        if ($errVal) {
                            $_SESSION['flash_error'] = $errVal;
                        } else {
                            self::_registrarCobro($_POST, $venta);
                            Model_vta_ventas::marcarCobrada($idx);
                            $_SESSION['flash_ok'] = 'Cobro de venta #' . (int)$venta['numero'] . ' registrado.';
                        }
                    }
                }
            }
            echo "<script>location.href='ventas';</script>";
            return;
        }

        // ── GET — renderizar lista ─────────────────────────────────────────────
        $ventas      = [];
        $stats_mes   = ['cant' => 0, 'total' => 0];
        $cuentas_tes = [];
        $tarjetas_tes= [];

        $arca_habilitado = false;
        $arca_pv         = 1;
        try {
            $ventas          = Model_vta_ventas::listar(100);
            $stats_mes       = Model_vta_ventas::statsMes();
            $cuentas_tes     = Model_tes_cuentas::queryHabilitadas();
            $tarjetas_tes    = Model_tes_tarjetas::queryHabilitadas();
            $arca_cfg        = Model_arca_service::getConfig();
            $arca_habilitado = $arca_cfg['habilitado'];
            $arca_pv         = $arca_cfg['pv'];
        } catch (\Throwable $e) { /* tablas no creadas aún */ }

        $flash_ok    = $_SESSION['flash_ok']    ?? '';
        $flash_error = $_SESSION['flash_error'] ?? '';
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

        $router->render('ventas/menu_ventas', [
            'protegida'    => true,
            'auth'         => true,
            'page_title'   => 'Ventas',
            'username'     => $userName,
            'nombre'       => $nombre,
            'perfil'       => $perfil,
            'em_nombre'    => $em_nombre ?? 'AKIM',
            'ventas'       => $ventas,
            'stats_mes'    => $stats_mes,
            'cuentas_tes'  => $cuentas_tes,
            'tarjetas_tes' => $tarjetas_tes,
            'flash_ok'        => $flash_ok,
            'flash_error'     => $flash_error,
            'arca_habilitado' => $arca_habilitado,
            'arca_pv'         => $arca_pv,
        ]);
    }

    // ── Validar campos obligatorios según medio ───────────────────────────────

    private static function _validarCobro(array $post): string
    {
        $medio = $post['cobro_medio'] ?? '';
        if ($medio === 'efectivo'      && empty($post['cobro_ef_id']))      return 'Seleccioná una caja o cuenta de efectivo.';
        if ($medio === 'transferencia' && empty($post['cobro_bco_id']))     return 'Seleccioná una cuenta bancaria destino.';
        if ($medio === 'cheque'        && empty($post['cobro_chq_bco_id'])) return 'Seleccioná la cuenta bancaria para acreditar el cheque.';
        if ($medio === 'tarjeta'       && empty($post['cobro_tarjeta_id'])) return 'Seleccioná la terminal / red de tarjeta.';
        return '';
    }

    // ── Registrar cobro en tesorería ──────────────────────────────────────────

    private static function _registrarCobro(array $post, array $venta): void
    {
        $medio     = $post['cobro_medio'] ?? 'sin_cobro';
        $total     = abs((float)$venta['total']);
        $fecha     = $post['cobro_fecha'] ?: ($venta['fecha'] ?? date('Y-m-d'));
        $num       = (int)$venta['numero'];
        $concepto  = 'Cobro Venta #' . $num;
        $vtaIdx    = $venta['idx'];

        if ($medio === 'sin_cobro' || $total <= 0) return;

        try {
            if ($medio === 'efectivo') {
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => (int)($post['cobro_ef_id']  ?? 0),
                    'tipo'            => 'ingreso',
                    'estado'          => 'confirmado',
                    'concepto'        => $concepto,
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'medio'           => 'efectivo',
                    'referencia_tipo' => 'venta',
                    'referencia_idx'  => $vtaIdx,
                ]);

            } elseif ($medio === 'transferencia') {
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => (int)($post['cobro_bco_id'] ?? 0),
                    'tipo'            => 'ingreso',
                    'estado'          => 'confirmado',
                    'concepto'        => $concepto,
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'medio'           => 'transferencia',
                    'referencia_tipo' => 'venta',
                    'referencia_idx'  => $vtaIdx,
                ]);

            } elseif ($medio === 'cheque') {
                $fecha_venc = $post['cobro_chq_fecha_venc'] ?: $fecha;
                $chq_id = Model_tes_cheques::insertar([
                    'tipo'              => 'recibido',
                    'numero'            => $post['cobro_chq_numero']   ?? '',
                    'banco'             => $post['cobro_chq_banco']    ?? '',
                    'fecha_emision'     => $fecha,
                    'fecha_vencimiento' => $fecha_venc,
                    'monto'             => $total,
                    'cuenta_id'         => !empty($post['cobro_chq_bco_id']) ? (int)$post['cobro_chq_bco_id'] : null,
                    'motivo'            => $concepto,
                    'referencia_tipo'   => 'venta',
                    'referencia_idx'    => $vtaIdx,
                ]);
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => !empty($post['cobro_chq_bco_id']) ? (int)$post['cobro_chq_bco_id'] : 0,
                    'tipo'            => 'ingreso',
                    'estado'          => 'pendiente',
                    'concepto'        => $concepto . ' — cheque ' . ($post['cobro_chq_numero'] ?? ''),
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'fecha_valor'     => $fecha_venc,
                    'medio'           => 'cheque',
                    'referencia_tipo' => 'venta',
                    'referencia_idx'  => $vtaIdx,
                    'cheque_id'       => $chq_id,
                ]);

            } elseif ($medio === 'tarjeta') {
                $tarjeta   = Model_tes_tarjetas::getById((int)($post['cobro_tarjeta_id'] ?? 0));
                if (!$tarjeta || !$tarjeta['cuenta_id']) return;
                $plazo     = (int)($tarjeta['plazo_dias']    ?? 0);
                $desc_pct  = (float)($tarjeta['descuento_pct'] ?? 0);
                $monto_net = round($total * (1 - $desc_pct / 100), 2);
                $fecha_val = date('Y-m-d', strtotime($fecha . ' +' . $plazo . ' days'));
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => (int)$tarjeta['cuenta_id'],
                    'tipo'            => 'ingreso',
                    'estado'          => 'pendiente',
                    'concepto'        => $concepto . ' — ' . ($tarjeta['nombre'] ?? 'Tarjeta'),
                    'monto'           => $monto_net,
                    'fecha'           => $fecha,
                    'fecha_valor'     => $fecha_val,
                    'medio'           => 'tarjeta',
                    'referencia_tipo' => 'venta',
                    'referencia_idx'  => $vtaIdx,
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[AKIM Tesorería cobro-CC] ' . $e->getMessage());
        }
    }
}
