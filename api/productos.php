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
        case 'detach_categoria':
            detachCategoria();
            break;
        case 'delete_imagen':
            deleteImagen();
            break;
        case 'set_cover':
            setCover();
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
// Tasa de Cambio
// ============================================

function getTasaCambio()
{
    global $pdo;
    $stmt = $pdo->query("SELECT valor, descripcion FROM configuracion WHERE clave = 'tasa_bcv'");
    $tasa = $stmt->fetch();
    $tasa_manual = 36.50;
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
    $stmt = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_bcv'");
    $tasa = $stmt->fetch();
    return $tasa ? floatval($tasa['valor']) : 36.50;
}

function precioToBs($precio_usd, $tasa)
{
    return round($precio_usd * $tasa, 2);
}

// ============================================
// CRUD Productos
// ============================================

function createProducto()
{
    global $pdo;

    $categoria_id = $_POST['categoria_id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_usd = floatval($_POST['precio_usd'] ?? $_POST['valor_total'] ?? 0);

    if (!$categoria_id || !$nombre || !$precio_usd) {
        jsonResponse(false, 'Categoría, nombre y precio USD son requeridos');
        return;
    }
    if ($precio_usd <= 0) {
        jsonResponse(false, 'El precio debe ser mayor a 0');
        return;
    }

    // Upload multiple images
    $imagenes = uploadMultiImagen('imagen');
    $cover = !empty($imagenes) ? $imagenes[0] : null;

    $stmt = $pdo->prepare("
        INSERT INTO productos (categoria_id, nombre, marca, modelo, descripcion, valor_total, imagen, activo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)
    ");
    $stmt->execute([$categoria_id, $nombre, $marca, $modelo, $descripcion, $precio_usd, $cover]);
    $producto_id = $pdo->lastInsertId();

    // Insert images into productos_imagenes
    if (!empty($imagenes)) {
        $stmtImg = $pdo->prepare("INSERT INTO productos_imagenes (producto_id, ruta, orden) VALUES (?, ?, ?)");
        foreach ($imagenes as $i => $ruta) {
            $stmtImg->execute([$producto_id, $ruta, $i]);
        }
    }

    jsonResponse(true, 'Producto creado exitosamente', ['id' => $producto_id, 'imagenes' => $imagenes]);
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

    // Fetch multiple images
    $stmtImg = $pdo->prepare("SELECT id, ruta, orden FROM productos_imagenes WHERE producto_id = ? ORDER BY orden ASC, id ASC");
    $stmtImg->execute([$id]);
    $producto['imagenes'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Producto obtenido', ['producto' => $producto]);
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

    // Attach image count to each product
    if (!empty($productos)) {
        $ids = array_column($productos, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtCount = $pdo->prepare("SELECT producto_id, COUNT(*) AS total FROM productos_imagenes WHERE producto_id IN ($placeholders) GROUP BY producto_id");
        $stmtCount->execute($ids);
        $counts = [];
        foreach ($stmtCount->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[$row['producto_id']] = (int)$row['total'];
        }
        foreach ($productos as &$p) {
            $p['imagenes_count'] = $counts[$p['id']] ?? 0;
        }
    }

    jsonResponse(true, 'Productos obtenidos', ['productos' => $productos, 'tasa_cambio' => $tasa]);
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

    if ($nombre)          { $updates[] = "nombre = ?";     $params[] = $nombre; }
    if ($marca)           { $updates[] = "marca = ?";      $params[] = $marca; }
    if ($modelo)          { $updates[] = "modelo = ?";     $params[] = $modelo; }
    if ($descripcion)     { $updates[] = "descripcion = ?"; $params[] = $descripcion; }
    if ($valor_total > 0) { $updates[] = "valor_total = ?"; $params[] = $valor_total; }

    // Upload new images (appends to existing)
    $nuevas = uploadMultiImagen('imagen');
    if (!empty($nuevas)) {
        // Get max existing orden
        $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(orden), -1) FROM productos_imagenes WHERE producto_id = ?");
        $stmtMax->execute([$id]);
        $maxOrden = (int)$stmtMax->fetchColumn();

        $stmtImg = $pdo->prepare("INSERT INTO productos_imagenes (producto_id, ruta, orden) VALUES (?, ?, ?)");
        foreach ($nuevas as $i => $ruta) {
            $stmtImg->execute([$id, $ruta, $maxOrden + 1 + $i]);
        }

        // If the product has no cover yet, set first new image as cover
        $stmtOld = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
        $stmtOld->execute([$id]);
        if (!$stmtOld->fetchColumn()) {
            $updates[] = "imagen = ?";
            $params[] = $nuevas[0];
        }
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

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM grupos_san WHERE producto_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        jsonResponse(false, 'No se puede eliminar un producto que está en uso');
        return;
    }

    // Delete image files from disk
    $stmtImg = $pdo->prepare("SELECT ruta FROM productos_imagenes WHERE producto_id = ?");
    $stmtImg->execute([$id]);
    foreach ($stmtImg->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $img['ruta'];
        if (file_exists($filePath)) @unlink($filePath);
    }

    // Soft delete
    $stmt = $pdo->prepare("UPDATE productos SET activo = FALSE WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(true, 'Producto y sus imágenes eliminados exitosamente');
}

function detachCategoria()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $stmt = $pdo->prepare("UPDATE productos SET categoria_id = NULL WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(true, 'Producto desvinculado de la categoría exitosamente');
}

// ============================================
// Image management
// ============================================

function deleteImagen()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    if (!$id) {
        jsonResponse(false, 'ID de imagen requerido');
        return;
    }

    $stmt = $pdo->prepare("SELECT id, ruta, producto_id FROM productos_imagenes WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();

    if (!$img) {
        jsonResponse(false, 'Imagen no encontrada');
        return;
    }

    // Delete file from disk
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $img['ruta'];
    if (file_exists($filePath)) @unlink($filePath);

    $producto_id = $img['producto_id'];
    $stmtDel = $pdo->prepare("DELETE FROM productos_imagenes WHERE id = ?");
    $stmtDel->execute([$id]);

    // If this was the cover image, update cover to the next available
    $stmtCheck = $pdo->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmtCheck->execute([$producto_id]);
    $currentCover = $stmtCheck->fetchColumn();

    if ($currentCover === $img['ruta']) {
        $stmtNext = $pdo->prepare("SELECT ruta FROM productos_imagenes WHERE producto_id = ? ORDER BY orden ASC, id ASC LIMIT 1");
        $stmtNext->execute([$producto_id]);
        $nextCover = $stmtNext->fetchColumn();
        $stmtUpd = $pdo->prepare("UPDATE productos SET imagen = ? WHERE id = ?");
        $stmtUpd->execute([$nextCover ?: null, $producto_id]);
    }

    jsonResponse(true, 'Imagen eliminada');
}

function setCover()
{
    global $pdo;

    $id = $_POST['id'] ?? null; // imagen id
    if (!$id) {
        jsonResponse(false, 'ID de imagen requerido');
        return;
    }

    $stmt = $pdo->prepare("SELECT ruta, producto_id FROM productos_imagenes WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();

    if (!$img) {
        jsonResponse(false, 'Imagen no encontrada');
        return;
    }

    $stmtUpd = $pdo->prepare("UPDATE productos SET imagen = ? WHERE id = ?");
    $stmtUpd->execute([$img['ruta'], $img['producto_id']]);

    jsonResponse(true, 'Imagen principal actualizada');
}

// ============================================
// Upload helpers
// ============================================

function uploadMultiImagen($fieldName)
{
    if (!isset($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        return [];
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $destDir = __DIR__ . '/../uploads/productos/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $paths = [];
    $files = reindexFiles($_FILES[$fieldName]);

    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        $filename = uniqid('prod_') . '.' . $ext;
        $destPath = $destDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $paths[] = '/uploads/productos/' . $filename;
        }
    }

    return $paths;
}

function reindexFiles($filePost)
{
    $result = [];
    $count = count($filePost['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($filePost['error'][$i] !== UPLOAD_ERR_OK) continue;
        $result[] = [
            'name'     => $filePost['name'][$i],
            'type'     => $filePost['type'][$i],
            'tmp_name' => $filePost['tmp_name'][$i],
            'error'    => $filePost['error'][$i],
            'size'     => $filePost['size'][$i],
        ];
    }
    return $result;
}
