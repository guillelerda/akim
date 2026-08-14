<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_usuarios;

class Controllers_adm_usuarios
{
    public static function adm_inicio(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $router->render('administrador/adm_inicio', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
        ], 'layout_admin');
    }

    public static function adm_lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj   = new Model_usuarios();
        $lista = $obj->queryAll_activos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['mi_idx'] = $_POST['username'] ?? '';
            echo "<script>location.href='adm_usuarios_edit';</script>";
            return;
        }

        $router->render('administrador/adm_usuarios', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'lista'     => $lista,
        ], 'layout_admin');
    }

    public static function adm_edit(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $router->render('administrador/adm_usuarios_edit', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
        ], 'layout_admin');
    }
}
