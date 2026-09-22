<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk_depositos;

class Controller_stk_depositos
{
    public static function lista(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj     = new Model_stk_depositos();
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'nuevo') {
                $nombre_dep = trim($_POST['nombre'] ?? '');
                if ($nombre_dep === '') {
                    $errores[] = 'El nombre del depósito es obligatorio.';
                } else {
                    $obj->insertar([
                        'nombre'      => $nombre_dep,
                        'descripcion' => trim($_POST['descripcion'] ?? ''),
                        'es_default'  => !empty($_POST['es_default']),
                    ]);
                    $_SESSION['flash_ok'] = 'Depósito creado correctamente.';
                    echo "<script>location.href='stk_depositos';</script>"; return;
                }
            }

            if ($accion === 'setDefault' && !empty($_POST['id'])) {
                $obj->setDefault((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Depósito por defecto actualizado.';
                echo "<script>location.href='stk_depositos';</script>"; return;
            }

            if ($accion === 'toggle' && !empty($_POST['id'])) {
                $obj->toggle((int)$_POST['id']);
                echo "<script>location.href='stk_depositos';</script>"; return;
            }

            if ($accion === 'eliminar' && !empty($_POST['id'])) {
                $obj->eliminar((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Depósito eliminado.';
                echo "<script>location.href='stk_depositos';</script>"; return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('stock/stk_depositos', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Depósitos',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $obj->queryAll(),
            'errores'     => $errores,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }
}
