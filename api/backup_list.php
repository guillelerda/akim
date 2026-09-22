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
 * El token se define en la constante BACKUP_API_TOKEN (en var_session.php).
 * ─────────────────────────────────────────────────────────────
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/app.php';

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

$authHeader = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? '') : '')
           ?? '';

$tokenRecibido = '';
if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) {
    $tokenRecibido = trim($m[1]);
}

if (!hash_equals($tokenEsperado, $tokenRecibido)) {
    resp_json(false, ['error' => 'Token inválido o ausente.'], 401);
}

// ── Solo GET ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    resp_json(false, ['error' => 'Método no permitido.'], 405);
}

// ── Leer BDs desde sys_bases ──────────────────────────────────
try {
    if (!isset($conexionBD) || !($conexionBD instanceof PDO)) {
        resp_json(false, [
            'error'  => 'Conexión a BD no disponible.',
            '_debug' => [
                'conexionBD_isset' => isset($conexionBD),
                'SSL_COD'          => defined('SSL_COD') ? 'SI' : 'NO',
                'BACKUP_API_TOKEN' => defined('BACKUP_API_TOKEN') ? 'SI' : 'NO',
            ],
        ], 500);
    }

    $stmt = $conexionBD->query('
        SELECT id, bd_code, bd_label, bd_name, bd_host, bd_user, bd_pass
        FROM sys_bases
        ORDER BY id ASC
    ');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    resp_json(false, ['error' => 'Error al consultar la base de datos.', '_debug' => $e->getMessage()], 500);
}

// ── Desencriptar credenciales y armar respuesta ───────────────
// bd_code es texto plano en AKIM (no encriptado)
// bd_name, bd_host, bd_user, bd_pass están encriptados con AES-128-CTR
$decrypt = function ($val) {
    if (!is_string($val) || $val === '') return '';
    $d = @openssl_decrypt($val, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
    return ($d !== false && $d !== null) ? $d : '';
};

$lista = [];
foreach ($rows as $row) {
    $lista[] = [
        'id'    => (int)$row['id'],
        'code'  => $row['bd_code'],
        'label' => $row['bd_label'] ?? '',
        'name'  => $decrypt($row['bd_name']),
        'host'  => $decrypt($row['bd_host']),
        'user'  => $decrypt($row['bd_user']),
        'pass'  => $decrypt($row['bd_pass']),
        'port'  => 3306,
        'dest'  => '',
    ];
}

resp_json(true, ['bases' => $lista, 'total' => count($lista)]);
