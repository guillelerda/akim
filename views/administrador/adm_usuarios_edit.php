<div class="adm-card" style="max-width:640px">
    <div class="adm-card-title">
        <i class="fa-solid fa-<?= $es_nuevo ? 'user-plus' : 'user-pen' ?>"></i>
        <?= $es_nuevo ? 'Nuevo usuario' : 'Editar usuario: <span style="font-family:monospace">' . htmlspecialchars($usuario['username'] ?? '') . '</span>' ?>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="a-flash a-flash--err">
        <div>
        <?php foreach ($errores as $e): ?>
            <div><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="adm_usuarios_edit" autocomplete="off">

        <div class="a-form-grid" style="margin-bottom:.5rem">

            <!-- Username -->
            <div class="a-form-group">
                <label class="a-form-label" for="fld_username">
                    Usuario <?php if ($es_nuevo): ?><span>*</span><?php endif; ?>
                </label>
                <?php if ($es_nuevo): ?>
                    <input type="text" id="fld_username" name="username" class="a-form-input"
                        value="<?= htmlspecialchars($usuario['username'] ?? '') ?>"
                        placeholder="nombre.usuario" required autocomplete="off" maxlength="50">
                    <span class="a-form-hint">Solo minúsculas, números y punto.</span>
                <?php else: ?>
                    <input type="text" class="a-form-input"
                        value="<?= htmlspecialchars($usuario['username'] ?? '') ?>"
                        readonly style="opacity:.5;cursor:not-allowed">
                    <input type="hidden" name="username" value="<?= htmlspecialchars($usuario['username'] ?? '') ?>">
                <?php endif; ?>
            </div>

            <!-- Perfil -->
            <div class="a-form-group">
                <label class="a-form-label" for="fld_perfil">Perfil <span>*</span></label>
                <select id="fld_perfil" name="perfil" class="a-form-select" required>
                    <?php
                    $perfiles  = [PERFIL_ADMIN_AKIM, 'Supervisor', 'Operador', 'Vendedor'];
                    $selPerfil = $usuario['perfil_dec'] ?? 'Operador';
                    foreach ($perfiles as $p):
                    ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $selPerfil === $p ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nombre completo -->
            <div class="a-form-group a-form-full">
                <label class="a-form-label" for="fld_nombre">Nombre completo <span>*</span></label>
                <input type="text" id="fld_nombre" name="nombre" class="a-form-input"
                    value="<?= htmlspecialchars($usuario['nombre_dec'] ?? '') ?>"
                    placeholder="Juan Pérez" required maxlength="120">
            </div>

            <!-- Email -->
            <div class="a-form-group">
                <label class="a-form-label" for="fld_email">Email <span>*</span></label>
                <input type="email" id="fld_email" name="email" class="a-form-input"
                    value="<?= htmlspecialchars($usuario['email_dec'] ?? '') ?>"
                    placeholder="usuario@ejemplo.com" required maxlength="120">
            </div>

            <!-- Teléfono -->
            <div class="a-form-group">
                <label class="a-form-label" for="fld_tel">Teléfono</label>
                <input type="tel" id="fld_tel" name="telefono" class="a-form-input"
                    value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
                    placeholder="+54 9 351 000-0000" maxlength="30">
            </div>

            <!-- Contraseña -->
            <div class="a-form-group">
                <label class="a-form-label" for="fld_pass">
                    Contraseña
                    <?php if ($es_nuevo): ?>
                        <span>*</span>
                    <?php else: ?>
                        <span style="font-size:.68rem;text-transform:none;letter-spacing:0;color:var(--text-muted)">(vacío = sin cambios)</span>
                    <?php endif; ?>
                </label>
                <input type="password" id="fld_pass" name="password" class="a-form-input"
                    placeholder="<?= $es_nuevo ? 'Mínimo 8 caracteres' : 'Dejar vacío para no cambiar' ?>"
                    <?= $es_nuevo ? 'required minlength="8"' : 'minlength="8"' ?>
                    autocomplete="new-password">
            </div>

            <!-- Confirmar contraseña -->
            <div class="a-form-group">
                <label class="a-form-label" for="fld_pass2">Confirmar contraseña</label>
                <input type="password" id="fld_pass2" class="a-form-input"
                    placeholder="Repetir contraseña"
                    autocomplete="new-password">
            </div>

            <!-- Activo -->
            <div class="a-form-group a-form-full" style="flex-direction:row;align-items:center;gap:.75rem;padding:.6rem 0">
                <input type="checkbox" id="fld_confirmado" name="confirmado" value="1"
                    <?= (int)($usuario['confirmado'] ?? 1) === 1 ? 'checked' : '' ?>
                    style="width:1.1rem;height:1.1rem;accent-color:#2563eb;cursor:pointer;flex-shrink:0">
                <label for="fld_confirmado" class="a-form-label" style="cursor:pointer;text-transform:none;letter-spacing:0;font-size:.85rem;margin:0">
                    Usuario activo (puede iniciar sesión)
                </label>
            </div>

        </div><!-- /a-form-grid -->

        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?= $es_nuevo ? 'Crear usuario' : 'Guardar cambios' ?>
            </button>
            <a href="adm_usuarios" class="a-btn a-btn--ghost">
                <i class="fa-solid fa-arrow-left"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var p1 = document.getElementById('fld_pass').value;
    var p2 = document.getElementById('fld_pass2').value;
    if (p1 && p1 !== p2) {
        e.preventDefault();
        alert('Las contraseñas no coinciden.');
        document.getElementById('fld_pass2').focus();
    }
});
</script>
