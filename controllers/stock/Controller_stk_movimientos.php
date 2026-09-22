<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;
use Model\Stock\Model_stk_depositos;
use Model\Stock\Model_stk_movimientos;

class Controller_stk_movimientos
{
    public static function lista(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $filtros = [
            'tipo'         => $_GET['tipo']         ?? '',
            'producto_id'  => (int)($_GET['producto_id']  ?? 0),
            'deposito_id'  => (int)($_GET['deposito_id']  ?? 0),
            'fecha_desde'  => $_GET['fecha_desde']  ?? '',
            'fecha_hasta'  => $_GET['fecha_hasta']  ?? '',
        ];

        $movimientos = Model_stk_movimientos::queryFiltrado($filtros);
        $productos   = (new Model_stk_0001_productos())->queryAll();
        $depositos   = Model_stk_depositos::queryHabilitados();

        $router->render('stock/stk_movimientos', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Historial de Movimientos',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'movimientos' => $movimientos,
            'productos'   => $productos,
            'depositos'   => $depositos,
            'filtros'     => $filtros,
        ]);
    }
}
