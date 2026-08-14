<div class="login-card login-card--token">

    <div class="login-card-brand">
        <div class="login-token-icon">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>
            </svg>
        </div>
        <h1 class="login-card-title">Verificación en dos pasos</h1>
        <p class="login-card-sub">Ingresá el código de <strong>8 dígitos</strong> enviado por Telegram</p>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="login-errors">
        <?php foreach ($errores as $e): ?>
            <p><?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    $debug_token = $_SESSION['admin_token_debug'] ?? '';
    if ($debug_token !== ''):
        unset($_SESSION['admin_token_debug']);
    ?>
    <div class="token-debug-box">
        <div class="token-debug-label">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Modo desarrollo — Telegram no configurado. Tu token:
        </div>
        <div class="token-debug-val"><?php echo htmlspecialchars($debug_token, ENT_QUOTES); ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="tokenAdmin" class="login-form" autocomplete="off">

        <div class="form-group">
            <label for="token">Código Token</label>
            <input
                type="text"
                id="token"
                name="token"
                placeholder="• • • • • • • •"
                inputmode="numeric"
                maxlength="8" minlength="8"
                autocomplete="one-time-code"
                autofocus required
                class="token-input"
            >
        </div>

        <button type="submit" class="btn-login btn-login--green">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Verificar acceso
        </button>

    </form>

    <div class="login-forgot">
        <a href="logout">&larr; Cancelar y salir</a>
    </div>

</div>

<style>
.login-card--token .login-card-brand {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; margin-bottom: 1.75rem;
}
.login-token-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: rgba(37,99,235,.15);
    border: 1px solid rgba(37,99,235,.25);
    display: flex; align-items: center; justify-content: center;
    color: #60a5fa;
    margin-bottom: .85rem;
    box-shadow: 0 0 24px rgba(37,99,235,.15);
}
.token-input {
    text-align: center !important;
    font-size: 1.75rem !important;
    font-weight: 700 !important;
    letter-spacing: .35em !important;
    padding: .9rem 1rem !important;
}
.token-debug-box {
    background: rgba(217,119,6,.1);
    border: 1px solid rgba(217,119,6,.3);
    border-radius: 10px;
    padding: .85rem 1rem;
    margin-bottom: 1.25rem;
    text-align: center;
}
.token-debug-label {
    display: flex; align-items: center; justify-content: center; gap: .4rem;
    font-size: .72rem; color: #92400e; margin-bottom: .5rem;
    color: #fcd34d;
}
.token-debug-val {
    font-size: 2rem; font-weight: 800; letter-spacing: .3em;
    color: #fbbf24;
    font-family: monospace;
}
.btn-login--green {
    background: linear-gradient(135deg, #16a34a, #15803d) !important;
    box-shadow: 0 4px 16px rgba(22,163,74,.35) !important;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.btn-login--green:hover {
    box-shadow: 0 6px 24px rgba(22,163,74,.5) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('token');
    input.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 8);
    });
    // Auto-submit cuando se ingresan 8 dígitos
    input.addEventListener('input', function () {
        if (this.value.length === 8) {
            this.closest('form').submit();
        }
    });
});
</script>
