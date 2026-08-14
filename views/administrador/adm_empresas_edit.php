<?php
$registro = $registro ?? null;
$errores  = $errores  ?? [];
$es_edit  = !empty($registro);

$v = [
    'em_code'      => $registro['em_code']      ?? '',
    'em_nombre'    => $es_edit ? (\Model\Administrador\Model_empresas::decryptNombre($registro['em_nombre'] ?? '')) : '',
    'em_slogan'    => $registro['em_slogan']    ?? '',
    'em_cuit'      => $registro['em_cuit']      ?? '',
    'em_iibb'      => $registro['em_iibb']      ?? '',
    'em_domicilio' => $registro['em_domicilio'] ?? '',
    'em_telefono'  => $registro['em_telefono']  ?? '',
    'em_email'     => $registro['em_email']     ?? '',
    'habilitada'   => $registro['habilitada']   ?? 1,
];
?>

<a href="adm_empresas" class="a-back">← Volver a empresas</a>

<?php if ($errores): ?>
<div class="a-flash a-flash--err">
    <?php foreach ($errores as $e): ?><div>✗ <?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="adm-card">
    <div class="adm-card-title">
        <?php echo $es_edit ? 'Editar empresa' : 'Nueva empresa'; ?>
        <?php if ($es_edit): ?>
            <code style="font-size:.75rem;color:#93c5fd;font-weight:400"><?php echo htmlspecialchars($v['em_code']); ?></code>
        <?php endif; ?>
    </div>

    <form method="POST" action="adm_empresas_edit">
        <div class="a-form-grid">

            <div class="a-form-group">
                <label class="a-form-label">Código <span>*</span></label>
                <input class="a-form-input" type="text" name="em_code"
                       value="<?php echo htmlspecialchars($v['em_code']); ?>"
                       placeholder="EMP001" maxlength="20"
                       <?php echo $es_edit ? 'readonly style="opacity:.5"' : ''; ?> required>
                <span class="a-form-hint">Identificador único. No se puede cambiar.</span>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Nombre / Razón social <span>*</span></label>
                <input class="a-form-input" type="text" name="em_nombre"
                       value="<?php echo htmlspecialchars($v['em_nombre']); ?>"
                       placeholder="Mi empresa SRL" required>
            </div>

            <div class="a-form-group a-form-full">
                <label class="a-form-label">Slogan / Descripción</label>
                <input class="a-form-input" type="text" name="em_slogan"
                       value="<?php echo htmlspecialchars($v['em_slogan']); ?>"
                       placeholder="Tu aliado comercial">
            </div>

            <div class="a-form-group">
                <label class="a-form-label">CUIT</label>
                <input class="a-form-input" type="text" name="em_cuit"
                       value="<?php echo htmlspecialchars($v['em_cuit']); ?>"
                       placeholder="30-12345678-9">
            </div>

            <div class="a-form-group">
                <label class="a-form-label">IIBB</label>
                <input class="a-form-input" type="text" name="em_iibb"
                       value="<?php echo htmlspecialchars($v['em_iibb']); ?>"
                       placeholder="Nº de ingresos brutos">
            </div>

            <div class="a-form-group a-form-full">
                <label class="a-form-label">Domicilio</label>
                <input class="a-form-input" type="text" name="em_domicilio"
                       value="<?php echo htmlspecialchars($v['em_domicilio']); ?>"
                       placeholder="Av. Colón 123, Córdoba">
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Teléfono</label>
                <input class="a-form-input" type="text" name="em_telefono"
                       value="<?php echo htmlspecialchars($v['em_telefono']); ?>"
                       placeholder="351 123-4567">
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Email</label>
                <input class="a-form-input" type="email" name="em_email"
                       value="<?php echo htmlspecialchars($v['em_email']); ?>"
                       placeholder="info@empresa.com">
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Estado</label>
                <select class="a-form-select" name="habilitada">
                    <option value="1" <?php echo $v['habilitada'] ? 'selected' : ''; ?>>Habilitada</option>
                    <option value="0" <?php echo !$v['habilitada'] ? 'selected' : ''; ?>>Deshabilitada</option>
                </select>
            </div>

        </div>

        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary a-btn--lg">
                <?php echo $es_edit ? 'Guardar cambios' : 'Crear empresa'; ?>
            </button>
            <a href="adm_empresas" class="a-btn a-btn--ghost">Cancelar</a>
        </div>
    </form>
</div>
