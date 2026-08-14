<div class="login-card">

    <h1 class="login-card-title">Iniciar sesión</h1>
    <p class="login-card-sub">Ingresá tus credenciales para continuar</p>

    <?php if (!empty($errores)): ?>
    <div class="login-errors">
        <?php foreach ($errores as $e): ?>
            <p><?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login" class="login-form" autocomplete="off">

        <div class="form-group">
            <label for="username">Usuario</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>"
                placeholder="Tu nombre de usuario"
                autocomplete="username"
                autofocus
                required
                style="text-transform:uppercase"
            >
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit" class="btn-login">
            Ingresar &rarr;
        </button>

    </form>

    <div class="login-forgot">
        <a href="#">¿Olvidaste tu contraseña?</a>
    </div>

</div>
