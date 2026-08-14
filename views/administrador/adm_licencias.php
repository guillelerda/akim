<?php
$lista     = $lista     ?? [];
$flash_ok  = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
?>

<?php if ($flash_ok):  ?><div class="a-flash a-flash--ok">✓ <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
<?php if ($flash_err): ?><div class="a-flash a-flash--err">✗ <?php echo htmlspecialchars($flash_err); ?></div><?php endif; ?>

<div class="a-toolbar">
    <span class="a-toolbar-title">Licencias <small style="color:var(--text-muted);font-weight:400">(<?php echo count($lista); ?>)</small></span>
    <div class="a-toolbar-actions">
        <a href="adm_licencias_edit" class="a-btn a-btn--primary">+ Nueva licencia</a>
    </div>
</div>

<div class="adm-card">
<?php if (empty($lista)): ?>
    <div class="a-empty"><div class="a-empty-icon">🔑</div><p>No hay licencias registradas aún.</p></div>
<?php else: ?>
    <div class="a-table-wrap">
    <table class="a-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Empresa cod.</th>
                <th>Base datos</th>
                <th>Módulos</th>
                <th>Vencimiento</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $row):
            $emp_cod = \Model\Administrador\Model_licencias::decryptEmpCod($row['lic_empresa_cod'] ?? '');
            $mods    = $row['lic_modulos'] ? json_decode($row['lic_modulos'], true) : [];
            $em_nombre = isset($row['em_nombre'])
                ? \Model\Administrador\Model_empresas::decryptNombre($row['em_nombre'])
                : $emp_cod;
            $venc = $row['vencimiento'] ?? null;
            $venc_str = $venc ? date('d/m/Y', strtotime($venc)) : '—';
            $vencido  = $venc && strtotime($venc) < time();
        ?>
            <tr>
                <td><code style="font-size:.75rem;color:#93c5fd"><?php echo htmlspecialchars($row['lic_code']); ?></code></td>
                <td>
                    <div style="font-size:.82rem;color:var(--text)"><?php echo htmlspecialchars($em_nombre); ?></div>
                    <div style="font-size:.7rem;color:var(--text-muted)"><?php echo htmlspecialchars($emp_cod); ?></div>
                </td>
                <td><?php echo htmlspecialchars($row['bd_label'] ?? '—'); ?></td>
                <td>
                    <?php if (empty($mods)): ?>
                        <span style="color:var(--text-muted);font-size:.75rem">Sin módulos</span>
                    <?php else: ?>
                        <?php foreach ($mods as $m): ?>
                            <span class="a-badge a-badge--warn" style="margin:.1rem"><?php echo htmlspecialchars($m); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($vencido): ?>
                        <span class="a-badge a-badge--off"><?php echo $venc_str; ?> (vencida)</span>
                    <?php else: ?>
                        <?php echo $venc_str; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($row['habilitada']): ?>
                        <span class="a-badge a-badge--on"><span class="a-dot"></span>Activa</span>
                    <?php else: ?>
                        <span class="a-badge a-badge--off"><span class="a-dot"></span>Inactiva</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="td-actions">
                        <form method="POST" action="adm_licencias" style="display:inline">
                            <input type="hidden" name="edit_code" value="<?php echo htmlspecialchars($row['lic_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit">Editar</button>
                        </form>
                        <form method="POST" action="adm_licencias" style="display:inline">
                            <input type="hidden" name="links_code" value="<?php echo htmlspecialchars($row['lic_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit">Usuarios</button>
                        </form>
                        <form method="POST" action="adm_licencias" style="display:inline">
                            <input type="hidden" name="toggle_code" value="<?php echo htmlspecialchars($row['lic_code']); ?>">
                            <button class="a-btn a-btn--amber a-btn--sm" type="submit">
                                <?php echo $row['habilitada'] ? 'Deshab.' : 'Habilitar'; ?>
                            </button>
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
