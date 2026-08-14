<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Ventas\Model_vta0001_clientes;

class Controller_vta0001_clientes
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $obj_clientes = new Model_vta0001_clientes();
        $lista        = $obj_clientes->queryAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['mi_idx'] = $_POST['idx'] ?? '';
            echo "<script>location.href='vta_clientes_edit';</script>";
            return;
        }

        $router->render('ventas/vta0001_clientes', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
            'lista'     => $lista,
        ]);
    }

    public static function edit(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $idx      = $_SESSION['mi_idx'] ?? '';
        $errores  = [];
        $cliente  = null;

        if ($idx) {
            $obj     = new Model_vta0001_clientes();
            $cliente = $obj->queryIdx($idx);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obj = new Model_vta0001_clientes();
            $obj->actualizar($_POST);
            echo "<script>location.href='vta_clientes';</script>";
            return;
        }

        $router->render('ventas/vta0001_clientes_edit', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'cliente'   => $cliente,
            'errores'   => $errores,
        ]);
    }
}
