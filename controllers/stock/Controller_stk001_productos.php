<?php

namespace Controllers\Stock;

use MVC\Router;
use Model\ActiveDBC;
use Model\Model_apphelper;
use Model\Stock\Model_stk_0001_productos;
use Model\Stock\Model_stk001_categorias;

class Controller_stk001_productos
{
    public static function lista(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj = new Model_stk_0001_productos();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $idx    = $_POST['idx']    ?? '';

            if ($accion === 'editar' && $idx !== '') {
                $_SESSION['mi_idx'] = $idx;
                echo "<script>location.href='stk_productos_edit';</script>"; return;
            }
            if ($accion === 'nuevo') {
                $_SESSION['mi_idx'] = '';
                echo "<script>location.href='stk_productos_edit';</script>"; return;
            }
            if ($accion === 'toggle' && $idx !== '') {
                $obj->toggle($idx);
            }
            if ($accion === 'eliminar' && $idx !== '') {
                $obj->eliminar($idx);
                $_SESSION['flash_ok'] = 'Producto eliminado.';
            }
            echo "<script>location.href='stk_productos';</script>"; return;
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $lista = $obj->queryAll();

        // Categorías presentes en la lista (para el filtro)
        $cats_map = [];
        foreach ($lista as $p) {
            $cid = $p['categoria_id'] ?? null;
            if ($cid && !isset($cats_map[$cid])) {
                $cats_map[$cid] = $p['categoria_nombre'] ?? '—';
            }
        }
        asort($cats_map);

        $router->render('stock/stk001_productos', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Productos',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $lista,
            'cats_map'    => $cats_map,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    public static function edit(Router $router): void
    {
        if (!ActiveDBC::isConnected()) {
            header('Location: seleccionarEmpresa');
            exit;
        }

        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $idx        = $_SESSION['mi_idx'] ?? '';
        $obj        = new Model_stk_0001_productos();
        $producto   = $idx !== '' ? $obj->queryIdx($idx) : null;
        $es_nuevo   = ($producto === null || $producto === false);
        $errores    = [];
        $categorias = Model_stk001_categorias::queryHabilitadas();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;

            if (empty(trim($data['nombre'] ?? ''))) $errores[] = 'El nombre del producto es obligatorio.';

            if (empty($errores)) {
                if ($es_nuevo) {
                    $idx_final = $obj->insertar($data);
                    $_SESSION['flash_ok'] = 'Producto creado correctamente.';
                } else {
                    $idx_final = $idx;
                    $data['idx'] = $idx;
                    $obj->actualizar($data);
                    $_SESSION['flash_ok'] = 'Producto actualizado correctamente.';
                }

                // ── Imagen del producto ────────────────────────────────────────
                if (($_FILES['producto_imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                    && !empty($_FILES['producto_imagen']['tmp_name'])) {
                    $ext   = strtolower(pathinfo($_FILES['producto_imagen']['name'], PATHINFO_EXTENSION));
                    $allow = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                    if (in_array($ext, $allow, true)) {
                        $init   = Model_apphelper::inicialize();
                        $root   = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
                        $imgDir = $root . '/' . REPO_PATH . $init['licencia'] . '/productos/';
                        if (!is_dir($imgDir)) mkdir($imgDir, 0775, true);
                        foreach (glob($imgDir . 'prd_' . $idx_final . '.*') ?: [] as $old) { @unlink($old); }
                        $dest = $imgDir . 'prd_' . $idx_final . '.' . $ext;
                        if (move_uploaded_file($_FILES['producto_imagen']['tmp_name'], $dest)) {
                            $obj->setImagen($idx_final, REPO_PATH . $init['licencia'] . '/productos/prd_' . $idx_final . '.' . $ext);
                        }
                    }
                }

                echo "<script>location.href='stk_productos';</script>"; return;
            }
        }

        $router->render('stock/stk001_productos_edit', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => $es_nuevo ? 'Nuevo Producto' : 'Editar Producto',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'producto'    => $producto,
            'es_nuevo'    => $es_nuevo,
            'categorias'  => $categorias,
            'errores'     => $errores,
        ]);
    }
}
