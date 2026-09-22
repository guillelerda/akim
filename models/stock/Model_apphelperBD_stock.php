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
        self::migrarColumnasProductos();
        self::crearTablaCategorias();
        self::crearTablaMarcas();
        self::crearTablaDepositos();
        self::crearTablaStock();
        self::crearTablaMovimientos();
        self::seedDeposito();
        self::seedCategorias();
        self::seedMarcas();
        self::seedProductoEjemplo();

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

    private static function migrarColumnasProductos(): void
    {
        $migraciones = [
            'imagen'    => "ALTER TABLE stk_productos ADD COLUMN imagen    VARCHAR(100) DEFAULT ''",
            'en_tienda' => "ALTER TABLE stk_productos ADD COLUMN en_tienda TINYINT(1)   DEFAULT 0",
        ];
        foreach ($migraciones as $col => $sql) {
            $existe = (int) self::$conexionBDC->query(
                "SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name   = 'stk_productos'
                   AND column_name  = '$col'"
            )->fetchColumn();
            if (!$existe) {
                self::$conexionBDC->exec($sql);
            }
        }
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

    private static function crearTablaDepositos(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS stk_depositos (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx         VARCHAR(25)  NOT NULL DEFAULT '',
            nombre      VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255) NOT NULL DEFAULT '',
            es_default  TINYINT(1)   NOT NULL DEFAULT 0,
            habilitado  TINYINT(1)   NOT NULL DEFAULT 1,
            created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaStock(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS stk_stock (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            producto_id  INT UNSIGNED NOT NULL,
            deposito_id  INT UNSIGNED NOT NULL,
            stock_actual DECIMAL(12,3) NOT NULL DEFAULT 0.000,
            UNIQUE KEY uq_prod_dep (producto_id, deposito_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function crearTablaMovimientos(): void
    {
        self::$conexionBDC->exec("CREATE TABLE IF NOT EXISTS stk_movimientos (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idx                 VARCHAR(25)  NOT NULL DEFAULT '',
            tipo                ENUM('compra','ajuste_pos','ajuste_neg','transferencia','venta') NOT NULL,
            producto_id         INT UNSIGNED NOT NULL,
            deposito_origen_id  INT UNSIGNED NOT NULL,
            deposito_destino_id INT UNSIGNED NULL,
            cantidad            DECIMAL(12,3) NOT NULL,
            precio_ref          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            motivo              VARCHAR(255) NOT NULL DEFAULT '',
            fecha               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usuario_id          INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private static function seedCategorias(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM stk_categorias")->fetchColumn();
        if ($count > 0) return;

        $cats = ['General', 'Alimentos y Bebidas', 'Limpieza', 'Indumentaria', 'Electrónica'];
        $stmt = self::$conexionBDC->prepare(
            "INSERT INTO stk_categorias (nombre, habilitada) VALUES (?, 1)"
        );
        foreach ($cats as $cat) { $stmt->execute([$cat]); }
    }

    private static function seedMarcas(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM stk_marcas")->fetchColumn();
        if ($count > 0) return;

        $stmt = self::$conexionBDC->prepare(
            "INSERT INTO stk_marcas (nombre, habilitada) VALUES (?, 1)"
        );
        foreach (['Sin marca', 'Genérico'] as $m) { $stmt->execute([$m]); }
    }

    private static function seedProductoEjemplo(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM stk_productos")->fetchColumn();
        if ($count > 0) return;

        $catId = (int)self::$conexionBDC
            ->query("SELECT id FROM stk_categorias WHERE nombre = 'General' LIMIT 1")
            ->fetchColumn();

        self::$conexionBDC->prepare(
            "INSERT INTO stk_productos
             (idx, codigo, nombre, descripcion, categoria_id, precio_costo, precio_venta,
              stock_actual, stock_minimo, unidad, habilitado, en_tienda)
             VALUES (?, 'DEMO-001', 'Producto de Ejemplo', 'Producto de prueba generado automáticamente.',
                     ?, 500.00, 1000.00, 100.000, 5.000, 'unidad', 1, 1)"
        )->execute([uniqid('prd_'), $catId ?: null]);

        $prodId = (int)self::$conexionBDC->lastInsertId();

        // Insertar stock inicial en stk_stock (tabla por depósito)
        $depId = (int)self::$conexionBDC
            ->query("SELECT id FROM stk_depositos WHERE es_default = 1 LIMIT 1")
            ->fetchColumn();

        if ($prodId && $depId) {
            self::$conexionBDC->prepare(
                "INSERT IGNORE INTO stk_stock (producto_id, deposito_id, stock_actual)
                 VALUES (?, ?, 100.000)"
            )->execute([$prodId, $depId]);
        }
    }

    private static function seedDeposito(): void
    {
        $count = (int)self::$conexionBDC->query("SELECT COUNT(*) FROM stk_depositos")->fetchColumn();
        if ($count > 0) return;

        self::$conexionBDC->prepare(
            "INSERT INTO stk_depositos (idx, nombre, descripcion, es_default, habilitado)
             VALUES (?, 'CENTRAL', 'Depósito central principal', 1, 1)"
        )->execute([uniqid('dep_')]);

        $dep_id = (int)self::$conexionBDC->lastInsertId();

        try {
            self::$conexionBDC->prepare(
                "UPDATE app_parametros SET valor = ? WHERE clave = 'stk_deposito_default' AND valor = ''"
            )->execute([$dep_id]);
        } catch (\Throwable $e) {
            // app_parametros puede no existir en instalaciones muy nuevas
        }
    }
}
