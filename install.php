<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() > 0) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    // Asumimos que si hay un error, puede ser porque la tabla no existe o está vacía
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MySan - Instalación</title>

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        .install-container {
            min-height: 100vh;
            display: block;
            padding: var(--space-4);
            box-sizing: border-box;
            position: relative;
        }

        .install-box {
            width: 100%;
            max-width: 500px;
            padding: var(--space-8);
            margin: 60px auto;
            box-shadow: var(--shadow-lg), var(--shadow-glow-menta);
            animation: float-static 3s ease-in-out infinite;
        }

        @keyframes float-static {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .install-logo {
            text-align: center;
            margin-bottom: var(--space-8);
        }

        .logo-mark {
            width: 56px;
            height: 56px;
            margin: 0 auto var(--space-4);
            background: linear-gradient(135deg, var(--color-menta), hsl(160, 70%, 40%));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px hsla(160, 60%, 60%, 0.25);
            position: relative;
        }

        .logo-mark::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 18px;
            background: linear-gradient(135deg, hsla(160, 60%, 60%, 0.3), hsla(270, 80%, 65%, 0.1));
            z-index: -1;
        }

        .logo-mark svg {
            width: 28px;
            height: 28px;
            stroke: white;
            stroke-width: 2;
        }

        .install-logo h1 {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            background: linear-gradient(135deg, var(--color-menta), var(--color-violeta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: var(--space-2);
            letter-spacing: -0.02em;
        }

        .install-logo p {
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

        .success-message {
            background: rgba(100, 255, 150, 0.1);
            border: 1px solid rgba(100, 255, 150, 0.3);
            color: var(--color-menta);
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
            font-size: var(--font-size-sm);
            display: none;
        }

        .success-message.show {
            display: block;
            animation: slideUp var(--transition-base);
        }
    </style>
</head>

<body>
    <!-- Icon Sprite -->
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="install-container">
        <div class="bento-box install-box">
            <div class="install-logo">
                <div class="logo-mark">
                    <svg><use href="#icon-settings"></use></svg>
                </div>
                <h1>Instalación de MySan</h1>
                <p>Crea el usuario administrador para comenzar</p>
            </div>

            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>

            <form id="installForm">
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Usuario *</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña *</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pregunta_secreta">Pregunta Secreta *</label>
                    <select id="pregunta_secreta" name="pregunta_secreta" class="form-select" required>
                        <option value="">Selecciona una pregunta...</option>
                        <option value="¿Cuál es el nombre de tu primera mascota?">¿Cuál es el nombre de tu primera mascota?</option>
                        <option value="¿En qué ciudad naciste?">¿En qué ciudad naciste?</option>
                        <option value="¿Cuál es tu color favorito?">¿Cuál es tu color favorito?</option>
                        <option value="¿Cuál es el nombre de tu colegio?">¿Cuál es el nombre de tu colegio?</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="respuesta_secreta">Respuesta Secreta *</label>
                    <input type="text" id="respuesta_secreta" name="respuesta_secreta" class="form-input" required>
                </div>

                <button type="submit" class="btn btn-menta" style="width: 100%; margin-top: var(--space-6);">
                    Instalar y Entrar
                    <svg class="icon">
                        <use href="#icon-arrow-right"></use>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        const installForm = document.getElementById('installForm');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        installForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(installForm);

            try {
                const response = await fetch('api/install.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1500);
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

        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.classList.add('show');
        }
    </script>
</body>

</html>
