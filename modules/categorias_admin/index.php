<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Obtener categorías
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY id ASC");
$categoriasList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Gestión de Categorías</title>

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

        .category-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            transition: all var(--transition-base);
            cursor: pointer;
            position: relative;
        }

        .category-card:hover {
            transform: translateY(-4px);
            border-color: var(--color-secondary);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }
        
        .category-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--glass-background);
        }

        /* Card Actions */
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

        .category-card:hover .card-actions {
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
                <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-menta);">
                    <use href="#icon-grid"></use>
                </svg>
                Gestión de Categorías
            </h1>
            <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                Añade, edita o elimina tipos de cuentas (ej. Teléfonos, Vivienda, Ropa).
            </p>
        </div>

        <div class="bento-container">
            <!-- Action Buttons -->
            <div class="bento-12" style="display: flex; gap: var(--space-4); margin-bottom: var(--space-4);">
                <button class="btn btn-menta" onclick="openModal('nuevaCategoriaModal')">
                    <svg class="icon">
                        <use href="#icon-plus"></use>
                    </svg>
                    Nueva Categoría
                </button>
            </div>

            <!-- Categories -->
            <div class="bento-12">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Categorías Existentes</div>
                    </div>
                    <div class="bento-content">
                        <?php if (empty($categoriasList)): ?>
                            <p style="text-align: center; color: var(--color-text-tertiary);">No hay categorías.</p>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-4);">
                                <?php foreach ($categoriasList as $cat): ?>
                                    <div class="category-card" style="border-color: var(--color-<?php echo htmlspecialchars($cat['color']); ?>)40;">
                                        <div class="card-actions">
                                            <button class="btn-action" onclick="event.stopPropagation(); loadCategoriaForEdit(<?php echo $cat['id']; ?>)" title="Editar">
                                                <svg class="icon-sm"><use href="#icon-edit"></use></svg>
                                            </button>
                                            <button class="btn-action btn-action-danger" onclick="event.stopPropagation(); deleteCategoria(<?php echo $cat['id']; ?>)" title="Eliminar">
                                                <svg class="icon-sm"><use href="#icon-trash-2"></use></svg>
                                            </button>
                                        </div>
                                        <div class="category-header">
                                            <div class="category-icon" style="color: var(--color-<?php echo htmlspecialchars($cat['color']); ?>); border: 1px solid var(--color-<?php echo htmlspecialchars($cat['color']); ?>);">
                                                <svg class="icon-lg" style="stroke: currentColor;"><use href="#icon-package"></use></svg>
                                            </div>
                                            <div>
                                                <h3 style="font-weight: 600; font-size: var(--font-size-lg);"><?php echo htmlspecialchars($cat['nombre']); ?></h3>
                                                <span style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">Color: <?php echo htmlspecialchars($cat['color']); ?></span>
                                            </div>
                                        </div>
                                        <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm);">
                                            <?php echo htmlspecialchars($cat['descripcion']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nueva Categoría -->
    <div id="nuevaCategoriaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Nueva Categoría</h2>
                <button class="modal-close" onclick="closeModal('nuevaCategoriaModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="nuevaCategoriaForm">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-input" required placeholder="Ej: Viajes">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Color *</label>
                    <select name="color" class="form-select" required>
                        <option value="violeta">Violeta</option>
                        <option value="menta">Menta</option>
                        <option value="salmon">Salmón</option>
                    </select>
                </div>
                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
                        Guardar
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('nuevaCategoriaModal')" style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Categoría -->
    <div id="editarCategoriaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Categoría</h2>
                <button class="modal-close" onclick="closeModal('editarCategoriaModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="editarCategoriaForm">
                <input type="hidden" name="id" id="edit_cat_id">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="edit_cat_nombre" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" id="edit_cat_descripcion" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Color *</label>
                    <select name="color" id="edit_cat_color" class="form-select" required>
                        <option value="violeta">Violeta</option>
                        <option value="menta">Menta</option>
                        <option value="salmon">Salmón</option>
                    </select>
                </div>
                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-menta" style="flex: 1;">
                        Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarCategoriaModal')" style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/js/shared.js"></script>
    <script>
        document.getElementById('nuevaCategoriaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            try {
                const response = await fetch('../../api/categorias.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        });

        async function loadCategoriaForEdit(id) {
            try {
                const response = await fetch(`../../api/categorias.php?action=get&id=${id}`);
                const data = await response.json();
                if (data.success) {
                    const cat = data.data.categoria;
                    document.getElementById('edit_cat_id').value = cat.id;
                    document.getElementById('edit_cat_nombre').value = cat.nombre;
                    document.getElementById('edit_cat_descripcion').value = cat.descripcion;
                    document.getElementById('edit_cat_color').value = cat.color;
                    openModal('editarCategoriaModal');
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error al cargar categoría', 'error');
            }
        }

        document.getElementById('editarCategoriaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            try {
                const response = await fetch('../../api/categorias.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        });

        async function deleteCategoria(id) {
            if (!confirm('¿Seguro que deseas eliminar esta categoría? Solo se podrá eliminar si no tiene productos asociados.')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            try {
                const response = await fetch('../../api/categorias.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }
    </script>
</body>
</html>
