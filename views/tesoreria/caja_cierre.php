<?php
$caja     = $caja     ?? [];
$saldo_teo= $saldo_teo?? 0.0;
$retiros  = $retiros  ?? [];
$resumen  = $resumen  ?? [];

$apertura   = (float)($caja['saldo_apertura'] ?? 0);
$hora_ap    = $caja['hora_apertura'] ?? '';
$hora_ap_f  = $hora_ap ? date('H:i', strtotime($hora_ap)) : '—';
$fecha_f    = date('d/m/Y');

$total_retiros  = array_sum(array_column($retiros, 'monto'));
$cobros_ef      = (float)($resumen['cobros_efectivo'] ?? 0);
$cobros_otros   = (float)($resumen['cobros_otros']    ?? 0);
$ventas_total   = (float)($resumen['ventas_total']    ?? 0);
$ventas_cant    = (int)  ($resumen['ventas_cant']     ?? 0);
$medios         = $resumen['medios'] ?? [];

$medio_labels = [
    'efectivo'      => 'Efectivo',
    'transferencia' => 'Transferencia',
    'tarjeta'       => 'Tarjeta',
    'cheque'        => 'Cheque',
    'sin_cobro'     => 'Sin cobro',
];

$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<style>
.cc-wrap { max-width: 860px; margin: 0 auto; }
.cc-header { margin-bottom: 1.75rem; }
.cc-header h1 { font-size: 1.35rem; font-weight: 700; color: var(--text);
                letter-spacing: -.025em; display: flex; align-items: center; gap: .6rem; }
.cc-header p  { font-size: .83rem; color: var(--text-dim); margin-top: .25rem; }

.cc-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.25rem; align-items: start; }

/* Cards */
.cc-card { background: var(--surface); border: 1px solid var(--border);
           border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 1.25rem; }
.cc-card:last-child { margin-bottom: 0; }
.cc-card-hd { display: flex; align-items: center; gap: .5rem;
              padding: .85rem 1.15rem; border-bottom: 1px solid var(--border);
              font-size: .78rem; font-weight: 700; text-transform: uppercase;
              letter-spacing: .08em; color: var(--text-dim); }
.cc-card-hd i { font-size: .9rem; }
.cc-card-body { padding: 1rem 1.15rem; }

/* Filas de resumen */
.cc-row { display: flex; justify-content: space-between; align-items: center;
          padding: .5rem 0; border-bottom: 1px solid var(--border); font-size: .875rem; }
.cc-row:last-child { border-bottom: none; }
.cc-row-lbl { color: var(--text-dim); display: flex; align-items: center; gap: .5rem; }
.cc-row-val { font-weight: 600; font-variant-numeric: tabular-nums; color: var(--text); }
.cc-row-val.green { color: #4ade80; }
.cc-row-val.amber { color: #fbbf24; }
.cc-row-val.red   { color: #f87171; }
.cc-row-total { font-size: .95rem; font-weight: 700; }
.cc-row-total .cc-row-lbl { color: var(--text); font-weight: 700; }

/* Medio badge */
.medio-badge { display: inline-flex; align-items: center; gap: .3rem;
               padding: .15rem .55rem; border-radius: 4px; font-size: .72rem;
               font-weight: 600; background: var(--surface-2); color: var(--text-dim);
               border: 1px solid var(--border); }

/* Inputs de reconciliación */
.cc-input-group { margin-bottom: 1rem; }
.cc-input-group:last-child { margin-bottom: 0; }
.cc-input-lbl { display: block; font-size: .72rem; font-weight: 600;
                text-transform: uppercase; letter-spacing: .07em;
                color: var(--text-dim); margin-bottom: .4rem; }
.cc-input-money { position: relative; }
.cc-input-money span { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
                       font-size: 1rem; font-weight: 700; color: var(--text-dim); }
.cc-input-money input {
    width: 100%; padding: .65rem .85rem .65rem 1.75rem;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text);
    font-size: 1.2rem; font-weight: 700; font-family: inherit;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.cc-input-money input:focus {
    border-color: var(--accent-lt);
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}
.cc-input-text {
    width: 100%; padding: .55rem .8rem;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text);
    font-size: .875rem; font-family: inherit; resize: vertical; min-height: 68px;
    outline: none; transition: border-color .15s, box-shadow .15s;
}
.cc-input-text:focus { border-color: var(--accent-lt); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

/* Diferencia */
.cc-dif-box { border-radius: var(--radius); padding: .75rem 1rem;
              display: flex; justify-content: space-between; align-items: center;
              margin-bottom: 1rem; transition: background .2s, border .2s; }
.cc-dif-box.ok     { background: rgba(22,163,74,.08); border: 1px solid rgba(22,163,74,.2); }
.cc-dif-box.warn   { background: rgba(234,179,8,.08); border: 1px solid rgba(234,179,8,.25); }
.cc-dif-box.bad    { background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.25); }
.cc-dif-lbl { font-size: .78rem; color: var(--text-dim); }
.cc-dif-val { font-size: 1.1rem; font-weight: 700; }
.cc-dif-val.ok   { color: #4ade80; }
.cc-dif-val.warn { color: #fbbf24; }
.cc-dif-val.bad  { color: #f87171; }

/* Motivo — se oculta si diferencia = 0 */
.cc-motivo-wrap { display: none; }
.cc-motivo-wrap.show { display: block; }

/* Retiros mini-lista */
.cc-retiro-item { display: flex; justify-content: space-between; align-items: center;
                  padding: .4rem 0; border-bottom: 1px solid var(--border);
                  font-size: .82rem; }
.cc-retiro-item:last-child { border-bottom: none; }
.cc-retiro-motivo { color: var(--text-dim); max-width: 220px;
                    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cc-retiro-hora   { font-size: .7rem; color: var(--text-dim); }
.cc-retiro-monto  { font-weight: 600; color: #fbbf24; white-space: nowrap; }

/* Botón de cierre */
.btn-cierre { width: 100%; padding: .8rem; font-size: .95rem; font-weight: 700;
              background: rgba(220,38,38,.85); color: #fff; border: 1px solid rgba(220,38,38,.5);
              border-radius: var(--radius); cursor: pointer; font-family: inherit;
              display: flex; align-items: center; justify-content: center; gap: .5rem;
              transition: background .15s; }
.btn-cierre:hover { background: rgba(220,38,38,1); }
.btn-cierre:disabled { opacity: .5; cursor: not-allowed; }

.btn-volver { display: inline-flex; align-items: center; gap: .4rem;
              font-size: .82rem; color: var(--text-dim); margin-bottom: 1.25rem;
              transition: color .15s; }
.btn-volver:hover { color: var(--text); }

@media (max-width: 860px) {
    .cc-grid { grid-template-columns: 1fr; }
}
</style>

<div class="cc-wrap">

    <a href="menu" class="btn-volver">
        <i class="fa-solid fa-arrow-left"></i> Volver al panel
    </a>

    <?php if ($flash_error): ?>
    <div class="alert alert-error" style="margin-bottom:1.25rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
    </div>
    <?php endif; ?>

    <div class="cc-header">
        <h1>
            <i class="fa-solid fa-lock" style="color:#f87171"></i>
            Cierre de Caja
        </h1>
        <p><?php echo $fecha_f; ?> — Apertura <?php echo $hora_ap_f; ?>hs &nbsp;·&nbsp;
           Operado por <strong><?php echo htmlspecialchars($nombre ?? '', ENT_QUOTES); ?></strong>
        </p>
    </div>

    <div class="cc-grid">

        <!-- ── COLUMNA IZQUIERDA: Resumen del día ── -->
        <div>

            <!-- Ventas por medio de pago -->
            <div class="cc-card">
                <div class="cc-card-hd">
                    <i class="fa-solid fa-receipt"></i> Ventas del día
                </div>
                <div class="cc-card-body">
                    <div class="cc-row">
                        <span class="cc-row-lbl"><i class="fa-solid fa-hashtag"></i> Cantidad de operaciones</span>
                        <span class="cc-row-val"><?php echo $ventas_cant; ?></span>
                    </div>
                    <?php if (empty($medios)): ?>
                    <div class="cc-row">
                        <span class="cc-row-lbl" style="font-style:italic">Sin ventas cobradas registradas hoy</span>
                        <span class="cc-row-val">$0,00</span>
                    </div>
                    <?php else: ?>
                        <?php foreach ($medios as $m): ?>
                        <div class="cc-row">
                            <span class="cc-row-lbl">
                                <span class="medio-badge">
                                    <?php echo htmlspecialchars($medio_labels[$m['medio']] ?? ucfirst($m['medio']), ENT_QUOTES); ?>
                                </span>
                                <span style="font-size:.72rem;color:var(--text-dim)"><?php echo (int)$m['cant']; ?> cobros</span>
                            </span>
                            <span class="cc-row-val <?php echo $m['medio'] === 'efectivo' ? 'green' : ''; ?>">
                                $<?php echo number_format((float)$m['tot'], 2, ',', '.'); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="cc-row cc-row-total" style="margin-top:.25rem;padding-top:.65rem;border-top:1px solid var(--border)">
                        <span class="cc-row-lbl">Total cobrado</span>
                        <span class="cc-row-val">$<?php echo number_format($ventas_total, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Movimientos de caja -->
            <div class="cc-card">
                <div class="cc-card-hd">
                    <i class="fa-solid fa-coins"></i> Movimientos de efectivo
                </div>
                <div class="cc-card-body">
                    <div class="cc-row">
                        <span class="cc-row-lbl"><i class="fa-solid fa-door-open"></i> Saldo de apertura</span>
                        <span class="cc-row-val">$<?php echo number_format($apertura, 2, ',', '.'); ?></span>
                    </div>
                    <div class="cc-row">
                        <span class="cc-row-lbl"><i class="fa-solid fa-arrow-down"></i> Cobros en efectivo</span>
                        <span class="cc-row-val green">+ $<?php echo number_format($cobros_ef, 2, ',', '.'); ?></span>
                    </div>
                    <?php if ($total_retiros > 0): ?>
                    <div class="cc-row">
                        <span class="cc-row-lbl"><i class="fa-solid fa-arrow-up"></i> Retiros del día</span>
                        <span class="cc-row-val amber">− $<?php echo number_format($total_retiros, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="cc-row cc-row-total" style="margin-top:.25rem;padding-top:.65rem;border-top:1px solid var(--border)">
                        <span class="cc-row-lbl">Saldo teórico en caja</span>
                        <span class="cc-row-val green">$<?php echo number_format($saldo_teo, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Retiros detalle -->
            <?php if (!empty($retiros)): ?>
            <div class="cc-card">
                <div class="cc-card-hd">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    Retiros registrados (<?php echo count($retiros); ?>)
                </div>
                <div class="cc-card-body">
                    <?php foreach ($retiros as $r): ?>
                    <div class="cc-retiro-item">
                        <div>
                            <div class="cc-retiro-motivo"><?php echo htmlspecialchars($r['motivo'], ENT_QUOTES); ?></div>
                            <div class="cc-retiro-hora">
                                <?php echo date('H:i', strtotime($r['created_at'])); ?>hs
                                — <?php echo htmlspecialchars($r['usuario'] ?? '', ENT_QUOTES); ?>
                            </div>
                        </div>
                        <span class="cc-retiro-monto">−$<?php echo number_format((float)$r['monto'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ── COLUMNA DERECHA: Reconciliación y cierre ── -->
        <div>
            <div class="cc-card">
                <div class="cc-card-hd">
                    <i class="fa-solid fa-scale-balanced"></i> Reconciliación de caja
                </div>
                <div class="cc-card-body">
                    <form method="POST" action="caja_cierre" id="form-cierre">

                        <!-- 1. Declarar efectivo físico -->
                        <div class="cc-input-group">
                            <label class="cc-input-lbl" for="inp-declarado">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                Efectivo contado físicamente
                            </label>
                            <div class="cc-input-money">
                                <span>$</span>
                                <input type="number" id="inp-declarado" name="declarado"
                                       step="0.01" min="0"
                                       value="<?php echo number_format($saldo_teo, 2, '.', ''); ?>"
                                       placeholder="0.00" required
                                       oninput="recalcular()">
                            </div>
                            <p style="font-size:.72rem;color:var(--text-dim);margin-top:.35rem">
                                Ingresá el total de efectivo que tenés físicamente en este momento.
                            </p>
                        </div>

                        <!-- 2. Diferencia (calculada en tiempo real) -->
                        <div class="cc-dif-box ok" id="dif-box">
                            <div>
                                <div class="cc-dif-lbl">Diferencia (declarado − teórico)</div>
                                <div style="font-size:.72rem;color:var(--text-dim)">
                                    Teórico: $<?php echo number_format($saldo_teo, 2, ',', '.'); ?>
                                </div>
                            </div>
                            <div class="cc-dif-val ok" id="dif-val">$0,00</div>
                        </div>

                        <!-- 3. Motivo de diferencia (condicional) -->
                        <div class="cc-motivo-wrap" id="motivo-wrap">
                            <div class="cc-input-group">
                                <label class="cc-input-lbl" for="inp-obs">
                                    <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
                                    Motivo de la diferencia <span style="color:#f87171">*</span>
                                </label>
                                <textarea id="inp-obs" name="obs_cierre" class="cc-input-text"
                                          placeholder="Describí el motivo de la diferencia..."
                                          maxlength="500"></textarea>
                            </div>
                        </div>
                        <!-- obs sin diferencia (vacío, se envía igual) -->
                        <input type="hidden" id="inp-obs-hidden" name="obs_cierre" value="">

                        <!-- 4. Saldo a dejar para mañana -->
                        <div class="cc-input-group" style="padding-top:.75rem;border-top:1px solid var(--border)">
                            <label class="cc-input-lbl" for="inp-cierre">
                                <i class="fa-solid fa-calendar-day"></i>
                                Saldo a dejar en caja (abre mañana)
                            </label>
                            <div class="cc-input-money">
                                <span>$</span>
                                <input type="number" id="inp-cierre" name="saldo_cierre"
                                       step="0.01" min="0"
                                       value="<?php echo number_format($saldo_teo, 2, '.', ''); ?>"
                                       placeholder="0.00" required>
                            </div>
                            <p style="font-size:.72rem;color:var(--text-dim);margin-top:.35rem">
                                Este monto se sugerirá como saldo de apertura mañana.
                                Podés ajustarlo si retirás parte del efectivo.
                            </p>
                        </div>

                        <!-- Confirmación -->
                        <button type="submit" class="btn-cierre" id="btn-cierre">
                            <i class="fa-solid fa-lock"></i>
                            Confirmar cierre de caja
                        </button>

                        <p style="font-size:.72rem;color:var(--text-dim);text-align:center;margin-top:.75rem">
                            Esta acción cierra el día. No podrás registrar nuevas ventas<br>
                            hasta la apertura de mañana.
                        </p>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
var saldoTeo = <?php echo json_encode(round($saldo_teo, 2)); ?>;

function fmt(n) {
    return '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcular() {
    var declarado  = parseFloat(document.getElementById('inp-declarado').value) || 0;
    var diferencia = Math.round((declarado - saldoTeo) * 100) / 100;

    var box  = document.getElementById('dif-box');
    var val  = document.getElementById('dif-val');
    var mwrap= document.getElementById('motivo-wrap');
    var obs  = document.getElementById('inp-obs');
    var obsH = document.getElementById('inp-obs-hidden');

    // Pre-llenar saldo_cierre con el declarado si el usuario no lo tocó
    var inpCierre = document.getElementById('inp-cierre');
    if (!inpCierre.dataset.touched) {
        inpCierre.value = declarado.toFixed(2);
    }

    var sign = diferencia >= 0 ? '+' : '';
    val.textContent = sign + fmt(diferencia);

    box.className  = 'cc-dif-box';
    val.className  = 'cc-dif-val';

    if (Math.abs(diferencia) < 0.01) {
        box.classList.add('ok');  val.classList.add('ok');
        mwrap.classList.remove('show');
        if (obs) obs.removeAttribute('required');
        obsH.disabled = false;
    } else if (Math.abs(diferencia) <= 500) {
        box.classList.add('warn'); val.classList.add('warn');
        mwrap.classList.add('show');
        if (obs) obs.setAttribute('required', '');
        obsH.disabled = true;
    } else {
        box.classList.add('bad');  val.classList.add('bad');
        mwrap.classList.add('show');
        if (obs) obs.setAttribute('required', '');
        obsH.disabled = true;
    }
}

// Marcar saldo_cierre como "tocado" si el usuario lo modifica manualmente
document.getElementById('inp-cierre').addEventListener('input', function() {
    this.dataset.touched = '1';
});

// Bloquear botón al submit
document.getElementById('form-cierre').addEventListener('submit', function() {
    var btn = document.getElementById('btn-cierre');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cerrando...';
});

// Inicializar
recalcular();
</script>
