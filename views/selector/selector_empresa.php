<?php
$errores  = $errores  ?? [];
$licencias = $licencias ?? [];
?>

<?php if ($errores): ?>
<div class="sel-errors">
    <?php foreach ($errores as $e): ?>
        <p><?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="sel-content">

<?php if (empty($licencias)): ?>
    <div style="text-align:center;padding:3rem;background:rgba(17,24,39,.7);border:1px solid rgba(255,255,255,.07);border-radius:16px;color:#6b7280;">
        <div style="font-size:2.5rem;margin-bottom:1rem;">🏢</div>
        <p style="font-size:1rem;margin-bottom:.5rem;color:#d1d5db;">No tenés empresas asignadas</p>
        <p style="font-size:.83rem;">Pedile al administrador que te asigne una licencia.</p>
    </div>
<?php else: ?>
    <form method="POST" action="seleccionarEmpresa" id="sel-form">
        <div class="sel-grid">
            <?php foreach ($licencias as $lic):
                $em_nombre = $lic['em_nombre'] ?? 'Empresa sin nombre';
                $em_slogan = $lic['em_slogan'] ?? '';
                $lic_code  = $lic['lic_code']  ?? '';
            ?>
            <?php
                $sin_bd = empty($lic['bd_name']);
                $em_nombre_dec = $em_nombre;
                if ($em_nombre && strlen($em_nombre) > 40) {
                    $dec = openssl_decrypt($em_nombre, SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
                    if ($dec !== false) $em_nombre_dec = $dec;
                }
            ?>
            <label class="sel-card<?php echo $sin_bd ? ' sel-card--nobd' : ''; ?>"
                   for="lic-<?php echo htmlspecialchars($lic_code, ENT_QUOTES); ?>"
                   <?php if ($sin_bd) echo 'title="Sin base de datos configurada"'; ?>>
                <input type="radio" name="lic_code" id="lic-<?php echo htmlspecialchars($lic_code, ENT_QUOTES); ?>"
                       value="<?php echo htmlspecialchars($lic_code, ENT_QUOTES); ?>"
                       class="sel-radio" required
                       <?php if ($sin_bd) echo 'disabled'; ?>>

                <div class="sel-card-avatar">
                    <?php echo mb_strtoupper(mb_substr($em_nombre_dec, 0, 2)); ?>
                </div>

                <div class="sel-card-body">
                    <span class="sel-card-name"><?php echo htmlspecialchars($em_nombre_dec, ENT_QUOTES); ?></span>
                    <?php if ($em_slogan): ?>
                        <span class="sel-card-slogan"><?php echo htmlspecialchars($em_slogan, ENT_QUOTES); ?></span>
                    <?php endif; ?>
                    <span class="sel-card-code"><?php echo htmlspecialchars($lic_code, ENT_QUOTES); ?></span>
                    <?php if ($sin_bd): ?>
                        <span class="sel-card-badge">Sin BD</span>
                    <?php endif; ?>
                </div>

                <div class="sel-card-check">✓</div>
            </label>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:2rem;">
            <button type="submit" class="sel-btn-ingresar">
                Ingresar a la empresa seleccionada
            </button>
        </div>
    </form>

    <?php if (count($licencias) === 1): ?>
    <script>
        // Si hay una sola empresa, seleccionarla automáticamente y enviar
        document.addEventListener('DOMContentLoaded', function () {
            var radios = document.querySelectorAll('.sel-radio:not([disabled])');
            if (radios.length === 1) {
                radios[0].checked = true;
                document.getElementById('sel-form').submit();
            }
        });
    </script>
    <?php endif; ?>
<?php endif; ?>

</div>

<style>
.sel-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}

.sel-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1rem;
    background: rgba(17,24,39,.8);
    border: 2px solid rgba(255,255,255,.07);
    border-radius: 14px;
    cursor: pointer;
    transition: border-color .2s, background .2s, box-shadow .2s;
    user-select: none;
}

.sel-card:hover {
    border-color: rgba(59,130,246,.4);
    background: rgba(30,40,60,.8);
}

.sel-radio { position: absolute; opacity: 0; width: 0; height: 0; }

.sel-radio:checked ~ .sel-card-check {
    opacity: 1;
    background: #2563eb;
    border-color: #2563eb;
}

/* Highlight card when radio is checked */
.sel-card:has(.sel-radio:checked) {
    border-color: #2563eb;
    background: rgba(37,99,235,.12);
    box-shadow: 0 0 0 1px rgba(37,99,235,.3), 0 0 24px rgba(37,99,235,.12);
}

.sel-card-avatar {
    flex-shrink: 0;
    width: 48px; height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1e3a8a, #1e40af);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    color: #93c5fd;
    letter-spacing: -.02em;
}

.sel-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: .2rem;
    min-width: 0;
}

.sel-card-name {
    font-size: .95rem;
    font-weight: 600;
    color: #f3f4f6;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sel-card-slogan {
    font-size: .78rem;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sel-card-code {
    font-size: .7rem;
    color: #374151;
    font-family: monospace;
    letter-spacing: .05em;
}

.sel-card-check {
    flex-shrink: 0;
    width: 22px; height: 22px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .7rem;
    color: #fff;
    opacity: 0;
    transition: opacity .15s, background .15s, border-color .15s;
}

.sel-btn-ingresar {
    padding: .85rem 2.5rem;
    background: linear-gradient(to right, #2563eb, #1d4ed8);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: opacity .2s, box-shadow .2s;
    box-shadow: 0 4px 16px rgba(37,99,235,.35);
    letter-spacing: .01em;
}

.sel-btn-ingresar:hover { opacity: .9; box-shadow: 0 6px 24px rgba(37,99,235,.5); }

.sel-card--nobd {
    opacity: .5;
    cursor: not-allowed;
}

.sel-card--nobd:hover {
    border-color: rgba(255,255,255,.07);
    background: rgba(17,24,39,.8);
}

.sel-card-badge {
    display: inline-block;
    font-size: .65rem;
    font-weight: 600;
    padding: .15rem .45rem;
    border-radius: 4px;
    background: rgba(239,68,68,.15);
    color: #fca5a5;
    border: 1px solid rgba(239,68,68,.2);
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-top: .15rem;
}
</style>
