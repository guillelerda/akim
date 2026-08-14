<?php
$lista  = $lista  ?? [];
$flash_ok  = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
?>

<?php if ($flash_ok):  ?><div class="a-flash a-flash--ok">✓ <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
<?php if ($flash_err): ?><div class="a-flash a-flash--err">✗ <?php echo htmlspecialchars($flash_err); ?></div><?php endif; ?>

<div class="a-toolbar">
    <span class="a-toolbar-title">Empresas <small style="color:var(--text-muted);font-weight:400">(<?php echo count($lista); ?>)</small></span>
    <div class="a-toolbar-actions">
        <a href="adm_empresas_edit" class="a-btn a-btn--primary">+ Nueva empresa</a>
    </div>
</div>

<div class="adm-card">
<?php if (empty($lista)): ?>
    <div class="a-empty"><div class="a-empty-icon">🏢</div><p>No hay empresas registradas aún.</p></div>
<?php else: ?>
    <div class="a-table-wrap">
    <table class="a-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>CUIT</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $row):
            $nombre_dec = \Model\Administrador\Model_empresas::decryptNombre($row['em_nombre'] ?? '');
        ?>
            <tr>
                <td><code style="font-size:.75rem;color:#93c5fd"><?php echo htmlspecialchars($row['em_code']); ?></code></td>
                <td style="font-weight:600;color:var(--text)"><?php echo htmlspecialchars($nombre_dec); ?></td>
                <td><?php echo htmlspecialchars($row['em_cuit'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($row['em_email'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($row['em_telefono'] ?? '—'); ?></td>
                <td>
                    <?php if ($row['habilitada']): ?>
                        <span class="a-badge a-badge--on"><span class="a-dot"></span>Activa</span>
                    <?php else: ?>
                        <span class="a-badge a-badge--off"><span class="a-dot"></span>Inactiva</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="td-actions">
                        <form method="POST" action="adm_empresas" style="display:inline">
                            <input type="hidden" name="edit_code" value="<?php echo htmlspecialchars($row['em_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit">Editar</button>
                        </form>
                        <form method="POST" action="adm_empresas" style="display:inline">
                            <input type="hidden" name="toggle_code" value="<?php echo htmlspecialchars($row['em_code']); ?>">
                            <button class="a-btn a-btn--amber a-btn--sm" type="submit">
                                <?php echo $row['habilitada'] ? 'Deshabilitar' : 'Habilitar'; ?>
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
