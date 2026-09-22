<?php
$productos    = $productos    ?? [];
$cuentas_tes  = $cuentas_tes  ?? [];
$tarjetas_tes = $tarjetas_tes ?? [];
$flash_ok     = $flash_ok     ?? null;
$flash_error  = $flash_error  ?? null;

$cuentas_ef  = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'efectivo');
$cuentas_bco = array_filter($cuentas_tes, fn($c) => $c['tipo'] === 'banco');

// Agrupar productos por categoría
$categorias = [];
foreach ($productos as $p) {
    $cat = $p['categoria_nombre'] ?: 'Sin categoría';
    $categorias[$cat][] = $p;
}
ksort($categorias);
?>

<style>
/* ── Layout POS ──────────────────────────────────────────────── */
.pos-layout {
    display: grid;
    grid-template-columns: 1fr 6px var(--cart-width, 340px);
    gap: 0;
    height: calc(100vh - 120px);
    min-height: 500px;
    position: relative;
}
/* ── Divider arrastrable ──────────────────────────────────────── */
.pos-resizer {
    width: 6px;
    cursor: col-resize;
    background: transparent;
    position: relative;
    z-index: 10;
    flex-shrink: 0;
    transition: background .15s;
    align-self: stretch;
}
.pos-resizer::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 2px;
    height: 40px;
    border-radius: 2px;
    background: var(--border-med);
    transition: background .15s, height .15s;
}
.pos-resizer:hover::after,
.pos-resizer.dragging::after {
    background: var(--accent);
    height: 60px;
}
.pos-resizer.dragging { background: rgba(37,99,235,.05); }

/* ── Modo pantalla completa ──────────────────────────────────── */
body.pos-fullscreen .sidebar-nav,
body.pos-fullscreen .topbar,
body.pos-fullscreen .layout-header { display: none !important; }
body.pos-fullscreen .main-content  { padding: 0 !important; margin: 0 !important; }
body.pos-fullscreen .page-wrapper  { padding: 0 !important; margin: 0 !important; }
body.pos-fullscreen .pos-layout    { height: 100vh; border-radius: 0; }
body.pos-fullscreen .pos-catalogo  { padding: .75rem; }
body.pos-fullscreen .pos-cart      { border-radius: 0; border-top: none; border-bottom: none; border-right: none; }
body.pos-fullscreen #btnFullscreen i { color: #fbbf24; }

/* ── Barra de código de barras ───────────────────────────────── */
.pos-barcode-bar {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: rgba(37,99,235,.07);
    border: 1px solid rgba(37,99,235,.2);
    border-radius: 9px;
    padding: .38rem .65rem .38rem .5rem;
}
.pos-barcode-bar i { color: #60a5fa; font-size: .88rem; flex-shrink: 0; }
.pos-barcode-bar input {
    background: none; border: none; outline: none;
    color: var(--text); font-size: .9rem; font-family: monospace;
    width: 155px; letter-spacing: .04em;
}
.pos-barcode-bar input::placeholder { color: var(--text-dim); font-family: inherit; font-size: .8rem; letter-spacing: 0; }
.pos-barcode-bar .pos-bc-btn {
    padding: .22rem .55rem; border-radius: 6px;
    background: var(--accent); color: #fff; border: none;
    font-size: .75rem; font-weight: 700; cursor: pointer; font-family: inherit;
}
.pos-barcode-bar .pos-bc-btn:hover { opacity: .85; }

/* ── Feedback de scan ────────────────────────────────────────── */
.pos-scan-toast {
    position: fixed; top: 1rem; right: 1rem; z-index: 9999;
    padding: .6rem 1rem; border-radius: 9px; font-size: .82rem; font-weight: 600;
    opacity: 0; transform: translateY(-8px);
    transition: opacity .2s, transform .2s;
    pointer-events: none;
}
.pos-scan-toast.show { opacity: 1; transform: translateY(0); }
.pos-scan-toast.ok  { background: rgba(22,163,74,.9);  color: #fff; }
.pos-scan-toast.err { background: rgba(220,38,38,.9);  color: #fff; }

/* ── Panel izquierdo — catálogo ──────────────────────────────── */
.pos-catalogo {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    overflow: hidden;
}
.pos-catalogo-top {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
}
.pos-catalogo-top h1 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -.02em;
    margin: 0;
    flex: 1;
}
.pos-search {
    display: flex;
    align-items: center;
    gap: .45rem;
    background: rgba(31,41,55,.5);
    border: 1px solid var(--border-med);
    border-radius: 8px;
    padding: .38rem .75rem;
}
.pos-search i { color: var(--text-dim); font-size: .8rem; }
.pos-search input {
    background: none;
    border: none;
    outline: none;
    color: var(--text);
    font-size: .84rem;
    font-family: inherit;
    width: 190px;
}
.pos-search input::placeholder { color: var(--text-dim); }

/* Tabs de categorías */
.pos-tabs {
    display: flex;
    gap: .35rem;
    overflow-x: auto;
    padding-bottom: .1rem;
    scrollbar-width: none;
}
.pos-tabs::-webkit-scrollbar { display: none; }
.pos-tab {
    padding: .28rem .8rem;
    border-radius: 6px;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    border: 1px solid var(--border);
    color: var(--text-dim);
    background: transparent;
    transition: all .12s;
    user-select: none;
}
.pos-tab.active, .pos-tab:hover {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}

/* Imagen miniatura en tarjeta POS */
.pos-product-img {
    width: 100%;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: .4rem;
    background: var(--bg);
    display: block;
}

/* Grilla de productos */
.pos-grid-wrap {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
}
.pos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
    gap: .65rem;
    padding-bottom: .5rem;
}
.pos-product-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: .85rem .9rem;
    cursor: pointer;
    transition: border-color .12s, background .12s, transform .08s;
    user-select: none;
    position: relative;
}
.pos-product-card:hover {
    border-color: var(--accent);
    background: rgba(37,99,235,.06);
}
.pos-product-card:active { transform: scale(.97); }
.pos-product-card.sin-stock { opacity: .45; cursor: not-allowed; }

.pos-product-code {
    font-size: .66rem;
    color: var(--text-dim);
    font-family: monospace;
    margin-bottom: .25rem;
}
.pos-product-name {
    font-size: .84rem;
    font-weight: 600;
    color: var(--text);
    line-height: 1.3;
    margin-bottom: .45rem;
    min-height: 2.2em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.pos-product-price {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: -.02em;
}
.pos-product-stock {
    font-size: .69rem;
    color: var(--text-dim);
    margin-top: .2rem;
}
.pos-product-stock.bajo { color: #f87171; }

/* ── Panel derecho — carrito ─────────────────────────────────── */
.pos-cart {
    display: flex;
    flex-direction: column;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}
.pos-cart-header {
    padding: .85rem 1rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pos-cart-title { font-size: .9rem; font-weight: 700; color: var(--text); }
.pos-cart-count {
    background: var(--accent);
    color: #fff;
    border-radius: 99px;
    font-size: .7rem;
    font-weight: 700;
    padding: .1rem .5rem;
    min-width: 22px;
    text-align: center;
}

.pos-cart-items {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    padding: .5rem .75rem;
    display: flex;
    flex-direction: column;
    gap: .4rem;
    min-height: 120px;
}
.pos-cart-empty {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: .5rem;
    color: var(--text-dim);
    font-size: .82rem;
    opacity: .5;
}

.pos-cart-item {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .5rem;
    border-radius: 8px;
    background: rgba(255,255,255,.03);
    border: 1px solid var(--border);
}
.pos-cart-item-name {
    flex: 1;
    font-size: .8rem;
    font-weight: 600;
    color: var(--text);
    line-height: 1.2;
}
.pos-cart-item-sub {
    font-size: .72rem;
    color: var(--text-dim);
    display: block;
    font-weight: 400;
}
.pos-qty-ctrl {
    display: flex;
    align-items: center;
    gap: .3rem;
}
.pos-qty-btn {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 1px solid var(--border-med);
    background: var(--bg);
    color: var(--text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    transition: background .1s;
}
.pos-qty-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.pos-qty-val {
    min-width: 28px;
    text-align: center;
    font-size: .82rem;
    font-weight: 700;
    color: var(--text);
}
.pos-item-total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
    min-width: 110px;
    cursor: text;
}
.pos-precio-wrapper {
    display: flex;
    align-items: baseline;
    gap: 3px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
}
.pos-item-subtotal {
    font-size: .72rem;
    color: var(--text-dim);
    font-weight: 400;
}
.pos-item-del {
    background: none;
    border: none;
    color: var(--text-dim);
    cursor: pointer;
    font-size: .75rem;
    padding: .15rem .2rem;
    border-radius: 4px;
    transition: color .1s;
}
.pos-item-del:hover { color: #f87171; }

/* Totales y pago */
.pos-cart-footer {
    border-top: 1px solid var(--border);
    padding: .85rem 1rem;
    display: flex;
    flex-direction: column;
    gap: .65rem;
}
.pos-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pos-total-label { font-size: .8rem; color: var(--text-dim); }
.pos-total-val { font-size: 1.25rem; font-weight: 800; color: var(--text); letter-spacing: -.025em; }

.pos-medio-btns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .4rem;
}
.pos-medio-btn {
    padding: .45rem;
    border-radius: 8px;
    border: 2px solid var(--border);
    background: transparent;
    color: var(--text-dim);
    cursor: pointer;
    font-size: .77rem;
    font-weight: 600;
    transition: all .12s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .2rem;
    font-family: inherit;
}
.pos-medio-btn i { font-size: .9rem; }
.pos-medio-btn.active {
    border-color: var(--accent);
    background: rgba(37,99,235,.1);
    color: var(--accent);
}

.pos-cuenta-wrap { display: none; }
.pos-cuenta-wrap.visible { display: block; }

.pos-cobrar-btn {
    width: 100%;
    padding: .75rem;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    transition: opacity .12s;
    font-family: inherit;
}
.pos-cobrar-btn:disabled { opacity: .4; pointer-events: none; }
.pos-cobrar-btn:hover:not(:disabled) { opacity: .9; }

/* Precio editable en carrito — siempre visible como input */
.pos-precio-input {
    background: transparent;
    border: none;
    border-bottom: 1px dashed transparent;
    color: var(--text);
    font-size: 1rem;
    font-family: monospace;
    font-weight: 700;
    width: 90px;
    text-align: right;
    cursor: text;
    outline: none;
    padding: 1px 2px;
    border-radius: 3px;
    transition: border-color .15s, background .15s, color .15s;
    -moz-appearance: textfield;
}
.pos-precio-input::-webkit-outer-spin-button,
.pos-precio-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.pos-precio-input:hover  { border-bottom-color: var(--text-dim); }
.pos-precio-input:focus  { border-bottom-color: var(--accent); color: var(--text); background: rgba(37,99,235,.06); }

/* Descuento */
.pos-descuento {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .8rem;
    color: var(--text-dim);
}
.pos-descuento input {
    width: 60px;
    padding: .28rem .45rem;
    border-radius: 6px;
    border: 1px solid var(--border-med);
    background: var(--bg);
    color: var(--text);
    font-size: .82rem;
    font-family: inherit;
    text-align: right;
}

@media (max-width: 900px) {
    .pos-layout { grid-template-columns: 1fr; height: auto; }
    .pos-grid-wrap { max-height: 60vh; }
    .pos-cart { min-height: 400px; }
}
</style>

<?php if ($flash_ok): ?>
<div class="alert alert-success" style="margin-bottom:1rem">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-error" style="margin-bottom:1rem">
    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<div class="pos-layout">

    <!-- ── CATÁLOGO ─────────────────────────────────────────── -->
    <div class="pos-catalogo">

        <div class="pos-catalogo-top">
            <h1><i class="fa-solid fa-store" style="color:var(--accent);margin-right:.4rem;font-size:1.05rem"></i>Punto de Venta</h1>

            <!-- Código de barras -->
            <div class="pos-barcode-bar" title="Escaneá o escribí un código y presioná Enter">
                <i class="fa-solid fa-barcode"></i>
                <input type="text" id="barcodeInput" placeholder="Código de barras…"
                       autocomplete="off" autocorrect="off" spellcheck="false"
                       onkeydown="if(event.key==='Enter'){posScanBarcode();event.preventDefault();}">
                <button type="button" class="pos-bc-btn" onclick="posScanBarcode()">OK</button>
            </div>

            <div class="pos-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="posSearch" placeholder="Buscar producto…" oninput="posFiltrar(this.value)">
            </div>
            <button type="button" id="btnFullscreen" class="btn btn-secondary btn-sm"
                    title="Pantalla completa" onclick="posToggleFullscreen()">
                <i class="fa-solid fa-expand"></i>
            </button>
            <a href="ventas" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-receipt"></i> Ventas
            </a>
        </div>

        <!-- Tabs categorías -->
        <div class="pos-tabs" id="posTabs">
            <div class="pos-tab active" data-cat="__all__" onclick="posTab(this, '__all__')">
                Todos
            </div>
            <?php foreach (array_keys($categorias) as $cat): ?>
            <div class="pos-tab" data-cat="<?php echo htmlspecialchars($cat, ENT_QUOTES); ?>"
                 onclick="posTab(this, '<?php echo htmlspecialchars(addslashes($cat)); ?>')">
                <?php echo htmlspecialchars($cat, ENT_QUOTES); ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Grid productos -->
        <div class="pos-grid-wrap">
            <?php if (empty($productos)): ?>
            <div style="padding:3rem;text-align:center;color:var(--text-dim)">
                <i class="fa-solid fa-boxes-stacked" style="font-size:1.8rem;opacity:.2;display:block;margin-bottom:.75rem"></i>
                <p>No hay productos habilitados.</p>
                <a href="stk_productos_edit" class="btn btn-secondary btn-sm" style="margin-top:.5rem">
                    Agregar producto
                </a>
            </div>
            <?php else: ?>
            <div class="pos-grid" id="posGrid">
                <?php foreach ($productos as $p):
                    $stock = (float)($p['stock_actual'] ?? 0);
                    $sinStock = $stock <= 0;
                ?>
                <div class="pos-product-card<?php echo $sinStock ? ' sin-stock' : ''; ?>"
                     data-id="<?php echo (int)$p['id']; ?>"
                     data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>"
                     data-precio="<?php echo (float)$p['precio_venta']; ?>"
                     data-stock="<?php echo $stock; ?>"
                     data-codigo="<?php echo htmlspecialchars(strtolower(trim($p['codigo'] ?? '')), ENT_QUOTES); ?>"
                     data-cat="<?php echo htmlspecialchars($p['categoria_nombre'] ?? '', ENT_QUOTES); ?>"
                     data-search="<?php echo strtolower(htmlspecialchars($p['nombre'] . ' ' . ($p['codigo'] ?? '') . ' ' . ($p['categoria_nombre'] ?? ''), ENT_QUOTES)); ?>"
                     onclick="posAgregar(this)">
                    <?php if (!empty($p['imagen'])): ?>
                    <img class="pos-product-img"
                         src="<?php echo htmlspecialchars($p['imagen'], ENT_QUOTES); ?>"
                         loading="lazy"
                         alt="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>">
                    <?php endif; ?>
                    <?php if ($p['codigo'] ?? ''): ?>
                    <div class="pos-product-code"><?php echo htmlspecialchars($p['codigo'], ENT_QUOTES); ?></div>
                    <?php endif; ?>
                    <div class="pos-product-name"><?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?></div>
                    <div class="pos-product-price">
                        $ <?php echo number_format((float)$p['precio_venta'], 2, ',', '.'); ?>
                    </div>
                    <div class="pos-product-stock<?php echo $stock <= 2 ? ' bajo' : ''; ?>">
                        <?php echo $sinStock ? 'Sin stock' : 'Stock: ' . number_format($stock, 0); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /pos-catalogo -->

    <!-- ── DIVIDER ARRASTRABLE ───────────────────────────────── -->
    <div class="pos-resizer" id="posResizer" title="Arrastrá para cambiar el tamaño"></div>

    <!-- ── CARRITO ──────────────────────────────────────────── -->
    <div class="pos-cart">

        <div class="pos-cart-header">
            <span class="pos-cart-title"><i class="fa-solid fa-cart-shopping" style="margin-right:.4rem"></i>Carrito</span>
            <span class="pos-cart-count" id="cartCount">0</span>
        </div>

        <div class="pos-cart-items" id="cartItems">
            <div class="pos-cart-empty" id="cartEmpty">
                <i class="fa-solid fa-cart-shopping" style="font-size:1.5rem"></i>
                <span>Seleccioná un producto</span>
            </div>
        </div>

        <div class="pos-cart-footer">

            <!-- Descuento -->
            <div class="pos-descuento">
                <span>Descuento</span>
                <input type="number" id="descInput" min="0" max="100" step="0.5" value="0"
                       oninput="posRecalcular()">
                <span>%</span>
            </div>

            <!-- Totales -->
            <div>
                <div class="pos-total-row" id="subtotalRow" style="display:none">
                    <span class="pos-total-label">Subtotal</span>
                    <span style="font-size:.85rem;font-weight:600;color:var(--text)" id="subtotalVal">$ 0,00</span>
                </div>
                <div class="pos-total-row" id="descuentoRow" style="display:none">
                    <span class="pos-total-label">Descuento</span>
                    <span style="font-size:.85rem;color:#f87171" id="descuentoVal">- $ 0,00</span>
                </div>
                <div class="pos-total-row">
                    <span class="pos-total-label" style="font-weight:700">TOTAL</span>
                    <span class="pos-total-val" id="totalVal">$ 0,00</span>
                </div>
            </div>

            <!-- Medio de pago -->
            <div>
                <p style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim);margin-bottom:.4rem">
                    Medio de pago
                </p>
                <div class="pos-medio-btns" style="grid-template-columns:<?php echo !empty($tarjetas_tes) ? '1fr 1fr 1fr' : '1fr 1fr'; ?>">
                    <button type="button" class="pos-medio-btn active" data-medio="efectivo"
                            onclick="posMedio(this)">
                        <i class="fa-solid fa-money-bill-wave"></i> Efectivo
                    </button>
                    <button type="button" class="pos-medio-btn" data-medio="transferencia"
                            onclick="posMedio(this)">
                        <i class="fa-solid fa-building-columns"></i> Transferencia
                    </button>
                    <?php if (!empty($tarjetas_tes)): ?>
                    <button type="button" class="pos-medio-btn" data-medio="tarjeta"
                            onclick="posMedio(this)">
                        <i class="fa-solid fa-credit-card"></i> Tarjeta
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cuenta destino -->
            <?php if (!empty($cuentas_ef) || !empty($cuentas_bco)): ?>
            <div>
                <div class="pos-cuenta-wrap visible" id="cuentaEfectivo">
                    <label style="font-size:.75rem;color:var(--text-dim)">Caja / Cuenta efectivo</label>
                    <select class="form-control form-control-sm" id="selCuentaEf">
                        <?php foreach ($cuentas_ef as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>">
                            <?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (empty($cuentas_ef)): ?>
                        <option value="0">— Sin cuenta efectivo —</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="pos-cuenta-wrap" id="cuentaBanco">
                    <label style="font-size:.75rem;color:var(--text-dim)">Cuenta bancaria</label>
                    <select class="form-control form-control-sm" id="selCuentaBco">
                        <?php foreach ($cuentas_bco as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>">
                            <?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if (empty($cuentas_bco)): ?>
                        <option value="0">— Sin cuenta banco —</option>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (!empty($tarjetas_tes)): ?>
                <div class="pos-cuenta-wrap" id="cuentaTarjeta">
                    <label style="font-size:.75rem;color:var(--text-dim)">Seleccioná la tarjeta</label>
                    <select class="form-control form-control-sm" id="selTarjeta">
                        <?php foreach ($tarjetas_tes as $t): ?>
                        <option value="<?php echo (int)$t['id']; ?>"
                                data-cuenta="<?php echo (int)($t['cuenta_id'] ?? 0); ?>">
                            <?php echo htmlspecialchars($t['nombre'], ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Botón cobrar -->
            <button type="button" class="pos-cobrar-btn" id="btnCobrar" disabled onclick="posCobrar()">
                <i class="fa-solid fa-circle-dollar-to-slot"></i>
                Cobrar
            </button>
        </div>

    </div><!-- /pos-cart -->

</div><!-- /pos-layout -->

<!-- Form oculto para submit -->
<form method="POST" action="tienda" id="posForm" style="display:none">
    <input type="hidden" name="accion"       value="vender">
    <input type="hidden" name="cliente_id"   value="0">
    <input type="hidden" name="items_json"   id="fItemsJson">
    <input type="hidden" name="medio"        id="fMedio"      value="efectivo">
    <input type="hidden" name="cuenta_id"    id="fCuentaId"   value="0">
    <input type="hidden" name="tarjeta_id"   id="fTarjetaId"  value="0">
    <input type="hidden" name="descuento"    id="fDescuento" value="0">
    <input type="hidden" name="observaciones" value="">
    <input type="hidden" name="fecha"        value="<?php echo date('Y-m-d'); ?>">
</form>

<script>
// ── Estado del carrito ──────────────────────────────────────────────────────
let cart = {}; // { id: { id, nombre, precio, cant } }
let medioActual   = 'efectivo';
let tarjetaActual = 0;

// ── Agregar producto al carrito ─────────────────────────────────────────────
function posAgregar(el) {
    // Subir al card padre si se clickeó un hijo
    if (!el.dataset.id) el = el.closest('.pos-product-card');
    if (!el) { console.warn('[POS] posAgregar: no se encontró el card'); return; }

    const id     = el.dataset.id;
    const nombre = el.dataset.nombre;
    const precio = parseFloat(el.dataset.precio);

    if (!id || isNaN(precio)) return;

    if (cart[id]) {
        cart[id].cant += 1;
    } else {
        cart[id] = { id, nombre, precio, cant: 1 };
    }
    posRenderCart();
    el.style.borderColor = 'var(--accent)';
    setTimeout(() => { el.style.borderColor = ''; }, 300);
}

// ── Cambiar cantidad ────────────────────────────────────────────────────────
function posQty(id, delta) {
    if (!cart[id]) return;
    cart[id].cant += delta;
    if (cart[id].cant <= 0) delete cart[id];
    posRenderCart();
}

// ── Eliminar ítem ────────────────────────────────────────────────────────────
function posQuitar(id) {
    delete cart[id];
    posRenderCart();
}

// ── Actualizar precio desde el input inline ──────────────────────────────────
function posPrecioActualizar(input) {
    const id = input.dataset.id;
    if (!cart[id]) return;
    const nuevo = parseFloat(String(input.value).replace(',', '.'));
    if (!isNaN(nuevo) && nuevo >= 0) {
        cart[id].precio = nuevo;
        const row = input.closest('.pos-cart-item');
        if (row) {
            const subEl = row.querySelector('.pos-item-subtotal');
            const cant  = cart[id].cant;
            if (cant > 1) {
                const sub = (cant * nuevo).toFixed(2).replace('.', ',');
                if (subEl) {
                    subEl.textContent = 'subtotal $ ' + sub;
                } else {
                    // Crear el elemento si no existe (cant cambió a > 1)
                    const wrapper = row.querySelector('.pos-item-total');
                    if (wrapper) {
                        const s = document.createElement('span');
                        s.className = 'pos-item-subtotal';
                        s.textContent = 'subtotal $ ' + sub;
                        wrapper.appendChild(s);
                    }
                }
            } else if (subEl) {
                subEl.remove();
            }
        }
    } else {
        input.value = cart[id].precio.toFixed(2);
    }
    posRecalcular();
}

// ── Renderizar carrito ──────────────────────────────────────────────────────
function posRenderCart() {
    const items = Object.values(cart);
    const container = document.getElementById('cartItems');
    const empty     = document.getElementById('cartEmpty');
    const count     = document.getElementById('cartCount');
    const btnCobrar = document.getElementById('btnCobrar');

    count.textContent = items.length;

    if (items.length === 0) {
        container.innerHTML = '';
        container.appendChild(empty);
        btnCobrar.disabled = true;
        posRecalcular();
        return;
    }

    const html = items.map(it => {
        const sub = (it.cant * it.precio).toFixed(2).replace('.', ',');
        const subtotalHtml = it.cant > 1
            ? `<span class="pos-item-subtotal">subtotal $ ${sub}</span>`
            : '';
        return `
        <div class="pos-cart-item">
            <div class="pos-cart-item-name">${escHtml(it.nombre)}</div>
            <div class="pos-qty-ctrl">
                <button class="pos-qty-btn" onclick="posQty('${it.id}', -1)">−</button>
                <span class="pos-qty-val">${it.cant}</span>
                <button class="pos-qty-btn" onclick="posQty('${it.id}', 1)">+</button>
            </div>
            <div class="pos-item-total" onclick="this.querySelector('.pos-precio-input').focus()">
                <div class="pos-precio-wrapper">
                    <span>$</span>
                    <input type="number" class="pos-precio-input" data-id="${it.id}"
                           value="${it.precio.toFixed(2)}" step="0.01" min="0"
                           onchange="posPrecioActualizar(this)"
                           onkeydown="if(event.key==='Enter')this.blur();"
                           title="Editá el precio unitario">
                    <span>c/u</span>
                </div>
                ${subtotalHtml}
            </div>
            <button class="pos-item-del" onclick="posQuitar('${it.id}')" title="Quitar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>`;
    }).join('');

    container.innerHTML = html;
    btnCobrar.disabled = false;
    posRecalcular();
}

// ── Recalcular totales ──────────────────────────────────────────────────────
function posRecalcular() {
    const items = Object.values(cart);
    const subtotal = items.reduce((acc, it) => acc + it.cant * it.precio, 0);
    const desc     = Math.max(0, Math.min(100, parseFloat(document.getElementById('descInput').value) || 0));
    const descMonto = subtotal * desc / 100;
    const total    = subtotal - descMonto;

    const fmt = v => '$ ' + v.toFixed(2).replace('.', ',');

    document.getElementById('subtotalVal').textContent  = fmt(subtotal);
    document.getElementById('descuentoVal').textContent = '- ' + fmt(descMonto);
    document.getElementById('totalVal').textContent     = fmt(total);

    const haDesc = desc > 0 && subtotal > 0;
    document.getElementById('subtotalRow').style.display  = haDesc ? '' : 'none';
    document.getElementById('descuentoRow').style.display = haDesc ? '' : 'none';
}

// ── Cambiar medio de pago ───────────────────────────────────────────────────
function posMedio(btn) {
    document.querySelectorAll('.pos-medio-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    medioActual   = btn.dataset.medio;
    tarjetaActual = 0;

    const ef   = document.getElementById('cuentaEfectivo');
    const bco  = document.getElementById('cuentaBanco');
    const tarj = document.getElementById('cuentaTarjeta');
    if (ef)   ef.classList.toggle('visible',   medioActual === 'efectivo');
    if (bco)  bco.classList.toggle('visible',  medioActual === 'transferencia');
    if (tarj) tarj.classList.toggle('visible', medioActual === 'tarjeta');
}

// ── Tabs de categorías ──────────────────────────────────────────────────────
function posTab(tab, cat) {
    document.querySelectorAll('.pos-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('#posGrid .pos-product-card').forEach(card => {
        const show = cat === '__all__' || card.dataset.cat === cat;
        card.style.display = show ? '' : 'none';
    });
    // Limpiar search
    document.getElementById('posSearch').value = '';
}

// ── Búsqueda ────────────────────────────────────────────────────────────────
function posFiltrar(q) {
    const term = q.toLowerCase().trim();
    // Desactivar tab
    document.querySelectorAll('.pos-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.pos-tab[data-cat="__all__"]').classList.add('active');
    document.querySelectorAll('#posGrid .pos-product-card').forEach(card => {
        card.style.display = (!term || card.dataset.search.includes(term)) ? '' : 'none';
    });
}

// ── Submit cobrar ───────────────────────────────────────────────────────────
function posCobrar() {
    const items = Object.values(cart);
    if (items.length === 0) return;

    const desc = parseFloat(document.getElementById('descInput').value) || 0;
    let cuentaId = 0;
    if (medioActual === 'efectivo') {
        const sel = document.getElementById('selCuentaEf');
        if (sel) cuentaId = parseInt(sel.value) || 0;
    } else if (medioActual === 'transferencia') {
        const sel = document.getElementById('selCuentaBco');
        if (sel) cuentaId = parseInt(sel.value) || 0;
    } else if (medioActual === 'tarjeta') {
        const sel = document.getElementById('selTarjeta');
        if (sel) {
            tarjetaActual = parseInt(sel.value) || 0;
            const opt = sel.options[sel.selectedIndex];
            cuentaId = parseInt(opt ? (opt.dataset.cuenta || '0') : '0') || 0;
        }
    }

    document.getElementById('fItemsJson').value  = JSON.stringify(items);
    document.getElementById('fMedio').value       = medioActual;
    document.getElementById('fCuentaId').value    = cuentaId;
    document.getElementById('fTarjetaId').value   = tarjetaActual;
    document.getElementById('fDescuento').value   = desc;

    document.getElementById('posForm').submit();
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function fmtNum(n) { return parseFloat(n).toFixed(2).replace('.', ','); }
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Mapa de productos por código (para barcode) ──────────────────────────────
const productosPorCodigo = {};
document.querySelectorAll('#posGrid .pos-product-card').forEach(function(card) {
    // data-codigo se agrega abajo en el HTML; aquí también usamos el texto del code
    var cod = card.dataset.codigo;
    if (cod) productosPorCodigo[cod.trim().toLowerCase()] = card;
});

// ── Scan de código de barras ─────────────────────────────────────────────────
function posScanBarcode() {
    var input = document.getElementById('barcodeInput');
    var raw   = (input.value || '').trim();
    input.value = '';
    input.focus();
    if (!raw) return;

    var key  = raw.toLowerCase();
    var card = productosPorCodigo[key];

    if (card) {
        if (card.classList.contains('sin-stock')) {
            posToast('Sin stock: ' + card.dataset.nombre, 'err');
        } else {
            posAgregar(card);
            posToast(card.dataset.nombre, 'ok');
        }
    } else {
        posToast('Código no encontrado: ' + raw, 'err');
    }
}

// ── Toast de feedback ────────────────────────────────────────────────────────
var _toastTimer = null;
function posToast(msg, tipo) {
    var el = document.getElementById('scanToast');
    if (!el) return;
    el.textContent = (tipo === 'ok' ? '✓ ' : '✗ ') + msg;
    el.className   = 'pos-scan-toast ' + tipo + ' show';
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(function() { el.classList.remove('show'); }, 2000);
}

// ── Fullscreen ───────────────────────────────────────────────────────────────
var _fullscreen = false;
function posToggleFullscreen() {
    _fullscreen = !_fullscreen;
    document.body.classList.toggle('pos-fullscreen', _fullscreen);
    var icon = document.querySelector('#btnFullscreen i');
    if (icon) icon.className = _fullscreen ? 'fa-solid fa-compress' : 'fa-solid fa-expand';

    // Reajustar altura del layout
    var layout = document.querySelector('.pos-layout');
    if (layout) layout.style.height = _fullscreen ? '100vh' : 'calc(100vh - 120px)';

    // Enfocar el input de barcode al entrar en fullscreen
    if (_fullscreen) setTimeout(function() {
        var bc = document.getElementById('barcodeInput');
        if (bc) bc.focus();
    }, 150);
}

// Salir de fullscreen con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && _fullscreen) posToggleFullscreen();
});

// ── Auto-foco en barcode cuando el usuario tipea fuera de inputs ─────────────
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    if (e.ctrlKey || e.altKey || e.metaKey) return;
    if (e.key.length !== 1) return;
    var bc = document.getElementById('barcodeInput');
    if (bc && document.activeElement !== bc) {
        bc.focus();
    }
});

// ── Resizer del panel carrito ────────────────────────────────────────────────
(function() {
    var MIN_W = 220, MAX_W = 620;
    var STORE_KEY = 'akim_pos_cart_width';
    var layout   = document.querySelector('.pos-layout');
    var resizer  = document.getElementById('posResizer');
    if (!layout || !resizer) return;

    // Restaurar ancho guardado
    var saved = parseInt(localStorage.getItem(STORE_KEY), 10);
    if (saved >= MIN_W && saved <= MAX_W) {
        layout.style.setProperty('--cart-width', saved + 'px');
    }

    var startX, startW;

    resizer.addEventListener('mousedown', function(e) {
        e.preventDefault();
        startX = e.clientX;
        startW = parseInt(getComputedStyle(layout).getPropertyValue('--cart-width')) || 340;
        resizer.classList.add('dragging');
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function(e) {
        if (!resizer.classList.contains('dragging')) return;
        var delta = startX - e.clientX; // mover izquierda = agrandar carrito
        var newW  = Math.min(MAX_W, Math.max(MIN_W, startW + delta));
        layout.style.setProperty('--cart-width', newW + 'px');
    });

    document.addEventListener('mouseup', function() {
        if (!resizer.classList.contains('dragging')) return;
        resizer.classList.remove('dragging');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        var cur = parseInt(getComputedStyle(layout).getPropertyValue('--cart-width')) || 340;
        try { localStorage.setItem(STORE_KEY, cur); } catch(e) {}
    });
})();
</script>

<!-- Toast de scan -->
<div id="scanToast" class="pos-scan-toast"></div>
