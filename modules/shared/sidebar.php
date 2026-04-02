<?php
/**
 * Sidebar Component - Include in all admin module pages
 * Provides consistent navigation across all modules
 * 
 * Usage: include this at the top of each module's <body>, before main-content
 */

$currentModule = basename(dirname($_SERVER['PHP_SELF']));
$moduleTitles = [
    'electrodomesticos' => 'Electrodomesticos',
    'telefonia' => 'Telefonia',
    'motocicletas' => 'Motocicletas',
    'turnos' => 'Turnos',
    'comprobantes' => 'Comprobantes'
];
?>
<div class="page-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="../../dashboard.php" class="sidebar-logo">MySan</a>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../../dashboard.php" class="sidebar-link">
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

    <!-- Main Content -->
    <div class="main-content-with-sidebar">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Toggle menu">
                    <svg class="icon"><use href="#icon-menu"></use></svg>
                </button>
                <div class="header-title">
                    <h1><?php echo $moduleTitles[$currentModule] ?? 'Modulo'; ?></h1>
                </div>
            </div>
            
            <div class="header-right">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></span>
                    <span class="user-role">Administrador</span>
                </div>
                <a href="../../logout.php" class="btn btn-outline btn-sm">
                    <svg class="icon" style="width:16px;height:16px;"><use href="#icon-log-out"></use></svg>
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <div class="main-content" style="padding: var(--space-6);">
