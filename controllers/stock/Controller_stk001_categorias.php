<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\Model_apphelper;

class Controller_stk001_categorias
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $lista = [];

        $router->render('stock/stk001_categorias', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'lista'     => $lista,
        ]);
    }
}
