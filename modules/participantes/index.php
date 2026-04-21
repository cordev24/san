<?php
require_once '../../config/database.php';
requireLogin();

// Fetch clients globally
$stmt = $pdo->query("
    SELECT 
        cedula, 
        MAX(nombre) as nombre, 
        MAX(apellido) as apellido, 
        MAX(telefono) as telefono, 
        MAX(direccion) as direccion,
        COUNT(DISTINCT grupo_san_id) as total_grupos,
        MAX(activo) as estado_activo
    FROM participantes 
    GROUP BY cedula
    ORDER BY MAX(created_at) DESC
");
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Directorio de Participantes</title>

    <!-- Offline Styles -->
    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .participantes-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: var(--space-4);
        }

        .participantes-table th {
            background: rgba(0,0,0,0.2);
            color: var(--color-text-tertiary);
            padding: var(--space-3) var(--space-4);
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--glass-border);
        }

        .participantes-table td {
            padding: var(--space-4);
            border-bottom: 1px solid var(--glass-border);
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
            vertical-align: middle;
        }

        .participantes-table tr:last-child td {
            border-bottom: none;
        }

        .participantes-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .client-name-cell {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .client-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--color-primary), var(--color-violeta));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .client-info {
            display: flex;
            flex-direction: column;
        }

        .client-title {
            color: var(--color-text-primary);
            font-weight: var(--font-weight-medium);
            font-size: var(--font-size-base);
        }
    </style>
</head>

<body>
    <!-- Icon Sprite -->
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <!-- Global Header -->
    <?php
    $headerLogoHref   = '../../dashboard.php';
    $headerLogoutHref = '../../logout.php';
    $headerBackUrl    = '../../dashboard.php';
    $headerBackLabel  = 'Volver al Dashboard';
    include '../../includes/header.php';
    ?>

    <div class="main-content">
        <div style="padding: var(--space-8); max-width: 1200px; margin: 0 auto;">
            
            <div class="dashboard-section" style="margin-bottom: var(--space-6);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-4);">
                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                        <svg class="icon" style="width: 24px; height: 24px; color: var(--color-primary);"><use href="#icon-users"></use></svg>
                        <h2 style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary);">
                            Directorio Global de Participantes
                        </h2>
                    </div>
                </div>
            </div>

            <div class="bento-box">
                <div class="bento-header">
                    <div class="bento-title">Registro Único de Participantes</div>
                    <span class="badge badge-success">
                        <span class="badge-dot"></span>
                        <?php echo count($participantes); ?> registros
                    </span>
                </div>
                <div class="bento-content" style="padding: 0;">
                    <?php if (empty($participantes)): ?>
                        <div style="padding: var(--space-10); text-align: center; color: var(--color-text-tertiary);">
                            <svg class="icon" style="width: 48px; height: 48px; margin-bottom: var(--space-4); opacity: 0.5;"><use href="#icon-users"></use></svg>
                            <p>No hay participantes registrados en el sistema global.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="participantes-table">
                                <thead>
                                    <tr>
                                        <th>Participante</th>
                                        <th>Cédula</th>
                                        <th>Teléfono</th>
                                        <th>Dirección</th>
                                        <th>Historial</th>
                                        <th style="width: 80px; text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participantes as $cli): ?>
                                    <tr>
                                        <td>
                                            <div class="client-name-cell">
                                                <div class="client-avatar">
                                                    <?php echo strtoupper(substr($cli['nombre'], 0, 1) . substr($cli['apellido'], 0, 1)); ?>
                                                </div>
                                                <div class="client-info">
                                                    <span class="client-title"><?php echo htmlspecialchars($cli['nombre'] . ' ' . $cli['apellido']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; color: var(--color-text-primary);">
                                                <?php echo htmlspecialchars($cli['cedula']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($cli['telefono'] ?: '-'); ?></td>
                                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($cli['direccion'] ?: '-'); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info" style="color: var(--color-primary); background: rgba(0, 203, 169, 0.1);">
                                                <svg style="width:11px; height:11px; stroke-width: 3;"><use href="#icon-grid"></use></svg>
                                                En <?php echo $cli['total_grupos']; ?> Grupo(s)
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: var(--space-2); justify-content: flex-end;">
                                                <a href="perfil.php?cedula=<?php echo htmlspecialchars($cli['cedula']); ?>" class="btn btn-outline" style="padding: 6px; min-width: 0; min-height: 0; color: var(--color-primary);" title="Ver Perfil Completo">
                                                    <svg class="icon" style="width:14px; height:14px; stroke-width: 2.5; fill: none; stroke: currentColor;"><use href="#icon-eye"></use></svg>
                                                </a>
                                                <button class="btn btn-outline" style="padding: 6px; min-width: 0; min-height: 0; color: var(--color-warning);" title="Editar Info Global" onclick="openEditModal('<?php echo htmlspecialchars($cli['cedula']); ?>')">
                                                    <svg class="icon" style="width:14px; height:14px; stroke-width: 2.5; fill: none; stroke: currentColor;"><use href="#icon-edit"></use></svg>
                                                </button>
                                                <button class="btn btn-outline" style="padding: 6px; min-width: 0; min-height: 0; color: var(--color-error);" title="Inhabilitar Participante" onclick="disableGlobal('<?php echo htmlspecialchars($cli['cedula']); ?>')">
                                                    <svg class="icon" style="width:14px; height:14px; stroke-width: 2.5; fill: none; stroke: currentColor;"><use href="#icon-trash-2"></use></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    
    <!-- Modal Edit Global Info -->
    <div class="modal" id="editGlobalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Información del Participante</h3>
                <button class="modal-close" onclick="closeModal('editGlobalModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <form id="editGlobalForm">
                <input type="hidden" name="action" value="update_global">
                <input type="hidden" id="edit_cedula" name="cedula">
                
                <div class="form-group" style="margin-bottom: var(--space-4);">
                    <label class="form-label" style="opacity: 0.7;">Nombre Completo (Solo Lectura)</label>
                    <input type="text" id="edit_nombre_completo" class="form-input" disabled style="background: rgba(255,255,255,0.05); color: var(--color-text-tertiary);">
                </div>

                <div class="form-group">
                    <label class="form-label">Teléfono de Contacto</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-text-tertiary);">
                            <svg class="icon"><use href="#icon-phone"></use></svg>
                        </span>
                        <input type="tel" id="edit_telefono" name="telefono" class="form-input" style="padding-left: 40px;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección Completa</label>
                    <textarea id="edit_direccion" name="direccion" class="form-input" rows="3" style="resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeModal('editGlobalModal')">Cancelar</button>
                    <button type="submit" class="btn btn-violeta" style="flex: 1;">
                        <svg class="icon"><use href="#icon-save"></use></svg>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/js/shared.js"></script>
    <script>
        async function openEditModal(cedula) {
            try {
                // Fetch latest data to populate
                const response = await fetch(`../../api/participantes.php?action=get_by_cedula&cedula=${cedula}`);
                const data = await response.json();
                
                if (data.success && data.data.participante) {
                    const p = data.data.participante;
                    document.getElementById('edit_cedula').value = cedula;
                    document.getElementById('edit_nombre_completo').value = `${p.nombre} ${p.apellido}`;
                    document.getElementById('edit_telefono').value = p.telefono || '';
                    document.getElementById('edit_direccion').value = p.direccion || '';
                    
                    openModal('editGlobalModal');
                } else {
                    showNotification('No se pudo cargar la información', 'error');
                }
            } catch(e) {
                showNotification('Error de conexión', 'error');
            }
        }

        document.getElementById('editGlobalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.style.opacity = '0.7';
            submitBtn.style.pointerEvents = 'none';

            try {
                const response = await fetch('../../api/participantes.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('editGlobalModal');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch(e) {
                showNotification('Error al guardar datos', 'error');
            } finally {
                submitBtn.style.opacity = '1';
                submitBtn.style.pointerEvents = 'all';
            }
        });

        async function disableGlobal(cedula) {
            if (!confirm('¿Estás seguro de inhabilitar globalmente a este participante? Ya no será elegible en ninguno de los grupos San en los que participe.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'disable_global');
                formData.append('cedula', cedula);

                const response = await fetch('../../api/participantes.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch(e) {
                showNotification('Error al inhabilitar participante', 'error');
            }
        }
    </script>
</body>
</html>
