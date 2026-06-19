<?php
/**
 * Script de utilidad para resetear la base de datos desde la consola (CLI).
 * Ejecuta el contenido de reset_data.sql utilizando la conexión PDO de la aplicación.
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la línea de comandos (CLI).\n");
}

require_once __DIR__ . '/config/database.php';

$sqlFile = __DIR__ . '/reset_data.sql';
if (!file_exists($sqlFile)) {
    die("Error: No se encontró el archivo reset_data.sql en la raíz del proyecto.\n");
}

echo "Leyendo archivo SQL...\n";
$sql = file_get_contents($sqlFile);

try {
    echo "Restableciendo base de datos a su estado original...\n";
    
    // MySQL PDO permite la ejecución de múltiples sentencias en una sola llamada a exec()
    $pdo->exec($sql);
    
    echo "¡Base de datos reseteada con éxito!\n";
    echo "Se han eliminado todos los registros y se restauraron las categorías y el usuario admin (pass: 1234).\n";
} catch (PDOException $e) {
    die("Error al resetear la base de datos: " . $e->getMessage() . "\n");
}
