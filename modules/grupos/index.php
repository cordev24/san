<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Fetch all active groups
$stmt = $pdo->query("
    SELECT gs.*, p.nombre as producto_nombre, p.marca as producto_marca, p.modelo as producto_modelo, c.color as categoria_color
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Gestión de Grupos San</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .page-header { padding: var(--space-8); max-width: 1600px; margin: 0 auto; }
        .page-title { font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary); margin-bottom: var(--space-2); display: flex; align-items: center; gap: var(--space-4); }

        .group-card { background: var(--color-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: var(--space-5); transition: all var(--transition-base); display: flex; flex-direction: column; gap: var(--space-4); position: relative; }
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
                    <button class="btn btn-menta" onclick="openModal('nuevoParticipanteModal')">
                        <svg class="icon"><use href="#icon-user-plus"></use></svg> Participante
                    </button>
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
                                onclick="location.href='../categoria/pagos.php?id=<?php echo $cat_id; ?>&grupo_id=<?php echo $grupo['id']; ?>'">
                                
                                <div class="card-actions">
                                    <button class="btn-action" onclick="event.stopPropagation(); editGrupo(<?php echo $grupo['id']; ?>)" title="Editar Grupo">
                                        <svg class="icon-sm"><use href="#icon-edit"></use></svg>
                                    </button>
                                    <button class="btn-action btn-action-danger" onclick="event.stopPropagation(); eliminarGrupo(<?php echo $grupo['id']; ?>)" title="Eliminar Grupo">
                                        <svg class="icon-sm"><use href="#icon-trash-2"></use></svg>
                                    </button>
                                </div>
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
            <form id="nuevoGrupoForm">
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
                            <option value="<?php echo $p['id']; ?>" data-valor="<?php echo $p['valor_total']; ?>">
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

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-secondary" style="flex: 1;">
                        <svg class="icon"><use href="#icon-check-circle"></use></svg> Crear Grupo
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('nuevoGrupoModal')" style="flex: 1;">Cancelar</button>
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
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="nuevoParticipanteForm">

                <!-- STEP 1: Cedula lookup -->
                <div id="step-cedula">
                    <p style="color: var(--color-text-tertiary); font-size: var(--font-size-sm); margin-bottom: var(--space-4);">
                        Ingresa la cédula para verificar si el participante ya existe en el sistema.
                    </p>
                    <div class="form-group" style="margin-bottom: var(--space-3);">
                        <label class="form-label">Cédula de Identidad *</label>
                        <div style="display: flex; gap: var(--space-3);">
                            <input type="text" id="lookup_cedula" class="form-input" placeholder="V-12345678" maxlength="15" style="flex: 1;"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();buscarCedula();}">
                            <button type="button" class="btn btn-outline" onclick="buscarCedula()" id="btn-buscar" style="white-space:nowrap;">
                                <svg class="icon"><use href="#icon-search"></use></svg>
                                Buscar
                            </button>
                        </div>
                        <div id="cedula-feedback" style="margin-top: var(--space-2); font-size: var(--font-size-sm);"></div>
                    </div>
                </div>

                <!-- STEP 2: Participant found — prefilled card -->
                <div id="step-found" style="display:none;">
                    <div id="found-card" style="
                        background: rgba(0,203,169,0.07);
                        border: 1px solid var(--color-menta);
                        border-radius: var(--radius-lg);
                        padding: var(--space-4);
                        margin-bottom: var(--space-4);
                        display: flex;
                        align-items: center;
                        gap: var(--space-4);
                    ">
                        <div id="found-avatar" style="
                            width: 48px; height: 48px; border-radius: 50%;
                            background: linear-gradient(135deg, var(--color-menta), var(--color-violeta));
                            display: flex; align-items: center; justify-content: center;
                            color: white; font-weight: 700; font-size: 18px; flex-shrink: 0;
                        "></div>
                        <div>
                            <div id="found-name" style="font-weight: var(--font-weight-semibold); color: var(--color-text-primary); font-size: var(--font-size-lg);"></div>
                            <div style="font-size: var(--font-size-sm); color: var(--color-text-tertiary); margin-top: 2px;">
                                <span id="found-cedula-display"></span>
                                &bull; <span id="found-tel"></span>
                            </div>
                        </div>
                        <span class="badge badge-success" style="margin-left: auto; flex-shrink:0;">
                            <span class="badge-dot"></span> Existente
                        </span>
                    </div>
                    <!-- Hidden fields populated from lookup -->
                    <input type="hidden" name="nombre"    id="p_nombre">
                    <input type="hidden" name="apellido"  id="p_apellido">
                    <input type="hidden" name="cedula"    id="p_cedula">
                    <input type="hidden" name="telefono"  id="p_telefono">
                    <input type="hidden" name="direccion" id="p_direccion">
                </div>

                <!-- STEP 3: New participant — full form (shown when not found) -->
                <div id="step-new" style="display:none;">
                    <div style="
                        background: rgba(255,180,100,0.07);
                        border: 1px solid var(--color-salmon);
                        border-radius: var(--radius-md);
                        padding: var(--space-3) var(--space-4);
                        margin-bottom: var(--space-4);
                        font-size: var(--font-size-sm);
                        color: var(--color-salmon);
                        display: flex; align-items: center; gap: var(--space-2);
                    ">
                        <svg style="width:16px;height:16px;flex-shrink:0;"><use href="#icon-alert-triangle"></use></svg>
                        Participante nuevo — completa sus datos para registrarlo.
                    </div>
                    <input type="hidden" name="cedula" id="new_cedula">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" id="new_nombre" name="nombre" class="form-input" required placeholder="Nombre">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido *</label>
                            <input type="text" id="new_apellido" name="apellido" class="form-input" required placeholder="Apellido">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" id="new_telefono" name="telefono" class="form-input" placeholder="04121234567" maxlength="15">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <textarea id="new_direccion" name="direccion" class="form-input" rows="2" placeholder="Sector, Calle..."></textarea>
                    </div>
                </div>

                <!-- Group selector — shown after lookup resolves -->
                <div id="step-group" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Grupo San a Inscribir *</label>
                        <select name="grupo_san_id" id="select_grupo" class="form-select" required>
                            <option value="">Selecciona el grupo...</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <?php if ($grupo['cupos_ocupados'] < $grupo['cupos_totales']): ?>
                                    <option value="<?php echo $grupo['id']; ?>">
                                        <?php echo htmlspecialchars($grupo['nombre']); ?> (<?php echo $grupo['cupos_ocupados']; ?>/<?php echo $grupo['cupos_totales']; ?> cupos)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: var(--space-4); margin-top: var(--space-4);">
                        <button type="button" class="btn btn-outline" style="flex:1;" onclick="resetInscripcionForm()">
                            <svg class="icon"><use href="#icon-arrow-left"></use></svg> Nueva Búsqueda
                        </button>
                        <button type="submit" class="btn btn-menta" style="flex:1;">
                            <svg class="icon"><use href="#icon-user-plus"></use></svg> Inscribir
                        </button>
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

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-secondary" style="flex: 1;">
                        <svg class="icon"><use href="#icon-check-circle"></use></svg> Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarGrupoModal')" style="flex: 1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shared Scripts -->
    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/grupos.js"></script>
    <script src="../../assets/js/participantes.js"></script>
    <script src="../../assets/js/eliminar_grupo.js"></script>

    <script>
    /* =========================================================
       INSCRIBIR PARTICIPANTE — Flujo de búsqueda por cédula
       ========================================================= */

    // Reset full modal to step-1 every time it opens
    const _origOpen = window.openModal;
    window.openModal = function(id) {
        if (id === 'nuevoParticipanteModal') resetInscripcionForm();
        _origOpen(id);
    };

    function resetInscripcionForm() {
        document.getElementById('lookup_cedula').value    = '';
        document.getElementById('cedula-feedback').innerHTML = '';
        document.getElementById('step-cedula').style.display = '';
        document.getElementById('step-found').style.display  = 'none';
        document.getElementById('step-new').style.display    = 'none';
        document.getElementById('step-group').style.display  = 'none';
        // Clear new-participant fields
        ['new_nombre','new_apellido','new_telefono','new_direccion'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.getElementById('select_grupo').value = '';
        setTimeout(() => document.getElementById('lookup_cedula').focus(), 150);
    }

    async function buscarCedula() {
        const cedula = document.getElementById('lookup_cedula').value.trim();
        if (!cedula) {
            setFeedback('Ingresa una cédula antes de buscar.', 'warn');
            return;
        }

        const btn = document.getElementById('btn-buscar');
        btn.disabled = true;
        btn.innerHTML = '<svg class="icon" style="animation:spin 0.8s linear infinite"><use href="#icon-refresh-cw"></use></svg> Buscando...';

        try {
            const res  = await fetch(`../../api/participantes.php?action=get_by_cedula&cedula=${encodeURIComponent(cedula)}`);
            const data = await res.json();

            if (data.success) {
                showFoundState(cedula, data.data.participante);
            } else {
                showNewState(cedula);
            }
        } catch(e) {
            setFeedback('Error de conexión. Intenta de nuevo.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="icon"><use href="#icon-search"></use></svg> Buscar';
        }
    }

    function showFoundState(cedula, p) {
        // Populate hidden fields
        document.getElementById('p_nombre').value    = p.nombre;
        document.getElementById('p_apellido').value  = p.apellido;
        document.getElementById('p_cedula').value    = cedula;
        document.getElementById('p_telefono').value  = p.telefono  || '';
        document.getElementById('p_direccion').value = p.direccion || '';

        // Populate display card
        const initials = (p.nombre[0] || '') + (p.apellido[0] || '');
        document.getElementById('found-avatar').textContent     = initials.toUpperCase();
        document.getElementById('found-name').textContent       = `${p.nombre} ${p.apellido}`;
        document.getElementById('found-cedula-display').textContent = cedula;
        document.getElementById('found-tel').textContent        = p.telefono || 'Sin teléfono';

        document.getElementById('step-cedula').style.display = 'none';
        document.getElementById('step-found').style.display  = '';
        document.getElementById('step-new').style.display    = 'none';
        document.getElementById('step-group').style.display  = '';
    }

    function showNewState(cedula) {
        document.getElementById('new_cedula').value = cedula;

        document.getElementById('step-cedula').style.display = 'none';
        document.getElementById('step-found').style.display  = 'none';
        document.getElementById('step-new').style.display    = '';
        document.getElementById('step-group').style.display  = '';

        setFeedback('', '');
        setTimeout(() => document.getElementById('new_nombre').focus(), 100);
    }

    function setFeedback(msg, type) {
        const el = document.getElementById('cedula-feedback');
        const colors = { warn: 'var(--color-salmon)', error: '#ff6464', ok: 'var(--color-menta)' };
        el.style.color = colors[type] || 'var(--color-text-tertiary)';
        el.textContent = msg;
    }

    // Spin keyframe for loading state
    const styleTag = document.createElement('style');
    styleTag.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(styleTag);

    // Form submit — delegates to existing participantes.js handler if present,
    // otherwise handles it directly
    document.getElementById('nuevoParticipanteForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const grupo_san_id = document.getElementById('select_grupo').value;
        if (!grupo_san_id) {
            showNotification('Selecciona un grupo San', 'error');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';

        const formData = new FormData(this);
        formData.set('action', 'create');
        formData.set('grupo_san_id', grupo_san_id);

        try {
            const res  = await fetch('../../api/participantes.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showNotification('Participante inscrito exitosamente', 'success');
                closeModal('nuevoParticipanteModal');
                setTimeout(() => location.reload(), 1200);
            } else {
                showNotification(data.message || 'Error al inscribir', 'error');
            }
        } catch(err) {
            showNotification('Error de conexión', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }
    });
    </script>
</body>

</html>
