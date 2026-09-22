<?php
$stats = $stats ?? ['total' => 0, 'bajo_minimo' => 0, 'valor_inventario' => 0, 'categorias' => 0];
$bajo  = (int)($stats['bajo_minimo'] ?? 0);
?>

<style>
.stk-header { margin-bottom:1.75rem; }
.stk-header h1 { font-size:1.35rem; font-weight:700; color:var(--text); letter-spacing:-.025em; }
.stk-header p  { font-size:.85rem; color:var(--text-dim); margin-top:.25rem; }

.stk-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:.85rem; margin-bottom:1.75rem; }
.stk-stat  { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:.9rem 1.1rem; display:flex; align-items:center; gap:.85rem; }
.stk-stat.alert { border-color:rgba(239,68,68,.35); background:rgba(239,68,68,.06); }
.stk-stat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.stk-stat-icon--green  { background:rgba(22,163,74,.15);  color:#4ade80; }
.stk-stat-icon--red    { background:rgba(239,68,68,.18);  color:#f87171; }
.stk-stat-icon--blue   { background:rgba(37,99,235,.15);  color:#60a5fa; }
.stk-stat-icon--violet { background:rgba(139,92,246,.15); color:#c4b5fd; }
.stk-stat-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; font-weight:600; color:var(--text-dim); display:block; }
.stk-stat-value { font-size:1.15rem; font-weight:700; color:var(--text); display:block; letter-spacing:-.02em; }

.stk-nav { display:grid; grid-template-columns:repeat(4,1fr); gap:.85rem; }
.stk-nav-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.25rem 1.4rem; text-decoration:none; display:flex; align-items:center; gap:1rem; transition:border-color .15s, background .15s; }
.stk-nav-card:hover { border-color:var(--accent); background:rgba(37,99,235,.06); }
.stk-nav-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.stk-nav-icon--blue   { background:rgba(37,99,235,.15);  color:#60a5fa; }
.stk-nav-icon--green  { background:rgba(22,163,74,.15);  color:#4ade80; }
.stk-nav-icon--violet { background:rgba(139,92,246,.15); color:#c4b5fd; }
.stk-nav-title { font-size:.95rem; font-weight:600; color:var(--text); display:block; }
.stk-nav-sub   { font-size:.78rem; color:var(--text-dim); display:block; margin-top:.15rem; }

@media (max-width:900px) { .stk-stats { grid-template-columns:1fr 1fr; } .stk-nav { grid-template-columns:1fr 1fr; } }
@media (max-width:480px) { .stk-stats, .stk-nav { grid-template-columns:1fr; } }
</style>

<div class="stk-header">
    <h1>Stock</h1>
    <p>Maestro de artículos, inventario y categorías</p>
</div>

<!-- Stats -->
<div class="stk-stats">
    <div class="stk-stat">
        <div class="stk-stat-icon stk-stat-icon--green">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <div>
            <span class="stk-stat-label">Productos activos</span>
            <span class="stk-stat-value"><?php echo (int)($stats['total'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stk-stat<?php echo $bajo > 0 ? ' alert' : ''; ?>">
        <div class="stk-stat-icon stk-stat-icon--red">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <span class="stk-stat-label">Bajo mínimo</span>
            <span class="stk-stat-value"><?php echo $bajo; ?></span>
        </div>
    </div>
    <div class="stk-stat">
        <div class="stk-stat-icon stk-stat-icon--blue">
            <i class="fa-solid fa-tag"></i>
        </div>
        <div>
            <span class="stk-stat-label">Categorías</span>
            <span class="stk-stat-value"><?php echo (int)($stats['categorias'] ?? 0); ?></span>
        </div>
    </div>
    <div class="stk-stat">
        <div class="stk-stat-icon stk-stat-icon--violet">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
        <div>
            <span class="stk-stat-label">Valor inventario</span>
            <span class="stk-stat-value">$ <?php echo number_format((float)($stats['valor_inventario'] ?? 0), 0, ',', '.'); ?></span>
        </div>
    </div>
</div>

<!-- Navegación -->
<div class="stk-nav">
    <a href="stk_productos" class="stk-nav-card">
        <div class="stk-nav-icon stk-nav-icon--blue">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <div>
            <span class="stk-nav-title">Productos</span>
            <span class="stk-nav-sub">Maestro de artículos</span>
        </div>
    </a>
    <a href="stk_categorias" class="stk-nav-card">
        <div class="stk-nav-icon stk-nav-icon--green">
            <i class="fa-solid fa-tag"></i>
        </div>
        <div>
            <span class="stk-nav-title">Categorías</span>
            <span class="stk-nav-sub">Clasificación de artículos</span>
        </div>
    </a>
    <a href="stk_depositos" class="stk-nav-card">
        <div class="stk-nav-icon stk-nav-icon--violet">
            <i class="fa-solid fa-warehouse"></i>
        </div>
        <div>
            <span class="stk-nav-title">Depósitos</span>
            <span class="stk-nav-sub">Almacenes y sucursales</span>
        </div>
    </a>
    <a href="stk_productos_edit" class="stk-nav-card">
        <div class="stk-nav-icon" style="background:rgba(6,182,212,.15);color:#67e8f9">
            <i class="fa-solid fa-plus"></i>
        </div>
        <div>
            <span class="stk-nav-title">Nuevo producto</span>
            <span class="stk-nav-sub">Agregar al maestro</span>
        </div>
    </a>
    <a href="stk_ajuste" class="stk-nav-card">
        <div class="stk-nav-icon" style="background:rgba(245,158,11,.15);color:#fcd34d">
            <i class="fa-solid fa-sliders"></i>
        </div>
        <div>
            <span class="stk-nav-title">Ajuste de stock</span>
            <span class="stk-nav-sub">Correcciones de inventario</span>
        </div>
    </a>
    <a href="stk_transferencia" class="stk-nav-card">
        <div class="stk-nav-icon" style="background:rgba(236,72,153,.15);color:#f9a8d4">
            <i class="fa-solid fa-arrow-right-arrow-left"></i>
        </div>
        <div>
            <span class="stk-nav-title">Transferencia</span>
            <span class="stk-nav-sub">Entre depósitos</span>
        </div>
    </a>
    <a href="stk_movimientos" class="stk-nav-card">
        <div class="stk-nav-icon" style="background:rgba(107,114,128,.15);color:#9ca3af">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div>
            <span class="stk-nav-title">Movimientos</span>
            <span class="stk-nav-sub">Historial completo</span>
        </div>
    </a>
</div>
