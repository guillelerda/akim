<?php
/**
 * JIT endpoint — Proveedores (para wizard de compras)
 */

require_once __DIR__ . '/../../includes/error_logger.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!($_SESSION['login'] ?? false)) { http_response_code(401); echo '[]'; exit; }

require_once __DIR__ . '/../../includes/app.php';

use Model\ActiveDB;
use Model\Model_JIT;

ActiveDB::setDB($conexionBD);

$termino = trim($_GET['q'] ?? '');
if (strlen($termino) < 2) { echo '[]'; exit; }

$lista = Model_JIT::buscar('cpa_proveedores', 'razon_social', $termino);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($lista, JSON_UNESCAPED_UNICODE);
