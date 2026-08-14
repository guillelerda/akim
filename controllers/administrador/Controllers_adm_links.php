<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_links;
use Model\Administrador\Model_licencias;
use Model\Administrador\Model_empresas;

class Controllers_adm_links
{
    private static function flash(string $tipo, string $msg): void
    {
        $_SESSION['flash_' . $tipo] = $msg;
    }

    public static function adm_lista(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();

        $obj_links = new Model_links();
        $obj_lic   = new Model_licencias();
        $obj_em    = new Model_empresas();

        $lic_code = $_SESSION['adm_lic_links'] ?? '';

        // Acciones POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (isset($_POST['asignar_username'])) {
                try {
                    $obj_links->crear([
                        'username'  => $_POST['asignar_username'],
                        'licencia'  => $lic_code,
                        'em_code'   => $_POST['asignar_em_code'],
                        'habilitada'=> 1,
                    ]);
                    self::flash('ok', 'Usuario asignado a la licencia.');
                } catch (\Throwable $e) {
                    self::flash('err', 'Error al asignar: ' . $e->getMessage());
                }
                echo "<script>location.href='adm_links';</script>"; return;
            }

            if (isset($_POST['toggle_id'])) {
                $obj_links->toggleHabilitada((int)$_POST['toggle_id']);
                echo "<script>location.href='adm_links';</script>"; return;
            }

            if (isset($_POST['delete_id'])) {
                $obj_links->eliminar((int)$_POST['delete_id']);
                self::flash('ok', 'Asignación eliminada.');
                echo "<script>location.href='adm_links';</script>"; return;
            }

            if (isset($_POST['cambiar_lic'])) {
                $_SESSION['adm_lic_links'] = $_POST['cambiar_lic'];
                echo "<script>location.href='adm_links';</script>"; return;
            }
        }

        $licencia    = $lic_code ? $obj_lic->getByCode($lic_code) : null;
        $asignaciones = $lic_code ? $obj_links->getByLicencia($lic_code) : [];
        $usuarios    = $obj_links->usuariosDisponibles();
        $todas_lic   = $obj_lic->queryAll();
        $empresas    = $obj_em->queryAll();

        $router->render('administrador/adm_links', [
            'protegida'   => true,
            'page_title'  => 'Asignaciones usuario/licencia',
            'username'    => $userName,
            'nombre'      => $nombre,
            'lic_code'    => $lic_code,
            'licencia'    => $licencia,
            'asignaciones'=> $asignaciones,
            'usuarios'    => $usuarios,
            'todas_lic'   => $todas_lic,
            'empresas'    => $empresas,
        ], 'layout_admin');
    }
}
