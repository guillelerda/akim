<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_licencias;
use Model\Administrador\Model_empresas;
use Model\Administrador\Model_bases;

class Controllers_adm_licencias
{
    private static function flash(string $tipo, string $msg): void
    {
        $_SESSION['flash_' . $tipo] = $msg;
    }

    // ── Lista ─────────────────────────────────────────────────────────────────
    public static function adm_lista(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();
        $obj  = new Model_licencias();
        $lista = $obj->queryAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_code'])) {
            $obj->toggleHabilitada($_POST['toggle_code']);
            self::flash('ok', 'Estado actualizado.');
            echo "<script>location.href='adm_licencias';</script>"; return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_code'])) {
            $_SESSION['adm_lic_code'] = $_POST['edit_code'];
            echo "<script>location.href='adm_licencias_edit';</script>"; return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['links_code'])) {
            $_SESSION['adm_lic_links'] = $_POST['links_code'];
            echo "<script>location.href='adm_links';</script>"; return;
        }

        $router->render('administrador/adm_licencias', [
            'protegida'  => true,
            'page_title' => 'Licencias',
            'username'   => $userName,
            'nombre'     => $nombre,
            'lista'      => $lista,
        ], 'layout_admin');
    }

    // ── Crear / Editar ────────────────────────────────────────────────────────
    public static function adm_edit(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();

        $obj_lic  = new Model_licencias();
        $obj_em   = new Model_empresas();
        $obj_bd   = new Model_bases();

        $lic_code  = $_SESSION['adm_lic_code'] ?? '';
        $registro  = $lic_code ? $obj_lic->getByCode($lic_code) : null;
        $empresas  = $obj_em->queryAll();
        $bases     = $obj_bd->queryAll();
        $errores   = [];

        $modulos_disponibles = self::modulosDisponibles();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $a = $_POST;

            if (empty($a['lic_code']))         $errores[] = 'El código de licencia es obligatorio.';
            if (empty($a['lic_empresa_cod']))   $errores[] = 'La empresa es obligatoria.';

            // Módulos seleccionados → JSON
            $mods_sel = array_filter(array_keys($modulos_disponibles), fn($k) => !empty($a['mod_' . $k]));
            $a['lic_modulos'] = empty($mods_sel) ? null : json_encode(array_values($mods_sel));

            if (empty($errores)) {
                try {
                    if ($registro) {
                        $obj_lic->actualizar($a);
                        self::flash('ok', 'Licencia actualizada.');
                    } else {
                        $obj_lic->crear($a);
                        self::flash('ok', 'Licencia creada correctamente.');
                    }
                    unset($_SESSION['adm_lic_code']);
                    echo "<script>location.href='adm_licencias';</script>"; return;
                } catch (\Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        $router->render('administrador/adm_licencias_edit', [
            'protegida'          => true,
            'page_title'         => $registro ? 'Editar licencia' : 'Nueva licencia',
            'username'           => $userName,
            'nombre'             => $nombre,
            'registro'           => $registro,
            'empresas'           => $empresas,
            'bases'              => $bases,
            'modulos_disponibles'=> $modulos_disponibles,
            'errores'            => $errores,
        ], 'layout_admin');
    }

    public static function modulosDisponibles(): array
    {
        return [
            'VTA'    => ['nombre' => 'Ventas',        'icono' => '🧾'],
            'CPA'    => ['nombre' => 'Compras',       'icono' => '🛒'],
            'STK'    => ['nombre' => 'Stock',         'icono' => '📦'],
            'TIENDA' => ['nombre' => 'Tienda online', 'icono' => '🏪'],
            'ARCA'   => ['nombre' => 'ARCA / AFIP',   'icono' => '📑'],
            'PAGOS'  => ['nombre' => 'Pagos/Cobros',  'icono' => '💳'],
        ];
    }
}
