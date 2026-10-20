<?php

namespace Model\Arca;

use Model\ActiveDBC;
use Model\Model_parametros;
use Arca\Wsfev1;

/**
 * Orquesta la emisión de facturas electrónicas AFIP/ARCA.
 *
 * Flujo: getConfig → determinarTipoCbte → calcularImportes → emitir
 */
class Model_arca_service extends ActiveDBC
{
    // Mapeo condicion_fiscal → CondicionIVAReceptorId AFIP
    private static array $condicionIvaId = [
        'RI' => 1,   // IVA Responsable Inscripto
        'EX' => 4,   // IVA Sujeto Exento
        'CF' => 5,   // Consumidor Final
        'MT' => 6,   // Responsable Monotributo
    ];

    // ── Config ───────────────────────────────────────────────────────────────

    public static function getConfig(): array
    {
        return [
            'habilitado'       => Model_parametros::get('arca_habilitado', '0') === '1',
            'cuit'             => Model_parametros::get('arca_cuit', ''),
            'modo'             => (int) Model_parametros::get('arca_modo', '0'),
            'pv'               => (int) Model_parametros::get('arca_pv', '1'),
            'condicion_fiscal' => Model_parametros::get('arca_condicion_fiscal', 'RI'),
            'cert_ok'          => Model_parametros::get('arca_cert_ok', '0') === '1',
        ];
    }

    // ── Tipo de comprobante ───────────────────────────────────────────────────

    /**
     * Determina el código de tipo de comprobante AFIP según la condición del
     * emisor y la condición fiscal del receptor.
     *
     *   RI → RI  = 1  (Factura A)
     *   MT → *   = 11 (Factura C)
     *   EX → *   = 6  (Factura B, no discrimina IVA)
     *   RI → !RI = 6  (Factura B)
     */
    public static function determinarTipoCbte(string $emisor, string $receptor): int
    {
        if ($emisor === 'MT') return 11;
        if ($emisor === 'RI' && $receptor === 'RI') return 1;
        return 6;
    }

    // ── Importes ─────────────────────────────────────────────────────────────

    /**
     * Descompone el total final en los campos que exige AFIP.
     * Para FA y FB: los precios del wizard se asumen con IVA 21% incluido.
     * Para FC (MT): sin IVA — todo va a importeNoGravado.
     */
    public static function calcularImportes(float $total, int $tipoCbte): array
    {
        if ($tipoCbte === 11) {
            return [
                'gravado'    => 0.00,
                'no_gravado' => round($total, 2),
                'exento'     => 0.00,
                'iva'        => 0.00,
                'subtotivas' => [],
            ];
        }

        // FA y FB: IVA 21% incluido en el precio de venta
        $neto = round($total / 1.21, 2);
        $iva  = round($total - $neto, 2);

        return [
            'gravado'    => $neto,
            'no_gravado' => 0.00,
            'exento'     => 0.00,
            'iva'        => $iva,
            'subtotivas' => [
                ['codigo' => 5, 'Alic' => 21, 'importe' => $iva, 'BaseImp' => $neto],
            ],
        ];
    }

    // ── Emisión ───────────────────────────────────────────────────────────────

    /**
     * Emite la factura electrónica y actualiza vta_ventas con los datos fiscales.
     *
     * @param  array  $wizard  Datos de sesión del wizard de venta.
     * @param  string $vtaIdx  idx de la venta ya insertada en BD.
     * @return array  ['cae', 'cae_vto', 'nro_afip', 'tipo_cbte']
     * @throws \RuntimeException si ARCA no está configurado o AFIP rechaza.
     */
    public static function emitir(array $wizard, string $vtaIdx): array
    {
        $cfg = self::getConfig();

        if (!$cfg['habilitado']) {
            throw new \RuntimeException('La facturación electrónica ARCA no está habilitada.');
        }
        if (!$cfg['cert_ok'] || $cfg['cuit'] === '') {
            throw new \RuntimeException('Certificados ARCA no configurados. Revisá Parámetros → ARCA.');
        }

        // ── Receptor: condición fiscal y CUIT ────────────────────────────────
        $cliente_id        = (int)($wizard['cliente_id'] ?? 0);
        $receptor_condicion = 'CF';
        $receptor_cuit     = 0;
        $receptor_doc_tipo = 99; // 99 = Consumidor Final

        if ($cliente_id > 0) {
            $stmt = self::$conexionBDC->prepare(
                "SELECT condicion_fiscal, cuit FROM vta_clientes WHERE id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $cliente_id]);
            $cli = $stmt->fetch();
            if ($cli) {
                $receptor_condicion = $cli['condicion_fiscal'] ?: 'CF';
                $cuit_raw = preg_replace('/\D/', '', (string)($cli['cuit'] ?? ''));
                if (strlen($cuit_raw) === 11) {
                    $receptor_cuit     = (float)$cuit_raw;
                    $receptor_doc_tipo = 80; // 80 = CUIT
                }
            }
        }

        $tipo_cbte = self::determinarTipoCbte($cfg['condicion_fiscal'], $receptor_condicion);

        // ── Totales ───────────────────────────────────────────────────────────
        $total_bruto = array_sum(array_column($wizard['items'], 'subtotal'));
        $descuento   = (float)($wizard['descuento'] ?? 0);
        $total       = round($total_bruto * (1 - $descuento / 100), 2);
        $importes    = self::calcularImportes($total, $tipo_cbte);

        // ── Consultar último autorizado y construir voucher ───────────────────
        $wsfe  = new Wsfev1($cfg['cuit'], $cfg['modo']);
        $ultimo = (int)$wsfe->consultarUltimoComprobanteAutorizado($cfg['pv'], $tipo_cbte);
        $nro    = $ultimo + 1;

        $condicion_receptor_id = self::$condicionIvaId[$receptor_condicion] ?? 5;

        $voucher = [
            'numeroPuntoVenta'       => $cfg['pv'],
            'codigoTipoComprobante'  => $tipo_cbte,
            'numeroComprobante'      => $nro,
            'codigoConcepto'         => 1,        // 1 = Productos
            'codigoMoneda'           => 'PES',
            'canmismaMoneda'         => 'N',
            'cotizacionMoneda'       => 1.000,
            'codigoTipoDocumento'    => $receptor_doc_tipo,
            'numeroDocumento'        => $receptor_cuit,
            'CondicionIVAReceptorId' => $condicion_receptor_id,
            'importeGravado'         => $importes['gravado'],
            'importeNoGravado'       => $importes['no_gravado'],
            'importeExento'          => $importes['exento'],
            'importeIVA'             => $importes['iva'],
            'importeOtrosTributos'   => 0.00,
            'importeTotal'           => $total,
            'subtotivas'             => $importes['subtotivas'],
            'Tributos'               => [],
            'CbtesAsoc'              => [],
        ];

        $resp = $wsfe->emitirComprobante($voucher);

        // ── Convertir fecha CAE de YYYYMMDD a Y-m-d ──────────────────────────
        $cae_raw = $resp['fechaVencimientoCAE'];
        $cae_vto = substr($cae_raw, 0, 4) . '-' . substr($cae_raw, 4, 2) . '-' . substr($cae_raw, 6, 2);
        $cae     = $resp['cae'];

        // ── Persistir datos fiscales ──────────────────────────────────────────
        self::$conexionBDC->prepare(
            "UPDATE vta_ventas
             SET modo_factura = 'fiscal',
                 tipo_cbte    = :tc,
                 nro_afip     = :nro,
                 cae          = :cae,
                 cae_vto      = :vto
             WHERE idx = :idx"
        )->execute([
            ':tc'  => $tipo_cbte,
            ':nro' => $nro,
            ':cae' => $cae,
            ':vto' => $cae_vto,
            ':idx' => $vtaIdx,
        ]);

        return [
            'cae'       => $cae,
            'cae_vto'   => $cae_vto,
            'nro_afip'  => $nro,
            'tipo_cbte' => $tipo_cbte,
        ];
    }

    // ── Test de conexión ──────────────────────────────────────────────────────

    /**
     * Prueba la conexión a AFIP WSFE (FEDummy).
     * Devuelve ['ok' => bool, 'msg' => string].
     */
    public static function testConexion(): array
    {
        $cfg = self::getConfig();
        try {
            if ($cfg['cuit'] === '') {
                return ['ok' => false, 'msg' => 'CUIT no configurado.'];
            }
            $wsfe = new Wsfev1($cfg['cuit'], $cfg['modo']);
            $wsfe->dummy();
            $entorno = $cfg['modo'] === 1 ? 'Producción' : 'Homologación';
            return ['ok' => true, 'msg' => "Conexión exitosa ({$entorno})."];
        } catch (\Throwable $e) {
            $diag = self::diagnosticarConectividad($cfg['modo'] ?? 0);
            return ['ok' => false, 'msg' => $e->getMessage() . ($diag ? " | " . $diag : '')];
        }
    }

    /**
     * Diagnóstico de red de bajo nivel para aislar la causa de "Could not connect to host".
     */
    private static function diagnosticarConectividad(int $modo): string
    {
        $url  = $modo === 1 ? 'https://servicios1.afip.gov.ar/wsfev1/service.asmx' : 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx';
        $host = parse_url($url, PHP_URL_HOST);
        $ip   = @gethostbyname($host);

        if ($ip === $host) {
            return "Diagnóstico: Falló resolución DNS para '$host' (el servidor de hosting no puede resolver dominios AFIP).";
        }

        $fp = @fsockopen($host, 443, $errno, $errstr, 4);
        if (!$fp) {
            return "Diagnóstico: DNS OK ($ip), pero falló conexión TCP al puerto 443: $errstr ($errno). El firewall saliente del hosting bloquea el puerto 443 o AFIP tiene bloqueada la IP del servidor.";
        }
        fclose($fp);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT:!DH:!DHE@SECLEVEL=1');
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; AKIM)');
            curl_exec($ch);
            $cErr = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($cErr !== '') {
                return "Diagnóstico: Puerto 443 abierto ($ip), pero cURL HTTPS falló: $cErr.";
            }
            return "Diagnóstico: Red y HTTPS responden correctamente (HTTP $httpCode en $ip). La falla es interna de SoapClient/WSDL.";
        }

        return "Diagnóstico: Conexión TCP al puerto 443 exitosa ($ip).";
    }
}
