<?php

namespace Model\Stock;

use Model\ActiveDBC;

class Model_apphelperBD_stock extends ActiveDBC
{
    public static function inicialize(): void
    {
        $key = 'akim_stk_schema_ok_' . STK_SCHEMA_VER;
        if (!empty($_SESSION[$key])) return;

        if (!self::isConnected()) return;

        self::crearTablaProductos();
        self::crearTablaCategorias();
        self::crearTablaMarcas();

        $_SESSION[$key] = true;
    }

    private static function crearTablaProductos(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS stk_productos (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            idx             VARCHAR(30)   NOT NULL UNIQUE,
            codigo          VARCHAR(50)   DEFAULT '',
            nombre          VARCHAR(255)  NOT NULL,
            descripcion     TEXT          DEFAULT NULL,
            categoria_id    INT           DEFAULT NULL,
            subcategoria_id INT           DEFAULT NULL,
            marca_id        INT           DEFAULT NULL,
            familia_id      INT           DEFAULT NULL,
            precio_costo    DECIMAL(12,2) DEFAULT 0.00,
            precio_venta    DECIMAL(12,2) DEFAULT 0.00,
            stock_actual    DECIMAL(10,3) DEFAULT 0.000,
            stock_minimo    DECIMAL(10,3) DEFAULT 0.000,
            unidad          VARCHAR(20)   DEFAULT 'unidad',
            imagen          VARCHAR(100)  DEFAULT '',
            habilitado      TINYINT(1)    DEFAULT 1,
            en_tienda       TINYINT(1)    DEFAULT 0,
            created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaCategorias(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS stk_categorias (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            nombre      VARCHAR(100) NOT NULL,
            habilitada  TINYINT(1)   DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaMarcas(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS stk_marcas (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            nombre      VARCHAR(100) NOT NULL,
            habilitada  TINYINT(1)   DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}
