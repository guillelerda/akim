<?php
$registro = $registro ?? null;
$errores  = $errores  ?? [];
$es_edit  = !empty($registro);

$v = [
    'bd_code'  => $registro['bd_code']  ?? '',
    'bd_label' => $registro['bd_label'] ?? '',
    'bd_host'  => $registro['bd_host']  ?? '',
    'bd_name'  => $registro['bd_name']  ?? '',
    'bd_user'  => $registro['bd_user']  ?? '',
];
?>

<a href="adm_bases" class="a-back">← Volver a bases de datos</a>

<?php if ($errores): ?>
<div class="a-flash a-flash--err">
    <?php foreach ($errores as $e): ?><div>✗ <?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="adm-card">
    <div class="adm-card-title">
        <?php echo $es_edit ? 'Editar base de datos' : 'Nueva base de datos'; ?>
        <?php if ($es_edit): ?>
            <code style="font-size:.75rem;color:#93c5fd;font-weight:400"><?php echo htmlspecialchars($v['bd_code']); ?></code>
        <?php endif; ?>
    </div>

    <form method="POST" action="adm_bases_edit" autocomplete="off">
        <div class="a-form-grid">

            <div class="a-form-group">
                <label class="a-form-label">Código (bd_code) <span>*</span></label>
                <input class="a-form-input" type="text" name="bd_code"
                       value="<?php echo htmlspecialchars($v['bd_code']); ?>"
                       placeholder="cliente_empresa_01" maxlength="30"
                       <?php echo $es_edit ? 'readonly style="opacity:.5"' : ''; ?> required>
                <span class="a-form-hint">Identificador único, sin espacios, en minúsculas.</span>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Etiqueta (bd_label) <span>*</span></label>
                <input class="a-form-input" type="text" name="bd_label"
                       value="<?php echo htmlspecialchars($v['bd_label']); ?>"
                       placeholder="Mi empresa — Producción" required>
                <span class="a-form-hint">Nombre amigable para identificar la base.</span>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Host <span>*</span></label>
                <input class="a-form-input" type="text" name="bd_host"
                       value="<?php echo htmlspecialchars($v['bd_host']); ?>"
                       placeholder="localhost" autocomplete="off" required>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Nombre de la base (BD) <span>*</span></label>
                <input class="a-form-input" type="text" name="bd_name"
                       value="<?php echo htmlspecialchars($v['bd_name']); ?>"
                       placeholder="akim_empresa01" autocomplete="off" required>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Usuario <span>*</span></label>
                <input class="a-form-input" type="text" name="bd_user"
                       value="<?php echo htmlspecialchars($v['bd_user']); ?>"
                       placeholder="db_user" autocomplete="off" required>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">
                    Contraseña <?php echo $es_edit ? '' : '<span>*</span>'; ?>
                </label>
                <div class="a-pass-wrap">
                    <input class="a-form-input" type="password" name="bd_pass"
                           id="bd_pass_input"
                           placeholder="<?php echo $es_edit ? 'Dejá vacío para no cambiar' : 'Contraseña de BD'; ?>"
                           autocomplete="new-password">
                    <button type="button" class="a-pass-toggle" onclick="togglePass()">👁</button>
                </div>
                <?php if ($es_edit): ?>
                    <span class="a-form-hint">Dejá el campo vacío para conservar la contraseña actual.</span>
                <?php endif; ?>
            </div>

        </div>

        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary a-btn--lg">
                <?php echo $es_edit ? 'Guardar cambios' : 'Crear base de datos'; ?>
            </button>
            <a href="adm_bases" class="a-btn a-btn--ghost">Cancelar</a>
            <?php if ($es_edit): ?>
                <span style="font-size:.75rem;color:var(--text-muted);margin-left:auto">
                    Los datos se guardan encriptados
                </span>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function togglePass() {
    var inp = document.getElementById('bd_pass_input');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
