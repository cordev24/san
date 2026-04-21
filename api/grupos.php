<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createGrupo();
            break;
        case 'list':
            listGrupos();
            break;
        case 'get':
            getGrupo();
            break;
        case 'update':
            updateGrupo();
            break;
        case 'delete':
            deleteGrupo();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function createGrupo()
{
    global $pdo;

    $producto_id = $_POST['producto_id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $frecuencia = $_POST['frecuencia'] ?? 'quincenal';
    $numero_cuotas = (int) ($_POST['numero_cuotas'] ?? 0);
    $cupos_totales = (int) ($_POST['cupos_totales'] ?? 0);

    // Backend fallback: If numero_cuotas is missing (cached JS issue), use cupos_totales
    if ($numero_cuotas === 0 && $cupos_totales > 0) {
        $numero_cuotas = $cupos_totales;
    }

    // Validations
    if (!$producto_id || !$nombre || !$fecha_inicio || !$numero_cuotas || !$cupos_totales) {
        jsonResponse(false, 'Todos los campos son requeridos');
        return;
    }

    // Get product value
    $stmt = $pdo->prepare("SELECT valor_total FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        jsonResponse(false, 'Producto no encontrado');
        return;
    }

    // Calculate monto_cuota
    $monto_cuota = $producto['valor_total'] / $numero_cuotas;

    // Insert group
    $stmt = $pdo->prepare("
        INSERT INTO grupos_san (producto_id, nombre, fecha_inicio, frecuencia, numero_cuotas, cupos_totales, monto_cuota, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'abierto')
    ");

    $stmt->execute([
        $producto_id,
        $nombre,
        $fecha_inicio,
        $frecuencia,
        $numero_cuotas,
        $cupos_totales,
        $monto_cuota
    ]);

    jsonResponse(true, 'Grupo creado exitosamente', ['id' => $pdo->lastInsertId()]);
}

function listGrupos()
{
    global $pdo;

    $categoria_id = $_GET['categoria_id'] ?? null;

    $sql = "
        SELECT gs.*, p.nombre as producto_nombre, p.marca, p.modelo, p.valor_total, c.nombre as categoria_nombre, c.color
        FROM grupos_san gs
        JOIN productos p ON gs.producto_id = p.id
        JOIN categorias c ON p.categoria_id = c.id
        WHERE 1=1
    ";

    $params = [];

    if ($categoria_id) {
        $sql .= " AND c.id = ?";
        $params[] = $categoria_id;
    }

    $sql .= " ORDER BY gs.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grupos = $stmt->fetchAll();

    jsonResponse(true, 'Grupos obtenidos', ['grupos' => $grupos]);
}

function getGrupo()
{
    global $pdo;

    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $stmt = $pdo->prepare("
        SELECT gs.*, p.nombre as producto_nombre, p.marca, p.modelo, p.valor_total, c.nombre as categoria_nombre
        FROM grupos_san gs
        JOIN productos p ON gs.producto_id = p.id
        JOIN categorias c ON p.categoria_id = c.id
        WHERE gs.id = ?
    ");

    $stmt->execute([$id]);
    $grupo = $stmt->fetch();

    if (!$grupo) {
        jsonResponse(false, 'Grupo no encontrado');
        return;
    }

    jsonResponse(true, 'Grupo obtenido', ['grupo' => $grupo]);
}

function updateGrupo()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $estado = $_POST['estado'] ?? null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $updates = [];
    $params = [];

    if ($nombre) {
        $updates[] = "nombre = ?";
        $params[] = $nombre;
    }

    if ($estado) {
        $updates[] = "estado = ?";
        $params[] = $estado;
    }

    if ($fecha_inicio) {
        $updates[] = "fecha_inicio = ?";
        $params[] = $fecha_inicio;
    }

    if (empty($updates)) {
        jsonResponse(false, 'No hay datos para actualizar');
        return;
    }

    $params[] = $id;

    $sql = "UPDATE grupos_san SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(true, 'Grupo actualizado exitosamente');
}

function deleteGrupo()
{
    global $pdo;

    $id = $_POST['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    // Check if group has participants
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participantes WHERE grupo_san_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        jsonResponse(false, 'No se puede eliminar un grupo con participantes');
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM grupos_san WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(true, 'Grupo eliminado exitosamente');
}
