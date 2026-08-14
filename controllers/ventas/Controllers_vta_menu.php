<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Ventas\Model_vta_ventas;

class Controllers_vta_menu
{
    public static function menu(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $init    = Model_apphelper::inicialize();
        [$em_nombre] = Model_apphelper::getLicenciaAutenticada($init['licencia']);
        $_SESSION['moduloActivo'] = 'VTA';

        $ventas    = [];
        $stats_mes = ['cant' => 0, 'total' => 0];

        try {
            $ventas    = Model_vta_ventas::listar(100);
            $stats_mes = Model_vta_ventas::statsMes();
        } catch (\Throwable $e) { /* tablas no existen aún */ }

        // Flash messages
        $flash_ok    = $_SESSION['flash_ok']    ?? '';
        $flash_error = $_SESSION['flash_error'] ?? '';
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);

        $router->render('ventas/menu_ventas', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Ventas',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'em_nombre'   => $em_nombre ?? 'AKIM',
            'ventas'      => $ventas,
            'stats_mes'   => $stats_mes,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }
}
