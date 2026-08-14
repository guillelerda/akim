<?php

// ── Entorno ───────────────────────────────────────────────────────────────────
// ############################################
define('A_HTTP', 'L');
// define('A_HTTP', 'W');  // activar solo en producción
// ############################################

define('CARPETA_IMAGENES',          $_SERVER['DOCUMENT_ROOT'] . '/public/imagenes/');
define('CARPETA_IMAGENES_USUARIOS', 'public/imagenes/usuarios/');
define('CARPETA_IMAGENES_EMPRESAS', 'public/imagenes/empresas/');
define('CARPETA_IMAGENES_PRODUCTOS','public/imagenes/productos/');

// ── Debug helpers ─────────────────────────────────────────────────────────────
function mostrame($data): void
{
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:6px;font-size:.85rem">';
    print_r($data);
    echo '</pre>';
}

function mostrarSESSION(): void
{
    mostrame($_SESSION);
}

// ── Cadenas ───────────────────────────────────────────────────────────────────
function generate_string(int $length = 10): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

function limpiar_cadena(string $cadena): string
{
    $cadena = trim($cadena);
    $bloqueos = [
        '<script>', '</script>', '<?php', '?>', 'DROP TABLE',
        'DROP DATABASE', 'DELETE FROM', 'INSERT INTO', 'SELECT * FROM',
        '--', '>>', '<<',
    ];
    foreach ($bloqueos as $b) {
        $cadena = str_ireplace($b, '', $cadena);
    }
    return htmlspecialchars($cadena, ENT_QUOTES, 'UTF-8');
}

// ── Directorios ───────────────────────────────────────────────────────────────
function verificar_carpeta(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

// ── Respuestas JSON (AJAX / JIT) ──────────────────────────────────────────────
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Conexión BD cliente (multi-tenant) ───────────────────────────────────────
function conectarDB2(): \PDO
{
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

    $host     = openssl_decrypt($_SESSION['data_licencia']['lic_host']    ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
    $database = openssl_decrypt($_SESSION['data_licencia']['lic_bd_name'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
    $username = openssl_decrypt($_SESSION['data_licencia']['lic_bd_user'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
    $password = openssl_decrypt($_SESSION['data_licencia']['lic_bd_pass'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);

    $opciones = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci",
    ];

    try {
        return new \PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password, $opciones);
    } catch (\PDOException $e) {
        error_log('conectarDB2() error: ' . $e->getMessage());
        throw new \RuntimeException('No se puede conectar a la base de datos del cliente: ' . $e->getMessage());
    }
}

// ── Fechas ───────────────────────────────────────────────────────────────────
function fecha_hoy(): string
{
    return date('Y-m-d');
}

function fecha_hora_ahora(): string
{
    return date('Y-m-d H:i:s');
}

function fecha_display(string $fecha): string
{
    if (!$fecha) return '--';
    $d = DateTime::createFromFormat('Y-m-d', substr($fecha, 0, 10));
    return $d ? $d->format('d/m/Y') : $fecha;
}
