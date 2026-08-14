<header class="akim-header">
    <div class="akim-header-brand">
        <a href="menu"><strong>AKIM</strong></a>
    </div>
    <nav class="akim-header-nav">
        <a href="ventas"  title="Ventas"><i class="fa-solid fa-cash-register"></i></a>
        <a href="compras" title="Compras"><i class="fa-solid fa-shopping-cart"></i></a>
        <a href="stock"   title="Stock"><i class="fa-solid fa-boxes-stacked"></i></a>
        <a href="tienda"  title="Tienda"><i class="fa-solid fa-store"></i></a>
    </nav>
    <div class="akim-header-user">
        <span><?php echo htmlspecialchars($nombre ?? '', ENT_QUOTES); ?></span>
        <a href="logout" title="Salir"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</header>
