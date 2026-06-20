<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        header('Location: install.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: install.php');
    exit;
}

header('Location: login.php');
exit;
?>