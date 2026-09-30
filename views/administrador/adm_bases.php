<?php
$lista        = $lista         ?? [];
$bd_crear_code = $bd_crear_code ?? '';
$flash_ok     = $_SESSION['flash_ok']  ?? ''; unset($_SESSION['flash_ok']);
$flash_err    = $_SESSION['flash_err'] ?? ''; unset($_SESSION['flash_err']);
$bd_sql       = $_SESSION['bd_sql_result'] ?? null; unset($_SESSION['bd_sql_result']);

// Buscar datos del registro a crear (para mostrar host/bd en el panel)
$bd_crear_row = null;
if ($bd_crear_code) {
    foreach ($lista as $r) {
        if ($r['bd_code'] === $bd_crear_code) {
            $bd_crear_row = $r; break;
        }
    }
}
?>

<?php if ($flash_ok):  ?><div class="a-flash a-flash--ok">✓ <?php echo htmlspecialchars($flash_ok); ?></div><?php endif; ?>
<?php if ($flash_err): ?><div class="a-flash a-flash--err">✗ <?php echo htmlspecialchars($flash_err); ?></div><?php endif; ?>

<?php if ($bd_crear_code && $bd_crear_row): ?>
<?php
$_host = \Model\Administrador\Model_bases::decrypt($bd_crear_row['bd_host'] ?? '');
$_name = \Model\Administrador\Model_bases::decrypt($bd_crear_row['bd_name'] ?? '');
$_user = \Model\Administrador\Model_bases::decrypt($bd_crear_row['bd_user'] ?? '');
?>
<div class="adm-card" style="margin-bottom:1rem;border-color:rgba(37,99,235,.4);background:rgba(37,99,235,.05)">
    <div class="adm-card-title">
        <i class="fa-solid fa-database" style="color:#93c5fd"></i>
        Crear BD en servidor —
        <code style="font-size:.75rem;color:#93c5fd"><?= htmlspecialchars($bd_crear_code) ?></code>
    </div>

    <div style="font-size:.83rem;color:var(--text-muted);margin-bottom:1rem;padding:.6rem .85rem;background:rgba(0,0,0,.2);border-radius:6px">
        <strong style="color:var(--text)">Host:</strong> <?= htmlspecialchars($_host) ?> &nbsp;·&nbsp;
        <strong style="color:var(--text)">BD:</strong> <code><?= htmlspecialchars($_name) ?></code> &nbsp;·&nbsp;
        <strong style="color:var(--text)">Usuario BD:</strong> <code><?= htmlspecialchars($_user) ?></code>
    </div>

    <p style="font-size:.82rem;color:#fbbf24;margin-bottom:1rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        El usuario de la BD no tiene permisos para crear bases de datos.
        Ingresá las credenciales de un usuario <strong>root/admin MySQL</strong> del servidor — no se guardan.
    </p>

    <form method="POST" action="adm_bases" autocomplete="off">
        <input type="hidden" name="exec_crear_bd"  value="1">
        <input type="hidden" name="bd_code_crear"  value="<?= htmlspecialchars($bd_crear_code) ?>">
        <div class="a-form-grid">
            <div class="a-form-group">
                <label class="a-form-label" for="mysql_admin_user">Usuario admin MySQL <span>*</span></label>
                <input type="text" id="mysql_admin_user" name="mysql_admin_user" class="a-form-input"
                    value="root" required autocomplete="off" maxlength="60">
            </div>
            <div class="a-form-group">
                <label class="a-form-label" for="mysql_admin_pass">Contraseña admin MySQL <span>*</span></label>
                <input type="password" id="mysql_admin_pass" name="mysql_admin_pass" class="a-form-input"
                    placeholder="Contraseña del root/admin" required autocomplete="new-password">
            </div>
        </div>
        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary">
                <i class="fa-solid fa-database"></i> Crear base de datos
            </button>
            <button type="button" class="a-btn a-btn--ghost"
                onclick="document.getElementById('form-cancelar-crear').submit()">
                Cancelar
            </button>
        </div>
    </form>
    <form id="form-cancelar-crear" method="POST" action="adm_bases" style="display:none">
        <input type="hidden" name="cancelar_crear" value="1">
    </form>
</div>
<?php endif; ?>

<?php if ($bd_sql): ?>
<div class="adm-card" style="margin-bottom:1rem;border-color:<?= $bd_sql['status'] === 'ok' ? 'rgba(16,185,129,.3)' : 'rgba(245,158,11,.3)' ?>">
    <div class="adm-card-title" style="display:flex;align-items:center;justify-content:space-between;gap:1rem">
        <span>
            <i class="fa-solid fa-<?= $bd_sql['status'] === 'ok' ? 'circle-check' : 'triangle-exclamation' ?>"
               style="color:<?= $bd_sql['status'] === 'ok' ? '#6ee7b7' : '#fbbf24' ?>"></i>
            SQL generado — <code style="font-size:.75rem;color:#93c5fd"><?= htmlspecialchars($bd_sql['code']) ?></code>
        </span>
        <button onclick="copiarSQL()" class="a-btn a-btn--ghost a-btn--sm">
            <i class="fa-solid fa-copy"></i> Copiar SQL
        </button>
    </div>
    <pre id="sql-generado" style="margin:0;padding:.85rem 1rem;background:rgba(0,0,0,.35);border-radius:8px;font-size:.78rem;color:#d1d5db;overflow-x:auto;white-space:pre-wrap;word-break:break-all"><?= htmlspecialchars($bd_sql['sql']) ?></pre>
    <p style="font-size:.75rem;color:var(--text-muted);margin-top:.6rem">
        <?php if ($bd_sql['status'] === 'ok'): ?>
            <i class="fa-solid fa-circle-check" style="color:#6ee7b7"></i>
            BD creada exitosamente. Si el <code>GRANT</code> falló (usuario sin permisos suficientes), ejecutá el SQL completo en phpMyAdmin con un usuario root.
        <?php else: ?>
            <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
            La conexión falló o el usuario no tiene permisos de <code>CREATE DATABASE</code>. Copiá el SQL y ejecutalo en phpMyAdmin / CLI con un usuario root.
        <?php endif; ?>
    </p>
</div>
<script>
function copiarSQL() {
    var txt = document.getElementById('sql-generado').innerText;
    navigator.clipboard.writeText(txt).then(function() {
        var btn = event.target.closest('button');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copiado';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
}
</script>
<?php endif; ?>

<div class="a-toolbar">
    <span class="a-toolbar-title">Bases de datos <small style="color:var(--text-muted);font-weight:400">(<?php echo count($lista); ?>)</small></span>
    <div class="a-toolbar-actions">
        <a href="adm_bases_edit?nuevo=1" class="a-btn a-btn--primary">+ Nueva base</a>
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
                        <!-- Test conexión -->
                        <form method="POST" action="adm_bases" style="display:inline">
                            <input type="hidden" name="test_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit" title="Probar conexión">
                                <i class="fa-solid fa-plug"></i> Test
                            </button>
                        </form>
                        <!-- Crear BD en servidor -->
                        <form method="POST" action="adm_bases" style="display:inline"
                              onsubmit="return confirm('¿Intentar crear la base de datos «<?= htmlspecialchars(addslashes($name)) ?>» en el servidor «<?= htmlspecialchars(addslashes($host)) ?>»?')">
                            <input type="hidden" name="create_bd_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--primary a-btn--sm" type="submit" title="Crear BD en el servidor MySQL">
                                <i class="fa-solid fa-database"></i> Crear BD
                            </button>
                        </form>
                        <!-- Editar -->
                        <form method="POST" action="adm_bases" style="display:inline">
                            <input type="hidden" name="edit_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--ghost a-btn--sm" type="submit">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>
                        </form>
                        <!-- Eliminar -->
                        <form method="POST" action="adm_bases" style="display:inline"
                              onsubmit="return confirm('¿Eliminar la base «<?php echo htmlspecialchars($row['bd_label']); ?>»? Verificá que no esté en uso.')">
                            <input type="hidden" name="delete_code" value="<?php echo htmlspecialchars($row['bd_code']); ?>">
                            <button class="a-btn a-btn--danger a-btn--sm" type="submit">
                                <i class="fa-solid fa-trash-alt"></i>
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

<div style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;padding:.75rem 1rem;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.12);border-radius:8px">
    🔒 Las credenciales se almacenan encriptadas (AES-128-CTR).
    El botón <strong>Crear BD</strong> intenta conectarse al host MySQL y ejecutar <code>CREATE DATABASE</code> — si el usuario no tiene permisos suficientes, se muestra el SQL para ejecutarlo manualmente.
</div>
