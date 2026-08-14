<?php

namespace Controllers\Compras;

use MVC\Router;
use Model\Model_apphelper;
use Model\Compras\Model_cpa0010_facturas;
use Model\Compras\Model_cpa0001_proveedores;

class Controller_cpa0010_facturas
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $obj       = new Model_cpa0010_facturas();
        $lista     = $obj->queryAll();
        $stats_mes = $obj->getStatsMes();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $idx    = $_POST['idx']    ?? '';
            if ($idx && in_array($accion, ['anular', 'pagada'])) {
                if ($accion === 'anular') $obj->anular($idx);
                if ($accion === 'pagada') $obj->marcarPagada($idx);
            }
            echo "<script>location.href='cpa_facturas';</script>";
            return;
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('compras/cpa0010_facturas', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Facturas de Compra',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $lista,
            'stats_mes'   => $stats_mes,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    public static function nueva(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $errores     = [];
        $proveedores = (new Model_cpa0001_proveedores())->queryAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data  = $_POST;
            $items = json_decode($data['items_json'] ?? '[]', true) ?: [];

            if (empty(trim($data['fecha'] ?? '')))         $errores[] = 'La fecha es obligatoria.';
            if (empty($items))                              $errores[] = 'Debe agregar al menos un ítem.';
            if ((float)($data['total'] ?? 0) <= 0)         $errores[] = 'El total debe ser mayor a cero.';

            if (empty($errores)) {
                $obj = new Model_cpa0010_facturas();
                $obj->insertar($data, $items);
                $_SESSION['flash_ok'] = 'Factura registrada correctamente.';
                echo "<script>location.href='cpa_facturas';</script>";
                return;
            }
        }

        $router->render('compras/cpa_facturas_nueva', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Nueva Factura de Compra',
            'username'    => $userName,
            'nombre'      => $nombre,
            'proveedores' => $proveedores,
            'errores'     => $errores,
        ]);
    }
}
