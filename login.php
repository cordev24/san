<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            padding: var(--space-8);
        }

        .login-logo {
            text-align: center;
            margin-bottom: var(--space-8);
        }

        .login-logo h1 {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            background: linear-gradient(135deg, var(--color-violeta), var(--color-menta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: var(--space-2);
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

    <div class="main-content">
        <div class="login-container">
            <div class="bento-box bento-floating login-box">
                <div class="login-logo">
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

                <div style="margin-top: var(--space-4); text-align: center;">
                    <a href="recuperar-password.php"
                        style="color: var(--color-menta); font-size: var(--font-size-sm); text-decoration: underline;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
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
                    window.location.href = 'dashboard.php';
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