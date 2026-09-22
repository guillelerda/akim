<?php

namespace Controllers\Tesoreria;

use MVC\Router;
use Model\Model_apphelper;
use Model\Tesoreria\Model_tes_cuentas;

class Controller_tes_cuentas
{
    public static function lista(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj     = new Model_tes_cuentas();
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'nuevo') {
                $nom = trim($_POST['nombre'] ?? '');
                if ($nom === '') {
                    $errores[] = 'El nombre de la cuenta es obligatorio.';
                } else {
                    $obj->insertar($_POST);
                    $_SESSION['flash_ok'] = 'Cuenta creada correctamente.';
                    echo "<script>location.href='tes_cuentas';</script>"; return;
                }
            }

            if ($accion === 'toggle' && !empty($_POST['id'])) {
                $obj->toggle((int)$_POST['id']);
                echo "<script>location.href='tes_cuentas';</script>"; return;
            }

            if ($accion === 'favorito' && !empty($_POST['id'])) {
                Model_tes_cuentas::setSingleFavorito((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Cuenta marcada como favorita.';
                echo "<script>location.href='tes_cuentas';</script>"; return;
            }

            if ($accion === 'eliminar' && !empty($_POST['id'])) {
                $obj->eliminar((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Cuenta eliminada.';
                echo "<script>location.href='tes_cuentas';</script>"; return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('tesoreria/tes_cuentas', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => Model_tes_cuentas::getSaldos(),
            'errores'     => $errores,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }
}
