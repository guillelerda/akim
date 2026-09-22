<?php
$lista         = $lista         ?? [];
$cuentas_banco = $cuentas_banco ?? [];
$errores       = $errores       ?? [];
$flash_ok      = $flash_ok      ?? null;
$flash_error   = $flash_error   ?? null;
?>

<style>
.tt-wrap { max-width: 900px; }
.tt-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.tt-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }

.badge-cred    { background:rgba(139,92,246,.15); color:#c4b5fd; }
.badge-deb     { background:rgba(6,182,212,.15);  color:#67e8f9; }
.badge-prep    { background:rgba(245,158,11,.15); color:#fcd34d; }
.badge-hab-t   { background:rgba(22,163,74,.15);  color:#86efac; }
.badge-dis-t   { background:rgba(107,114,128,.12); color:#9ca3af; }

.tt-grid   { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem; }
.tt-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
@media(max-width:700px){ .tt-grid { grid-template-columns:1fr 1fr; } }
@media(max-width:480px){ .tt-grid, .tt-grid-2 { grid-template-columns:1fr; } }

.tt-red-badge { display:inline-block; padding:.15rem .45rem; border-radius:4px; font-size:.72rem; font-weight:700; }
.tt-red-Visa        { background:rgba(37,99,235,.2);   color:#93c5fd; }
.tt-red-Mastercard  { background:rgba(239,68,68,.2);   color:#fca5a5; }
.tt-red-Amex        { background:rgba(22,163,74,.2);   color:#4ade80; }
.tt-red-Naranja     { background:rgba(245,158,11,.2);  color:#fcd34d; }
.tt-red-Cabal       { background:rgba(139,92,246,.2);  color:#c4b5fd; }
.tt-red-default     { background:rgba(107,114,128,.15);color:#9ca3af; }

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

/* Modal edición */
.tt-modal-overlay {
    display: none;
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(3,7,18,.75);
    backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
}
.tt-modal-overlay.open { display: flex; }
.tt-modal {
    background: var(--surface);
    border: 1px solid var(--border-med);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    width: 100%; max-width: 560px;
    box-shadow: 0 24px 64px rgba(0,0,0,.5);
}
.tt-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.25rem;
}
.tt-modal-title { font-size:1rem; font-weight:700; color:var(--text); }
.tt-modal-close {
    background: none; border: none; color: var(--text-dim);
    cursor: pointer; font-size: 1.1rem; padding: .2rem .4rem;
    border-radius: 6px; transition: color .12s;
}
.tt-modal-close:hover { color: var(--text); }
</style>

<div class="tt-wrap">

    <div class="tt-header">
        <h1>Terminales de tarjeta</h1>
        <a href="tesoreria" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Tesorería
        </a>
    </div>

    <div class="card" style="margin-bottom:.5rem">
        <div class="card-body" style="padding:.75rem 1rem;border-radius:var(--radius)">
            <p style="font-size:.8rem;color:var(--text-dim);margin:0">
                <i class="fa-solid fa-circle-info" style="color:#60a5fa;margin-right:.35rem"></i>
                Cada terminal representa una red de cobro (Visa Crédito, MC Débito, etc.).
                Configurá el <strong>plazo de acreditación</strong> (días hasta que el banco recibe el dinero)
                y el <strong>descuento</strong> que aplica la procesadora.
            </p>
        </div>
    </div>

    <?php if ($flash_ok): ?>
    <div class="alert alert-success" style="margin:1rem 0">
        <i class="fa-solid fa-circle-check"></i>
        <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
    <div class="alert alert-error" style="margin:1rem 0">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <!-- Alta rápida -->
    <div class="card" style="margin-bottom:1.25rem">
        <div class="card-body">
            <p style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim);margin-bottom:.85rem">
                Nueva terminal
            </p>

            <?php if (!empty($errores)): ?>
            <div class="alert alert-error" style="margin-bottom:.75rem">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($errores[0], ENT_QUOTES); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="tes_tarjetas">
                <input type="hidden" name="accion" value="nuevo">

                <div class="tt-grid" style="margin-bottom:.65rem">
                    <div>
                        <label class="form-label" style="font-size:.8rem">Nombre *</label>
                        <input type="text" name="nombre" class="form-control"
                               placeholder="Ej: Visa Crédito" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Red</label>
                        <select name="red" class="form-control">
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="Amex">American Express</option>
                            <option value="Naranja">Naranja</option>
                            <option value="Cabal">Cabal</option>
                            <option value="">Otra</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Tipo</label>
                        <select name="tipo_tarjeta" class="form-control">
                            <option value="credito">Crédito</option>
                            <option value="debito">Débito</option>
                            <option value="prepago">Prepago / QR</option>
                        </select>
                    </div>
                </div>

                <div class="tt-grid" style="margin-bottom:.65rem">
                    <div>
                        <label class="form-label" style="font-size:.8rem">Plazo acreditación (días)</label>
                        <input type="number" name="plazo_dias" class="form-control"
                               value="2" min="0" max="90" step="1">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Descuento procesadora (%)</label>
                        <input type="number" name="descuento_pct" class="form-control"
                               value="0" min="0" max="30" step="0.01"
                               placeholder="Ej: 2.50">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem">Acredita en cuenta</label>
                        <select name="cuenta_id" class="form-control">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($cuentas_banco as $cb): ?>
                            <option value="<?php echo (int)$cb['id']; ?>">
                                <?php echo htmlspecialchars($cb['nombre'], ENT_QUOTES); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($cuentas_banco)): ?>
                        <p style="font-size:.75rem;color:#fcd34d;margin-top:.3rem">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Primero creá una cuenta bancaria.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Agregar terminal
                </button>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="prv-table-wrap">
        <?php if (empty($lista)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-credit-card"></i>
            <p>No hay terminales configuradas.</p>
            <p style="font-size:.8rem">Agregá Visa, Mastercard u otras redes para registrar cobros con tarjeta.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:36px"></th>
                        <th>Red / Nombre</th>
                        <th style="text-align:center">Tipo</th>
                        <th style="text-align:center">Plazo</th>
                        <th style="text-align:center">Descuento</th>
                        <th>Acredita en</th>
                        <th style="text-align:center;width:80px">Estado</th>
                        <th style="width:100px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $t): ?>
                    <tr style="<?php echo !$t['habilitada'] ? 'opacity:.5' : ''; ?>">
                        <td style="text-align:center">
                            <form method="POST" action="tes_tarjetas" style="margin:0">
                                <input type="hidden" name="accion" value="favorito">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                <button type="submit" class="btn-fav <?php echo $t['favorito'] ? 'is-fav' : 'no-fav'; ?>"
                                        title="<?php echo $t['favorito'] ? 'Favorita' : 'Marcar como favorita'; ?>">
                                    <i class="fa-<?php echo $t['favorito'] ? 'solid' : 'regular'; ?> fa-star"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <?php if ($t['red']): ?>
                            <span class="tt-red-badge tt-red-<?php echo htmlspecialchars($t['red'], ENT_QUOTES); ?> tt-red-default">
                                <?php echo htmlspecialchars($t['red'], ENT_QUOTES); ?>
                            </span>
                            <?php endif; ?>
                            <span style="font-weight:600;color:var(--text);margin-left:.35rem">
                                <?php echo htmlspecialchars($t['nombre'], ENT_QUOTES); ?>
                            </span>
                        </td>
                        <td style="text-align:center">
                            <?php
                            $tipoCls  = ['credito'=>'badge-cred','debito'=>'badge-deb','prepago'=>'badge-prep'];
                            $tipoLabel= ['credito'=>'Crédito','debito'=>'Débito','prepago'=>'Prepago'];
                            $tipo_t   = $t['tipo_tarjeta'] ?? 'credito';
                            ?>
                            <span class="badge <?php echo $tipoCls[$tipo_t] ?? 'badge-dis-t'; ?>">
                                <?php echo $tipoLabel[$tipo_t] ?? $tipo_t; ?>
                            </span>
                        </td>
                        <td style="text-align:center;font-weight:600;color:var(--text)">
                            <?php echo (int)$t['plazo_dias']; ?> d
                        </td>
                        <td style="text-align:center;font-weight:600;color:<?php echo $t['descuento_pct'] > 0 ? '#fca5a5' : 'var(--text-dim)'; ?>">
                            <?php echo number_format((float)$t['descuento_pct'], 2); ?> %
                        </td>
                        <td style="font-size:.83rem;color:var(--text-dim)">
                            <?php echo $t['cuenta_nombre'] ? htmlspecialchars($t['cuenta_nombre'], ENT_QUOTES) : '—'; ?>
                        </td>
                        <td style="text-align:center">
                            <span class="badge <?php echo $t['habilitada'] ? 'badge-hab-t' : 'badge-dis-t'; ?>">
                                <?php echo $t['habilitada'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:.3rem;justify-content:center">
                            <button class="btn btn-secondary btn-sm" title="Editar"
                                    onclick="ttEditarAbrir(<?php echo htmlspecialchars(json_encode([
                                        'id'           => (int)$t['id'],
                                        'nombre'       => $t['nombre'],
                                        'red'          => $t['red'],
                                        'tipo_tarjeta' => $t['tipo_tarjeta'],
                                        'plazo_dias'   => (int)$t['plazo_dias'],
                                        'descuento_pct'=> (float)$t['descuento_pct'],
                                        'cuenta_id'    => $t['cuenta_id'] ? (int)$t['cuenta_id'] : '',
                                    ]), ENT_QUOTES); ?>)">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form method="POST" action="tes_tarjetas">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                <button class="btn btn-secondary btn-sm"
                                        title="<?php echo $t['habilitada'] ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fa-solid fa-<?php echo $t['habilitada'] ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="tes_tarjetas"
                                  onsubmit="return confirm('¿Eliminar esta terminal?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
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

<!-- ── Modal Editar Terminal ────────────────────────────────────────────── -->
<div class="tt-modal-overlay" id="ttEditarOverlay" onclick="if(event.target===this)ttEditarCerrar()">
    <div class="tt-modal">
        <div class="tt-modal-header">
            <span class="tt-modal-title">
                <i class="fa-solid fa-pen-to-square" style="margin-right:.45rem;color:var(--accent)"></i>Editar terminal
            </span>
            <button type="button" class="tt-modal-close" onclick="ttEditarCerrar()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="tes_tarjetas">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="editId">

            <div class="tt-grid" style="margin-bottom:.65rem">
                <div>
                    <label class="form-label" style="font-size:.8rem">Nombre *</label>
                    <input type="text" name="nombre" id="editNombre" class="form-control" required>
                </div>
                <div>
                    <label class="form-label" style="font-size:.8rem">Red</label>
                    <select name="red" id="editRed" class="form-control">
                        <option value="Visa">Visa</option>
                        <option value="Mastercard">Mastercard</option>
                        <option value="Amex">American Express</option>
                        <option value="Naranja">Naranja</option>
                        <option value="Cabal">Cabal</option>
                        <option value="">Otra</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:.8rem">Tipo</label>
                    <select name="tipo_tarjeta" id="editTipo" class="form-control">
                        <option value="credito">Crédito</option>
                        <option value="debito">Débito</option>
                        <option value="prepago">Prepago / QR</option>
                    </select>
                </div>
            </div>

            <div class="tt-grid" style="margin-bottom:1rem">
                <div>
                    <label class="form-label" style="font-size:.8rem">Plazo acreditación (días)</label>
                    <input type="number" name="plazo_dias" id="editPlazo" class="form-control"
                           min="0" max="90" step="1">
                </div>
                <div>
                    <label class="form-label" style="font-size:.8rem">Descuento procesadora (%)</label>
                    <input type="number" name="descuento_pct" id="editDescuento" class="form-control"
                           min="0" max="30" step="0.01">
                </div>
                <div>
                    <label class="form-label" style="font-size:.8rem">Acredita en cuenta</label>
                    <select name="cuenta_id" id="editCuenta" class="form-control">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($cuentas_banco as $cb): ?>
                        <option value="<?php echo (int)$cb['id']; ?>">
                            <?php echo htmlspecialchars($cb['nombre'], ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:.65rem;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" onclick="ttEditarCerrar()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function ttEditarAbrir(data) {
    document.getElementById('editId').value        = data.id;
    document.getElementById('editNombre').value    = data.nombre;
    document.getElementById('editRed').value       = data.red;
    document.getElementById('editTipo').value      = data.tipo_tarjeta;
    document.getElementById('editPlazo').value     = data.plazo_dias;
    document.getElementById('editDescuento').value = data.descuento_pct;
    document.getElementById('editCuenta').value    = data.cuenta_id !== undefined ? data.cuenta_id : '';
    document.getElementById('ttEditarOverlay').classList.add('open');
}
function ttEditarCerrar() {
    document.getElementById('ttEditarOverlay').classList.remove('open');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') ttEditarCerrar();
});
</script>
