<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();
$tasa_bcv = getBcvRate();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Dashboard</title>

    <!-- Offline Styles -->
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        .header {
            padding: var(--space-6) var(--space-8);
            background: var(--glass-background);
            backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: var(--z-dropdown);
        }

        .header-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-logo {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            background: linear-gradient(135deg, var(--color-violeta), var(--color-menta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
        }

        .user-role {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
        }

        .module-card {
            cursor: pointer;
            min-height: 200px;
            display: flex;
            flex-direction: column;
        }

        .module-icon {
            width: 48px;
            height: 48px;
            margin-bottom: var(--space-4);
        }

        .module-stats {
            margin-top: auto;
            padding-top: var(--space-4);
            border-top: 1px solid var(--glass-border);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--space-2);
            font-size: var(--font-size-sm);
        }

        .stat-label {
            color: var(--color-text-tertiary);
        }

        .stat-value {
            color: var(--color-text-primary);
            font-weight: var(--font-weight-semibold);
        }
    </style>
</head>

<body>
    <!-- Icon Sprite -->
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <!-- Header -->
        <?php
        $headerLogoHref         = 'dashboard.php';
        $headerLogoutHref       = 'logout.php';
        $headerCrearUsuarioHref = 'crear-usuario.php';
        $showCrearUsuario       = true;
        include 'includes/header.php';
        ?>

        <!-- User Dashboard Content -->
        <div style="padding: var(--space-8); max-width: 1600px; margin: 0 auto;">

            <!-- SECTION: CATEGORÍAS DE GRUPOS SAN -->
            <div class="dashboard-section" style="margin-bottom: var(--space-10);">
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4);">
                    <svg class="icon" style="width: 24px; height: 24px; color: var(--color-text-secondary);"><use href="#icon-users"></use></svg>
                    <h2 style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary);">Últimos Grupos San Activos</h2>
                </div>
                
                <div class="bento-container">
                    <!-- Dynamic Groups Loop -->
                    <?php
                    $stmt = $pdo->query("
                        SELECT gs.*, p.nombre as producto_nombre, p.categoria_id, c.nombre as categoria_nombre, c.color as categoria_color
                        FROM grupos_san gs
                        JOIN productos p ON gs.producto_id = p.id
                        JOIN categorias c ON p.categoria_id = c.id
                        WHERE gs.estado != 'finalizado'
                        ORDER BY gs.fecha_inicio DESC
                        LIMIT 6
                    ");
                    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($grupos)):
                    ?>
                        <div class="bento-box bento-12" style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-8);">
                            No hay grupos San activos en este momento. Dirígete a la sección de categorías para crear uno.
                        </div>
                    <?php
                    endif;

                    $delay = 0;
                    foreach ($grupos as $grupo):
                        $cat_id = $grupo['categoria_id'];
                        $grupo_id = $grupo['id'];
                        $cat_color = htmlspecialchars($grupo['categoria_color']);
                        
                        $bento_class = 'bento-4';
                        if ($cat_color == 'salmon') {
                            $bento_class .= ' bento-box--salmon';
                        } elseif ($cat_color == 'menta') {
                            $bento_class .= ' bento-box--menta';
                        }
                    ?>
                        <!-- <?php echo htmlspecialchars($grupo['nombre']); ?> Group -->
                        <div class="bento-box <?php echo $bento_class; ?> module-card animate-slide-up"
                            style="animation-delay: <?php echo $delay; ?>ms; cursor: pointer;"
                            onclick="location.href='modules/categoria/grupo.php?id=<?php echo $cat_id; ?>&grupo_id=<?php echo $grupo_id; ?>'">

                            <div class="bento-header">
                                <div class="bento-title">
                                    <svg class="module-icon bento-icon" style="stroke: var(--color-<?php echo $cat_color ? $cat_color : 'violeta'; ?>);">
                                        <use href="#icon-users"></use>
                                    </svg>
                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                </div>
                                <?php if ($grupo['estado'] == 'abierto'): ?>
                                    <span class="badge badge-success">
                                        <span class="badge-dot"></span>
                                        Abierto
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <span class="badge-dot"></span>
                                        <?php echo ucfirst($grupo['estado']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="bento-content" style="color: var(--color-<?php echo $cat_color ? $cat_color : 'violeta'; ?>);">
                                <?php echo htmlspecialchars($grupo['categoria_nombre']); ?> &raquo; <?php echo htmlspecialchars($grupo['producto_nombre']); ?>
                            </div>
                            <div class="module-stats">
                                <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                    <span class="stat-label">Cupos Llenos</span>
                                    <span class="stat-value" style="font-size: var(--font-size-xl);">
                                        <?php echo $grupo['cupos_ocupados']; ?> / <?php echo $grupo['cupos_totales']; ?>
                                    </span>
                                </div>
                                <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                    <span class="stat-label">Cuota</span>
                                    <span class="stat-value" style="font-size: var(--font-size-xl);">
                                        $<?php echo number_format($grupo['monto_cuota'], 2); ?>
                                    </span>
                                </div>
                                <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                    <span class="stat-label">Frecuencia</span>
                                    <span class="stat-value" style="font-size: var(--font-size-xl);">
                                        <?php echo ucfirst($grupo['frecuencia']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php
                        $delay += 100;
                    endforeach;
                    ?>
                </div>
            </div>


            <!-- SECTION: ADMINISTRACIÓN Y UTILIDADES -->
            <div class="dashboard-section">
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4);">
                    <svg class="icon" style="width: 24px; height: 24px; color: var(--color-text-secondary);"><use href="#icon-settings"></use></svg>
                    <h2 style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary);">Administración</h2>
                </div>
                
                <div class="bento-container">
                    <!-- Catálogo de Productos Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay; ?>ms;"
                        onclick="location.href='modules/productos/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-electro);">
                                    <use href="#icon-package"></use>
                                </svg>
                                Catálogo de Productos
                            </div>
                        </div>
                        <div class="bento-content">
                            Administra todos los productos financiables.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Total Productos</span>
                                <?php
                                $stmtProdCount = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = TRUE");
                                $countProd = $stmtProdCount->fetchColumn();
                                ?>
                                <span class="stat-value" style="font-size: var(--font-size-xl);"><?php echo $countProd; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Grupos San Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 100; ?>ms;"
                        onclick="location.href='modules/grupos/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-secondary);">
                                    <use href="#icon-users"></use>
                                </svg>
                                Gestión de Grupos San
                            </div>
                        </div>
                        <div class="bento-content">
                            Administra todos los San, participantes y pagos.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Grupos Activos</span>
                                <?php
                                $stmtGroupCount = $pdo->query("SELECT COUNT(*) FROM grupos_san WHERE estado != 'finalizado'");
                                $countGroup = $stmtGroupCount->fetchColumn();
                                ?>
                                <span class="stat-value" style="font-size: var(--font-size-xl);"><?php echo $countGroup; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Directorio de Participantes Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 100; ?>ms;"
                        onclick="location.href='modules/participantes/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-primary);">
                                    <use href="#icon-users"></use>
                                </svg>
                                Directorio de Participantes
                            </div>
                        </div>
                        <div class="bento-content">
                            Administra la base de datos global de participantes.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Total Participantes Únicos</span>
                                <?php
                                // Count unique cedulas across all groups
                                $stmtClientCount = $pdo->query("SELECT COUNT(DISTINCT cedula) FROM participantes");
                                $countClients = $stmtClientCount->fetchColumn();
                                ?>
                                <span class="stat-value" style="font-size: var(--font-size-xl);"><?php echo $countClients; ?></span>
                            </div>
                        </div>
                    </div>


                    <!-- Gestión de Categorias Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay; ?>ms;"
                        onclick="location.href='modules/categorias_admin/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-menta);">
                                    <use href="#icon-grid"></use>
                                </svg>
                                Configurar Categorías
                            </div>
                        </div>
                        <div class="bento-content">
                            Añade, edita, cambia colores y elimina categorías.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Categorías Activas</span>
                                <?php
                                $stmtCountCat = $pdo->query("SELECT COUNT(*) FROM categorias");
                                $countCat = $stmtCountCat->fetchColumn();
                                ?>
                                <span class="stat-value" style="font-size: var(--font-size-xl);"><?php echo $countCat; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Turnos Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 100; ?>ms;"
                        onclick="location.href='modules/turnos/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-violeta);">
                                    <use href="#icon-dice"></use>
                                </svg>
                                Sorteos de Turnos
                            </div>
                        </div>
                        <div class="bento-content">
                            Sistema de sorteo y asignación de turnos.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Pendientes</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl);">7</span>
                            </div>
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Próximo</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl);">15 Feb</span>
                            </div>
                        </div>
                    </div>

                    <!-- Comprobantes Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 200; ?>ms;"
                        onclick="location.href='modules/comprobantes/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-salmon);">
                                    <use href="#icon-printer"></use>
                                </svg>
                                Recibos y Comprobantes
                            </div>
                        </div>
                        <div class="bento-content">
                            Generación de recibos y certificados.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Generados</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl);">156</span>
                            </div>
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Mes Actual</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl);">23</span>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Pagos Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 300; ?>ms;"
                        onclick="location.href='modules/pagos/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-menta);">
                                    <use href="#icon-credit-card"></use>
                                </svg>
                                Gestión de Pagos
                            </div>
                        </div>
                        <div class="bento-content">
                            Registra y controla los pagos de cuotas por grupo.
                        </div>
                        <div class="module-stats">
                            <?php
                            $stmtPagPend = $pdo->query("SELECT COUNT(*) FROM pagos WHERE estado = 'pendiente'");
                            $countPagPend = $stmtPagPend->fetchColumn();
                            $stmtPagAtras = $pdo->query("SELECT COUNT(*) FROM pagos WHERE estado = 'atrasado'");
                            $countPagAtras = $stmtPagAtras->fetchColumn();
                            ?>
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Pendientes</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl);"><?php echo $countPagPend; ?></span>
                            </div>
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label" style="color: var(--color-salmon);">Atrasados</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl); color: var(--color-salmon);"><?php echo $countPagAtras; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>