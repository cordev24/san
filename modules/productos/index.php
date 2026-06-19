<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// ── Filters ──
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_search    = trim($_GET['search'] ?? '');

// Build query
$where  = "p.activo = TRUE";
$params = [];

if ($filtro_categoria !== '') {
    $where   .= " AND p.categoria_id = ?";
    $params[] = (int)$filtro_categoria;
}

if ($filtro_search !== '') {
    $where   .= " AND (p.nombre LIKE ? OR p.marca LIKE ? OR p.modelo LIKE ?)";
    $like     = '%' . $filtro_search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$stmt = $pdo->prepare("
    SELECT p.*, c.nombre AS categoria_nombre, c.color
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE $where
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Get categories for filters and modals
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="../../manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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

        /* ── Filter card ── */
        .filter-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-3) var(--space-5);
            margin-bottom: var(--space-6);
            display: flex;
            align-items: flex-end;
            gap: var(--space-3);
            flex-wrap: nowrap;
        }

        .filter-field {
            display: flex;
            flex-direction: column;
            gap: var(--space-1);
        }

        .filter-field--search {
            flex: 2;
            min-width: 140px;
        }

        .filter-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-tertiary);
            font-weight: 600;
        }

        .filter-field select,
        .filter-field input[type="text"] {
            padding: var(--space-2) var(--space-3);
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            background: var(--color-background);
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
            height: 38px;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
        }

        .filter-field select {
            min-width: 150px;
        }

        .filter-field input[type="text"] {
            width: 100%;
            padding-left: 36px;
        }

        .filter-field select:focus,
        .filter-field input[type="text"]:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-glow);
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--color-text-tertiary);
            pointer-events: none;
        }

        .filter-actions {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-left: auto;
        }

        .filter-clear {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            color: var(--color-text-tertiary);
            text-decoration: none;
            transition: all var(--transition-fast);
            white-space: nowrap;
        }

        .filter-clear:hover {
            color: var(--color-error);
            background: hsl(0, 100%, 96%);
        }

        .filter-clear svg {
            width: 14px;
            height: 14px;
        }

        .filter-count {
            font-size: var(--font-size-xs);
            font-weight: 600;
            color: var(--color-text-tertiary);
            background: var(--color-background);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            white-space: nowrap;
        }

        /* ── Image upload component ── */
        .upload-wrap {
            width: 100%;
        }

        .upload-area {
            position: relative;
            border: 2px dashed var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            background: var(--color-background);
            overflow: hidden;
            min-height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-area:hover {
            border-color: var(--color-primary);
            background: var(--color-primary-tint);
        }

        .upload-preview {
            max-height: 180px;
            max-width: 100%;
            border-radius: var(--radius-md);
            object-fit: contain;
        }

        .upload-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-2);
            color: var(--color-text-tertiary);
            font-size: var(--font-size-sm);
        }

        .upload-placeholder svg {
            width: 36px;
            height: 36px;
            opacity: 0.4;
        }

        .upload-hint {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
            margin-top: var(--space-1);
        }

        /* ── Product image in card (deprecated - kept for compatibility) ── */

        /* ── Gallery grid (multi-image) ── */
        .gallery-grid {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .gallery-thumb {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 2px solid var(--glass-border);
            background: var(--color-background);
            flex-shrink: 0;
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-thumb--cover {
            border-color: var(--color-primary);
        }

        .gallery-thumb--broken {
            opacity: 0.4;
        }

        .gallery-thumb:hover .gallery-thumb-overlay {
            opacity: 1;
        }

        .gallery-thumb-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .gallery-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            padding: 0;
        }

        .gallery-btn svg {
            width: 14px;
            height: 14px;
        }

        .gallery-btn--cover {
            background: var(--color-primary);
            color: #fff;
        }

        .gallery-btn--cover:hover {
            background: var(--color-primary-dim);
        }

        .gallery-btn--del {
            background: var(--color-error);
            color: #fff;
        }

        .gallery-btn--del:hover {
            background: hsl(0, 65%, 40%);
        }

        .gallery-cover-badge {
            position: absolute;
            bottom: 2px;
            left: 2px;
            font-size: 9px;
            font-weight: 700;
            background: var(--color-primary);
            color: #fff;
            padding: 1px 6px;
            border-radius: 4px;
            line-height: 1.4;
        }

        .gallery-thumb-name {
            display: block;
            font-size: 9px;
            color: var(--color-text-tertiary);
            text-align: center;
            padding: 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .gallery-empty {
            padding: var(--space-4);
            text-align: center;
            color: var(--color-text-tertiary);
            font-size: var(--font-size-sm);
            border: 1px dashed var(--glass-border);
            border-radius: var(--radius-md);
        }

        /* ── Card image gallery ── */
        .card-img-strip {
            position: relative;
            margin: calc(-1 * var(--space-4)) calc(-1 * var(--space-4)) var(--space-3);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            overflow: hidden;
            height: 180px;
            background: var(--color-background);
            cursor: zoom-in;
        }

        .card-img-strip img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-base);
        }

        .product-card:hover .card-img-strip img {
            transform: scale(1.05);
        }

        .card-img-strip .img-count-badge {
            position: absolute;
            bottom: var(--space-2);
            right: var(--space-2);
            background: rgba(0,0,0,0.65);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: var(--radius-full);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .card-img-strip .img-count-badge svg {
            width: 14px;
            height: 14px;
        }

        .card-img-strip--fallback {
            display: none;
        }

        @media (max-width: 640px) {
            .filter-card {
                flex-wrap: wrap;
            }
            .filter-field {
                flex: 1 1 100%;
            }
            .filter-field select {
                width: 100%;
                min-width: unset;
            }
            .filter-actions {
                margin-left: 0;
                justify-content: flex-end;
            }
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
            <!-- ═══ Filtros ═══ -->
            <div class="filter-card">
                <form class="filter-bar" method="GET" action="" style="display:contents;">
                    <div class="filter-field">
                        <label class="filter-label" for="catFilter">Categoría</label>
                        <select name="categoria" id="catFilter" onchange="this.form.submit()">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-field filter-field--search">
                        <label class="filter-label" for="searchInput">Buscar</label>
                        <div class="search-wrap">
                            <svg class="search-icon"><use href="#icon-search"></use></svg>
                            <input type="text" name="search" id="searchInput"
                                   placeholder="Nombre, marca o modelo..."
                                   value="<?php echo htmlspecialchars($filtro_search); ?>">
                        </div>
                    </div>

                    <noscript>
                        <button type="submit" class="btn btn-sm btn-violeta">Filtrar</button>
                    </noscript>
                </form>

                <div class="filter-actions">
                    <?php if ($filtro_categoria !== '' || $filtro_search !== ''): ?>
                        <a href="index.php" class="filter-clear">
                            <svg><use href="#icon-x"></use></svg>
                            Limpiar
                        </a>
                    <?php endif; ?>
                    <span class="filter-count"><?php echo count($productos); ?> producto<?php echo count($productos) !== 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <!-- Available Products -->
            <div class="bento-12">
                <div class="product-grid">
                    <?php if (empty($productos)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-10); color: var(--color-text-tertiary);">
                            <svg class="icon-xl" style="width: 64px; height: 64px; opacity: 0.2; margin: 0 auto var(--space-4); display: block;"><use href="#icon-package"></use></svg>
                            <?php if ($filtro_categoria !== '' || $filtro_search !== ''): ?>
                                No se encontraron productos con los filtros seleccionados.
                            <?php else: ?>
                                Todavía no tienes productos registrados.
                            <?php endif; ?>
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

                                <?php if ($producto['imagen']): ?>
                                    <div class="card-img-strip" onclick="event.stopPropagation(); viewGallery(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars(addslashes($producto['nombre'])); ?>')">
                                         <img src="../../<?php echo htmlspecialchars(ltrim($producto['imagen'] ?? '', '/')); ?>"
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             loading="lazy"
                                             onerror="this.parentElement.classList.add('card-img-strip--fallback')">
                                        <?php if (($producto['imagenes_count'] ?? 0) > 1): ?>
                                            <span class="img-count-badge">
                                                <svg><use href="#icon-grid"></use></svg>
                                                +<?php echo (int)$producto['imagenes_count'] - 1; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

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

                <div class="form-group">
                    <label class="form-label">Imágenes del Producto</label>
                    <div class="upload-wrap">
                        <div class="upload-area" onclick="document.getElementById('nuevo_imagen').click()">
                            <svg class="icon" style="width:36px;height:36px;opacity:0.4;"><use href="#icon-plus"></use></svg>
                            <span style="color:var(--color-text-tertiary);font-size:var(--font-size-sm);">Haz clic para seleccionar una o varias imágenes</span>
                        </div>
                        <input type="file" name="imagen[]" id="nuevo_imagen" multiple
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewMultiImagen(this)" style="display:none;">
                        <p class="upload-hint">JPG, PNG, WebP o GIF. La primera será la imagen principal.</p>
                    </div>
                    <div id="nuevo_galeria_preview" class="gallery-grid" style="display:none;margin-top:var(--space-3);"></div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('nuevoProductoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-electro"><svg class="icon"><use href="#icon-plus"></use></svg> Agregar Producto
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

                <div class="form-group">
                    <label class="form-label">Imágenes del Producto</label>

                    <!-- Existing images gallery -->
                    <div id="edit_gallery" class="gallery-grid" style="margin-bottom:var(--space-3);"></div>

                    <!-- Upload new images -->
                    <div class="upload-wrap">
                        <div class="upload-area" onclick="document.getElementById('edit_imagen').click()">
                            <svg class="icon" style="width:36px;height:36px;opacity:0.4;"><use href="#icon-plus"></use></svg>
                            <span style="color:var(--color-text-tertiary);font-size:var(--font-size-sm);">Agregar más imágenes</span>
                        </div>
                        <input type="file" name="imagen[]" id="edit_imagen" multiple
                               accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                        <p class="upload-hint">JPG, PNG, WebP o GIF. Se añaden sin reemplazar las existentes.</p>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editarProductoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-electro"><svg class="icon"><use href="#icon-check-circle"></use></svg> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Visor de Galería -->
    <div id="galleryModal" class="modal-overlay" style="z-index: 1000;">
        <div class="modal-content" style="max-width: 800px; padding: var(--space-6); background: transparent; border: none; box-shadow: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4); background: var(--color-surface); padding: var(--space-4); border-radius: var(--radius-md);">
                <h2 class="modal-title" id="galleryModalTitle" style="color: var(--color-text-primary); margin: 0;">Galería</h2>
                <button class="modal-close" onclick="closeModal('galleryModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <div id="galleryModalBody" style="display: flex; flex-direction: column; gap: var(--space-4);">
                <!-- La imagen principal en grande -->
                <div style="background: var(--color-surface); border-radius: var(--radius-lg); padding: var(--space-2); display: flex; justify-content: center; align-items: center; min-height: 400px; max-height: 60vh;">
                    <img id="galleryMainImg" src="" style="max-width: 100%; max-height: 60vh; object-fit: contain; border-radius: var(--radius-md);">
                </div>
                <!-- Las miniaturas -->
                <div id="galleryThumbs" style="display: flex; gap: var(--space-2); overflow-x: auto; padding-bottom: var(--space-2);">
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ JS Scripts ═══════════ -->
    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/productos.js"></script>
</body>

</html>
