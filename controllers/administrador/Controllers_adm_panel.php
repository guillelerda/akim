<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Administrador\Model_usuarios;
use Model\Administrador\Model_empresas;
use Model\Administrador\Model_licencias;

class Controllers_adm_panel
{
    public static function panel(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

        // Verificar sesión admin completa (login + loginADMIN)
        if (!($_SESSION['login'] ?? false) || !($_SESSION['loginADMIN'] ?? false)) {
            echo "<script>location.href='loginAdmin';</script>";
            return;
        }

        $counts = self::getCounts();

        $router->render('administrador/admin_panel', [
            'page_title' => 'Dashboard',
            'protegida'  => true,
            'counts'     => $counts,
        ], 'layout_admin');
    }

    private static function getCounts(): array
    {
        $counts = ['empresas' => 0, 'licencias' => 0, 'usuarios' => 0, 'bases' => 0, 'modulos' => 5];

        try {
            $obj = new Model_usuarios();
            $lista = $obj->queryAll();
            $counts['usuarios'] = is_array($lista) ? count($lista) : 0;
        } catch (\Throwable $e) {}

        try {
            $obj = new Model_empresas();
            $lista = $obj->queryAll();
            $counts['empresas'] = is_array($lista) ? count($lista) : 0;
        } catch (\Throwable $e) {}

        try {
            $obj = new Model_licencias();
            $lista = $obj->queryAll();
            $counts['licencias'] = is_array($lista) ? count($lista) : 0;
        } catch (\Throwable $e) {}

        return $counts;
    }
}
