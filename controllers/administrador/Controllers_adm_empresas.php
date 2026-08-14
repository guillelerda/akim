<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_empresas;

class Controllers_adm_empresas
{
    private static function flash(string $tipo, string $msg): void
    {
        $_SESSION['flash_' . $tipo] = $msg;
    }

    // ── Lista ─────────────────────────────────────────────────────────────────
    public static function adm_lista(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();
        $obj  = new Model_empresas();
        $lista = $obj->queryAll();

        // Toggle enable/disable
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_code'])) {
            $obj->toggleHabilitada($_POST['toggle_code']);
            self::flash('ok', 'Estado actualizado.');
            echo "<script>location.href='adm_empresas';</script>"; return;
        }

        // Abrir edición
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_code'])) {
            $_SESSION['adm_em_code'] = $_POST['edit_code'];
            echo "<script>location.href='adm_empresas_edit';</script>"; return;
        }

        $router->render('administrador/adm_empresas', [
            'protegida'  => true,
            'page_title' => 'Empresas',
            'username'   => $userName,
            'nombre'     => $nombre,
            'lista'      => $lista,
        ], 'layout_admin');
    }

    // ── Crear / Editar ────────────────────────────────────────────────────────
    public static function adm_edit(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();
        $obj      = new Model_empresas();
        $em_code  = $_SESSION['adm_em_code'] ?? '';
        $registro = $em_code ? $obj->getRegistro_codigo($em_code) : null;
        $errores  = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $a = $_POST;

            if (empty($a['em_code']))   $errores[] = 'El código es obligatorio.';
            if (empty($a['em_nombre'])) $errores[] = 'El nombre es obligatorio.';

            if (empty($errores)) {
                try {
                    if ($registro) {
                        $obj->actualizar($a);
                        self::flash('ok', 'Empresa actualizada correctamente.');
                    } else {
                        $obj->crear($a);
                        self::flash('ok', 'Empresa creada correctamente.');
                    }
                    unset($_SESSION['adm_em_code']);
                    echo "<script>location.href='adm_empresas';</script>"; return;
                } catch (\Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        $router->render('administrador/adm_empresas_edit', [
            'protegida'  => true,
            'page_title' => $registro ? 'Editar empresa' : 'Nueva empresa',
            'username'   => $userName,
            'nombre'     => $nombre,
            'registro'   => $registro,
            'errores'    => $errores,
        ], 'layout_admin');
    }
}
