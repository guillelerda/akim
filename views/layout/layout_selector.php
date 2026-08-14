<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKIM — Seleccionar empresa</title>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        min-height: 100vh;
        background-color: #030712;
        background-image:
            radial-gradient(ellipse 80% 60% at 50% -10%, rgba(37,99,235,.18) 0%, transparent 70%),
            radial-gradient(ellipse 50% 40% at 80% 90%,  rgba(29,78,216,.10) 0%, transparent 60%);
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        color: #f9fafb;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* ── Topbar ─────────────────────────────────────────────────────────────── */
    .sel-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 2rem;
        border-bottom: 1px solid rgba(255,255,255,.06);
        background: rgba(17,24,39,.7);
        backdrop-filter: blur(12px);
    }

    .sel-logo {
        display: flex;
        align-items: center;
        gap: .75rem;
        text-decoration: none;
    }

    .sel-logo-mark {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.04em;
    }

    .sel-logo-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -.03em;
    }

    .sel-logo-name span { color: #3b82f6; }

    .sel-user {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: .85rem;
        color: #9ca3af;
    }

    .sel-user a {
        color: #6b7280;
        text-decoration: none;
        font-size: .8rem;
        padding: .35rem .75rem;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 8px;
        transition: color .15s, border-color .15s;
    }

    .sel-user a:hover { color: #f87171; border-color: rgba(239,68,68,.3); }

    /* ── Main ───────────────────────────────────────────────────────────────── */
    .sel-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 3rem 1.5rem 4rem;
    }

    .sel-header {
        text-align: center;
        margin-bottom: 2.5rem;
        animation: fadeUp .4s ease both;
    }

    .sel-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -.03em;
        margin-bottom: .4rem;
    }

    .sel-header p {
        font-size: .9rem;
        color: #6b7280;
    }

    /* ── Errores ────────────────────────────────────────────────────────────── */
    .sel-errors {
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.25);
        border-radius: 10px;
        padding: .85rem 1.1rem;
        margin-bottom: 1.5rem;
        max-width: 640px;
        width: 100%;
        animation: fadeUp .4s ease both;
    }

    .sel-errors p {
        font-size: .83rem;
        color: #fca5a5;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .sel-errors p::before { content: '⚠'; }

    /* ── Contenido de vista ─────────────────────────────────────────────────── */
    .sel-content { width: 100%; max-width: 900px; animation: fadeUp .45s .05s ease both; }

    /* ── Footer ─────────────────────────────────────────────────────────────── */
    .sel-footer {
        text-align: center;
        padding: 1.25rem;
        font-size: .75rem;
        color: #1f2937;
        border-top: 1px solid rgba(255,255,255,.04);
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    </style>
</head>
<body>

<header class="sel-topbar">
    <a class="sel-logo" href="menu">
        <div class="sel-logo-mark">AK</div>
        <span class="sel-logo-name">A<span>KIM</span></span>
    </a>
    <div class="sel-user">
        <span>Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES); ?></strong></span>
        <a href="logout">Cerrar sesión</a>
    </div>
</header>

<main class="sel-main">

    <div class="sel-header">
        <h1>Seleccioná la empresa</h1>
        <p>Elegí con qué empresa querés trabajar en esta sesión</p>
    </div>

    <?php echo $contenido; ?>

</main>

<footer class="sel-footer">
    &copy; <?php echo date('Y'); ?> AKIM &mdash; Todos los derechos reservados
</footer>

</body>
</html>
