<?php

namespace Model;

use Model\ActiveDBC;

class Model_apphelperBD_parametros extends ActiveDBC
{
    public static function inicialize(): void
    {
        $key = 'akim_cfg_schema_ok_' . CFG_SCHEMA_VER;
        if (!empty($_SESSION[$key])) return;

        if (!self::isConnected()) return;

        self::crearTablaParametros();
        self::insertarDefaults();

        $_SESSION[$key] = true;
    }

    private static function crearTablaParametros(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS app_parametros (
            clave       VARCHAR(80)  NOT NULL,
            valor       VARCHAR(255) NOT NULL DEFAULT '',
            descripcion VARCHAR(255) NOT NULL DEFAULT '',
            categoria   VARCHAR(50)  NOT NULL DEFAULT 'general',
            PRIMARY KEY (clave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function insertarDefaults(): void
    {
        $defaults = [
            ['prv_condicion_fiscal',       'Responsable Inscripto', 'Condición fiscal default de proveedores',        'proveedores'],
            ['prd_alicuota_iva',           '21',                    'Alícuota IVA default de productos (%)',           'productos'],
            ['prd_condicion_iva',          'Gravado',               'Condición IVA default de productos',              'productos'],
            ['cpa_condicion_pago',         'Contado',               'Condición de pago default en facturas de compra', 'compras'],
            ['stk_deposito_default',       '',                      'Depósito por defecto para movimientos de compra', 'stock'],
            ['stk_permite_negativo',       '1',                     'Permite vender con stock en cero o negativo',      'stock'],
            // ── Datos de empresa (usados en comprobantes e impresiones) ──
            ['empresa_razon_social',       '',  'Razón social o nombre del emisor',           'empresa'],
            ['empresa_domicilio',          '',  'Domicilio fiscal',                            'empresa'],
            ['empresa_localidad',          '',  'Localidad',                                   'empresa'],
            ['empresa_provincia',          '',  'Provincia',                                   'empresa'],
            ['empresa_telefono',           '',  'Teléfono de contacto',                        'empresa'],
            ['empresa_email',              '',  'Email de contacto',                           'empresa'],
            ['empresa_inicio_actividad',   '',  'Fecha de inicio de actividad (dd/mm/aaaa)',   'empresa'],
            ['empresa_iibb',               '',  'Número o condición de IIBB',                  'empresa'],
            ['empresa_slogan',             '',  'Eslogan o actividad breve',                   'empresa'],
            ['empresa_logo',               '',  'Ruta relativa al logo (public/logo/...)',      'empresa'],
        ];

        $sql  = "INSERT IGNORE INTO app_parametros (clave, valor, descripcion, categoria) VALUES (?, ?, ?, ?)";
        $stmt = self::$conexionBDC->prepare($sql);
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    }
}
