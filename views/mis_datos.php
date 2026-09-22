<?php
$params      = $params      ?? [];
$tab         = $tab         ?? 'empresa';
$logo_exists = $logo_exists ?? false;
$email       = $email       ?? '';
?>

<style>
/* ── Encabezado y tabs ── */
.md-header { margin-bottom:1.5rem; }
.md-header h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.md-header p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }

.md-tabs { display:flex; gap:.25rem; border-bottom:1px solid var(--border); margin-bottom:1.75rem; }
.md-tab  { padding:.55rem 1rem; font-size:.84rem; font-weight:600; color:var(--text-dim);
           border-bottom:2px solid transparent; cursor:pointer; text-decoration:none;
           transition:color .15s, border-color .15s; }
.md-tab:hover  { color:var(--text); }
.md-tab.active { color:var(--accent-lt); border-bottom-color:var(--accent-lt); }

/* ── Cards ── */
.md-card  { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.25rem; }
.md-card-title { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.09em;
                 color:var(--text-dim); margin-bottom:1.1rem; display:flex; align-items:center; gap:.5rem; }

/* ── Grids ── */
.md-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }
.md-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.85rem; }

/* ── Form groups e inputs ── */
.akim-form-group { display:flex; flex-direction:column; gap:.35rem; }
.akim-form-group label {
    font-size:.72rem; font-weight:600; text-transform:uppercase;
    letter-spacing:.07em; color:var(--text-dim);
}
.akim-input {
    width:100%; padding:.55rem .8rem;
    background:var(--surface-2);
    border:1px solid var(--border);
    border-radius:var(--radius);
    color:var(--text);
    font-size:.875rem;
    font-family:inherit;
    transition:border-color .15s, box-shadow .15s;
    outline:none;
    appearance:none;
}
.akim-input::placeholder { color:var(--text-dim); opacity:.6; }
.akim-input:focus {
    border-color:var(--accent-lt);
    box-shadow:0 0 0 3px rgba(99,102,241,.15);
}
.akim-input:hover:not(:focus) { border-color:var(--border-med,#4b5563); }

/* ── Logo ── */
.md-logo-preview { width:120px; height:80px; object-fit:contain; border:1px solid var(--border);
                   border-radius:8px; background:var(--surface-2); padding:.5rem; }
.md-logo-placeholder { width:120px; height:80px; border:1px dashed var(--border-med,#4b5563);
                       border-radius:8px; background:var(--surface-2); display:flex;
                       align-items:center; justify-content:center;
                       font-size:.72rem; color:var(--text-dim); text-align:center; }
.md-logo-area { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }

/* ── Info de cuenta (read-only) ── */
.md-info-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.08em;
                 color:var(--text-dim); margin-bottom:.3rem; }
.md-info-val   { font-size:.95rem; font-weight:600; color:var(--text); }

@media(max-width:640px){
    .md-grid-2, .md-grid-3 { grid-template-columns:1fr; }
}
</style>

<!-- Flash -->
<?php if (!empty($flash_ok)): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<div class="md-header">
    <h1><i class="fa-solid fa-id-card" style="color:var(--accent-lt)"></i> Mis Datos</h1>
    <p>Configurá los datos de tu empresa y tu cuenta de acceso.</p>
</div>

<!-- Tabs -->
<div class="md-tabs">
    <a href="mis_datos?tab=empresa" class="md-tab <?php echo $tab === 'empresa' ? 'active' : ''; ?>">
        <i class="fa-solid fa-building"></i> Mi Empresa
    </a>
    <a href="mis_datos?tab=cuenta" class="md-tab <?php echo $tab === 'cuenta' ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-shield"></i> Mi Cuenta
    </a>
</div>

<?php if ($tab === 'empresa'): ?>
<!-- ══════════════════════════════════════════════════════════════
     TAB: MI EMPRESA
══════════════════════════════════════════════════════════════ -->

<form method="POST" action="mis_datos" enctype="multipart/form-data">
    <input type="hidden" name="accion" value="empresa">

    <!-- Identidad -->
    <div class="md-card">
        <div class="md-card-title">
            <i class="fa-solid fa-file-signature"></i> Identidad fiscal
        </div>

        <div class="akim-form-group" style="margin-bottom:.85rem">
            <label>Razón social / Nombre del emisor</label>
            <input type="text" name="empresa_razon_social" class="akim-input"
                   value="<?php echo htmlspecialchars($params['empresa_razon_social'] ?? '', ENT_QUOTES); ?>"
                   placeholder="Ej: Ing. Juan García o García S.R.L.">
        </div>

        <div class="md-grid-2">
            <div class="akim-form-group">
                <label>Inicio de actividad</label>
                <input type="text" name="empresa_inicio_actividad" class="akim-input"
                       value="<?php echo htmlspecialchars($params['empresa_inicio_actividad'] ?? '', ENT_QUOTES); ?>"
                       placeholder="dd/mm/aaaa">
            </div>
            <div class="akim-form-group">
                <label>IIBB (nro. o condición)</label>
                <input type="text" name="empresa_iibb" class="akim-input"
                       value="<?php echo htmlspecialchars($params['empresa_iibb'] ?? '', ENT_QUOTES); ?>"
                       placeholder="Exento / CM 123456 / etc.">
            </div>
        </div>
    </div>

    <!-- Domicilio -->
    <div class="md-card">
        <div class="md-card-title">
            <i class="fa-solid fa-location-dot"></i> Domicilio fiscal
        </div>

        <div class="akim-form-group" style="margin-bottom:.85rem">
            <label>Domicilio</label>
            <input type="text" name="empresa_domicilio" class="akim-input"
                   value="<?php echo htmlspecialchars($params['empresa_domicilio'] ?? '', ENT_QUOTES); ?>"
                   placeholder="Calle, número">
        </div>

        <div class="md-grid-3">
            <div class="akim-form-group">
                <label>Localidad</label>
                <input type="text" name="empresa_localidad" class="akim-input"
                       value="<?php echo htmlspecialchars($params['empresa_localidad'] ?? '', ENT_QUOTES); ?>"
                       placeholder="Ciudad / localidad">
            </div>
            <div class="akim-form-group">
                <label>Provincia</label>
                <input type="text" name="empresa_provincia" class="akim-input"
                       value="<?php echo htmlspecialchars($params['empresa_provincia'] ?? '', ENT_QUOTES); ?>"
                       placeholder="Córdoba">
            </div>
            <div class="akim-form-group">
                <label>Teléfono</label>
                <input type="text" name="empresa_telefono" class="akim-input"
                       value="<?php echo htmlspecialchars($params['empresa_telefono'] ?? '', ENT_QUOTES); ?>"
                       placeholder="+54 351 ...">
            </div>
        </div>

        <div class="akim-form-group">
            <label>Email</label>
            <input type="email" name="empresa_email" class="akim-input"
                   value="<?php echo htmlspecialchars($params['empresa_email'] ?? '', ENT_QUOTES); ?>"
                   placeholder="contacto@empresa.com">
        </div>
    </div>

    <!-- Logo -->
    <div class="md-card">
        <div class="md-card-title">
            <i class="fa-solid fa-image"></i> Logo de la empresa
        </div>
        <p style="font-size:.8rem;color:var(--text-dim);margin-bottom:1rem">
            Se usará en facturas, remitos y reportes. Formatos: PNG, JPG, SVG, WEBP. Recomendado: fondo transparente (PNG).
        </p>
        <div class="md-logo-area">
            <?php if ($logo_exists): ?>
            <img src="<?php echo htmlspecialchars($params['empresa_logo'], ENT_QUOTES); ?>"
                 class="md-logo-preview" alt="Logo actual" id="logo-preview">
            <?php else: ?>
            <div class="md-logo-placeholder" id="logo-placeholder">
                <div><i class="fa-solid fa-image" style="font-size:1.5rem;display:block;margin-bottom:.3rem"></i>Sin logo</div>
            </div>
            <img src="" class="md-logo-preview" alt="Preview" id="logo-preview" style="display:none">
            <?php endif; ?>
            <div>
                <label class="btn btn-secondary" style="cursor:pointer">
                    <i class="fa-solid fa-upload"></i> Subir logo
                    <input type="file" name="empresa_logo" accept=".png,.jpg,.jpeg,.svg,.webp"
                           style="display:none" onchange="previewLogo(this)">
                </label>
                <p style="font-size:.72rem;color:var(--text-dim);margin-top:.5rem">
                    Máx. 2MB. El logo reemplaza al anterior al guardar.
                </p>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar datos de empresa
        </button>
    </div>
</form>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════
     TAB: MI CUENTA
══════════════════════════════════════════════════════════════ -->

<div class="md-card">
    <div class="md-card-title">
        <i class="fa-solid fa-circle-info"></i> Información de acceso
    </div>
    <div class="md-grid-2">
        <div>
            <div class="md-info-label">Usuario</div>
            <div class="md-info-val"><?php echo htmlspecialchars($username ?? '', ENT_QUOTES); ?></div>
        </div>
        <div>
            <div class="md-info-label">Email</div>
            <div class="md-info-val" style="font-weight:400"><?php echo htmlspecialchars($email, ENT_QUOTES) ?: '—'; ?></div>
        </div>
        <div>
            <div class="md-info-label">Perfil</div>
            <div class="md-info-val" style="font-weight:400"><?php echo ucfirst(htmlspecialchars($perfil ?? '', ENT_QUOTES)); ?></div>
        </div>
    </div>
</div>

<div class="md-card">
    <div class="md-card-title">
        <i class="fa-solid fa-lock"></i> Cambiar contraseña
    </div>
    <form method="POST" action="mis_datos?tab=cuenta">
        <input type="hidden" name="accion" value="password">
        <div class="akim-form-group" style="margin-bottom:.85rem">
            <label>Contraseña actual</label>
            <input type="password" name="pwd_actual" class="akim-input" required
                   placeholder="Ingresá tu contraseña actual">
        </div>
        <div class="md-grid-2">
            <div class="akim-form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="pwd_nueva" class="akim-input" required
                       placeholder="Mínimo 6 caracteres">
            </div>
            <div class="akim-form-group">
                <label>Confirmar nueva contraseña</label>
                <input type="password" name="pwd_confirmar" class="akim-input" required
                       placeholder="Repetí la nueva contraseña">
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-key"></i> Actualizar contraseña
            </button>
        </div>
    </form>
</div>

<?php endif; ?>

<script>
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = document.getElementById('logo-preview');
        var ph  = document.getElementById('logo-placeholder');
        if (img) { img.src = e.target.result; img.style.display = ''; }
        if (ph)  { ph.style.display = 'none'; }
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
