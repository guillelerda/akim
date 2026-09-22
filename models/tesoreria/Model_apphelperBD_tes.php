<?php

namespace Model\Tesoreria;

use Model\ActiveDBC;

class Model_apphelperBD_tes extends ActiveDBC
{
    public static function inicialize(): void
    {
        $key = 'akim_tes_schema_ok_' . TES_SCHEMA_VER;
        if (!empty($_SESSION[$key])) return;
        if (!self::isConnected()) return;

        self::crearTablaCuentas();
        self::crearTablaTarjetas();
        self::crearTablaChequeras();
        self::crearTablaCheques();
        self::crearTablaMovimientos();
        self::seedCuentas();
        self::seedTarjetas();
        self::migrarFavorito();

        $_SESSION[$key] = true;
    }

    private static function crearTablaCuentas(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS tes_cuentas (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx             VARCHAR(25)  NOT NULL UNIQUE,
            tipo            ENUM('efectivo','banco') NOT NULL DEFAULT 'efectivo',
            nombre          VARCHAR(100) NOT NULL,
            descripcion     VARCHAR(255) NOT NULL DEFAULT '',
            banco           VARCHAR(100) NOT NULL DEFAULT '',
            numero_cuenta   VARCHAR(50)  NOT NULL DEFAULT '',
            cbu             VARCHAR(22)  NOT NULL DEFAULT '',
            alias_cbu       VARCHAR(50)  NOT NULL DEFAULT '',
            moneda          CHAR(3)      NOT NULL DEFAULT 'ARS',
            habilitada      TINYINT(1)   NOT NULL DEFAULT 1,
            favorito        TINYINT(1)   NOT NULL DEFAULT 0,
            created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaTarjetas(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS tes_tarjetas (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx             VARCHAR(25)  NOT NULL UNIQUE,
            nombre          VARCHAR(100) NOT NULL,
            red             VARCHAR(50)  NOT NULL DEFAULT '',
            tipo_tarjeta    ENUM('credito','debito','prepago') NOT NULL DEFAULT 'credito',
            plazo_dias      TINYINT UNSIGNED NOT NULL DEFAULT 0,
            descuento_pct   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            cuenta_id       INT UNSIGNED NULL,
            habilitada      TINYINT(1)   NOT NULL DEFAULT 1,
            favorito        TINYINT(1)   NOT NULL DEFAULT 0,
            created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaChequeras(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS tes_chequeras (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx             VARCHAR(25)  NOT NULL UNIQUE,
            cuenta_id       INT UNSIGNED NOT NULL,
            banco           VARCHAR(100) NOT NULL DEFAULT '',
            numero_desde    INT UNSIGNED NOT NULL DEFAULT 0,
            numero_hasta    INT UNSIGNED NOT NULL DEFAULT 0,
            proximo_numero  INT UNSIGNED NOT NULL DEFAULT 0,
            activa          TINYINT(1)   NOT NULL DEFAULT 1,
            created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaCheques(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS tes_cheques (
            id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx               VARCHAR(25)  NOT NULL UNIQUE,
            tipo              ENUM('emitido','recibido') NOT NULL,
            estado            ENUM('cartera','depositado','endosado','cobrado','rechazado','anulado') NOT NULL DEFAULT 'cartera',
            numero            VARCHAR(30)  NOT NULL DEFAULT '',
            banco             VARCHAR(100) NOT NULL DEFAULT '',
            fecha_emision     DATE         NOT NULL,
            fecha_vencimiento DATE         NOT NULL,
            monto             DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            cuenta_id         INT UNSIGNED NULL,
            chequera_id       INT UNSIGNED NULL,
            proveedor_id      INT UNSIGNED NULL,
            cliente_id        INT UNSIGNED NULL,
            motivo            VARCHAR(255) NOT NULL DEFAULT '',
            referencia_tipo   VARCHAR(30)  NOT NULL DEFAULT '',
            referencia_idx    VARCHAR(25)  NOT NULL DEFAULT '',
            created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function seedCuentas(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM tes_cuentas")->fetchColumn();
        if ($count > 0) return;

        $stmt = self::$conexionBDC->prepare(
            "INSERT INTO tes_cuentas (idx, tipo, nombre, descripcion, habilitada)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([uniqid('tes_'), 'efectivo', 'Caja Principal',
                        'Caja de efectivo por defecto para cobros en mostrador.']);
        $stmt->execute([uniqid('tes_'), 'banco',    'Cuenta Bancaria Principal',
                        'Cuenta bancaria por defecto para transferencias y depósitos.']);
    }

    private static function seedTarjetas(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM tes_tarjetas")->fetchColumn();
        if ($count > 0) return;

        // Obtener id de la cuenta banco para acreditar tarjetas
        $cuentaId = self::$conexionBDC
            ->query("SELECT id FROM tes_cuentas WHERE tipo = 'banco' LIMIT 1")
            ->fetchColumn() ?: null;

        $tarjetas = [
            ['Visa Crédito',      'Visa',       'credito', 18, 0.00],
            ['Visa Débito',       'Visa',       'debito',   1, 0.00],
            ['Mastercard Crédito','Mastercard', 'credito', 18, 0.00],
            ['Naranja X',         'Naranja',    'credito', 21, 0.00],
        ];

        $stmt = self::$conexionBDC->prepare(
            "INSERT INTO tes_tarjetas
             (idx, nombre, red, tipo_tarjeta, plazo_dias, descuento_pct, cuenta_id, habilitada)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        foreach ($tarjetas as [$nombre, $red, $tipo, $plazo, $desc]) {
            $stmt->execute([uniqid('tar_'), $nombre, $red, $tipo, $plazo, $desc, $cuentaId]);
        }
    }

    private static function crearTablaMovimientos(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS tes_movimientos (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx              VARCHAR(25)  NOT NULL UNIQUE,
            cuenta_id        INT UNSIGNED NOT NULL,
            tipo             ENUM('ingreso','egreso') NOT NULL,
            estado           ENUM('confirmado','pendiente','anulado') NOT NULL DEFAULT 'confirmado',
            concepto         VARCHAR(255) NOT NULL DEFAULT '',
            monto            DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            fecha            DATE         NOT NULL,
            fecha_valor      DATE         NOT NULL,
            medio            VARCHAR(50)  NOT NULL DEFAULT '',
            referencia_tipo  VARCHAR(30)  NOT NULL DEFAULT '',
            referencia_idx   VARCHAR(25)  NOT NULL DEFAULT '',
            cheque_id        INT UNSIGNED NULL,
            usuario_id       INT UNSIGNED NOT NULL DEFAULT 0,
            created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function migrarFavorito(): void
    {
        foreach (['tes_cuentas', 'tes_tarjetas'] as $tabla) {
            try {
                self::$conexionBDC->exec(
                    "ALTER TABLE {$tabla} ADD COLUMN favorito TINYINT(1) NOT NULL DEFAULT 0"
                );
            } catch (\Throwable $e) {
                // columna ya existe — ignorar
            }
        }
    }
}
