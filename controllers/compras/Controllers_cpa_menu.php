<?php

namespace Controllers\Compras;

use MVC\Router;
use Model\Model_apphelper;
use Model\Compras\Model_cpa0010_facturas;
use Model\Compras\Model_cpa0001_proveedores;

class Controllers_cpa_menu
{
    public static function menu(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $_SESSION['moduloActivo'] = 'CPA';
        $_SESSION['layoutActivo'] = 'layout';

        $stats_mes   = ['cant' => 0, 'total' => 0];
        $pendientes  = 0;
        $proveedores = 0;

        try {
            $objF       = new Model_cpa0010_facturas();
            $stats_mes  = $objF->getStatsMes();
            $pendientes = $objF->countPendientes();
            $objP       = new Model_cpa0001_proveedores();
            $proveedores= $objP->countActivos();
        } catch (\Throwable $e) { /* BD no conectada aún */ }

        $router->render('compras/menu_compras', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Compras',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'stats_mes'   => $stats_mes,
            'pendientes'  => $pendientes,
            'proveedores' => $proveedores,
        ]);
    }
}
