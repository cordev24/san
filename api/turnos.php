<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_participantes':
            getParticipantes();
            break;
        case 'assign_random':
            assignRandom();
            break;
        case 'assign_manual':
            assignManual();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function getParticipantes()
{
    global $pdo;
    $grupo_id = $_GET['grupo_id'] ?? null;
    if (!$grupo_id) {
        jsonResponse(false, 'ID de grupo requerido');
        return;
    }

    $stmt = $pdo->prepare("SELECT id, nombre, apellido, cedula, orden_turno, fecha_entrega FROM participantes WHERE grupo_san_id = ? ORDER BY orden_turno ASC, id ASC");
    $stmt->execute([$grupo_id]);
    $participantes = $stmt->fetchAll();

    jsonResponse(true, 'Participantes obtenidos', ['participantes' => $participantes]);
}

function assignRandom()
{
    global $pdo;
    $grupo_id = $_POST['grupo_id'] ?? null;
    if (!$grupo_id) {
        jsonResponse(false, 'ID de grupo requerido');
        return;
    }

    // Get all participants
    $stmt = $pdo->prepare("SELECT id FROM participantes WHERE grupo_san_id = ? AND activo = 1");
    $stmt->execute([$grupo_id]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($ids)) {
        jsonResponse(false, 'No hay participantes en este grupo');
        return;
    }

    shuffle($ids);

    $pdo->beginTransaction();
    try {
        foreach ($ids as $index => $id) {
            $orden = $index + 1;
            $stmt = $pdo->prepare("UPDATE participantes SET orden_turno = ? WHERE id = ?");
            $stmt->execute([$orden, $id]);
        }
        $pdo->commit();

        recalculateDates($grupo_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function assignManual()
{
    global $pdo;
    $grupo_id = $_POST['grupo_id'] ?? null;
    $asignaciones = $_POST['asignaciones'] ?? null; // Expecting JSON string or array

    if (!$grupo_id || !$asignaciones) {
        jsonResponse(false, 'Datos incompletos');
        return;
    }

    if (is_string($asignaciones)) {
        $asignaciones = json_decode($asignaciones, true);
    }

    $pdo->beginTransaction();
    try {
        foreach ($asignaciones as $item) {
            $stmt = $pdo->prepare("UPDATE participantes SET orden_turno = ? WHERE id = ? AND grupo_san_id = ?");
            $stmt->execute([$item['orden'], $item['participante_id'], $grupo_id]);
        }
        $pdo->commit();

        recalculateDates($grupo_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function recalculateDates($grupo_id)
{
    global $pdo;

    // Get group info
    $stmt = $pdo->prepare("SELECT fecha_inicio, frecuencia FROM grupos_san WHERE id = ?");
    $stmt->execute([$grupo_id]);
    $grupo = $stmt->fetch();

    if (!$grupo)
        return;

    // Get participants ordered by turn
    $stmt = $pdo->prepare("SELECT id, orden_turno FROM participantes WHERE grupo_san_id = ? AND orden_turno IS NOT NULL ORDER BY orden_turno ASC");
    $stmt->execute([$grupo_id]);
    $participantes = $stmt->fetchAll();

    foreach ($participantes as $part) {
        $orden = (int) $part['orden_turno'];
        $fecha_inicio = new DateTime($grupo['fecha_inicio']);

        if ($grupo['frecuencia'] === 'quincenal') {
            $intervalo = ($orden - 1) * 15;
            $fecha_inicio->modify("+$intervalo days");
        } else { // mensual
            $intervalo = ($orden - 1);
            $fecha_inicio->modify("+$intervalo months");
        }

        $stmt = $pdo->prepare("UPDATE participantes SET fecha_entrega = ? WHERE id = ?");
        $stmt->execute([$fecha_inicio->format('Y-m-d'), $part['id']]);
    }

    jsonResponse(true, 'Turnos y fechas actualizadas correctamente');
}
