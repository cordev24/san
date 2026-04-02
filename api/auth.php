<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(false, 'Usuario y contraseña son requeridos');
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password, nombre FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(false, 'Usuario o contraseña incorrectos');
    }

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre'] = $user['nombre'];

        jsonResponse(true, 'Inicio de sesión exitoso', [
            'username' => $user['username'],
            'nombre' => $user['nombre']
        ]);
    } else {
        jsonResponse(false, 'Usuario o contraseña incorrectos');
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Error del servidor: ' . $e->getMessage());
}
?>