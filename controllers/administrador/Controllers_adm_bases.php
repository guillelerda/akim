<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_bases;
use Model\ActiveDBC;
use Model\Model_apphelperBD_parametros;
use Model\Ventas\Model_apphelperBD_ventas;
use Model\Stock\Model_apphelperBD_stock;
use Model\Compras\Model_apphelperBD_compras;
use Model\Tesoreria\Model_apphelperBD_tes;
use Model\Tesoreria\Model_tes_cajas;
use Model\Arca\Model_apphelperBD_arca;

class Controllers_adm_bases
{
    private static function flash(string $tipo, string $msg): void
    {
        $_SESSION['flash_' . $tipo] = $msg;
    }

    // ── Crea/verifica el 100% del esquema de la BD cliente ──────────────────────
    // Devuelve [errores_por_modulo[], total_tablas]
    private static function verificarEsquemaCompleto(\PDO $pdo): array
    {
        ActiveDBC::setDB($pdo);

        // Forzar re-chequeo aunque este mismo admin ya haya inicializado
        // el esquema de OTRA licencia en esta sesión.
        $cacheKeys = [
            'akim_cfg_schema_ok_'  . CFG_SCHEMA_VER,
            'akim_vta_schema_ok_'  . VTA_SCHEMA_VER,
            'akim_stk_schema_ok_'  . STK_SCHEMA_VER,
            'akim_cpa_schema_ok_'  . CPA_SCHEMA_VER,
            'akim_tes_schema_ok_'  . TES_SCHEMA_VER,
            'akim_arca_schema_ok_' . ARCA_SCHEMA_VER,
        ];
        foreach ($cacheKeys as $k) unset($_SESSION[$k]);

        $modulos = [
            'Parámetros' => fn() => Model_apphelperBD_parametros::inicialize(),
            'Ventas'     => fn() => Model_apphelperBD_ventas::inicialize(),
            'Stock'      => fn() => Model_apphelperBD_stock::inicialize(),
            'Compras'    => fn() => Model_apphelperBD_compras::inicialize(),
            'Tesorería'  => fn() => Model_apphelperBD_tes::inicialize(),
            'ARCA'       => fn() => Model_apphelperBD_arca::inicialize(),
            'Caja'       => fn() => Model_tes_cajas::getHoy(), // dispara ensureTable() de tes_cajas/tes_caja_retiros
        ];

        $errores = [];
        foreach ($modulos as $nombre => $fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                $errores[] = "{$nombre}: " . $e->getMessage();
            }
        }

        // No dejar el flag "inicializado" pisando el de la licencia real activa
        foreach ($cacheKeys as $k) unset($_SESSION[$k]);

        $total = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()"
        )->fetchColumn();

        return [$errores, $total];
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
                    $pdo = new \PDO("mysql:host={$r['bd_host']};dbname={$r['bd_name']};charset=utf8mb4",
                             $r['bd_user'], $r['bd_pass'],
                             [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

                    [$erroresEsquema, $totalTablas] = self::verificarEsquemaCompleto($pdo);

                    if ($erroresEsquema) {
                        self::flash('err',
                            "Conexión OK a '{$r['bd_name']}', pero fallaron módulos al crear/verificar tablas: "
                            . implode(' | ', $erroresEsquema));
                    } else {
                        self::flash('ok',
                            "Conexión exitosa a '{$r['bd_name']}' en {$r['bd_host']}. "
                            . "Esquema completo verificado/creado: {$totalTablas} tablas (6 módulos OK).");
                    }
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

        // "+ Nueva base" siempre debe limpiar cualquier bd_code que haya
        // quedado en sesión de una edición anterior no completada.
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['nuevo'])) {
            unset($_SESSION['adm_bd_code']);
        }

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
