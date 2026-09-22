<?php

namespace Controllers\Ventas;

use MVC\Router;
use Model\Model_apphelper;
use Model\Model_parametros;
use Model\Ventas\Model_vta_ventas;

class Controller_vta_comprobante
{
    public static function ver(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $idx   = $_GET['idx'] ?? '';
        $venta = null;
        $items = [];

        if ($idx) {
            $venta = Model_vta_ventas::getByIdx($idx);
            if ($venta) {
                $items = Model_vta_ventas::getItems($idx);
            }
        }

        if (!$venta) {
            echo "<script>location.href='ventas';</script>";
            return;
        }

        $init    = Model_apphelper::inicialize();
        $licencia = $init['licencia'] ?? [];
        [$em_nombre, $em_slogan, , $dataEm] = Model_apphelper::getLicenciaAutenticada($licencia);

        // ── Datos de empresa ──────────────────────────────────────────────────
        // Fuente primaria: tabla em_* del sistema (siempre cargada por el admin).
        // Fuente secundaria: parámetros del cliente (localidad, provincia, etc.
        // que no tienen columna en em_empresas).
        $params  = Model_parametros::getAll();
        // Prioridad: params del cliente (editables por el usuario) > datos sistema (admin).
        $empresa = [
            'razon_social'     => ($params['empresa_razon_social'] ?? '')
                                  ?: ($em_nombre ?: 'AKIM'),
            'domicilio'        => ($params['empresa_domicilio'] ?? '')
                                  ?: ($dataEm['em_domicilio'] ?? ''),
            'localidad'        => $params['empresa_localidad']        ?? '',
            'provincia'        => $params['empresa_provincia']        ?? '',
            'telefono'         => ($params['empresa_telefono'] ?? '')
                                  ?: ($dataEm['em_telefono'] ?? ''),
            'email'            => ($params['empresa_email'] ?? '')
                                  ?: ($dataEm['em_email'] ?? ''),
            'slogan'           => ($params['empresa_slogan'] ?? '')
                                  ?: ($dataEm['em_slogan'] ?? ''),
            'inicio_actividad' => $params['empresa_inicio_actividad'] ?? '',
            'iibb'             => ($params['empresa_iibb'] ?? '')
                                  ?: ($dataEm['em_iibb'] ?? ''),
            'cuit'             => ($params['arca_cuit'] ?? '')
                                  ?: ($dataEm['em_cuit'] ?? ''),
            'condicion_fiscal' => $params['arca_condicion_fiscal']    ?? '',
            'logo'             => ($params['empresa_logo'] ?? '')
                                  ?: ($dataEm['em_logo'] ?? ''),
        ];

        // ── Datos fiscales AFIP ───────────────────────────────────────────────
        $afip_qr_url = null;
        $afip_tipo   = null;

        if (!empty($venta['cae'])) {
            $tipo_cbte = (int)($venta['tipo_cbte'] ?? 0);
            $afip_tipo = match($tipo_cbte) { 1 => 'A', 6 => 'B', 11 => 'C', default => '' };

            $cuit_emisor = Model_parametros::get('arca_cuit', '');
            $pv          = (int) Model_parametros::get('arca_pv', '1');

            $cuit_receptor = preg_replace('/\D/', '', (string)($venta['cliente_cuit'] ?? ''));
            $condFiscal    = $venta['cliente_condicion_fiscal'] ?? 'CF';
            $tipoDocRec    = strlen($cuit_receptor) === 11 ? 80 : 99;
            $nroDocRec     = strlen($cuit_receptor) === 11 ? (int)$cuit_receptor : 0;

            $qr_data = [
                'ver'        => 1,
                'fecha'      => $venta['fecha'],
                'cuit'       => (int)$cuit_emisor,
                'ptoVta'     => $pv,
                'tipoCmp'    => $tipo_cbte,
                'nroCmp'     => (int)($venta['nro_afip'] ?? 0),
                'importe'    => round((float)($venta['total'] ?? 0), 2),
                'moneda'     => 'PES',
                'ctz'        => 1,
                'tipoDocRec' => $tipoDocRec,
                'nroDocRec'  => $nroDocRec,
                'tipoCodAut' => 'E',
                'codAut'     => (int)($venta['cae']),
            ];
            $afip_qr_url = 'https://www.afip.gob.ar/fe/qr/?p=' . base64_encode(json_encode($qr_data));
            $afip_pv     = $pv;
        }

        $router->render('ventas/vta_comprobante', [
            'protegida'    => true,
            'auth'         => true,
            'page_title'   => 'Comprobante #' . str_pad((int)($venta['numero'] ?? 0), 4, '0', STR_PAD_LEFT),
            'username'     => $userName,
            'nombre'       => $nombre,
            'perfil'       => $perfil,
            'venta'        => $venta,
            'items'        => $items,
            'em_nombre'    => $em_nombre ?? 'AKIM',
            'em_slogan'    => $em_slogan ?? '',
            'afip_qr_url'  => $afip_qr_url,
            'afip_tipo'    => $afip_tipo,
            'afip_pv'      => $afip_pv ?? null,
            'empresa'      => $empresa,
        ], 'layout_print');
    }
}
