<?php

namespace Model\Administrador;

use Model\ActiveDB;

class Model_apphelperBD_administrador extends ActiveDB
{
    public static function inicialize(): void
    {
        $sesionKey = 'akim_adm_schema_ok_' . ADM_SCHEMA_VER;
        if (!empty($_SESSION[$sesionKey])) return;

        self::verificarTablaUsuarios();
        self::verificarTablaEmpresas();
        self::verificarTablaBases();
        self::verificarTablaLicencias();
        self::migrarLicencias();
        self::verificarTablaLinks();

        $_SESSION[$sesionKey] = true;
    }

    // ── sys_usuarios ──────────────────────────────────────────────────────────

    private static function verificarTablaUsuarios(): void
    {
        self::$conexionBD->exec("CREATE TABLE IF NOT EXISTS sys_usuarios (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            username        VARCHAR(50)  NOT NULL UNIQUE,
            password        VARCHAR(255) NOT NULL,
            nombre          VARCHAR(255) DEFAULT '',
            email           VARCHAR(255) DEFAULT '',
            telefono        VARCHAR(50)  DEFAULT '',
            perfil          VARCHAR(100) DEFAULT 'usuario',
            foto            VARCHAR(100) DEFAULT 'default.png',
            empresa         VARCHAR(50)  DEFAULT '',
            confirmado      TINYINT(1)   DEFAULT 0,
            token_apertura  VARCHAR(100) DEFAULT '',
            pin             VARCHAR(20)  DEFAULT '',
            venc_pin        DATETIME     DEFAULT NULL,
            last_seen       DATETIME     DEFAULT NULL,
            version         INT          DEFAULT 1,
            created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    // ── sys_empresas ──────────────────────────────────────────────────────────

    private static function verificarTablaEmpresas(): void
    {
        self::$conexionBD->exec("CREATE TABLE IF NOT EXISTS sys_empresas (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            em_code      VARCHAR(20)  NOT NULL UNIQUE,
            em_nombre    VARCHAR(255) NOT NULL,
            em_slogan    VARCHAR(255) DEFAULT '',
            em_logo      VARCHAR(100) DEFAULT '',
            em_cuit      VARCHAR(20)  DEFAULT '',
            em_iibb      VARCHAR(20)  DEFAULT '',
            em_domicilio VARCHAR(255) DEFAULT '',
            em_telefono  VARCHAR(50)  DEFAULT '',
            em_email     VARCHAR(100) DEFAULT '',
            habilitada   TINYINT(1)   DEFAULT 1,
            created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    // ── sys_bases: credenciales de BD cliente (encriptadas) ───────────────────

    private static function verificarTablaBases(): void
    {
        self::$conexionBD->exec("CREATE TABLE IF NOT EXISTS sys_bases (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            bd_code    VARCHAR(30)  NOT NULL UNIQUE,
            bd_label   VARCHAR(100) DEFAULT '',
            bd_host    VARCHAR(255) NOT NULL,
            bd_name    VARCHAR(255) NOT NULL,
            bd_user    VARCHAR(255) NOT NULL,
            bd_pass    VARCHAR(255) NOT NULL,
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    // ── sys_licencias ─────────────────────────────────────────────────────────

    private static function verificarTablaLicencias(): void
    {
        self::$conexionBD->exec("CREATE TABLE IF NOT EXISTS sys_licencias (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            lic_code        VARCHAR(30)  NOT NULL UNIQUE,
            lic_empresa_cod VARCHAR(100) NOT NULL,
            bd_code         VARCHAR(30)  DEFAULT NULL,
            lic_modulos     TEXT         DEFAULT NULL,
            habilitada      TINYINT(1)   DEFAULT 1,
            vencimiento     DATE         DEFAULT NULL,
            created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    // Migración: agrega bd_code si la tabla ya existía sin esa columna
    private static function migrarLicencias(): void
    {
        try {
            self::$conexionBD->exec(
                "ALTER TABLE sys_licencias ADD COLUMN bd_code VARCHAR(30) DEFAULT NULL AFTER lic_empresa_cod"
            );
        } catch (\Throwable $e) {
            // Columna ya existe — ignorar
        }
    }

    // ── sys_links: usuario ↔ licencia ─────────────────────────────────────────

    private static function verificarTablaLinks(): void
    {
        self::$conexionBD->exec("CREATE TABLE IF NOT EXISTS sys_links (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            username   VARCHAR(50)  NOT NULL,
            licencia   VARCHAR(30)  NOT NULL,
            em_code    VARCHAR(20)  NOT NULL,
            habilitada TINYINT(1)   DEFAULT 1,
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_lic (username, licencia)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}
