<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;

class Controller_stk001_productos
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj   = new Model_stk_0001_productos();
        $lista = $obj->queryAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['mi_idx'] = $_POST['idx'] ?? '';
            echo "<script>location.href='stk_productos_edit';</script>";
            return;
        }

        $router->render('stock/stk001_productos', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'lista'     => $lista,
        ]);
    }

    public static function edit(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $router->render('stock/stk001_productos_edit', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
        ]);
    }
}
