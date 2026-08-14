<?php

namespace Controllers;

use MVC\Router;
use Model\ActiveDB;
use Model\Administrador\Model_apphelperBD_administrador;

/**
 * Setup de primer acceso: crea el usuario Administrador AKIM.
 * Solo funciona si no existe ningún usuario con ese perfil.
 * Ruta: GET/POST /setupAdmin
 */
class Controllers_setup_admin
{
    public static function setup(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

        // Garantizar que las tablas existen
        Model_apphelperBD_administrador::inicialize();

        $db = ActiveDB::getDB();

        // ── Bloquear si ya existe un Administrador AKIM ──────────────────────
        if (self::existeAdminAkim($db)) {
            echo "<script>location.href='loginAdmin';</script>";
            return;
        }

        $errores = [];
        $ok      = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username  = strtoupper(trim($_POST['username']  ?? ''));
            $nombre    = trim($_POST['nombre']    ?? '');
            $email     = trim($_POST['email']     ?? '');
            $telefono  = trim($_POST['telefono']  ?? '');
            $password  = $_POST['password']  ?? '';
            $password2 = $_POST['password2'] ?? '';

            if (!$username)              $errores[] = 'El usuario es obligatorio.';
            if (!$nombre)                $errores[] = 'El nombre completo es obligatorio.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido.';
            if (!$telefono)              $errores[] = 'El teléfono es obligatorio.';
            if (strlen($password) < 8)   $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
            if ($password !== $password2) $errores[] = 'Las contraseñas no coinciden.';

            // Verificar que el username no exista ya
            if (empty($errores)) {
                $chk = $db->prepare("SELECT COUNT(*) FROM sys_usuarios WHERE username = :u");
                $chk->execute([':u' => $username]);
                if ((int)$chk->fetchColumn() > 0) {
                    $errores[] = 'Ese nombre de usuario ya existe.';
                }
            }

            if (empty($errores)) {
                $hash          = password_hash($password, PASSWORD_BCRYPT);
                $nombre_enc    = openssl_encrypt($nombre,           SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $email_enc     = openssl_encrypt($email,            SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $telefono_enc  = openssl_encrypt($telefono,         SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                $perfil_enc    = openssl_encrypt(PERFIL_ADMIN_AKIM, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);

                $db->prepare(
                    "INSERT INTO sys_usuarios
                     (username, password, nombre, email, telefono, perfil, foto, empresa, confirmado, version, created_at)
                     VALUES (:u, :p, :n, :e, :t, :pf, 'default.png', 'SISTEMA', 1, 2, NOW())"
                )->execute([
                    ':u'  => $username,
                    ':p'  => $hash,
                    ':n'  => $nombre_enc,
                    ':e'  => $email_enc,
                    ':t'  => $telefono_enc,
                    ':pf' => $perfil_enc,
                ]);

                $ok = true;
            }
        }

        $router->render('install/setup_admin', [
            'protegida' => false,
            'errores'   => $errores,
            'ok'        => $ok,
        ], 'layout_login');
    }

    private static function existeAdminAkim(\PDO $db): bool
    {
        try {
            $rows = $db->query("SELECT perfil FROM sys_usuarios")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($rows as $perfil_enc) {
                $perfil = openssl_decrypt($perfil_enc, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                if ($perfil === PERFIL_ADMIN_AKIM) return true;
            }
        } catch (\Throwable $e) {
            // tabla aún no existe
        }
        return false;
    }
}
