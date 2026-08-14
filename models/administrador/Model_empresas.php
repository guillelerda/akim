<?php

namespace Model\Administrador;

use Model\ActiveDB;

class Model_empresas extends ActiveDB
{
    protected static $tabla      = 'sys_empresas';
    protected static $columnasDB = ['id', 'em_code', 'em_nombre', 'em_slogan', 'em_logo', 'em_cuit', 'em_iibb', 'em_domicilio', 'em_telefono', 'em_email', 'habilitada'];

    public function getRegistro_codigo(string $em_code): array|false
    {
        $sql      = "SELECT * FROM sys_empresas WHERE em_code = :em_code LIMIT 1";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':em_code', $em_code);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function queryAll(): array
    {
        return self::$conexionBD->query(
            "SELECT * FROM sys_empresas ORDER BY created_at DESC"
        )->fetchAll() ?: [];
    }

    public function crear(array $a): void
    {
        self::$conexionBD->prepare(
            "INSERT INTO sys_empresas
             (em_code, em_nombre, em_slogan, em_cuit, em_iibb, em_domicilio, em_telefono, em_email, habilitada)
             VALUES (:em_code,:em_nombre,:em_slogan,:em_cuit,:em_iibb,:em_domicilio,:em_telefono,:em_email,:habilitada)"
        )->execute([
            ':em_code'     => strtoupper(trim($a['em_code'])),
            ':em_nombre'   => openssl_encrypt(trim($a['em_nombre']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':em_slogan'   => trim($a['em_slogan']   ?? ''),
            ':em_cuit'     => trim($a['em_cuit']     ?? ''),
            ':em_iibb'     => trim($a['em_iibb']     ?? ''),
            ':em_domicilio'=> trim($a['em_domicilio']?? ''),
            ':em_telefono' => trim($a['em_telefono'] ?? ''),
            ':em_email'    => trim($a['em_email']    ?? ''),
            ':habilitada'  => (int)($a['habilitada'] ?? 1),
        ]);
    }

    public function actualizar(array $a): void
    {
        self::$conexionBD->prepare(
            "UPDATE sys_empresas SET
             em_nombre=:em_nombre, em_slogan=:em_slogan, em_cuit=:em_cuit, em_iibb=:em_iibb,
             em_domicilio=:em_domicilio, em_telefono=:em_telefono, em_email=:em_email, habilitada=:habilitada
             WHERE em_code=:em_code"
        )->execute([
            ':em_code'     => $a['em_code'],
            ':em_nombre'   => openssl_encrypt(trim($a['em_nombre']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':em_slogan'   => trim($a['em_slogan']   ?? ''),
            ':em_cuit'     => trim($a['em_cuit']     ?? ''),
            ':em_iibb'     => trim($a['em_iibb']     ?? ''),
            ':em_domicilio'=> trim($a['em_domicilio']?? ''),
            ':em_telefono' => trim($a['em_telefono'] ?? ''),
            ':em_email'    => trim($a['em_email']    ?? ''),
            ':habilitada'  => (int)($a['habilitada'] ?? 1),
        ]);
    }

    public function toggleHabilitada(string $em_code): void
    {
        self::$conexionBD->prepare(
            "UPDATE sys_empresas SET habilitada = 1 - habilitada WHERE em_code = :c"
        )->execute([':c' => $em_code]);
    }

    public static function decryptNombre(string $enc): string
    {
        return openssl_decrypt($enc, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV) ?: '(sin nombre)';
    }
}
