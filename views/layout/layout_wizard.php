<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKIM — Nueva Venta</title>
    <link rel="stylesheet" href="includes/plugins/fontawesome-free-6.6.0-web/css/all.min.css">
    <script src="includes/plugins/jquery/jquery-3.5.1.min.js"></script>
    <style>
    /* ══════════════════════════════════════════════════════════════════════════
       AKIM — Wizard Layout (dark theme)
    ══════════════════════════════════════════════════════════════════════════ */
    :root {
        --bg:          #030712;
        --surface:     #111827;
        --surface-2:   #1f2937;
        --border:      rgba(255,255,255,.08);
        --border-med:  rgba(255,255,255,.13);
        --text:        #f9fafb;
        --text-muted:  #9ca3af;
        --text-dim:    #6b7280;
        --accent:      #2563eb;
        --accent-lt:   #3b82f6;
        --green:       #16a34a;
        --green-lt:    #22c55e;
        --red:         #dc2626;
        --amber:       #d97706;
        --radius:      10px;
        --radius-lg:   16px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 16px; }

    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--bg);
        background-image:
            radial-gradient(ellipse 70% 50% at 50% -5%, rgba(37,99,235,.12) 0%, transparent 65%);
        color: var(--text);
        min-height: 100vh;
    }

    /* ── Topbar ──────────────────────────────────────────────────────────────── */
    .wiz-topbar {
        background: rgba(11,17,32,.9);
        border-bottom: 1px solid var(--border);
        backdrop-filter: blur(16px);
        padding: 0 1.5rem;
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .wiz-topbar-brand {
        display: flex;
        align-items: center;
        gap: .65rem;
        text-decoration: none;
    }

    .wiz-topbar-mark {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 800; color: #fff;
        letter-spacing: -.04em;
    }

    .wiz-topbar-title {
        font-size: .9rem;
        font-weight: 600;
        color: var(--text);
        letter-spacing: -.01em;
    }

    .wiz-topbar-title span {
        color: var(--text-dim);
        font-weight: 400;
        font-size: .82rem;
    }

    .wiz-topbar-cancel {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .82rem;
        color: var(--text-dim);
        text-decoration: none;
        padding: .35rem .7rem;
        border-radius: 7px;
        border: 1px solid var(--border);
        transition: color .15s, border-color .15s, background .15s;
    }

    .wiz-topbar-cancel:hover {
        color: #fca5a5;
        border-color: rgba(220,38,38,.3);
        background: rgba(220,38,38,.06);
    }

    /* ── Progress bar ────────────────────────────────────────────────────────── */
    .wiz-progress-wrap {
        max-width: 640px;
        margin: 2rem auto 0;
        padding: 0 1.5rem;
    }

    .wiz-progress {
        display: flex;
        align-items: flex-start;
        position: relative;
    }

    /* Line between steps */
    .wiz-progress::before {
        content: '';
        position: absolute;
        top: 14px;
        left: calc(12.5% + 14px);
        right: calc(12.5% + 14px);
        height: 2px;
        background: var(--border-med);
        z-index: 0;
    }

    .wiz-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .45rem;
        position: relative;
        z-index: 1;
    }

    .wiz-step-dot {
        width: 28px; height: 28px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700;
        transition: background .2s, box-shadow .2s;
    }

    .wiz-step.done   .wiz-step-dot {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 0 12px rgba(37,99,235,.4);
    }

    .wiz-step.active .wiz-step-dot {
        background: var(--accent-lt);
        color: #fff;
        box-shadow: 0 0 0 4px rgba(59,130,246,.2), 0 0 16px rgba(59,130,246,.35);
    }

    .wiz-step.pending .wiz-step-dot {
        background: var(--surface-2);
        color: var(--text-dim);
        border: 1px solid var(--border-med);
    }

    .wiz-step-label {
        font-size: .68rem;
        font-weight: 500;
        color: var(--text-dim);
        text-align: center;
        white-space: nowrap;
    }

    .wiz-step.active  .wiz-step-label { color: var(--accent-lt); font-weight: 600; }
    .wiz-step.done    .wiz-step-label { color: var(--text-muted); }

    /* ── Container ───────────────────────────────────────────────────────────── */
    .wiz-container {
        max-width: 640px;
        margin: 1.5rem auto 3rem;
        padding: 0 1.5rem;
    }

    /* ── Card ────────────────────────────────────────────────────────────────── */
    .wiz-card {
        background: rgba(17,24,39,.85);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.75rem 2rem;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow:
            0 0 60px rgba(37,99,235,.06),
            0 24px 48px rgba(0,0,0,.35),
            inset 0 1px 0 rgba(255,255,255,.04);
    }

    .wiz-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -.025em;
        margin-bottom: .3rem;
    }

    .wiz-subtitle {
        font-size: .85rem;
        color: var(--text-dim);
        margin-bottom: 1.5rem;
    }

    .wiz-subtitle strong { color: var(--text-muted); }

    /* ── Footer wizard ───────────────────────────────────────────────────────── */
    .wiz-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
        gap: .75rem;
    }

    .btn-wiz-next {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: linear-gradient(to right, var(--accent), #1d4ed8);
        color: #fff;
        border: none;
        padding: .65rem 1.4rem;
        border-radius: var(--radius);
        font-size: .9rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        box-shadow: 0 3px 12px rgba(37,99,235,.3);
        transition: opacity .18s, box-shadow .18s;
    }

    .btn-wiz-next:hover  { opacity: .9; box-shadow: 0 5px 20px rgba(37,99,235,.45); }
    .btn-wiz-next:active { opacity: .85; }
    .btn-wiz-next:disabled {
        opacity: .35;
        cursor: not-allowed;
        box-shadow: none;
    }

    .btn-wiz-confirm {
        background: linear-gradient(to right, var(--green), #15803d);
        box-shadow: 0 3px 12px rgba(22,163,74,.3);
    }

    .btn-wiz-confirm:hover { box-shadow: 0 5px 20px rgba(22,163,74,.45); }

    .btn-wiz-back {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: transparent;
        color: var(--text-muted);
        border: 1px solid var(--border-med);
        padding: .65rem 1.2rem;
        border-radius: var(--radius);
        font-size: .88rem;
        font-family: inherit;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s, color .15s, border-color .15s;
    }

    .btn-wiz-back:hover {
        background: var(--surface-2);
        color: var(--text);
        border-color: rgba(255,255,255,.2);
    }

    /* ── Alertas ─────────────────────────────────────────────────────────────── */
    .akim-alert {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        padding: .8rem 1rem;
        border-radius: var(--radius);
        font-size: .84rem;
        margin-bottom: 1.25rem;
    }

    .akim-alert--error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.2);  color: #fca5a5; }
    .akim-alert--success { background: rgba(22,163,74,.1);  border: 1px solid rgba(22,163,74,.2);  color: #86efac; }
    .akim-alert--info    { background: rgba(37,99,235,.1);  border: 1px solid rgba(37,99,235,.2);  color: #93c5fd; }
    .akim-alert--warning { background: rgba(217,119,6,.1);  border: 1px solid rgba(217,119,6,.2);  color: #fcd34d; }

    .akim-alert p { margin: 0; }
    .akim-alert p + p { margin-top: .25rem; }

    /* ── Formulario ──────────────────────────────────────────────────────────── */
    .akim-form-group {
        display: flex;
        flex-direction: column;
        gap: .4rem;
        margin-bottom: .9rem;
    }

    .akim-form-group label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--text-muted);
    }

    .akim-input {
        width: 100%;
        padding: .7rem .95rem;
        background: rgba(31,41,55,.55);
        border: 1px solid var(--border-med);
        border-radius: var(--radius);
        color: var(--text);
        font-size: .9rem;
        font-family: inherit;
        outline: none;
        transition: border-color .18s, box-shadow .18s, background .18s;
    }

    .akim-input::placeholder { color: #4b5563; }

    .akim-input:focus {
        border-color: rgba(59,130,246,.55);
        background: rgba(31,41,55,.8);
        box-shadow: 0 0 0 3px rgba(59,130,246,.1);
    }

    .akim-input--lg { font-size: .95rem; padding: .8rem 1rem; }

    textarea.akim-input { resize: vertical; min-height: 64px; }

    select.akim-input { cursor: pointer; appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right .9rem center; padding-right: 2.5rem; }

    /* ── JIT Dropdown ────────────────────────────────────────────────────────── */
    .jit-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0; right: 0;
        background: #1e2d3d;
        border: 1px solid rgba(59,130,246,.3);
        border-radius: var(--radius);
        box-shadow: 0 8px 32px rgba(0,0,0,.45);
        z-index: 500;
        max-height: 240px;
        overflow-y: auto;
    }

    .jit-option {
        padding: .65rem 1rem;
        cursor: pointer;
        font-size: .88rem;
        color: var(--text-muted);
        border-bottom: 1px solid rgba(255,255,255,.05);
        transition: background .12s, color .12s;
    }

    .jit-option:last-child { border-bottom: none; }

    .jit-option:hover { background: rgba(37,99,235,.15); color: var(--text); }

    .jit-option--nuevo {
        color: #60a5fa;
        font-style: italic;
        border-top: 1px dashed rgba(59,130,246,.25);
    }

    .jit-option--nuevo:hover { background: rgba(37,99,235,.2); color: #93c5fd; }

    /* ── Tabla de ítems ──────────────────────────────────────────────────────── */
    .akim-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .84rem;
        margin-bottom: .5rem;
    }

    .akim-table thead th {
        text-align: left;
        padding: .55rem .8rem;
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--text-dim);
        border-bottom: 1px solid var(--border-med);
    }

    .akim-table tbody td {
        padding: .65rem .8rem;
        border-bottom: 1px solid var(--border);
        color: var(--text-muted);
        vertical-align: middle;
    }

    .akim-table tbody tr:last-child td { border-bottom: none; }

    .akim-table tbody tr:hover td { background: rgba(255,255,255,.025); }

    .akim-table tfoot td {
        padding: .6rem .8rem;
        border-top: 1px solid var(--border-med);
        color: var(--text-muted);
        font-size: .84rem;
    }

    .akim-table .total-row td {
        font-weight: 700;
        color: var(--green-lt);
        font-size: .92rem;
        border-top: 2px solid rgba(22,163,74,.3);
    }

    .btn-delete {
        background: rgba(220,38,38,.1);
        border: 1px solid rgba(220,38,38,.2);
        color: #f87171;
        border-radius: 6px;
        width: 28px; height: 28px;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer;
        font-size: .75rem;
        transition: background .15s, color .15s;
    }

    .btn-delete:hover { background: rgba(220,38,38,.22); color: #fca5a5; }

    /* ── Empty state ─────────────────────────────────────────────────────────── */
    .akim-empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--text-dim);
        font-size: .85rem;
        border: 1px dashed var(--border-med);
        border-radius: var(--radius);
        margin-bottom: .5rem;
    }

    /* ── Total display ───────────────────────────────────────────────────────── */
    .akim-total-display {
        padding: .75rem 1rem;
        background: rgba(22,163,74,.08);
        border: 1px solid rgba(22,163,74,.2);
        border-radius: var(--radius);
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--green-lt);
        letter-spacing: -.01em;
    }

    /* ── Grid de campos ──────────────────────────────────────────────────────── */
    .wiz-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .wiz-grid-items {
        display: grid;
        grid-template-columns: 1fr 90px 130px auto;
        gap: .65rem;
        align-items: end;
        margin-bottom: 1.25rem;
    }

    /* ── Resumen (step 4) ────────────────────────────────────────────────────── */
    .wiz-resumen {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 1.25rem;
    }

    .wiz-resumen-row {
        display: flex;
        align-items: center;
        padding: .65rem 1rem;
        border-bottom: 1px solid var(--border);
        gap: 1rem;
    }

    .wiz-resumen-row:last-child { border-bottom: none; }

    .wiz-resumen-label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--text-dim);
        width: 150px;
        flex-shrink: 0;
    }

    .wiz-resumen-value {
        font-size: .88rem;
        color: var(--text);
        font-weight: 500;
    }

    /* ── Responsive ──────────────────────────────────────────────────────────── */
    @media (max-width: 600px) {
        .wiz-grid-2 { grid-template-columns: 1fr; }
        .wiz-grid-items { grid-template-columns: 1fr 80px; }
        .wiz-grid-items .akim-form-group:nth-child(3),
        .wiz-grid-items button { grid-column: 1 / -1; }
        .wiz-card { padding: 1.25rem; }
        .wiz-resumen-label { width: 110px; }
    }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="wiz-topbar">
    <a href="menu" class="wiz-topbar-brand">
        <div class="wiz-topbar-mark">AK</div>
        <div class="wiz-topbar-title">
            AKIM <span>/ Nueva Venta</span>
        </div>
    </a>
    <a href="ventas" class="wiz-topbar-cancel">
        <i class="fa-solid fa-xmark"></i> Cancelar
    </a>
</div>

<!-- Progress bar -->
<div class="wiz-progress-wrap">
    <?php
    $paso        = $paso ?? 1;
    $pasos_names = ['¿Quién?', '¿Qué?', '¿Cómo?', 'Confirmar'];
    echo '<div class="wiz-progress">';
    for ($i = 1; $i <= 4; $i++) {
        $clase = ($i < $paso) ? 'done' : ($i === $paso ? 'active' : 'pending');
        $dot   = ($i < $paso) ? '<i class="fa-solid fa-check" style="font-size:.65rem"></i>' : $i;
        echo "<div class='wiz-step {$clase}'>";
        echo "<div class='wiz-step-dot'>{$dot}</div>";
        echo "<div class='wiz-step-label'>{$pasos_names[$i-1]}</div>";
        echo "</div>";
    }
    echo '</div>';
    ?>
</div>

<!-- Contenido del paso -->
<div class="wiz-container">
    <?php echo $contenido; ?>
</div>

</body>
</html>
