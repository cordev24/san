<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Fetch all active groups
$stmt = $pdo->query("
    SELECT gs.*, p.nombre as producto_nombre, p.imagen as producto_imagen, p.marca as producto_marca, p.modelo as producto_modelo, c.color as categoria_color
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    WHERE gs.estado != 'finalizado'
    ORDER BY gs.fecha_inicio DESC
");
$grupos = $stmt->fetchAll();

// Fetch all products for the nuevoGrupoModal
$stmtProd = $pdo->query("
    SELECT p.id, p.nombre, p.valor_total, c.nombre as categoria_nombre 
    FROM productos p 
    JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.activo = TRUE 
    ORDER BY c.nombre, p.nombre
");
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
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
    <title>MySan - Gestión de Grupos San</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .page-header { padding: var(--space-8); max-width: 1600px; margin: 0 auto; }
        .page-title { font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary); margin-bottom: var(--space-2); display: flex; align-items: center; gap: var(--space-4); }

        .group-card { background: var(--color-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: var(--space-5); transition: all var(--transition-base); display: flex; flex-direction: column; gap: var(--space-4); position: relative; overflow: hidden; }
        .group-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-glow-secondary); }

        .group-header { display: flex; align-items: center; gap: var(--space-4); }
        .group-icon { width: 44px; height: 44px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: rgba(255,255,255,0.05); }
        
        .group-title { font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--color-text-primary); }

        .group-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: var(--space-2); padding-top: var(--space-2); border-top: 1px solid var(--glass-border); }
        .detail-item { display: flex; align-items: center; gap: var(--space-2); color: var(--color-text-secondary); font-size: var(--font-size-sm); }
        .detail-item span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .card-actions { position: absolute; top: var(--space-3); right: var(--space-3); display: flex; gap: var(--space-2); opacity: 0; transition: all var(--transition-base); z-index: 20; }
        .group-card:hover .card-actions { opacity: 1; }

        .btn-action { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); background: var(--glass-background); backdrop-filter: blur(4px); border: 1px solid var(--glass-border); color: var(--color-text-secondary); transition: all var(--transition-base); cursor: pointer; }
        .btn-action:hover { background: var(--color-surface); border-color: var(--color-violeta); color: var(--color-violeta); transform: translateY(-2px); }
        .btn-action-danger:hover { border-color: #ff6464; color: #ff6464; }
        .icon-sm { width: 16px; height: 16px; stroke-width: 2; }
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
                        <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-secondary);">
                            <use href="#icon-users"></use>
                        </svg>
                        Gestión de Grupos San
                    </h1>
                    <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                        Administra participantes y grupos globales financiados.
                    </p>
                </div>
                <div style="display: flex; gap: var(--space-4);">
                    <button class="btn btn-secondary" onclick="openModal('nuevoGrupoModal')">
                        <svg class="icon"><use href="#icon-plus"></use></svg> Grupo San
                    </button>
                </div>
            </div>
        </div>

        <div class="bento-container" style="max-width: 1600px; margin: 0 auto; padding: 0 var(--space-6);">
            <div class="bento-12">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: var(--space-4);">
                    <?php if (empty($grupos)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-10); color: var(--color-text-tertiary);">
                            <svg class="icon-xl" style="width: 64px; height: 64px; opacity: 0.2; margin: 0 auto var(--space-4); display: block;"><use href="#icon-users"></use></svg>
                            No hay grupos activos. Crea un nuevo Grupo San y asocia un producto.
                        </div>
                    <?php else: ?>
                        <?php foreach ($grupos as $grupo): ?>
                            <!-- Important: Send them to payments based on their true category/product id so pagos.php works smoothly if it relies on categoria_id -->
                            <?php 
                            // Resolving product details -> category logic
                            $stmtC = $pdo->prepare("SELECT categoria_id FROM productos WHERE id = ?");
                            $stmtC->execute([$grupo['producto_id']]);
                            $cat_id = $stmtC->fetchColumn(); 
                            ?>
                            <div class="group-card" onmouseover="this.style.borderColor='var(--color-<?php echo $grupo['categoria_color']; ?>)'" onmouseout="this.style.borderColor='var(--glass-border)'"
                                onclick="location.href='../categoria/grupo.php?id=<?php echo $cat_id; ?>&grupo_id=<?php echo $grupo['id']; ?>'">
                                
                                <div class="card-actions">
                                    <button class="btn-action" onclick="event.stopPropagation(); editGrupo(<?php echo $grupo['id']; ?>)" title="Editar Grupo">
                                        <svg class="icon-sm"><use href="#icon-edit"></use></svg>
                                    </button>
                                    <button class="btn-action btn-action-danger" onclick="event.stopPropagation(); eliminarGrupo(<?php echo $grupo['id']; ?>)" title="Eliminar Grupo">
                                        <svg class="icon-sm"><use href="#icon-trash-2"></use></svg>
                                    </button>
                                </div>
                                <?php if (!empty($grupo['producto_imagen'])): ?>
                                <div style="margin: calc(-1 * var(--space-5)) calc(-1 * var(--space-5)) 0; height: 160px; background: var(--color-background); border-bottom: 1px solid var(--glass-border); position:relative; cursor:zoom-in; overflow:hidden; padding: 12px; display: flex; align-items: center; justify-content: center;"
                                     onclick="event.stopPropagation(); viewGallery(<?php echo (int)$grupo['producto_id']; ?>, '<?php echo htmlspecialchars(addslashes($grupo['producto_nombre'])); ?>')">
                                    <img src="../../<?php echo htmlspecialchars(ltrim($grupo['producto_imagen'] ?? '', '/')); ?>" alt="<?php echo htmlspecialchars($grupo['producto_nombre']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform .3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                    <span style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;backdrop-filter:blur(4px);display:flex;align-items:center;gap:4px;">
                                        <svg style="width:11px;height:11px;stroke:#fff;stroke-width:2.5;"><use href="#icon-image"></use></svg>Ver galería
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="group-header">
                                    <div class="group-icon" style="border: 1px solid var(--color-<?php echo $grupo['categoria_color']; ?>); color: var(--color-<?php echo $grupo['categoria_color']; ?>);">
                                        <svg class="icon-lg"><use href="#icon-users"></use></svg>
                                    </div>
                                    <div class="group-title">
                                        <?php echo htmlspecialchars($grupo['nombre']); ?>
                                        <div style="font-size: var(--font-size-xs); font-weight: normal; color: var(--color-text-tertiary); margin-top:2px;">
                                            <?php echo ucfirst($grupo['estado']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="group-details">
                                    <div class="detail-item" style="grid-column: 1 / -1; margin-bottom: var(--space-2);">
                                        <svg class="icon" style="stroke: var(--color-text-tertiary);"><use href="#icon-package"></use></svg>
                                        <span title="<?php echo htmlspecialchars($grupo['producto_nombre']); ?>"><?php echo htmlspecialchars($grupo['producto_nombre']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <svg class="icon" style="stroke: var(--color-text-tertiary);"><use href="#icon-users"></use></svg>
                                        <span><?php echo $grupo['cupos_ocupados']; ?>/<?php echo $grupo['cupos_totales']; ?> Cupos</span>
                                    </div>
                                    <div class="detail-item">
                                        <svg class="icon" style="stroke: var(--color-text-tertiary);"><use href="#icon-dollar"></use></svg>
                                        <span><?php echo formatMoneyBcv($grupo['monto_cuota']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <svg class="icon" style="stroke: var(--color-text-tertiary);"><use href="#icon-calendar"></use></svg>
                                        <span><?php echo ucfirst($grupo['frecuencia']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="nuevoGrupoForm" data-siguiente-num="<?php echo count($grupos) + 1; ?>">
                <div class="form-group">
                    <label class="form-label">Producto Asociado *</label>
                    <select id="producto_id" name="producto_id" class="form-select" required>
                        <option value="">Selecciona un producto del catálogo...</option>
                        <?php 
                        $current_cat = '';
                        foreach ($productos as $p): 
                            if ($current_cat != $p['categoria_nombre']) {
                                if ($current_cat != '') echo "</optgroup>";
                                $current_cat = $p['categoria_nombre'];
                                echo "<optgroup label='" . htmlspecialchars($current_cat) . "'>";
                            }
                        ?>
                            <option value="<?php echo $p['id']; ?>" data-valor="<?php echo $p['valor_total']; ?>" data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>">
                                <?php echo htmlspecialchars($p['nombre']); ?> - $<?php echo number_format($p['valor_total'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_cat != '') echo "</optgroup>"; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre del Grupo *</label>
                    <input type="text" name="nombre" class="form-input" required placeholder="Ej: Grupo Semanal Mayo">
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
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
                    <input type="number" id="cupos_totales" name="cupos_totales" class="form-input" required min="2" max="50" value="10">
                </div>

                <div class="form-group">
                    <label class="form-label">Monto por Cuota (Calculado)</label>
                    <div style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-secondary); padding: var(--space-3);" id="monto_cuota_display">$0.00</div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('nuevoGrupoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-secondary"><svg class="icon"><use href="#icon-check-circle"></use></svg> Crear Grupo
                    </button>
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
                    <svg class="icon"><use href="#icon-x"></use></svg>
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

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarGrupoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-secondary"><svg class="icon"><use href="#icon-check-circle"></use></svg> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Shared Scripts -->
    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/grupos.js"></script>
    <script src="../../assets/js/participantes.js"></script>
    <script src="../../assets/js/eliminar_grupo.js"></script>




</body>

</html>
