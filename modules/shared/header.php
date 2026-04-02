<?php
/**
 * Shared Layout Component for MySan Modules
 * Provides consistent header, sidebar, and navigation across all admin modules
 * 
 * Usage: include this file at the beginning of each module page after require_login()
 */

if (!isset($user)) {
    $user = getCurrentUser();
}

$currentModule = basename(dirname($_SERVER['PHP_SELF']));
$moduleTitles = [
    'electrodomesticos' => 'Electrodomesticos',
    'telefonia' => 'Telefonia',
    'motocicletas' => 'Motocicletas',
    'turnos' => 'Turnos',
    'comprobantes' => 'Comprobantes'
];
$moduleTitle = $moduleTitles[$currentModule] ?? 'Modulo';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - <?php echo $moduleTitle; ?></title>

    <!-- Shared Styles -->
    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <!-- Layout Styles -->
    <link rel="stylesheet" href="../../assets/css/layout.css">

    <style>
        /* Module-specific overrides can go here */
        <?php echo isset($extraStyles) ? $extraStyles : ''; ?>
    </style>
</head>

<body>
    <!-- Icon Sprite -->
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="../../dashboard.php" class="sidebar-logo">
                MySan
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../../dashboard.php" class="sidebar-link <?php echo ($currentModule === 'dashboard') ? 'active' : ''; ?>">
                <svg class="icon"><use href="#icon-grid"></use></svg>
                Dashboard
            </a>
            
            <div class="sidebar-section">
                <span class="sidebar-section-title">Modulos</span>
                
                <a href="../electrodomesticos/index.php" class="sidebar-link <?php echo ($currentModule === 'electrodomesticos') ? 'active' : ''; ?>">
                    <svg class="icon"><use href="#icon-cpu"></use></svg>
                    Electrodomesticos
                </a>
                
                <a href="../telefonia/index.php" class="sidebar-link <?php echo ($currentModule === 'telefonia') ? 'active' : ''; ?>">
                    <svg class="icon"><use href="#icon-smartphone"></use></svg>
                    Telefonia
                </a>
                
                <a href="../motocicletas/index.php" class="sidebar-link <?php echo ($currentModule === 'motocicletas') ? 'active' : ''; ?>">
                    <svg class="icon"><use href="#icon-motocycle"></use></svg>
                    Motocicletas
                </a>
                
                <a href="../turnos/index.php" class="sidebar-link <?php echo ($currentModule === 'turnos') ? 'active' : ''; ?>">
                    <svg class="icon"><use href="#icon-calendar"></use></svg>
                    Turnos
                </a>
                
                <a href="../comprobantes/index.php" class="sidebar-link <?php echo ($currentModule === 'comprobantes') ? 'active' : ''; ?>">
                    <svg class="icon"><use href="#icon-file-text"></use></svg>
                    Comprobantes
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../../logout.php" class="sidebar-link">
                <svg class="icon"><use href="#icon-log-out"></use></svg>
                Cerrar Sesion
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        <!-- Top Header -->
        <header class="top-header">
            <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                <svg class="icon"><use href="#icon-menu"></use></svg>
            </button>
            
            <div class="header-title">
                <h1><?php echo $moduleTitle; ?></h1>
            </div>
            
            <div class="header-user">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></span>
                    <span class="user-role">Administrador</span>
                </div>
                <a href="../../logout.php" class="btn btn-outline btn-sm">
                    <svg class="icon"><use href="#icon-log-out"></use></svg>
                    Salir
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
