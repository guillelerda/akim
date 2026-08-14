<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKIM — Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
    /* ── Reset ─────────────────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Base ──────────────────────────────────────────────────────────────── */
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
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    /* ── Wrapper ───────────────────────────────────────────────────────────── */
    .login-wrap {
        width: 100%;
        max-width: 420px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2rem;
    }

    /* ── Logo ──────────────────────────────────────────────────────────────── */
    .login-logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .5rem;
        text-decoration: none;
    }

    .login-logo-mark {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.04em;
        box-shadow: 0 0 40px rgba(37,99,235,.35), 0 8px 24px rgba(0,0,0,.4);
    }

    .login-logo-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -.03em;
    }

    .login-logo-name span {
        color: #3b82f6;
    }

    .login-logo-tag {
        font-size: .78rem;
        color: #6b7280;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 500;
    }

    /* ── Card ──────────────────────────────────────────────────────────────── */
    .login-card {
        width: 100%;
        background: rgba(17, 24, 39, 0.8);
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 20px;
        padding: 2.25rem 2rem;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow:
            0 0 80px rgba(37,99,235,.08),
            0 32px 64px rgba(0,0,0,.4),
            inset 0 1px 0 rgba(255,255,255,.05);
    }

    .login-card-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: .4rem;
        letter-spacing: -.02em;
    }

    .login-card-sub {
        font-size: .85rem;
        color: #6b7280;
        margin-bottom: 1.75rem;
    }

    /* ── Errores ───────────────────────────────────────────────────────────── */
    .login-errors {
        background: rgba(239,68,68,.12);
        border: 1px solid rgba(239,68,68,.25);
        border-radius: 10px;
        padding: .85rem 1rem;
        margin-bottom: 1.25rem;
    }

    .login-errors p {
        font-size: .82rem;
        color: #fca5a5;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .login-errors p::before { content: '⚠'; }

    /* ── Form ──────────────────────────────────────────────────────────────── */
    .login-form { display: flex; flex-direction: column; gap: 1.1rem; }

    .form-group { display: flex; flex-direction: column; gap: .45rem; }

    .form-group label {
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #9ca3af;
    }

    .form-group input {
        width: 100%;
        padding: .8rem 1rem;
        background: rgba(31, 41, 55, 0.5);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 12px;
        color: #fff;
        font-size: .95rem;
        font-family: inherit;
        outline: none;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }

    .form-group input::placeholder { color: #4b5563; }

    .form-group input:focus {
        border-color: rgba(59,130,246,.5);
        background: rgba(31,41,55,.8);
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    }

    /* ── Botón principal ───────────────────────────────────────────────────── */
    .btn-login {
        width: 100%;
        padding: .85rem;
        margin-top: .35rem;
        background: linear-gradient(to right, #2563eb, #1d4ed8);
        border: none;
        border-radius: 12px;
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: opacity .2s, transform .1s, box-shadow .2s;
        box-shadow: 0 4px 16px rgba(37,99,235,.35);
        letter-spacing: .01em;
    }

    .btn-login:hover  { opacity: .9; box-shadow: 0 6px 24px rgba(37,99,235,.5); }
    .btn-login:active { transform: scale(.99); }

    /* ── Link olvidé ───────────────────────────────────────────────────────── */
    .login-forgot {
        text-align: center;
        margin-top: 1rem;
    }

    .login-forgot a {
        font-size: .82rem;
        color: #6b7280;
        text-decoration: none;
        transition: color .15s;
    }

    .login-forgot a:hover { color: #3b82f6; }

    /* ── Footer ────────────────────────────────────────────────────────────── */
    .login-footer {
        font-size: .75rem;
        color: #374151;
        text-align: center;
    }

    /* ── Animación de entrada ──────────────────────────────────────────────── */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .login-logo  { animation: fadeUp .45s ease both; }
    .login-card  { animation: fadeUp .5s .08s ease both; }
    .login-footer{ animation: fadeUp .5s .16s ease both; }

    /* ── Responsive ────────────────────────────────────────────────────────── */
    @media (max-width: 480px) {
        .login-card { padding: 1.75rem 1.25rem; border-radius: 16px; }
        .login-logo-mark { width: 54px; height: 54px; font-size: 1.5rem; }
    }
    </style>
</head>
<body>

<div class="login-wrap">

    <!-- Logo -->
    <a class="login-logo" href=".">
        <div class="login-logo-mark">AK</div>
        <div class="login-logo-name">A<span>KIM</span></div>
        <div class="login-logo-tag">Gestión comercial</div>
    </a>

    <!-- Card con el contenido de la vista -->
    <?php echo $contenido; ?>

    <!-- Footer -->
    <p class="login-footer">&copy; <?php echo date('Y'); ?> AKIM &mdash; Todos los derechos reservados</p>

</div>

</body>
</html>
