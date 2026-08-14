<?php
$stats_mes   = $stats_mes   ?? ['cant' => 0, 'total' => 0];
$pendientes  = $pendientes  ?? 0;
$proveedores = $proveedores ?? 0;
?>

<style>
.cpa-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.cpa-header-text h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.cpa-header-text p  { font-size:.83rem; color:var(--text-dim); margin-top:.2rem; }

.cpa-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .85rem;
    margin-bottom: 1.75rem;
}

.cpa-stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: .85rem;
    text-decoration: none;
    transition: border-color var(--transition), background var(--transition);
}

.cpa-stat-card:hover { border-color: var(--border-med); background: var(--surface-2); }

.cpa-stat-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.cpa-stat-icon--blue   { background: rgba(37,99,235,.15);  color: #60a5fa; }
.cpa-stat-icon--amber  { background: rgba(217,119,6,.15);  color: #fcd34d; }
.cpa-stat-icon--purple { background: rgba(124,58,237,.15); color: #a78bfa; }

.cpa-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.cpa-stat-value { font-size:1.2rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }

.cpa-nav { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem; }

.cpa-nav-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: border-color var(--transition), background var(--transition), transform var(--transition);
}

.cpa-nav-card:hover { border-color: rgba(37,99,235,.35); background: rgba(37,99,235,.06); transform: translateY(-1px); }

.cpa-nav-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: rgba(37,99,235,.12);
    display: flex; align-items: center; justify-content: center;
    color: #60a5fa;
    font-size: .95rem;
    flex-shrink: 0;
}

.cpa-nav-text strong { display:block; font-size:.88rem; font-weight:600; color:var(--text); }
.cpa-nav-text span   { display:block; font-size:.73rem; color:var(--text-dim); margin-top:.1rem; }

@media (max-width:768px) {
    .cpa-stats { grid-template-columns:1fr 1fr; }
    .cpa-header { flex-direction:column; }
}
</style>

<!-- Header -->
<div class="cpa-header">
    <div class="cpa-header-text">
        <h1>Compras</h1>
        <p>Gestión de proveedores y facturas de compra</p>
    </div>
    <a href="cpa_facturas_nueva" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Nueva Factura
    </a>
</div>

<!-- Stats -->
<div class="cpa-stats">
    <div class="cpa-stat-card">
        <div class="cpa-stat-icon cpa-stat-icon--blue">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
            <span class="cpa-stat-label">Facturas este mes</span>
            <span class="cpa-stat-value"><?php echo (int)$stats_mes['cant']; ?></span>
        </div>
    </div>
    <div class="cpa-stat-card">
        <div class="cpa-stat-icon cpa-stat-icon--amber">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div>
            <span class="cpa-stat-label">Pendientes de pago</span>
            <span class="cpa-stat-value"><?php echo (int)$pendientes; ?></span>
        </div>
    </div>
    <div class="cpa-stat-card">
        <div class="cpa-stat-icon cpa-stat-icon--purple">
            <i class="fa-solid fa-truck"></i>
        </div>
        <div>
            <span class="cpa-stat-label">Proveedores activos</span>
            <span class="cpa-stat-value"><?php echo (int)$proveedores; ?></span>
        </div>
    </div>
</div>

<!-- Navegación -->
<div class="cpa-nav">
    <a href="cpa_facturas" class="cpa-nav-card">
        <div class="cpa-nav-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="cpa-nav-text">
            <strong>Facturas de compra</strong>
            <span>Ver y gestionar comprobantes</span>
        </div>
    </a>
    <a href="cpa_facturas_nueva" class="cpa-nav-card">
        <div class="cpa-nav-icon"><i class="fa-solid fa-plus"></i></div>
        <div class="cpa-nav-text">
            <strong>Nueva factura</strong>
            <span>Registrar comprobante</span>
        </div>
    </a>
    <a href="cpa_proveedores" class="cpa-nav-card">
        <div class="cpa-nav-icon"><i class="fa-solid fa-truck"></i></div>
        <div class="cpa-nav-text">
            <strong>Proveedores</strong>
            <span>ABM de proveedores</span>
        </div>
    </a>
</div>
