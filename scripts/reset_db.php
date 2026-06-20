<?php
/**
 * Script de limpieza de base de datos
 * Solo puede ser ejecutado desde la línea de comandos (CLI) por seguridad.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Error: Este script es destructivo y solo puede ejecutarse desde la terminal (CLI) por razones de seguridad.\n");
}

// Cargar la configuración de la BD
require_once __DIR__ . '/../config/database.php';

echo "\n=============================================\n";
echo "  MYSAN - RESET DE BASE DE DATOS\n";
echo "=============================================\n\n";

echo "⚠️  ADVERTENCIA CRÍTICA: Estás a punto de ELIMINAR TODOS los datos transaccionales y de usuarios.\n";
echo "Esto dejará el sistema como nuevo. Los productos y categorías se mantendrán intactos.\n\n";



try {
    echo "\nIniciando limpieza...\n";
    
    // Desactivar validación de llaves foráneas temporalmente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Lista de tablas a truncar (reiniciar)
    $tablas_a_limpiar = [
        'comprobantes',
        'turnos',
        'pagos',
        'participantes',
        'grupos_san',
        'notificaciones',
        'tasas_cambio',
        'usuarios'
    ];

    foreach ($tablas_a_limpiar as $tabla) {
        $pdo->exec("TRUNCATE TABLE $tabla");
        echo "✓ Tabla '$tabla' limpiada.\n";
    }

    // Activar de nuevo la validación
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "\n=============================================\n";
    echo "✅ LIMPIEZA COMPLETADA CON ÉXITO\n";
    echo "=============================================\n";
    echo "El sistema ha quedado como nuevo.\n";
    echo "Como la tabla 'usuarios' está vacía, al abrir la web serás \n";
    echo "redirigido a la pantalla de instalación para crear tu administrador.\n";
    echo "=============================================\n\n";

} catch (PDOException $e) {
    // Si algo falla, intentamos restaurar los checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\n❌ ERROR CRÍTICO durante la limpieza:\n" . $e->getMessage() . "\n";
}
