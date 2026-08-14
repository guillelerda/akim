<?php

namespace Model\Ventas;

use Model\ActiveDBC;

class Model_apphelperBD_ventas extends ActiveDBC
{
    public static function inicialize(): void
    {
        $key = 'akim_vta_schema_ok_' . VTA_SCHEMA_VER;
        if (!empty($_SESSION[$key])) return;

        if (!self::isConnected()) return;

        self::crearTablaClientes();
        self::crearTablaVentas();
        self::crearTablaVentasItems();

        $_SESSION[$key] = true;
    }

    private static function crearTablaClientes(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS vta_clientes (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            idx              VARCHAR(30)  NOT NULL UNIQUE,
            razon_social     VARCHAR(255) NOT NULL,
            cuit             VARCHAR(20)  DEFAULT '',
            condicion_fiscal VARCHAR(50)  DEFAULT '',
            tipo_documento   VARCHAR(20)  DEFAULT 'DNI',
            nro_documento    VARCHAR(20)  DEFAULT '',
            domicilio        VARCHAR(255) DEFAULT '',
            localidad        VARCHAR(100) DEFAULT '',
            provincia        VARCHAR(100) DEFAULT '',
            cp               VARCHAR(10)  DEFAULT '',
            telefono         VARCHAR(50)  DEFAULT '',
            email            VARCHAR(100) DEFAULT '',
            condicion_pago   VARCHAR(50)  DEFAULT 'Contado',
            limite_credito   DECIMAL(12,2)DEFAULT 0.00,
            habilitado       TINYINT(1)   DEFAULT 1,
            observaciones    TEXT         DEFAULT NULL,
            created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaVentas(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS vta_ventas (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            idx             VARCHAR(30)   NOT NULL UNIQUE,
            numero          INT           DEFAULT NULL,
            cliente_id      INT           DEFAULT NULL,
            fecha           DATE          NOT NULL,
            condicion_pago  VARCHAR(50)   DEFAULT 'Contado',
            descuento       DECIMAL(5,2)  DEFAULT 0.00,
            total_bruto     DECIMAL(12,2) DEFAULT 0.00,
            total           DECIMAL(12,2) DEFAULT 0.00,
            estado          VARCHAR(20)   DEFAULT 'emitida',
            observaciones   TEXT          DEFAULT NULL,
            vendedor        VARCHAR(50)   DEFAULT NULL,
            created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaVentasItems(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS vta_ventas_items (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            venta_idx       VARCHAR(30)   NOT NULL,
            producto_id     INT           DEFAULT NULL,
            producto_label  VARCHAR(255)  NOT NULL,
            cantidad        DECIMAL(10,3) DEFAULT 1.000,
            precio_unit     DECIMAL(12,2) DEFAULT 0.00,
            subtotal        DECIMAL(12,2) DEFAULT 0.00,
            orden           INT           DEFAULT 0,
            KEY idx_venta (venta_idx)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}
