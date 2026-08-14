<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_bases;

class Controllers_adm_bases
{
    private static function flash(string $tipo, string $msg): void
    {
        $_SESSION['flash_' . $tipo] = $msg;
    }

    // ── Lista ─────────────────────────────────────────────────────────────────
    public static function adm_lista(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();
        $obj  = new Model_bases();
        $lista = $obj->queryAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_code'])) {
            $_SESSION['adm_bd_code'] = $_POST['edit_code'];
            echo "<script>location.href='adm_bases_edit';</script>"; return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_code'])) {
            try {
                $obj->eliminar($_POST['delete_code']);
                self::flash('ok', 'Base de datos eliminada.');
            } catch (\Throwable $e) {
                self::flash('err', 'No se puede eliminar: está en uso por licencias.');
            }
            echo "<script>location.href='adm_bases';</script>"; return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_code'])) {
            $row = $obj->getByCode($_POST['test_code']);
            if ($row) {
                $r = Model_bases::decryptRow($row);
                try {
                    new \PDO("mysql:host={$r['bd_host']};dbname={$r['bd_name']};charset=utf8mb4",
                             $r['bd_user'], $r['bd_pass'],
                             [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
                    self::flash('ok', "Conexión exitosa a '{$r['bd_name']}' en {$r['bd_host']}.");
                } catch (\Throwable $e) {
                    self::flash('err', 'Falló la conexión: ' . $e->getMessage());
                }
            }
            echo "<script>location.href='adm_bases';</script>"; return;
        }

        $router->render('administrador/adm_bases', [
            'protegida'  => true,
            'page_title' => 'Bases de datos',
            'username'   => $userName,
            'nombre'     => $nombre,
            'lista'      => $lista,
        ], 'layout_admin');
    }

    // ── Crear / Editar ────────────────────────────────────────────────────────
    public static function adm_edit(Router $router): void
    {
        [$userName,,$nombre] = Model_apphelper::getUsuarioAutenticado();
        $obj     = new Model_bases();
        $bd_code = $_SESSION['adm_bd_code'] ?? '';
        $registro_raw = $bd_code ? $obj->getByCode($bd_code) : null;

        // Desencriptar para mostrar en formulario
        $registro = $registro_raw ? Model_bases::decryptRow($registro_raw) : null;
        $errores  = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $a = $_POST;
            if (empty($a['bd_code']))  $errores[] = 'El código de BD es obligatorio.';
            if (empty($a['bd_label'])) $errores[] = 'La etiqueta es obligatoria.';
            if (empty($a['bd_host']))  $errores[] = 'El host es obligatorio.';
            if (empty($a['bd_name']))  $errores[] = 'El nombre de base de datos es obligatorio.';
            if (empty($a['bd_user']))  $errores[] = 'El usuario es obligatorio.';
            if (!$registro && empty($a['bd_pass'])) $errores[] = 'La contraseña es obligatoria.';

            $cambiar_pass = !empty($a['bd_pass']);

            if (empty($errores)) {
                try {
                    if ($registro) {
                        $obj->actualizar($a, $cambiar_pass);
                        self::flash('ok', 'Base de datos actualizada.');
                    } else {
                        $obj->crear($a);
                        self::flash('ok', 'Base de datos creada correctamente.');
                    }
                    unset($_SESSION['adm_bd_code']);
                    echo "<script>location.href='adm_bases';</script>"; return;
                } catch (\Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        $router->render('administrador/adm_bases_edit', [
            'protegida'  => true,
            'page_title' => $registro ? 'Editar base de datos' : 'Nueva base de datos',
            'username'   => $userName,
            'nombre'     => $nombre,
            'registro'   => $registro,
            'errores'    => $errores,
        ], 'layout_admin');
    }
}
