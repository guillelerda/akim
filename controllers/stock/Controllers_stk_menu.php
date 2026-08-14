<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\Model_apphelper;

class Controllers_stk_menu
{
    public static function menu(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $_SESSION['moduloActivo'] = 'STK';

        $router->render('stock/menu_stock', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
        ]);
    }
}
