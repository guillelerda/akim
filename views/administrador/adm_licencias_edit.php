<?php
$registro           = $registro           ?? null;
$errores            = $errores            ?? [];
$empresas           = $empresas           ?? [];
$bases              = $bases              ?? [];
$modulos_disponibles = $modulos_disponibles ?? [];
$es_edit            = !empty($registro);

$mods_activos = [];
if ($es_edit && !empty($registro['lic_modulos'])) {
    $mods_activos = json_decode($registro['lic_modulos'], true) ?? [];
}

$emp_cod_actual = $es_edit
    ? (\Model\Administrador\Model_licencias::decryptEmpCod($registro['lic_empresa_cod'] ?? ''))
    : '';

$v = [
    'lic_code'       => $registro['lic_code']    ?? '',
    'lic_empresa_cod'=> $emp_cod_actual,
    'bd_code'        => $registro['bd_code']     ?? '',
    'habilitada'     => $registro['habilitada']  ?? 1,
    'vencimiento'    => $registro['vencimiento'] ?? '',
];
?>

<a href="adm_licencias" class="a-back">← Volver a licencias</a>

<?php if ($errores): ?>
<div class="a-flash a-flash--err">
    <?php foreach ($errores as $e): ?><div>✗ <?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="adm-card">
    <div class="adm-card-title">
        <?php echo $es_edit ? 'Editar licencia' : 'Nueva licencia'; ?>
        <?php if ($es_edit): ?>
            <code style="font-size:.75rem;color:#93c5fd;font-weight:400"><?php echo htmlspecialchars($v['lic_code']); ?></code>
        <?php endif; ?>
    </div>

    <form method="POST" action="adm_licencias_edit">
        <div class="a-form-grid">

            <div class="a-form-group">
                <label class="a-form-label">Código de licencia <span>*</span></label>
                <input class="a-form-input" type="text" name="lic_code"
                       value="<?php echo htmlspecialchars($v['lic_code']); ?>"
                       placeholder="LIC-ABC12345"
                       <?php echo $es_edit ? 'readonly style="opacity:.5"' : ''; ?> required>
                <span class="a-form-hint">Identificador único de la licencia.</span>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Empresa <span>*</span></label>
                <select class="a-form-select" name="lic_empresa_cod" required>
                    <option value="">— Seleccioná empresa —</option>
                    <?php foreach ($empresas as $em):
                        $nom = \Model\Administrador\Model_empresas::decryptNombre($em['em_nombre'] ?? '');
                        $sel = ($em['em_code'] === $v['lic_empresa_cod']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($em['em_code']); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($nom . ' (' . $em['em_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Base de datos</label>
                <select class="a-form-select" name="bd_code">
                    <option value="">— Sin base asignada —</option>
                    <?php foreach ($bases as $b):
                        $sel = ($b['bd_code'] === $v['bd_code']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($b['bd_code']); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($b['bd_label'] . ' (' . $b['bd_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="a-form-hint">Debe existir en el catálogo de bases de datos.</span>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Vencimiento</label>
                <input class="a-form-input" type="date" name="vencimiento"
                       value="<?php echo htmlspecialchars($v['vencimiento']); ?>">
                <span class="a-form-hint">Dejá vacío para sin vencimiento.</span>
            </div>

            <div class="a-form-group">
                <label class="a-form-label">Estado</label>
                <select class="a-form-select" name="habilitada">
                    <option value="1" <?php echo $v['habilitada'] ? 'selected' : ''; ?>>Habilitada</option>
                    <option value="0" <?php echo !$v['habilitada'] ? 'selected' : ''; ?>>Deshabilitada</option>
                </select>
            </div>

        </div>

        <!-- ── Módulos ─────────────────────────────────────────────────────── -->
        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border)">
            <div style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.85rem">
                Módulos habilitados
            </div>
            <div class="a-mod-grid">
                <?php foreach ($modulos_disponibles as $cod => $mod):
                    $checked = in_array($cod, $mods_activos) ? 'checked' : '';
                ?>
                <div class="a-mod-item">
                    <input type="checkbox" id="mod_<?php echo $cod; ?>"
                           name="mod_<?php echo $cod; ?>" value="1" <?php echo $checked; ?>>
                    <label class="a-mod-label" for="mod_<?php echo $cod; ?>">
                        <span class="a-mod-icon"><?php echo $mod['icono']; ?></span>
                        <span class="a-mod-name"><?php echo htmlspecialchars($mod['nombre']); ?></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary a-btn--lg">
                <?php echo $es_edit ? 'Guardar cambios' : 'Crear licencia'; ?>
            </button>
            <a href="adm_licencias" class="a-btn a-btn--ghost">Cancelar</a>
        </div>
    </form>
</div>
