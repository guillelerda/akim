<?php
$lista       = $lista       ?? [];
$errores     = $errores     ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;

$fmt = fn(float $v): string => '$ ' . number_format($v, 2, ',', '.');
?>

<style>
.tc-wrap { max-width: 860px; }
.tc-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.tc-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

/* Tipo badges */
.badge-efectivo { background:rgba(22,163,74,.15);  color:#4ade80; }
.badge-banco    { background:rgba(37,99,235,.15);  color:#93c5fd; }
.badge-hab      { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-dis      { background:rgba(107,114,128,.12); color:#9ca3af; }
.saldo-pos      { color:#4ade80; font-weight:600; }
.saldo-neg      { color:#f87171; font-weight:600; }
.saldo-zer      { color:var(--text-dim); }

.btn-fav {
    background: none; border: none; cursor: pointer;
    font-size: .95rem; padding: .2rem .3rem; border-radius: 5px;
    transition: transform .15s, color .15s;
    line-height: 1;
}
.btn-fav:hover { transform: scale(1.25); }
.btn-fav.is-fav  { color: #fbbf24; }
.btn-fav.no-fav  { color: var(--text-dim); }
.btn-fav.no-fav:hover { color: #fbbf24; }

/* Banco fields */
#fields-banco { display:none; }

.tc-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.tc-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem; }
@media(max-width:600px){ .tc-grid, .tc-grid-3 { grid-template-columns:1fr; } }
</style>

<div class="tc-wrap">

    <div class="tc-header">
        <h1>Cuentas de tesorería</h1>
        <a href="tesoreria" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Tesorería
        </a>
    </div>

    <?php if ($flash_ok): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-circle-check"></i>
        <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
    <div class="alert alert-error" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <!-- Alta rápida -->
    <div class="card" style="margin-bottom:1.25rem">
        <div class="card-body">
            <p style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim);margin-bottom:.85rem">
                Nueva cuenta
            </p>

            <?php if (!empty($errores)): ?>
            <div class="alert alert-error" style="margin-bottom:.75rem">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($errores[0], ENT_QUOTES); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="tes_cuentas" id="form-nueva-cuenta">
                <input type="hidden" name="accion" value="nuevo">

                <!-- Tipo + Nombre + Moneda -->
                <div class="tc-grid" style="margin-bottom:.65rem">
                    <div>
                        <label class="form-label" style="font-size:.8rem">Tipo *</label>
                        <select name="tipo" id="sel-tipo" class="form-control" onchange="toggleBanco(this.value)">
                            <option value="efectivo">Caja efectivo</option>
                            <option value="banco">Cuenta banco</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Nombre *</label>
                        <input type="text" name="nombre" class="form-control"
                               placeholder="Ej: CAJA PRINCIPAL, GALICIA ARS" required>
                    </div>
                </div>

                <div class="tc-grid" style="margin-bottom:.65rem">
                    <div>
                        <label class="form-label" style="font-size:.8rem">Descripción</label>
                        <input type="text" name="descripcion" class="form-control"
                               placeholder="Descripción opcional">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Saldo inicial</label>
                        <input type="number" name="saldo_inicial" class="form-control"
                               value="0" step="0.01" min="0"
                               placeholder="0.00">
                    </div>
                </div>

                <!-- Campos solo banco -->
                <div id="fields-banco">
                    <div class="tc-grid-3" style="margin-bottom:.65rem">
                        <div>
                            <label class="form-label" style="font-size:.8rem">Banco</label>
                            <input type="text" name="banco" class="form-control"
                                   placeholder="Ej: Galicia, Santander">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.8rem">N° de cuenta</label>
                            <input type="text" name="numero_cuenta" class="form-control"
                                   placeholder="Número de cuenta">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.8rem">Moneda</label>
                            <select name="moneda" class="form-control">
                                <option value="ARS">ARS — Pesos</option>
                                <option value="USD">USD — Dólares</option>
                            </select>
                        </div>
                    </div>
                    <div class="tc-grid" style="margin-bottom:.65rem">
                        <div>
                            <label class="form-label" style="font-size:.8rem">CBU</label>
                            <input type="text" name="cbu" class="form-control"
                                   placeholder="22 dígitos" maxlength="22">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.8rem">Alias CBU</label>
                            <input type="text" name="alias_cbu" class="form-control"
                                   placeholder="alias.del.banco">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Agregar cuenta
                </button>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="prv-table-wrap">
        <?php if (empty($lista)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-wallet"></i>
            <p>No hay cuentas registradas.</p>
            <p style="font-size:.8rem">Cargá la caja o cuenta bancaria para comenzar.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:36px"></th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Banco / Info</th>
                        <th style="text-align:right">Saldo actual</th>
                        <th style="text-align:right">Disponible</th>
                        <th style="text-align:center;width:80px">Estado</th>
                        <th style="width:100px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $c):
                    $sAct  = (float)$c['saldo_actual'];
                    $sDisp = (float)$c['saldo_disponible'];
                    $clsA  = $sAct  < 0 ? 'saldo-neg' : ($sAct  == 0 ? 'saldo-zer' : 'saldo-pos');
                    $clsD  = $sDisp < 0 ? 'saldo-neg' : ($sDisp == 0 ? 'saldo-zer' : 'saldo-pos');
                ?>
                    <tr style="<?php echo !$c['habilitada'] ? 'opacity:.5' : ''; ?>">
                        <td style="text-align:center">
                            <form method="POST" action="tes_cuentas" style="margin:0">
                                <input type="hidden" name="accion" value="favorito">
                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                <button type="submit" class="btn-fav <?php echo $c['favorito'] ? 'is-fav' : 'no-fav'; ?>"
                                        title="<?php echo $c['favorito'] ? 'Favorita' : 'Marcar como favorita'; ?>">
                                    <i class="fa-<?php echo $c['favorito'] ? 'solid' : 'regular'; ?> fa-star"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $c['tipo']; ?>">
                                <?php echo $c['tipo'] === 'efectivo' ? 'Caja' : 'Banco'; ?>
                            </span>
                        </td>
                        <td style="font-weight:600;color:var(--text)">
                            <?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>
                        </td>
                        <td style="font-size:.82rem;color:var(--text-dim)">
                            <?php
                            if ($c['tipo'] === 'banco') {
                                $parts = array_filter([
                                    $c['banco'],
                                    $c['numero_cuenta'] ? 'Cta: ' . $c['numero_cuenta'] : '',
                                    $c['cbu'] ? 'CBU: ' . $c['cbu'] : '',
                                ]);
                                echo htmlspecialchars(implode(' · ', $parts) ?: '—', ENT_QUOTES);
                            } else {
                                echo htmlspecialchars($c['descripcion'] ?: '—', ENT_QUOTES);
                            }
                            ?>
                        </td>
                        <td style="text-align:right">
                            <span class="<?php echo $clsA; ?>"><?php echo $fmt($sAct); ?></span>
                        </td>
                        <td style="text-align:right">
                            <?php if ($c['tipo'] === 'banco'): ?>
                            <span class="<?php echo $clsD; ?>"><?php echo $fmt($sDisp); ?></span>
                            <?php else: ?>
                            <span style="color:var(--text-dim);font-size:.8rem">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center">
                            <span class="badge <?php echo $c['habilitada'] ? 'badge-hab' : 'badge-dis'; ?>">
                                <?php echo $c['habilitada'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:.3rem;justify-content:center">
                            <form method="POST" action="tes_cuentas">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                <button class="btn btn-secondary btn-sm"
                                        title="<?php echo $c['habilitada'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fa-solid fa-<?php echo $c['habilitada'] ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="tes_cuentas"
                                  onsubmit="return confirm('¿Eliminar esta cuenta? Solo es posible si no tiene movimientos.')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                <button class="btn btn-secondary btn-sm" title="Eliminar" style="color:#fca5a5">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleBanco(tipo) {
    document.getElementById('fields-banco').style.display = tipo === 'banco' ? '' : 'none';
}
</script>
