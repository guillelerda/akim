<?php
$lista       = $lista       ?? [];
$flash_ok    = $flash_ok    ?? null;
$flash_error = $flash_error ?? null;

$total      = count($lista);
$habilitados = count(array_filter($lista, fn($c) => $c['habilitado']));
$con_cc      = count(array_filter($lista, fn($c) => ($c['limite_credito'] ?? 0) > 0));
?>

<style>
.cli-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.cli-header-text h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.cli-header-text p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }

.cli-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.85rem; margin-bottom:1.5rem; }
.cli-stat  { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.9rem 1.1rem; display:flex; align-items:center; gap:.85rem; }
.cli-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.cli-stat-icon--blue   { background:rgba(37,99,235,.15);  color:#60a5fa; }
.cli-stat-icon--green  { background:rgba(22,163,74,.15);  color:#4ade80; }
.cli-stat-icon--purple { background:rgba(124,58,237,.15); color:#a78bfa; }
.cli-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.cli-stat-value { font-size:1.15rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }

.cli-table-wrap { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.cli-table-header { display:flex; align-items:center; justify-content:space-between; padding:.9rem 1.25rem; border-bottom:1px solid var(--border); gap:.75rem; flex-wrap:wrap; }
.cli-table-title { font-size:.88rem; font-weight:600; color:var(--text); }

.cli-search { display:flex; align-items:center; gap:.5rem; background:rgba(31,41,55,.5); border:1px solid var(--border-med); border-radius:8px; padding:.4rem .8rem; }
.cli-search i { color:var(--text-dim); font-size:.8rem; }
.cli-search input { background:none; border:none; outline:none; color:var(--text); font-size:.84rem; font-family:inherit; width:210px; }
.cli-search input::placeholder { color:var(--text-dim); }

.cond-badge { display:inline-block; padding:.15rem .5rem; border-radius:5px; font-size:.68rem; font-weight:600; }
.cond-ri    { background:rgba(37,99,235,.12); color:#93c5fd; }
.cond-mono  { background:rgba(124,58,237,.12); color:#c4b5fd; }
.cond-exento{ background:rgba(6,182,212,.12); color:#67e8f9; }
.cond-cf    { background:rgba(107,114,128,.12); color:#9ca3af; }

.hab-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:99px; font-size:.68rem; font-weight:600; }
.hab-badge::before { content:''; width:5px; height:5px; border-radius:50%; }
.hab-si  { background:rgba(22,163,74,.12);  color:#86efac; }
.hab-si::before  { background:#4ade80; }
.hab-no  { background:rgba(107,114,128,.12); color:#9ca3af; }
.hab-no::before  { background:#6b7280; }

tr.dis-row td { opacity:.45; }

@media (max-width:768px) { .cli-stats { grid-template-columns:1fr 1fr; } }
</style>

<?php if ($flash_ok): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash_ok, ENT_QUOTES); ?>
</div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-error" style="margin-bottom:1.25rem">
    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($flash_error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="cli-header">
    <div class="cli-header-text">
        <h1>Clientes</h1>
        <p>Cartera de clientes y condiciones comerciales</p>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="ventas" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Ventas
        </a>
        <form method="POST" action="vta_clientes" style="margin:0">
            <input type="hidden" name="accion" value="nuevo">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo Cliente
            </button>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="cli-stats">
    <div class="cli-stat">
        <div class="cli-stat-icon cli-stat-icon--blue">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <span class="cli-stat-label">Total clientes</span>
            <span class="cli-stat-value"><?php echo $total; ?></span>
        </div>
    </div>
    <div class="cli-stat">
        <div class="cli-stat-icon cli-stat-icon--green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <span class="cli-stat-label">Habilitados</span>
            <span class="cli-stat-value"><?php echo $habilitados; ?></span>
        </div>
    </div>
    <div class="cli-stat">
        <div class="cli-stat-icon cli-stat-icon--purple">
            <i class="fa-solid fa-credit-card"></i>
        </div>
        <div>
            <span class="cli-stat-label">Con cuenta corriente</span>
            <span class="cli-stat-value"><?php echo $con_cc; ?></span>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="cli-table-wrap">
    <div class="cli-table-header">
        <span class="cli-table-title">
            <i class="fa-solid fa-list" style="color:var(--text-dim);margin-right:.4rem;font-size:.8rem"></i>
            Listado de clientes
        </span>
        <div class="cli-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="buscador" placeholder="Buscar por nombre, CUIT…" oninput="filtrarClientes(this.value)">
        </div>
    </div>

    <?php if (empty($lista)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-dim)">
        <i class="fa-solid fa-users" style="font-size:1.8rem;opacity:.2;display:block;margin-bottom:.75rem"></i>
        <p>No hay clientes registrados.</p>
        <form method="POST" action="vta_clientes" style="margin-top:.75rem">
            <input type="hidden" name="accion" value="nuevo">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Crear primer cliente
            </button>
        </form>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="tbl" id="tablaClientes">
            <thead>
                <tr>
                    <th>Razón social</th>
                    <th>CUIT / Doc.</th>
                    <th>Condición fiscal</th>
                    <th>Condición pago</th>
                    <th>Contacto</th>
                    <th style="text-align:right">Lím. CC</th>
                    <th style="text-align:center">Estado</th>
                    <th style="text-align:center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $c):
                $hab     = (bool)$c['habilitado'];
                $limCC   = (float)($c['limite_credito'] ?? 0);
                $condMap = [
                    'Responsable Inscripto' => 'cond-ri',
                    'Monotributista'        => 'cond-mono',
                    'Exento'                => 'cond-exento',
                    'Consumidor Final'      => 'cond-cf',
                ];
                $condClass = $condMap[$c['condicion_fiscal'] ?? ''] ?? 'cond-cf';
            ?>
                <tr class="cli-row<?php echo !$hab ? ' dis-row' : ''; ?>"
                    data-search="<?php echo strtolower(htmlspecialchars(
                        ($c['razon_social'] ?? '') . ' ' . ($c['cuit'] ?? '') . ' ' . ($c['nro_documento'] ?? ''),
                        ENT_QUOTES
                    )); ?>">
                    <td>
                        <span style="font-weight:600;font-size:.88rem">
                            <?php echo htmlspecialchars($c['razon_social'], ENT_QUOTES); ?>
                        </span>
                        <?php if ($c['localidad'] ?? ''): ?>
                        <br><span style="font-size:.74rem;color:var(--text-dim)">
                            <?php echo htmlspecialchars($c['localidad'], ENT_QUOTES); ?>
                            <?php if ($c['provincia'] ?? ''): ?>, <?php echo htmlspecialchars($c['provincia'], ENT_QUOTES); ?><?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.83rem;color:var(--text-dim)">
                        <?php if ($c['cuit'] ?? ''): ?>
                            <span style="color:var(--text)"><?php echo htmlspecialchars($c['cuit'], ENT_QUOTES); ?></span>
                        <?php elseif ($c['nro_documento'] ?? ''): ?>
                            <span style="color:var(--text-dim);font-size:.77rem"><?php echo htmlspecialchars($c['tipo_documento'] ?? 'DNI', ENT_QUOTES); ?></span>
                            <?php echo htmlspecialchars($c['nro_documento'], ENT_QUOTES); ?>
                        <?php else: ?>
                            <span style="opacity:.35">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="cond-badge <?php echo $condClass; ?>">
                            <?php echo htmlspecialchars($c['condicion_fiscal'] ?? 'Consumidor Final', ENT_QUOTES); ?>
                        </span>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-dim)">
                        <?php echo htmlspecialchars($c['condicion_pago'] ?? 'Contado', ENT_QUOTES); ?>
                    </td>
                    <td style="font-size:.82rem">
                        <?php if ($c['email'] ?? ''): ?>
                        <a href="mailto:<?php echo htmlspecialchars($c['email'], ENT_QUOTES); ?>"
                           style="color:var(--accent);text-decoration:none">
                            <?php echo htmlspecialchars($c['email'], ENT_QUOTES); ?>
                        </a><br>
                        <?php endif; ?>
                        <?php if ($c['telefono'] ?? ''): ?>
                        <span style="color:var(--text-dim)"><?php echo htmlspecialchars($c['telefono'], ENT_QUOTES); ?></span>
                        <?php endif; ?>
                        <?php if (!($c['email'] ?? '') && !($c['telefono'] ?? '')): ?>
                        <span style="opacity:.35">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;font-size:.85rem;font-weight:600;
                        color:<?php echo $limCC > 0 ? '#a78bfa' : 'var(--text-dim)'; ?>">
                        <?php echo $limCC > 0 ? '$ ' . number_format($limCC, 0, ',', '.') : '—'; ?>
                    </td>
                    <td style="text-align:center">
                        <span class="hab-badge <?php echo $hab ? 'hab-si' : 'hab-no'; ?>">
                            <?php echo $hab ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <div style="display:flex;gap:.4rem;justify-content:center">
                            <a href="vta_cliente_cuenta?id=<?php echo (int)$c['id']; ?>"
                               class="btn btn-secondary btn-sm" title="Ver cuenta corriente">
                                <i class="fa-solid fa-address-card"></i>
                            </a>
                            <form method="POST" action="vta_clientes" style="margin:0">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="idx" value="<?php echo htmlspecialchars($c['idx'], ENT_QUOTES); ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </form>
                            <form method="POST" action="vta_clientes" style="margin:0"
                                  onsubmit="return confirm('¿<?php echo $hab ? 'Deshabilitar' : 'Habilitar'; ?> este cliente?')">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                <input type="hidden" name="habilitado" value="<?php echo (int)$hab; ?>">
                                <button type="submit"
                                    class="btn btn-sm <?php echo $hab ? 'btn-danger' : 'btn-success'; ?>"
                                    title="<?php echo $hab ? 'Deshabilitar' : 'Habilitar'; ?>">
                                    <i class="fa-solid <?php echo $hab ? 'fa-ban' : 'fa-circle-check'; ?>"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filtrarClientes(q) {
    const term = q.toLowerCase().trim();
    document.querySelectorAll('#tablaClientes .cli-row').forEach(tr => {
        const haystack = tr.dataset.search || '';
        tr.style.display = (!term || haystack.includes(term)) ? '' : 'none';
    });
}
</script>
