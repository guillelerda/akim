<?php

namespace Controllers;

use MVC\Router;
use Model\ActiveDBC;
use Model\Administrador\Model_licencias;
use Model\Ventas\Model_apphelperBD_ventas;
use Model\Stock\Model_apphelperBD_stock;

class Controllers_selector
{
    public static function seleccionar(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();

        // Requiere login de usuario normal
        if (empty($_SESSION['login']) || empty($_SESSION['usuario'])) {
            header('Location: login');
            exit;
        }

        // Si ya tiene empresa seleccionada, ir directo al menú
        if (!empty($_SESSION['data_licencia']['lic_bd_name']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: menu');
            exit;
        }

        $username = $_SESSION['usuario'];
        $obj      = new Model_licencias();
        $licencias = $obj->getLicenciasUsuario($username);

        // Si no tiene registros en sys_links, usar todas las activas (primer admin)
        if (empty($licencias)) {
            $licencias = $obj->getLicenciasActivas();
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lic_code = trim($_POST['lic_code'] ?? '');

            // Verificar que el code elegido está en la lista permitida
            $elegida = null;
            foreach ($licencias as $lic) {
                if ($lic['lic_code'] === $lic_code) { $elegida = $lic; break; }
            }

            if (!$elegida) {
                $errores[] = 'Licencia no válida.';
            } elseif (empty($elegida['bd_name'])) {
                $errores[] = 'Esta empresa no tiene base de datos configurada. Configurá las credenciales en el panel de administración (sys_bases).';
            } else {
                // Guardar credenciales encriptadas en sesión
                $_SESSION['data_licencia'] = [
                    'lic_code'    => $elegida['lic_code'],
                    'lic_host'    => $elegida['bd_host'],
                    'lic_bd_name' => $elegida['bd_name'],
                    'lic_bd_user' => $elegida['bd_user'],
                    'lic_bd_pass' => $elegida['bd_pass'],
                ];
                $_SESSION['licencia'] = $lic_code;

                // Guardar nombre de empresa desencriptado para mostrar en el sidebar
                $em_raw = $elegida['em_nombre'] ?? '';
                if ($em_raw && strlen($em_raw) > 40) {
                    $dec = openssl_decrypt($em_raw, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                    if ($dec !== false) $em_raw = $dec;
                }
                $_SESSION['empresa_nombre'] = $em_raw ?: $lic_code;

                // Conectar a la BD cliente y crear esquema si es primera vez
                try {
                    $conexionBDC = conectarDB2();
                    ActiveDBC::setDB($conexionBDC);
                    Model_apphelperBD_ventas::inicialize();
                    Model_apphelperBD_stock::inicialize();
                } catch (\Throwable $e) {
                    error_log('selector — fallo conexión BD cliente: ' . $e->getMessage());
                    $errores[] = 'No se pudo conectar a la base de datos de la empresa.';
                    unset($_SESSION['data_licencia']);
                }

                if (empty($errores)) {
                    header('Location: menu');
                    exit;
                }
            }
        }

        $router->render('selector/selector_empresa', [
            'licencias' => $licencias,
            'errores'   => $errores,
        ], 'layout_selector');
    }

    // Permite cambiar de empresa sin salir del sistema
    public static function cambiar(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['login'])) { header('Location: login'); exit; }

        unset($_SESSION['data_licencia'], $_SESSION['licencia'], $_SESSION['empresa_nombre']);
        // Limpiar caches de esquema para que re-inicialicen en la nueva empresa
        foreach (array_keys($_SESSION) as $k) {
            if (str_starts_with($k, 'akim_vta_') || str_starts_with($k, 'akim_stk_')) {
                unset($_SESSION[$k]);
            }
        }
        header('Location: seleccionarEmpresa');
        exit;
    }
}
