<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Ventas\Model_vta0001_clientes;
use Model\Ventas\Model_vta_ventas;
use Model\Stock\Model_stk_0001_productos;

/**
 * Wizard de venta — patrón Microsoft Money / JIT.
 *
 * Paso 1: ¿A quién le vendemos?   (cliente — JIT)
 * Paso 2: ¿Qué vendemos?          (productos — JIT, múltiples ítems)
 * Paso 3: ¿Cómo se paga?          (condición, fechas, descuento)
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

            if ($es_nuevo && $cliente_label !== '') {
                $cliente_id = Model_vta0001_clientes::jit_crear($cliente_label);
            }

            if ($cliente_id > 0 && $cliente_label !== '') {
                $_SESSION[self::SESSION_KEY] = [
                    'cliente_id'    => $cliente_id,
                    'cliente_label' => $cliente_label,
                    'items'         => [],
                ];
                echo "<script>location.href='vta_wizard_step2';</script>";
                return;
            }
            $errores[] = 'Seleccioná o ingresá un cliente para continuar.';
        }

        $router->render('ventas/wizard/step1_cliente', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
            'wizard'    => $_SESSION[self::SESSION_KEY] ?? [],
            'errores'   => $errores ?? [],
            'paso'      => 1,
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

    // ── STEP 3 — Condición de pago ────────────────────────────────────────────

    public static function step3(Router $router): void
    {
        self::_guardSession();
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $wizard = &$_SESSION[self::SESSION_KEY];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $wizard['condicion_pago'] = trim($_POST['condicion_pago'] ?? 'Contado');
            $wizard['descuento']      = min(100, max(0, (float) ($_POST['descuento'] ?? 0)));
            $wizard['observaciones']  = trim($_POST['observaciones'] ?? '');
            $wizard['fecha_venta']    = $_POST['fecha_venta'] ?? date('Y-m-d');

            echo "<script>location.href='vta_wizard_step4';</script>";
            return;
        }

        $condiciones = ['Contado', '15 días', '30 días', '60 días', '90 días'];
        $total_bruto = array_sum(array_column($wizard['items'] ?? [], 'subtotal'));

        $router->render('ventas/wizard/step3_condicion', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'wizard'      => $wizard,
            'condiciones' => $condiciones,
            'total_bruto' => $total_bruto,
            'paso'        => 3,
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

        $router->render('ventas/wizard/step4_confirmar', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'wizard'      => $wizard,
            'total_bruto' => $total_bruto,
            'total_neto'  => $total_neto,
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

        try {
            $vtaIdx = Model_vta_ventas::insertar($wizard, $userName);

            $stmt = \Model\ActiveDBC::getDB()->prepare(
                "SELECT numero FROM vta_ventas WHERE idx = :idx LIMIT 1"
            );
            $stmt->execute([':idx' => $vtaIdx]);
            $row    = $stmt->fetch();
            $numero = $row ? (int)$row['numero'] : 0;

            unset($_SESSION[self::SESSION_KEY]);
            $_SESSION['flash_ok'] = "Venta #{$numero} registrada correctamente.";

        } catch (\Throwable $e) {
            error_log('[AKIM Wizard] ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al registrar la venta. Reintentá.';
            echo "<script>location.href='vta_wizard_step4';</script>";
            return;
        }

        echo "<script>location.href='ventas';</script>";
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
