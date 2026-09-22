<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Comprobante', ENT_QUOTES); ?> — AKIM</title>
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size: 13px;
        color: #111;
        background: #f5f5f5;
        padding: 1rem;
    }
    .print-shell {
        max-width: 780px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
    }
    .print-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        padding: .75rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .btn-print, .btn-close {
        padding: .4rem .9rem;
        border-radius: 6px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid;
        font-family: inherit;
    }
    .btn-print {
        background: #2563eb;
        color: #fff;
        border-color: #1d4ed8;
    }
    .btn-close {
        background: #fff;
        color: #374151;
        border-color: #d1d5db;
    }
    .btn-print:hover { background: #1d4ed8; }
    .btn-close:hover  { background: #f3f4f6; }

    @media print {
        body { background: #fff; padding: 0; }
        .print-shell { border: none; border-radius: 0; }
        .print-actions { display: none !important; }
    }
    </style>
</head>
<body>

<div class="print-shell">
    <div class="print-actions">
        <button class="btn-close" onclick="window.history.back()">
            ← Volver
        </button>
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir
        </button>
    </div>
    <?php echo $contenido; ?>
</div>

</body>
</html>
