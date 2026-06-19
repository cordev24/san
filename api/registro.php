<?php
/**
 * api/registro.php
 * Endpoints públicos para registro de participantes.
 * NO requiere sesión activa.
 */
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'validate_group':
            validateGroup();
            break;
        case 'register':
            registerParticipante();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error del servidor: ' . $e->getMessage());
}

/**
 * Valida que un grupo esté abierto y tenga cupos disponibles (sin necesidad de token).
 * Retorna los datos del grupo para mostrar en el formulario de registro.
 */
function validateGroup()
{
    global $pdo;

    $grupo_id = (int)($_GET['grupo_id'] ?? 0);

    if ($grupo_id <= 0) {
        jsonResponse(false, 'ID de grupo requerido');
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            gs.id,
            gs.nombre,
            gs.fecha_inicio,
            gs.frecuencia,
            gs.numero_cuotas,
            gs.cupos_totales,
            gs.cupos_ocupados,
            gs.monto_cuota,
            gs.estado,
            p.nombre  AS producto_nombre,
            p.marca,
            p.modelo,
            p.valor_total,
            c.nombre  AS categoria_nombre
        FROM grupos_san gs
        JOIN productos p  ON gs.producto_id = p.id
        JOIN categorias c ON p.categoria_id = c.id
        WHERE gs.id = ?
    ");
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch();

    if (!$grupo) {
        jsonResponse(false, 'El grupo no existe.');
        return;
    }

    if ($grupo['estado'] === 'finalizado') {
        jsonResponse(false, 'Este grupo San ya ha finalizado y no acepta nuevos participantes.');
        return;
    }

    $cupos_disponibles = $grupo['cupos_totales'] - $grupo['cupos_ocupados'];
    if ($cupos_disponibles <= 0) {
        jsonResponse(false, 'Este grupo San ya no tiene cupos disponibles.');
        return;
    }

    jsonResponse(true, 'Grupo válido', [
        'grupo'             => $grupo,
        'cupos_disponibles' => $cupos_disponibles
    ]);
}

/**
 * Registra un nuevo usuario con rol 'participante'.
 * Si se envía grupo_id, lo vincula al grupo y actualiza cupos.
 * Si no, solo crea la cuenta de usuario (registro sin grupo).
 */
function registerParticipante()
{
    global $pdo;

    $grupo_id  = (int)($_POST['grupo_id'] ?? 0);
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $cedula    = trim($_POST['cedula'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $pregunta_tipo_1 = $_POST['pregunta_tipo_1'] ?? '';
    $pregunta_pers_1 = trim($_POST['pregunta_personalizada_1'] ?? '');
    $respuesta_1     = trim($_POST['respuesta_1'] ?? '');

    $pregunta_tipo_2 = $_POST['pregunta_tipo_2'] ?? '';
    $pregunta_pers_2 = trim($_POST['pregunta_personalizada_2'] ?? '');
    $respuesta_2     = trim($_POST['respuesta_2'] ?? '');

    $pregunta_tipo_3 = $_POST['pregunta_tipo_3'] ?? '';
    $pregunta_pers_3 = trim($_POST['pregunta_personalizada_3'] ?? '');
    $respuesta_3     = trim($_POST['respuesta_3'] ?? '');

    // Resolviendo preguntas finales
    $pregunta_final_1 = ($pregunta_tipo_1 === 'Personalizada') ? $pregunta_pers_1 : $pregunta_tipo_1;
    $pregunta_final_2 = ($pregunta_tipo_2 === 'Personalizada') ? $pregunta_pers_2 : $pregunta_tipo_2;
    $pregunta_final_3 = ($pregunta_tipo_3 === 'Personalizada') ? $pregunta_pers_3 : $pregunta_tipo_3;

    // Validaciones básicas
    if (empty($username) || empty($email) || empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || empty($direccion) || empty($password)) {
        jsonResponse(false, 'Todos los campos obligatorios deben completarse.');
        return;
    }

    if (empty($pregunta_final_1) || empty($respuesta_1) || 
        empty($pregunta_final_2) || empty($respuesta_2) || 
        empty($pregunta_final_3) || empty($respuesta_3)) {
        jsonResponse(false, 'Debes completar las 3 preguntas y respuestas de seguridad.');
        return;
    }

    if (!isset($_POST['terminos']) || $_POST['terminos'] !== 'on') {
        jsonResponse(false, 'Debes aceptar los Términos y Condiciones para registrarte.');
        return;
    }

    if (strlen($password) < 6) {
        jsonResponse(false, 'La contraseña debe tener al menos 6 caracteres.');
        return;
    }

    if ($password !== $password2) {
        jsonResponse(false, 'Las contraseñas no coinciden.');
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'El correo electrónico no es válido.');
        return;
    }

    // Si viene con grupo_id, validar grupo antes de comenzar la transacción
    $grupo = null;
    if ($grupo_id > 0) {
        $stmt = $pdo->prepare("
            SELECT id, nombre, cupos_totales, cupos_ocupados, estado
            FROM grupos_san
            WHERE id = ?
            FOR UPDATE
        ");
        $pdo->beginTransaction();
        $stmt->execute([$grupo_id]);
        $grupo = $stmt->fetch();

        if (!$grupo || $grupo['estado'] === 'finalizado') {
            $pdo->rollBack();
            jsonResponse(false, 'El grupo no está disponible.');
            return;
        }

        if ($grupo['cupos_ocupados'] >= $grupo['cupos_totales']) {
            $pdo->rollBack();
            jsonResponse(false, 'Lo sentimos, el grupo acaba de llenarse. No hay cupos disponibles.');
            return;
        }
    } else {
        $pdo->beginTransaction();
    }

    // Verificar que el username no exista
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'El nombre de usuario ya está en uso. Elegí otro.');
        return;
    }

    // Verificar que el email no exista
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'El correo electrónico ya está registrado.');
        return;
    }

    // Verificar que la cédula no esté ya registrada
    $stmt = $pdo->prepare("SELECT id FROM participantes WHERE cedula = ?");
    $stmt->execute([$cedula]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'Esta cédula ya está registrada en el sistema.');
        return;
    }

    try {
        // 1. Crear usuario con rol 'participante'
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre, email, rol, pregunta_secreta, respuesta_secreta, pregunta_secreta_2, respuesta_secreta_2, pregunta_secreta_3, respuesta_secreta_3)
            VALUES (?, ?, ?, ?, 'participante', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username, $hash, $nombre . ' ' . $apellido, $email,
            $pregunta_final_1, $respuesta_1,
            $pregunta_final_2, $respuesta_2,
            $pregunta_final_3, $respuesta_3
        ]);
        $usuario_id = $pdo->lastInsertId();

        // 2. Crear participante si viene de un grupo
        $grupo_nombre = '';
        if ($grupo) {
            $stmt = $pdo->prepare("
                INSERT INTO participantes
                    (grupo_san_id, nombre, apellido, cedula, telefono, direccion, fecha_inscripcion, usuario_id, tipo_registro)
                VALUES
                    (?, ?, ?, ?, ?, ?, CURDATE(), ?, 'autonomo')
            ");
            $stmt->execute([
                $grupo['id'], $nombre, $apellido, $cedula,
                $telefono ?: null, $direccion ?: null, $usuario_id
            ]);

            // 3. Actualizar cupos_ocupados del grupo
            $stmt = $pdo->prepare("UPDATE grupos_san SET cupos_ocupados = cupos_ocupados + 1 WHERE id = ?");
            $stmt->execute([$grupo['id']]);

            $grupo_nombre = $grupo['nombre'];
        }

        $pdo->commit();

        $msg = $grupo
            ? '¡Registro exitoso! Ya formas parte del grupo. Inicia sesión con tu usuario y contraseña.'
            : '¡Registro exitoso! Inicia sesión con tu usuario y contraseña.';

        jsonResponse(true, $msg, [
            'username' => $username,
            'grupo'    => $grupo_nombre
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Error al registrar: ' . $e->getMessage());
    }
}
