<?php

namespace Controllers\Tienda;

use MVC\Router;
use Model\Model_apphelper;

class Controllers_tienda_menu
{
    public static function menu(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $_SESSION['moduloActivo'] = 'TIENDA';

        $router->render('tienda/menu_tienda', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
        ]);
    }

    public static function catalogo(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        // TODO: cargar productos habilitados en tienda
        $productos = [];

        $router->render('tienda/catalogo', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'productos' => $productos,
        ]);
    }
}
