<?php

namespace Model\Administrador;

use Model\ActiveDB;

class Model_bases extends ActiveDB
{
    protected static $tabla      = 'sys_bases';
    protected static $columnasDB = ['id', 'bd_code', 'bd_label', 'bd_host', 'bd_name', 'bd_user', 'bd_pass'];

    public function queryAll(): array
    {
        return self::$conexionBD->query(
            "SELECT * FROM sys_bases ORDER BY bd_label ASC"
        )->fetchAll() ?: [];
    }

    public function getByCode(string $bd_code): array|false
    {
        $c = self::$conexionBD->prepare("SELECT * FROM sys_bases WHERE bd_code = :c LIMIT 1");
        $c->execute([':c' => $bd_code]);
        return $c->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function crear(array $a): void
    {
        self::$conexionBD->prepare(
            "INSERT INTO sys_bases (bd_code, bd_label, bd_host, bd_name, bd_user, bd_pass)
             VALUES (:bd_code, :bd_label, :bd_host, :bd_name, :bd_user, :bd_pass)"
        )->execute([
            ':bd_code'  => strtolower(trim($a['bd_code'])),
            ':bd_label' => trim($a['bd_label']),
            ':bd_host'  => openssl_encrypt(trim($a['bd_host']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':bd_name'  => openssl_encrypt(trim($a['bd_name']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':bd_user'  => openssl_encrypt(trim($a['bd_user']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':bd_pass'  => openssl_encrypt(trim($a['bd_pass']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
        ]);
    }

    public function actualizar(array $a, bool $cambiar_pass): void
    {
        if ($cambiar_pass) {
            self::$conexionBD->prepare(
                "UPDATE sys_bases SET bd_label=:bd_label, bd_host=:bd_host, bd_name=:bd_name,
                 bd_user=:bd_user, bd_pass=:bd_pass WHERE bd_code=:bd_code"
            )->execute([
                ':bd_code'  => $a['bd_code'],
                ':bd_label' => trim($a['bd_label']),
                ':bd_host'  => openssl_encrypt(trim($a['bd_host']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
                ':bd_name'  => openssl_encrypt(trim($a['bd_name']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
                ':bd_user'  => openssl_encrypt(trim($a['bd_user']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
                ':bd_pass'  => openssl_encrypt(trim($a['bd_pass']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ]);
        } else {
            self::$conexionBD->prepare(
                "UPDATE sys_bases SET bd_label=:bd_label, bd_host=:bd_host, bd_name=:bd_name,
                 bd_user=:bd_user WHERE bd_code=:bd_code"
            )->execute([
                ':bd_code'  => $a['bd_code'],
                ':bd_label' => trim($a['bd_label']),
                ':bd_host'  => openssl_encrypt(trim($a['bd_host']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
                ':bd_name'  => openssl_encrypt(trim($a['bd_name']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
                ':bd_user'  => openssl_encrypt(trim($a['bd_user']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ]);
        }
    }

    public function eliminar(string $bd_code): void
    {
        self::$conexionBD->prepare("DELETE FROM sys_bases WHERE bd_code = :c")
            ->execute([':c' => $bd_code]);
    }

    public static function decrypt(string $enc): string
    {
        return openssl_decrypt($enc, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV) ?: '';
    }

    public static function decryptRow(array $row): array
    {
        foreach (['bd_host', 'bd_name', 'bd_user', 'bd_pass'] as $k) {
            if (isset($row[$k])) {
                $row[$k] = self::decrypt($row[$k]);
            }
        }
        return $row;
    }
}
