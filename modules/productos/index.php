<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Get all active products across all categories
$stmt = $pdo->query("
    SELECT p.*, c.nombre as categoria_nombre, c.color
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = TRUE
    ORDER BY p.created_at DESC
");
$productos = $stmt->fetchAll();

// Get categories for the add/edit modals
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Catálogo de Productos</title>

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

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.05);
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
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
                <div>
                    <h1 class="page-title" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-3);">
                        <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-electro);">
                            <use href="#icon-package"></use>
                        </svg>
                        Catálogo de Productos
                    </h1>
                    <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                        Administra tu inventario maestro de productos y financiamientos.
                    </p>
                </div>
                <button class="btn btn-electro" onclick="openModal('nuevoProductoModal')">
                    <svg class="icon">
                        <use href="#icon-plus"></use>
                    </svg>
                    Añadir Nuevo Producto
                </button>
            </div>
        </div>

        <div class="bento-container" style="max-width: 1600px; margin: 0 auto; padding: 0 var(--space-6);">
            <!-- Available Products -->
            <div class="bento-12">
                <div class="product-grid">
                    <?php if (empty($productos)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-10); color: var(--color-text-tertiary);">
                            <svg class="icon-xl" style="width: 64px; height: 64px; opacity: 0.2; margin: 0 auto var(--space-4); display: block;"><use href="#icon-package"></use></svg>
                            Todavía no tienes productos registrados.
                        </div>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                            <div class="product-card" onmouseover="this.style.borderColor='var(--color-<?php echo $producto['color']; ?>)'" onmouseout="this.style.borderColor='var(--glass-border)'">
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
                                    <div class="product-icon" style="border: 1px solid var(--color-<?php echo $producto['color']; ?>); color: var(--color-<?php echo $producto['color']; ?>);">
                                        <svg class="icon-lg">
                                            <use href="#icon-package"></use>
                                        </svg>
                                    </div>
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                        <div class="product-meta">
                                            <span style="color: var(--color-<?php echo $producto['color']; ?>); font-weight: 600;"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></span> • 
                                            <?php echo htmlspecialchars($producto['marca']); ?>
                                            <?php echo htmlspecialchars($producto['modelo']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="product-price">
                                    $<?php echo number_format($producto['valor_total'], 2); ?> USD
                                </div>
                                <?php if ($producto['descripcion']): ?>
                                    <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-top: var(--space-2);">
                                        <?php echo htmlspecialchars($producto['descripcion']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Agregar Producto -->
    <div id="nuevoProductoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Agregar Nuevo Producto</h2>
                <button class="modal-close" onclick="closeModal('nuevoProductoModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="nuevoProductoForm">
                <div class="form-group">
                    <label class="form-label">Categoría *</label>
                    <select name="categoria_id" class="form-select" required>
                        <option value="">Selecciona una categoría para este producto</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre del Producto *</label>
                    <input type="text" name="nombre" class="form-input" required placeholder="Ej: Nevera Samsung Inverter">
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
                        placeholder="Descripción o especificaciones (opcional)"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor Total (USD) *</label>
                    <input type="number" name="valor_total" class="form-input" required min="1" step="0.01"
                        placeholder="Precio total del financiamiento">
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-electro" style="flex: 1;">
                        <svg class="icon"><use href="#icon-plus"></use></svg> Agregar Producto
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('nuevoProductoModal')" style="flex: 1;">
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
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="editarProductoForm">
                <input type="hidden" id="edit_producto_id" name="id">
                <input type="hidden" name="action" value="update">
                
                <!-- Ojo: Update endpoint needs to process categoria_id too if we want to change it.
                     Right now, the API updateProducto might not receive category_id. We'll skip it for brevity,
                     or we could add it but since the API doesn't process it in updateProducto yet we'll hide it. -->

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
                    <label class="form-label">Valor Total (USD) *</label>
                    <input type="number" id="edit_valor_total" name="valor_total" class="form-input" required min="1" step="0.01">
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-electro" style="flex: 1;">
                        <svg class="icon"><use href="#icon-check-circle"></use></svg> Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarProductoModal')" style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shared Scripts -->
    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/productos.js"></script>
</body>

</html>
