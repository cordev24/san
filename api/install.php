<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() > 0) {
        jsonResponse(false, 'El sistema ya está instalado');
    }
} catch (Exception $e) {
    // En caso de que la base de datos no tenga la tabla usuarios, 
    // asumimos que igual podemos intentar continuar si las tablas están allí 
    // y solo hay un error temporal, aunque si no hay tabla el INSERT fallará.
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$email = $_POST['email'] ?? '';
$pregunta_secreta = $_POST['pregunta_secreta'] ?? '';
$respuesta_secreta = $_POST['respuesta_secreta'] ?? '';

if (empty($username) || empty($password) || empty($nombre) || empty($pregunta_secreta) || empty($respuesta_secreta)) {
    jsonResponse(false, 'Todos los campos marcados con * son obligatorios');
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, email, rol, pregunta_secreta, respuesta_secreta) VALUES (?, ?, ?, ?, 'admin', ?, ?)");
    $stmt->execute([$username, $hashedPassword, $nombre, $email, $pregunta_secreta, $respuesta_secreta]);
    
    // Auto-login
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['rol'] = 'admin';

    jsonResponse(true, 'Instalación completada con éxito. Redirigiendo...');
} catch (PDOException $e) {
    jsonResponse(false, 'Error al crear el usuario administrador: ' . $e->getMessage());
}
?>
