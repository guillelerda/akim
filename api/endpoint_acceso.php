<?php
// ============================================================
//  endpoint_acceso.php
//  API móvil — Módulo RRHG: Control de Acceso por Geovallas.
//
//    400  FICHAR               — registra ingreso/egreso validado por geoposición
//    401  VERIFICAR_HABILITACION — solo lectura; la app decide si mostrar
//                                  la sección de fichaje en el dashboard
//    402  GEOVALLAS_ACTIVAS    — solo lectura; geovallas habilitadas + tipo de
//                                ubicación para mostrarle al usuario "dónde está"
//                                ANTES de fichar (informativo, no es la validación real)
//
//  Autenticación: PIN + TOKEN (mismo mecanismo que endpoint_rrhg.php).
//  El servidor resuelve el username desde sys_moviles — nunca se confía
//  en un username enviado por el cliente.
//
//  La validación geométrica (punto_en_geovalla) está duplicada a propósito:
//  esta capa no puede depender de conectarDB2() que requiere $_SESSION.
//  Si se ajusta la geometría en Model_rrhg001_geovallas.php, replicar acá.
// ============================================================

// ── CORS ─────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$MODO   = 'produccion';

$allowed_origins = [
    'capacitor://localhost', 'ionic://localhost',
    'https://localhost',
    'http://localhost', 'http://localhost:8100', 'http://localhost:4200',
];
if (in_array($origin, $allowed_origins) || $MODO === 'desarrollo') {
    header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
} else {
    header('Access-Control-Allow-Origin: https://localhost');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── LOG ──────────────────────────────────────────────────────
$LOG_FILE = __DIR__ . '/acceso_log.txt';
function log_acceso(string $msg): void {
    global $LOG_FILE;
    file_put_contents($LOG_FILE, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND | LOCK_EX);
}

$CAMPOS_SENS = ['DB2_USER', 'DB2_PASS', 'DB2_NAME', 'PIN', 'TOKEN', 'FOTO'];
function san_log_ac(array $d, array $c): array {
    foreach ($c as $k) { if (array_key_exists($k, $d)) $d[$k] = '***'; }
    return $d;
}

// ── Configuración centralizada ────────────────────────────────
require_once __DIR__ . '/config.php';
define('ACCESO_API_KEY', $API_KEY);
define('ACCESO_DB_HOST', $DB_ADDRESS);
define('ACCESO_DB_USER', $DB_USER);
define('ACCESO_DB_PASS', $DB_PASS);
define('ACCESO_DB_NAME', $DB_NAME);

function responder_ac(array $d, int $h = 200): void {
    http_response_code($h);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function error_ac(string $m, int $h = 400): void {
    log_acceso("ERROR: $m");
    responder_ac(['error' => $m], $h);
}

// ── CONECTAR DB2 (empresa del cliente) ────────────────────────
function db2_ac(): mysqli {
    $h = trim($_POST['DB2_ADDRESS'] ?? '');
    $u = trim($_POST['DB2_USER']    ?? '');
    $p = trim($_POST['DB2_PASS']    ?? '');
    $d = trim($_POST['DB2_NAME']    ?? '');
    if (!$h || !$u || !$d) {
        log_acceso("DB2_ERROR: parámetros incompletos host=$h db=$d user=$u");
        error_ac('Parámetros DB2 incompletos');
    }
    $link = new mysqli($h, $u, $p, $d);
    if ($link->connect_error) {
        log_acceso("DB2_ERROR: no se pudo conectar a host=$h db=$d — " . $link->connect_error);
        error_ac('Error DB2: ' . $link->connect_error, 503);
    }
    $link->set_charset('utf8mb4');
    return $link;
}

// ── RESOLVER USUARIO DESDE PIN+TOKEN (DB principal) ───────────
// Nunca se confía en un USERNAME enviado por el cliente.
// Devuelve ['username' => string, 'habilitado' => 'SI'|'NO'] o null.
function resolver_movil_ac(string $pin, string $token): ?array {
    $link = new mysqli(ACCESO_DB_HOST, ACCESO_DB_USER, ACCESO_DB_PASS, ACCESO_DB_NAME);
    if ($link->connect_error) {
        log_acceso("ERROR conectando a DB principal: " . $link->connect_error);
        error_ac('Error de verificación de dispositivo. Intentá nuevamente.', 503);
    }
    $pinEsc   = $link->real_escape_string($pin);
    $tokenEsc = $link->real_escape_string($token);
    $res = $link->query(
        "SELECT username, habilitado FROM sys_moviles
         WHERE pin = '$pinEsc' AND token = '$tokenEsc' LIMIT 1"
    );
    $row = $res ? $res->fetch_assoc() : null;
    $link->close();
    return $row ?: null;
}

// ── GEOMETRÍA — copiado de Model_rrhg001_geovallas.php ────────
function punto_en_poligono_ac(float $lat, float $lng, array $poligono): bool {
    $dentro = false;
    $n = count($poligono);
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $latI = (float)$poligono[$i][0]; $lngI = (float)$poligono[$i][1];
        $latJ = (float)$poligono[$j][0]; $lngJ = (float)$poligono[$j][1];
        $interseca = (($lngI > $lng) !== ($lngJ > $lng))
            && ($lat < ($latJ - $latI) * ($lng - $lngI) / ($lngJ - $lngI) + $latI);
        if ($interseca) { $dentro = !$dentro; }
    }
    return $dentro;
}

function punto_en_circulo_ac(float $lat, float $lng, float $cLat, float $cLng, float $radioM): bool {
    $R   = 6371000;
    $dLat = deg2rad($cLat - $lat);
    $dLng = deg2rad($cLng - $lng);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($cLat)) * sin($dLng / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a)) <= $radioM;
}

function punto_en_geovalla_ac(float $lat, float $lng, array $geovalla): bool {
    $geo = json_decode($geovalla['geo_json'], true);
    if (!is_array($geo)) return false;
    if ($geovalla['tipo'] === 'POLIGONO') {
        return punto_en_poligono_ac($lat, $lng, $geo);
    }
    if ($geovalla['tipo'] === 'CIRCULO') {
        return punto_en_circulo_ac(
            $lat, $lng,
            (float)($geo['center']['lat'] ?? 0),
            (float)($geo['center']['lng'] ?? 0),
            (float)($geo['radio_m'] ?? 0)
        );
    }
    return false;
}

// ── GUARDAR FOTO (best-effort) ─────────────────────────────────
function guardar_foto_ac(string $licencia, string $idx_fichaje, string $base64): array {
    if (trim($base64) === '') return [null, 'Sin datos'];
    $b64 = str_replace(' ', '+', preg_replace('#^data:image/\w+;base64,#i', '', $base64));
    $bin = base64_decode($b64);
    if ($bin === false) return [null, 'Error al decodificar base64'];
    $lic  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $licencia) ?: 'sin_licencia';
    $dir  = __DIR__ . "/public/repositorio/$lic/rrhg_fichajes/";
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $nombre = 'fichaje_' . strtolower($idx_fichaje) . '.jpg';
    $bytes  = @file_put_contents($dir . $nombre, $bin);
    if ($bytes === false) return [null, "Error al guardar imagen en $dir$nombre"];
    return [$nombre, null];
}

// ── MENSAJES AMIGABLES ──────────────────────────────────────────
const MENSAJES_RECHAZO_AC = [
    'FUERA_DE_GEOVALLA'     => 'No estás dentro de un área habilitada para fichar. Acercate a la zona correspondiente e intentá nuevamente.',
    'EMPLEADO_INHABILITADO' => 'Tu usuario no está habilitado para fichar. Contactá a RRHH.',
    'EMPLEADO_NO_PARTICIPA' => 'Tu perfil no está habilitado para control de acceso por app. Contactá a RRHH.',
    'EGRESO_SIN_INGRESO'    => 'No podés registrar un egreso sin haber registrado un ingreso previo.',
    'INGRESO_YA_ACTIVO'     => 'Ya tenés un ingreso registrado. Registrá el egreso antes de volver a ingresar.',
];

// ── AUTENTICACIÓN COMÚN ────────────────────────────────────────
$api_key = $_POST['API_KEY'] ?? $_GET['API_KEY'] ?? null;
if ($api_key !== ACCESO_API_KEY) error_ac('Acceso no autorizado', 401);

$accion = trim($_POST['accion'] ?? '');
log_acceso("accion=$accion | " . json_encode(san_log_ac($_POST, $CAMPOS_SENS)));
if (!$accion) error_ac('Accion requerida');

$pin   = trim($_POST['PIN']   ?? '');
$token = trim($_POST['TOKEN'] ?? '');
if (!$pin || !$token) error_ac("Faltan parámetros 'PIN' o 'TOKEN'");

$movil = resolver_movil_ac($pin, $token);
if (!$movil) {
    log_acceso("DISPOSITIVO NO ENCONTRADO: pin/token inválidos");
    error_ac('DISPOSITIVO_BLOQUEADO', 403);
}
if ($movil['habilitado'] !== 'SI') {
    log_acceso("DISPOSITIVO BLOQUEADO: username={$movil['username']}");
    error_ac('DISPOSITIVO_BLOQUEADO', 403);
}
$username = $movil['username'];

switch ($accion) {

    // ══════════════════════════════════════════════════════════
    // ACCION 400 — FICHAR
    // Registra un ingreso o egreso validado por geoposición.
    // Siempre graba el intento (OK o RECHAZADO) para auditoría inmutable.
    // La foto (selfie) se guarda best-effort y nunca bloquea el fichaje.
    // ══════════════════════════════════════════════════════════
    case "400":
        $tipo              = trim($_POST['TIPO']         ?? '');
        $lat               = $_POST['LAT']              ?? null;
        $lng               = $_POST['LNG']              ?? null;
        $licencia          = trim($_POST['LICENCIA']    ?? '');
        $foto_b64          = $_POST['FOTO']             ?? '';
        $geovalla_idx_input = trim($_POST['GEOVALLA_IDX'] ?? '');

        if (!in_array($tipo, ['INGRESO', 'EGRESO'], true)) {
            error_ac("TIPO debe ser 'INGRESO' o 'EGRESO'");
        }
        if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
            error_ac('LAT y LNG requeridos y numéricos');
        }
        $lat = (float)$lat;
        $lng = (float)$lng;
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';

        $resultado = $motivo_rechazo = $geovalla_idx = $geovalla_nombre = $estado_actual = null;
        $empleado  = null;

        try {
            $lk = db2_ac();

            // -- Resolver empleado por usuario_app (nunca por un username del cliente) --
            $usernameEsc = $lk->real_escape_string($username);
            $res_emp = $lk->query(
                "SELECT idx, nombre_completo, habilitado, participa_control_acceso
                 FROM rrhh_empleados WHERE usuario_app = '$usernameEsc' LIMIT 1"
            );
            $empleado = $res_emp ? $res_emp->fetch_assoc() : null;

            if (!$empleado) {
                $lk->close();
                error_ac('Empleado no vinculado a este usuario de app', 404);
            }

            $resultado = 'OK';

            log_acceso("EMP_CHECK: idx={$empleado['idx']} hab={$empleado['habilitado']} participa={$empleado['participa_control_acceso']} lat=$lat lng=$lng geo_input=" . ($geovalla_idx_input ?: '-'));

            if ($empleado['habilitado'] !== 'SI') {
                $resultado      = 'RECHAZADO';
                $motivo_rechazo = 'EMPLEADO_INHABILITADO';
            } elseif ($empleado['participa_control_acceso'] !== 'SI') {
                $resultado      = 'RECHAZADO';
                $motivo_rechazo = 'EMPLEADO_NO_PARTICIPA';
            } else {
                if ($geovalla_idx_input !== '') {
                    // -- Geovalla preseleccionada por la app: validar solo contra ella --
                    $gIdxEsc = $lk->real_escape_string($geovalla_idx_input);
                    $res_geo = $lk->query(
                        "SELECT idx, nombre, tipo, geo_json FROM rrhg_geovallas
                         WHERE idx = '$gIdxEsc' AND habilitado = 'SI' LIMIT 1"
                    );
                    $match = $res_geo ? $res_geo->fetch_assoc() : null;

                    if (!$match) {
                        log_acceso("GEO_EVAL: geovalla_input=$geovalla_idx_input NO ENCONTRADA o deshabilitada");
                        $resultado      = 'RECHAZADO';
                        $motivo_rechazo = 'FUERA_DE_GEOVALLA';
                    } else {
                        $dentroGeovalla = punto_en_geovalla_ac($lat, $lng, $match);
                        log_acceso("GEO_EVAL: geovalla={$match['idx']} tipo={$match['tipo']} lat=$lat lng=$lng dentro=" . ($dentroGeovalla ? 'SI' : 'NO'));
                        if ($dentroGeovalla) {
                            $geovalla_idx    = $match['idx'];
                            $geovalla_nombre = $match['nombre'];
                        } else {
                            $resultado      = 'RECHAZADO';
                            $motivo_rechazo = 'FUERA_DE_GEOVALLA';
                        }
                    }
                } else {
                    // -- Sin preselección: buscar la primera geovalla habilitada que contenga el punto --
                    $res_geo = $lk->query(
                        "SELECT idx, nombre, tipo, geo_json FROM rrhg_geovallas WHERE habilitado = 'SI'"
                    );
                    $match     = null;
                    $evaluadas = 0;
                    if ($res_geo) {
                        while ($g = $res_geo->fetch_assoc()) {
                            $evaluadas++;
                            if (punto_en_geovalla_ac($lat, $lng, $g)) { $match = $g; break; }
                        }
                    }
                    log_acceso("GEO_SCAN: lat=$lat lng=$lng evaluadas=$evaluadas match=" . ($match ? $match['idx'] : 'ninguna'));
                    if ($match) {
                        $geovalla_idx    = $match['idx'];
                        $geovalla_nombre = $match['nombre'];
                    } else {
                        $resultado      = 'RECHAZADO';
                        $motivo_rechazo = 'FUERA_DE_GEOVALLA';
                    }
                }
            }

            // -- Validar secuencia INGRESO/EGRESO (solo el día actual) --
            if ($resultado === 'OK') {
                $empIdxEscSeq = $lk->real_escape_string($empleado['idx']);
                $res_seq = $lk->query(
                    "SELECT tipo FROM rrhg_fichajes
                      WHERE empleado_idx = '$empIdxEscSeq'
                        AND resultado = 'OK'
                        AND DATE(fecha_hora) = CURDATE()
                      ORDER BY fecha_hora DESC LIMIT 1"
                );
                $ult_seq = $res_seq ? $res_seq->fetch_assoc() : null;
                $ultimo_tipo = $ult_seq['tipo'] ?? null; // NULL = nunca fichó

                if ($tipo === 'EGRESO' && $ultimo_tipo !== 'INGRESO') {
                    $resultado      = 'RECHAZADO';
                    $motivo_rechazo = 'EGRESO_SIN_INGRESO';
                } elseif ($tipo === 'INGRESO' && $ultimo_tipo === 'INGRESO') {
                    $resultado      = 'RECHAZADO';
                    $motivo_rechazo = 'INGRESO_YA_ACTIVO';
                }
                log_acceso("SEQ_CHECK: tipo=$tipo ultimo=$ultimo_tipo resultado=" . ($resultado ?? 'OK'));
            }

            // -- Foto (best-effort): nunca bloquea el fichaje --
            $idx_fichaje = date('ymdHis') . rand(11, 99);
            $nombre_foto = null;
            if (trim($foto_b64) !== '' && $licencia !== '') {
                [$nombre_foto, $errorFoto] = guardar_foto_ac($licencia, $idx_fichaje, $foto_b64);
                if ($errorFoto) {
                    log_acceso("FOTO fichaje $idx_fichaje no guardada: $errorFoto");
                }
            }

            // -- INSERT: intenta con columna foto; si la BD es anterior, reintenta sin ella --
            $sql_con_foto = "INSERT INTO rrhg_fichajes
                (idx, empleado_idx, geovalla_idx, tipo, lat, lng, precision_m,
                 fecha_hora, usuario_app, ip, resultado, motivo_rechazo, foto)
             VALUES (?, ?, ?, ?, ?, ?, NULL, NOW(), ?, ?, ?, ?, ?)";

            $tieneColumneFoto = true;
            try {
                $stmt = @$lk->prepare($sql_con_foto);
                if ($stmt === false) { $tieneColumneFoto = false; }
            } catch (\Throwable $e) {
                $tieneColumneFoto = false;
            }

            if ($tieneColumneFoto) {
                $stmt->bind_param(
                    'ssssddsssss',
                    $idx_fichaje, $empleado['idx'], $geovalla_idx, $tipo, $lat, $lng,
                    $username, $ip, $resultado, $motivo_rechazo, $nombre_foto
                );
            } else {
                log_acceso("INSERT sin columna 'foto' (BD aún sin migrar) — fichaje $idx_fichaje");
                $stmt = $lk->prepare(
                    "INSERT INTO rrhg_fichajes
                        (idx, empleado_idx, geovalla_idx, tipo, lat, lng, precision_m,
                         fecha_hora, usuario_app, ip, resultado, motivo_rechazo)
                     VALUES (?, ?, ?, ?, ?, ?, NULL, NOW(), ?, ?, ?, ?)"
                );
                if ($stmt === false) {
                    throw new \RuntimeException('prepare() falló para INSERT rrhg_fichajes: ' . $lk->error);
                }
                $stmt->bind_param(
                    'ssssddssss',
                    $idx_fichaje, $empleado['idx'], $geovalla_idx, $tipo, $lat, $lng,
                    $username, $ip, $resultado, $motivo_rechazo
                );
            }

            if (!$stmt->execute()) {
                log_acceso("ERROR SQL INSERT rrhg_fichajes: " . $stmt->error);
            }
            $stmt->close();

            // -- Estado actual del día (para que la UI muestre el estado siguiente) --
            $empIdxEsc = $lk->real_escape_string($empleado['idx']);
            $res_ult = $lk->query(
                "SELECT tipo FROM rrhg_fichajes
                 WHERE empleado_idx = '$empIdxEsc'
                   AND resultado = 'OK' AND DATE(fecha_hora) = CURDATE()
                 ORDER BY fecha_hora DESC LIMIT 1"
            );
            $ult = $res_ult ? $res_ult->fetch_assoc() : null;
            $estado_actual = !$ult ? 'SIN_FICHAR_HOY' : ($ult['tipo'] === 'INGRESO' ? 'DENTRO' : 'FUERA');

            $lk->close();

        } catch (\Throwable $e) {
            log_acceso(
                "EXCEPCION en accion 400 (FICHAR): " . $e->getMessage() .
                " | " . $e->getFile() . ":" . $e->getLine()
            );
            error_ac('Error interno al registrar el fichaje.', 500);
        }

        $mensaje = ($resultado === 'OK')
            ? "Fichaje de " . ucfirst(strtolower($tipo)) . " registrado correctamente a las " . date('H:i') . "."
            : (MENSAJES_RECHAZO_AC[$motivo_rechazo] ?? 'No se pudo registrar el fichaje.');

        log_acceso("FICHAJE $resultado: username=$username emp={$empleado['idx']} tipo=$tipo lat=$lat lng=$lng geo=" . ($geovalla_idx ?? 'ninguna') . " motivo=" . ($motivo_rechazo ?? '-'));

        responder_ac([
            'resultado'       => $resultado,
            'mensaje'         => $mensaje,
            'geovalla_idx'    => $geovalla_idx,
            'geovalla_nombre' => $geovalla_nombre,
            'estado_actual'   => $estado_actual,
            'motivo_rechazo'  => $motivo_rechazo,
        ]);
        break;

    // ══════════════════════════════════════════════════════════
    // ACCION 401 — VERIFICAR_HABILITACION (solo lectura)
    // La app la llama tras el login y tras cada cambio de licencia.
    // Decide si mostrar la opción de fichaje en el dashboard.
    // ══════════════════════════════════════════════════════════
    case "401":
        $habilitado = false;

        try {
            $lk = db2_ac();
            $usernameEsc = $lk->real_escape_string($username);
            $res_emp = $lk->query(
                "SELECT habilitado, participa_control_acceso
                 FROM rrhh_empleados WHERE usuario_app = '$usernameEsc' LIMIT 1"
            );
            $empleado = $res_emp ? $res_emp->fetch_assoc() : null;
            if ($empleado
                && $empleado['habilitado'] === 'SI'
                && $empleado['participa_control_acceso'] === 'SI'
            ) {
                $habilitado = true;
            }
            $lk->close();

            // Diagnóstico: registrar resultado para detectar por qué no habilita
            $emp_hab   = $empleado['habilitado']                ?? 'NO_ENCONTRADO';
            $emp_part  = $empleado['participa_control_acceso']  ?? 'NO_ENCONTRADO';
            log_acceso("GEO_HAB: username=$username hab=$emp_hab participa=$emp_part result=" . ($habilitado ? 'SI' : 'NO'));

        } catch (\Throwable $e) {
            log_acceso(
                "EXCEPCION en accion 401 (VERIFICAR_HABILITACION): " . $e->getMessage() .
                " | " . $e->getFile() . ":" . $e->getLine()
            );
            $habilitado = false;
        }

        responder_ac(['habilitado' => $habilitado]);
        break;

    // ══════════════════════════════════════════════════════════
    // ACCION 402 — GEOVALLAS_ACTIVAS (solo lectura)
    // La app la llama al entrar a la pantalla de fichaje para
    // mostrarle al usuario dónde está ANTES de fichar.
    // Informativo: la validación real ocurre server-side en accion 400.
    // ══════════════════════════════════════════════════════════
    case "402":
        $geovallas = [];

        try {
            $lk = db2_ac();
            $res_geo = $lk->query(
                "SELECT
                    g.idx, g.nombre, g.tipo, g.geo_json,
                    g.tipo_ubicacion_idx,
                    t.nombre AS tipo_nombre,
                    t.icono  AS tipo_icono,
                    t.color  AS tipo_color
                 FROM rrhg_geovallas g
                 LEFT JOIN rrhg_ubicacion_tipos t ON t.idx = g.tipo_ubicacion_idx
                 WHERE g.habilitado = 'SI'"
            );
            if ($res_geo) {
                while ($g = $res_geo->fetch_assoc()) {
                    $geovallas[] = $g;
                }
            }
            $lk->close();

        } catch (\Throwable $e) {
            log_acceso(
                "EXCEPCION en accion 402 (GEOVALLAS_ACTIVAS): " . $e->getMessage() .
                " | " . $e->getFile() . ":" . $e->getLine()
            );
            $geovallas = [];
        }

        responder_ac(['geovallas' => $geovallas]);
        break;

    // ── Accion 404 — TIPOS_VISITA_ACTIVOS ──────────────────────
    // rrhg_tipos_visita está en DB2 (la base de cada empresa).
    case '404':
        $tipos = [];
        $debug_info = [];
        try {
            $lk = db2_ac();
            $debug_info['db'] = trim($_POST['DB2_NAME'] ?? '?');
            $debug_info['host'] = trim($_POST['DB2_ADDRESS'] ?? '?');
            $res_tv = $lk->query(
                "SELECT idx, nombre, codigo, icono, color
                   FROM rrhg_tipos_visita
                  WHERE habilitado = 'SI'
                  ORDER BY nombre ASC"
            );
            if ($res_tv === false) {
                $debug_info['error'] = $lk->error;
            } else {
                while ($t = $res_tv->fetch_assoc()) $tipos[] = $t;
                $debug_info['count'] = count($tipos);
            }
            $lk->close();
        } catch (\Throwable $e) {
            $debug_info['exception'] = $e->getMessage();
            $tipos = [];
        }
        responder_ac(['tipos' => $tipos, 'debug' => $debug_info]);
        break;

    // ── Accion 403 — REGISTRAR_VISITA ──────────────────────────
    case '403':
        $tipo           = strtoupper(trim($_POST['TIPO']             ?? ''));
        $tipo_visita_idx = trim($_POST['TIPO_VISITA_IDX']            ?? '');
        $lugar_nombre   = trim($_POST['LUGAR_NOMBRE']                ?? '');
        $lat            = isset($_POST['LAT'])  && $_POST['LAT']  !== '' ? (float)$_POST['LAT']  : null;
        $lng            = isset($_POST['LNG'])  && $_POST['LNG']  !== '' ? (float)$_POST['LNG']  : null;
        $precision_m    = isset($_POST['PRECISION_M']) && is_numeric($_POST['PRECISION_M']) ? (float)$_POST['PRECISION_M'] : null;
        $cliente_idx    = trim($_POST['CLIENTE_IDX']   ?? '') ?: null;
        $observaciones  = trim($_POST['OBSERVACIONES'] ?? '') ?: null;
        $foto_b64       = trim($_POST['FOTO']          ?? '');

        if (!in_array($tipo, ['INGRESO', 'EGRESO'], true)) {
            error_ac("TIPO debe ser 'INGRESO' o 'EGRESO'");
        }
        if ($tipo_visita_idx === '') error_ac('TIPO_VISITA_IDX es obligatorio.');
        if (mb_strlen($lugar_nombre) < 1)   error_ac('LUGAR_NOMBRE es obligatorio.');
        if (mb_strlen($lugar_nombre) > 200)  error_ac('LUGAR_NOMBRE supera los 200 caracteres.');

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        try {
            $lk = db2_ac();

            // -- Resolver empleado por PIN+TOKEN --
            $usernameEsc = $lk->real_escape_string($username);
            $res_emp = $lk->query(
                "SELECT idx, nombre_completo FROM rrhh_empleados
                  WHERE usuario_app = '$usernameEsc' LIMIT 1"
            );
            if (!$res_emp || !($empleado = $res_emp->fetch_assoc())) {
                $lk->close();
                error_ac('Empleado no encontrado para este usuario.');
            }

            // -- Validar tipo de visita --
            $tvIdxEsc = $lk->real_escape_string($tipo_visita_idx);
            $res_tv = $lk->query(
                "SELECT idx, nombre FROM rrhg_tipos_visita
                  WHERE idx = '$tvIdxEsc' AND habilitado = 'SI' LIMIT 1"
            );
            if (!$res_tv || !($tipo_visita = $res_tv->fetch_assoc())) {
                $lk->close();
                error_ac('Tipo de visita no válido o inactivo.');
            }

            // -- Foto (best-effort) --
            $idx_visita  = date('ymdHis') . rand(11, 99);
            $nombre_foto = null;
            if ($foto_b64 !== '' && $licencia !== '') {
                $dir = __DIR__ . "/public/repositorio/$licencia/rrhg_visitas/";
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $nombre_foto = 'visita_' . strtolower($idx_visita) . '.jpg';
                $decoded = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $foto_b64));
                if ($decoded !== false) {
                    if (!@file_put_contents($dir . $nombre_foto, $decoded)) {
                        log_acceso("FOTO visita $idx_visita no guardada.");
                        $nombre_foto = null;
                    }
                }
            }

            // -- INSERT --
            $empIdx      = $empleado['idx'];
            $lugarEsc    = $lk->real_escape_string($lugar_nombre);
            $obsEsc      = $observaciones !== null ? $lk->real_escape_string($observaciones) : null;
            $cliEsc      = $cliente_idx   !== null ? $lk->real_escape_string($cliente_idx)   : null;
            $fotoEsc     = $nombre_foto   !== null ? $lk->real_escape_string($nombre_foto)   : null;
            $latVal      = $lat  !== null ? $lat  : 'NULL';
            $lngVal      = $lng  !== null ? $lng  : 'NULL';
            $precVal     = $precision_m !== null ? $precision_m : 'NULL';
            $obsVal      = $obsEsc  !== null ? "'$obsEsc'"  : 'NULL';
            $cliVal      = $cliEsc  !== null ? "'$cliEsc'"  : 'NULL';
            $fotoVal     = $fotoEsc !== null ? "'$fotoEsc'" : 'NULL';

            $sql = "INSERT INTO rrhg_visitas
                        (idx, empleado_idx, tipo_visita_idx, tipo, lat, lng, precision_m,
                         fecha_hora, lugar_nombre, cliente_idx, observaciones, foto,
                         usuario_app, ip, resultado)
                    VALUES
                        ('$idx_visita', '$empIdx', '$tvIdxEsc', '$tipo',
                         $latVal, $lngVal, $precVal,
                         NOW(), '$lugarEsc', $cliVal, $obsVal, $fotoVal,
                         '$usernameEsc', '$ip', 'OK')";

            if (!$lk->query($sql)) {
                log_acceso("ERROR INSERT rrhg_visitas: " . $lk->error);
                $lk->close();
                error_ac('Error interno al registrar la visita.', 500);
            }

            $fecha_hora = date('Y-m-d H:i:s');
            $lk->close();

            log_acceso("VISITA OK: username=$username emp=$empIdx tipo=$tipo tv={$tipo_visita['nombre']} lugar=$lugar_nombre");

        } catch (\Throwable $e) {
            log_acceso("EXCEPCION accion 403: " . $e->getMessage() . " | " . $e->getFile() . ":" . $e->getLine());
            error_ac('Error interno al registrar la visita.', 500);
        }

        responder_ac([
            'resultado'          => 'OK',
            'mensaje'            => 'Visita registrada correctamente.',
            'tipo'               => $tipo,
            'tipo_visita_nombre' => $tipo_visita['nombre'],
            'lugar_nombre'       => $lugar_nombre,
            'fecha_hora'         => $fecha_hora,
        ]);
        break;

    default:
        error_ac('Acción no reconocida', 400);
}
