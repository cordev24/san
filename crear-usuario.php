<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();

// Predefined security questions
$preguntas_predefinidas = [
    '¿Cuál es tu color favorito?',
    '¿En qué ciudad naciste?',
    '¿Cuál es el nombre de tu primera mascota?',
    '¿Cuál es tu comida favorita?',
    '¿Cuál es el nombre de tu mejor amigo de la infancia?',
    '¿Cuál es tu película favorita?',
    'Personalizada' // Option to write custom question
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $pregunta_tipo = $_POST['pregunta_tipo'];
    $pregunta_personalizada = trim($_POST['pregunta_personalizada'] ?? '');
    $respuesta_secreta = trim($_POST['respuesta_secreta']);
    
    $errors = [];
    
    // Validations
    if (empty($username)) $errors[] = 'El usuario es requerido';
    if (empty($password)) $errors[] = 'La contraseña es requerida';
    if (empty($nombre)) $errors[] = 'El nombre es requerido';
    if (empty($email)) $errors[] = 'El email es requerido';
    if (empty($respuesta_secreta)) $errors[] = 'La respuesta secreta es requerida';
    
    // Determine security question
    if ($pregunta_tipo === 'Personalizada') {
        if (empty($pregunta_personalizada)) {
            $errors[] = 'Debes escribir tu pregunta personalizada';
        }
        $pregunta_final = $pregunta_personalizada;
    } else {
        $pregunta_final = $pregunta_tipo;
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'El usuario ya existe';
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'El email ya está registrado';
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (username, password, nombre, email, pregunta_secreta, respuesta_secreta) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $username,
                $hashed_password,
                $nombre,
                $email,
                $pregunta_final,
                $respuesta_secreta
            ]);
            
            $success = 'Usuario creado exitosamente';
            
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            $errors[] = 'Error al crear usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Crear Usuario</title>
    
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        .header {
            padding: var(--space-6) var(--space-8);
            background: var(--glass-background);
            backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: var(--z-dropdown);
        }
        
        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-logo {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            background: linear-gradient(135deg, var(--color-violeta), var(--color-menta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-4);
        }
        
        .form-grid-full {
            grid-column: span 2;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-grid-full {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-content">
                <div class="header-logo">MySan</div>
                <a href="dashboard.php" class="btn btn-outline">
                    <svg class="icon">
                        <use href="#icon-home"></use>
                    </svg>
                    Volver al Dashboard
                </a>
            </div>
        </header>
        
        <div class="bento-container" style="padding-top: var(--space-8);">
            <div class="form-container">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">
                            <svg class="bento-icon" style="stroke: var(--color-violeta);">
                                <use href="#icon-user"></use>
                            </svg>
                            Crear Nuevo Usuario
                        </div>
                    </div>
                    
                    <div class="bento-content">
                        <?php if (isset($errors) && !empty($errors)): ?>
                            <div class="message error-message">
                                <ul style="margin: 0; padding-left: var(--space-4);">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="message success-message">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-grid">
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="username">Usuario *</label>
                                        <input 
                                            type="text" 
                                            id="username" 
                                            name="username" 
                                            class="form-input" 
                                            placeholder="Ej: jperez"
                                            required
                                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="password">Contraseña *</label>
                                        <input 
                                            type="password" 
                                            id="password" 
                                            name="password" 
                                            class="form-input" 
                                            placeholder="Mínimo 4 caracteres"
                                            required
                                        >
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="nombre">Nombre Completo *</label>
                                        <input 
                                            type="text" 
                                            id="nombre" 
                                            name="nombre" 
                                            class="form-input" 
                                            placeholder="Ej: Juan Pérez"
                                            required
                                            value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="form-group">
                                        <label class="form-label" for="email">Email *</label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="form-input" 
                                            placeholder="Ej: juan@ejemplo.com"
                                            required
                                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div class="form-grid-full">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_tipo">Pregunta de Seguridad *</label>
                                        <select 
                                            id="pregunta_tipo" 
                                            name="pregunta_tipo" 
                                            class="form-select"
                                            required
                                            onchange="toggleCustomQuestion()"
                                        >
                                            <option value="">-- Selecciona una pregunta --</option>
                                            <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                                <option value="<?php echo htmlspecialchars($pregunta); ?>"
                                                    <?php echo (isset($_POST['pregunta_tipo']) && $_POST['pregunta_tipo'] === $pregunta) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($pregunta); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-grid-full" id="customQuestionDiv" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_personalizada">Escribe tu Pregunta Personalizada</label>
                                        <input 
                                            type="text" 
                                            id="pregunta_personalizada" 
                                            name="pregunta_personalizada" 
                                            class="form-input" 
                                            placeholder="Ej: ¿Cuál es el nombre de tu abuelo?"
                                            value="<?php echo htmlspecialchars($_POST['pregunta_personalizada'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div class="form-grid-full">
                                    <div class="form-group">
                                        <label class="form-label" for="respuesta_secreta">Respuesta Secreta *</label>
                                        <input 
                                            type="text" 
                                            id="respuesta_secreta" 
                                            name="respuesta_secreta" 
                                            class="form-input" 
                                            placeholder="Ingresa la respuesta a tu pregunta de seguridad"
                                            required
                                            value="<?php echo htmlspecialchars($_POST['respuesta_secreta'] ?? ''); ?>"
                                        >
                                        <small style="color: var(--color-text-tertiary); font-size: var(--font-size-xs); margin-top: var(--space-1); display: block;">
                                            Esta respuesta se usará para recuperar tu contraseña
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                                <button type="submit" class="btn btn-violeta" style="flex: 1;">
                                    <svg class="icon">
                                        <use href="#icon-check-circle"></use>
                                    </svg>
                                    Crear Usuario
                                </button>
                                <a href="dashboard.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCustomQuestion() {
            const select = document.getElementById('pregunta_tipo');
            const customDiv = document.getElementById('customQuestionDiv');
            const customInput = document.getElementById('pregunta_personalizada');
            
            if (select.value === 'Personalizada') {
                customDiv.style.display = 'block';
                customInput.required = true;
            } else {
                customDiv.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
        
        // Check on page load
        toggleCustomQuestion();
    </script>
</body>
</html>
