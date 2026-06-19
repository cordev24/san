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
    
    $pregunta_tipo_1 = $_POST['pregunta_tipo_1'] ?? '';
    $pregunta_pers_1 = trim($_POST['pregunta_personalizada_1'] ?? '');
    $respuesta_1     = trim($_POST['respuesta_1'] ?? '');

    $pregunta_tipo_2 = $_POST['pregunta_tipo_2'] ?? '';
    $pregunta_pers_2 = trim($_POST['pregunta_personalizada_2'] ?? '');
    $respuesta_2     = trim($_POST['respuesta_2'] ?? '');

    $pregunta_tipo_3 = $_POST['pregunta_tipo_3'] ?? '';
    $pregunta_pers_3 = trim($_POST['pregunta_personalizada_3'] ?? '');
    $respuesta_3     = trim($_POST['respuesta_3'] ?? '');
    
    $errors = [];
    
    // Validations
    if (empty($username)) $errors[] = 'El usuario es requerido';
    if (empty($password)) $errors[] = 'La contraseña es requerida';
    if (empty($nombre)) $errors[] = 'El nombre es requerido';
    if (empty($email)) $errors[] = 'El email es requerido';

    // Resolviendo preguntas finales
    $pregunta_final_1 = ($pregunta_tipo_1 === 'Personalizada') ? $pregunta_pers_1 : $pregunta_tipo_1;
    $pregunta_final_2 = ($pregunta_tipo_2 === 'Personalizada') ? $pregunta_pers_2 : $pregunta_tipo_2;
    $pregunta_final_3 = ($pregunta_tipo_3 === 'Personalizada') ? $pregunta_pers_3 : $pregunta_tipo_3;

    if (empty($pregunta_final_1) || empty($respuesta_1) || 
        empty($pregunta_final_2) || empty($respuesta_2) || 
        empty($pregunta_final_3) || empty($respuesta_3)) {
        $errors[] = 'Debes completar las 3 preguntas y respuestas de seguridad';
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
                INSERT INTO usuarios (username, password, nombre, email, pregunta_secreta, respuesta_secreta, pregunta_secreta_2, respuesta_secreta_2, pregunta_secreta_3, respuesta_secreta_3) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $username,
                $hashed_password,
                $nombre,
                $email,
                $pregunta_final_1,
                $respuesta_1,
                $pregunta_final_2,
                $respuesta_2,
                $pregunta_final_3,
                $respuesta_3
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
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MySan - Crear Usuario</title>
    
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        .icon-xl {
            width: 32px;
            height: 32px;
        }
        
        .form-card {
            max-width: 800px;
            margin: 0 auto;
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-8);
        }
        
        .form-title {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
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
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <a href="dashboard.php" class="header-logo">MySan</a>
                <a href="dashboard.php" class="btn btn-outline">
                    <svg class="icon"><use href="#icon-arrow-left"></use></svg>
                    Volver
                </a>
            </div>
        </header>
        
        <div class="page-content" style="padding: var(--space-8);">
            <div class="form-card">
                <h2 class="form-title">
                    <svg class="icon-xl" style="stroke: var(--color-violeta);">
                        <use href="#icon-user"></use>
                    </svg>
                    Crear Nuevo Usuario
                </h2>
                
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
                                
                                <div class="form-grid-full" style="margin-top: var(--space-4); border-top: 1px solid var(--glass-border); padding-top: var(--space-4);">
                                    <h3 style="font-size: var(--font-size-md); font-weight: var(--font-weight-bold); color: var(--color-text-primary); margin-bottom: var(--space-2); display: flex; align-items: center; gap: 8px;">
                                        <svg class="icon-sm" style="width:16px;height:16px;stroke:var(--color-menta);"><use href="#icon-shield"></use></svg>
                                        Preguntas de Seguridad
                                    </h3>
                                </div>

                                <!-- Pregunta 1 -->
                                <div class="form-grid-full">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_tipo_1">Pregunta de Seguridad 1 *</label>
                                        <select id="pregunta_tipo_1" name="pregunta_tipo_1" class="form-select" required onchange="toggleCustomQuestion(1)">
                                            <option value="">-- Selecciona una pregunta --</option>
                                            <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                                <option value="<?php echo htmlspecialchars($pregunta); ?>" <?php echo (isset($_POST['pregunta_tipo_1']) && $_POST['pregunta_tipo_1'] === $pregunta) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pregunta); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-grid-full" id="customQuestionDiv_1" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_personalizada_1">Pregunta Personalizada 1 *</label>
                                        <input type="text" id="pregunta_personalizada_1" name="pregunta_personalizada_1" class="form-input" placeholder="Ej: ¿Cómo se llama tu mascota?" value="<?php echo htmlspecialchars($_POST['pregunta_personalizada_1'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-grid-full">
                                    <div class="form-group">
                                        <label class="form-label" for="respuesta_1">Respuesta Secreta 1 *</label>
                                        <input type="text" id="respuesta_1" name="respuesta_1" class="form-input" placeholder="Tu respuesta a la pregunta 1" required value="<?php echo htmlspecialchars($_POST['respuesta_1'] ?? ''); ?>">
                                    </div>
                                </div>

                                <!-- Pregunta 2 -->
                                <div class="form-grid-full" style="margin-top: var(--space-3); border-top: 1px dashed var(--glass-border); padding-top: var(--space-3);">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_tipo_2">Pregunta de Seguridad 2 *</label>
                                        <select id="pregunta_tipo_2" name="pregunta_tipo_2" class="form-select" required onchange="toggleCustomQuestion(2)">
                                            <option value="">-- Selecciona una pregunta --</option>
                                            <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                                <option value="<?php echo htmlspecialchars($pregunta); ?>" <?php echo (isset($_POST['pregunta_tipo_2']) && $_POST['pregunta_tipo_2'] === $pregunta) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pregunta); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-grid-full" id="customQuestionDiv_2" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_personalizada_2">Pregunta Personalizada 2 *</label>
                                        <input type="text" id="pregunta_personalizada_2" name="pregunta_personalizada_2" class="form-input" placeholder="Ej: ¿Cuál fue tu primer carro?" value="<?php echo htmlspecialchars($_POST['pregunta_personalizada_2'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-grid-full">
                                    <div class="form-group">
                                        <label class="form-label" for="respuesta_2">Respuesta Secreta 2 *</label>
                                        <input type="text" id="respuesta_2" name="respuesta_2" class="form-input" placeholder="Tu respuesta a la pregunta 2" required value="<?php echo htmlspecialchars($_POST['respuesta_2'] ?? ''); ?>">
                                    </div>
                                </div>

                                <!-- Pregunta 3 -->
                                <div class="form-grid-full" style="margin-top: var(--space-3); border-top: 1px dashed var(--glass-border); padding-top: var(--space-3);">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_tipo_3">Pregunta de Seguridad 3 *</label>
                                        <select id="pregunta_tipo_3" name="pregunta_tipo_3" class="form-select" required onchange="toggleCustomQuestion(3)">
                                            <option value="">-- Selecciona una pregunta --</option>
                                            <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                                <option value="<?php echo htmlspecialchars($pregunta); ?>" <?php echo (isset($_POST['pregunta_tipo_3']) && $_POST['pregunta_tipo_3'] === $pregunta) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pregunta); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-grid-full" id="customQuestionDiv_3" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="pregunta_personalizada_3">Pregunta Personalizada 3 *</label>
                                        <input type="text" id="pregunta_personalizada_3" name="pregunta_personalizada_3" class="form-input" placeholder="Ej: ¿Cuál es tu banda favorita?" value="<?php echo htmlspecialchars($_POST['pregunta_personalizada_3'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-grid-full">
                                    <div class="form-group">
                                        <label class="form-label" for="respuesta_3">Respuesta Secreta 3 *</label>
                                        <input type="text" id="respuesta_3" name="respuesta_3" class="form-input" placeholder="Tu respuesta a la pregunta 3" required value="<?php echo htmlspecialchars($_POST['respuesta_3'] ?? ''); ?>">
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
    
    <script>
        function toggleCustomQuestion(index) {
            const select = document.getElementById('pregunta_tipo_' + index);
            const customDiv = document.getElementById('customQuestionDiv_' + index);
            const customInput = document.getElementById('pregunta_personalizada_' + index);
            if (!select || !customDiv || !customInput) return;
            
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
        toggleCustomQuestion(1);
        toggleCustomQuestion(2);
        toggleCustomQuestion(3);
    </script>
</body>
</html>
