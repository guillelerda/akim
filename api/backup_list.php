<?php
/**
 * api/backup_list.php
 * ─────────────────────────────────────────────────────────────
 * Endpoint: GET https://tudominio.com/api/backup_list.php
 *
 * Devuelve JSON con la lista de todas las BDs registradas,
 * con credenciales desencriptadas (PHP maneja las claves SSL,
 * Python nunca las necesita conocer).
 *
 * Seguridad: token Bearer en header Authorization.
 * El token se define en la constante BACKUP_API_TOKEN (en config o .env).
 * ─────────────────────────────────────────────────────────────
 */

header('Content-Type: application/json; charset=UTF-8');

// ── Incluir config de la app (conexión BD + constantes SSL) ──
// Ajustá la ruta si tu config está en otro lugar
require_once __DIR__ . '/../includes/app.php';

// ── Helpers ──────────────────────────────────────────────────
function resp_json($ok, $data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Autenticación por token ───────────────────────────────────
$tokenEsperado = defined('BACKUP_API_TOKEN') ? BACKUP_API_TOKEN : null;

if (!$tokenEsperado) {
    resp_json(false, ['error' => 'BACKUP_API_TOKEN no definido en el servidor.'], 500);
}

// ── DEBUG TEMPORAL — eliminar después de verificar ────────────
// Captura TODOS los headers recibidos para diagnosticar por qué falla el token
$debugHeaders = [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0 || $k === 'REDIRECT_HTTP_AUTHORIZATION') {
        $debugHeaders[$k] = $v;
    }
}
$allHeaders = function_exists('getallheaders') ? getallheaders() : [];
// ── FIN DEBUG ─────────────────────────────────────────────────

$authHeader = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? '') : '')
           ?? '';

$tokenRecibido = '';
if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
    $tokenRecibido = trim($m[1]);
}

if (!hash_equals($tokenEsperado, $tokenRecibido)) {
    // Respuesta con debug para diagnosticar
    resp_json(false, [
        'error'          => 'Token inválido o ausente.',
        '_debug' => [
            'auth_header_found'  => $authHeader,
            'token_recibido'     => substr($tokenRecibido, 0, 6) . '...',
            'token_esperado_ini' => substr($tokenEsperado,  0, 6) . '...',
            'server_keys'        => $debugHeaders,
            'getallheaders'      => $allHeaders,
        ],
    ], 401);
}

// ── Solo GET ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    resp_json(false, ['error' => 'Método no permitido.'], 405);
}

// ── Leer BDs desde la misma BD de la app ─────────────────────
try {
    if (!isset($conexionBD) || !($conexionBD instanceof PDO)) {
        resp_json(false, [
            'error'  => 'Conexión a BD no disponible.',
            '_debug' => [
                'conexionBD_isset'  => isset($conexionBD),
                'conexionBD_tipo'   => isset($conexionBD) ? get_class($conexionBD) : 'no definida',
                'defined_constants' => [
                    'SSL_COD' => defined('SSL_COD') ? 'SI' : 'NO',
                    'SSL_KEY' => defined('SSL_KEY') ? 'SI' : 'NO',
                    'BACKUP_API_TOKEN' => defined('BACKUP_API_TOKEN') ? 'SI' : 'NO',
                ],
            ],
        ], 500);
    }

    $stmt = $conexionBD->query('
        SELECT id, bd_code, bd_name, bd_host, bd_user, bd_pass, bd_resp
        FROM sys_bases
        WHERE bd_activa = \'SI\'
        ORDER BY id ASC
    ');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    resp_json(false, [
        'error'  => 'Error al consultar la base de datos.',
        '_debug' => [
            'exception'  => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
        ],
    ], 500);
}

// ── Desencriptar credenciales y armar respuesta ───────────────
$decrypt = function ($val) {
    if (!is_string($val) || $val === '') return '';
    $d = @openssl_decrypt($val, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
    return ($d !== false && $d !== null) ? $d : '';
};

$lista = [];
foreach ($rows as $row) {
    $lista[] = [
        'id'   => (int)$row['id'],
        'code' => $decrypt($row['bd_code']),
        'name' => $decrypt($row['bd_name']),
        'host' => $decrypt($row['bd_host']),
        'user' => $decrypt($row['bd_user']),
        'pass' => $decrypt($row['bd_pass']),
        'port' => 3306,
        'dest' => $row['bd_resp'] ?? '',
    ];
}

resp_json(true, ['bases' => $lista, 'total' => count($lista)]);