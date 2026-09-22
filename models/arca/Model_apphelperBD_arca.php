<?php

namespace Model\Arca;

use Model\ActiveDBC;

class Model_apphelperBD_arca extends ActiveDBC
{
    public static function inicialize(): void
    {
        $key = 'akim_arca_schema_ok_' . ARCA_SCHEMA_VER;
        if (!empty($_SESSION[$key])) return;

        if (!self::isConnected()) return;

        self::crearTablaPuntosVenta();
        self::seedParametros();

        $_SESSION[$key] = true;
    }

    private static function crearTablaPuntosVenta(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS arca_puntos_venta (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            numero      SMALLINT     NOT NULL COMMENT 'Número de 4 dígitos del PV en AFIP',
            descripcion VARCHAR(100) DEFAULT NULL,
            habilitado  TINYINT      NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function seedParametros(): void
    {
        $defaults = [
            ['arca_habilitado',       '0',  'arca'],
            ['arca_cuit',             '',   'arca'],
            ['arca_modo',             '0',  'arca'],
            ['arca_pv',               '1',  'arca'],
            ['arca_condicion_fiscal', 'RI', 'arca'],
            ['arca_cert_ok',          '0',  'arca'],
        ];

        $stmt = self::$conexionBDC->prepare(
            "INSERT INTO app_parametros (clave, valor, categoria)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE clave = clave"
        );

        foreach ($defaults as [$clave, $valor, $cat]) {
            $stmt->execute([$clave, $valor, $cat]);
        }
    }
}
