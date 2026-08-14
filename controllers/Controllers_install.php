<?php

namespace Controllers;

use MVC\Router;
use Model\ActiveDB;
use Model\Administrador\Model_apphelperBD_administrador;

class Controllers_install
{
    public static function install(Router $router): void
    {
        // 1. Crear tablas si no existen
        Model_apphelperBD_administrador::inicialize();

        $db = ActiveDB::getDB();

        // 2. Verificar si ya existe algún usuario — si sí, redirigir al login
        $check = $db->query("SELECT COUNT(*) FROM sys_usuarios")->fetchColumn();
        if ((int)$check > 0) {
            echo "<script>location.href='login';</script>";
            return;
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ── Validaciones ──────────────────────────────────────────────────
            $em_nombre = trim($_POST['em_nombre'] ?? '');
            $em_cuit   = trim($_POST['em_cuit']   ?? '');
            $username  = strtoupper(trim($_POST['username'] ?? ''));
            $nombre    = trim($_POST['nombre']    ?? '');
            $email     = trim($_POST['email']     ?? '');
            $password  = $_POST['password']  ?? '';
            $password2 = $_POST['password2'] ?? '';

            if (!$em_nombre) $errores[] = 'El nombre de la empresa es obligatorio.';
            if (!$username)  $errores[] = 'El usuario es obligatorio.';
            if (!$nombre)    $errores[] = 'El nombre completo es obligatorio.';
            if (!$email)     $errores[] = 'El email es obligatorio.';
            if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
            if ($password !== $password2) $errores[] = 'Las contraseñas no coinciden.';

            if (empty($errores)) {
                // ── Crear empresa ─────────────────────────────────────────────
                $em_code = 'EMP001';
                $em_nombre_enc = openssl_encrypt($em_nombre, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $db->prepare(
                    "INSERT INTO sys_empresas (em_code, em_nombre, em_cuit) VALUES (:code, :nombre, :cuit)"
                )->execute([':code' => $em_code, ':nombre' => $em_nombre_enc, ':cuit' => $em_cuit]);

                // ── Crear licencia ────────────────────────────────────────────
                $lic_empresa_enc = openssl_encrypt($em_code, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $lic_code = 'LIC-' . strtoupper(substr(md5($em_code . time()), 0, 8));
                $db->prepare(
                    "INSERT INTO sys_licencias (lic_code, lic_empresa_cod, habilitada) VALUES (:code, :emp, 1)"
                )->execute([':code' => $lic_code, ':emp' => $lic_empresa_enc]);

                // ── Crear usuario admin ───────────────────────────────────────
                $hash        = password_hash($password, PASSWORD_BCRYPT);
                $nombre_enc  = openssl_encrypt($nombre, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $email_enc   = openssl_encrypt($email,  SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $perfil_enc  = openssl_encrypt('admin', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);

                $db->prepare(
                    "INSERT INTO sys_usuarios
                     (username, password, nombre, email, perfil, foto, empresa, confirmado, version)
                     VALUES (:u, :p, :n, :e, :pf, 'default.png', :emp, 1, 1)"
                )->execute([
                    ':u'   => $username,
                    ':p'   => $hash,
                    ':n'   => $nombre_enc,
                    ':e'   => $email_enc,
                    ':pf'  => $perfil_enc,
                    ':emp' => $em_code,
                ]);

                // ── Vincular usuario a licencia (sys_links) ───────────────────
                try {
                    $db->prepare(
                        "INSERT INTO sys_links (username, licencia, em_code, habilitada)
                         VALUES (:u, :lic, :em, 1)"
                    )->execute([':u' => $username, ':lic' => $lic_code, ':em' => $em_code]);
                } catch (\Throwable $e) { /* ya existe — ignorar */ }

                // ── Autenticar y redirigir ────────────────────────────────────
                $_SESSION['usuario']    = $username;
                $_SESSION['login']      = true;
                $_SESSION['loginADMIN'] = false;
                $_SESSION['licencia']   = $lic_code;

                echo "<script>location.href='seleccionarEmpresa';</script>";
                return;
            }
        }

        $router->render('install/install', [
            'protegida' => false,
            'errores'   => $errores,
        ], 'layout_login');
    }
}
