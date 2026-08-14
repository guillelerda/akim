<?php

require_once __DIR__ . '/includes/error_logger.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/includes/app.php';

use MVC\Router;
use Controllers\Controllers_login;
use Controllers\Controllers_menu;
use Controllers\Controllers_paginas;
use Controllers\Controllers_install;
use Controllers\Controllers_setup_admin;
use Controllers\Controllers_selector;

// Administrador
use Controllers\Administrador\Controllers_adm_panel;
use Controllers\Administrador\Controllers_adm_usuarios;
use Controllers\Administrador\Controllers_adm_empresas;
use Controllers\Administrador\Controllers_adm_licencias;
use Controllers\Administrador\Controllers_adm_bases;
use Controllers\Administrador\Controllers_adm_links;

// Compras
use Controllers\Compras\Controllers_cpa_menu;
use Controllers\Compras\Controller_cpa0001_proveedores;
use Controllers\Compras\Controller_cpa0010_facturas;

// Stock
use Controllers\Stock\Controllers_stk_menu;
use Controllers\Stock\Controller_stk001_productos;
use Controllers\Stock\Controller_stk001_categorias;

// Ventas
use Controllers\Ventas\Controllers_vta_menu;
use Controllers\Ventas\Controller_vta0001_clientes;
use Controllers\Ventas\Controller_vta_wizard;

// Tienda
use Controllers\Tienda\Controllers_tienda_menu;

$router = new Router();

// ── Instalador de primer uso ──────────────────────────────────────────────────
$router->get('/install',      [Controllers_install::class,       'install']);
$router->post('/install',     [Controllers_install::class,       'install']);

// ── Setup primer admin (se bloquea sola al crear el primero) ──────────────────
$router->get('/setupAdmin',   [Controllers_setup_admin::class,   'setup']);
$router->post('/setupAdmin',  [Controllers_setup_admin::class,   'setup']);

// ── Páginas públicas ──────────────────────────────────────────────────────────
$router->get('/',       [Controllers_paginas::class, 'inicio']);
$router->get('/login',  [Controllers_login::class,   'login']);
$router->post('/login', [Controllers_login::class,   'login']);
$router->get('/logout', [Controllers_login::class,   'logout']);

// ── Admin login + 2FA ─────────────────────────────────────────────────────────
$router->get('/loginAdmin',  [Controllers_login::class, 'loginAdmin']);
$router->post('/loginAdmin', [Controllers_login::class, 'loginAdmin']);
$router->get('/tokenAdmin',  [Controllers_login::class, 'tokenAdmin']);
$router->post('/tokenAdmin', [Controllers_login::class, 'tokenAdmin']);
$router->get('/logoutAdmin', [Controllers_login::class, 'logoutAdmin']);

// ── Selector de empresa ───────────────────────────────────────────────────────
$router->get('/seleccionarEmpresa',  [Controllers_selector::class, 'seleccionar']);
$router->post('/seleccionarEmpresa', [Controllers_selector::class, 'seleccionar']);
$router->get('/cambiarEmpresa',      [Controllers_selector::class, 'cambiar']);

// ── Menú principal ────────────────────────────────────────────────────────────
$router->get('/menu',         [Controllers_menu::class, 'menu']);
$router->get('/menu_modulos', [Controllers_menu::class, 'menu_modulos']);

// ── Panel Admin (protegido por loginADMIN) ────────────────────────────────────
$router->get('/adminPanel',     [Controllers_adm_panel::class,    'panel']);

// ── Administrador ─────────────────────────────────────────────────────────────
$router->get('/admin',              [Controllers_adm_usuarios::class,  'adm_inicio']);
$router->get('/adm_usuarios',       [Controllers_adm_usuarios::class,  'adm_lista']);
$router->post('/adm_usuarios',      [Controllers_adm_usuarios::class,  'adm_lista']);
$router->get('/adm_usuarios_edit',  [Controllers_adm_usuarios::class,  'adm_edit']);
$router->post('/adm_usuarios_edit', [Controllers_adm_usuarios::class,  'adm_edit']);

// Empresas
$router->get('/adm_empresas',       [Controllers_adm_empresas::class,  'adm_lista']);
$router->post('/adm_empresas',      [Controllers_adm_empresas::class,  'adm_lista']);
$router->get('/adm_empresas_edit',  [Controllers_adm_empresas::class,  'adm_edit']);
$router->post('/adm_empresas_edit', [Controllers_adm_empresas::class,  'adm_edit']);

// Licencias
$router->get('/adm_licencias',      [Controllers_adm_licencias::class, 'adm_lista']);
$router->post('/adm_licencias',     [Controllers_adm_licencias::class, 'adm_lista']);
$router->get('/adm_licencias_edit', [Controllers_adm_licencias::class, 'adm_edit']);
$router->post('/adm_licencias_edit',[Controllers_adm_licencias::class, 'adm_edit']);

// Bases de datos
$router->get('/adm_bases',          [Controllers_adm_bases::class,     'adm_lista']);
$router->post('/adm_bases',         [Controllers_adm_bases::class,     'adm_lista']);
$router->get('/adm_bases_edit',     [Controllers_adm_bases::class,     'adm_edit']);
$router->post('/adm_bases_edit',    [Controllers_adm_bases::class,     'adm_edit']);

// Asignaciones usuario ↔ licencia
$router->get('/adm_links',          [Controllers_adm_links::class,     'adm_lista']);
$router->post('/adm_links',         [Controllers_adm_links::class,     'adm_lista']);

// Módulos → redirige a licencias (módulos se gestionan por licencia)
$router->get('/adm_modulos', function($r) { header('Location: adm_licencias'); exit; });

// ── Compras ───────────────────────────────────────────────────────────────────
$router->get('/compras',                 [Controllers_cpa_menu::class,       'menu']);
$router->get('/cpa_proveedores',         [Controller_cpa0001_proveedores::class, 'lista']);
$router->post('/cpa_proveedores',        [Controller_cpa0001_proveedores::class, 'lista']);
$router->get('/cpa_proveedores_edit',    [Controller_cpa0001_proveedores::class, 'edit']);
$router->post('/cpa_proveedores_edit',   [Controller_cpa0001_proveedores::class, 'edit']);
$router->get('/cpa_facturas',            [Controller_cpa0010_facturas::class,    'lista']);
$router->post('/cpa_facturas',           [Controller_cpa0010_facturas::class,    'lista']);
$router->get('/cpa_facturas_nueva',      [Controller_cpa0010_facturas::class,    'nueva']);
$router->post('/cpa_facturas_nueva',     [Controller_cpa0010_facturas::class,    'nueva']);

// ── Stock ─────────────────────────────────────────────────────────────────────
$router->get('/stock',              [Controllers_stk_menu::class,      'menu']);
$router->get('/stk_productos',      [Controller_stk001_productos::class, 'lista']);
$router->post('/stk_productos',     [Controller_stk001_productos::class, 'lista']);
$router->get('/stk_productos_edit', [Controller_stk001_productos::class, 'edit']);
$router->post('/stk_productos_edit',[Controller_stk001_productos::class, 'edit']);
$router->get('/stk_categorias',     [Controller_stk001_categorias::class, 'lista']);
$router->post('/stk_categorias',    [Controller_stk001_categorias::class, 'lista']);

// ── Ventas ────────────────────────────────────────────────────────────────────
$router->get('/ventas',          [Controllers_vta_menu::class,    'menu']);
$router->get('/vta_clientes',    [Controller_vta0001_clientes::class, 'lista']);
$router->post('/vta_clientes',   [Controller_vta0001_clientes::class, 'lista']);
$router->get('/vta_clientes_edit',  [Controller_vta0001_clientes::class, 'edit']);
$router->post('/vta_clientes_edit', [Controller_vta0001_clientes::class, 'edit']);

// Wizard de venta (patrón JIT + multi-paso)
$router->get('/vta_wizard_step1',  [Controller_vta_wizard::class, 'step1']);
$router->post('/vta_wizard_step1', [Controller_vta_wizard::class, 'step1']);
$router->get('/vta_wizard_step2',  [Controller_vta_wizard::class, 'step2']);
$router->post('/vta_wizard_step2', [Controller_vta_wizard::class, 'step2']);
$router->get('/vta_wizard_step3',  [Controller_vta_wizard::class, 'step3']);
$router->post('/vta_wizard_step3', [Controller_vta_wizard::class, 'step3']);
$router->get('/vta_wizard_step4',  [Controller_vta_wizard::class, 'step4']);
$router->post('/vta_wizard_step4', [Controller_vta_wizard::class, 'step4']);
$router->post('/vta_wizard_confirmar', [Controller_vta_wizard::class, 'confirmar']);

// ── Tienda ────────────────────────────────────────────────────────────────────
$router->get('/tienda',        [Controllers_tienda_menu::class, 'menu']);
$router->get('/tienda_catalogo',[Controllers_tienda_menu::class, 'catalogo']);

$router->comprobarRutas();
