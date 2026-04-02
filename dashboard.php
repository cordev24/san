<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();
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
        <header class="header">
            <div class="header-content">
                <a href="dashboard.php" class="header-logo">MySan</a>
                <div class="header-user">
                    <div class="user-info">
                        <div class="user-name">
                            <?php echo htmlspecialchars($user['nombre']); ?>
                        </div>
                        <div class="user-role">Administrador</div>
                    </div>
                    <a href="crear-usuario.php" class="btn btn-violeta">
                        <svg class="icon">
                            <use href="#icon-user"></use>
                        </svg>
                        Crear Usuario
                    </a>
                    <a href="logout.php" class="btn btn-outline">
                        <svg class="icon">
                            <use href="#icon-log-out"></use>
                        </svg>
                        Salir
                    </a>
                </div>
            </div>
        </header>

        <!-- Bento Grid Dashboard -->
        <div class="bento-container" style="padding-top: var(--space-8);">

            <!-- Electrodomésticos Module -->
            <div class="bento-box bento-6 module-card animate-slide-up"
                onclick="location.href='modules/electrodomesticos/index.php'">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg class="module-icon bento-icon" style="stroke: var(--color-violeta);">
                            <use href="#icon-package"></use>
                        </svg>
                        Electrodomésticos
                    </div>
                </div>
                <div class="bento-content">
                    Gestión de grupos para neveras, lavadoras, televisores y más.
                </div>
                <div class="module-stats">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM grupos_san gs JOIN productos p ON gs.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Electrodomésticos' AND gs.estado != 'finalizado'");
                    $stmt->execute();
                    $grupos_count = $stmt->fetch()['count'];

                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participantes part JOIN grupos_san gs ON part.grupo_san_id = gs.id JOIN productos p ON gs.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Electrodomésticos' AND part.activo = TRUE");
                    $stmt->execute();
                    $part_count = $stmt->fetch()['count'];
                    ?>
                    <div class="stat-item">
                        <span class="stat-label">Grupos Activos</span>
                        <span class="stat-value"><?php echo $grupos_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Participantes</span>
                        <span class="stat-value"><?php echo $part_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Telefonía Module -->
            <div class="bento-box bento-6 bento-box--menta module-card animate-slide-up" style="animation-delay: 100ms;"
                onclick="location.href='modules/telefonia/index.php'">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg class="module-icon bento-icon" style="stroke: var(--color-menta);">
                            <use href="#icon-smartphone"></use>
                        </svg>
                        Telefonía
                    </div>
                </div>
                <div class="bento-content">
                    Administración de Sans para smartphones de alta gama.
                </div>
                <div class="module-stats">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM grupos_san gs JOIN productos p ON gs.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Telefonía' AND gs.estado != 'finalizado'");
                    $stmt->execute();
                    $grupos_count = $stmt->fetch()['count'];

                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participantes part JOIN grupos_san gs ON part.grupo_san_id = gs.id JOIN productos p ON gs.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Telefonía' AND part.activo = TRUE");
                    $stmt->execute();
                    $part_count = $stmt->fetch()['count'];
                    ?>
                    <div class="stat-item">
                        <span class="stat-label">Grupos Activos</span>
                        <span class="stat-value"><?php echo $grupos_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Participantes</span>
                        <span class="stat-value"><?php echo $part_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Motocicletas Module (Double Width) -->
            <div class="bento-box bento-12 bento-box--salmon module-card animate-slide-up"
                style="animation-delay: 200ms;" onclick="location.href='modules/motocicletas/index.php'">
                <div class="bento-header">
                    <div class="bento-title">
                        <div class="bento-icon-container"
                            style="background: rgba(255, 128, 128, 0.1); color: var(--color-salmon);">
                            <svg class="icon-xl">
                                <use href="#icon-motorcycle"></use>
                            </svg>
                        </div> Motocicletas
                    </div>
                    <span class="badge badge-warning">
                        <span class="badge-dot"></span>
                        Alto Valor
                    </span>
                </div>
                <div class="bento-content">
                    Control de marcas, cilindradas y cuotas para motocicletas. El módulo de mayor valor del sistema.
                </div>
                <div class="module-stats"
                    style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM grupos_san gs JOIN productos p ON gs.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Motocicletas' AND gs.estado != 'finalizado'");
                    $stmt->execute();
                    $grupos_count = $stmt->fetch()['count'];

                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participantes part JOIN grupos_san gs ON part.grupo_san_id = gs.id JOIN productos p ON gs.producto_id = p.id JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Motocicletas' AND part.activo = TRUE");
                    $stmt->execute();
                    $part_count = $stmt->fetch()['count'];

                    $stmt = $pdo->prepare("SELECT SUM(p.valor_total) as total FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE c.nombre = 'Motocicletas' AND p.activo = TRUE");
                    $stmt->execute();
                    $valor_total = $stmt->fetch()['total'] ?? 0;
                    ?>
                    <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                        <span class="stat-label">Grupos Activos</span>
                        <span class="stat-value"
                            style="font-size: var(--font-size-xl);"><?php echo $grupos_count; ?></span>
                    </div>
                    <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                        <span class="stat-label">Participantes</span>
                        <span class="stat-value"
                            style="font-size: var(--font-size-xl);"><?php echo $part_count; ?></span>
                    </div>
                    <div class="stat-item" style="flex-direction: column; align-items: flex-start;">
                        <span class="stat-label">Valor Total</span>
                        <span class="stat-value" style="font-size: var(--font-size-xl);">Bs
                            <?php echo number_format($valor_total, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Gestión de Turnos Module -->
            <div class="bento-box bento-6 module-card animate-slide-up" style="animation-delay: 300ms;"
                onclick="location.href='modules/turnos/index.php'">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg class="module-icon bento-icon" style="stroke: var(--color-violeta);">
                            <use href="#icon-dice"></use>
                        </svg>
                        Gestión de Turnos
                    </div>
                </div>
                <div class="bento-content">
                    Sistema de sorteo y asignación de turnos para entrega de productos.
                </div>
                <div class="module-stats">
                    <div class="stat-item">
                        <span class="stat-label">Sorteos Pendientes</span>
                        <span class="stat-value">7</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Próximo Sorteo</span>
                        <span class="stat-value">15 Feb</span>
                    </div>
                </div>
            </div>

            <!-- Comprobantes Module -->
            <div class="bento-box bento-6 module-card animate-slide-up" style="animation-delay: 400ms;"
                onclick="location.href='modules/comprobantes/index.php'">
                <div class="bento-header">
                    <div class="bento-title">
                        <svg class="module-icon bento-icon" style="stroke: var(--color-menta);">
                            <use href="#icon-printer"></use>
                        </svg>
                        Comprobantes
                    </div>
                </div>
                <div class="bento-content">
                    Generación de recibos de pago y certificados de entrega.
                </div>
                <div class="module-stats">
                    <div class="stat-item">
                        <span class="stat-label">Recibos Generados</span>
                        <span class="stat-value">156</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Este Mes</span>
                        <span class="stat-value">23</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>