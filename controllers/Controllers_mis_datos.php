<?php

namespace Controllers;

use MVC\Router;
use Model\Model_apphelper;
use Model\Model_parametros;
use Model\Administrador\Model_usuarios;

class Controllers_mis_datos
{
    public static function index(Router $router): void
    {
        [$userName, $usuario, $nombre, $perfil, $email] = Model_apphelper::getUsuarioAutenticado();

        $flash_ok    = $_SESSION['flash_ok']    ?? ''; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? ''; unset($_SESSION['flash_error']);
        $tab         = $_GET['tab'] ?? 'empresa';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            // ── Guardar datos de empresa ────────────────────────────────────
            if ($accion === 'empresa') {
                $campos = [
                    'empresa_razon_social',
                    'empresa_domicilio',
                    'empresa_localidad',
                    'empresa_provincia',
                    'empresa_telefono',
                    'empresa_email',
                    'empresa_inicio_actividad',
                    'empresa_iibb',
                ];
                foreach ($campos as $clave) {
                    Model_parametros::set($clave, trim($_POST[$clave] ?? ''));
                }

                // Logo
                if (!empty($_FILES['empresa_logo']['tmp_name']) && $_FILES['empresa_logo']['error'] === UPLOAD_ERR_OK) {
                    $ext      = strtolower(pathinfo($_FILES['empresa_logo']['name'], PATHINFO_EXTENSION));
                    $allowed  = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
                    if (in_array($ext, $allowed)) {
                        $dir = dirname(__DIR__) . '/public/logo/';
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $filename = 'logo.' . $ext;
                        move_uploaded_file($_FILES['empresa_logo']['tmp_name'], $dir . $filename);
                        Model_parametros::set('empresa_logo', 'public/logo/' . $filename);
                    }
                }

                $_SESSION['flash_ok'] = 'Datos de empresa guardados.';
                echo "<script>location.href='mis_datos?tab=empresa';</script>";
                return;
            }

            // ── Cambio de contraseña ────────────────────────────────────────
            if ($accion === 'password') {
                $actual    = $_POST['pwd_actual']    ?? '';
                $nueva     = $_POST['pwd_nueva']     ?? '';
                $confirmar = $_POST['pwd_confirmar'] ?? '';

                if ($nueva === '' || strlen($nueva) < 6) {
                    $_SESSION['flash_error'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
                } elseif ($nueva !== $confirmar) {
                    $_SESSION['flash_error'] = 'Las contraseñas no coinciden.';
                } else {
                    // Verificar contraseña actual
                    $obj = new Model_usuarios();
                    $raw = $obj->query_username($userName);
                    if ($raw && password_verify($actual, $raw['password'] ?? '')) {
                        // Actualizar
                        $hash = password_hash($nueva, PASSWORD_DEFAULT);
                        \Model\ActiveDB::getDB()->prepare(
                            "UPDATE sys_usuarios SET password = ? WHERE username = ?"
                        )->execute([$hash, $userName]);
                        $_SESSION['flash_ok'] = 'Contraseña actualizada correctamente.';
                    } else {
                        $_SESSION['flash_error'] = 'La contraseña actual no es correcta.';
                    }
                }
                echo "<script>location.href='mis_datos?tab=cuenta';</script>";
                return;
            }
        }

        $params      = Model_parametros::getAll();
        $logo_exists = !empty($params['empresa_logo'])
            && file_exists(dirname(__DIR__) . '/' . $params['empresa_logo']);

        $router->render('mis_datos', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Mis Datos',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'email'       => $email,
            'params'      => $params,
            'logo_exists' => $logo_exists,
            'tab'         => $tab,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }
}
