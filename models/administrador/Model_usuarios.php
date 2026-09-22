<?php

namespace Model\Administrador;

use Model\ActiveDB;
use Data\Administrador\sys_usuarios;

class Model_usuarios extends ActiveDB
{
    protected static $tabla      = 'sys_usuarios';
    protected static $columnasDB = ['id', 'username', 'password', 'nombre', 'email', 'telefono', 'perfil', 'foto', 'empresa', 'confirmado', 'token_apertura', 'pin', 'venc_pin', 'last_seen', 'version'];

    public function query_username(string $username): array|false
    {
        $obj = new sys_usuarios();
        $obj->setDB(self::$conexionBD);
        return $obj->usuario_query_username($username);
    }

    public static function updateLastSeen(string $username): void
    {
        sys_usuarios::setStaticDB(self::$conexionBD);
        sys_usuarios::update_last_seen($username);
    }

    public function queryAll_activos(): array
    {
        $sql      = "SELECT * FROM sys_usuarios WHERE confirmado = 1 ORDER BY username ASC";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function queryAll(): array
    {
        $stmt = self::$conexionBD->prepare("SELECT * FROM sys_usuarios ORDER BY username ASC");
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function getByUsername(string $username): array|false
    {
        $stmt = self::$conexionBD->prepare("SELECT * FROM sys_usuarios WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: false;
    }

    public function usernameExiste(string $username): bool
    {
        $stmt = self::$conexionBD->prepare("SELECT COUNT(*) FROM sys_usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function insertar(array $d): void
    {
        self::$conexionBD->prepare(
            "INSERT INTO sys_usuarios (username, password, nombre, email, telefono, perfil, confirmado)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            strtolower(trim($d['username'])),
            password_hash($d['password'], PASSWORD_DEFAULT),
            openssl_encrypt(trim($d['nombre']   ?? ''), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            openssl_encrypt(trim($d['email']    ?? ''), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            trim($d['telefono'] ?? ''),
            openssl_encrypt(trim($d['perfil']   ?? 'Operador'), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            isset($d['confirmado']) ? 1 : 0,
        ]);
    }

    public function actualizar(array $d): void
    {
        $sets = "nombre = ?, email = ?, telefono = ?, perfil = ?, confirmado = ?";
        $vals = [
            openssl_encrypt(trim($d['nombre']  ?? ''), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            openssl_encrypt(trim($d['email']   ?? ''), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            trim($d['telefono'] ?? ''),
            openssl_encrypt(trim($d['perfil']  ?? 'Operador'), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            isset($d['confirmado']) ? 1 : 0,
        ];
        if (!empty(trim($d['password'] ?? ''))) {
            $sets .= ', password = ?';
            $vals[] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        $vals[] = $d['username'];
        self::$conexionBD->prepare("UPDATE sys_usuarios SET $sets WHERE username = ?")->execute($vals);
    }

    public function toggle(string $username): void
    {
        self::$conexionBD->prepare(
            "UPDATE sys_usuarios SET confirmado = 1 - confirmado WHERE username = ?"
        )->execute([$username]);
    }

    public function eliminar(string $username): void
    {
        self::$conexionBD->prepare("DELETE FROM sys_usuarios WHERE username = ?")->execute([$username]);
    }
}
