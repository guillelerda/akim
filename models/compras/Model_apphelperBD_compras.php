<?php

namespace Model\Compras;

use Model\ActiveDBC;

class Model_apphelperBD_compras extends ActiveDBC
{
    public static function inicialize(): void
    {
        $key = 'akim_cpa_schema_ok_' . CPA_SCHEMA_VER;
        if (!empty($_SESSION[$key])) return;
        if (!self::isConnected()) return;

        self::crearTablaProveedores();
        self::crearTablaFacturas();
        self::crearTablaFacturasItems();

        $_SESSION[$key] = true;
    }

    private static function crearTablaProveedores(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS cpa_proveedores (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            idx              VARCHAR(30)  NOT NULL UNIQUE,
            razon_social     VARCHAR(255) NOT NULL,
            cuit             VARCHAR(20)  DEFAULT '',
            condicion_fiscal VARCHAR(50)  DEFAULT '',
            domicilio        VARCHAR(255) DEFAULT '',
            localidad        VARCHAR(100) DEFAULT '',
            provincia        VARCHAR(100) DEFAULT '',
            cp               VARCHAR(10)  DEFAULT '',
            telefono         VARCHAR(50)  DEFAULT '',
            email            VARCHAR(100) DEFAULT '',
            contacto         VARCHAR(150) DEFAULT '',
            condicion_pago   VARCHAR(50)  DEFAULT 'Contado',
            habilitado       TINYINT(1)   DEFAULT 1,
            observaciones    TEXT         DEFAULT NULL,
            created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaFacturas(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS cpa_facturas (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            idx               VARCHAR(30)   NOT NULL UNIQUE,
            numero            VARCHAR(20)   DEFAULT NULL,
            tipo              VARCHAR(5)    DEFAULT 'A',
            proveedor_id      INT           DEFAULT NULL,
            proveedor_label   VARCHAR(255)  DEFAULT '',
            fecha             DATE          NOT NULL,
            fecha_vencimiento DATE          DEFAULT NULL,
            condicion_pago    VARCHAR(50)   DEFAULT 'Contado',
            total_neto        DECIMAL(12,2) DEFAULT 0.00,
            iva_pct           DECIMAL(5,2)  DEFAULT 21.00,
            iva               DECIMAL(12,2) DEFAULT 0.00,
            total             DECIMAL(12,2) DEFAULT 0.00,
            estado            VARCHAR(20)   DEFAULT 'pendiente',
            observaciones     TEXT          DEFAULT NULL,
            created_at        DATETIME      DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaFacturasItems(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS cpa_facturas_items (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            factura_idx    VARCHAR(30)   NOT NULL,
            producto_label VARCHAR(255)  NOT NULL,
            cantidad       DECIMAL(10,3) DEFAULT 1.000,
            precio_unit    DECIMAL(12,2) DEFAULT 0.00,
            subtotal       DECIMAL(12,2) DEFAULT 0.00,
            orden          INT           DEFAULT 0,
            KEY idx_factura (factura_idx)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}
