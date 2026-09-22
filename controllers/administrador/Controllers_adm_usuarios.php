<?php

namespace Controllers\Administrador;

use MVC\Router;
use Model\Model_apphelper;
use Model\Administrador\Model_usuarios;
use Model\Administrador\Model_links;
use Model\Administrador\Model_licencias;

class Controllers_adm_usuarios
{
    public static function adm_inicio(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $router->render('administrador/adm_inicio', [
            'protegida' => true,
            'auth'      => true,
            'username'  => $userName,
            'nombre'    => $nombre,
            'perfil'    => $perfil,
        ], 'layout_admin');
    }

    public static function adm_lista(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj = new Model_usuarios();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $uname  = trim($_POST['uname'] ?? '');

            if ($accion === 'nuevo') {
                $_SESSION['adm_edit_uname'] = '';
                header('Location: adm_usuarios_edit'); exit;
            }
            if ($accion === 'editar' && $uname !== '') {
                $_SESSION['adm_edit_uname'] = $uname;
                header('Location: adm_usuarios_edit'); exit;
            }
            if ($accion === 'licencias' && $uname !== '') {
                $_SESSION['adm_edit_uname'] = $uname;
                header('Location: adm_usuarios_licencias'); exit;
            }
            if ($accion === 'toggle' && $uname !== '') {
                $obj->toggle($uname);
            }
            if ($accion === 'eliminar' && $uname !== '' && $uname !== $userName) {
                $obj->eliminar($uname);
                $_SESSION['flash_ok'] = 'Usuario eliminado.';
            }
            header('Location: adm_usuarios'); exit;
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $raw   = $obj->queryAll();
        $lista = [];
        foreach ($raw as $u) {
            $u['nombre_dec'] = openssl_decrypt($u['nombre'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
            $u['email_dec']  = openssl_decrypt($u['email']  ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
            $u['perfil_dec'] = openssl_decrypt($u['perfil'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
            $lista[]         = $u;
        }

        $router->render('administrador/adm_usuarios', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'lista'       => $lista,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ], 'layout_admin');
    }

    public static function adm_edit(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $obj      = new Model_usuarios();
        $uname    = trim($_SESSION['adm_edit_uname'] ?? '');
        $es_nuevo = ($uname === '');
        $usuario  = null;
        $errores  = [];

        if (!$es_nuevo) {
            $raw = $obj->getByUsername($uname);
            if ($raw) {
                $raw['nombre_dec'] = openssl_decrypt($raw['nombre'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $raw['email_dec']  = openssl_decrypt($raw['email']  ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $raw['perfil_dec'] = openssl_decrypt($raw['perfil'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $usuario = $raw;
            } else {
                $es_nuevo = true;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;

            if ($es_nuevo && empty(trim($data['username'] ?? ''))) $errores[] = 'El nombre de usuario es obligatorio.';
            if (empty(trim($data['nombre']   ?? '')))              $errores[] = 'El nombre es obligatorio.';
            if (empty(trim($data['email']    ?? '')))              $errores[] = 'El email es obligatorio.';
            if (empty(trim($data['perfil']   ?? '')))              $errores[] = 'El perfil es obligatorio.';
            if ($es_nuevo && empty(trim($data['password'] ?? ''))) $errores[] = 'La contraseña es obligatoria para usuarios nuevos.';

            if ($es_nuevo && !empty(trim($data['username'] ?? ''))
                && $obj->usernameExiste(strtolower(trim($data['username'])))) {
                $errores[] = 'El nombre de usuario ya existe.';
            }

            if (empty($errores)) {
                if ($es_nuevo) {
                    $obj->insertar($data);
                    $_SESSION['flash_ok'] = 'Usuario creado correctamente.';
                } else {
                    $data['username'] = $uname;
                    $obj->actualizar($data);
                    $_SESSION['flash_ok'] = 'Usuario actualizado correctamente.';
                }
                header('Location: adm_usuarios'); exit;
            }

            $usuario = [
                'username'   => $data['username'] ?? $uname,
                'nombre_dec' => $data['nombre']   ?? '',
                'email_dec'  => $data['email']    ?? '',
                'telefono'   => $data['telefono'] ?? '',
                'perfil_dec' => $data['perfil']   ?? '',
                'confirmado' => isset($data['confirmado']) ? 1 : 0,
            ];
        }

        $router->render('administrador/adm_usuarios_edit', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'usuario'     => $usuario,
            'es_nuevo'    => $es_nuevo,
            'errores'     => $errores,
        ], 'layout_admin');
    }

    public static function adm_licencias_usuario(Router $router): void
    {
        [$userName, $mi_usuario, $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $uname = trim($_SESSION['adm_edit_uname'] ?? '');
        if ($uname === '') {
            header('Location: adm_usuarios'); exit;
        }

        $obj_usr  = new Model_usuarios();
        $obj_lnk  = new Model_links();
        $obj_lic  = new Model_licencias();

        $raw = $obj_usr->getByUsername($uname);
        if (!$raw) { header('Location: adm_usuarios'); exit; }

        $raw['nombre_dec'] = openssl_decrypt($raw['nombre'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
        $raw['perfil_dec'] = openssl_decrypt($raw['perfil'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
        $usuario = $raw;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'asignar') {
                $lic_code = trim($_POST['lic_code'] ?? '');
                $em_code  = trim($_POST['em_code']  ?? '');
                if ($lic_code !== '' && $em_code !== '') {
                    try {
                        $obj_lnk->crear([
                            'username'   => $uname,
                            'licencia'   => $lic_code,
                            'em_code'    => $em_code,
                            'habilitada' => 1,
                        ]);
                        $_SESSION['flash_ok'] = "Licencia «{$lic_code}» asignada.";
                    } catch (\Throwable $e) {
                        $_SESSION['flash_error'] = 'Error al asignar: ' . $e->getMessage();
                    }
                }
            }
            if ($accion === 'toggle') {
                $obj_lnk->toggleHabilitada((int)($_POST['link_id'] ?? 0));
            }
            if ($accion === 'quitar') {
                $obj_lnk->eliminar((int)($_POST['link_id'] ?? 0));
                $_SESSION['flash_ok'] = 'Licencia desvinculada.';
            }
            header('Location: adm_usuarios_licencias'); exit;
        }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $asignaciones   = $obj_lnk->getByUsername($uname);
        $disponibles    = $obj_lic->getLicenciasDisponibles($uname);

        $router->render('administrador/adm_usuarios_licencias', [
            'protegida'   => true,
            'auth'        => true,
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'usuario'     => $usuario,
            'asignaciones'=> $asignaciones,
            'disponibles' => $disponibles,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ], 'layout_admin');
    }
}
