<?php

namespace Model\Administrador;

use Model\ActiveDB;

class Model_licencias extends ActiveDB
{
    protected static $tabla      = 'sys_licencias';
    protected static $columnasDB = ['id', 'lic_code', 'lic_empresa_cod', 'bd_code', 'lic_modulos', 'habilitada', 'vencimiento'];

    public function getRegistro_lic_code(string $lic_code): array|false
    {
        $sql = "SELECT l.*, b.bd_host, b.bd_name, b.bd_user, b.bd_pass
                FROM sys_licencias l
                LEFT JOIN sys_bases b ON b.bd_code = l.bd_code
                WHERE l.lic_code = :lic_code AND l.habilitada = 1
                LIMIT 1";
        $c = self::$conexionBD->prepare($sql);
        $c->execute([':lic_code' => $lic_code]);
        return $c->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    // Licencias de un usuario via sys_links + datos de empresa
    public function getLicenciasUsuario(string $username): array
    {
        $sql = "SELECT l.lic_code, l.lic_empresa_cod, l.bd_code, l.habilitada, l.vencimiento,
                       e.em_nombre, e.em_logo, e.em_slogan,
                       b.bd_host, b.bd_name, b.bd_user, b.bd_pass
                FROM sys_links lk
                JOIN sys_licencias l ON l.lic_code = lk.licencia
                LEFT JOIN sys_empresas e ON e.em_code = lk.em_code
                LEFT JOIN sys_bases   b ON b.bd_code  = l.bd_code
                WHERE lk.username = :u AND lk.habilitada = 1 AND l.habilitada = 1
                ORDER BY e.em_nombre ASC";
        $c = self::$conexionBD->prepare($sql);
        $c->execute([':u' => $username]);
        return $c->fetchAll() ?: [];
    }

    // Fallback: todas las licencias activas (para el primer admin sin sys_links)
    public function getLicenciasActivas(): array
    {
        $sql = "SELECT l.lic_code, l.lic_empresa_cod, l.bd_code, l.habilitada, l.vencimiento,
                       e.em_nombre, e.em_logo, e.em_slogan,
                       b.bd_host, b.bd_name, b.bd_user, b.bd_pass
                FROM sys_licencias l
                LEFT JOIN sys_empresas e ON e.em_code = (
                    SELECT DISTINCT em_code FROM sys_links WHERE licencia = l.lic_code LIMIT 1
                )
                LEFT JOIN sys_bases b ON b.bd_code = l.bd_code
                WHERE l.habilitada = 1
                ORDER BY e.em_nombre ASC";
        return self::$conexionBD->query($sql)->fetchAll() ?: [];
    }

    public function crear(array $a): void
    {
        self::$conexionBD->prepare(
            "INSERT INTO sys_licencias
             (lic_code, lic_empresa_cod, bd_code, lic_modulos, habilitada, vencimiento)
             VALUES (:code, :emp, :bd, :mods, :h, :venc)"
        )->execute([
            ':code' => trim($a['lic_code']),
            ':emp'  => openssl_encrypt(trim($a['lic_empresa_cod']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':bd'   => $a['bd_code'] ?: null,
            ':mods' => $a['lic_modulos'] ?: null,
            ':h'    => (int)($a['habilitada'] ?? 1),
            ':venc' => $a['vencimiento'] ?: null,
        ]);
    }

    public function queryAll(): array
    {
        return self::$conexionBD->query(
            "SELECT l.*, b.bd_label, e.em_nombre
             FROM sys_licencias l
             LEFT JOIN sys_bases b ON b.bd_code = l.bd_code
             LEFT JOIN sys_empresas e ON e.em_code = (
                 SELECT em_code FROM sys_links WHERE licencia = l.lic_code LIMIT 1
             )
             ORDER BY l.created_at DESC"
        )->fetchAll() ?: [];
    }

    public function getByCode(string $lic_code): array|false
    {
        $c = self::$conexionBD->prepare(
            "SELECT l.*, b.bd_label FROM sys_licencias l
             LEFT JOIN sys_bases b ON b.bd_code = l.bd_code
             WHERE l.lic_code = :c LIMIT 1"
        );
        $c->execute([':c' => $lic_code]);
        return $c->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function actualizar(array $a): void
    {
        self::$conexionBD->prepare(
            "UPDATE sys_licencias SET
             lic_empresa_cod=:emp, bd_code=:bd, lic_modulos=:mods, habilitada=:h, vencimiento=:venc
             WHERE lic_code=:c"
        )->execute([
            ':c'    => $a['lic_code'],
            ':emp'  => openssl_encrypt(trim($a['lic_empresa_cod']), SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV),
            ':bd'   => $a['bd_code'] ?: null,
            ':mods' => $a['lic_modulos'] ?: null,
            ':h'    => (int)($a['habilitada'] ?? 1),
            ':venc' => $a['vencimiento'] ?: null,
        ]);
    }

    public function toggleHabilitada(string $lic_code): void
    {
        self::$conexionBD->prepare(
            "UPDATE sys_licencias SET habilitada = 1 - habilitada WHERE lic_code = :c"
        )->execute([':c' => $lic_code]);
    }

    public function eliminar(string $lic_code): void
    {
        self::$conexionBD->prepare("DELETE FROM sys_licencias WHERE lic_code = :c")
            ->execute([':c' => $lic_code]);
    }

    public static function decryptEmpCod(string $enc): string
    {
        return openssl_decrypt($enc, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV) ?: '';
    }
}
