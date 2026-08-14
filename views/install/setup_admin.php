<?php if ($ok ?? false): ?>

<!-- ── ÉXITO ─────────────────────────────────────────────────────────── -->
<div class="login-card setup-card">
    <div class="setup-success-icon">
        <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
    </div>
    <h1 class="login-card-title" style="text-align:center">¡Admin creado!</h1>
    <p class="login-card-sub" style="text-align:center;margin-bottom:1.5rem">
        El usuario administrador fue creado correctamente.<br>
        Esta página se bloqueará automáticamente.
    </p>
    <a href="loginAdmin" class="btn-login" style="display:block;text-align:center;text-decoration:none;padding:.85rem">
        Ir al login de administrador &rarr;
    </a>
</div>

<?php else: ?>

<!-- ── FORMULARIO ────────────────────────────────────────────────────── -->
<div class="login-card setup-card">

    <div class="setup-header">
        <div class="setup-icon">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>
        <h1 class="login-card-title">Crear Administrador AKIM</h1>
        <p class="login-card-sub">Configuración inicial de la plataforma</p>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="login-errors" style="margin-bottom:1.25rem">
        <?php foreach ($errores as $e): ?>
            <p><?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="setupAdmin" class="login-form" autocomplete="off">

        <div class="form-group">
            <label for="username">Usuario <span class="req">*</span></label>
            <input type="text" id="username" name="username"
                value="<?php echo htmlspecialchars(strtoupper($_POST['username'] ?? ''), ENT_QUOTES); ?>"
                placeholder="Ej: SUPERADMIN"
                style="text-transform:uppercase"
                autofocus required>
            <small class="field-hint">Solo letras y números, sin espacios. Se guarda en mayúsculas.</small>
        </div>

        <div class="form-group">
            <label for="nombre">Nombre completo <span class="req">*</span></label>
            <input type="text" id="nombre" name="nombre"
                value="<?php echo htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES); ?>"
                placeholder="Ej: Juan Pérez" required>
        </div>

        <div class="form-group">
            <label for="email">Email <span class="req">*</span></label>
            <input type="email" id="email" name="email"
                value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                placeholder="admin@tudominio.com" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono / WhatsApp <span class="req">*</span></label>
            <input type="tel" id="telefono" name="telefono"
                value="<?php echo htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES); ?>"
                placeholder="Ej: +54 9 351 123 4567" required>
            <small class="field-hint">Se usa para contacto y notificaciones del sistema.</small>
        </div>

        <div class="form-group">
            <label for="password">Contraseña <span class="req">*</span></label>
            <input type="password" id="password" name="password"
                placeholder="Mínimo 8 caracteres" minlength="8" required>
        </div>

        <div class="form-group">
            <label for="password2">Confirmar contraseña <span class="req">*</span></label>
            <input type="password" id="password2" name="password2"
                placeholder="Repetí la contraseña" required>
        </div>

        <div class="setup-info">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Perfil asignado automáticamente: <strong>Administrador AKIM</strong>
        </div>

        <button type="submit" class="btn-login btn-setup">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Crear administrador
        </button>

    </form>

    <div class="login-forgot">
        <a href="loginAdmin">&larr; Ya tengo cuenta, ir al login</a>
    </div>

</div>

<?php endif; ?>

<style>
.setup-card { padding: 2rem; }
.setup-header {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; margin-bottom: 1.75rem;
}
.setup-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: rgba(37,99,235,.15); border: 1px solid rgba(37,99,235,.25);
    display: flex; align-items: center; justify-content: center;
    color: #60a5fa; margin-bottom: .85rem;
    box-shadow: 0 0 24px rgba(37,99,235,.15);
}
.setup-success-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(22,163,74,.15); border: 1px solid rgba(22,163,74,.25);
    display: flex; align-items: center; justify-content: center;
    color: #4ade80; margin: 0 auto 1.25rem;
    box-shadow: 0 0 32px rgba(22,163,74,.2);
}
.req { color: #ef4444; }
.field-hint {
    font-size: .72rem; color: #4b5563; margin-top: .2rem; display: block;
}
.setup-info {
    display: flex; align-items: center; gap: .5rem;
    background: rgba(37,99,235,.08); border: 1px solid rgba(37,99,235,.15);
    border-radius: 8px; padding: .65rem .9rem;
    font-size: .78rem; color: #6b7280;
}
.setup-info strong { color: #93c5fd; }
.btn-setup {
    background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
</style>
