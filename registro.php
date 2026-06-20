<?php
$preguntas_predefinidas = [
    '¿Cuál es tu color favorito?',
    '¿En qué ciudad naciste?',
    '¿Cuál es el nombre de tu primera mascota?',
    '¿Cuál es tu comida favorita?',
    '¿Cuál es el nombre de tu mejor amigo de la infancia?',
    '¿Cuál es tu película favorita?',
    'Personalizada'
];
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
    <title>MySan — Registro</title>
    <meta name="description" content="Regístrate en MySan, el sistema de ahorro grupal.">

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        .reg-container {
            min-height: 100vh;
            display: block;
            padding: 60px var(--space-4);
            position: relative;
        }

        .reg-container::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 20% 30%, hsla(270, 80%, 65%, 0.06), transparent),
                radial-gradient(ellipse 60% 40% at 80% 70%, hsla(160, 60%, 60%, 0.05), transparent);
            pointer-events: none;
        }

        .reg-card {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), var(--shadow-lg);
            overflow: hidden;
            animation: regFloat 6s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes regFloat {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }

        .reg-header {
            background: linear-gradient(135deg,
                hsl(270, 75%, 50%) 0%,
                hsl(270, 70%, 40%) 40%,
                hsl(260, 65%, 35%) 100%);
            padding: var(--space-8);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .reg-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, hsla(160, 60%, 70%, 0.15), transparent 70%);
            border-radius: 50%;
        }

        .reg-header::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -20%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, hsla(270, 80%, 80%, 0.1), transparent 70%);
            border-radius: 50%;
        }

        .reg-logo-mark {
            width: 44px;
            height: 44px;
            margin-bottom: var(--space-3);
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(4px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .reg-logo-mark svg {
            width: 22px;
            height: 22px;
            stroke: white;
            stroke-width: 2;
        }

        .reg-header h1 {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--space-1);
            position: relative;
            z-index: 1;
        }

        .reg-header > p {
            opacity: 0.85;
            font-size: var(--font-size-sm);
            position: relative;
            z-index: 1;
        }

        .group-info-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: var(--radius-md);
            padding: var(--space-4);
            margin-top: var(--space-4);
            position: relative;
            z-index: 1;
            animation: fadeSlideUp 0.4s ease-out;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .group-info-card .g-name {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
        }

        .group-info-card .g-meta {
            font-size: var(--font-size-xs);
            opacity: 0.8;
            margin-top: 4px;
        }

        .group-info-card .g-badges {
            display: flex;
            gap: var(--space-2);
            margin-top: var(--space-3);
            flex-wrap: wrap;
        }

        .g-badge {
            background: rgba(255,255,255,0.18);
            padding: 3px 12px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
            backdrop-filter: blur(2px);
        }

        .reg-body {
            padding: var(--space-8);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .reg-header { padding: var(--space-6); }
            .reg-body { padding: var(--space-6); }
        }

        /* Estados de UI */
        #loadingState, #errorState, #successState, #formState {
            display: none;
        }

        #loadingState.show, #errorState.show, #successState.show, #formState.show {
            display: block;
        }

        .state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto var(--space-4);
            display: block;
        }

        /* Loading spinner mejorado */
        .loader-ring {
            width: 48px;
            height: 48px;
            margin: 0 auto var(--space-4);
            border: 3px solid var(--glass-border);
            border-top-color: var(--color-violeta);
            border-radius: 50%;
            animation: ringSpin 0.8s linear infinite;
        }

        @keyframes ringSpin {
            to { transform: rotate(360deg); }
        }

        /* Error state */
        .error-state-inner {
            animation: fadeSlideUp 0.3s ease-out;
        }

        /* Success state mejorado */
        .success-check {
            width: 64px;
            height: 64px;
            margin: 0 auto var(--space-4);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-menta), hsl(160, 60%, 40%));
            animation: successPulse 0.6s ease-out;
        }

        .success-check svg {
            width: 32px;
            height: 32px;
            stroke: white;
            stroke-width: 2.5;
        }

        @keyframes successPulse {
            0%   { transform: scale(0); opacity: 0; }
            60%  { transform: scale(1.15); }
            100% { transform: scale(1); opacity: 1; }
        }

        .alert-box {
            padding: var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
            font-size: var(--font-size-sm);
            display: none;
            animation: fadeSlideUp 0.2s ease-out;
        }

        .alert-error {
            background: rgba(220,38,38,0.08);
            border: 1px solid rgba(220,38,38,0.25);
            color: var(--color-error);
        }

        .alert-box.show { display: block; }

        .password-hint {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
            margin-top: 4px;
        }

        /* Transition para los inputs */
        .reg-body .form-input:focus {
            border-color: var(--color-violeta);
            box-shadow: 0 0 0 3px hsla(270, 80%, 65%, 0.12);
        }

        .form-section-divider {
            height: 1px;
            background: var(--glass-border);
            margin: var(--space-5) 0;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: var(--space-6);
        }
        .step-dot {
            width: 32px;
            height: 4px;
            background: var(--glass-border);
            border-radius: 2px;
            transition: background 0.3s ease;
        }
        .step-dot.active {
            background: var(--color-violeta);
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: var(--color-text-secondary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.05);
            color: var(--color-text-primary);
        }
        .step-content {
            animation: fadeSlideUp 0.3s ease-out;
        }
    </style>
</head>
<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <div class="reg-container">
            <div class="reg-card">

                <!-- Loading -->
                <div id="loadingState" class="show" style="padding: var(--space-16); text-align: center;">
                    <div class="loader-ring"></div>
                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm);">Cargando formulario…</p>
                </div>

                <!-- Error -->
                <div id="errorState" style="padding: var(--space-12); text-align: center;">
                    <div class="error-state-inner">
                        <svg class="state-icon" style="stroke: var(--color-error);">
                            <use href="#icon-x-circle"></use>
                        </svg>
                        <h2 id="errorTitle" style="font-size: var(--font-size-xl); margin-bottom: var(--space-2);">No disponible</h2>
                        <p id="errorMsg" style="color: var(--color-text-secondary); font-size: var(--font-size-sm);"></p>
                        <a href="login.php" class="btn btn-violeta" style="display: inline-block; margin-top: var(--space-6);">
                            Ir al Inicio de Sesión
                        </a>
                    </div>
                </div>

                <!-- Éxito del registro -->
                <div id="successState" style="padding: var(--space-12); text-align: center;">
                    <div class="success-check">
                        <svg><use href="#icon-check"></use></svg>
                    </div>
                    <h2 style="font-size: var(--font-size-xl); margin-bottom: var(--space-2); color: var(--color-text-primary);">
                        ¡Registro exitoso!
                    </h2>
                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--space-2);">
                        Tu cuenta ha sido creada correctamente.
                    </p>
                    <p style="font-size: var(--font-size-sm); color: var(--color-text-tertiary);">
                        Inicia sesión con tu usuario y contraseña.
                    </p>
                    <a href="login.php" class="btn btn-violeta" style="display: inline-block; margin-top: var(--space-6);">
                        Iniciar Sesión
                        <svg class="icon"><use href="#icon-log-in"></use></svg>
                    </a>
                </div>

                <!-- Formulario de registro -->
                <div id="formState">
                    <div class="reg-header">
                        <div class="reg-logo-mark">
                            <svg><use href="#icon-dollar-sign"></use></svg>
                        </div>
                        <h1 id="formTitle">Crear Cuenta</h1>
                        <p id="formSubtitle">Crea tu cuenta para empezar a usar MySan.</p>

                        <div id="groupInfoCard" class="group-info-card" style="display:none;">
                            <div class="g-name" id="groupName">—</div>
                            <div class="g-meta" id="groupMeta">—</div>
                            <div class="g-badges" id="groupBadges"></div>
                        </div>
                    </div>

                    <!-- Cuerpo del formulario -->
                    <div class="reg-body">
                                                <div class="step-indicator">
                            <div class="step-dot active" id="dot-1"></div>
                            <div class="step-dot" id="dot-2"></div>
                            <div class="step-dot" id="dot-3"></div>
                        </div>

                        <div id="formAlert" class="alert-box alert-error"></div>

                        <form id="regForm" enctype="multipart/form-data">
                            <input type="hidden" id="grupoIdField" name="grupo_id" value="0">

                            <!-- PASO 1: Datos Personales -->
                            <div id="step1" class="step-content">
                                <!-- Nombre y Apellido -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="nombre">Nombre *</label>
                                        <input type="text" id="nombre" name="nombre" class="form-input"
                                               placeholder="Ej: María" required autocomplete="given-name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="apellido">Apellido *</label>
                                        <input type="text" id="apellido" name="apellido" class="form-input"
                                               placeholder="Ej: González" required autocomplete="family-name">
                                    </div>
                                </div>

                                <!-- Cédula -->
                                <div class="form-group">
                                    <label class="form-label" for="cedula">
                                        Número de Cédula *
                                    </label>
                                    <input type="text" id="cedula" name="cedula" class="form-input"
                                           placeholder="Ej: V-12345678" required autocomplete="off">
                                </div>

                                <!-- Teléfono y Dirección -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="telefono">Teléfono</label>
                                        <input type="tel" id="telefono" name="telefono" class="form-input"
                                               placeholder="Ej: 0414-1234567" required autocomplete="tel">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="direccion">Dirección *</label>
                                        <input type="text" id="direccion" name="direccion" class="form-input"
                                               placeholder="Ciudad / Sector" required autocomplete="street-address">
                                    </div>
                                </div>

                                <div class="step-actions" style="display: flex; justify-content: flex-end; margin-top: var(--space-6);">
                                    <button type="button" class="btn btn-violeta" onclick="nextStep(1)">
                                        Siguiente <svg class="icon"><use href="#icon-arrow-right"></use></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- PASO 2: Credenciales -->
                            <div id="step2" class="step-content" style="display: none;">
                                <!-- Usuario y Email -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="username">Usuario *</label>
                                        <input type="text" id="username" name="username" class="form-input"
                                               placeholder="Ej: mgonzalez" required autocomplete="username">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="email">
                                            <svg class="icon" style="vertical-align:middle; width:14px; height:14px; margin-right:5px;">
                                                <use href="#icon-mail"></use>
                                            </svg>
                                            Correo electrónico *
                                        </label>
                                        <input type="email" id="email" name="email" class="form-input"
                                               placeholder="Ej: maria@correo.com" required autocomplete="email">
                                    </div>
                                </div>

                                <!-- Contraseña -->
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="password">Contraseña *</label>
                                        <input type="password" id="password" name="password" class="form-input"
                                               placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="password2">Confirmar *</label>
                                        <input type="password" id="password2" name="password2" class="form-input"
                                               placeholder="Repite tu contraseña" required autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="step-actions" style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                        <svg class="icon"><use href="#icon-arrow-left"></use></svg> Anterior
                                    </button>
                                    <button type="button" class="btn btn-violeta" onclick="nextStep(2)">
                                        Siguiente <svg class="icon"><use href="#icon-arrow-right"></use></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- PASO 3: Preguntas de Seguridad -->
                            <div id="step3" class="step-content" style="display: none;">
                                <h3 style="font-size: var(--font-size-md); font-weight: var(--font-weight-bold); color: var(--color-text-primary); margin-bottom: var(--space-4); display: flex; align-items: center; gap: 8px;">
                                    <svg style="width: 16px; height: 16px; stroke: var(--color-menta); stroke-width: 2.5;"><use href="#icon-shield"></use></svg>
                                    Capa de Seguridad
                                </h3>

                                <!-- Pregunta 1 -->
                                <div class="form-group">
                                    <label class="form-label" for="pregunta_tipo_1">Pregunta de Seguridad 1 *</label>
                                    <select id="pregunta_tipo_1" name="pregunta_tipo_1" class="form-select pregunta-select" required>
                                        <option value="">-- Selecciona una pregunta --</option>
                                        <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                            <option value="<?php echo htmlspecialchars($pregunta); ?>"><?php echo htmlspecialchars($pregunta); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="customQuestionDiv_1" class="form-group" style="display: none;">
                                    <label class="form-label" for="pregunta_personalizada_1">Pregunta Personalizada 1 *</label>
                                    <input type="text" id="pregunta_personalizada_1" name="pregunta_personalizada_1" class="form-input" placeholder="Ej: ¿Cómo se llama tu mascota?">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="respuesta_1">Respuesta Secreta 1 *</label>
                                    <input type="text" id="respuesta_1" name="respuesta_1" class="form-input" placeholder="Tu respuesta a la pregunta 1" required autocomplete="off">
                                </div>

                                <div class="form-section-divider"></div>

                                <!-- Pregunta 2 -->
                                <div class="form-group">
                                    <label class="form-label" for="pregunta_tipo_2">Pregunta de Seguridad 2 *</label>
                                    <select id="pregunta_tipo_2" name="pregunta_tipo_2" class="form-select pregunta-select" required>
                                        <option value="">-- Selecciona una pregunta --</option>
                                        <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                            <option value="<?php echo htmlspecialchars($pregunta); ?>"><?php echo htmlspecialchars($pregunta); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="customQuestionDiv_2" class="form-group" style="display: none;">
                                    <label class="form-label" for="pregunta_personalizada_2">Pregunta Personalizada 2 *</label>
                                    <input type="text" id="pregunta_personalizada_2" name="pregunta_personalizada_2" class="form-input" placeholder="Ej: ¿Cuál fue tu primer carro?">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="respuesta_2">Respuesta Secreta 2 *</label>
                                    <input type="text" id="respuesta_2" name="respuesta_2" class="form-input" placeholder="Tu respuesta a la pregunta 2" required autocomplete="off">
                                </div>

                                <div class="form-section-divider"></div>

                                <!-- Pregunta 3 -->
                                <div class="form-group">
                                    <label class="form-label" for="pregunta_tipo_3">Pregunta de Seguridad 3 *</label>
                                    <select id="pregunta_tipo_3" name="pregunta_tipo_3" class="form-select pregunta-select" required>
                                        <option value="">-- Selecciona una pregunta --</option>
                                        <?php foreach ($preguntas_predefinidas as $pregunta): ?>
                                            <option value="<?php echo htmlspecialchars($pregunta); ?>"><?php echo htmlspecialchars($pregunta); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="customQuestionDiv_3" class="form-group" style="display: none;">
                                    <label class="form-label" for="pregunta_personalizada_3">Pregunta Personalizada 3 *</label>
                                    <input type="text" id="pregunta_personalizada_3" name="pregunta_personalizada_3" class="form-input" placeholder="Ej: ¿Cuál es tu banda favorita?">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="respuesta_3">Respuesta Secreta 3 *</label>
                                    <input type="text" id="respuesta_3" name="respuesta_3" class="form-input" placeholder="Tu respuesta a la pregunta 3" required autocomplete="off">
                                </div>

                                <div class="form-group" style="margin-top: var(--space-4); margin-bottom: var(--space-4);">
                                    <label style="display: flex; align-items: flex-start; gap: var(--space-2); cursor: pointer;">
                                        <input type="checkbox" name="terminos" id="terminos" required style="margin-top: 4px;">
                                        <span style="font-size: var(--font-size-sm); color: var(--color-text-secondary); line-height: 1.4;">
                                            He leído y acepto los <a href="javascript:void(0)" onclick="openTosModal()" style="color: var(--color-violeta); text-decoration: underline;">Términos y Condiciones de Servicio</a> *
                                        </span>
                                    </label>
                                </div>

                                <div class="step-actions" style="display: flex; justify-content: space-between; margin-top: var(--space-6);">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                                        <svg class="icon"><use href="#icon-arrow-left"></use></svg> Anterior
                                    </button>
                                    <button type="submit" id="submitBtn" class="btn btn-violeta">
                                        <span id="submitBtnText">Crear Cuenta</span>
                                        <svg class="icon"><use href="#icon-user-plus"></use></svg>
                                    </button>
                                </div>
                            </div>
                        </form>

<div style="margin-top: var(--space-5); text-align:center; display: flex; flex-direction: column; gap: var(--space-3);">
                            <div style="display: flex; align-items: center; gap: var(--space-3); opacity: 0.4;">
                                <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                                <span style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">o</span>
                                <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                            </div>
                            <a href="login.php"
                               style="font-size: var(--font-size-sm); color: var(--color-text-secondary); transition: color var(--transition-base);"
                               onmouseover="this.style.color='var(--color-menta)'"
                               onmouseout="this.style.color='var(--color-text-secondary)'">
                                ¿Ya tienes cuenta? <strong style="color: var(--color-menta);">Inicia sesión</strong>
                            </a>
                            <div style="display: flex; align-items: center; gap: var(--space-3); opacity: 0.4;">
                                <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                                <span style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">o</span>
                                <span style="flex:1; height:1px; background: var(--glass-border);"></span>
                            </div>
                            <a href="catalogo.php"
                               style="font-size: var(--font-size-sm); color: var(--color-text-secondary); transition: color var(--transition-base);"
                               onmouseover="this.style.color='var(--color-violeta)'"
                               onmouseout="this.style.color='var(--color-text-secondary)'">
                                <svg class="icon" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px; stroke: currentColor;"><use href="#icon-arrow-left"></use></svg>
                                Volver al Catálogo
                            </a>
                        </div>
                    </div>
                </div><!-- /formState -->

            </div><!-- /reg-card -->
        </div>
    </div>

    <!-- Modal Términos -->
    <div id="tosModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: var(--space-4);">
        <div class="modal-content" style="background: var(--color-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); width: 100%; max-width: 600px; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); animation: fadeSlideUp 0.3s ease-out;">
            <div class="modal-header" style="padding: var(--space-4) var(--space-6); border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <h2 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-bold); color: var(--color-text-primary); margin: 0;">Términos y Condiciones</h2>
                <button type="button" onclick="closeTosModal()" style="background: transparent; border: none; color: var(--color-text-secondary); cursor: pointer; padding: 4px; display: flex; align-items: center;">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <div class="modal-body" id="tosModalBody" style="padding: var(--space-6); overflow-y: auto; color: var(--color-text-secondary); font-size: var(--font-size-sm); line-height: 1.6;">
                <div style="text-align:center; padding: var(--space-8);">
                    <div class="loader-ring"></div>
                    <p>Cargando términos...</p>
                </div>
            </div>
            <div class="modal-footer" style="padding: var(--space-4) var(--space-6); border-top: 1px solid var(--glass-border); display: flex; justify-content: flex-end; background: rgba(255,255,255,0.02);">
                <button type="button" class="btn btn-violeta" onclick="closeTosModal()">Entendido</button>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function updateStepIndicator() {
            for (let i = 1; i <= totalSteps; i++) {
                const dot = document.getElementById('dot-' + i);
                if (dot) {
                    if (i <= currentStep) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                }
            }
        }

        function showStep(step) {
            for (let i = 1; i <= totalSteps; i++) {
                const el = document.getElementById('step' + i);
                if (el) {
                    el.style.display = (i === step) ? 'block' : 'none';
                }
            }
            currentStep = step;
            updateStepIndicator();
        }

        function validateStep(step) {
            const stepEl = document.getElementById('step' + step);
            const inputs = stepEl.querySelectorAll('input, select');
            let valid = true;
            for (const input of inputs) {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    valid = false;
                    break;
                }
            }
            
            if (valid && step === 2) {
                const p1 = document.getElementById('password').value;
                const p2 = document.getElementById('password2').value;
                if (p1 !== p2) {
                    showFormAlert("Las contraseñas no coinciden");
                    return false;
                } else if (p1.length < 6) {
                    showFormAlert("La contraseña debe tener al menos 6 caracteres");
                    return false;
                } else {
                    hideFormAlert();
                }
            }
            
            return valid;
        }

        function nextStep(step) {
            if (validateStep(step)) {
                hideFormAlert();
                showStep(step + 1);
            }
        }

        function prevStep(step) {
            hideFormAlert();
            showStep(step - 1);
        }

        function updateQuestionOptions() {
            const selects = [
                document.getElementById('pregunta_tipo_1'),
                document.getElementById('pregunta_tipo_2'),
                document.getElementById('pregunta_tipo_3')
            ];
            
            const selectedValues = selects.map(s => s.value).filter(v => v !== '' && v !== 'Personalizada');
            
            selects.forEach(select => {
                const currentValue = select.value;
                Array.from(select.options).forEach(option => {
                    if (option.value === '' || option.value === 'Personalizada') return;
                    
                    if (selectedValues.includes(option.value) && option.value !== currentValue) {
                        option.style.display = 'none';
                        option.disabled = true;
                    } else {
                        option.style.display = '';
                        option.disabled = false;
                    }
                });
            });
        }

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

        let tosLoaded = false;
        function openTosModal() {
            const modal = document.getElementById('tosModal');
            modal.style.display = 'flex';
            if (!tosLoaded) {
                fetch('terminos.php')
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const content = doc.querySelector('.bento-content');
                        if (content) {
                            const header = content.querySelector('.page-header');
                            if (header) header.style.display = 'none';
                            
                            // Adjust styles to match modal
                            const h3s = content.querySelectorAll('.tos-section h3');
                            h3s.forEach(h3 => {
                                h3.style.fontSize = 'var(--font-size-md)';
                                h3.style.fontWeight = 'var(--font-weight-bold)';
                                h3.style.color = 'var(--color-violeta)';
                                h3.style.marginBottom = 'var(--space-2)';
                            });
                            
                            const sections = content.querySelectorAll('.tos-section');
                            sections.forEach(sec => {
                                sec.style.marginBottom = 'var(--space-4)';
                            });

                            document.getElementById('tosModalBody').innerHTML = content.innerHTML;
                        } else {
                            document.getElementById('tosModalBody').innerHTML = '<p>No se pudieron cargar los términos.</p>';
                        }
                        tosLoaded = true;
                    })
                    .catch(() => {
                        document.getElementById('tosModalBody').innerHTML = '<p>Error de conexión al cargar los términos.</p>';
                    });
            }
        }

        function closeTosModal() {
            document.getElementById('tosModal').style.display = 'none';
        }

        const params = new URLSearchParams(window.location.search);
        const grupoId = params.get('grupo_id');

        const loadingState = document.getElementById('loadingState');
        const errorState   = document.getElementById('errorState');
        const successState = document.getElementById('successState');
        const formState    = document.getElementById('formState');

        function showState(id) {
            [loadingState, errorState, successState, formState].forEach(el => el.classList.remove('show'));
            document.getElementById(id).classList.add('show');
        }

        function showFormAlert(msg) {
            const box = document.getElementById('formAlert');
            box.textContent = msg;
            box.classList.add('show');
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideFormAlert() {
            document.getElementById('formAlert').classList.remove('show');
        }

        const groupInfoCard = document.getElementById('groupInfoCard');
        const formTitle     = document.getElementById('formTitle');
        const formSubtitle  = document.getElementById('formSubtitle');
        const submitBtnText = document.getElementById('submitBtnText');

        if (grupoId) {
            // Modo: registro desde el catálogo (vinculado a un grupo)
            fetch('api/registro.php?action=validate_group&grupo_id=' + encodeURIComponent(grupoId))
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('errorTitle').textContent = 'Grupo no disponible';
                        document.getElementById('errorMsg').textContent = data.message;
                        showState('errorState');
                        return;
                    }

                    const g = data.data.grupo;

                    formTitle.textContent    = 'Unirse al San';
                    formSubtitle.textContent = 'Completa tus datos para unirte al grupo de ahorro.';

                    document.getElementById('groupName').textContent = g.nombre;
                    document.getElementById('groupMeta').textContent =
                        g.categoria_nombre + ' › ' + g.producto_nombre + (g.marca ? ' ' + g.marca : '');

                    const badges = document.getElementById('groupBadges');
                    badges.innerHTML = `
                        <span class="g-badge">$${parseFloat(g.monto_cuota).toFixed(2)}/cuota</span>
                        <span class="g-badge">${g.numero_cuotas} cuotas</span>
                        <span class="g-badge">${data.data.cupos_disponibles} cupo${data.data.cupos_disponibles !== 1 ? 's' : ''} disponible${data.data.cupos_disponibles !== 1 ? 's' : ''}</span>
                        <span class="g-badge">${g.frecuencia}</span>
                    `;

                    groupInfoCard.style.display = '';
                    document.getElementById('grupoIdField').value = grupoId;
                    submitBtnText.textContent = 'Registrarme en el San';

                    showState('formState');
                })
                .catch(() => {
                    document.getElementById('errorTitle').textContent = 'Error de conexión';
                    document.getElementById('errorMsg').textContent = 'No se pudo verificar el grupo. Por favor, intenta nuevamente.';
                    showState('errorState');
                });
        } else {
            // Modo: registro standalone (sin grupo)
            groupInfoCard.style.display = 'none';
            formTitle.textContent    = 'Crear Cuenta';
            formSubtitle.textContent = 'Crea tu cuenta para empezar a usar MySan.';
            submitBtnText.textContent = 'Crear Cuenta';
            showState('formState');
        }

        // Inicializar preguntas de seguridad
        [1, 2, 3].forEach(i => {
            const select = document.getElementById('pregunta_tipo_' + i);
            if(select) {
                select.addEventListener('change', () => {
                    toggleCustomQuestion(i);
                    updateQuestionOptions();
                });
                toggleCustomQuestion(i);
            }
        });
        updateQuestionOptions();
        showStep(1);

        // Envío del formulario
        document.getElementById('regForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            hideFormAlert();

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            document.getElementById('submitBtnText').textContent = 'Registrando…';

            const formData = new FormData(e.target);
            formData.set('action', 'register');

            try {
                const res  = await fetch('api/registro.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showState('successState');
                } else {
                    showFormAlert(data.message);
                    btn.disabled = false;
                    submitBtnText.textContent = grupoId ? 'Registrarme en el San' : 'Crear Cuenta';
                }
            } catch {
                showFormAlert('Error de conexión. Por favor, intenta nuevamente.');
                btn.disabled = false;
                submitBtnText.textContent = grupoId ? 'Registrarme en el San' : 'Crear Cuenta';
            }
        });
    </script>
</body>
</html>
