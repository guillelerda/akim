<?php
/**
 * api/backup_download.php
 * ─────────────────────────────────────────────────────────────
 * Endpoint: POST https://tudominio.com/api/backup_download.php
 * Body:     id=<id_base>
 *
 * Genera el dump SQL de la BD indicada y lo devuelve como
 * stream de descarga. Python lo recibe y guarda en D:\...
 *
 * Seguridad: mismo token Bearer que backup_list.php
 * ─────────────────────────────────────────────────────────────
 */

// Sin output previo — este endpoint solo devuelve el archivo SQL
require_once __DIR__ . '/../includes/app.php';

// ── Helpers ──────────────────────────────────────────────────
function resp_error($msg, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Logger propio
$logFile = __DIR__ . '/../logs/backup_api.log';
$log = function (string $msg) use ($logFile) {
    @file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] [backup_download] ' . $msg . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
};

// ── Autenticación ─────────────────────────────────────────────
$tokenEsperado = defined('BACKUP_API_TOKEN') ? BACKUP_API_TOKEN : null;
if (!$tokenEsperado) {
    resp_error('BACKUP_API_TOKEN no definido.', 500);
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
    $log('Token inválido. Recibido=[' . substr($tokenRecibido, 0, 8) . '...] AuthHeader=[' . substr($authHeader, 0, 20) . ']');
    resp_error('Token inválido o ausente.', 401);
}

// Dumps de BDs grandes pueden tardar varios minutos — extender límite de ejecución PHP
set_time_limit(300);
ini_set('max_execution_time', 300);

// ── Solo POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp_error('Método no permitido.', 405);
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    resp_error('ID inválido.');
}

$log("══════════ INICIO id=[{$id}] ══════════");

// ── Leer registro de BD ───────────────────────────────────────
try {
    if (!isset($conexionBD) || !($conexionBD instanceof PDO)) {
        resp_error('Conexión a BD no disponible.', 500);
    }
    $stmt = $conexionBD->prepare('
        SELECT id, bd_name, bd_host, bd_user, bd_pass
        FROM sys_bases
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $log('Error BD: ' . $e->getMessage());
    resp_error('Error al consultar la base de datos.', 500);
}

if (!$row) {
    $log("Registro id=[{$id}] no encontrado.");
    resp_error('Registro no encontrado.', 404);
}

// ── Desencriptar ──────────────────────────────────────────────
$decrypt = function ($val) {
    if (!is_string($val) || $val === '') return '';
    $d = @openssl_decrypt($val, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
    return ($d !== false && $d !== null) ? $d : '';
};

$host   = $decrypt($row['bd_host']);
$user   = $decrypt($row['bd_user']);
$pass   = $decrypt($row['bd_pass']);
$dbname = $decrypt($row['bd_name']);
$port   = 3306;

$log("host=[{$host}] user=[{$user}] dbname=[{$dbname}] port=[{$port}]");

if (empty($host) || empty($user) || empty($dbname)) {
    $log('ERROR: credenciales incompletas.');
    resp_error('Credenciales incompletas para esta base de datos.', 422);
}
if ($pass === '') {
    $log('ADVERTENCIA: bd_pass vacío para id=[' . $id . ']. Verificar registro en sys_bases.');
}

// ── Generar dump en /tmp ──────────────────────────────────────
$safeDb   = preg_replace('/[^A-Za-z0-9_\-]/', '_', $dbname);
$fileName = sprintf('respaldo_%s_%s.sql', $safeDb, date('Y-m-d_H-i-s'));
$tmpFile  = '/tmp/' . $fileName;

$log("Generando dump → tmpFile=[{$tmpFile}]");
$ok = respaldoPHP($host, $user, $pass, $dbname, $tmpFile, $port);
$log("respaldoPHP=[" . ($ok ? 'TRUE' : 'FALSE') . "]");

if (!$ok || !file_exists($tmpFile) || filesize($tmpFile) === 0) {
    @unlink($tmpFile);
    $log('FALLO: dump no generado o vacío.');
    resp_error('No se pudo generar el dump. Verificá la conexión a la BD remota.', 500);
}

$fileSize = filesize($tmpFile);
$log("Dump OK. Tamaño=[{$fileSize} bytes]. Enviando stream...");

// ── Limpiar buffers y enviar el archivo ───────────────────────
while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-store');
header('X-DB-Name: ' . $safeDb);

readfile($tmpFile);
@unlink($tmpFile);

$log("Stream completado. FIN OK.");
exit;


// ── Función dump PHP puro (sin mysqldump) ─────────────────────
// Copia de respaldoPHP del controlador — autocontenida aquí
// para que la API no dependa de ninguna clase del MVC.
function respaldoPHP($host, $usuario, $password, $base, $rutaArchivo, $port = 3306)
{
    try {
        $mysqli = new mysqli($host, $usuario, $password, $base, $port);
    } catch (\mysqli_sql_exception $e) {
        error_log('[backup_download] Conexión MySQL: ' . $e->getMessage());
        return false;
    }
    if ($mysqli->connect_error) {
        error_log('[backup_download] Conexión MySQL: ' . $mysqli->connect_error);
        return false;
    }
    if (!@$mysqli->set_charset('utf8mb4')) {
        @$mysqli->query("SET NAMES 'utf8mb4'");
    }

    $fh = @fopen($rutaArchivo, 'w');
    if (!$fh) {
        error_log('[backup_download] No se pudo abrir archivo: ' . $rutaArchivo);
        return false;
    }

    $w = static function ($s) use ($fh) { fwrite($fh, $s); };

    $w("-- Dump generado: " . date('Y-m-d H:i:s') . "\n");
    $w("-- Host: {$host} | DB: {$base}\n\n");
    $w("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET time_zone='+00:00';\n");
    $w("/*!40101 SET NAMES utf8mb4 */;\n\n");

    $isNum = static function (int $t): bool {
        return in_array($t, [
            MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_LONGLONG,
            MYSQLI_TYPE_INT24, MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_NEWDECIMAL,
            MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE
        ], true);
    };
    $isBin = static function (int $t): bool {
        return in_array($t, [
            MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_MEDIUM_BLOB,
            MYSQLI_TYPE_BLOB, MYSQLI_TYPE_LONG_BLOB, MYSQLI_TYPE_BIT
        ], true);
    };

    $rsTablas = $mysqli->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")
             ?: $mysqli->query('SHOW TABLES');
    if (!$rsTablas) { fclose($fh); return false; }

    while ($row = $rsTablas->fetch_row()) {
        $tabla = $row[0];

        $resCreate = @$mysqli->query("SHOW CREATE TABLE `{$tabla}`");
        if (!$resCreate) continue;
        $createRow = $resCreate->fetch_row();
        $createSQL = $createRow[1] ?? '';
        if (!$createSQL) continue;

        $w("\n-- Tabla: `{$tabla}`\n");
        $w("DROP TABLE IF EXISTS `{$tabla}`;\n");
        $w($createSQL . ";\n\n");

        $rsCols = $mysqli->query("SELECT * FROM `{$tabla}` LIMIT 0");
        if (!$rsCols) continue;
        $fields   = $rsCols->fetch_fields();
        $colNames = array_map(fn($f) => '`' . str_replace('`','``',$f->name) . '`', $fields);
        $colTypes = array_map(fn($f) => $f->type, $fields);
        $colsSQL  = '(' . implode(',', $colNames) . ')';

        $rsData = $mysqli->query("SELECT * FROM `{$tabla}`", MYSQLI_USE_RESULT);
        if (!$rsData) continue;

        $batch = [];
        while ($dataRow = $rsData->fetch_row()) {
            $vals = [];
            foreach ($dataRow as $i => $val) {
                if ($val === null)                        { $vals[] = 'NULL'; }
                elseif ($isNum($colTypes[$i] ?? 0))       { $vals[] = is_numeric($val) ? (string)$val : "'" . $mysqli->real_escape_string($val) . "'"; }
                elseif ($isBin($colTypes[$i] ?? 0))       { $vals[] = '0x' . bin2hex($val); }
                else                                      { $vals[] = "'" . $mysqli->real_escape_string((string)$val) . "'"; }
            }
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= 500) {
                $w("INSERT INTO `{$tabla}` {$colsSQL} VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        $rsData->free();
        if ($batch) {
            $w("INSERT INTO `{$tabla}` {$colsSQL} VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }

    $w("\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
    fclose($fh);
    return true;
}