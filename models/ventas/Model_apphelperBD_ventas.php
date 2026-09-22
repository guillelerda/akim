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
        self::agregarColumnasArca();
        self::seedClientes();

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
            modo_factura    ENUM('manual','fiscal') NOT NULL DEFAULT 'manual',
            tipo_cbte       TINYINT       DEFAULT NULL COMMENT '1=FA 6=FB 11=FC',
            nro_afip        INT           DEFAULT NULL,
            cae             VARCHAR(20)   DEFAULT NULL,
            cae_vto         DATE          DEFAULT NULL,
            created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function agregarColumnasArca(): void
    {
        $stmt = self::$conexionBDC->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vta_ventas'"
        );
        $cols = array_flip($stmt->fetchAll(\PDO::FETCH_COLUMN));

        $db = self::$conexionBDC;
        if (!isset($cols['modo_factura'])) {
            $db->exec("ALTER TABLE vta_ventas ADD COLUMN modo_factura ENUM('manual','fiscal') NOT NULL DEFAULT 'manual' AFTER observaciones");
        }
        if (!isset($cols['tipo_cbte'])) {
            $db->exec("ALTER TABLE vta_ventas ADD COLUMN tipo_cbte TINYINT DEFAULT NULL AFTER modo_factura");
        }
        if (!isset($cols['nro_afip'])) {
            $db->exec("ALTER TABLE vta_ventas ADD COLUMN nro_afip INT DEFAULT NULL AFTER tipo_cbte");
        }
        if (!isset($cols['cae'])) {
            $db->exec("ALTER TABLE vta_ventas ADD COLUMN cae VARCHAR(20) DEFAULT NULL AFTER nro_afip");
        }
        if (!isset($cols['cae_vto'])) {
            $db->exec("ALTER TABLE vta_ventas ADD COLUMN cae_vto DATE DEFAULT NULL AFTER cae");
        }
    }

    private static function seedClientes(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM vta_clientes")->fetchColumn();
        if ($count > 0) return;

        self::$conexionBDC->prepare(
            "INSERT INTO vta_clientes
             (idx, razon_social, condicion_fiscal, condicion_pago, habilitado, observaciones)
             VALUES (?, 'Consumidor Final', 'Consumidor Final', 'Contado', 1,
                     'Cliente genérico para ventas en mostrador sin identificación.')"
        )->execute([uniqid('cli_')]);
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
