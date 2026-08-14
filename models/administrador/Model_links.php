<?php

namespace Model\Administrador;

use Model\ActiveDB;

class Model_links extends ActiveDB
{
    protected static $tabla      = 'sys_links';
    protected static $columnasDB = ['id', 'username', 'licencia', 'em_code', 'habilitada'];

    public function queryAll(): array
    {
        return self::$conexionBD->query(
            "SELECT lk.*, e.em_nombre, l.lic_empresa_cod, l.habilitada AS lic_habilitada
             FROM sys_links lk
             LEFT JOIN sys_licencias l ON l.lic_code = lk.licencia
             LEFT JOIN sys_empresas  e ON e.em_code  = lk.em_code
             ORDER BY lk.username ASC, lk.licencia ASC"
        )->fetchAll() ?: [];
    }

    public function getByLicencia(string $lic_code): array
    {
        $c = self::$conexionBD->prepare(
            "SELECT lk.*, e.em_nombre FROM sys_links lk
             LEFT JOIN sys_empresas e ON e.em_code = lk.em_code
             WHERE lk.licencia = :lic ORDER BY lk.username ASC"
        );
        $c->execute([':lic' => $lic_code]);
        return $c->fetchAll() ?: [];
    }

    public function crear(array $a): void
    {
        self::$conexionBD->prepare(
            "INSERT INTO sys_links (username, licencia, em_code, habilitada)
             VALUES (:u, :lic, :em, :h)
             ON DUPLICATE KEY UPDATE habilitada = VALUES(habilitada)"
        )->execute([
            ':u'   => strtoupper(trim($a['username'])),
            ':lic' => trim($a['licencia']),
            ':em'  => trim($a['em_code']),
            ':h'   => (int)($a['habilitada'] ?? 1),
        ]);
    }

    public function toggleHabilitada(int $id): void
    {
        self::$conexionBD->prepare(
            "UPDATE sys_links SET habilitada = 1 - habilitada WHERE id = :id"
        )->execute([':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        self::$conexionBD->prepare("DELETE FROM sys_links WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public function usuariosDisponibles(): array
    {
        return self::$conexionBD->query(
            "SELECT username FROM sys_usuarios WHERE confirmado = 1 ORDER BY username ASC"
        )->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
