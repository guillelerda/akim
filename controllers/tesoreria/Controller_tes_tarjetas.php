<?php

namespace Controllers\Tesoreria;

use MVC\Router;
use Model\Model_apphelper;
use Model\Tesoreria\Model_tes_tarjetas;
use Model\Tesoreria\Model_tes_cuentas;

class Controller_tes_tarjetas
{
    public static function lista(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj     = new Model_tes_tarjetas();
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'nuevo') {
                $nom = trim($_POST['nombre'] ?? '');
                if ($nom === '') {
                    $errores[] = 'El nombre de la terminal es obligatorio.';
                } else {
                    $obj->insertar($_POST);
                    $_SESSION['flash_ok'] = 'Terminal configurada correctamente.';
                    echo "<script>location.href='tes_tarjetas';</script>"; return;
                }
            }

            if ($accion === 'toggle' && !empty($_POST['id'])) {
                $obj->toggle((int)$_POST['id']);
                echo "<script>location.href='tes_tarjetas';</script>"; return;
            }

            if ($accion === 'editar' && !empty($_POST['id'])) {
                $nom = trim($_POST['nombre'] ?? '');
                if ($nom === '') {
                    $_SESSION['flash_error'] = 'El nombre es obligatorio.';
                } else {
                    Model_tes_tarjetas::actualizar((int)$_POST['id'], $_POST);
                    $_SESSION['flash_ok'] = 'Terminal actualizada correctamente.';
                }
                echo "<script>location.href='tes_tarjetas';</script>"; return;
            }

            if ($accion === 'favorito' && !empty($_POST['id'])) {
                Model_tes_tarjetas::setSingleFavorito((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Tarjeta marcada como favorita.';
                echo "<script>location.href='tes_tarjetas';</script>"; return;
            }

            if ($accion === 'eliminar' && !empty($_POST['id'])) {
                $obj->eliminar((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Terminal eliminada.';
                echo "<script>location.href='tes_tarjetas';</script>"; return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $cuentas_banco = array_values(array_filter(
            Model_tes_cuentas::queryHabilitadas(),
            fn($c) => $c['tipo'] === 'banco'
        ));

        $router->render('tesoreria/tes_tarjetas', [
            'protegida'     => true,
            'auth'          => true,
            'username'      => $userName,
            'nombre'        => $nombre,
            'perfil'        => $perfil,
            'lista'         => $obj->queryAll(),
            'cuentas_banco' => $cuentas_banco,
            'errores'       => $errores,
            'flash_ok'      => $flash_ok,
            'flash_error'   => $flash_error,
        ]);
    }
}
