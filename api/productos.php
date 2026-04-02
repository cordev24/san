<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createProducto();
            break;
        case 'list':
            listProductos();
            break;
        case 'get':
            getProducto();
            break;
        case 'update':
            updateProducto();
            break;
        case 'delete':
            deleteProducto();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function createProducto()
{
    global $pdo;

    $categoria_id = $_POST['categoria_id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $valor_total = floatval($_POST['valor_total'] ?? 0);

    // Validations
    if (!$categoria_id || !$nombre || !$valor_total) {
        jsonResponse(false, 'Categoría, nombre y valor son requeridos');
        return;
    }

    if ($valor_total <= 0) {
        jsonResponse(false, 'El valor debe ser mayor a 0');
        return;
    }

    // Insert product
    $stmt = $pdo->prepare("
        INSERT INTO productos (categoria_id, nombre, marca, modelo, descripcion, valor_total, activo) 
        VALUES (?, ?, ?, ?, ?, ?, TRUE)
    ");

    $stmt->execute([
        $categoria_id,
        $nombre,
        $marca,
        $modelo,
        $descripcion,
        $valor_total
    ]);

    jsonResponse(true, 'Producto creado exitosamente', ['id' => $pdo->lastInsertId()]);
}

function listProductos()
{
    global $pdo;

    $categoria_id = $_GET['categoria_id'] ?? null;
    $activo = $_GET['activo'] ?? 1;

    $sql = "
        SELECT p.*, c.nombre as categoria_nombre, c.color
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE p.activo = ?
    ";

    $params = [$activo];

    if ($categoria_id) {
        $sql .= " AND p.categoria_id = ?";
        $params[] = $categoria_id;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    jsonResponse(true, 'Productos obtenidos', ['productos' => $productos]);
}

function getProducto()
{
    global $pdo;

    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $stmt = $pdo->prepare("
        SELECT p.*, c.nombre as categoria_nombre
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE p.id = ?
    ");

    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        jsonResponse(false, 'Producto no encontrado');
        return;
    }

    jsonResponse(true, 'Producto obtenido', ['producto' => $producto]);
}

function updateProducto()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $valor_total = floatval($_POST['valor_total'] ?? 0);

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

    if ($marca) {
        $updates[] = "marca = ?";
        $params[] = $marca;
    }

    if ($modelo) {
        $updates[] = "modelo = ?";
        $params[] = $modelo;
    }

    if ($descripcion) {
        $updates[] = "descripcion = ?";
        $params[] = $descripcion;
    }

    if ($valor_total > 0) {
        $updates[] = "valor_total = ?";
        $params[] = $valor_total;
    }

    if (empty($updates)) {
        jsonResponse(false, 'No hay datos para actualizar');
        return;
    }

    $params[] = $id;

    $sql = "UPDATE productos SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(true, 'Producto actualizado exitosamente');
}

function deleteProducto()
{
    global $pdo;

    $id = $_POST['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    // Check if product is used in any group
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM grupos_san WHERE producto_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        jsonResponse(false, 'No se puede eliminar un producto que está en uso');
        return;
    }

    // Soft delete
    $stmt = $pdo->prepare("UPDATE productos SET activo = FALSE WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(true, 'Producto eliminado exitosamente');
}
