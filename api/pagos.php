<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createPago();
            break;
        case 'list':
        case 'list_all':
            listPagos();
            break;
        case 'update':
            updatePago();
            break;
        case 'stats':
            getStats();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function createPago()
{
    global $pdo;

    $pago_id = $_POST['pago_id'] ?? null;
    $fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    $notas = trim($_POST['notas'] ?? '');

    if (!$pago_id) {
        jsonResponse(false, 'ID de pago requerido');
        return;
    }

    // Get payment info
    $stmt = $pdo->prepare("SELECT * FROM pagos WHERE id = ?");
    $stmt->execute([$pago_id]);
    $pago = $stmt->fetch();

    if (!$pago) {
        jsonResponse(false, 'Pago no encontrado');
        return;
    }

    if ($pago['estado'] === 'pagado') {
        jsonResponse(false, 'Este pago ya fue registrado');
        return;
    }

    $tasa_aplicada = getBcvRate();

    // Update payment
    $stmt = $pdo->prepare("
        UPDATE pagos 
        SET fecha_pago = ?, estado = 'pagado', metodo_pago = ?, notas = ?, tasa_aplicada = ? 
        WHERE id = ?
    ");

    $stmt->execute([
        $fecha_pago,
        $metodo_pago,
        $notas,
        $tasa_aplicada,
        $pago_id
    ]);

    jsonResponse(true, 'Pago registrado exitosamente');
}

function listPagos()
{
    global $pdo;

    $participante_id = $_GET['participante_id'] ?? null;
    $grupo_san_id = $_GET['grupo_san_id'] ?? null;
    $estado = $_GET['estado'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "
        SELECT p.*, 
               part.nombre, part.apellido, part.cedula,
               part.nombre as nombre_participante, part.apellido as apellido_participante, part.cedula as cedula_participante,
               gs.nombre as grupo_nombre, gs.monto_cuota,
               CASE 
                   WHEN p.estado = 'pendiente' AND p.fecha_vencimiento < CURDATE() THEN 'atrasado'
                   ELSE p.estado
               END as estado_real
        FROM pagos p
        JOIN participantes part ON p.participante_id = part.id
        JOIN grupos_san gs ON part.grupo_san_id = gs.id
        WHERE 1=1
    ";

    $params = [];

    if ($search) {
        $sql .= " AND (part.nombre LIKE ? OR part.apellido LIKE ? OR part.cedula LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($participante_id) {
        $sql .= " AND p.participante_id = ?";
        $params[] = $participante_id;
    }

    if ($grupo_san_id) {
        $sql .= " AND part.grupo_san_id = ?";
        $params[] = $grupo_san_id;
    }

    if ($estado) {
        if ($estado === 'atrasado') {
            $sql .= " AND p.estado = 'pendiente' AND p.fecha_vencimiento < CURDATE()";
        } else {
            $sql .= " AND p.estado = ?";
            $params[] = $estado;
        }
    }

    $sql .= " ORDER BY p.fecha_vencimiento ASC, p.numero_cuota ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pagos = $stmt->fetchAll();

    jsonResponse(true, 'Pagos obtenidos', ['pagos' => $pagos]);
}

function updatePago()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    $estado = $_POST['estado'] ?? null;

    if (!$id || !$estado) {
        jsonResponse(false, 'ID y estado requeridos');
        return;
    }

    $stmt = $pdo->prepare("UPDATE pagos SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    jsonResponse(true, 'Estado actualizado exitosamente');
}

function getStats()
{
    global $pdo;

    $grupo_san_id = $_GET['grupo_san_id'] ?? null;

    $sql = "
        SELECT 
            COUNT(*) as total_pagos,
            COALESCE(SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END), 0) as pagados,
            COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END), 0) as pendientes,
            COALESCE(SUM(CASE WHEN estado = 'pendiente' AND fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) as atrasados,
            COALESCE(SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END), 0) as total_recaudado,
            COALESCE(SUM(monto), 0) as total_esperado
        FROM pagos p
        JOIN participantes part ON p.participante_id = part.id
        WHERE 1=1
    ";

    $params = [];

    if ($grupo_san_id) {
        $sql .= " AND part.grupo_san_id = ?";
        $params[] = $grupo_san_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch();

    jsonResponse(true, 'Estadísticas obtenidas', ['stats' => $stats]);
}
