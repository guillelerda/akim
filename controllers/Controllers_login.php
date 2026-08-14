<?php

namespace Controllers;

use MVC\Router;
use Model\Model_login;
use Model\Administrador\Model_usuarios;
use Classes\Telegram;

class Controllers_login
{
    // ── Login usuario normal ───────────────────────────────────────────────────
    public static function login(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obj_login           = new Model_login($_POST);
            $obj_login->username = mb_strtoupper(trim($_POST['username'] ?? ''), 'UTF-8');
            $errores             = $obj_login->validar();

            if (empty($errores)) {
                $data_usuario = $obj_login->existeUsuario();
                if ($data_usuario) {
                    if ($data_usuario['confirmado'] == 1) {
                        $obj_login->comprobarPassword($_POST['password'] ?? '', $data_usuario);
                        if ($obj_login->autenticado) {
                            $obj_login->autenticar();
                        } else {
                            $errores[] = 'La contraseña es incorrecta.';
                        }
                    } else {
                        $errores[] = 'El usuario aún no está confirmado. Contacte al administrador.';
                    }
                }
            }
        }

        $router->render('login/login', ['errores' => $errores], 'layout_login');
    }

    // ── Login administrador AKIM (con 2FA Telegram) ───────────────────────────
    public static function loginAdmin(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = mb_strtoupper(trim($_POST['username'] ?? ''), 'UTF-8');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $password === '') {
                $errores[] = 'Ingresá usuario y contraseña.';
            }

            if (empty($errores)) {
                $obj_login           = new Model_login(['username' => $username, 'password' => $password]);
                $obj_login->username = $username;
                $data_usuario        = $obj_login->existeUsuario();

                if (!$data_usuario) {
                    $errores[] = 'Usuario no encontrado.';
                } else {
                    // Verificar que sea administrador de la plataforma
                    $perfil = openssl_decrypt(
                        $data_usuario['perfil'] ?? '',
                        SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV
                    );

                    if ($perfil !== PERFIL_ADMIN_AKIM) {
                        $errores[] = 'Esta cuenta no tiene perfil de administrador.';
                    } elseif ($data_usuario['confirmado'] != 1) {
                        $errores[] = 'El usuario no está confirmado.';
                    } else {
                        $obj_login->comprobarPassword($password, $data_usuario);
                        if (!$obj_login->autenticado) {
                            $errores[] = 'La contraseña es incorrecta.';
                        } else {
                            // Generar token 2FA (8 dígitos)
                            $token  = str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

                            $_SESSION['usuario']        = $username;
                            $_SESSION['login']          = false;  // aún no autenticado del todo
                            $_SESSION['admin_token']    = $token;
                            $_SESSION['admin_token_exp']= time() + 600; // 10 min

                            // Enviar por Telegram
                            $msg = "🔐 AKIM — Token de acceso admin:\n\n"
                                 . "{$token}\n\n"
                                 . "Usuario: {$username}\n"
                                 . "Expira en 10 minutos.";

                            $telegram_ok = false;
                            if (TELEGRAM_BOT_TOKEN !== '' && TELEGRAM_CHAT_ID !== '') {
                                $telegram = new Telegram(TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID);
                                $envio    = $telegram->sendToDefault($msg);
                                $telegram_ok = $envio['success'];
                                if (!$telegram_ok) {
                                    error_log('Telegram 2FA admin: ' . ($envio['error'] ?? 'error desconocido'));
                                }
                            }

                            // En desarrollo local, guardar el token en sesión para mostrarlo en pantalla
                            if (A_HTTP === 'L' && !$telegram_ok) {
                                $_SESSION['admin_token_debug'] = $token;
                            }

                            echo "<script>location.href='tokenAdmin';</script>";
                            return;
                        }
                    }
                }
            }
        }

        $router->render('login/login_admin', ['errores' => $errores], 'layout_login');
    }

    // ── Verificación del token 2FA ─────────────────────────────────────────────
    public static function tokenAdmin(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $errores = [];

        // Si no hay sesión pendiente de admin, volver al login admin
        if (empty($_SESSION['admin_token'])) {
            echo "<script>location.href='loginAdmin';</script>";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token_post = trim($_POST['token'] ?? '');

            if (strlen($token_post) !== 8 || !ctype_digit($token_post)) {
                $errores[] = 'El token debe tener exactamente 8 dígitos numéricos.';
            } elseif (time() > ($_SESSION['admin_token_exp'] ?? 0)) {
                $errores[] = 'El token ha vencido. Volvé a iniciar sesión.';
                unset($_SESSION['admin_token'], $_SESSION['admin_token_exp']);
            } elseif ($token_post !== $_SESSION['admin_token']) {
                $errores[] = 'El token ingresado no es correcto.';
            } else {
                // Token válido → acceso completo al panel admin
                unset($_SESSION['admin_token'], $_SESSION['admin_token_exp']);
                $_SESSION['login']       = true;
                $_SESSION['loginADMIN']  = true;

                echo "<script>location.href='adminPanel';</script>";
                return;
            }
        }

        $router->render('login/token_admin', ['errores' => $errores], 'layout_login');
    }

    // ── Logout ────────────────────────────────────────────────────────────────
    public static function logout(Router $router): void
    {
        session_destroy();
        echo "<script>location.href='login';</script>";
    }

    public static function logoutAdmin(Router $router): void
    {
        session_destroy();
        echo "<script>location.href='loginAdmin';</script>";
    }
}
