<div class="login-card" style="max-width:460px">

    <h1 class="login-card-title">Configuración inicial</h1>
    <p class="login-card-sub" style="color:#f59e0b">
        ⚙&nbsp; Primer uso &mdash; completá los datos para comenzar
    </p>

    <?php if (!empty($errores)): ?>
    <div class="login-errors">
        <?php foreach ($errores as $e): ?>
            <p><?php echo htmlspecialchars($e, ENT_QUOTES); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="install" class="login-form" autocomplete="off">

        <!-- Empresa -->
        <div style="border-top:1px solid rgba(255,255,255,.07);padding-top:1rem">
            <p style="font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#4b5563;margin-bottom:.85rem">Tu empresa</p>

            <div class="form-group">
                <label>Nombre de la empresa</label>
                <input type="text" name="em_nombre" required placeholder="Ej: Maderas del Sur SRL"
                       value="<?php echo htmlspecialchars($_POST['em_nombre'] ?? '', ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label>CUIT <span style="color:#4b5563;font-weight:400">(opcional)</span></label>
                <input type="text" name="em_cuit" placeholder="20-12345678-9"
                       value="<?php echo htmlspecialchars($_POST['em_cuit'] ?? '', ENT_QUOTES); ?>">
            </div>
        </div>

        <!-- Usuario admin -->
        <div style="border-top:1px solid rgba(255,255,255,.07);padding-top:1rem">
            <p style="font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#4b5563;margin-bottom:.85rem">Usuario administrador</p>

            <div class="form-group">
                <label>Nombre de usuario</label>
                <input type="text" name="username" required placeholder="ADMIN" autocomplete="off"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>"
                       style="text-transform:uppercase">
            </div>
            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" required placeholder="Nombre y apellido"
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="admin@empresa.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="Mínimo 6 caracteres" minlength="6">
            </div>
            <div class="form-group">
                <label>Repetir contraseña</label>
                <input type="password" name="password2" required placeholder="Repetí la contraseña" minlength="6">
            </div>
        </div>

        <button type="submit" class="btn-login" style="background:linear-gradient(to right,#16a34a,#15803d)">
            Crear cuenta y comenzar &rarr;
        </button>

    </form>

    <div class="login-forgot">
        <span style="color:#374151;font-size:.75rem">Esta pantalla desaparece una vez creado el primer usuario</span>
    </div>

</div>
