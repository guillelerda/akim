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
}
