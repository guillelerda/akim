<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;
use Model\Stock\Model_stk_depositos;
use Model\Stock\Model_stk_movimientos;

class Controller_stk_transferencia
{
    public static function lista(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $productos = (new Model_stk_0001_productos())->queryAll();
        $depositos = Model_stk_depositos::queryHabilitados();
        $errores   = [];
        $post_prev = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto_id  = (int)($_POST['producto_id']        ?? 0);
            $dep_origen   = (int)($_POST['deposito_origen_id'] ?? 0);
            $dep_destino  = (int)($_POST['deposito_destino_id'] ?? 0);
            $cantidad     = (float)($_POST['cantidad'] ?? 0);
            $motivo       = trim($_POST['motivo'] ?? '');
            $post_prev    = $_POST;

            if (!$producto_id)               $errores[] = 'Seleccioná un producto.';
            if (!$dep_origen)                $errores[] = 'Seleccioná el depósito de origen.';
            if (!$dep_destino)               $errores[] = 'Seleccioná el depósito de destino.';
            if ($dep_origen === $dep_destino && $dep_origen) $errores[] = 'El depósito de origen y destino no pueden ser el mismo.';
            if ($cantidad <= 0)              $errores[] = 'La cantidad debe ser mayor a cero.';

            if (empty($errores)) {
                $disp = Model_stk_movimientos::getStockEnDeposito($producto_id, $dep_origen);
                if ($cantidad > $disp) {
                    $errores[] = 'Stock insuficiente en el depósito origen. Disponible: ' . number_format($disp, 3, ',', '.');
                }
            }

            if (empty($errores)) {
                Model_stk_movimientos::registrar(
                    'transferencia', $producto_id, $dep_origen, $cantidad,
                    0, $motivo, $dep_destino, (int)($mi_usuario ?? 0)
                );
                $_SESSION['flash_ok'] = 'Transferencia registrada correctamente.';
                echo "<script>location.href='stk_transferencia';</script>"; return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('stock/stk_transferencia', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Transferencia entre Depósitos',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'productos'   => $productos,
            'depositos'   => $depositos,
            'errores'     => $errores,
            'post_prev'   => $post_prev,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }
}
