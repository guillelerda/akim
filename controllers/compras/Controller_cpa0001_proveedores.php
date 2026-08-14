<?php

namespace Controllers\Compras;

use MVC\Router;
use Model\Model_apphelper;
use Model\Compras\Model_cpa0001_proveedores;

class Controller_cpa0001_proveedores
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $obj   = new Model_cpa0001_proveedores();
        $lista = $obj->queryAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $idx    = $_POST['idx']    ?? '';

            if ($accion === 'editar' && $idx) {
                $_SESSION['mi_idx'] = $idx;
                echo "<script>location.href='cpa_proveedores_edit';</script>";
                return;
            }
            if ($accion === 'toggle' && $idx) {
                $obj->toggleHabilitado($idx);
                echo "<script>location.href='cpa_proveedores';</script>";
                return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('compras/cpa0001_proveedores', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Proveedores',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $lista,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    public static function edit(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $idx       = $_SESSION['mi_idx'] ?? '';
        $es_nuevo  = !$idx;
        $errores   = [];
        $proveedor = null;

        if (!$es_nuevo) {
            $obj       = new Model_cpa0001_proveedores();
            $proveedor = $obj->queryIdx($idx);
            if (!$proveedor) {
                header('Location: cpa_proveedores'); exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;

            if (empty(trim($data['razon_social'] ?? ''))) {
                $errores[] = 'La razón social es obligatoria.';
            }

            if (empty($errores)) {
                $obj = new Model_cpa0001_proveedores();
                if ($es_nuevo) {
                    $data['idx']       = uniqid('prv_');
                    $data['habilitado'] = 1;
                    $obj->insertar($data);
                    $_SESSION['flash_ok'] = 'Proveedor creado correctamente.';
                } else {
                    $data['idx'] = $idx;
                    $obj->actualizar($data);
                    $_SESSION['flash_ok'] = 'Proveedor actualizado.';
                }
                unset($_SESSION['mi_idx']);
                echo "<script>location.href='cpa_proveedores';</script>";
                return;
            }
        }

        $router->render('compras/cpa0001_proveedores_edit', [
            'protegida'  => true,
            'auth'       => true,
            'page_title' => $es_nuevo ? 'Nuevo Proveedor' : 'Editar Proveedor',
            'username'   => $userName,
            'nombre'     => $nombre,
            'proveedor'  => $proveedor,
            'es_nuevo'   => $es_nuevo,
            'errores'    => $errores,
        ]);
    }
}
