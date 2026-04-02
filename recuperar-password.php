<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 1: Verify username and show security question
    if ($action === 'verify_username') {
        $username = $_POST['username'] ?? '';

        if (empty($username)) {
            $error = 'Por favor ingresa tu usuario';
        } else {
            $stmt = $pdo->prepare("SELECT id, pregunta_secreta FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['pregunta_secreta']) {
                $show_question = true;
                $security_question = $user['pregunta_secreta'];
                $user_id = $user['id'];
            } else {
                $error = 'Usuario no encontrado o no tiene pregunta de seguridad configurada';
            }
        }
    }

    // Step 2: Verify security answer and reset password
    if ($action === 'reset_password') {
        $user_id = $_POST['user_id'] ?? '';
        $respuesta = $_POST['respuesta'] ?? '';
        $nueva_password = $_POST['nueva_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';

        if (empty($respuesta) || empty($nueva_password) || empty($confirmar_password)) {
            $error = 'Todos los campos son requeridos';
            $show_reset = true;
        } elseif ($nueva_password !== $confirmar_password) {
            $error = 'Las contraseñas no coinciden';
            $show_reset = true;
        } else {
            $stmt = $pdo->prepare("SELECT respuesta_secreta FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && strtolower(trim($respuesta)) === strtolower(trim($user['respuesta_secreta']))) {
                // Update password
                $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);

                $success = 'Contraseña actualizada exitosamente. Redirigiendo al login...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Respuesta incorrecta';
                $show_reset = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Recuperar Contraseña</title>

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

        .message {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
            font-size: var(--font-size-sm);
        }

        .error-message {
            background: rgba(255, 100, 100, 0.1);
            border: 1px solid rgba(255, 100, 100, 0.3);
            color: #ff6464;
        }

        .success-message {
            background: rgba(100, 255, 150, 0.1);
            border: 1px solid rgba(100, 255, 150, 0.3);
            color: var(--color-menta);
        }
    </style>
</head>

<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <div class="login-container">
            <div class="bento-box bento-floating login-box">
                <div class="login-logo">
                    <h1>MySan</h1>
                    <p>Recuperar Contraseña</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="message error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="message success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!isset($show_question) && !isset($show_reset) && !isset($success)): ?>
                    <!-- Step 1: Enter Username -->
                    <form method="POST">
                        <input type="hidden" name="action" value="verify_username">

                        <div class="form-group">
                            <label class="form-label" for="username">
                                <svg class="icon" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                                    <use href="#icon-user"></use>
                                </svg>
                                Usuario
                            </label>
                            <input type="text" id="username" name="username" class="form-input"
                                placeholder="Ingresa tu usuario" required
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-violeta" style="width: 100%; margin-top: var(--space-6);">
                            Continuar
                        </button>
                    </form>
                <?php elseif (isset($show_question)): ?>
                    <!-- Step 2: Answer Security Question -->
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                        <div class="form-group">
                            <label class="form-label">Pregunta de Seguridad</label>
                            <div
                                style="padding: var(--space-3); background: var(--color-surface); border-radius: var(--radius-md); margin-bottom: var(--space-4);">
                                <p style="color: var(--color-text-primary);">
                                    <?php echo htmlspecialchars($security_question); ?>
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="respuesta">Tu Respuesta</label>
                            <input type="text" id="respuesta" name="respuesta" class="form-input"
                                placeholder="Ingresa tu respuesta" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nueva_password">Nueva Contraseña</label>
                            <input type="password" id="nueva_password" name="nueva_password" class="form-input"
                                placeholder="Ingresa tu nueva contraseña" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirmar_password">Confirmar Contraseña</label>
                            <input type="password" id="confirmar_password" name="confirmar_password" class="form-input"
                                placeholder="Confirma tu nueva contraseña" required>
                        </div>

                        <button type="submit" class="btn btn-violeta" style="width: 100%; margin-top: var(--space-6);">
                            Restablecer Contraseña
                        </button>
                    </form>
                <?php endif; ?>

                <div style="margin-top: var(--space-4); text-align: center;">
                    <a href="login.php"
                        style="color: var(--color-menta); font-size: var(--font-size-sm); text-decoration: underline;">
                        Volver al Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>