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
        case 'get_tasa':
            getTasaCambio();
            break;
        case 'set_tasa':
            setTasaCambio();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

// ============================================
// Funciones de Tasa de Cambio
// ============================================

function getTasaCambio()
{
    global $pdo;
    
    $stmt = $pdo->query("SELECT valor, descripcion FROM configuracion WHERE clave = 'tasa_bcv'");
    $tasa = $stmt->fetch();
    
    $tasa_manual = 36.50; // Default
    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_default'");
    if ($row = $stmt->fetch()) {
        $tasa_manual = floatval($row['valor']);
    }
    
    jsonResponse(true, 'Tasa de cambio', [
        'tasa' => $tasa ? floatval($tasa['valor']) : $tasa_manual,
        'tasa_manual' => $tasa_manual
    ]);
}

function setTasaCambio()
{
    global $pdo;
    
    $tasa = floatval($_POST['tasa'] ?? 0);
    $usar_manual = $_POST['usar_manual'] ?? '1';
    
    if ($tasa <= 0) {
        jsonResponse(false, 'La tasa debe ser mayor a 0');
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'tasa_bcv'");
    $stmt->execute([$tasa]);
    
    $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'tasa_default'");
    $stmt->execute([$tasa]);
    
    $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'tasa_manual'");
    $stmt->execute([$usar_manual]);
    
    jsonResponse(true, 'Tasa de cambio actualizada');
}

function precioToBs($precio_usd, $tasa)
{
    return round($precio_usd * $tasa, 2);
}

function createProducto()
{
    global $pdo;

    $categoria_id = $_POST['categoria_id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_usd = floatval($_POST['precio_usd'] ?? $_POST['valor_total'] ?? 0);

    // Validations
    if (!$categoria_id || !$nombre || !$precio_usd) {
        jsonResponse(false, 'Categoría, nombre y precio USD (valor_total) son requeridos');
        return;
    }

    if ($precio_usd <= 0) {
        jsonResponse(false, 'El precio debe ser mayor a 0');
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
        $precio_usd
    ]);

    jsonResponse(true, 'Producto creado exitosamente', ['id' => $pdo->lastInsertId()]);
}

function getTasaActual()
{
    global $pdo;
    
    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_manual'");
    $usar_manual = $stmt->fetch();
    
    if ($usar_manual && $usar_manual['valor'] == '1') {
        $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_default'");
        $tasa = $stmt->fetch();
        return $tasa ? floatval($tasa['valor']) : 36.50;
    }
    
    // Usar tasa del BCV
    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_bcv'");
    $tasa = $stmt->fetch();
    return $tasa ? floatval($tasa['valor']) : 36.50;
}

function listProductos()
{
    global $pdo;

    $categoria_id = $_GET['categoria_id'] ?? null;
    $activo = $_GET['activo'] ?? 1;
    $tasa = getTasaActual();

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

    jsonResponse(true, 'Productos obtenidos', ['productos' => $productos, 'tasa_cambio' => $tasa]);
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
    $valor_total = floatval($_POST['valor_total'] ?? $_POST['precio_usd'] ?? 0);

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
