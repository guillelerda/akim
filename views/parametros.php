<?php
$params       = $params       ?? [];
$flash_ok     = $flash_ok     ?? null;
$flash_error  = $flash_error  ?? null;
$arca_cert_ok = $arca_cert_ok ?? false;

$p          = fn(string $k, string $d = '') => $params[$k] ?? $d;
$depositos  = $depositos ?? [];

$condiciones_fiscales = ['Responsable Inscripto', 'Monotributista', 'Exento', 'Consumidor Final', 'No Responsable', 'Sujeto No Categorizado'];
$alicuotas_iva        = ['0' => '0 % (Exento)', '10.5' => '10.5 %', '21' => '21 %', '27' => '27 %'];
$condiciones_iva      = ['Gravado', 'No Gravado', 'Exento'];
$condiciones_pago     = ['Contado', '15 días', '30 días', '45 días', '60 días', '90 días'];
$arca_emisores        = ['RI' => 'Responsable Inscripto', 'MT' => 'Monotributista', 'EX' => 'Exento'];
?>

<style>
.prm-wrap { max-width: 760px; }
.prm-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; }
.prm-header h1 { font-size:1.25rem; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.section-sep { font-size:.65rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--text-dim); padding:.5rem 0 .25rem; border-bottom:1px solid var(--border); margin:1.25rem 0 .75rem; }
.section-sep i { margin-right:.4rem; }
.prm-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.prm-hint { font-size:.75rem; color:var(--text-dim); margin-top:.3rem; }
.prm-logo-row { display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap; }
.prm-logo-preview { display:flex; flex-direction:column; align-items:center; gap:.25rem; }
.prm-logo-preview img { max-height:64px; max-width:160px; border:1px solid var(--border); border-radius:6px; padding:4px; background:#fff; }
@media (max-width:600px) { .prm-grid-2 { grid-template-columns:1fr; } }
</style>

<div class="prm-wrap">

    <div class="prm-header">
        <h1><i class="fa-solid fa-sliders" style="color:var(--accent);margin-right:.5rem"></i>Parámetros del sistema</h1>
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

    <form method="POST" action="parametros" enctype="multipart/form-data">
        <div class="card">
            <div class="card-body">

                <!-- ── Datos de mi empresa ── -->
                <div class="section-sep"><i class="fa-solid fa-building"></i>Datos de mi empresa</div>
                <p class="prm-hint" style="margin-bottom:.85rem">Estos datos aparecen en el encabezado de facturas y comprobantes.</p>

                <!-- Logo -->
                <div class="form-group prm-logo-wrap">
                    <label>Logo de la empresa</label>
                    <div class="prm-logo-row">
                        <?php if (!empty($params['empresa_logo'])): ?>
                        <?php
                            $logo_prev_abs = dirname(__DIR__) . '/' . ltrim($params['empresa_logo'], '/\\');
                            $logo_prev_ok  = file_exists($logo_prev_abs);
                        ?>
                        <?php if ($logo_prev_ok): ?>
                        <div class="prm-logo-preview">
                            <img src="<?php echo htmlspecialchars($params['empresa_logo'], ENT_QUOTES); ?>?v=<?php echo filemtime($logo_prev_abs); ?>"
                                 alt="Logo actual">
                            <span class="prm-hint">Logo actual</span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <div style="flex:1">
                            <input type="file" name="empresa_logo" class="form-control"
                                   accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
                            <p class="prm-hint">PNG, JPG o SVG. Reemplaza el existente. Recomendado: fondo transparente, ancho ≤ 400px.</p>
                        </div>
                    </div>
                </div>

                <div class="prm-grid-2">
                    <div class="form-group">
                        <label>Razón social / Nombre</label>
                        <input type="text" name="empresa_razon_social" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_razon_social'), ENT_QUOTES); ?>"
                               placeholder="Ing. Guillermo A. Lerda">
                    </div>
                    <div class="form-group">
                        <label>Eslogan / Actividad</label>
                        <input type="text" name="empresa_slogan" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_slogan'), ENT_QUOTES); ?>"
                               placeholder="Ej: Instalación y service de sistemas">
                    </div>
                </div>

                <div class="form-group">
                    <label>Domicilio fiscal</label>
                    <input type="text" name="empresa_domicilio" class="form-control"
                           value="<?php echo htmlspecialchars($p('empresa_domicilio'), ENT_QUOTES); ?>"
                           placeholder="Silva Nro 48">
                </div>

                <div class="prm-grid-2">
                    <div class="form-group">
                        <label>Localidad</label>
                        <input type="text" name="empresa_localidad" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_localidad'), ENT_QUOTES); ?>"
                               placeholder="Villa Carlos Paz">
                    </div>
                    <div class="form-group">
                        <label>Provincia</label>
                        <input type="text" name="empresa_provincia" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_provincia'), ENT_QUOTES); ?>"
                               placeholder="Córdoba">
                    </div>
                </div>

                <div class="prm-grid-2">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="empresa_telefono" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_telefono'), ENT_QUOTES); ?>"
                               placeholder="351 000-0000">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="empresa_email" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_email'), ENT_QUOTES); ?>"
                               placeholder="contacto@empresa.com.ar">
                    </div>
                </div>

                <div class="prm-grid-2">
                    <div class="form-group">
                        <label>IIBB</label>
                        <input type="text" name="empresa_iibb" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_iibb'), ENT_QUOTES); ?>"
                               placeholder="Exento / 123456789">
                    </div>
                    <div class="form-group">
                        <label>Inicio de actividad</label>
                        <input type="text" name="empresa_inicio_actividad" class="form-control"
                               value="<?php echo htmlspecialchars($p('empresa_inicio_actividad'), ENT_QUOTES); ?>"
                               placeholder="20/01/2002">
                    </div>
                </div>

                <!-- Proveedores -->
                <div class="section-sep"><i class="fa-solid fa-truck"></i>Proveedores</div>
                <div class="form-group">
                    <label>Condición fiscal por defecto</label>
                    <select name="prv_condicion_fiscal" class="form-control">
                        <?php foreach ($condiciones_fiscales as $cf): ?>
                        <option value="<?php echo $cf; ?>" <?php echo $p('prv_condicion_fiscal', 'Responsable Inscripto') === $cf ? 'selected' : ''; ?>>
                            <?php echo $cf; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="prm-hint">Se aplica al auto-registrar un proveedor nuevo desde una factura de compra.</p>
                </div>

                <!-- Productos -->
                <div class="section-sep"><i class="fa-solid fa-boxes-stacked"></i>Productos / Stock</div>
                <div class="prm-grid-2">
                    <div class="form-group">
                        <label>Alícuota IVA por defecto</label>
                        <select name="prd_alicuota_iva" class="form-control">
                            <?php foreach ($alicuotas_iva as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $p('prd_alicuota_iva', '21') === (string)$val ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Condición IVA por defecto</label>
                        <select name="prd_condicion_iva" class="form-control">
                            <?php foreach ($condiciones_iva as $ci): ?>
                            <option value="<?php echo $ci; ?>" <?php echo $p('prd_condicion_iva', 'Gravado') === $ci ? 'selected' : ''; ?>>
                                <?php echo $ci; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="prm-hint" style="margin-top:0">Se aplica al auto-registrar un artículo nuevo desde una factura de compra.</p>

                <!-- Stock / Depósitos -->
                <div class="section-sep"><i class="fa-solid fa-warehouse"></i>Stock / Depósitos</div>
                <div class="form-group" style="max-width:320px">
                    <label>Depósito por defecto para compras</label>
                    <select name="stk_deposito_default" class="form-control">
                        <option value="">— Ninguno / usar el marcado como default —</option>
                        <?php foreach ($depositos as $dep): ?>
                        <option value="<?php echo (int)$dep['id']; ?>"
                                <?php echo $p('stk_deposito_default') === (string)$dep['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['nombre'], ENT_QUOTES); ?>
                            <?php echo $dep['es_default'] ? '★' : ''; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="prm-hint">Depósito donde se registra el ingreso de stock al cargar facturas de compra.</p>
                </div>

                <div class="form-group" style="margin-top:.75rem">
                    <label style="display:flex;align-items:center;gap:.65rem;cursor:pointer;user-select:none">
                        <input type="hidden"   name="stk_permite_negativo" value="0">
                        <input type="checkbox" name="stk_permite_negativo" value="1"
                               id="stk_permite_negativo"
                               <?php echo $p('stk_permite_negativo', '1') === '1' ? 'checked' : ''; ?>>
                        <span>Permitir vender con stock en cero o negativo</span>
                    </label>
                    <p class="prm-hint" style="margin-left:1.5rem">
                        Si está desactivado, el sistema bloquea la venta de productos sin stock disponible
                        tanto en la Tienda POS como en el wizard de ventas.
                    </p>
                </div>

                <!-- Compras -->
                <div class="section-sep"><i class="fa-solid fa-cart-shopping"></i>Compras</div>
                <div class="form-group" style="max-width:280px">
                    <label>Condición de pago por defecto</label>
                    <select name="cpa_condicion_pago" class="form-control">
                        <?php foreach ($condiciones_pago as $cp): ?>
                        <option value="<?php echo $cp; ?>" <?php echo $p('cpa_condicion_pago', 'Contado') === $cp ? 'selected' : ''; ?>>
                            <?php echo $cp; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="prm-hint">Se pre-selecciona al cargar una nueva factura de compra.</p>
                </div>

                <!-- ARCA / Facturación electrónica -->
                <div class="section-sep"><i class="fa-solid fa-file-invoice-dollar"></i>ARCA / Facturación electrónica</div>

                <div class="form-group">
                    <label class="arca-toggle-label">
                        <input type="checkbox" name="arca_habilitado" id="arca_habilitado" value="1"
                               <?php echo $p('arca_habilitado') === '1' ? 'checked' : ''; ?>
                               onchange="arcaToggle(this.checked)">
                        Habilitar facturación electrónica AFIP
                    </label>
                </div>

                <div id="arca_campos" style="<?php echo $p('arca_habilitado') !== '1' ? 'display:none' : ''; ?>">
                    <div class="prm-grid-2">
                        <div class="form-group">
                            <label>CUIT del emisor</label>
                            <input type="text" name="arca_cuit" class="form-control"
                                   value="<?php echo htmlspecialchars($p('arca_cuit'), ENT_QUOTES); ?>"
                                   placeholder="20123456789" maxlength="11">
                            <p class="prm-hint">11 dígitos sin guiones.</p>
                        </div>
                        <div class="form-group">
                            <label>Entorno</label>
                            <select name="arca_modo" class="form-control">
                                <option value="0" <?php echo $p('arca_modo', '0') === '0' ? 'selected' : ''; ?>>Homologación (pruebas)</option>
                                <option value="1" <?php echo $p('arca_modo') === '1' ? 'selected' : ''; ?>>Producción</option>
                            </select>
                        </div>
                    </div>
                    <div class="prm-grid-2">
                        <div class="form-group">
                            <label>Punto de venta AFIP</label>
                            <input type="number" name="arca_pv" class="form-control"
                                   value="<?php echo (int)$p('arca_pv', '1'); ?>"
                                   min="1" max="9999" style="max-width:120px">
                            <p class="prm-hint">Número de 4 dígitos habilitado en AFIP.</p>
                        </div>
                        <div class="form-group">
                            <label>Condición fiscal del emisor</label>
                            <select name="arca_condicion_fiscal" class="form-control">
                                <?php foreach ($arca_emisores as $code => $label): ?>
                                <option value="<?php echo $code; ?>"
                                        <?php echo $p('arca_condicion_fiscal', 'RI') === $code ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="section-sep" style="font-size:.6rem;margin-top:.5rem">Certificados digitales</div>

                    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem">
                        <?php if ($arca_cert_ok): ?>
                        <span class="badge badge-ok"><i class="fa-solid fa-circle-check"></i> Certificados cargados</span>
                        <?php else: ?>
                        <span class="badge badge-warn"><i class="fa-solid fa-triangle-exclamation"></i> Sin certificados</span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-secondary" id="btn_test_arca" onclick="arcaTest()">
                            <i class="fa-solid fa-plug" id="ico_test"></i> Test conexión AFIP
                        </button>
                        <span id="arca_test_result" style="font-size:.8rem"></span>
                    </div>

                    <div class="prm-grid-2">
                        <div class="form-group">
                            <label>Certificado x.509 <span class="prm-hint" style="display:inline">(certificado.pem)</span></label>
                            <input type="file" name="arca_cert" class="form-control" accept=".pem,.crt,.cer">
                            <p class="prm-hint">Subir solo si cambió. Reemplaza el existente.</p>
                        </div>
                        <div class="form-group">
                            <label>Clave privada <span class="prm-hint" style="display:inline">(privada)</span></label>
                            <input type="file" name="arca_privada" class="form-control">
                            <p class="prm-hint">Archivo sin extensión. Reemplaza el existente.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div style="margin-top:1.25rem">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar parámetros
            </button>
        </div>
    </form>
</div>

<style>
.arca-toggle-label { display:flex; align-items:center; gap:.5rem; cursor:pointer; font-weight:500; }
.arca-toggle-label input[type=checkbox] { width:1rem; height:1rem; }
.badge { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .6rem; border-radius:.4rem; font-size:.72rem; font-weight:600; }
.badge-ok   { background:rgba(34,197,94,.15); color:#16a34a; }
.badge-warn { background:rgba(234,179,8,.15);  color:#b45309; }
</style>

<script>
function arcaToggle(on) {
    document.getElementById('arca_campos').style.display = on ? '' : 'none';
}
function arcaTest() {
    const btn = document.getElementById('btn_test_arca');
    const ico = document.getElementById('ico_test');
    const res = document.getElementById('arca_test_result');
    btn.disabled = true;
    ico.className = 'fa-solid fa-spinner fa-spin';
    res.textContent = '';
    fetch('arca_test', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            ico.className = data.ok ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
            res.style.color = data.ok ? '#16a34a' : '#dc2626';
            res.textContent = data.msg;
        })
        .catch(() => {
            ico.className = 'fa-solid fa-circle-xmark';
            res.style.color = '#dc2626';
            res.textContent = 'Error de red.';
        })
        .finally(() => { btn.disabled = false; });
}
</script>
