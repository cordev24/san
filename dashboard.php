<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();
$tasa_bcv = getBcvRate();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MySan - Dashboard</title>

    <!-- Offline Styles -->
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
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
        $headerLogoHref   = 'dashboard.php';
        $headerLogoutHref = 'logout.php';
        include 'includes/header.php';
        ?>

        <!-- User Dashboard Content -->
        <div style="padding: var(--space-8); max-width: 1600px; margin: 0 auto;">

            <?php
            // Alerta de API caída para administradores
            if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
                $stmtApiStatus = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'bcv_api_status'");
                $statusRow = $stmtApiStatus->fetch();
                if ($statusRow && $statusRow['valor'] === 'down') {
                    // Obtener la tasa y fecha más recientes para mostrarla en el banner
                    $lastRateVal = 75.00;
                    $lastRateDate = '-';
                    $stmtLastTasa = $pdo->query("SELECT tasa, fecha FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
                    if ($rowLastTasa = $stmtLastTasa->fetch()) {
                        $lastRateVal = (float)$rowLastTasa['tasa'];
                        $lastRateDate = $rowLastTasa['fecha'];
                    }
                    ?>
                    <div class="animate-slide-up" style="
                        background: rgba(239, 68, 68, 0.1);
                        border: 1px solid var(--color-salmon);
                        border-radius: var(--radius-lg);
                        padding: var(--space-4) var(--space-6);
                        margin-bottom: var(--space-6);
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: var(--space-4);
                        backdrop-filter: blur(10px);
                    ">
                        <div style="display: flex; align-items: center; gap: var(--space-3);">
                            <div style="
                                width: 36px;
                                height: 36px;
                                border-radius: var(--radius-md);
                                background: rgba(239, 68, 68, 0.15);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: var(--color-salmon);
                                flex-shrink: 0;
                            ">
                                <svg style="width: 20px; height: 20px; stroke: currentColor; stroke-width: 2;">
                                    <use href="#icon-alert-triangle"></use>
                                </svg>
                            </div>
                            <div>
                                <h4 style="font-weight: 700; color: var(--color-text-primary); font-size: var(--font-size-sm); margin-bottom: 2px;">
                                    La API del Banco Central de Venezuela (BCV) no está disponible
                                </h4>
                                <p style="color: var(--color-text-secondary); font-size: var(--font-size-xs);">
                                    Se está utilizando la última tasa registrada de <strong>Bs. <?php echo number_format($lastRateVal, 2); ?></strong> del <strong><?php echo $lastRateDate; ?></strong>. Por favor, registre la tasa manualmente para evitar pérdidas.
                                </p>
                            </div>
                        </div>
                        <a href="modules/comprobantes/index.php" class="btn btn-salmon" style="font-size: var(--font-size-xs); padding: var(--space-2) var(--space-4); flex-shrink: 0; display: flex; align-items: center; gap: 8px;">
                            <svg style="width: 14px; height: 14px; stroke: currentColor;"><use href="#icon-edit"></use></svg>
                            Registrar Tasa Manual
                        </a>
                    </div>
                    <?php
                }
            }
            ?>

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
                        SELECT gs.*, p.nombre as producto_nombre, p.imagen as producto_imagen, p.categoria_id, c.nombre as categoria_nombre, c.color as categoria_color
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
                            style="animation-delay: <?php echo $delay; ?>ms; cursor: pointer; overflow: hidden;"
                            onclick="location.href='modules/categoria/grupo.php?id=<?php echo $cat_id; ?>&grupo_id=<?php echo $grupo_id; ?>'">

                            <?php if (!empty($grupo['producto_imagen'])): ?>
                            <div style="margin: -24px -24px 16px -24px; height: 160px; background: var(--color-background); border-bottom: 1px solid var(--glass-border); position:relative; cursor:zoom-in; overflow:hidden; padding: 12px; display: flex; align-items: center; justify-content: center;"
                                 onclick="event.stopPropagation(); viewGallery(<?php echo (int)$grupo['producto_id']; ?>, '<?php echo htmlspecialchars(addslashes($grupo['producto_nombre'])); ?>')">
                                <img src="<?php echo htmlspecialchars(ltrim($grupo['producto_imagen'] ?? '', '/')); ?>" alt="<?php echo htmlspecialchars($grupo['producto_nombre']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform .3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <span style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;backdrop-filter:blur(4px);display:flex;align-items:center;gap:4px;">
                                    <svg style="width:11px;height:11px;stroke:#fff;stroke-width:2.5;"><use href="#icon-image"></use></svg>Ver galería
                                </span>
                            </div>
                            <?php endif; ?>


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


                    <!-- Gestión de Usuarios Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 100; ?>ms;"
                        onclick="location.href='modules/usuarios/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-primary);">
                                    <use href="#icon-user"></use>
                                </svg>
                                Gestión de Usuarios
                            </div>
                        </div>
                        <div class="bento-content">
                            Administra los usuarios del sistema.
                        </div>
                        <div class="module-stats">
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Total Usuarios</span>
                                <?php
                                $stmtUserCount = $pdo->query("SELECT COUNT(*) FROM usuarios");
                                $countUsers = $stmtUserCount->fetchColumn();
                                ?>
                                <span class="stat-value" style="font-size: var(--font-size-xl);"><?php echo $countUsers; ?></span>
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
                            Generación de recibos.
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

                    <!-- Morosidad Module -->
                    <div class="bento-box bento-4 module-card animate-slide-up" style="animation-delay: <?php echo $delay + 400; ?>ms;"
                        onclick="location.href='modules/morosidad/index.php'">
                        <div class="bento-header">
                            <div class="bento-title">
                                <svg class="module-icon bento-icon" style="stroke: var(--color-salmon);">
                                    <use href="#icon-alert-triangle"></use>
                                </svg>
                                Morosidad
                            </div>
                        </div>
                        <div class="bento-content">
                            Control de deudores y cuotas vencidas.
                        </div>
                        <div class="module-stats">
                            <?php
                            $stmtMorDeud = $pdo->query("
                                SELECT COUNT(DISTINCT p.id)
                                FROM participantes p
                                JOIN pagos pg ON pg.participante_id = p.id
                                WHERE pg.estado IN ('pendiente','atrasado')
                                  AND pg.fecha_vencimiento < CURDATE()
                                  AND p.activo = 1
                            ");
                            $countMorDeud = (int)$stmtMorDeud->fetchColumn();
                            $stmtMorTotal = $pdo->query("
                                SELECT COALESCE(SUM(pg.monto), 0)
                                FROM participantes p
                                JOIN pagos pg ON pg.participante_id = p.id
                                WHERE pg.estado IN ('pendiente','atrasado')
                                  AND pg.fecha_vencimiento < CURDATE()
                                  AND p.activo = 1
                            ");
                            $countMorTotal = (float)$stmtMorTotal->fetchColumn();
                            ?>
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label" style="color: var(--color-salmon);">Deudores</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl); color: var(--color-salmon);"><?php echo $countMorDeud; ?></span>
                            </div>
                            <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                                <span class="stat-label">Total Adeudado</span>
                                <span class="stat-value" style="font-size: var(--font-size-xl);">$<?php echo number_format($countMorTotal, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/shared.js"></script>
</body>

</html>