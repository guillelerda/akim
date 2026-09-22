<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Ventas\Model_vta0001_clientes;
use Model\Ventas\Model_vta_ventas;
use Model\Stock\Model_stk_0001_productos;
use Model\Tesoreria\Model_tes_cuentas;
use Model\Tesoreria\Model_tes_tarjetas;
use Model\Tesoreria\Model_tes_movimientos;
use Model\Tesoreria\Model_tes_cheques;
use Model\Arca\Model_arca_service;
use Model\Model_parametros;

/**
 * Wizard de venta — patrón Microsoft Money / JIT.
 *
 * Paso 1: ¿A quién le vendemos?   (cliente — JIT)
 * Paso 2: ¿Qué vendemos?          (productos — JIT, múltiples ítems)
 * Paso 3: ¿Cómo se paga?          (condición, fechas, descuento, cobro en tesorería)
 * Paso 4: Confirmar y emitir      (resumen + guardar)
 */
class Controller_vta_wizard
{
    private const SESSION_KEY = 'akim_wizard_venta';

    // ── STEP 1 — Cliente ──────────────────────────────────────────────────────

    public static function step1(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cliente_id    = (int) ($_POST['cliente_id']    ?? 0);
            $cliente_label = trim($_POST['cliente_label']   ?? '');
            $es_nuevo      = ($_POST['cliente_nuevo'] ?? '0') === '1';
            $modo_factura  = ($_POST['modo_factura']  ?? 'manual') === 'fiscal' ? 'fiscal' : 'manual';

            if ($es_nuevo && $cliente_label !== '') {
                $cliente_id = Model_vta0001_clientes::jit_crear($cliente_label);
            }

            if ($cliente_id > 0 && $cliente_label !== '') {
                $_SESSION[self::SESSION_KEY] = [
                    'cliente_id'    => $cliente_id,
                    'cliente_label' => $cliente_label,
                    'modo_factura'  => $modo_factura,
                    'items'         => [],
                ];
                echo "<script>location.href='vta_wizard_step2';</script>";
                return;
            }
            $errores[] = 'Seleccioná o ingresá un cliente para continuar.';
        }

        // Leer el modo elegido desde el botón del menú (?modo=fiscal|manual)
        $modo_factura = ($_GET['modo'] ?? 'manual') === 'fiscal' ? 'fiscal' : 'manual';

        $router->render('ventas/wizard/step1_cliente', [
            'protegida'    => true,
            'auth'         => true,
            'username'     => $userName,
            'nombre'       => $nombre,
            'perfil'       => $perfil,
            'wizard'       => $_SESSION[self::SESSION_KEY] ?? [],
            'errores'      => $errores ?? [],
            'modo_factura' => $modo_factura,
            'paso'         => 1,
        ], 'layout_wizard');
    }

    // ── STEP 2 — Productos ────────────────────────────────────────────────────

    public static function step2(Router $router): void
    {
        self::_guardSession();
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $wizard = &$_SESSION[self::SESSION_KEY];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'agregar_item') {
                $producto_id    = (int) ($_POST['producto_id']    ?? 0);
                $producto_label = trim($_POST['producto_label']   ?? '');
                $es_nuevo       = ($_POST['producto_nuevo'] ?? '0') === '1';
                $cantidad       = max(0.001, (float) ($_POST['cantidad'] ?? 1));
                $precio         = max(0, (float) ($_POST['precio'] ?? 0));

                if ($es_nuevo && $producto_label !== '') {
                    $producto_id = Model_stk_0001_productos::jit_crear($producto_label, $precio);
                }

                if ($producto_id > 0 && $producto_label !== '') {
                    $wizard['items'][] = [
                        'producto_id'    => $producto_id,
                        'producto_label' => $producto_label,
                        'cantidad'       => $cantidad,
                        'precio_unit'    => $precio,
                        'subtotal'       => round($cantidad * $precio, 2),
                    ];
                }
            }

            if ($accion === 'quitar_item') {
                $idx = (int) ($_POST['item_idx'] ?? -1);
                if (isset($wizard['items'][$idx])) {
                    array_splice($wizard['items'], $idx, 1);
                }
            }

            if ($accion === 'siguiente' && !empty($wizard['items'])) {
                echo "<script>location.href='vta_wizard_step3';</script>";
                return;
            }
        }

        $router->render('ventas/wizard/step2_productos', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
            'wizard'    => $wizard,
            'errores'   => $errores ?? [],
            'paso'      => 2,
        ], 'layout_wizard');
    }

    // ── STEP 3 — Condición de pago + Cobro ───────────────────────────────────

    public static function step3(Router $router): void
    {
        self::_guardSession();
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $wizard = &$_SESSION[self::SESSION_KEY];

        $condiciones  = ['Contado', '15 días', '30 días', '60 días', '90 días'];
        $total_bruto  = array_sum(array_column($wizard['items'] ?? [], 'subtotal'));
        $cuentas_tes  = Model_tes_cuentas::queryHabilitadas();
        $tarjetas_tes = Model_tes_tarjetas::queryHabilitadas();
        $errores      = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $wizard['condicion_pago'] = trim($_POST['condicion_pago'] ?? 'Contado');
            $wizard['descuento']      = min(100, max(0, (float) ($_POST['descuento'] ?? 0)));
            $wizard['observaciones']  = trim($_POST['observaciones'] ?? '');
            $wizard['fecha_venta']    = $_POST['fecha_venta'] ?? date('Y-m-d');

            $medio     = $_POST['cobro_medio'] ?? 'sin_cobro';
            $cuenta_id = 0;
            if ($medio === 'efectivo') {
                $cuenta_id = (int)($_POST['cobro_ef_id']  ?? 0);
            } elseif ($medio === 'transferencia') {
                $cuenta_id = (int)($_POST['cobro_bco_id'] ?? 0);
            } elseif ($medio === 'cheque') {
                $cuenta_id = (int)($_POST['cobro_chq_bco_id'] ?? 0);
            }

            // Validación server-side del cobro
            if ($wizard['condicion_pago'] === 'Contado' && $medio !== 'sin_cobro') {
                if ($medio === 'efectivo'      && $cuenta_id <= 0) $errores[] = 'Seleccioná una caja o cuenta de efectivo.';
                if ($medio === 'transferencia' && $cuenta_id <= 0) $errores[] = 'Seleccioná una cuenta bancaria destino.';
                if ($medio === 'cheque'        && $cuenta_id <= 0) $errores[] = 'Seleccioná la cuenta bancaria para acreditar el cheque.';
                if ($medio === 'tarjeta'       && (int)($_POST['cobro_tarjeta_id'] ?? 0) <= 0) $errores[] = 'Seleccioná la terminal / red de tarjeta.';
            }

            if (empty($errores)) {
                $wizard['cobro'] = [
                    'medio'          => $medio,
                    'cuenta_id'      => $cuenta_id,
                    'tarjeta_id'     => (int)($_POST['cobro_tarjeta_id']    ?? 0),
                    'chq_numero'     => trim($_POST['cobro_chq_numero']      ?? ''),
                    'chq_banco'      => trim($_POST['cobro_chq_banco']       ?? ''),
                    'chq_fecha_venc' => $_POST['cobro_chq_fecha_venc']        ?? '',
                ];
                echo "<script>location.href='vta_wizard_step4';</script>";
                return;
            }
            // Si hay errores: caer al render con $errores
        }

        $router->render('ventas/wizard/step3_condicion', [
            'protegida'    => true,
            'auth'         => true,
            'username'     => $userName,
            'nombre'       => $nombre,
            'perfil'       => $perfil,
            'wizard'       => $wizard,
            'condiciones'  => $condiciones,
            'total_bruto'  => $total_bruto,
            'cuentas_tes'  => $cuentas_tes,
            'tarjetas_tes' => $tarjetas_tes,
            'errores'      => $errores,
            'paso'         => 3,
        ], 'layout_wizard');
    }

    // ── STEP 4 — Confirmar ────────────────────────────────────────────────────

    public static function step4(Router $router): void
    {
        self::_guardSession();
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $wizard = $_SESSION[self::SESSION_KEY];

        $total_bruto = array_sum(array_column($wizard['items'] ?? [], 'subtotal'));
        $descuento   = (float)($wizard['descuento'] ?? 0);
        $total_neto  = round($total_bruto * (1 - $descuento / 100), 2);

        $flash_error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $router->render('ventas/wizard/step4_confirmar', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'wizard'      => $wizard,
            'total_bruto' => $total_bruto,
            'total_neto'  => $total_neto,
            'arca_cfg'    => Model_arca_service::getConfig(),
            'flash_error' => $flash_error,
            'paso'        => 4,
        ], 'layout_wizard');
    }

    // ── CONFIRMAR — Guardar la venta en BD ────────────────────────────────────

    public static function confirmar(Router $router): void
    {
        self::_guardSession();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>location.href='ventas';</script>";
            return;
        }

        $wizard   = $_SESSION[self::SESSION_KEY];
        $userName = $_SESSION['usuario'] ?? '';

        $modo_factura = ($_POST['modo_factura'] ?? 'manual') === 'fiscal' ? 'fiscal' : 'manual';

        // Validar stock si el parámetro lo exige
        if (Model_parametros::get('stk_permite_negativo', '1') === '0') {
            foreach ($wizard['items'] as $item) {
                $pid  = (int)($item['producto_id'] ?? 0);
                $cant = (float)($item['cantidad']   ?? 1);
                if ($pid <= 0) continue;
                try {
                    $r = \Model\ActiveDBC::getDB()->prepare(
                        "SELECT COALESCE(SUM(stock_actual),0) FROM stk_stock WHERE producto_id = ?"
                    );
                    $r->execute([$pid]);
                    $disponible = (float)$r->fetchColumn();
                    if ($disponible < $cant) {
                        $_SESSION['flash_error'] = 'Sin stock suficiente para "' . htmlspecialchars($item['producto_label'], ENT_QUOTES) . '". Disponible: ' . number_format($disponible, 0) . ' u.';
                        echo "<script>location.href='vta_wizard_step4';</script>";
                        return;
                    }
                } catch (\Throwable $e) { /* si falla la consulta, deja pasar */ }
            }
        }

        try {
            $vtaIdx = Model_vta_ventas::insertar($wizard, $userName);

            $stmt = \Model\ActiveDBC::getDB()->prepare(
                "SELECT numero FROM vta_ventas WHERE idx = :idx LIMIT 1"
            );
            $stmt->execute([':idx' => $vtaIdx]);
            $row    = $stmt->fetch();
            $numero = $row ? (int)$row['numero'] : 0;

            // Registrar cobro en tesorería solo si es Contado y marcar cobrada
            $total_bruto = array_sum(array_column($wizard['items'], 'subtotal'));
            $descuento   = (float)($wizard['descuento'] ?? 0);
            $total_neto  = round($total_bruto * (1 - $descuento / 100), 2);
            if (($wizard['condicion_pago'] ?? '') === 'Contado') {
                self::_registrarCobroTesoreria($wizard, $vtaIdx, $numero, $total_neto);
                Model_vta_ventas::marcarCobrada($vtaIdx);
            }

            // ── Emisión AFIP ──────────────────────────────────────────────────
            if ($modo_factura === 'fiscal') {
                try {
                    Model_arca_service::emitir($wizard, $vtaIdx);
                    $_SESSION['flash_ok'] = "Venta #{$numero} registrada con factura electrónica.";
                } catch (\Throwable $e) {
                    error_log('[AKIM ARCA] ' . $e->getMessage());
                    $_SESSION['flash_ok']    = "Venta #{$numero} registrada.";
                    $_SESSION['flash_error'] = 'Error al emitir factura electrónica: ' . $e->getMessage();
                }
            } else {
                $_SESSION['flash_ok'] = "Venta #{$numero} registrada correctamente.";
            }

            unset($_SESSION[self::SESSION_KEY]);

        } catch (\Throwable $e) {
            error_log('[AKIM Wizard] ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al registrar la venta. Reintentá.';
            echo "<script>location.href='vta_wizard_step4';</script>";
            return;
        }

        echo "<script>location.href='ventas';</script>";
    }

    // ── Tesorería — cobro de venta ────────────────────────────────────────────

    public static function _registrarCobroTesoreria(
        array  $wizard,
        string $vtaIdx,
        int    $num,
        float  $total
    ): void {
        $cobro    = $wizard['cobro'] ?? [];
        $medio    = $cobro['medio'] ?? 'sin_cobro';
        if ($medio === 'sin_cobro' || $total <= 0) return;

        $fecha     = $wizard['fecha_venta'] ?? date('Y-m-d');
        $concepto  = 'Cobro Venta #' . $num;
        $cuenta_id = (int)($cobro['cuenta_id'] ?? 0);

        try {
            if ($medio === 'efectivo') {
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => $cuenta_id,
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
                    'cuenta_id'       => $cuenta_id,
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
                $fecha_venc = $cobro['chq_fecha_venc'] ?: $fecha;
                $chq_id = Model_tes_cheques::insertar([
                    'tipo'             => 'recibido',
                    'numero'           => $cobro['chq_numero']  ?? '',
                    'banco'            => $cobro['chq_banco']   ?? '',
                    'fecha_emision'    => $fecha,
                    'fecha_vencimiento'=> $fecha_venc,
                    'monto'            => $total,
                    'cuenta_id'        => $cuenta_id,
                    'motivo'           => $concepto,
                    'referencia_tipo'  => 'venta',
                    'referencia_idx'   => $vtaIdx,
                ]);
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => $cuenta_id,
                    'tipo'            => 'ingreso',
                    'estado'          => 'pendiente',
                    'concepto'        => $concepto . ' — cheque ' . ($cobro['chq_numero'] ?? ''),
                    'monto'           => $total,
                    'fecha'           => $fecha,
                    'fecha_valor'     => $fecha_venc,
                    'medio'           => 'cheque',
                    'referencia_tipo' => 'venta',
                    'referencia_idx'  => $vtaIdx,
                    'cheque_id'       => $chq_id,
                ]);

            } elseif ($medio === 'tarjeta') {
                $tarjeta = Model_tes_tarjetas::getById((int)($cobro['tarjeta_id'] ?? 0));
                if (!$tarjeta || !$tarjeta['cuenta_id']) return;
                $plazo      = (int)($tarjeta['plazo_dias']    ?? 0);
                $desc_pct   = (float)($tarjeta['descuento_pct'] ?? 0);
                $monto_net  = round($total * (1 - $desc_pct / 100), 2);
                $fecha_val  = date('Y-m-d', strtotime($fecha . ' +' . $plazo . ' days'));
                Model_tes_movimientos::registrar([
                    'cuenta_id'       => (int)$tarjeta['cuenta_id'],
                    'tipo'            => 'ingreso',
                    'estado'          => 'confirmado',
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
            error_log('[AKIM Tesorería cobro] ' . $e->getMessage());
        }
    }

    // ── JIT endpoint — búsqueda de productos (AJAX GET /jit_productos?q=) ──────

    public static function jit_productos(Router $router): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!($_SESSION['login'] ?? false)) {
            http_response_code(401);
            echo '[]';
            return;
        }
        $termino = trim($_GET['q'] ?? '');
        if (strlen($termino) < 1) {
            echo '[]';
            return;
        }
        echo json_encode(
            Model_stk_0001_productos::jit_buscar($termino),
            JSON_UNESCAPED_UNICODE
        );
    }

    // ── JIT endpoint — búsqueda de clientes (AJAX GET /jit_clientes?q=) ─────────

    public static function jit_clientes(Router $router): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!($_SESSION['login'] ?? false)) {
            http_response_code(401);
            echo '[]';
            return;
        }
        $termino = trim($_GET['q'] ?? '');
        if (strlen($termino) < 2) {
            echo '[]';
            return;
        }
        echo json_encode(
            Model_vta0001_clientes::jit_buscar($termino),
            JSON_UNESCAPED_UNICODE
        );
    }

    // ── Guard ─────────────────────────────────────────────────────────────────

    private static function _guardSession(): void
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            echo "<script>location.href='vta_wizard_step1';</script>";
            exit;
        }
    }
}
