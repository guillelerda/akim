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

        // Cancelar creación de BD
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_crear'])) {
            unset($_SESSION['bd_crear_code']);
            echo "<script>location.href='adm_bases';</script>"; return;
        }

        // Paso 1: usuario hizo clic en "Crear BD" → guardar en sesión y mostrar form de credenciales admin
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_bd_code'])) {
            $_SESSION['bd_crear_code'] = $_POST['create_bd_code'];
            echo "<script>location.href='adm_bases';</script>"; return;
        }

        // Paso 2: usuario envió credenciales root → ejecutar CREATE DATABASE
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exec_crear_bd'])) {
            $bd_code    = trim($_POST['bd_code_crear']   ?? '');
            $admin_user = trim($_POST['mysql_admin_user'] ?? 'root');
            $admin_pass = $_POST['mysql_admin_pass'] ?? '';
            $row = $obj->getByCode($bd_code);

            if ($row) {
                $r    = Model_bases::decryptRow($row);
                $host = $r['bd_host'];
                $name = $r['bd_name'];
                $user = $r['bd_user'];
                $pass = $r['bd_pass'];

                $sql_db    = "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
                $sql_user  = "CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY '{$pass}';";
                $sql_grant = "GRANT ALL PRIVILEGES ON `{$name}`.* TO '{$user}'@'%';";
                $sql_flush = "FLUSH PRIVILEGES;";

                $_SESSION['bd_sql_result'] = [
                    'code' => $bd_code,
                    'sql'  => implode("\n", [$sql_db, $sql_user, $sql_grant, $sql_flush]),
                ];
                unset($_SESSION['bd_crear_code']);

                try {
                    $pdo = new \PDO(
                        "mysql:host={$host};charset=utf8mb4",
                        $admin_user, $admin_pass,
                        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                    );
                    $pdo->exec($sql_db);
                    $pdo->exec($sql_user);
                    $pdo->exec($sql_grant);
                    $pdo->exec($sql_flush);
                    self::flash('ok', "Base de datos «{$name}» y usuario «{$user}» creados exitosamente en {$host}.");
                    $_SESSION['bd_sql_result']['status'] = 'ok';
                } catch (\Throwable $e) {
                    self::flash('err', "Error al crear: " . $e->getMessage() . " — Ejecutá el SQL manualmente en phpMyAdmin.");
                    $_SESSION['bd_sql_result']['status'] = 'err';
                }
            }
            echo "<script>location.href='adm_bases';</script>"; return;
        }

        $router->render('administrador/adm_bases', [
            'protegida'    => true,
            'page_title'   => 'Bases de datos',
            'username'     => $userName,
            'nombre'       => $nombre,
            'lista'        => $lista,
            'bd_crear_code'=> $_SESSION['bd_crear_code'] ?? '',
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
