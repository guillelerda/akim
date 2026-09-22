<?php

namespace Controllers;

use MVC\Router;
use Model\Model_apphelper;
use Model\Model_parametros;
use Model\Stock\Model_stk_depositos;
use Model\Arca\Model_arca_service;

class Controllers_parametros
{
    public static function lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campos = [
                'prv_condicion_fiscal',
                'prd_alicuota_iva',
                'prd_condicion_iva',
                'cpa_condicion_pago',
                'stk_deposito_default',
                'stk_permite_negativo',
            ];
            foreach ($campos as $clave) {
                if (array_key_exists($clave, $_POST)) {
                    Model_parametros::set($clave, trim($_POST[$clave]));
                }
            }

            // ── Datos de empresa ───────────────────────────────────────────────
            $empresa_campos = [
                'empresa_razon_social', 'empresa_domicilio', 'empresa_localidad',
                'empresa_provincia',    'empresa_telefono',  'empresa_email',
                'empresa_slogan',       'empresa_iibb',      'empresa_inicio_actividad',
            ];
            foreach ($empresa_campos as $clave) {
                if (array_key_exists($clave, $_POST)) {
                    Model_parametros::set($clave, trim($_POST[$clave]));
                }
            }

            // ── Logo de empresa ────────────────────────────────────────────────
            $init   = Model_apphelper::inicialize();
            $licDir = dirname(__DIR__) . '/' . REPO_PATH . $init['licencia'] . '/logo/';
            if (!is_dir($licDir)) {
                mkdir($licDir, 0750, true);
            }
            if (!empty($_FILES['empresa_logo']['tmp_name']) && $_FILES['empresa_logo']['error'] === UPLOAD_ERR_OK) {
                $ext   = strtolower(pathinfo($_FILES['empresa_logo']['name'], PATHINFO_EXTENSION));
                $allow = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
                if (in_array($ext, $allow, true)) {
                    foreach (glob($licDir . 'empresa_logo.*') ?: [] as $old) {
                        @unlink($old);
                    }
                    $dest = $licDir . 'empresa_logo.' . $ext;
                    if (move_uploaded_file($_FILES['empresa_logo']['tmp_name'], $dest)) {
                        Model_parametros::set(
                            'empresa_logo',
                            REPO_PATH . $init['licencia'] . '/logo/empresa_logo.' . $ext
                        );
                    }
                }
            }

            // ── ARCA ──────────────────────────────────────────────────────────
            $arca_campos = [
                'arca_habilitado'       => isset($_POST['arca_habilitado']) ? '1' : '0',
                'arca_cuit'             => preg_replace('/\D/', '', $_POST['arca_cuit'] ?? ''),
                'arca_modo'             => in_array($_POST['arca_modo'] ?? '', ['0','1']) ? $_POST['arca_modo'] : '0',
                'arca_pv'               => (string)(int)($_POST['arca_pv'] ?? 1),
                'arca_condicion_fiscal' => in_array($_POST['arca_condicion_fiscal'] ?? '', ['RI','MT','EX']) ? $_POST['arca_condicion_fiscal'] : 'RI',
            ];
            foreach ($arca_campos as $clave => $valor) {
                Model_parametros::set($clave, $valor);
            }

            // ── Subida de certificados ─────────────────────────────────────────
            $cuit    = $arca_campos['arca_cuit'];
            $entorno = $arca_campos['arca_modo'] === '1' ? 'produccion' : 'homologacion';

            if ($cuit !== '') {
                self::_subirCert($cuit, $entorno);
            }

            // Actualizar flag cert_ok según existencia real de archivos
            Model_parametros::set('arca_cert_ok', self::_certExiste($cuit, $entorno) ? '1' : '0');

            // Invalidar tokens de acceso de ambos entornos (se regeneran en el próximo uso)
            if ($cuit !== '') {
                self::_invalidarTokens($cuit);
            }

            $_SESSION['flash_ok'] = 'Parámetros actualizados correctamente.';
            echo "<script>location.href='parametros';</script>";
            return;
        }

        $params  = Model_parametros::getAll();
        $cuit    = $params['arca_cuit']  ?? '';
        $entorno = ($params['arca_modo'] ?? '0') === '1' ? 'produccion' : 'homologacion';

        $depositos   = Model_stk_depositos::queryHabilitados();
        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('parametros', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Parámetros del sistema',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'params'      => $params,
            'depositos'   => $depositos,
            'arca_cert_ok' => self::_certExiste($cuit, $entorno),
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    // ── Test de conexión AFIP (endpoint AJAX) ─────────────────────────────────

    public static function testArca(Router $router): void
    {
        header('Content-Type: application/json');
        try {
            $result = Model_arca_service::testConexion();
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'msg' => $e->getMessage()];
        }
        echo json_encode($result);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private static function _invalidarTokens(string $cuit): void
    {
        $base = dirname(__DIR__) . '/arca/' . $cuit . '/wsfe/token/';
        foreach (['TA_prod.xml', 'TA_homo.xml'] as $f) {
            $path = $base . $f;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    private static function _certDir(string $cuit, string $entorno): string
    {
        return dirname(__DIR__) . '/arca/' . $cuit . '/key/' . $entorno . '/';
    }

    private static function _certExiste(string $cuit, string $entorno): bool
    {
        if ($cuit === '') return false;
        $dir = self::_certDir($cuit, $entorno);
        return file_exists($dir . 'certificado.pem') && file_exists($dir . 'privada');
    }

    private static function _subirCert(string $cuit, string $entorno): void
    {
        $dir = self::_certDir($cuit, $entorno);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $uploads = [
            'arca_cert'    => $dir . 'certificado.pem',
            'arca_privada' => $dir . 'privada',
        ];

        foreach ($uploads as $field => $destino) {
            if (!empty($_FILES[$field]['tmp_name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                move_uploaded_file($_FILES[$field]['tmp_name'], $destino);
            }
        }

        // Crear subdirectorios necesarios para tokens y logs de Wsfev1
        foreach (['wsfe/token', 'wsfe/tmp'] as $sub) {
            $subdir = dirname(__DIR__) . '/arca/' . $cuit . '/' . $sub;
            if (!is_dir($subdir)) mkdir($subdir, 0750, true);
        }
    }
}
