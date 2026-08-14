<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'AKIM', ENT_QUOTES); ?> — AKIM</title>
    <link rel="stylesheet" href="includes/plugins/fontawesome-free-6.6.0-web/css/all.min.css">
    <link rel="stylesheet" href="includes/plugins/datatable/datatables.min.css">
    <link rel="stylesheet" href="includes/plugins/toastr/toastr.min.css">
    <script src="includes/plugins/jquery/jquery-3.5.1.min.js"></script>
    <script src="includes/plugins/datatable/datatables.min.js"></script>
    <script src="includes/plugins/toastr/toastr.min.js"></script>
    <script>
        toastr.options = { closeButton:true, progressBar:true, positionClass:"toast-top-right", timeOut:"3500" };
    </script>
    <style>
    /* ═══════════════════════════════════════════════════════════════════════════
       AKIM — App Shell CSS
       Variables, reset, sidebar, topbar, content, components.
    ═══════════════════════════════════════════════════════════════════════════ */

    :root {
        --bg:             #030712;
        --sidebar-bg:     #0b1120;
        --sidebar-w:      240px;
        --surface:        #111827;
        --surface-2:      #1f2937;
        --border:         rgba(255,255,255,.07);
        --border-med:     rgba(255,255,255,.11);
        --text:           #f9fafb;
        --text-muted:     #9ca3af;
        --text-dim:       #6b7280;
        --accent:         #2563eb;
        --accent-lt:      #3b82f6;
        --accent-glow:    rgba(37,99,235,.22);
        --green:          #16a34a;
        --green-lt:       #22c55e;
        --red:            #dc2626;
        --amber:          #d97706;
        --purple:         #7c3aed;
        --topbar-h:       58px;
        --radius:         10px;
        --radius-lg:      16px;
        --transition:     .18s ease;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { font-size: 16px; }

    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--bg);
        color: var(--text);
        line-height: 1.5;
        min-height: 100vh;
    }

    a { color: inherit; text-decoration: none; }

    /* ── Scrollbar ──────────────────────────────────────────────────────────── */
    ::-webkit-scrollbar            { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track      { background: transparent; }
    ::-webkit-scrollbar-thumb      { background: rgba(255,255,255,.12); border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover{ background: rgba(255,255,255,.2); }

    /* ═══════════════════════════════════════════════════════════════════════════
       APP SHELL — Sidebar + Content
    ═══════════════════════════════════════════════════════════════════════════ */

    .app-shell {
        display: flex;
        min-height: 100vh;
    }

    /* ── Sidebar ────────────────────────────────────────────────────────────── */
    .app-sidebar {
        width: var(--sidebar-w);
        min-height: 100vh;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0; left: 0; bottom: 0;
        z-index: 200;
        overflow-y: auto;
        overflow-x: hidden;
        transition: transform var(--transition);
    }

    /* Brand */
    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: 1.1rem .9rem;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }

    .sidebar-brand-mark {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 60%, #1e40af 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; font-weight: 800; color: #fff;
        letter-spacing: -.04em;
        flex-shrink: 0;
        box-shadow: 0 0 18px rgba(37,99,235,.35);
    }

    .sidebar-brand-name {
        display: block;
        font-size: .95rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -.02em;
        line-height: 1;
    }

    .sidebar-brand-tag {
        display: block;
        font-size: .62rem;
        color: var(--text-dim);
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-top: 2px;
    }

    /* CTA nueva venta */
    .sidebar-cta-wrap {
        padding: .75rem .75rem .5rem;
        flex-shrink: 0;
    }

    .sidebar-cta-btn {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .6rem .85rem;
        border-radius: var(--radius);
        background: linear-gradient(135deg, rgba(37,99,235,.18), rgba(29,78,216,.22));
        border: 1px solid rgba(37,99,235,.3);
        color: #93c5fd;
        font-size: .82rem;
        font-weight: 600;
        transition: background var(--transition), border-color var(--transition), color var(--transition);
    }

    .sidebar-cta-btn:hover {
        background: linear-gradient(135deg, rgba(37,99,235,.3), rgba(29,78,216,.35));
        border-color: rgba(59,130,246,.5);
        color: #bfdbfe;
    }

    .sidebar-cta-btn i { font-size: .8rem; }

    /* Nav */
    .sidebar-nav {
        flex: 1;
        padding: .35rem .75rem;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .sidebar-nav-section {
        font-size: .6rem;
        font-weight: 600;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--text-dim);
        padding: .7rem .5rem .2rem;
        margin-top: .15rem;
    }

    .sidebar-nav-link {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .52rem .75rem;
        border-radius: 8px;
        color: var(--text-muted);
        font-size: .85rem;
        font-weight: 500;
        transition: background var(--transition), color var(--transition);
    }

    .sidebar-nav-link i {
        width: 16px;
        font-size: .82rem;
        flex-shrink: 0;
        opacity: .75;
    }

    .sidebar-nav-link:hover {
        background: rgba(255,255,255,.05);
        color: #e5e7eb;
    }

    .sidebar-nav-link:hover i { opacity: 1; }

    .sidebar-nav-link.active {
        background: rgba(37,99,235,.15);
        color: #93c5fd;
    }

    .sidebar-nav-link.active i { opacity: 1; }

    /* Empresa (cambiar) */
    .sidebar-company-link {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 0 .75rem;
        padding: .55rem .65rem;
        border-radius: 8px;
        background: rgba(255,255,255,.03);
        border: 1px solid var(--border);
        font-size: .72rem;
        color: var(--text-dim);
        overflow: hidden;
        text-decoration: none;
        transition: background var(--transition), border-color var(--transition), color var(--transition);
    }

    .sidebar-company-link:hover {
        background: rgba(37,99,235,.1);
        border-color: rgba(37,99,235,.3);
        color: #93c5fd;
    }

    .sidebar-company-icon { flex-shrink: 0; font-size: .7rem; }

    .sidebar-company-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sidebar-company-swap {
        flex-shrink: 0;
        font-size: .62rem;
        opacity: 0;
        transition: opacity var(--transition);
    }

    .sidebar-company-link:hover .sidebar-company-swap { opacity: 1; }

    /* Perfil de usuario */
    .sidebar-profile {
        padding: .75rem;
        border-top: 1px solid var(--border);
        margin-top: .5rem;
        flex-shrink: 0;
    }

    .sidebar-profile-row {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-bottom: .6rem;
    }

    .sidebar-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: .82rem; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }

    .sidebar-profile-name {
        font-size: .8rem;
        font-weight: 600;
        color: #e5e7eb;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.2;
    }

    .sidebar-profile-role {
        font-size: .68rem;
        color: var(--text-dim);
    }

    .sidebar-logout {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .65rem;
        border-radius: 7px;
        color: var(--text-dim);
        font-size: .78rem;
        transition: background var(--transition), color var(--transition);
        width: 100%;
    }

    .sidebar-logout:hover {
        background: rgba(239,68,68,.09);
        color: #f87171;
    }

    /* Overlay mobile */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.6);
        z-index: 199;
        backdrop-filter: blur(2px);
    }

    /* ── App Content ────────────────────────────────────────────────────────── */
    .app-content {
        margin-left: var(--sidebar-w);
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* ── Topbar ─────────────────────────────────────────────────────────────── */
    .app-topbar {
        height: var(--topbar-h);
        background: rgba(11,17,32,.85);
        border-bottom: 1px solid var(--border);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        display: flex;
        align-items: center;
        padding: 0 1.5rem;
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .topbar-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 1rem;
        cursor: pointer;
        padding: .35rem;
        border-radius: 6px;
        transition: color var(--transition), background var(--transition);
    }

    .topbar-toggle:hover { color: var(--text); background: rgba(255,255,255,.06); }

    .topbar-breadcrumb {
        flex: 1;
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .88rem;
        color: var(--text-muted);
    }

    .topbar-page { font-weight: 600; color: var(--text); }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .topbar-time {
        font-size: .78rem;
        color: var(--text-dim);
        font-variant-numeric: tabular-nums;
    }

    /* ── Main content ───────────────────────────────────────────────────────── */
    .app-main {
        flex: 1;
        padding: 1.75rem;
    }

    /* ═══════════════════════════════════════════════════════════════════════════
       PUBLIC LAYOUT (login, install)
    ═══════════════════════════════════════════════════════════════════════════ */

    .public-header {
        background: rgba(11,17,32,.7);
        border-bottom: 1px solid var(--border);
        padding: .85rem 1.5rem;
        display: flex;
        align-items: center;
    }

    .public-header strong {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .akim-main { padding: 1.5rem; }

    .akim-footer {
        text-align: center;
        padding: 1rem;
        font-size: .72rem;
        color: var(--text-dim);
        border-top: 1px solid var(--border);
    }

    /* ═══════════════════════════════════════════════════════════════════════════
       SHARED COMPONENTS
    ═══════════════════════════════════════════════════════════════════════════ */

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .55rem 1.1rem;
        border-radius: var(--radius);
        font-size: .85rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        border: 1px solid transparent;
        transition: opacity var(--transition), box-shadow var(--transition), background var(--transition);
        line-height: 1;
    }

    .btn-primary {
        background: linear-gradient(to right, var(--accent), #1d4ed8);
        color: #fff;
        box-shadow: 0 3px 12px rgba(37,99,235,.3);
    }

    .btn-primary:hover { opacity: .9; box-shadow: 0 5px 20px rgba(37,99,235,.45); }

    .btn-secondary {
        background: var(--surface-2);
        color: var(--text-muted);
        border-color: var(--border-med);
    }

    .btn-secondary:hover { background: #374151; color: var(--text); }

    .btn-success {
        background: linear-gradient(to right, var(--green), #15803d);
        color: #fff;
        box-shadow: 0 3px 12px rgba(22,163,74,.25);
    }

    .btn-success:hover { opacity: .9; }

    .btn-danger {
        background: rgba(220,38,38,.12);
        color: #fca5a5;
        border-color: rgba(220,38,38,.25);
    }

    .btn-danger:hover { background: rgba(220,38,38,.2); }

    .btn-sm { padding: .38rem .75rem; font-size: .78rem; }
    .btn-lg { padding: .75rem 1.5rem; font-size: .95rem; }

    /* Cards */
    .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
    }

    .card-title {
        font-size: .9rem;
        font-weight: 600;
        color: var(--text);
    }

    .card-body { padding: 1.25rem; }

    /* Table */
    .tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: .84rem;
    }

    .tbl thead th {
        text-align: left;
        padding: .6rem .85rem;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--text-dim);
        border-bottom: 1px solid var(--border);
    }

    .tbl tbody td {
        padding: .7rem .85rem;
        border-bottom: 1px solid var(--border);
        color: var(--text-muted);
    }

    .tbl tbody tr:last-child td { border-bottom: none; }

    .tbl tbody tr:hover td { background: rgba(255,255,255,.025); }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: .18rem .55rem;
        border-radius: 99px;
        font-size: .7rem;
        font-weight: 600;
    }

    .badge-blue   { background: rgba(37,99,235,.15);  color: #93c5fd; }
    .badge-green  { background: rgba(22,163,74,.15);  color: #86efac; }
    .badge-amber  { background: rgba(217,119,6,.15);  color: #fcd34d; }
    .badge-red    { background: rgba(220,38,38,.15);  color: #fca5a5; }
    .badge-gray   { background: rgba(107,114,128,.15);color: #9ca3af; }

    /* Form elements */
    .form-group { display: flex; flex-direction: column; gap: .4rem; margin-bottom: .9rem; }

    .form-group label {
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--text-muted);
    }

    .form-control {
        width: 100%;
        padding: .65rem .9rem;
        background: rgba(31,41,55,.5);
        border: 1px solid var(--border-med);
        border-radius: var(--radius);
        color: var(--text);
        font-size: .9rem;
        font-family: inherit;
        outline: none;
        transition: border-color var(--transition), box-shadow var(--transition);
    }

    .form-control::placeholder { color: #4b5563; }

    .form-control:focus {
        border-color: rgba(59,130,246,.5);
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    select.form-control { cursor: pointer; }

    /* Alerts */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        padding: .85rem 1rem;
        border-radius: var(--radius);
        font-size: .85rem;
        margin-bottom: 1rem;
    }

    .alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.2);  color: #fca5a5; }
    .alert-success { background: rgba(22,163,74,.1);  border: 1px solid rgba(22,163,74,.2);  color: #86efac; }
    .alert-info    { background: rgba(37,99,235,.1);  border: 1px solid rgba(37,99,235,.2);  color: #93c5fd; }
    .alert-warning { background: rgba(217,119,6,.1);  border: 1px solid rgba(217,119,6,.2);  color: #fcd34d; }

    /* Empty state */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .75rem;
        padding: 3rem 1.5rem;
        color: var(--text-dim);
        text-align: center;
    }

    .empty-state i { font-size: 2rem; opacity: .4; }
    .empty-state p { font-size: .85rem; }

    /* ═══════════════════════════════════════════════════════════════════════════
       MOBILE
    ═══════════════════════════════════════════════════════════════════════════ */
    @media (max-width: 768px) {
        .app-sidebar {
            transform: translateX(calc(-1 * var(--sidebar-w)));
        }

        .app-sidebar.sidebar-open {
            transform: translateX(0);
            box-shadow: 4px 0 40px rgba(0,0,0,.5);
        }

        .sidebar-overlay.sidebar-open { display: block; }

        .app-content { margin-left: 0; }

        .topbar-toggle { display: flex; }

        .app-main { padding: 1rem; }
    }
    </style>
</head>
<body>

<?php
$auth      = $auth      ?? false;
$protegida = $protegida ?? false;
$login     = $_SESSION['login'] ?? false;
?>

<?php if ($auth && $login): ?>

    <!-- ── AUTH LAYOUT: sidebar + content ───────────────────────────────── -->
    <div class="app-shell">

        <?php include_once __DIR__ . '/modulos/sidebar.php'; ?>

        <div class="app-content">

            <!-- Topbar -->
            <header class="app-topbar">
                <button class="topbar-toggle" onclick="toggleSidebar()" title="Menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-breadcrumb">
                    <span class="topbar-page"><?php echo htmlspecialchars($page_title ?? 'Dashboard', ENT_QUOTES); ?></span>
                </div>
                <div class="topbar-right">
                    <span class="topbar-time" id="topbarClock"></span>
                </div>
            </header>

            <!-- Main -->
            <main class="app-main">
                <?php if ($protegida && !$auth): ?>
                    <script>location.href='login';</script>
                <?php else: ?>
                    <?php echo $contenido; ?>
                <?php endif; ?>
            </main>

        </div><!-- /.app-content -->

    </div><!-- /.app-shell -->

    <script>
    function toggleSidebar() {
        document.getElementById('appSidebar').classList.toggle('sidebar-open');
        document.getElementById('sidebarOverlay').classList.toggle('sidebar-open');
    }
    function closeSidebar() {
        document.getElementById('appSidebar').classList.remove('sidebar-open');
        document.getElementById('sidebarOverlay').classList.remove('sidebar-open');
    }
    // Reloj en topbar
    (function tick() {
        var el = document.getElementById('topbarClock');
        if (el) {
            var now = new Date();
            el.textContent = now.toLocaleTimeString('es-AR', { hour:'2-digit', minute:'2-digit' });
        }
        setTimeout(tick, 30000);
    })();
    </script>

<?php else: ?>

    <!-- ── PUBLIC LAYOUT ────────────────────────────────────────────────── -->
    <header class="public-header">
        <strong>AKIM</strong>
    </header>
    <main class="akim-main">
        <?php echo $contenido; ?>
    </main>
    <footer class="akim-footer">
        AKIM &copy; <?php echo date('Y'); ?>
    </footer>

<?php endif; ?>

</body>
</html>
