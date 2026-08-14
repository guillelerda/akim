<?php
$lista     = $lista     ?? [];
$flash_ok  = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
?>

<?php if ($flash_ok):  ?><div class="a-flash a-flash--ok">✓ <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
<?php if ($flash_err): ?><div class="a-flash a-flash--err">✗ <?php echo htmlspecialchars($flash_err); ?></div><?php endif; ?>

<div class="a-toolbar">
    <span class="a-toolbar-title">Bases de datos <small style="color:var(--text-muted);font-weight:400">(<?php echo count($lista); ?>)</small></span>
    <div class="a-toolbar-actions">
        <a href="adm_bases_edit" class="a-btn a-btn--primary">+ Nueva base</a>
    </div>
</div>

<div class="adm-card">
<?php if (empty($lista)): ?>
    <div class="a-empty"><div class="a-empty-icon">🗄️</div><p>No hay bases de datos registradas.</p></div>
<?php else: ?>
    <div class="a-table-wrap">
    <table class="a-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Etiqueta</th>
                <th>Host</th>
                <th>Base (BD)</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $row):
            $host = \Model\Administrador\Model_bases::decrypt($row['bd_host'] ?? '');
            $name = \Model\Administrador\Model_bases::decrypt($row['bd_name'] ?? '');
            $user = \Model\Administrador\Model_bases::decrypt($row['bd_user'] ?? '');
        ?>
            <tr>
                <td><code style="font-size:.75rem;color:#93c5fd"><?php echo htmlspecialchars($row['bd_code']); ?></code></td>
                <td style="font-weight:600;color:var(--text)"><?php echo htmlspecialchars($row['bd_label']); ?></td>
                <td><code style="font-size:.75rem"><?php echo htmlspecialchars($host); ?></code></td>
                <td><code style="font-size:.75rem"><?php echo htmlspecialchars($name); ?></code></td>
                <td><?php echo htmlspecialchars($user); ?></td>
                <td><span style="color:var(--text-muted);font-size:.8rem">••••••••</span></td>
                <td>
                    <div class="td-actions">
                        <form method="POST" action="adm_bases" style="display:inline">
                            <input type="hidden" name="test_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit"
                                    title="Probar conexión">⚡ Test</button>
                        </form>
                        <form method="POST" action="adm_bases" style="display:inline">
                            <input type="hidden" name="edit_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit">Editar</button>
                        </form>
                        <form method="POST" action="adm_bases" style="display:inline"
                              onsubmit="return confirm('¿Eliminar la base «<?php echo htmlspecialchars($row['bd_label']); ?>»? Verificá que no esté en uso.')">
                            <input type="hidden" name="delete_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--danger a-btn--sm" type="submit">Eliminar</button>
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

<div style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;padding:.75rem 1rem;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.12);border-radius:8px">
    🔒 Las credenciales se almacenan encriptadas (AES-128-CTR). Solo se muestran desencriptadas en el formulario de edición.
</div>
