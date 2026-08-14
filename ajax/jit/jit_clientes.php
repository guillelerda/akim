<?php
/**
 * JIT endpoint — Clientes
 * GET ?q=texto   → devuelve JSON con coincidencias + opción "Crear nuevo"
 */

require_once __DIR__ . '/../../includes/error_logger.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!($_SESSION['login'] ?? false)) { http_response_code(401); echo '[]'; exit; }

require_once __DIR__ . '/../../includes/app.php';

use Model\Ventas\Model_vta0001_clientes;

$termino = trim($_GET['q'] ?? '');
if (strlen($termino) < 2) { echo '[]'; exit; }

$lista = Model_vta0001_clientes::jit_buscar($termino);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($lista, JSON_UNESCAPED_UNICODE);
