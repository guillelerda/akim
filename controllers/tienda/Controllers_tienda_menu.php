<?php

namespace Controllers\Tienda;

use MVC\Router;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;
use Model\Ventas\Model_vta_ventas;
use Model\Ventas\Model_vta0001_clientes;
use Model\Tesoreria\Model_tes_cuentas;
use Model\Tesoreria\Model_tes_tarjetas;
use Controllers\Ventas\Controller_vta_wizard;
use Model\Model_parametros;

class Controllers_tienda_menu
{
    public static function menu(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $_SESSION['moduloActivo'] = 'TIENDA';

        $productos    = [];
        $cuentas_tes  = [];
        $tarjetas_tes = [];
        try {
            $productos    = Model_stk_0001_productos::queryParaPos();
            $cuentas_tes  = Model_tes_cuentas::queryHabilitadas();
            $tarjetas_tes = Model_tes_tarjetas::queryHabilitadas();
        } catch (\Throwable $e) { /* tablas vacías */ }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('tienda/menu_tienda', [
            'protegida'    => true,
            'auth'         => true,
            'page_title'   => 'Punto de Venta',
            'username'     => $userName,
            'nombre'       => $nombre,
            'perfil'       => $perfil,
            'productos'    => $productos,
            'cuentas_tes'  => $cuentas_tes,
            'tarjetas_tes' => $tarjetas_tes,
            'flash_ok'     => $flash_ok,
            'flash_error'  => $flash_error,
        ]);
    }

    public static function vender(Router $router): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['accion'] ?? '') !== 'vender') {
            echo "<script>location.href='tienda';</script>";
            return;
        }

        $userName = $_SESSION['usuario'] ?? '';

        $itemsRaw  = json_decode($_POST['items_json'] ?? '[]', true) ?: [];
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $medio     = $_POST['medio']     ?? 'efectivo';
        $cuentaId  = (int)($_POST['cuenta_id']  ?? 0);
        $tarjetaId = (int)($_POST['tarjeta_id'] ?? 0);
        $fecha     = $_POST['fecha'] ?: date('Y-m-d');
        $desc      = (float)($_POST['descuento'] ?? 0);
        $obs       = trim($_POST['observaciones'] ?? '');

        if (empty($itemsRaw)) {
            $_SESSION['flash_error'] = 'El carrito está vacío.';
            echo "<script>location.href='tienda';</script>";
            return;
        }

        // Validar stock si el parámetro lo exige
        if (Model_parametros::get('stk_permite_negativo', '1') === '0') {
            foreach ($itemsRaw as $it) {
                $pid  = (int)($it['id'] ?? 0);
                $cant = (float)($it['cant'] ?? 1);
                if ($pid <= 0) continue;
                try {
                    $r = \Model\ActiveDBC::getDB()->prepare(
                        "SELECT COALESCE(SUM(stock_actual),0) AS total FROM stk_stock WHERE producto_id = ?"
                    );
                    $r->execute([$pid]);
                    $disponible = (float)$r->fetchColumn();
                    if ($disponible < $cant) {
                        $_SESSION['flash_error'] = 'Sin stock suficiente para "' . htmlspecialchars($it['nombre'] ?? 'producto', ENT_QUOTES) . '". Disponible: ' . number_format($disponible, 0) . ' u.';
                        echo "<script>location.href='tienda';</script>";
                        return;
                    }
                } catch (\Throwable $e) { /* si falla la consulta, deja pasar */ }
            }
        }

        // Normalizar ítems al formato que espera Model_vta_ventas::insertar()
        $items = [];
        foreach ($itemsRaw as $it) {
            $cant    = (float)($it['cant']   ?? 1);
            $precio  = (float)($it['precio'] ?? 0);
            $items[] = [
                'producto_id'    => (int)($it['id']     ?? 0),
                'producto_label' => $it['nombre']        ?? '',
                'cantidad'       => $cant,
                'precio_unit'    => $precio,
                'subtotal'       => round($cant * $precio, 2),
            ];
        }

        $wizard = [
            'cliente_id'      => $clienteId ?: null,
            'fecha_venta'     => $fecha,
            'condicion_pago'  => 'Contado',
            'descuento'       => $desc,
            'observaciones'   => $obs,
            'items'           => $items,
            'cobro'           => [
                'medio'      => $medio,
                'cuenta_id'  => $cuentaId,
                'tarjeta_id' => $tarjetaId,
            ],
        ];

        try {
            $vtaIdx = Model_vta_ventas::insertar($wizard, $userName);

            $stmt = \Model\ActiveDBC::getDB()->prepare(
                "SELECT numero FROM vta_ventas WHERE idx = :idx LIMIT 1"
            );
            $stmt->execute([':idx' => $vtaIdx]);
            $numero = (int)($stmt->fetch()['numero'] ?? 0);

            $totalBruto = array_sum(array_column($items, 'subtotal'));
            $totalNeto  = round($totalBruto * (1 - $desc / 100), 2);

            Controller_vta_wizard::_registrarCobroTesoreria($wizard, $vtaIdx, $numero, $totalNeto);
            Model_vta_ventas::marcarCobrada($vtaIdx);

            $_SESSION['flash_ok'] = "Venta #{$numero} registrada. Total: $ " . number_format($totalNeto, 2, ',', '.');

        } catch (\Throwable $e) {
            error_log('[AKIM POS] ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al registrar la venta. Reintentá.';
        }

        echo "<script>location.href='tienda';</script>";
    }

    public static function catalogo(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $productos = [];
        try { $productos = Model_stk_0001_productos::queryParaPos(); } catch (\Throwable $e) {}

        $router->render('tienda/catalogo', [
            'protegida' => true,
            'auth'      => true,
            'page_title'=> 'Catálogo',
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
            'productos' => $productos,
        ]);
    }
}
