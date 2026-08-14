<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKIM — Gestión Comercial</title>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --bg:        #030712;
        --surface:   #0b1120;
        --surface2:  #111827;
        --border:    rgba(255,255,255,.07);
        --accent:    #2563eb;
        --accent2:   #1d4ed8;
        --text:      #f9fafb;
        --text-muted:#6b7280;
        --green:     #16a34a;
        --purple:    #7c3aed;
        --amber:     #d97706;
    }

    html, body {
        min-height: 100vh;
        background-color: var(--bg);
        background-image:
            radial-gradient(ellipse 80% 50% at 50% -5%,  rgba(37,99,235,.20) 0%, transparent 70%),
            radial-gradient(ellipse 40% 30% at 85% 80%,  rgba(124,58,237,.10) 0%, transparent 60%),
            radial-gradient(ellipse 30% 20% at 10% 70%,  rgba(16,163,74,.06) 0%, transparent 50%);
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        color: var(--text);
    }

    /* ── NAV ──────────────────────────────────────────────────────────────── */
    .home-nav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 100;
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 2.5rem;
        background: rgba(3,7,18,.7);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border);
    }
    .nav-brand {
        display: flex; align-items: center; gap: .75rem; text-decoration: none;
    }
    .nav-mark {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; font-weight: 800; color: #fff; letter-spacing: -.04em;
        box-shadow: 0 0 20px rgba(37,99,235,.3);
    }
    .nav-name {
        font-size: 1.15rem; font-weight: 700; color: #fff; letter-spacing: -.02em;
    }
    .nav-name span { color: #3b82f6; }
    .btn-nav-login {
        padding: .5rem 1.25rem;
        background: rgba(37,99,235,.15);
        border: 1px solid rgba(37,99,235,.3);
        border-radius: 8px;
        color: #93c5fd;
        font-size: .875rem; font-weight: 500; font-family: inherit;
        text-decoration: none;
        transition: all .2s;
    }
    .btn-nav-login:hover {
        background: rgba(37,99,235,.25);
        border-color: rgba(37,99,235,.5);
        color: #bfdbfe;
    }

    /* ── HERO ─────────────────────────────────────────────────────────────── */
    .hero {
        min-height: 100vh;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        padding: 7rem 1.5rem 5rem;
        text-align: center;
    }
    .hero-badge {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .35rem .9rem;
        background: rgba(37,99,235,.12);
        border: 1px solid rgba(37,99,235,.2);
        border-radius: 100px;
        font-size: .75rem; font-weight: 600; letter-spacing: .06em;
        color: #93c5fd; text-transform: uppercase;
        margin-bottom: 2rem;
    }
    .hero-badge::before {
        content: ''; display: block;
        width: 6px; height: 6px; border-radius: 50%;
        background: #3b82f6;
        box-shadow: 0 0 8px #3b82f6;
        animation: pulse 2s ease infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .6; transform: scale(1.3); }
    }
    .hero-title {
        font-size: clamp(2.8rem, 6vw, 4.5rem);
        font-weight: 800; letter-spacing: -.04em;
        line-height: 1.08;
        margin-bottom: 1.5rem;
    }
    .hero-title span {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .hero-sub {
        font-size: 1.15rem; color: var(--text-muted);
        max-width: 540px; line-height: 1.65;
        margin-bottom: 2.75rem;
    }
    .hero-cta {
        display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
    }
    .btn-hero-primary {
        display: inline-flex; align-items: center; gap: .6rem;
        padding: .9rem 2rem;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        border: none; border-radius: 12px;
        color: #fff; font-size: 1rem; font-weight: 600; font-family: inherit;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 4px 24px rgba(37,99,235,.4);
        transition: all .2s;
    }
    .btn-hero-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 32px rgba(37,99,235,.55);
    }
    .btn-hero-primary:active { transform: translateY(0); }

    /* ── FEATURES ─────────────────────────────────────────────────────────── */
    .features {
        padding: 4rem 1.5rem 6rem;
        max-width: 1100px; margin: 0 auto;
    }
    .features-title {
        text-align: center;
        font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em;
        margin-bottom: .6rem;
    }
    .features-sub {
        text-align: center;
        font-size: .9rem; color: var(--text-muted);
        margin-bottom: 3rem;
    }
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.25rem;
    }
    .feat-card {
        background: var(--surface2);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.75rem 1.5rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .feat-card:hover {
        border-color: rgba(37,99,235,.25);
        box-shadow: 0 0 24px rgba(37,99,235,.08);
    }
    .feat-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; margin-bottom: 1rem;
    }
    .feat-icon.blue   { background: rgba(37,99,235,.15);  color: #60a5fa; }
    .feat-icon.green  { background: rgba(22,163,74,.15);  color: #4ade80; }
    .feat-icon.purple { background: rgba(124,58,237,.15); color: #a78bfa; }
    .feat-icon.amber  { background: rgba(217,119,6,.15);  color: #fcd34d; }
    .feat-icon.pink   { background: rgba(219,39,119,.15); color: #f9a8d4; }
    .feat-icon.cyan   { background: rgba(6,182,212,.15);  color: #67e8f9; }
    .feat-name {
        font-size: .95rem; font-weight: 600; margin-bottom: .45rem;
    }
    .feat-desc {
        font-size: .82rem; color: var(--text-muted); line-height: 1.55;
    }

    /* ── FOOTER ───────────────────────────────────────────────────────────── */
    .home-footer {
        border-top: 1px solid var(--border);
        padding: 1.5rem 2.5rem;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: .75rem;
    }
    .home-footer-copy {
        font-size: .75rem; color: #374151;
    }
    /* link admin: casi invisible, solo visible al hover con mucho cuidado */
    .admin-link {
        font-size: .65rem;
        color: #1f2937;
        text-decoration: none;
        letter-spacing: .04em;
        transition: color .3s;
        user-select: none;
    }
    .admin-link:hover { color: #4b5563; }

    /* ── Animaciones ──────────────────────────────────────────────────────── */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .hero-badge  { animation: fadeUp .4s ease both; }
    .hero-title  { animation: fadeUp .5s .08s ease both; }
    .hero-sub    { animation: fadeUp .5s .16s ease both; }
    .hero-cta    { animation: fadeUp .5s .24s ease both; }
    .feat-card   { animation: fadeUp .5s .32s ease both; }

    @media (max-width: 640px) {
        .home-nav { padding: .9rem 1.25rem; }
        .hero { padding-top: 6rem; }
        .home-footer { flex-direction: column; align-items: center; text-align: center; }
    }
    </style>
</head>
<body>

<!-- NAV -->
<nav class="home-nav">
    <a class="nav-brand" href="/">
        <div class="nav-mark">AK</div>
        <div class="nav-name">A<span>KIM</span></div>
    </a>
    <a class="btn-nav-login" href="login">Iniciar sesión &rarr;</a>
</nav>

<!-- Contenido de la vista -->
<?php echo $contenido; ?>

<!-- FOOTER -->
<footer class="home-footer">
    <span class="home-footer-copy">&copy; <?php echo date('Y'); ?> AKIM — Todos los derechos reservados</span>
    <a class="admin-link" href="loginAdmin">&#9632;</a>
</footer>

</body>
</html>
