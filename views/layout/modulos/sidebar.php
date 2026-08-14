<?php
$currentPath  = $_SERVER['REQUEST_URI'] ?? '';
$sidebarPerfil   = $perfil    ?? 'user';
$sidebarNombre   = $nombre    ?? '';
$sidebarUsername = $username  ?? ($_SESSION['usuario'] ?? '');
$sidebarEmpresa  = $em_nombre ?? $_SESSION['empresa_nombre'] ?? $_SESSION['empresa'] ?? 'AKIM';

$isActive = fn(string $seg): string =>
    str_contains($currentPath, '/' . $seg) ? ' active' : '';

$initial = strtoupper(substr($sidebarNombre ?: $sidebarUsername, 0, 1) ?: 'U');
?>

<aside class="app-sidebar" id="appSidebar">

    <!-- ── Brand ──────────────────────────────────────────── -->
    <a href="menu" class="sidebar-brand">
        <div class="sidebar-brand-mark">AK</div>
        <div class="sidebar-brand-info">
            <span class="sidebar-brand-name">AKIM</span>
            <span class="sidebar-brand-tag">Gestión comercial</span>
        </div>
    </a>

    <!-- ── CTA principal ──────────────────────────────────── -->
    <div class="sidebar-cta-wrap">
        <a href="vta_wizard_step1" class="sidebar-cta-btn">
            <i class="fa-solid fa-bolt"></i>
            <span>Nueva Venta</span>
        </a>
    </div>

    <!-- ── Navegación ─────────────────────────────────────── -->
    <nav class="sidebar-nav">

        <a href="menu" class="sidebar-nav-link<?php echo $isActive('menu'); ?>">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-nav-section">Operaciones</div>

        <a href="ventas" class="sidebar-nav-link<?php echo $isActive('ventas'); ?>">
            <i class="fa-solid fa-receipt"></i>
            <span>Ventas</span>
        </a>

        <a href="compras" class="sidebar-nav-link<?php echo $isActive('compras'); ?>">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Compras</span>
        </a>

        <a href="stock" class="sidebar-nav-link<?php echo $isActive('stock'); ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Stock</span>
        </a>

        <a href="tienda" class="sidebar-nav-link<?php echo $isActive('tienda'); ?>">
            <i class="fa-solid fa-store"></i>
            <span>Tienda</span>
        </a>

        <?php if ($sidebarPerfil === 'admin'): ?>
        <div class="sidebar-nav-section">Sistema</div>
        <a href="admin" class="sidebar-nav-link<?php echo $isActive('admin'); ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Administración</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- ── Empresa (cambiar) ─────────────────────────────── -->
    <a href="cambiarEmpresa" class="sidebar-company-link" title="Cambiar empresa">
        <i class="fa-solid fa-building sidebar-company-icon"></i>
        <span class="sidebar-company-name"><?php echo htmlspecialchars($sidebarEmpresa, ENT_QUOTES); ?></span>
        <i class="fa-solid fa-right-left sidebar-company-swap"></i>
    </a>

    <!-- ── Usuario ────────────────────────────────────────── -->
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
