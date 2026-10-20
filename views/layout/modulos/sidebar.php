<?php
$currentPath     = $_SERVER['REQUEST_URI'] ?? '';
$sidebarPerfil   = $perfil    ?? 'user';
$sidebarNombre   = $nombre    ?? '';
$sidebarUsername = $username  ?? ($_SESSION['usuario'] ?? '');
$sidebarEmpresa  = $em_nombre ?? $_SESSION['empresa_nombre'] ?? $_SESSION['empresa'] ?? 'AKIM';

$isActive = fn(string $seg): string =>
    str_contains($currentPath, '/' . $seg) ? ' active' : '';

$initial = strtoupper(substr($sidebarNombre ?: $sidebarUsername, 0, 1) ?: 'U');
?>

<aside class="app-sidebar" id="appSidebar">

    <!-- ── Brand ──────────────────────────────────────────────────────────── -->
    <a href="menu" class="sidebar-brand">
        <div class="sidebar-brand-mark">AK</div>
        <div class="sidebar-brand-info">
            <span class="sidebar-brand-name">AKIM</span>
            <span class="sidebar-brand-tag">Gestión comercial</span>
        </div>
    </a>

    <!-- ── CTA Nueva Venta (solo si tiene módulo VTA) ─────────────────────── -->
    <?php if (\Model\Model_apphelper::tieneAccesoModulo('VTA')): ?>
    <div class="sidebar-cta-wrap">
        <a href="vta_wizard_step1" class="sidebar-cta-btn">
            <i class="fa-solid fa-bolt"></i>
            <span>Nueva Venta</span>
        </a>
    </div>
    <?php endif; ?>

    <!-- ── Navegación ─────────────────────────────────────────────────────── -->
    <nav class="sidebar-nav">

        <a href="menu" class="sidebar-nav-link<?php echo $isActive('menu'); ?>">
            <i class="fa-solid fa-house"></i>
            <span>Panel de actividad</span>
        </a>

        <div class="sidebar-nav-section">Operaciones</div>

        <?php if (\Model\Model_apphelper::tieneAccesoModulo('VTA')): ?>
        <a href="ventas" class="sidebar-nav-link<?php echo $isActive('ventas'); ?>">
            <i class="fa-solid fa-receipt"></i>
            <span>Ventas</span>
        </a>
        <?php endif; ?>

        <?php if (\Model\Model_apphelper::tieneAccesoModulo('CPA')): ?>
        <a href="compras" class="sidebar-nav-link<?php echo $isActive('compras'); ?>">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Compras</span>
        </a>
        <?php endif; ?>

        <?php if (\Model\Model_apphelper::tieneAccesoModulo('STK')): ?>
        <a href="stock" class="sidebar-nav-link<?php echo $isActive('stock'); ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Stock</span>
        </a>
        <?php endif; ?>

        <?php if (\Model\Model_apphelper::tieneAccesoModulo('TES')): ?>
        <a href="tesoreria" class="sidebar-nav-link<?php echo $isActive('tesoreria') . $isActive('tes_'); ?>">
            <i class="fa-solid fa-coins"></i>
            <span>Tesorería</span>
        </a>
        <?php endif; ?>

        <?php if (\Model\Model_apphelper::tieneAccesoModulo('TIENDA')): ?>
        <a href="tienda" class="sidebar-nav-link<?php echo $isActive('tienda'); ?>">
            <i class="fa-solid fa-store"></i>
            <span>Tienda</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-nav-section">Análisis</div>

        <a href="reportes" class="sidebar-nav-link<?php echo $isActive('reportes'); ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Reportes</span>
        </a>

        <div class="sidebar-nav-section">Sistema</div>

        <a href="mis_datos" class="sidebar-nav-link<?php echo $isActive('mis_datos'); ?>">
            <i class="fa-solid fa-id-card"></i>
            <span>Mis Datos</span>
        </a>

        <a href="caja_historial" class="sidebar-nav-link<?php echo $isActive('caja_historial'); ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historial Caja</span>
        </a>

        <a href="parametros" class="sidebar-nav-link<?php echo $isActive('parametros'); ?>">
            <i class="fa-solid fa-sliders"></i>
            <span>Parámetros</span>
        </a>

    </nav>

    <!-- ── Empresa (cambiar) ───────────────────────────────────────────────── -->
    <a href="cambiarEmpresa" class="sidebar-company-link" title="Cambiar empresa">
        <i class="fa-solid fa-building sidebar-company-icon"></i>
        <span class="sidebar-company-name"><?php echo htmlspecialchars($sidebarEmpresa, ENT_QUOTES); ?></span>
        <i class="fa-solid fa-right-left sidebar-company-swap"></i>
    </a>

    <!-- ── Usuario ────────────────────────────────────────────────────────── -->
    <div class="sidebar-profile">
        <div class="sidebar-profile-row">
            <div class="sidebar-avatar"><?php echo htmlspecialchars($initial); ?></div>
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name">
                    <?php echo htmlspecialchars($sidebarNombre ?: $sidebarUsername, ENT_QUOTES); ?>
                </div>
                <div class="sidebar-profile-role">
                    <?php echo ucfirst($sidebarPerfil); ?>
                </div>
            </div>
        </div>
        <a href="logout" class="sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar sesión
        </a>
    </div>

</aside>

<!-- Overlay para mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
