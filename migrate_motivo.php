<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE participantes ADD COLUMN motivo_salida TEXT NULL, ADD COLUMN fecha_salida DATE NULL;");
    echo "Migration successful\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
