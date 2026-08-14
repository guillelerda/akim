<?php

namespace Controllers;

use MVC\Router;
use Model\Model_apphelper;
use Model\ActiveDB;
use Model\ActiveDBC;

class Controllers_menu
{
    public static function menu(Router $router): void
    {
        if (empty($_SESSION['data_licencia']['lic_bd_name'])) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $init    = Model_apphelper::inicialize();
        $licencia = $init['licencia'];
        [$em_nombre, $em_slogan] = Model_apphelper::getLicenciaAutenticada($licencia);

        $modulos = self::getModulosDisponibles($perfil);
        $stats   = self::getDashboardStats();

        $router->render('menu/menu', [
            'protegida'  => true,
            'auth'       => true,
            'page_title' => 'Dashboard',
            'username'   => $userName,
            'nombre'     => $nombre,
            'perfil'     => $perfil,
            'foto'       => $mi_usuario->foto ?? '',
            'em_nombre'  => $em_nombre ?? 'AKIM',
            'modulos'    => $modulos,
            'stats'      => $stats,
        ]);
    }

    public static function menu_modulos(Router $router): void
    {
        self::menu($router);
    }

    private static function getModulosDisponibles(string $perfil): array
    {
        $todos = [
            ['codigo' => 'VTA',    'nombre' => 'Ventas',        'icono' => 'fa-receipt',       'url' => 'ventas'],
            ['codigo' => 'CPA',    'nombre' => 'Compras',       'icono' => 'fa-cart-shopping',  'url' => 'compras'],
            ['codigo' => 'STK',    'nombre' => 'Stock',         'icono' => 'fa-boxes-stacked',  'url' => 'stock'],
            ['codigo' => 'TIENDA', 'nombre' => 'Tienda',        'icono' => 'fa-store',          'url' => 'tienda'],
            ['codigo' => 'ADM',    'nombre' => 'Administración','icono' => 'fa-gear',           'url' => 'admin'],
        ];

        if ($perfil !== 'admin') {
            return array_values(array_filter($todos, fn($m) => $m['codigo'] !== 'ADM'));
        }
        return $todos;
    }

    private static function getDashboardStats(): array
    {
        $stats = [
            'ventas_hoy_cant'  => 0,
            'ventas_hoy_total' => 0.0,
            'clientes'         => 0,
            'productos'        => 0,
            'stock_bajo'       => 0,
            'ventas_recientes' => [],
            'stock_alertas'    => [],
        ];

        try {
            $db = ActiveDBC::getDB();

            // Ventas del día
            $r = $db->query(
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS tot
                 FROM vta_ventas WHERE DATE(created_at) = CURDATE()"
            );
            if ($r) {
                $row = $r->fetch();
                $stats['ventas_hoy_cant']  = (int)$row['cnt'];
                $stats['ventas_hoy_total'] = (float)$row['tot'];
            }
        } catch (\Throwable $e) { /* tabla inexistente */ }

        try {
            $db = ActiveDBC::getDB();
            $r  = $db->query("SELECT COUNT(*) AS cnt FROM vta_clientes WHERE habilitado = 1");
            if ($r) { $stats['clientes'] = (int)$r->fetch()['cnt']; }
        } catch (\Throwable $e) { }

        try {
            $db = ActiveDBC::getDB();
            $r  = $db->query("SELECT COUNT(*) AS cnt FROM stk_productos WHERE habilitado = 1");
            if ($r) { $stats['productos'] = (int)$r->fetch()['cnt']; }
        } catch (\Throwable $e) { }

        try {
            $db = ActiveDBC::getDB();
            $r  = $db->query(
                "SELECT COUNT(*) AS cnt FROM stk_productos
                 WHERE stock_actual IS NOT NULL AND stock_minimo IS NOT NULL
                   AND stock_actual <= stock_minimo AND habilitado = 1"
            );
            if ($r) { $stats['stock_bajo'] = (int)$r->fetch()['cnt']; }
        } catch (\Throwable $e) { }

        try {
            $db = ActiveDBC::getDB();
            $r  = $db->query(
                "SELECT v.idx, v.total, v.fecha,
                        COALESCE(c.razon_social,'Consumidor final') AS razon_social
                 FROM vta_ventas v
                 LEFT JOIN vta_clientes c ON v.cliente_idx = c.idx
                 ORDER BY v.created_at DESC LIMIT 6"
            );
            if ($r) { $stats['ventas_recientes'] = $r->fetchAll(); }
        } catch (\Throwable $e) { }

        try {
            $db = ActiveDBC::getDB();
            $r  = $db->query(
                "SELECT nombre, stock_actual, stock_minimo
                 FROM stk_productos
                 WHERE stock_actual IS NOT NULL AND stock_minimo IS NOT NULL
                   AND stock_actual <= stock_minimo AND habilitado = 1
                 ORDER BY stock_actual ASC LIMIT 8"
            );
            if ($r) { $stats['stock_alertas'] = $r->fetchAll(); }
        } catch (\Throwable $e) { }

        return $stats;
    }
}
