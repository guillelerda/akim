<div class="login-card login-card--admin">

    <div class="login-card-brand">
        <div class="login-admin-icon">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>
        <h1 class="login-card-title">Panel Administrador</h1>
        <p class="login-card-sub">Ingresá tus credenciales para solicitar el token de acceso</p>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="login-errors">
        <?php foreach ($errores as $e): ?>
            <p><?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="loginAdmin" class="login-form" autocomplete="off">

        <div class="form-group">
            <label for="username">Usuario Admin</label>
            <div class="input-wrap">
                <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>"
                    placeholder="Tu nombre de usuario"
                    autocomplete="username"
                    autofocus required
                    style="text-transform:uppercase"
                >
            </div>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="input-wrap">
                <svg class="input-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <input type="password" id="password" name="password" placeholder="••••••••"
                    autocomplete="current-password" required>
                <button type="button" class="toggle-pass" id="togglePass" tabindex="-1">
                    <svg id="eye-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login btn-login--amber">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>
            </svg>
            Solicitar Token Telegram
        </button>

    </form>

    <div class="login-forgot">
        <a href="login">&larr; Volver al acceso de usuarios</a>
    </div>

</div>

<style>
.login-card--admin .login-card-brand {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; margin-bottom: 1.75rem;
}
.login-admin-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: rgba(217,119,6,.15);
    border: 1px solid rgba(217,119,6,.25);
    display: flex; align-items: center; justify-content: center;
    color: #fbbf24;
    margin-bottom: .85rem;
    box-shadow: 0 0 24px rgba(217,119,6,.15);
}
.login-card--admin .login-card-title {
    font-size: 1.2rem; margin-bottom: .25rem;
}
.input-wrap {
    position: relative; display: flex; align-items: center;
}
.input-wrap input {
    padding-left: 2.5rem !important;
}
.input-icon {
    position: absolute; left: .85rem;
    color: #4b5563; pointer-events: none;
}
.toggle-pass {
    position: absolute; right: .85rem;
    background: none; border: none; cursor: pointer;
    color: #4b5563; padding: 0; display: flex; align-items: center;
    transition: color .15s;
}
.toggle-pass:hover { color: #9ca3af; }
.btn-login--amber {
    background: linear-gradient(135deg, #d97706, #b45309) !important;
    box-shadow: 0 4px 16px rgba(217,119,6,.35) !important;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.btn-login--amber:hover {
    box-shadow: 0 6px 24px rgba(217,119,6,.5) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('togglePass');
    var pass   = document.getElementById('password');
    if (toggle && pass) {
        toggle.addEventListener('click', function () {
            var show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            document.getElementById('eye-icon').style.opacity = show ? '.5' : '1';
        });
    }
});
</script>
