<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createCategoria();
            break;
        case 'list':
            listCategorias();
            break;
        case 'get':
            getCategoria();
            break;
        case 'update':
            updateCategoria();
            break;
        case 'delete':
            deleteCategoria();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function createCategoria()
{
    global $pdo;

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = $_POST['color'] ?? 'violeta';

    if (!$nombre) {
        jsonResponse(false, 'El nombre es requerido');
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, color) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $descripcion, $color]);

    jsonResponse(true, 'Categoría creada exitosamente', ['id' => $pdo->lastInsertId()]);
}

function listCategorias()
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll();

    jsonResponse(true, 'Categorías obtenidas', ['categorias' => $categorias]);
}

function getCategoria()
{
    global $pdo;

    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoria = $stmt->fetch();

    if (!$categoria) {
        jsonResponse(false, 'Categoría no encontrada');
        return;
    }

    jsonResponse(true, 'Categoría obtenida', ['categoria' => $categoria]);
}

function updateCategoria()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = $_POST['color'] ?? 'violeta';

    if (!$id || !$nombre) {
        jsonResponse(false, 'ID y Nombre son requeridos');
        return;
    }

    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, color = ? WHERE id = ?");
    $stmt->execute([$nombre, $descripcion, $color, $id]);

    jsonResponse(true, 'Categoría actualizada exitosamente');
}

function deleteCategoria()
{
    global $pdo;

    $id = $_POST['id'] ?? null;

    if (!$id) {
        jsonResponse(false, 'ID requerido');
        return;
    }

    // Check if category is used in any product
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM productos WHERE categoria_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        jsonResponse(false, 'No se puede eliminar una categoría que tiene productos asociados');
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(true, 'Categoría eliminada exitosamente');
}
