<?php

namespace Model;

use Data\Administrador\sys_usuarios;

class Model_login extends ActiveDB
{
    protected static $columnasDB = ['id', 'username', 'password'];

    public $id;
    public $username;
    public $password;
    public $autenticado = false;

    protected static $errores = [];

    public function __construct(array $args = [])
    {
        $this->id       = $args['id']       ?? null;
        $this->username = $args['username'] ?? '';
        $this->password = $args['password'] ?? '';
    }

    public function validar(): array
    {
        self::$errores = [];
        if (!$this->username) self::$errores[] = 'El usuario es obligatorio.';
        if (!$this->password) self::$errores[] = 'La contraseña es obligatoria.';
        return self::$errores;
    }

    public static function getErrores(): array
    {
        return static::$errores;
    }

    public function existeUsuario(): array|false
    {
        $obj    = new sys_usuarios();
        $obj->setDB(self::$conexionBD);
        $result = $obj->usuario_query_username($this->username);
        if (!$result) {
            self::$errores[] = 'El usuario no existe.';
            return false;
        }
        return $result;
    }

    public function comprobarPassword(string $password, array $data_usuario): void
    {
        if (empty($data_usuario['password'])) {
            self::$errores[] = 'Datos de usuario inválidos.';
            $this->autenticado = false;
            return;
        }
        $this->autenticado = password_verify($password, $data_usuario['password']);
        if (!$this->autenticado) {
            self::$errores[] = 'La contraseña es incorrecta.';
        }
    }

    public function autenticar(): void
    {
        $_SESSION['usuario']    = $this->username;
        $_SESSION['login']      = true;
        $_SESSION['loginADMIN'] = false;
        echo "<script>location.href='menu';</script>";
    }
}
