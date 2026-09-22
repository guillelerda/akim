<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Ventas\Model_vta0001_clientes;
use Model\Ventas\Model_vta_ventas;
use Model\Tesoreria\Model_tes_cuentas;
use Model\Tesoreria\Model_tes_tarjetas;

class Controller_vta0001_clientes
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $obj = new Model_vta0001_clientes();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'editar') {
                $_SESSION['mi_idx'] = $_POST['idx'] ?? '';
                echo "<script>location.href='vta_clientes_edit';</script>";
                return;
            }

            if ($accion === 'nuevo') {
                $_SESSION['mi_idx'] = '';
                echo "<script>location.href='vta_clientes_edit';</script>";
                return;
            }

            if ($accion === 'toggle') {
                $id  = (int)($_POST['id'] ?? 0);
                $hab = (int)($_POST['habilitado'] ?? 0);
                if ($id > 0) {
                    $obj->toggleHabilitado($id, $hab === 1 ? 0 : 1);
                }
                echo "<script>location.href='vta_clientes';</script>";
                return;
            }
        }

        $lista       = $obj->queryAll();
        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('ventas/vta0001_clientes', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Clientes',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $lista,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    public static function cuenta(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $clienteId = (int)($_GET['id'] ?? 0);
        if ($clienteId <= 0) { header('Location: vta_clientes'); exit; }

        $obj     = new Model_vta0001_clientes();
        $cliente = $obj->queryById($clienteId);
        if (!$cliente) { header('Location: vta_clientes'); exit; }

        $ventas       = [];
        $cuentas_tes  = [];
        $tarjetas_tes = [];
        try {
            $ventas       = Model_vta_ventas::getByClienteId($clienteId);
            $cuentas_tes  = Model_tes_cuentas::queryHabilitadas();
            $tarjetas_tes = Model_tes_tarjetas::queryHabilitadas();
        } catch (\Throwable $e) {}

        $total_facturado = 0.0;
        $total_cobrado   = 0.0;
        $total_pendiente = 0.0;
        foreach ($ventas as $v) {
            if ($v['estado'] === 'anulada') continue;
            $total_facturado += (float)$v['total'];
            if ($v['estado'] === 'cobrada') $total_cobrado   += (float)$v['total'];
            if ($v['estado'] === 'emitida') $total_pendiente += (float)$v['total'];
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? ''; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? ''; unset($_SESSION['flash_error']);

        $router->render('ventas/vta_cliente_cuenta', [
            'protegida'       => true,
            'auth'            => true,
            'page_title'      => 'Cuenta de ' . ($cliente['razon_social'] ?? 'Cliente'),
            'username'        => $userName,
            'nombre'          => $nombre,
            'perfil'          => $perfil,
            'cliente'         => $cliente,
            'ventas'          => $ventas,
            'cuentas_tes'     => $cuentas_tes,
            'tarjetas_tes'    => $tarjetas_tes,
            'total_facturado' => $total_facturado,
            'total_cobrado'   => $total_cobrado,
            'total_pendiente' => $total_pendiente,
            'flash_ok'        => $flash_ok,
            'flash_error'     => $flash_error,
        ]);
    }

    public static function edit(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $idx     = $_SESSION['mi_idx'] ?? '';
        $errores = [];
        $cliente = null;
        $es_nuevo = $idx === '';

        if (!$es_nuevo) {
            $obj     = new Model_vta0001_clientes();
            $cliente = $obj->queryIdx($idx);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obj = new Model_vta0001_clientes();

            // Validaciones mínimas
            if (empty(trim($_POST['razon_social'] ?? ''))) {
                $errores[] = 'La razón social es obligatoria.';
            }

            if (empty($errores)) {
                if ($es_nuevo) {
                    $obj->insertar($_POST);
                    $_SESSION['flash_ok'] = 'Cliente creado correctamente.';
                } else {
                    $obj->actualizar($_POST);
                    $_SESSION['flash_ok'] = 'Cliente actualizado correctamente.';
                }
                echo "<script>location.href='vta_clientes';</script>";
                return;
            }

            // Si hay errores, repoblar con lo que vino del form
            $cliente = $_POST;
        }

        $router->render('ventas/vta0001_clientes_edit', [
            'protegida'  => true,
            'auth'       => true,
            'page_title' => $es_nuevo ? 'Nuevo Cliente' : 'Editar Cliente',
            'username'   => $userName,
            'nombre'     => $nombre,
            'perfil'     => $perfil,
            'cliente'    => $cliente,
            'es_nuevo'   => $es_nuevo,
            'errores'    => $errores,
        ]);
    }
}
