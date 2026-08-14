<?php

namespace Data\Administrador;

class sys_usuarios
{
    protected static $tabla      = 'sys_usuarios';
    protected static $conexionBD;

    public $id;
    public $username;
    public $password;
    public $nombre;
    public $email;
    public $telefono;
    public $perfil;
    public $foto;
    public $empresa;
    public $confirmado;
    public $token_apertura;
    public $pin;
    public $venc_pin;
    public $last_seen;
    public $version;

    public function __construct(array $args = [])
    {
        $this->id             = $args['id']             ?? null;
        $this->username       = $args['username']       ?? '';
        $this->password       = $args['password']       ?? '';
        $this->nombre         = $args['nombre']         ?? '';
        $this->email          = $args['email']          ?? '';
        $this->telefono       = $args['telefono']       ?? '';
        $this->perfil         = $args['perfil']         ?? 'usuario';
        $this->foto           = $args['foto']           ?? 'default.png';
        $this->empresa        = $args['empresa']        ?? '';
        $this->confirmado     = $args['confirmado']     ?? 0;
        $this->token_apertura = $args['token_apertura'] ?? '';
        $this->pin            = $args['pin']            ?? '';
        $this->venc_pin       = $args['venc_pin']       ?? null;
        $this->last_seen      = $args['last_seen']      ?? null;
        $this->version        = $args['version']        ?? 1;
    }

    public static function setDB(\PDO $database): void
    {
        self::$conexionBD = $database;
    }

    public static function setStaticDB(\PDO $database): void
    {
        self::$conexionBD = $database;
    }

    public function usuario_query_username(string $username): array|false
    {
        $sql      = "SELECT * FROM " . self::$tabla . " WHERE username = :username LIMIT 1";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':username', $username);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function usuario_query_email(string $email): array|false
    {
        $sql      = "SELECT * FROM " . self::$tabla . " WHERE email = :email LIMIT 1";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':email', $email);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public static function update_last_seen(string $username): void
    {
        $sql      = "UPDATE " . self::$tabla . " SET last_seen = NOW() WHERE username = :username LIMIT 1";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':username', $username);
        $consulta->execute();
    }

    public static function usuario_registrar(array $atributos): void
    {
        $sql = "INSERT INTO " . self::$tabla . "
                (username, nombre, password, email, perfil, token_apertura, pin, confirmado, version, telefono, foto)
                VALUES
                (:username, :nombre, :password, :email, :perfil, :token_apertura, :pin, :confirmado, :version, :telefono, :foto)";
        $consulta = self::$conexionBD->prepare($sql);
        foreach (['username','nombre','password','email','perfil','token_apertura','pin','confirmado','version','telefono','foto'] as $k) {
            $consulta->bindValue(":$k", $atributos[$k] ?? '');
        }
        $consulta->execute();
    }

    public static function tb_update_password(array $atributos): void
    {
        $sql      = "UPDATE " . self::$tabla . " SET password = :password, confirmado = :confirmado WHERE username = :username";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindValue(':username',   $atributos['username']);
        $consulta->bindValue(':password',   $atributos['password']);
        $consulta->bindValue(':confirmado', $atributos['confirmado']);
        $consulta->execute();
    }

    public static function all_reg(): array
    {
        $sql      = "SELECT * FROM " . self::$tabla;
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }
}
