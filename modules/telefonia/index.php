<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Get categoria ID
$stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = 'Telefonia'");
$stmt->execute();
$categoria = $stmt->fetch();
$categoria_id = $categoria['id'];

// Get telefonia products
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre, c.color
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE c.nombre = 'Telefonia' AND p.activo = TRUE
");
$stmt->execute();
$productos = $stmt->fetchAll();

// Get active groups
$stmt = $pdo->prepare("
    SELECT gs.*, p.nombre as producto_nombre, p.marca as producto_marca, p.modelo as producto_modelo
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    WHERE c.nombre = 'Telefonia' AND gs.estado != 'finalizado'
");
$stmt->execute();
$grupos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Telefonia</title>

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
            color: var(--color-violeta);
            transition: color var(--transition-base);
        }

        .breadcrumb a:hover {
            color: var(--color-menta);
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
            position: relative;
            overflow: hidden;
        }

        .product-card:hover {
            border-color: var(--color-menta);
            transform: translateY(-4px);
            box-shadow: var(--shadow-glow-menta);
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
            background: var(--color-menta-glow);
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
            color: var(--color-menta);
            margin: var(--space-2) 0;
        }

        /* Group Card Styles */
        .group-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            transition: all var(--transition-base);
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
            position: relative;
        }

        .group-card:hover {
            border-color: var(--color-menta);
            transform: translateX(4px);
            box-shadow: var(--shadow-glow-menta);
        }

        .group-header {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .group-icon {
            width: 44px;
            height: 44px;
            background: var(--color-menta-glow);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .group-title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
        }

        .group-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: var(--space-2);
            padding-top: var(--space-2);
            border-top: 1px solid var(--glass-border);
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
        }

        .detail-item span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Card Actions Premium */
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

        .product-card:hover .card-actions,
        .group-card:hover .card-actions {
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
            border-color: var(--color-menta);
            color: var(--color-menta);
            transform: translateY(-2px);
        }

        .btn-action-danger:hover {
            border-color: #ff6464;
            color: #ff6464;
        }
    </style>
</head>

<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="../../dashboard.php">Dashboard</a>
                <span>/</span>
                <span>Telefonia</span>
            </div>
            <h1 class="page-title">
                <svg class="icon-xl" style="stroke: var(--color-menta);">
                    <use href="#icon-smartphone"></use>
                </svg>
                Telefonia
            </h1>
            <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                Administración de grupos para smartphones y accesorios.
            </p>
        </div>

        <div class="bento-container">
            <!-- Action Buttons -->
            <div class="bento-12"
                style="display: flex; gap: var(--space-4); margin-bottom: var(--space-4); flex-wrap: wrap;">
                <button class="btn btn-menta" onclick="openModal('nuevoGrupoModal')">
                    <svg class="icon">
                        <use href="#icon-plus"></use>
                    </svg>
                    Nuevo Grupo San
                </button>
                <button class="btn btn-violeta" onclick="openModal('nuevoParticipanteModal')">
                    <svg class="icon">
                        <use href="#icon-users"></use>
                    </svg>
                    Inscribir Participante
                </button>
                <button class="btn btn-salmon" onclick="location.href='pagos.php'">
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
                <div class="bento-box bento-box--menta">
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
                                <?php foreach ($grupos as $grupo): ?>
                                    <div class="group-card"
                                        onclick="location.href='pagos.php?grupo_id=<?php echo $grupo['id']; ?>'">
                                        <div class="card-actions">
                                            <button class="btn-action"
                                                onclick="event.stopPropagation(); editGrupo(<?php echo $grupo['id']; ?>)"
                                                title="Editar Grupo">
                                                <svg class="icon">
                                                    <use href="#icon-edit"></use>
                                                </svg>
                                            </button>
                                            <button class="btn-action btn-action-danger"
                                                onclick="event.stopPropagation(); eliminarGrupo(<?php echo $grupo['id']; ?>)"
                                                title="Eliminar Grupo">
                                                <svg class="icon" style="width: 16px; height: 16px;">
                                                    <use href="#icon-trash-2"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="group-header">
                                            <div class="group-icon">
                                                <svg class="icon-lg" style="stroke: var(--color-menta);">
                                                    <use href="#icon-smartphone"></use>
                                                </svg>
                                            </div>
                                            <div class="group-title"><?php echo htmlspecialchars($grupo['nombre']); ?></div>
                                        </div>
                                        <div class="group-details">
                                            <div class="detail-item">
                                                <svg class="icon" style="stroke: var(--color-text-tertiary);">
                                                    <use href="#icon-smartphone"></use>
                                                </svg>
                                                <span
                                                    title="<?php echo htmlspecialchars($grupo['producto_nombre']); ?>"><?php echo htmlspecialchars($grupo['producto_nombre']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <svg class="icon" style="stroke: var(--color-text-tertiary);">
                                                    <use href="#icon-users"></use>
                                                </svg>
                                                <span><?php echo $grupo['cupos_ocupados']; ?>/<?php echo $grupo['cupos_totales']; ?>
                                                    Cupos</span>
                                            </div>
                                            <div class="detail-item">
                                                <svg class="icon" style="stroke: var(--color-text-tertiary);">
                                                    <use href="#icon-dollar"></use>
                                                </svg>
                                                <span>Bs <?php echo number_format($grupo['monto_cuota'], 2); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <svg class="icon" style="stroke: var(--color-text-tertiary);">
                                                    <use href="#icon-calendar"></use>
                                                </svg>
                                                <span><?php echo ucfirst($grupo['frecuencia']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Available Products -->
            <div class="bento-12">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Smartphones Disponibles</div>
                    </div>
                    <div class="bento-content">
                        <div class="product-grid">
                            <?php foreach ($productos as $producto): ?>
                                <div class="product-card">
                                    <div class="card-actions">
                                        <button class="btn-action"
                                            onclick="event.stopPropagation(); loadProductoForEdit(<?php echo $producto['id']; ?>)"
                                            title="Editar">
                                            <svg class="icon" style="width: 16px; height: 16px;">
                                                <use href="#icon-edit"></use>
                                            </svg>
                                        </button>
                                        <button class="btn-action btn-action-danger"
                                            onclick="event.stopPropagation(); deleteProducto(<?php echo $producto['id']; ?>)"
                                            title="Eliminar">
                                            <svg class="icon" style="width: 16px; height: 16px;">
                                                <use href="#icon-trash-2"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="product-header">
                                        <div class="product-icon">
                                            <svg class="icon-lg" style="stroke: var(--color-menta);">
                                                <use href="#icon-smartphone"></use>
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
                                        Bs <?php echo number_format($producto['valor_total'], 2); ?>
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
                                Bs <?php echo number_format($producto['valor_total'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre del Grupo *</label>
                    <input type="text" name="nombre" class="form-input" required
                        placeholder="Ej: Grupo iPhone Enero 2026">
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
                                color: var(--color-menta); padding: var(--space-3);" id="monto_cuota_display">$0.00
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
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
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-input" required placeholder="Nombre">
                </div>

                <div class="form-group">
                    <label class="form-label">Apellido *</label>
                    <input type="text" name="apellido" class="form-input" required placeholder="Apellido">
                </div>

                <div class="form-group">
                    <label class="form-label">Cédula *</label>
                    <input type="text" id="cedula" name="cedula" class="form-input" required placeholder="12345678"
                        maxlength="10">
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
                <h2 class="modal-title">Agregar Smartphone</h2>
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
                    <input type="text" name="nombre" class="form-input" required placeholder="Ej: iPhone 15 Pro">
                </div>

                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-input" placeholder="Ej: Apple">
                </div>

                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" class="form-input" placeholder="Ej: A2848">
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-input" rows="3"
                        placeholder="Descripción del producto (opcional)"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor Total *</label>
                    <input type="number" name="valor_total" class="form-input" required min="1" step="0.01"
                        placeholder="1200.00">
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
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
                <h2 class="modal-title">Editar Smartphone</h2>
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
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
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
                    <label class="form-label">Estado</label>
                    <select id="edit_grupo_estado" name="estado" class="form-select">
                        <option value="abierto">Abierto</option>
                        <option value="cerrado">Cerrado</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
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

    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/grupos.js?v=3"></script>
    <script src="../../assets/js/participantes.js"></script>
    <script src="../../assets/js/productos.js"></script>
    <script src="../../assets/js/eliminar_grupo.js"></script>
</body>

</html>