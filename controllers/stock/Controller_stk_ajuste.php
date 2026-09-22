<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;
use Model\Stock\Model_stk_depositos;
use Model\Stock\Model_stk_movimientos;

class Controller_stk_ajuste
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
            $producto_id = (int)($_POST['producto_id'] ?? 0);
            $deposito_id = (int)($_POST['deposito_id'] ?? 0);
            $tipo        = in_array($_POST['tipo'] ?? '', ['ajuste_pos', 'ajuste_neg']) ? $_POST['tipo'] : '';
            $cantidad    = (float)($_POST['cantidad'] ?? 0);
            $motivo      = trim($_POST['motivo'] ?? '');
            $post_prev   = $_POST;

            if (!$producto_id) $errores[] = 'Seleccioná un producto.';
            if (!$deposito_id) $errores[] = 'Seleccioná un depósito.';
            if (!$tipo)        $errores[] = 'Seleccioná el tipo de ajuste.';
            if ($cantidad <= 0) $errores[] = 'La cantidad debe ser mayor a cero.';

            if ($tipo === 'ajuste_neg' && empty($errores)) {
                $disp = Model_stk_movimientos::getStockEnDeposito($producto_id, $deposito_id);
                if ($cantidad > $disp) {
                    $errores[] = 'Stock insuficiente en ese depósito. Disponible: ' . number_format($disp, 3, ',', '.');
                }
            }

            if (empty($errores)) {
                Model_stk_movimientos::registrar($tipo, $producto_id, $deposito_id, $cantidad, 0, $motivo, 0, (int)($mi_usuario ?? 0));
                $_SESSION['flash_ok'] = 'Ajuste de inventario registrado correctamente.';
                echo "<script>location.href='stk_ajuste';</script>"; return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('stock/stk_ajuste', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Ajuste de Inventario',
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
