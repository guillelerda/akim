<?php
$lic_code    = $lic_code    ?? '';
$licencia    = $licencia    ?? null;
$asignaciones = $asignaciones ?? [];
$usuarios    = $usuarios    ?? [];
$todas_lic   = $todas_lic   ?? [];
$empresas    = $empresas    ?? [];
$flash_ok    = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err   = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
?>

<a href="adm_licencias" class="a-back">← Volver a licencias</a>

<?php if ($flash_ok):  ?><div class="a-flash a-flash--ok">✓ <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
<?php if ($flash_err): ?><div class="a-flash a-flash--err">✗ <?php echo htmlspecialchars($flash_err); ?></div><?php endif; ?>

<!-- Selector de licencia -->
<div class="adm-card" style="margin-bottom:1rem">
    <form method="POST" action="adm_links" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
        <div class="a-form-group" style="flex:1;min-width:200px">
            <label class="a-form-label">Seleccioná la licencia</label>
            <select class="a-form-select" name="cambiar_lic" onchange="this.form.submit()">
                <option value="">— Elegí una licencia —</option>
                <?php foreach ($todas_lic as $tl):
                    $sel = ($tl['lic_code'] === $lic_code) ? 'selected' : '';
                    $emp = \Model\Administrador\Model_licencias::decryptEmpCod($tl['lic_empresa_cod'] ?? '');
                ?>
                    <option value="<?php echo htmlspecialchars($tl['lic_code']); ?>" <?php echo $sel; ?>>
                        <?php echo htmlspecialchars($tl['lic_code'] . ' — ' . $emp); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (!$lic_code): ?>
    <div class="a-empty"><div class="a-empty-icon">👥</div><p>Seleccioná una licencia para ver y gestionar sus usuarios.</p></div>
<?php else: ?>

<div class="adm-card">
    <div class="adm-card-title">
        Usuarios asignados a
        <code style="font-size:.75rem;color:#93c5fd;font-weight:400"><?php echo htmlspecialchars($lic_code); ?></code>
    </div>

    <?php if (empty($asignaciones)): ?>
        <div class="a-empty"><div class="a-empty-icon">👤</div><p>No hay usuarios asignados a esta licencia.</p></div>
    <?php else: ?>
        <div class="a-table-wrap">
        <table class="a-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Empresa cod.</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($asignaciones as $a): ?>
                <tr>
                    <td style="font-weight:600;color:var(--text)"><?php echo htmlspecialchars($a['username']); ?></td>
                    <td><?php echo htmlspecialchars($a['em_code']); ?></td>
                    <td>
                        <?php if ($a['habilitada']): ?>
                            <span class="a-badge a-badge--on"><span class="a-dot"></span>Habilitado</span>
                        <?php else: ?>
                            <span class="a-badge a-badge--off"><span class="a-dot"></span>Deshabilitado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="td-actions">
                            <form method="POST" action="adm_links" style="display:inline">
                                <input type="hidden" name="toggle_id" value="<?php echo (int)$a['id']; ?>">
                                <button class="a-btn a-btn--amber a-btn--sm" type="submit">
                                    <?php echo $a['habilitada'] ? 'Deshab.' : 'Habilitar'; ?>
                                </button>
                            </form>
                            <form method="POST" action="adm_links" style="display:inline"
                                  onsubmit="return confirm('¿Quitar a <?php echo htmlspecialchars($a['username']); ?> de esta licencia?')">
                                <input type="hidden" name="delete_id" value="<?php echo (int)$a['id']; ?>">
                                <button class="a-btn a-btn--danger a-btn--sm" type="submit">Quitar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<!-- Agregar usuario -->
<div class="adm-card">
    <div class="adm-card-title">Agregar usuario a esta licencia</div>
    <form method="POST" action="adm_links">
        <div class="a-form-grid">
            <div class="a-form-group">
                <label class="a-form-label">Usuario <span>*</span></label>
                <select class="a-form-select" name="asignar_username" required>
                    <option value="">— Seleccioná usuario —</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo htmlspecialchars($u); ?>"><?php echo htmlspecialchars($u); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="a-form-group">
                <label class="a-form-label">Empresa (em_code) <span>*</span></label>
                <select class="a-form-select" name="asignar_em_code" required>
                    <option value="">— Seleccioná empresa —</option>
                    <?php foreach ($empresas as $em):
                        $nom = \Model\Administrador\Model_empresas::decryptNombre($em['em_nombre'] ?? '');
                    ?>
                        <option value="<?php echo htmlspecialchars($em['em_code']); ?>">
                            <?php echo htmlspecialchars($nom . ' (' . $em['em_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary">Asignar usuario</button>
        </div>
    </form>
</div>

<?php endif; ?>
