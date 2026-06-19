<?php
// @deprecated: El uso de la tabla 'turnos' y la asignación mediante sorteo se ha descontinuado.
// La asignación actual se rige por el 'orden_inscripcion' según leivis-pg2.md.
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createParticipante();
            break;
        case 'join_group':
            joinGroup();
            break;
        case 'remove_from_group':
            removeFromGroup();
            break;
        case 'list':
        case 'list_all':
            listParticipantes();
            break;
        case 'get':
            getParticipante();
            break;
        case 'get_by_cedula':
            getParticipanteByCedula();
            break;
        case 'update':
            updateParticipante();
            break;
        case 'update_global':
            updateParticipanteGlobal();
            break;
        case 'disable_global':
            disableParticipanteGlobal();
            break;
        case 'delete':
            deleteParticipante();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function createParticipante()
{
    global $pdo;

    $grupo_san_id = $_POST['grupo_san_id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $fecha_inscripcion = $_POST['fecha_inscripcion'] ?? date('Y-m-d');

    // Validations
    if (!$grupo_san_id || !$nombre || !$apellido || !$cedula) {
        jsonResponse(false, 'Nombre, apellido, cédula y grupo son requeridos');
        return;
    }

    // Check if cedula already exists IN THE SAME GROUP
    $stmt = $pdo->prepare("SELECT id FROM participantes WHERE grupo_san_id = ? AND cedula = ?");
    $stmt->execute([$grupo_san_id, $cedula]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'La cédula ya está registrada en este grupo');
        return;
    }

    // Check if group has available spots
    $stmt = $pdo->prepare("SELECT cupos_totales, cupos_ocupados FROM grupos_san WHERE id = ?");
    $stmt->execute([$grupo_san_id]);
    $grupo = $stmt->fetch();

    if (!$grupo) {
        jsonResponse(false, 'Grupo no encontrado');
        return;
    }

    if ($grupo['cupos_ocupados'] >= $grupo['cupos_totales']) {
        jsonResponse(false, 'El grupo no tiene cupos disponibles');
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if user account already exists by username (cedula)
        $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmtUser->execute([$cedula]);
        $existingUser = $stmtUser->fetch();
        
        $usuario_id = null;

        if ($existingUser) {
            $usuario_id = $existingUser['id'];
        } else {
            // 1. Create User account for Participant (Username: cedula, Password: hash(cedula))
            $password_hash = password_hash($cedula, PASSWORD_DEFAULT);
            $nombre_completo = $nombre . ' ' . $apellido;
            
            $stmtUser = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, rol) VALUES (?, ?, ?, 'participante')");
            $stmtUser->execute([$cedula, $password_hash, $nombre_completo]);
            
            $usuario_id = $pdo->lastInsertId();
        }

        // 2. Insert participant linked to user account
        $stmt = $pdo->prepare("
            INSERT INTO participantes (grupo_san_id, nombre, apellido, cedula, telefono, direccion, fecha_inscripcion, usuario_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $grupo_san_id,
            $nombre,
            $apellido,
            $cedula,
            $telefono,
            $direccion,
            $fecha_inscripcion,
            $usuario_id
        ]);

        $participante_id = $pdo->lastInsertId();

    // Update group cupos_ocupados
    $stmt = $pdo->prepare("UPDATE grupos_san SET cupos_ocupados = cupos_ocupados + 1 WHERE id = ?");
    $stmt->execute([$grupo_san_id]);

    // Auto-asignar turno por orden de inscripción
    $stmtTurno = $pdo->prepare("SELECT COALESCE(MAX(numero_turno), 0) + 1 FROM turnos WHERE grupo_san_id = ? FOR UPDATE");
    $stmtTurno->execute([$grupo_san_id]);
    $nextTurno = (int)$stmtTurno->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO turnos (grupo_san_id, participante_id, numero_turno, metodo_asignacion, estado, fecha_asignacion) VALUES (?, ?, ?, 'orden_inscripcion', 'asignado', NOW())");
    $stmt->execute([$grupo_san_id, $participante_id, $nextTurno]);

    // Create payment records for all cuotas
    $stmt = $pdo->prepare("SELECT numero_cuotas, monto_cuota, fecha_inicio, frecuencia FROM grupos_san WHERE id = ?");
    $stmt->execute([$grupo_san_id]);
    $grupo_info = $stmt->fetch();

    $fecha_base = new DateTime($grupo_info['fecha_inicio']);
    $dias_incremento = ($grupo_info['frecuencia'] === 'quincenal') ? 15 : 30;

    for ($i = 1; $i <= $grupo_info['numero_cuotas']; $i++) {
        // Payment date is the base date at the start, then increments
        $fecha_vencimiento = clone $fecha_base;

        $stmt = $pdo->prepare("
            INSERT INTO pagos (participante_id, numero_cuota, monto, fecha_vencimiento, estado) 
            VALUES (?, ?, ?, ?, 'pendiente')
        ");

        $stmt->execute([
            $participante_id,
            $i,
            $grupo_info['monto_cuota'],
            $fecha_vencimiento->format('Y-m-d')
        ]);

        // Increment the base date for the next installment
        $fecha_base->modify("+{$dias_incremento} days");
    }

    $pdo->commit();
    jsonResponse(true, 'Participante inscrito exitosamente', ['id' => $participante_id]);
    
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Error al inscribir participante: ' . $e->getMessage());
    }
}

/**
 * Permite a un participante autenticado unirse a un grupo disponible.
 * POST: grupo_id
 * Usa la sesión actual para identificar al usuario.
 */
function joinGroup()
{
    global $pdo;

    $grupo_id = (int)($_POST['grupo_id'] ?? 0);
    $user_id  = (int)($_SESSION['user_id'] ?? 0);

    if ($grupo_id <= 0 || $user_id <= 0) {
        jsonResponse(false, 'Solicitud inválida.');
        return;
    }

    // Obtener datos del usuario
    $stmt = $pdo->prepare("SELECT username, nombre, email FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(false, 'Usuario no encontrado.');
        return;
    }

    // Obtener cédula y datos del participante desde sus registros existentes
    $stmt = $pdo->prepare("SELECT cedula, telefono, direccion FROM participantes WHERE usuario_id = ? AND activo = 1 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();

    // Separar nombre completo en nombre y apellido
    $nombre_parts = explode(' ', $user['nombre'], 2);
    $nombre   = $nombre_parts[0] ?? '';
    $apellido = $nombre_parts[1] ?? '';
    $cedula   = $existing ? $existing['cedula'] : $user['username'];
    $telefono = $existing ? ($existing['telefono'] ?? '') : '';
    $direccion = $existing ? ($existing['direccion'] ?? '') : '';

    if (empty($nombre) || empty($apellido) || empty($cedula)) {
        jsonResponse(false, 'Completa tu perfil antes de unirte a un grupo. Contacta al administrador.');
        return;
    }

    $pdo->beginTransaction();

    // Validar grupo
    $stmt = $pdo->prepare("SELECT id, nombre, cupos_totales, cupos_ocupados, estado FROM grupos_san WHERE id = ? FOR UPDATE");
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch();

    if (!$grupo || $grupo['estado'] !== 'abierto') {
        $pdo->rollBack();
        jsonResponse(false, 'El grupo no está disponible.');
        return;
    }

    if ($grupo['cupos_ocupados'] >= $grupo['cupos_totales']) {
        $pdo->rollBack();
        jsonResponse(false, 'El grupo ya no tiene cupos disponibles.');
        return;
    }

    // Verificar que no esté ya inscrito en este grupo
    $stmt = $pdo->prepare("SELECT id FROM participantes WHERE grupo_san_id = ? AND usuario_id = ? AND activo = 1");
    $stmt->execute([$grupo_id, $user_id]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(false, 'Ya estás inscrito en este grupo.');
        return;
    }

    try {
        // Crear participante
        $stmt = $pdo->prepare("
            INSERT INTO participantes (grupo_san_id, nombre, apellido, cedula, telefono, direccion, fecha_inscripcion, usuario_id, tipo_registro)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, 'autonomo')
        ");
        $stmt->execute([$grupo_id, $nombre, $apellido, $cedula, $telefono ?: null, $direccion ?: null, $user_id]);
        $participante_id = $pdo->lastInsertId();

        // Actualizar cupos
        $pdo->prepare("UPDATE grupos_san SET cupos_ocupados = cupos_ocupados + 1 WHERE id = ?")->execute([$grupo_id]);

        // Auto-asignar turno por orden de inscripción
        $stmtTurno = $pdo->prepare("SELECT COALESCE(MAX(numero_turno), 0) + 1 FROM turnos WHERE grupo_san_id = ? FOR UPDATE");
        $stmtTurno->execute([$grupo_id]);
        $nextTurno = (int)$stmtTurno->fetchColumn();

        $pdo->prepare("INSERT INTO turnos (grupo_san_id, participante_id, numero_turno, metodo_asignacion, estado, fecha_asignacion) VALUES (?, ?, ?, 'orden_inscripcion', 'asignado', NOW())")
            ->execute([$grupo_id, $participante_id, $nextTurno]);

        // Crear pagos
        $stmt = $pdo->prepare("SELECT numero_cuotas, monto_cuota, fecha_inicio, frecuencia FROM grupos_san WHERE id = ?");
        $stmt->execute([$grupo_id]);
        $g = $stmt->fetch();

        $fecha_base = new DateTime($g['fecha_inicio']);
        $dias = ($g['frecuencia'] === 'quincenal') ? 15 : 30;

        for ($i = 1; $i <= $g['numero_cuotas']; $i++) {
            $fv = clone $fecha_base;
            $pdo->prepare("INSERT INTO pagos (participante_id, numero_cuota, monto, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, 'pendiente')")
                ->execute([$participante_id, $i, $g['monto_cuota'], $fv->format('Y-m-d')]);
            $fecha_base->modify("+{$dias} days");
        }

        $pdo->commit();
        jsonResponse(true, '¡Te has inscrito exitosamente en el grupo!');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Error al inscribirte: ' . $e->getMessage());
    }
}

function listParticipantes()
{
    global $pdo;

    $grupo_san_id = $_GET['grupo_san_id'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "
        SELECT p.*, gs.nombre as grupo_nombre, gs.monto_cuota
        FROM participantes p
        JOIN grupos_san gs ON p.grupo_san_id = gs.id
        WHERE p.activo = TRUE
    ";

    $params = [];

    if ($search) {
        $sql .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR p.cedula LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($grupo_san_id) {
        $sql .= " AND p.grupo_san_id = ?";
        $params[] = $grupo_san_id;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $participantes = $stmt->fetchAll();

    jsonResponse(true, 'Participantes obtenidos', ['participantes' => $participantes]);
}

function getParticipante()
{
    global $pdo;

    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $stmt = $pdo->prepare("
        SELECT p.*, gs.nombre as grupo_nombre, gs.monto_cuota, gs.numero_cuotas
        FROM participantes p
        JOIN grupos_san gs ON p.grupo_san_id = gs.id
        WHERE p.id = ?
    ");

    $stmt->execute([$id]);
    $participante = $stmt->fetch();

    if (!$participante) {
        jsonResponse(false, 'Participante no encontrado');
        return;
    }

    jsonResponse(true, 'Participante obtenido', ['participante' => $participante]);
}

function getParticipanteByCedula()
{
    global $pdo;

    $cedula   = $_GET['cedula'] ?? null;
    $grupo_id = (int)($_GET['grupo_id'] ?? 0);

    if (!$cedula) {
        jsonResponse(false, 'Cédula requerida');
        return;
    }

    // Obtener la información del participante correspondiente a esta cédula más reciente
    $stmt = $pdo->prepare("
        SELECT nombre, apellido, telefono, direccion
        FROM participantes 
        WHERE cedula = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");

    $stmt->execute([$cedula]);
    $participante = $stmt->fetch();

    if (!$participante) {
        jsonResponse(false, 'Participante no encontrado');
        return;
    }

    // Si se especificó un grupo, verificar si ya está inscrito en él
    $already_inscrito = false;
    if ($grupo_id > 0) {
        $stmtCheck = $pdo->prepare("SELECT id FROM participantes WHERE grupo_san_id = ? AND cedula = ? AND activo = 1");
        $stmtCheck->execute([$grupo_id, $cedula]);
        $already_inscrito = (bool) $stmtCheck->fetch();
    }

    jsonResponse(true, 'Participante encontrado', [
        'participante'      => $participante,
        'already_inscrito'  => $already_inscrito
    ]);
}

function updateParticipante()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $updates = [];
    $params = [];

    if ($telefono) {
        $updates[] = "telefono = ?";
        $params[] = $telefono;
    }

    if ($direccion) {
        $updates[] = "direccion = ?";
        $params[] = $direccion;
    }

    if (empty($updates)) {
        jsonResponse(false, 'No hay datos para actualizar');
        return;
    }

    $params[] = $id;

    $sql = "UPDATE participantes SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(true, 'Participante actualizado exitosamente');
}

function updateParticipanteGlobal()
{
    global $pdo;

    $cedula = $_POST['cedula'] ?? null;
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!$cedula) {
        jsonResponse(false, 'Cédula requerida para actualización global');
        return;
    }

    $updates = [];
    $params = [];

    if ($telefono !== '') {
        $updates[] = "telefono = ?";
        $params[] = $telefono;
    }

    if ($direccion !== '') {
        $updates[] = "direccion = ?";
        $params[] = $direccion;
    }

    if (empty($updates)) {
        jsonResponse(false, 'No hay datos para actualizar');
        return;
    }

    $params[] = $cedula;

    $sql = "UPDATE participantes SET " . implode(', ', $updates) . " WHERE cedula = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(true, 'Perfil global actualizado exitosamente en todos sus grupos');
}

function deleteParticipante()
{
    global $pdo;
    $participante_id = $_POST['id'] ?? null;

    if (!$participante_id) {
        jsonResponse(false, 'ID de participante requerido');
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get grupo_san_id before deleting
        $stmt = $pdo->prepare("SELECT grupo_san_id FROM participantes WHERE id = ?");
        $stmt->execute([$participante_id]);
        $part = $stmt->fetch();

        if (!$part) {
            jsonResponse(false, 'Participante no encontrado');
            $pdo->rollBack();
            return;
        }

        $grupo_san_id = $part['grupo_san_id'];

        // Check if has payments
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pagos WHERE participante_id = ? AND estado = 'pagado'");
        $stmt->execute([$participante_id]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            $pdo->rollBack();
            jsonResponse(false, 'No se puede eliminar un participante con pagos registrados');
            return;
        }

        // Delete turno
        $stmt = $pdo->prepare("DELETE FROM turnos WHERE participante_id = ?");
        $stmt->execute([$participante_id]);

        // Decrement cupos
        $stmt = $pdo->prepare("UPDATE grupos_san SET cupos_ocupados = cupos_ocupados - 1 WHERE id = ?");
        $stmt->execute([$grupo_san_id]);

        // Delete payments (pending)
        $stmt = $pdo->prepare("DELETE FROM pagos WHERE participante_id = ?");
        $stmt->execute([$participante_id]);

        // Delete participant
        $stmt = $pdo->prepare("DELETE FROM participantes WHERE id = ?");
        $stmt->execute([$participante_id]);

        $pdo->commit();
        jsonResponse(true, 'Participante eliminado correctamente');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Error al eliminar participante: ' . $e->getMessage());
    }
}

function disableParticipanteGlobal()
{
    global $pdo;
    $cedula = $_POST['cedula'] ?? null;

    if (!$cedula) {
        jsonResponse(false, 'Cédula requerida');
        return;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE participantes SET activo = 0 WHERE cedula = ?");
        $stmt->execute([$cedula]);

        // Option 1: Also disable user account if you have an 'activo' column in usuarios.
        // $stmtUser = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE username = ?");
        // $stmtUser->execute([$cedula]);

        $pdo->commit();
        jsonResponse(true, 'Participante inhabilitado globalmente de todos sus grupos');
    } catch(Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Error: ' . $e->getMessage());
    }
}

function removeFromGroup()
{
    global $pdo;

    $participante_id = $_POST['id'] ?? null;
    $motivo_salida = trim($_POST['motivo_salida'] ?? '');

    if (!$participante_id) {
        jsonResponse(false, 'ID de participante requerido');
        return;
    }

    if (empty($motivo_salida)) {
        jsonResponse(false, 'Debe indicar el motivo de la salida');
        return;
    }

    try {
        $pdo->beginTransaction();

        // Obtener grupo_san_id para este participante
        $stmt = $pdo->prepare("SELECT grupo_san_id FROM participantes WHERE id = ?");
        $stmt->execute([$participante_id]);
        $part = $stmt->fetch();

        if (!$part) {
            $pdo->rollBack();
            jsonResponse(false, 'Participante no encontrado');
            return;
        }

        $grupo_san_id = $part['grupo_san_id'];

        // 1. Marcar inactivo y guardar motivo
        $stmt = $pdo->prepare("UPDATE participantes SET activo = 0, motivo_salida = ?, fecha_salida = CURDATE() WHERE id = ?");
        $stmt->execute([$motivo_salida, $participante_id]);

        // 2. Liberar el cupo
        $stmt = $pdo->prepare("UPDATE grupos_san SET cupos_ocupados = cupos_ocupados - 1 WHERE id = ?");
        $stmt->execute([$grupo_san_id]);

        // 3. Eliminar pagos pendientes o atrasados (los pagados se mantienen como historial)
        $stmt = $pdo->prepare("DELETE FROM pagos WHERE participante_id = ? AND estado IN ('pendiente', 'atrasado')");
        $stmt->execute([$participante_id]);

        // 4. Eliminar el turno (si no ha sido entregado)
        $stmt = $pdo->prepare("DELETE FROM turnos WHERE participante_id = ? AND estado IN ('pendiente', 'asignado')");
        $stmt->execute([$participante_id]);

        $pdo->commit();
        jsonResponse(true, 'Participante removido del grupo exitosamente');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Error al remover participante: ' . $e->getMessage());
    }
}
