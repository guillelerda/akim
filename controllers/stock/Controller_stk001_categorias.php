<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk001_categorias;

class Controller_stk001_categorias
{
    public static function lista(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj     = new Model_stk001_categorias();
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'nueva') {
                $nombre_cat = trim($_POST['nombre'] ?? '');
                if ($nombre_cat === '') {
                    $errores[] = 'El nombre de la categoría es obligatorio.';
                } else {
                    $obj->insertar($nombre_cat);
                    $_SESSION['flash_ok'] = 'Categoría creada correctamente.';
                    echo "<script>location.href='stk_categorias';</script>"; return;
                }
            }

            if ($accion === 'toggle' && !empty($_POST['id'])) {
                $obj->toggle((int)$_POST['id']);
                echo "<script>location.href='stk_categorias';</script>"; return;
            }

            if ($accion === 'eliminar' && !empty($_POST['id'])) {
                $obj->eliminar((int)$_POST['id']);
                $_SESSION['flash_ok'] = 'Categoría eliminada.';
                echo "<script>location.href='stk_categorias';</script>"; return;
            }
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('stock/stk001_categorias', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Categorías de Stock',
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
