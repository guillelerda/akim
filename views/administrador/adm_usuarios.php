<?php if ($flash_ok):    ?><div class="a-flash a-flash--ok"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash_ok)    ?></div><?php endif; ?>
<?php if ($flash_error): ?><div class="a-flash a-flash--err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

<div class="adm-card">
    <div class="adm-card-title">
        <i class="fa-solid fa-users-gear"></i> Usuarios del sistema
    </div>

    <div class="a-toolbar">
        <form method="POST" action="adm_usuarios">
            <input type="hidden" name="accion" value="nuevo">
            <button type="submit" class="a-btn a-btn--primary">
                <i class="fa-solid fa-user-plus"></i> Nuevo usuario
            </button>
        </form>
        <span style="color:var(--text-muted);font-size:.85rem">
            <?= count($lista) ?> usuario<?= count($lista) !== 1 ? 's' : '' ?> registrado<?= count($lista) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <div class="a-table-wrap">
        <table class="a-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Estado</th>
                    <th>Última sesión</th>
                    <th style="text-align:center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lista)): ?>
                <tr>
                    <td colspan="7" class="a-empty">
                        <i class="fa-solid fa-users" style="font-size:1.5rem;margin-bottom:.5rem;display:block"></i>
                        No hay usuarios registrados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($lista as $u):
                    $esActivo = (int)($u['confirmado'] ?? 0) === 1;
                    $esSelf   = ($u['username'] === $username);
                    $lastSeen = $u['last_seen'] ?? null;
                    $lastStr  = $lastSeen ? date('d/m/Y H:i', strtotime($lastSeen)) : '—';
                ?>
                <tr>
                    <td>
                        <span style="font-family:monospace;font-size:.88rem"><?= htmlspecialchars($u['username']) ?></span>
                        <?php if ($esSelf): ?>
                            <span class="a-badge a-badge--warn" style="margin-left:.35rem;vertical-align:middle">yo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($u['nombre_dec'] ?: '—') ?></td>
                    <td style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars($u['email_dec'] ?: '—') ?></td>
                    <td>
                        <?php $p = $u['perfil_dec'] ?: '—'; ?>
                        <span class="a-badge <?= $p === PERFIL_ADMIN_AKIM ? 'a-badge--warn' : 'a-badge--on' ?>">
                            <?= htmlspecialchars($p) ?>
                        </span>
                    </td>
                    <td>
                        <span class="a-badge <?= $esActivo ? 'a-badge--on' : 'a-badge--off' ?>">
                            <span class="a-dot"></span>
                            <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted)"><?= $lastStr ?></td>
                    <td>
                        <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:nowrap">

                            <!-- Licencias -->
                            <form method="POST" action="adm_usuarios">
                                <input type="hidden" name="accion" value="licencias">
                                <input type="hidden" name="uname"  value="<?= htmlspecialchars($u['username']) ?>">
                                <button type="submit" class="a-btn a-btn--ghost a-btn--sm" title="Gestionar licencias">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                            </form>

                            <!-- Editar -->
                            <form method="POST" action="adm_usuarios">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="uname"  value="<?= htmlspecialchars($u['username']) ?>">
                                <button type="submit" class="a-btn a-btn--ghost a-btn--sm" title="Editar usuario">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </form>

                            <?php if (!$esSelf): ?>
                            <!-- Toggle activo/inactivo -->
                            <form method="POST" action="adm_usuarios">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="uname"  value="<?= htmlspecialchars($u['username']) ?>">
                                <button type="submit"
                                    class="a-btn a-btn--sm <?= $esActivo ? 'a-btn--amber' : 'a-btn--ghost' ?>"
                                    title="<?= $esActivo ? 'Desactivar usuario' : 'Activar usuario' ?>">
                                    <i class="fa-solid <?= $esActivo ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                </button>
                            </form>

                            <!-- Eliminar -->
                            <form method="POST" action="adm_usuarios"
                                  onsubmit="return confirm('¿Eliminar al usuario «<?= htmlspecialchars(addslashes($u['username'])) ?>»?\nEsta acción no se puede deshacer.')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="uname"  value="<?= htmlspecialchars($u['username']) ?>">
                                <button type="submit" class="a-btn a-btn--danger a-btn--sm" title="Eliminar usuario">
                                    <i class="fa-solid fa-trash-alt"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
