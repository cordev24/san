<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();

$mensaje = '';
$mensaje_tipo = '';

// Obtener datos completos del usuario
$stmt = $pdo->prepare("SELECT id, username, nombre, email, pregunta_secreta, respuesta_secreta, rol FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetail = $stmt->fetch();

if (!$userDetail) {
    die("Usuario no encontrado.");
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']);
    $email     = trim($_POST['email']);
    $username  = trim($_POST['username']);
    $pregunta  = trim($_POST['pregunta_secreta']);
    $respuesta = trim($_POST['respuesta_secreta']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    $errors = [];
    if (empty($nombre)) $errors[] = 'El nombre es requerido';
    if (empty($email)) $errors[] = 'El email es requerido';
    if (empty($username)) $errors[] = 'El nombre de usuario es requerido';

    // Verificar si el usuario ya existe para otro ID
    if ($username !== $userDetail['username']) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userDetail['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'El nombre de usuario ya está en uso';
        }
    }

    // Verificar si el email ya existe para otro ID
    if ($email !== $userDetail['email']) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userDetail['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'El correo electrónico ya está registrado';
        }
    }

    if (!empty($password)) {
        if (strlen($password) < 4) {
            $errors[] = 'La contraseña debe tener al menos 4 caracteres';
        }
        if ($password !== $confirm) {
            $errors[] = 'Las contraseñas de confirmación no coinciden';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE usuarios SET nombre = ?, email = ?, username = ?, pregunta_secreta = ?, respuesta_secreta = ? WHERE id = ?";
            $params = [$nombre, $email, $username, $pregunta, $respuesta, $userDetail['id']];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $userDetail['id']]);
            }

            $pdo->commit();
            $mensaje = 'Perfil actualizado exitosamente';
            $mensaje_tipo = 'success';

            // Recargar información actualizada
            $stmt = $pdo->prepare("SELECT id, username, nombre, email, pregunta_secreta, respuesta_secreta, rol FROM usuarios WHERE id = ?");
            $stmt->execute([$userDetail['id']]);
            $userDetail = $stmt->fetch();
            $user = getCurrentUser();
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = 'Error al guardar los cambios: ' . $e->getMessage();
            $mensaje_tipo = 'error';
        }
    } else {
        $mensaje = implode('<br>', $errors);
        $mensaje_tipo = 'error';
    }
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
    <title>MySan - Mi Perfil</title>

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        .profile-layout {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-8) var(--space-6);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-5);
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
            margin-bottom: var(--space-1);
            font-weight: var(--font-weight-medium);
        }

        .form-group input {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            background: var(--color-surface);
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
            box-sizing: border-box;
            transition: border-color var(--transition-base);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-violeta);
        }

        .msg {
            padding: var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-6);
            font-size: var(--font-size-sm);
        }

        .msg--success {
            background: color-mix(in srgb, var(--color-menta) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-menta) 30%, transparent);
            color: var(--color-menta);
        }

        .msg--error {
            background: color-mix(in srgb, var(--color-salmon) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-salmon) 30%, transparent);
            color: var(--color-salmon);
        }

        .quick-action-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
            height: 100%;
            box-sizing: border-box;
        }

        .rol-badge {
            display: inline-flex;
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-bold);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: fit-content;
        }

        .rol-badge--admin {
            background: color-mix(in srgb, var(--color-violeta) 20%, transparent);
            color: var(--color-violeta);
            border: 1px solid color-mix(in srgb, var(--color-violeta) 40%, transparent);
        }

        .rol-badge--participante {
            background: color-mix(in srgb, var(--color-menta) 20%, transparent);
            color: var(--color-menta);
            border: 1px solid color-mix(in srgb, var(--color-menta) 40%, transparent);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <!-- Header -->
        <?php
        $headerLogoHref   = 'dashboard.php';
        $headerLogoutHref = 'logout.php';
        // Determinar a dónde volver
        $headerBackUrl    = ($userDetail['rol'] === 'participante') ? 'dashboard_participante.php' : 'dashboard.php';
        $headerBackLabel  = 'Volver al Dashboard';
        include 'includes/header.php';
        ?>

        <div class="page-header" style="padding: var(--space-6); border-bottom: 1px solid var(--glass-border);">
            <h1 class="page-title" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-3);">
                <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-violeta);">
                    <use href="#icon-user"></use>
                </svg>
                Mi Perfil
            </h1>
            <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                Administra tus credenciales de acceso, datos personales y preguntas de seguridad.
            </p>
        </div>

        <div class="profile-layout">
            <?php if ($mensaje): ?>
                <div class="msg msg--<?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="bento-container">
                <!-- Columna del Formulario de Credenciales -->
                <div class="bento-8">
                    <div class="bento-box" style="padding: var(--space-6);">
                        <div class="bento-header" style="margin-bottom: var(--space-6);">
                            <div class="bento-title">Editar Información Personal</div>
                        </div>

                        <form method="POST" action="perfil.php" style="display: flex; flex-direction: column; gap: var(--space-6);">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nombre">Nombre Completo *</label>
                                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($userDetail['nombre']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Correo Electrónico *</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userDetail['email'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group full">
                                    <label for="username">Nombre de Usuario *</label>
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userDetail['username']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="password">Nueva Contraseña <small style="color: var(--color-text-tertiary);">(Vacío para no cambiar)</small></label>
                                    <input type="password" id="password" name="password" placeholder="Mínimo 4 caracteres">
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña">
                                </div>

                                <div class="form-group">
                                    <label for="pregunta_secreta">Pregunta Secreta (Recuperación)</label>
                                    <input type="text" id="pregunta_secreta" name="pregunta_secreta" value="<?php echo htmlspecialchars($userDetail['pregunta_secreta'] ?? ''); ?>" placeholder="Ej: ¿Cuál es tu color favorito?">
                                </div>

                                <div class="form-group">
                                    <label for="respuesta_secreta">Respuesta Secreta</label>
                                    <input type="password" id="respuesta_secreta" name="respuesta_secreta" value="<?php echo htmlspecialchars($userDetail['respuesta_secreta'] ?? ''); ?>" placeholder="Respuesta de recuperación">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-violeta" style="width: fit-content; align-self: flex-start;">
                                <svg class="icon"><use href="#icon-save"></use></svg>
                                Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Columna de Acciones Rápidas y Rol -->
                <div class="bento-4">
                    <div class="quick-action-card">
                        <div>
                            <span class="stat-label" style="display: block; margin-bottom: var(--space-2);">Rol Asignado</span>
                            <span class="rol-badge rol-badge--<?php echo $userDetail['rol']; ?>">
                                <?php echo $userDetail['rol']; ?>
                            </span>
                        </div>

                        <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: var(--space-2) 0;">

                        <?php if ($userDetail['rol'] === 'admin'): ?>
                            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                                <h4 style="font-weight: var(--font-weight-bold); font-size: var(--font-size-sm); color: var(--color-text-primary);">Configuración Financiera</h4>
                                <p style="color: var(--color-text-secondary); font-size: var(--font-size-xs); line-height: 1.5;">
                                    Como administrador, puedes ajustar la tasa oficial de cambio BCV utilizada para los cálculos del sistema.
                                </p>
                                <a href="modules/comprobantes/index.php#tasa-bcv" class="btn btn-salmon" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <svg class="icon"><use href="#icon-trending-up"></use></svg>
                                    Ajustar Tasa BCV
                                </a>
                            </div>
                        <?php else: ?>
                            <div>
                                <p style="color: var(--color-text-secondary); font-size: var(--font-size-xs); line-height: 1.5;">
                                    Si requieres cambiar tu rol o tienes algún problema con tus grupos de ahorro, por favor comunícate con el administrador del sistema.
                                </p>
                            </div>
                        <?php endif; ?>

                        <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: var(--space-2) 0; margin-top: auto;">

                        <a href="logout.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 8px; border-color: var(--color-salmon); color: var(--color-salmon);">
                            <svg class="icon"><use href="#icon-log-out"></use></svg>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/shared.js"></script>
</body>

</html>
