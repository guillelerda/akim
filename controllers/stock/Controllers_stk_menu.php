<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;
use Model\Stock\Model_stk001_categorias;

class Controllers_stk_menu
{
    public static function menu(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        Model_apphelper::requerirAccesoModulo('STK');
        $_SESSION['moduloActivo'] = 'STK';

        $stats = Model_stk_0001_productos::getStats();
        $stats['categorias'] = Model_stk001_categorias::countHabilitadas();

        $router->render('stock/menu_stock', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
            'stats'     => $stats,
        ]);
    }
}
