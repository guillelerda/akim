<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKIM — Panel Administrador</title>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --bg:        #030712;
        --surface:   #0b1120;
        --surface2:  #111827;
        --surface3:  #1f2937;
        --border:    rgba(255,255,255,.07);
        --accent:    #2563eb;
        --amber:     #d97706;
        --text:      #f9fafb;
        --text-muted:#6b7280;
        --sidebar-w: 220px;
        --radius:    10px;
    }

    html, body {
        height: 100%; background: var(--bg);
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        color: var(--text); font-size: 15px;
    }

    /* ── SHELL ──────────────────────────────────────────────────────── */
    .adm-shell { display: flex; height: 100vh; overflow: hidden; }

    /* ── SIDEBAR ────────────────────────────────────────────────────── */
    .adm-sidebar {
        width: var(--sidebar-w);
        background: var(--surface);
        border-right: 1px solid var(--border);
        display: flex; flex-direction: column;
        flex-shrink: 0; overflow-y: auto;
    }
    .adm-sidebar-brand {
        display: flex; align-items: center; gap: .65rem;
        padding: 1.25rem 1.25rem 1rem;
        border-bottom: 1px solid var(--border);
    }
    .adm-brand-mark {
        width: 34px; height: 34px; border-radius: 9px;
        background: linear-gradient(135deg, var(--amber) 0%, #b45309 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 800; color: #fff;
        box-shadow: 0 0 16px rgba(217,119,6,.25);
        flex-shrink: 0;
    }
    .adm-brand-info { min-width: 0; }
    .adm-brand-name { font-size: .9rem; font-weight: 700; color: #fff; }
    .adm-brand-tag  { font-size: .65rem; color: var(--amber); font-weight: 500;
                      text-transform: uppercase; letter-spacing: .06em; }

    .adm-nav { flex: 1; padding: .75rem 0; }
    .adm-nav-section {
        padding: .5rem 1rem .25rem;
        font-size: .65rem; font-weight: 600; letter-spacing: .08em;
        color: var(--text-muted); text-transform: uppercase;
    }
    .adm-nav a {
        display: flex; align-items: center; gap: .65rem;
        padding: .55rem 1.25rem;
        font-size: .82rem; color: #9ca3af;
        text-decoration: none;
        border-radius: 0;
        transition: background .15s, color .15s;
        border-left: 2px solid transparent;
    }
    .adm-nav a:hover {
        background: rgba(255,255,255,.04);
        color: var(--text);
    }
    .adm-nav a.active {
        color: #fbbf24;
        border-left-color: var(--amber);
        background: rgba(217,119,6,.08);
    }
    .adm-nav a svg { flex-shrink: 0; opacity: .7; }
    .adm-nav a.active svg { opacity: 1; }

    .adm-sidebar-foot {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--border);
        display: flex; flex-direction: column; gap: .5rem;
    }
    .adm-foot-user {
        font-size: .75rem; color: var(--text-muted);
    }
    .adm-foot-user strong { display: block; color: var(--text); font-size: .8rem; }
    .adm-logout {
        display: flex; align-items: center; gap: .5rem;
        font-size: .75rem; color: #ef4444; text-decoration: none;
        padding: .35rem 0; transition: opacity .15s;
    }
    .adm-logout:hover { opacity: .75; }

    /* ── CONTENT ────────────────────────────────────────────────────── */
    .adm-content {
        flex: 1; overflow-y: auto;
        display: flex; flex-direction: column;
    }
    .adm-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 2rem;
        background: rgba(11,17,32,.8);
        border-bottom: 1px solid var(--border);
        backdrop-filter: blur(8px);
        position: sticky; top: 0; z-index: 10;
    }
    .adm-topbar-title { font-size: 1rem; font-weight: 600; }
    .adm-topbar-meta  { font-size: .75rem; color: var(--text-muted); }
    .adm-badge-admin {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .7rem; border-radius: 100px;
        background: rgba(217,119,6,.12); border: 1px solid rgba(217,119,6,.2);
        font-size: .7rem; font-weight: 600; color: #fbbf24;
        text-transform: uppercase; letter-spacing: .06em;
    }
    .adm-main {
        flex: 1; padding: 2rem;
    }

    /* ── CARDS genericas ────────────────────────────────────────────── */
    .adm-card {
        background: var(--surface2);
        border: 1px solid var(--border);
        border-radius: var(--radius); padding: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .adm-card-title {
        font-size: .9rem; font-weight: 600; margin-bottom: 1rem;
        padding-bottom: .75rem; border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
    }

    /* ── FLASH ──────────────────────────────────────────────────────── */
    .a-flash { border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.25rem; font-size: .83rem; display: flex; align-items: center; gap: .5rem; }
    .a-flash--ok  { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.25); color: #6ee7b7; }
    .a-flash--err { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.25);  color: #fca5a5; }

    /* ── TOOLBAR ─────────────────────────────────────────────────────── */
    .a-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
    .a-toolbar-title { font-size: 1rem; font-weight: 600; }
    .a-toolbar-actions { display: flex; gap: .5rem; align-items: center; }

    /* ── BUTTONS ─────────────────────────────────────────────────────── */
    .a-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: 8px; font-size: .8rem; font-weight: 600; font-family: inherit; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: opacity .15s, box-shadow .15s; white-space: nowrap; }
    .a-btn--primary { background: #2563eb; color: #fff; border-color: #1d4ed8; }
    .a-btn--primary:hover { opacity: .9; box-shadow: 0 2px 12px rgba(37,99,235,.4); }
    .a-btn--amber   { background: rgba(217,119,6,.15); color: #fbbf24; border-color: rgba(217,119,6,.3); }
    .a-btn--amber:hover { background: rgba(217,119,6,.25); }
    .a-btn--ghost   { background: rgba(255,255,255,.04); color: var(--text-muted); border-color: var(--border); }
    .a-btn--ghost:hover { background: rgba(255,255,255,.08); color: var(--text); }
    .a-btn--danger  { background: rgba(239,68,68,.12); color: #f87171; border-color: rgba(239,68,68,.25); }
    .a-btn--danger:hover { background: rgba(239,68,68,.22); }
    .a-btn--sm { padding: .3rem .65rem; font-size: .72rem; }
    .a-btn--lg { padding: .65rem 1.4rem; font-size: .9rem; }

    /* ── TABLE ───────────────────────────────────────────────────────── */
    .a-table-wrap { overflow-x: auto; }
    .a-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .a-table th { padding: .6rem .85rem; text-align: left; font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
    .a-table td { padding: .65rem .85rem; border-bottom: 1px solid rgba(255,255,255,.04); color: #d1d5db; vertical-align: middle; }
    .a-table tr:last-child td { border-bottom: none; }
    .a-table tr:hover td { background: rgba(255,255,255,.025); }
    .a-table .td-actions { display: flex; gap: .4rem; }

    /* ── BADGES ──────────────────────────────────────────────────────── */
    .a-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 6px; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .a-badge--on   { background: rgba(16,185,129,.12); color: #6ee7b7; border: 1px solid rgba(16,185,129,.2); }
    .a-badge--off  { background: rgba(107,114,128,.12); color: #6b7280; border: 1px solid rgba(107,114,128,.2); }
    .a-badge--warn { background: rgba(245,158,11,.12);  color: #fbbf24; border: 1px solid rgba(245,158,11,.2); }
    .a-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block; }

    /* ── FORM ────────────────────────────────────────────────────────── */
    .a-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.5rem; }
    .a-form-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
    .a-form-full { grid-column: 1 / -1; }
    .a-form-group { display: flex; flex-direction: column; gap: .4rem; }
    .a-form-label { font-size: .7rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--text-muted); }
    .a-form-label span { color: #f87171; margin-left: .15rem; }
    .a-form-input, .a-form-select, .a-form-textarea {
        width: 100%; padding: .6rem .85rem;
        background: rgba(31,41,55,.6); border: 1px solid rgba(255,255,255,.1);
        border-radius: 8px; color: var(--text); font-size: .87rem; font-family: inherit;
        outline: none; transition: border-color .2s, box-shadow .2s;
    }
    .a-form-input:focus, .a-form-select:focus, .a-form-textarea:focus {
        border-color: rgba(59,130,246,.5);
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }
    .a-form-input::placeholder { color: #374151; }
    .a-form-select option { background: #1f2937; }
    .a-form-textarea { resize: vertical; min-height: 80px; }
    .a-form-hint { font-size: .72rem; color: #4b5563; margin-top: .15rem; }
    .a-form-actions { display: flex; gap: .75rem; align-items: center; padding-top: 1rem; border-top: 1px solid var(--border); margin-top: 1.25rem; }

    /* ── PASS FIELD ──────────────────────────────────────────────────── */
    .a-pass-wrap { position: relative; }
    .a-pass-wrap .a-form-input { padding-right: 2.5rem; }
    .a-pass-toggle { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: .75rem; padding: 0; }

    /* ── EMPTY STATE ─────────────────────────────────────────────────── */
    .a-empty { text-align: center; padding: 3rem 1rem; color: var(--text-muted); }
    .a-empty-icon { font-size: 2rem; margin-bottom: .75rem; opacity: .4; }

    /* ── BACK LINK ───────────────────────────────────────────────────── */
    .a-back { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; color: var(--text-muted); text-decoration: none; margin-bottom: 1rem; transition: color .15s; }
    .a-back:hover { color: var(--text); }

    /* ── MODULES GRID ────────────────────────────────────────────────── */
    .a-mod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .65rem; }
    .a-mod-item { position: relative; }
    .a-mod-item input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
    .a-mod-label { display: flex; flex-direction: column; align-items: center; gap: .4rem; padding: .85rem .5rem; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; transition: border-color .15s, background .15s; text-align: center; }
    .a-mod-label:hover { border-color: rgba(59,130,246,.35); }
    .a-mod-item input:checked + .a-mod-label { border-color: #2563eb; background: rgba(37,99,235,.1); }
    .a-mod-icon { font-size: 1.3rem; }
    .a-mod-name { font-size: .72rem; font-weight: 600; color: var(--text-muted); }
    .a-mod-item input:checked + .a-mod-label .a-mod-name { color: #93c5fd; }

    @media (max-width: 768px) {
        .a-form-grid, .a-form-grid--3 { grid-template-columns: 1fr; }
        .adm-main { padding: 1rem; }
    }
    </style>
</head>
<body>

<div class="adm-shell">

    <!-- SIDEBAR -->
    <aside class="adm-sidebar">
        <div class="adm-sidebar-brand">
            <div class="adm-brand-mark">AK</div>
            <div class="adm-brand-info">
                <div class="adm-brand-name">AKIM</div>
                <div class="adm-brand-tag">Administración</div>
            </div>
        </div>

        <nav class="adm-nav">
            <div class="adm-nav-section">Panel</div>
            <a href="adminPanel" <?php if(str_contains($_SERVER['REQUEST_URI']??'','adminPanel')) echo 'class="active"'; ?>>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>

            <div class="adm-nav-section">Gestión</div>
            <a href="adm_empresas" <?php if(str_contains($_SERVER['REQUEST_URI']??'','adm_empresa')) echo 'class="active"'; ?>>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Empresas
            </a>
            <a href="adm_usuarios" <?php if(str_contains($_SERVER['REQUEST_URI']??'','adm_usuario')) echo 'class="active"'; ?>>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Usuarios
            </a>
            <a href="adm_licencias" <?php if(str_contains($_SERVER['REQUEST_URI']??'','adm_licencia')) echo 'class="active"'; ?>>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                Licencias
            </a>
            <a href="adm_bases" <?php if(str_contains($_SERVER['REQUEST_URI']??'','adm_base')) echo 'class="active"'; ?>>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
                Bases de datos
            </a>
            <a href="adm_modulos" <?php if(str_contains($_SERVER['REQUEST_URI']??'','adm_modulo')) echo 'class="active"'; ?>>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                Módulos
            </a>

            <div class="adm-nav-section">Sistema</div>
            <a href="menu">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                Ir al menú usuario
            </a>
        </nav>

        <div class="adm-sidebar-foot">
            <div class="adm-foot-user">
                <strong><?php echo htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES); ?></strong>
                Administrador AKIM
            </div>
            <a class="adm-logout" href="logoutAdmin">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                Cerrar sesión admin
            </a>
        </div>
    </aside>

    <!-- CONTENT -->
    <div class="adm-content">
        <div class="adm-topbar">
            <div class="adm-topbar-title">
                <?php echo htmlspecialchars($page_title ?? 'Panel Administrador', ENT_QUOTES); ?>
            </div>
            <div style="display:flex;align-items:center;gap:1rem">
                <div class="adm-badge-admin">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Admin
                </div>
                <span class="adm-topbar-meta"><?php echo date('d/m/Y H:i'); ?></span>
            </div>
        </div>

        <main class="adm-main">
            <?php
                $protegida = $protegida ?? true;
                $auth      = ($_SESSION['loginADMIN'] ?? false) && ($_SESSION['login'] ?? false);
                if (!$protegida || $auth) {
                    echo $contenido;
                } else {
                    echo "<script>location.href='loginAdmin';</script>";
                }
            ?>
        </main>
    </div>

</div>

</body>
</html>
