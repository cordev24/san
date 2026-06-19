<?php
require_once 'config/database.php';
$stmt = $pdo->query("DESCRIBE participantes");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
