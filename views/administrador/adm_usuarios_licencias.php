<?php
$usuario     = $usuario      ?? [];
$asignaciones = $asignaciones ?? [];
$disponibles  = $disponibles  ?? [];
$uname        = $usuario['username'] ?? '';
?>

<a href="adm_usuarios" class="a-back"><i class="fa-solid fa-arrow-left"></i> Volver a usuarios</a>

<?php if ($flash_ok):    ?><div class="a-flash a-flash--ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash_ok)    ?></div><?php endif; ?>
<?php if ($flash_error): ?><div class="a-flash a-flash--err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

<!-- Encabezado usuario -->
<div class="adm-card" style="margin-bottom:1rem;display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap">
    <div style="width:2.5rem;height:2.5rem;border-radius:50%;background:#1e40af;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#93c5fd;flex-shrink:0">
        <i class="fa-solid fa-user"></i>
    </div>
    <div>
        <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($usuario['nombre_dec'] ?? $uname) ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:.1rem">
            <span style="font-family:monospace"><?= htmlspecialchars($uname) ?></span>
            &nbsp;·&nbsp;
            <span class="a-badge <?= ($usuario['perfil_dec'] ?? '') === PERFIL_ADMIN_AKIM ? 'a-badge--warn' : 'a-badge--on' ?>">
                <?= htmlspecialchars($usuario['perfil_dec'] ?? '—') ?>
            </span>
        </div>
    </div>
    <div style="margin-left:auto">
        <a href="adm_usuarios_edit" class="a-btn a-btn--ghost a-btn--sm">
            <i class="fa-solid fa-pen-to-square"></i> Editar usuario
        </a>
    </div>
</div>

<!-- Licencias asignadas -->
<div class="adm-card" style="margin-bottom:1rem">
    <div class="adm-card-title">
        <i class="fa-solid fa-key"></i> Licencias asignadas
        <span style="font-size:.75rem;font-weight:400;color:var(--text-muted);margin-left:.5rem">(<?= count($asignaciones) ?>)</span>
    </div>

    <?php if (empty($asignaciones)): ?>
        <div class="a-empty">
            <i class="fa-solid fa-key" style="font-size:1.5rem;margin-bottom:.5rem;display:block;opacity:.3"></i>
            <p>Este usuario no tiene licencias asignadas.</p>
        </div>
    <?php else: ?>
        <div class="a-table-wrap">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Licencia</th>
                        <th>Empresa (em_code)</th>
                        <th>Estado licencia</th>
                        <th>Vencimiento</th>
                        <th>Acceso</th>
                        <th style="text-align:center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($asignaciones as $a):
                    $esActivo  = (int)($a['habilitada']     ?? 0) === 1;
                    $licActiva = (int)($a['lic_habilitada'] ?? 0) === 1;
                    $venc      = $a['vencimiento'] ?? null;
                    $vencStr   = $venc ? date('d/m/Y', strtotime($venc)) : '—';
                    $vencida   = $venc && strtotime($venc) < strtotime('today');
                ?>
                <tr>
                    <td>
                        <span style="font-family:monospace;font-size:.88rem;font-weight:600;color:#93c5fd">
                            <?= htmlspecialchars($a['licencia']) ?>
                        </span>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($a['em_code']) ?></div>
                        <?php if (!empty($a['em_nombre'])): ?>
                            <div style="font-size:.78rem;color:var(--text-muted)">
                                <?= htmlspecialchars(\Model\Administrador\Model_empresas::decryptNombre($a['em_nombre'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="a-badge <?= $licActiva ? 'a-badge--on' : 'a-badge--off' ?>">
                            <span class="a-dot"></span>
                            <?= $licActiva ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>
                    <td style="font-size:.82rem;<?= $vencida ? 'color:#f87171' : 'color:var(--text-muted)' ?>">
                        <?= $vencStr ?>
                        <?php if ($vencida): ?> <i class="fa-solid fa-triangle-exclamation" title="Vencida"></i><?php endif; ?>
                    </td>
                    <td>
                        <span class="a-badge <?= $esActivo ? 'a-badge--on' : 'a-badge--off' ?>">
                            <?= $esActivo ? 'Habilitado' : 'Deshabilitado' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:.35rem;justify-content:center">
                            <!-- Toggle acceso -->
                            <form method="POST" action="adm_usuarios_licencias">
                                <input type="hidden" name="accion"  value="toggle">
                                <input type="hidden" name="link_id" value="<?= (int)$a['id'] ?>">
                                <button type="submit"
                                    class="a-btn a-btn--sm <?= $esActivo ? 'a-btn--amber' : 'a-btn--ghost' ?>"
                                    title="<?= $esActivo ? 'Deshabilitar acceso' : 'Habilitar acceso' ?>">
                                    <i class="fa-solid <?= $esActivo ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                </button>
                            </form>
                            <!-- Quitar licencia -->
                            <form method="POST" action="adm_usuarios_licencias"
                                  onsubmit="return confirm('¿Quitar la licencia «<?= htmlspecialchars(addslashes($a['licencia'])) ?>» a este usuario?')">
                                <input type="hidden" name="accion"  value="quitar">
                                <input type="hidden" name="link_id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="a-btn a-btn--danger a-btn--sm" title="Quitar licencia">
                                    <i class="fa-solid fa-unlink"></i>
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

<!-- Asignar nueva licencia -->
<?php if (!empty($disponibles)): ?>
<div class="adm-card">
    <div class="adm-card-title">
        <i class="fa-solid fa-plus-circle"></i> Asignar licencia
    </div>
    <form method="POST" action="adm_usuarios_licencias" id="form-asignar">
        <input type="hidden" name="accion" value="asignar">
        <div class="a-form-grid">
            <div class="a-form-group">
                <label class="a-form-label" for="sel_lic">Licencia <span>*</span></label>
                <select id="sel_lic" name="lic_code" class="a-form-select" required onchange="actualizarEmCode(this)">
                    <option value="">— Seleccioná una licencia —</option>
                    <?php foreach ($disponibles as $d):
                        $empNombre = !empty($d['em_nombre'])
                            ? \Model\Administrador\Model_empresas::decryptNombre($d['em_nombre'])
                            : \Model\Administrador\Model_licencias::decryptEmpCod($d['lic_empresa_cod'] ?? '');
                        $emCode    = $d['em_code_empresa'] ?? '';
                    ?>
                        <option value="<?= htmlspecialchars($d['lic_code']) ?>"
                                data-em="<?= htmlspecialchars($emCode) ?>">
                            <?= htmlspecialchars($d['lic_code'] . ($empNombre ? ' — ' . $empNombre : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="a-form-group">
                <label class="a-form-label" for="inp_em_code">Empresa (em_code) <span>*</span></label>
                <input type="text" id="inp_em_code" name="em_code" class="a-form-input"
                    placeholder="Se completa automáticamente" required maxlength="20">
                <span class="a-form-hint">Se completa al elegir la licencia. Editá si es necesario.</span>
            </div>
        </div>
        <div class="a-form-actions">
            <button type="submit" class="a-btn a-btn--primary">
                <i class="fa-solid fa-link"></i> Asignar licencia
            </button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="adm-card">
    <div class="a-empty" style="padding:1.5rem">
        <i class="fa-solid fa-circle-check" style="font-size:1.3rem;color:#6ee7b7;display:block;margin-bottom:.5rem"></i>
        <p>Todas las licencias activas ya están asignadas a este usuario.</p>
    </div>
</div>
<?php endif; ?>

<script>
function actualizarEmCode(sel) {
    var op = sel.options[sel.selectedIndex];
    var em = op.getAttribute('data-em') || '';
    document.getElementById('inp_em_code').value = em;
}
</script>
