<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        header('Location: install.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: install.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MySan - Iniciar Sesión</title>

    <!-- Offline Styles -->
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        .login-container {
            min-height: 100vh;
            display: block;
            padding: var(--space-4);
            box-sizing: border-box;
            position: relative;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            padding: var(--space-8);
            margin: 60px auto;
            box-shadow: var(--shadow-lg), var(--shadow-glow-violeta);
            animation: float-static 3s ease-in-out infinite;
        }

        @keyframes float-static {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-logo {
            text-align: center;
            margin-bottom: var(--space-8);
        }

        .logo-mark {
            width: 56px;
            height: 56px;
            margin: 0 auto var(--space-4);
            background: linear-gradient(135deg, var(--color-violeta), hsl(260, 70%, 50%));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px hsla(270, 80%, 65%, 0.25);
            position: relative;
        }

        .logo-mark::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 18px;
            background: linear-gradient(135deg, hsla(270, 80%, 65%, 0.3), hsla(160, 60%, 60%, 0.1));
            z-index: -1;
        }

        .logo-mark svg {
            width: 28px;
            height: 28px;
            stroke: white;
            stroke-width: 2;
        }

        .login-logo h1 {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            background: linear-gradient(135deg, var(--color-violeta), var(--color-menta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: var(--space-2);
            letter-spacing: -0.02em;
        }

        .login-logo p {
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
        }

        .error-message {
            background: rgba(255, 100, 100, 0.1);
            border: 1px solid rgba(255, 100, 100, 0.3);
            color: #ff6464;
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
            font-size: var(--font-size-sm);
            display: none;
        }

        .error-message.show {
            display: block;
            animation: slideUp var(--transition-base);
        }
    </style>
</head>

<body>
    <!-- Icon Sprite -->
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="login-container">
        <div class="bento-box login-box">
                <div class="login-logo">
                    <div class="logo-mark">
                        <svg><use href="#icon-dollar-sign"></use></svg>
                    </div>
                    <h1>MySan</h1>
                    <p>Sistema de Administración de Ahorros Grupales</p>
                </div>

                <div id="errorMessage" class="error-message"></div>

                <form id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <svg class="icon" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                                <use href="#icon-user"></use>
                            </svg>
                            Usuario
                        </label>
                        <input type="text" id="username" name="username" class="form-input"
                            placeholder="Ingresa tu usuario" required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <svg class="icon" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                                <use href="#icon-lock"></use>
                            </svg>
                            Contraseña
                        </label>
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="Ingresa tu contraseña" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-violeta" style="width: 100%; margin-top: var(--space-6);">
                        Entrar
                        <svg class="icon">
                            <use href="#icon-log-out"></use>
                        </svg>
                    </button>
                </form>

                <div style="margin-top: var(--space-5); text-align: center; display: flex; flex-direction: column; gap: var(--space-3);">
                    <a href="recuperar-password.php"
                        style="color: var(--color-menta); font-size: var(--font-size-sm); text-decoration: underline;">
                        ¿Olvidaste tu contraseña?
                    </a>
                    <div style="display: flex; align-items: center; gap: var(--space-3); opacity: 0.4;">
                        <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                        <span style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">o</span>
                        <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                    </div>
                    <a href="registro.php"
                        style="color: var(--color-text-secondary); font-size: var(--font-size-sm); transition: color var(--transition-base);"
                        onmouseover="this.style.color='var(--color-violeta)'"
                        onmouseout="this.style.color='var(--color-text-secondary)'">
                        ¿No tienes cuenta? <strong style="color: var(--color-violeta);">Regístrate aquí</strong>
                    </a>
                    <div style="display: flex; align-items: center; gap: var(--space-3); opacity: 0.4;">
                        <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                        <span style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">o</span>
                        <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                    </div>
                    <a href="catalogo.php"
                        style="color: var(--color-text-secondary); font-size: var(--font-size-sm); transition: color var(--transition-base);"
                        onmouseover="this.style.color='var(--color-menta)'"
                        onmouseout="this.style.color='var(--color-text-secondary)'">
                        <svg class="icon" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px; stroke: currentColor;"><use href="#icon-arrow-left"></use></svg>
                        Volver al Catálogo
                    </a>
                </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(loginForm);

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (data.data.rol === 'participante') {
                        window.location.href = 'dashboard_participante.php';
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    showError(data.message);
                }
            } catch (error) {
                showError('Error de conexión. Por favor, intenta nuevamente.');
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');

            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 5000);
        }
    </script>
</body>

</html>