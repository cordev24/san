<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Get categoria ID from URL
$categoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header("Location: ../../dashboard.php");
    exit;
}

// Get category products
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre, c.color
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.categoria_id = ? AND p.activo = TRUE
");
$stmt->execute([$categoria_id]);
$productos = $stmt->fetchAll();

// Get active groups
$stmt = $pdo->prepare("
    SELECT gs.*, p.nombre as producto_nombre, p.marca as producto_marca, p.modelo as producto_modelo
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    WHERE p.categoria_id = ? AND gs.estado != 'finalizado'
");
$stmt->execute([$categoria_id]);
$grupos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - <?php echo htmlspecialchars($categoria['nombre']); ?></title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .page-header {
            padding: var(--space-8);
            max-width: 1600px;
            margin: 0 auto;
        }

        .page-title {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-2);
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .breadcrumb {
            display: flex;
            gap: var(--space-2);
            color: var(--color-text-tertiary);
            font-size: var(--font-size-sm);
        }

        .breadcrumb a {
            color: var(--color-electro);
            transition: color var(--transition-base);
        }

        .breadcrumb a:hover {
            color: var(--color-secondary);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--space-4);
        }

        .product-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            transition: all var(--transition-base);
            cursor: pointer;
        }

        .product-card:hover {
            border-color: var(--color-electro);
            transform: translateY(-4px);
            box-shadow: var(--shadow-glow-primary);
        }

        .product-header {
            display: flex;
            align-items: flex-start;
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }

        .product-icon {
            width: 48px;
            height: 48px;
            background: var(--color-electro-glow);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .product-info h3 {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-1);
        }

        .product-meta {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
        }

        .product-price {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-electro);
            margin: var(--space-2) 0;
        }

        /* ── Group Card Premium ── */
        .card-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-5);
        }

        .group-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all 0.25s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .group-card:hover {
            border-color: var(--color-violeta);
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(170, 100, 255, 0.18);
        }

        .group-card-banner {
            height: 6px;
            background: linear-gradient(90deg, var(--color-violeta), var(--color-menta));
        }

        .group-card-body {
            padding: var(--space-5);
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
            flex: 1;
        }

        .group-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-3);
        }

        .group-header-left {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            flex: 1;
            min-width: 0;
        }

        .group-icon {
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, rgba(170,100,255,0.15), rgba(100,220,170,0.1));
            border: 1px solid rgba(170,100,255,0.25);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .group-title-wrap {
            min-width: 0;
        }

        .group-title {
            font-size: var(--font-size-md);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .group-product-name {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Status badge on card */
        .group-status-badge {
            flex-shrink: 0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(100, 220, 170, 0.12);
            color: var(--color-menta);
            border: 1px solid rgba(100, 220, 170, 0.35);
        }

        /* Stats row */
        .group-stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-3);
        }

        .group-stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: var(--space-3);
        }

        .group-stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-tertiary);
            margin-bottom: var(--space-1);
        }

        .group-stat-value {
            font-size: var(--font-size-md);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
        }

        /* Cupos progress bar */
        .group-cupos-section {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }

        .group-cupos-label {
            display: flex;
            justify-content: space-between;
            font-size: var(--font-size-xs);
            color: var(--color-text-secondary);
        }

        .progress-bar-track {
            height: 6px;
            background: rgba(255,255,255,0.07);
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--color-violeta), var(--color-menta));
            transition: width 0.6s ease;
        }

        /* Card action strip */
        .group-card-actions {
            display: flex;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-5);
            border-top: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.02);
        }

        .btn-card-action {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: var(--space-2) var(--space-3);
            border-radius: var(--radius-md);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            text-decoration: none;
        }

        .btn-card-participants {
            background: rgba(100, 220, 170, 0.1);
            color: var(--color-menta);
            border-color: rgba(100, 220, 170, 0.25);
        }

        .btn-card-participants:hover {
            background: rgba(100, 220, 170, 0.2);
            border-color: var(--color-menta);
            transform: translateY(-1px);
        }

        .btn-card-payments {
            background: rgba(170, 100, 255, 0.1);
            color: var(--color-violeta);
            border-color: rgba(170, 100, 255, 0.25);
        }

        .btn-card-payments:hover {
            background: rgba(170, 100, 255, 0.2);
            border-color: var(--color-violeta);
            transform: translateY(-1px);
        }

        .btn-card-edit {
            width: 32px;
            flex: none;
            background: rgba(255,255,255,0.04);
            color: var(--color-text-secondary);
            border-color: var(--glass-border);
        }

        .btn-card-edit:hover {
            background: rgba(255,255,255,0.08);
            color: var(--color-text-primary);
        }

        .btn-card-delete {
            width: 32px;
            flex: none;
            background: rgba(255,100,100,0.07);
            color: #ff8080;
            border-color: rgba(255,100,100,0.2);
        }

        .btn-card-delete:hover {
            background: rgba(255,100,100,0.15);
            color: #ff6464;
            border-color: #ff6464;
        }

        /* Product card actions (keep existing) */
        .card-actions {
            position: absolute;
            top: var(--space-3);
            right: var(--space-3);
            display: flex;
            gap: var(--space-2);
            opacity: 0;
            transition: all var(--transition-base);
            z-index: 20;
        }

        .product-card:hover .card-actions {
            opacity: 1;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--glass-background);
            backdrop-filter: blur(4px);
            border: 1px solid var(--glass-border);
            color: var(--color-text-secondary);
            transition: all var(--transition-base);
            cursor: pointer;
        }

        .btn-action:hover {
            background: var(--color-surface);
            border-color: var(--color-violeta);
            color: var(--color-violeta);
            transform: translateY(-2px);
        }

        .btn-action-danger:hover {
            border-color: #ff6464;
            color: #ff6464;
        }

        .icon-sm {
            width: 16px;
            height: 16px;
            stroke-width: 2;
        }
    </style>
</head>

<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <?php
        $headerLogoHref     = '../../dashboard.php';
        $headerLogoutHref   = '../../logout.php';
        $headerBackUrl      = '../../dashboard.php';
        $headerBackLabel    = 'Volver al Dashboard';
        include '../../includes/header.php';
        ?>

        <div class="page-header" style="padding: var(--space-6); margin-bottom: var(--space-4); border-bottom: 1px solid var(--glass-border);">
            <h1 class="page-title" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-3);">
                <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-<?php echo htmlspecialchars($categoria['color']); ?>);">
                    <use href="#icon-cpu"></use>
                </svg>
                <?php echo htmlspecialchars($categoria['nombre']); ?>
            </h1>
            <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                Administración de grupos para <?php echo htmlspecialchars(strtolower($categoria['nombre'])); ?>.
            </p>
        </div>

        <div class="bento-container">
            <!-- Action Buttons -->
            <div class="bento-12"
                style="display: flex; gap: var(--space-4); margin-bottom: var(--space-4); flex-wrap: wrap;">
                <button class="btn btn-violeta" onclick="openModal('nuevoGrupoModal')">
                    <svg class="icon">
                        <use href="#icon-plus"></use>
                    </svg>
                    Nuevo Grupo San
                </button>
                <button class="btn btn-menta" onclick="openModal('nuevoParticipanteModal')">
                    <svg class="icon">
                        <use href="#icon-users"></use>
                    </svg>
                    Inscribir Participante
                </button>
                <button class="btn btn-salmon" onclick="location.href='pagos.php?id=<?php echo $categoria_id; ?>'">
                    <svg class="icon">
                        <use href="#icon-dollar"></use>
                    </svg>
                    Gestión de Pagos
                </button>
                <button class="btn btn-outline" onclick="openModal('nuevoProductoModal')">
                    <svg class="icon">
                        <use href="#icon-package"></use>
                    </svg>
                    Agregar Producto
                </button>
            </div>

            <!-- Active Groups -->
            <div class="bento-12">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Grupos Activos</div>
                        <span class="badge badge-success">
                            <span class="badge-dot"></span>
                            <?php echo count($grupos); ?> grupos
                        </span>
                    </div>
                    <div class="bento-content">
                        <?php if (empty($grupos)): ?>
                            <p style="text-align: center; padding: var(--space-8); color: var(--color-text-tertiary);">
                                No hay grupos activos. Crea uno nuevo para comenzar.
                            </p>
                        <?php else: ?>
                            <div class="card-list">
                                <?php foreach ($grupos as $grupo):
                                    $pct = $grupo['cupos_totales'] > 0
                                        ? round(($grupo['cupos_ocupados'] / $grupo['cupos_totales']) * 100)
                                        : 0;
                                    $lleno = $grupo['cupos_ocupados'] >= $grupo['cupos_totales'];
                                ?>
                                    <div class="group-card">
                                        <div class="group-card-banner"></div>
                                        <!-- Clickable body → grupo detail page -->
                                        <div class="group-card-body"
                                             style="cursor:pointer;"
                                             onclick="location.href='grupo.php?id=<?php echo $categoria_id; ?>&grupo_id=<?php echo $grupo['id']; ?>'">
                                            <!-- Header -->
                                            <div class="group-header">
                                                <div class="group-header-left">
                                                    <div class="group-icon">
                                                        <svg class="icon-lg" style="stroke: var(--color-violeta);">
                                                            <use href="#icon-users"></use>
                                                        </svg>
                                                    </div>
                                                    <div class="group-title-wrap">
                                                        <div class="group-title"><?php echo htmlspecialchars($grupo['nombre']); ?></div>
                                                        <div class="group-product-name"><?php echo htmlspecialchars($grupo['producto_nombre']); ?></div>
                                                    </div>
                                                </div>
                                                <span class="group-status-badge"><?php echo ucwords(str_replace('_', ' ', $grupo['estado'] ?? 'abierto')); ?></span>
                                            </div>

                                            <!-- Stats row -->
                                            <div class="group-stats-row">
                                                <div class="group-stat">
                                                    <div class="group-stat-label">Cuota</div>
                                                    <div class="group-stat-value" style="color: var(--color-violeta); font-size: var(--font-size-sm);"><?php echo formatMoneyBcv($grupo['monto_cuota']); ?></div>
                                                </div>
                                                <div class="group-stat">
                                                    <div class="group-stat-label">Frecuencia</div>
                                                    <div class="group-stat-value" style="font-size: var(--font-size-sm);"><?php echo ucfirst($grupo['frecuencia']); ?></div>
                                                </div>
                                            </div>

                                            <!-- Cupos progress -->
                                            <div class="group-cupos-section">
                                                <div class="group-cupos-label">
                                                    <span>Cupos ocupados</span>
                                                    <span style="font-weight: 600; color: <?php echo $lleno ? 'var(--color-error)' : 'var(--color-primary)'; ?>">
                                                        <?php echo $grupo['cupos_ocupados']; ?> / <?php echo $grupo['cupos_totales']; ?>
                                                    </span>
                                                </div>
                                                <div class="progress-bar-track">
                                                    <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%; background: <?php echo $lleno ? 'var(--color-error)' : 'linear-gradient(90deg, var(--color-primary), var(--color-secondary))'; ?>;"></div>
                                                </div>
                                            </div>

                                            <!-- Ver detalles hint -->
                                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;
                                                        font-size:11px;color:var(--color-text-tertiary);margin-top:var(--space-1);">
                                                Ver detalles
                                                <svg style="width:11px;height:11px;stroke:currentColor;stroke-width:2.5;"><use href="#icon-arrow-right"></use></svg>
                                            </div>
                                        </div>

                                        <!-- Action strip -->
                                        <div class="group-card-actions">
                                            <a href="pagos.php?id=<?php echo $categoria_id; ?>&grupo_id=<?php echo $grupo['id']; ?>" class="btn-card-action btn-card-payments">
                                                <svg style="width:13px;height:13px;stroke-width:2.5;"><use href="#icon-dollar"></use></svg>
                                                Ver Pagos
                                            </a>
                                            <button class="btn-card-action btn-card-edit" onclick="editGrupo(<?php echo $grupo['id']; ?>)" title="Editar">
                                                <svg style="width:13px;height:13px;stroke-width:2.5;"><use href="#icon-edit"></use></svg>
                                            </button>
                                            <button class="btn-card-action btn-card-delete" onclick="eliminarGrupo(<?php echo $grupo['id']; ?>)" title="Eliminar">
                                                <svg style="width:13px;height:13px;stroke-width:2.5;"><use href="#icon-trash-2"></use></svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div><!-- /.card-list -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Available Products -->
            <div class="bento-12">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Productos Disponibles</div>
                    </div>
                    <div class="bento-content">
                        <div class="product-grid">
                            <?php foreach ($productos as $producto): ?>
                                <div class="product-card" style="position: relative; overflow: hidden;">
                                    <div class="card-actions">
                                        <button class="btn-action"
                                            onclick="event.stopPropagation(); loadProductoForEdit(<?php echo $producto['id']; ?>)"
                                            title="Editar">
                                            <svg class="icon-sm">
                                                <use href="#icon-edit"></use>
                                            </svg>
                                        </button>
                                        <button class="btn-action btn-action-danger"
                                            onclick="event.stopPropagation(); deleteProducto(<?php echo $producto['id']; ?>)"
                                            title="Eliminar">
                                            <svg class="icon-sm">
                                                <use href="#icon-trash-2"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="product-header">
                                        <div class="product-icon">
                                            <svg class="icon-lg" style="stroke: var(--color-violeta);">
                                                <use href="#icon-package"></use>
                                            </svg>
                                        </div>
                                        <div class="product-info">
                                            <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                            <div class="product-meta">
                                                <?php echo htmlspecialchars($producto['marca']); ?>
                                                <?php echo htmlspecialchars($producto['modelo']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-price">
                                        <?php echo formatMoneyBcv($producto['valor_total']); ?>
                                    </div>
                                    <?php if ($producto['descripcion']): ?>
                                        <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm);">
                                            <?php echo htmlspecialchars($producto['descripcion']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nuevo Grupo San -->
    <div id="nuevoGrupoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Crear Nuevo Grupo San</h2>
                <button class="modal-close" onclick="closeModal('nuevoGrupoModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="nuevoGrupoForm">
                <div class="form-group">
                    <label class="form-label">Producto *</label>
                    <select id="producto_id" name="producto_id" class="form-select" required>
                        <option value="">-- Selecciona un producto --</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id']; ?>"
                                data-valor="<?php echo $producto['valor_total']; ?>">
                                <?php echo htmlspecialchars($producto['nombre']); ?> -
                                <?php echo formatMoneyBcv($producto['valor_total']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre del Grupo *</label>
                    <input type="text" name="nombre" class="form-input" required
                        placeholder="Ej: Grupo Neveras Enero 2026">
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-input" required
                        value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Frecuencia de Pago *</label>
                    <select name="frecuencia" class="form-select" required>
                        <option value="quincenal">Quincenal</option>
                        <option value="mensual">Mensual</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Participantes / Cuotas *</label>
                    <input type="number" id="cupos_totales" name="cupos_totales" class="form-input" required min="2"
                        max="50" value="10">
                </div>

                <div class="form-group">
                    <label class="form-label">Monto por Cuota (Calculado)</label>
                    <div style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); 
                                color: var(--color-violeta); padding: var(--space-3);" id="monto_cuota_display">$0.00
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-violeta" style="flex: 1;">
                        <svg class="icon">
                            <use href="#icon-check-circle"></use>
                        </svg>
                        Crear Grupo
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('nuevoGrupoModal')"
                        style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Inscribir Participante -->
    <div id="nuevoParticipanteModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Inscribir Participante</h2>
                <button class="modal-close" onclick="closeModal('nuevoParticipanteModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="nuevoParticipanteForm">
                <div class="form-group">
                    <label class="form-label">Grupo San *</label>
                    <select name="grupo_san_id" class="form-select" required>
                        <option value="">-- Selecciona un grupo --</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <?php if ($grupo['cupos_ocupados'] < $grupo['cupos_totales']): ?>
                                <option value="<?php echo $grupo['id']; ?>">
                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                    (<?php echo $grupo['cupos_ocupados']; ?>/<?php echo $grupo['cupos_totales']; ?> cupos)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Cédula *</label>
                    <input type="text" id="cedula" name="cedula" class="form-input" required placeholder="12345678" maxlength="10">
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-input" required placeholder="Nombre">
                </div>

                <div class="form-group">
                    <label class="form-label">Apellido *</label>
                    <input type="text" name="apellido" class="form-input" required placeholder="Apellido">
                </div>

                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" class="form-input" placeholder="04121234567"
                        maxlength="11">
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <textarea name="direccion" class="form-input" rows="3" placeholder="Dirección completa"></textarea>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
                        <svg class="icon">
                            <use href="#icon-user-plus"></use>
                        </svg>
                        Inscribir
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('nuevoParticipanteModal')"
                        style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Agregar Producto -->
    <div id="nuevoProductoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Agregar Producto</h2>
                <button class="modal-close" onclick="closeModal('nuevoProductoModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="nuevoProductoForm">
                <input type="hidden" name="categoria_id" value="<?php echo $categoria_id; ?>">

                <div class="form-group">
                    <label class="form-label">Nombre del Producto *</label>
                    <input type="text" name="nombre" class="form-input" required placeholder="Ej: Nevera Samsung">
                </div>

                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-input" placeholder="Ej: Samsung">
                </div>

                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" class="form-input" placeholder="Ej: RT38K5930SL">
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-input" rows="3"
                        placeholder="Descripción del producto (opcional)"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor Total *</label>
                    <input type="number" name="valor_total" class="form-input" required min="1" step="0.01"
                        placeholder="15000.00">
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-violeta" style="flex: 1;">
                        <svg class="icon">
                            <use href="#icon-plus"></use>
                        </svg>
                        Agregar
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('nuevoProductoModal')"
                        style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Producto -->
    <div id="editarProductoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Producto</h2>
                <button class="modal-close" onclick="closeModal('editarProductoModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="editarProductoForm">
                <input type="hidden" id="edit_producto_id" name="id">
                <input type="hidden" name="action" value="update">

                <div class="form-group">
                    <label class="form-label">Nombre del Producto *</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" id="edit_marca" name="marca" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" id="edit_modelo" name="modelo" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-input" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor Total *</label>
                    <input type="number" id="edit_valor_total" name="valor_total" class="form-input" required min="1"
                        step="0.01">
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-violeta" style="flex: 1;">
                        <svg class="icon">
                            <use href="#icon-check-circle"></use>
                        </svg>
                        Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarProductoModal')"
                        style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    </form>
    </div>
    </div>

    <!-- Modal: Editar Grupo -->
    <div id="editarGrupoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Grupo San</h2>
                <button class="modal-close" onclick="closeModal('editarGrupoModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="editarGrupoForm">
                <input type="hidden" id="edit_grupo_id" name="id">

                <div class="form-group">
                    <label class="form-label">Nombre del Grupo *</label>
                    <input type="text" id="edit_grupo_nombre" name="nombre" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Inicio *</label>
                    <input type="date" id="edit_grupo_fecha_inicio" name="fecha_inicio" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="edit_grupo_estado" name="estado" class="form-select">
                        <option value="en_espera">En Espera</option>
                        <option value="abierto">Abierto</option>
                        <option value="cerrado">Cerrado</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-violeta" style="flex: 1;">
                        <svg class="icon">
                            <use href="#icon-check-circle"></use>
                        </svg>
                        Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarGrupoModal')"
                        style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shared Scripts -->
    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/grupos.js?v=3"></script>
    <script src="../../assets/js/participantes.js"></script>
    <script src="../../assets/js/productos.js"></script>
    <script src="../../assets/js/eliminar_grupo.js"></script>
</body>

</html>