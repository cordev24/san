<?php
/**
 * API de Usuarios
 *
 * Endpoints:
 *   GET ?action=get&id=X  → JSON con datos del usuario
 */
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            break;
        }
        $stmt = $pdo->prepare("SELECT id, username, nombre, email, rol FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        if ($usuario) {
            echo json_encode($usuario);
        } else {
            echo json_encode(['error' => 'Usuario no encontrado']);
        }
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
