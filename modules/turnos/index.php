<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Get all active groups
$stmt = $pdo->prepare("
    SELECT gs.*, p.nombre as producto_nombre, p.marca, p.modelo, c.nombre as categoria_nombre, c.color
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    WHERE gs.estado != 'finalizado'
    ORDER BY gs.created_at DESC
");
$stmt->execute();
$grupos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Gestión de Turnos</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .sorteo-box {
            position: relative;
            padding: var(--space-8);
            text-align: center;
            min-height: 480px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .dice-container {
            width: 100px;
            height: 100px;
            margin: var(--space-4) auto;
            position: relative;
        }

        .dice-icon {
            width: 100%;
            height: 100%;
            stroke: var(--color-violeta);
            filter: drop-shadow(0 0 20px var(--color-violeta-glow));
            transition: transform var(--transition-base);
        }

        .dice-icon.spinning {
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg) scale(1);
            }

            50% {
                transform: rotate(360deg) scale(1.2);
            }

            100% {
                transform: rotate(720deg) scale(1);
            }
        }

        .timeline {
            position: relative;
            padding-left: var(--space-8);
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, var(--color-violeta), var(--color-menta));
        }

        .timeline-item {
            position: relative;
            padding: var(--space-4);
            margin-bottom: var(--space-4);
            background: var(--color-surface);
            border-radius: var(--radius-md);
            border-left: 3px solid var(--color-surface-elevated);
            transition: all var(--transition-base);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .timeline-item.current {
            border-left-color: var(--color-violeta);
            box-shadow: var(--shadow-glow-violeta);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: calc(var(--space-8) * -1 - 6px);
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--color-surface-elevated);
            border: 2px solid var(--color-violeta);
        }

        .timeline-item.current::before {
            background: var(--color-violeta);
            box-shadow: 0 0 10px var(--color-violeta-glow);
        }

        .participant-list {
            width: 100%;
            margin-top: var(--space-6);
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
        }

        .participant-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: var(--space-3) var(--space-4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all var(--transition-base);
        }

        .participant-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }

        .turn-number {
            width: 32px;
            height: 32px;
            background: var(--color-violeta);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: var(--font-size-sm);
        }

        .turn-input {
            width: 50px;
            background: var(--color-background);
            border: 1px solid var(--glass-border);
            color: white;
            text-align: center;
            border-radius: var(--radius-sm);
            padding: var(--space-1);
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
                <span>Gestión de Turnos</span>
            </div>
            <h1 class="page-title">
                <svg class="icon-xl" style="stroke: var(--color-violeta);">
                    <use href="#icon-dice"></use>
                </svg>
                Asignación de Turnos y Fechas
            </h1>
        </div>

        <div class="bento-container">
            <!-- Sorteo Section -->
            <div class="bento-7">
                <div class="bento-box sorteo-box">
                    <h2
                        style="font-size: var(--font-size-xl); margin-bottom: var(--space-4); width: 100%; text-align: left;">
                        Configurar Orden</h2>

                    <div class="form-group" style="width: 100%;">
                        <label class="form-label">Seleccionar Grupo San</label>
                        <select id="grupoSelect" class="form-select" onchange="loadParticipants()">
                            <option value="">-- Selecciona un grupo --</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?php echo $grupo['id']; ?>">
                                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="participantsContainer" class="participant-list">
                        <div style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-8);">
                            Selecciona un grupo para gestionar sus turnos
                        </div>
                    </div>

                    <div id="actionButtons"
                        style="display: none; gap: var(--space-4); margin-top: auto; width: 100%; padding-top: var(--space-6);">
                        <button id="randomBtn" class="btn btn-violeta" style="flex: 1;" onclick="assignRandom()">
                            <svg class="icon">
                                <use href="#icon-shuffle"></use>
                            </svg>
                            Aleatorio
                        </button>
                        <button id="saveManualBtn" class="btn btn-menta" style="flex: 1;" onclick="saveManual()">
                            <svg class="icon">
                                <use href="#icon-save"></use>
                            </svg>
                            Guardar Manual
                        </button>
                    </div>
                </div>
            </div>

            <!-- Proximas Actividades / Timeline -->
            <div class="bento-5">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">
                            <svg class="bento-icon" style="stroke: var(--color-menta);">
                                <use href="#icon-calendar"></use>
                            </svg>
                            Calendario de Entregas
                        </div>
                    </div>
                    <div class="bento-content">
                        <div class="timeline" id="timelineContainer">
                            <div
                                style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-4);">
                                No hay entregas programadas
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dice Overlay for Animation -->
    <div id="diceOverlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; flex-direction: column; backdrop-filter: blur(8px);">
        <div class="dice-container">
            <svg class="dice-icon spinning">
                <use href="#icon-dice"></use>
            </svg>
        </div>
        <h2 style="color: white; margin-top: var(--space-4);">Barajando turnos...</h2>
    </div>

    <script src="../../assets/js/shared.js"></script>
    <script>
        async function loadParticipants() {
            const grupoId = document.getElementById('grupoSelect').value;
            const container = document.getElementById('participantsContainer');
            const actionButtons = document.getElementById('actionButtons');
            const timelineContainer = document.getElementById('timelineContainer');

            if (!grupoId) {
                container.innerHTML = '<div style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-8);">Selecciona un grupo para gestionar sus turnos</div>';
                actionButtons.style.display = 'none';
                timelineContainer.innerHTML = '<div style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-4);">No hay entregas programadas</div>';
                return;
            }

            try {
                const response = await fetch(`../../api/turnos.php?action=get_participantes&grupo_id=${grupoId}`);
                const data = await response.json();

                if (data.success) {
                    const parts = data.data.participantes;

                    if (parts.length === 0) {
                        container.innerHTML = '<div style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-8);">No hay participantes registrados en este grupo</div>';
                        actionButtons.style.display = 'none';
                    } else {
                        actionButtons.style.display = 'flex';
                        renderParticipants(parts);
                        renderTimeline(parts);
                    }
                }
            } catch (error) {
                showNotification('Error al cargar participantes', 'error');
            }
        }

        function renderParticipants(parts) {
            const container = document.getElementById('participantsContainer');
            container.innerHTML = '';

            parts.forEach((p, index) => {
                const card = document.createElement('div');
                card.className = 'participant-card';
                card.innerHTML = `
                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                        <div class="participant-info">
                            <div style="font-weight: bold;">${p.nombre} ${p.apellido}</div>
                            <div style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">${p.cedula}</div>
                        </div>
                    </div>
                    <div>
                        <input type="number" class="turn-input" data-id="${p.id}" value="${p.orden_turno || (index + 1)}" min="1" max="${parts.length}">
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function renderTimeline(parts) {
            const container = document.getElementById('timelineContainer');
            container.innerHTML = '';

            // Order by date if available, otherwise by turn
            const sorted = [...parts].sort((a, b) => {
                if (a.fecha_entrega && b.fecha_entrega) return new Date(a.fecha_entrega) - new Date(b.fecha_entrega);
                return (a.orden_turno || 99) - (b.orden_turno || 99);
            });

            sorted.forEach(p => {
                if (!p.fecha_entrega && !p.orden_turno) return;

                const item = document.createElement('div');
                item.className = 'timeline-item';
                const dateStr = p.fecha_entrega ? formatDate(p.fecha_entrega) : 'Sin fecha';

                item.innerHTML = `
                    <div>
                        <div style="font-weight: bold;">${p.nombre} ${p.apellido}</div>
                        <div style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">Turno ${p.orden_turno || '--'}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: var(--color-violeta); font-weight: bold;">${dateStr}</div>
                    </div>
                `;
                container.appendChild(item);
            });

            if (container.innerHTML === '') {
                container.innerHTML = '<div style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-4);">Asigna turnos para generar el calendario</div>';
            }
        }

        async function assignRandom() {
            const grupoId = document.getElementById('grupoSelect').value;
            const overlay = document.getElementById('diceOverlay');

            overlay.style.display = 'flex';

            try {
                const formData = new FormData();
                formData.append('grupo_id', grupoId);

                const response = await fetch('../../api/turnos.php?action=assign_random', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                setTimeout(() => {
                    overlay.style.display = 'none';
                    if (data.success) {
                        showNotification('Turnos asignados aleatoriamente', 'success');
                        loadParticipants();
                    } else {
                        showNotification(data.message, 'error');
                    }
                }, 1500);

            } catch (error) {
                overlay.style.display = 'none';
                showNotification('Error de conexión', 'error');
            }
        }

        async function saveManual() {
            const grupoId = document.getElementById('grupoSelect').value;
            const inputs = document.querySelectorAll('.turn-input');
            const assignments = [];

            inputs.forEach(input => {
                assignments.push({
                    participante_id: input.dataset.id,
                    orden: input.value
                });
            });

            try {
                const formData = new FormData();
                formData.append('grupo_id', grupoId);
                formData.append('asignaciones', JSON.stringify(assignments));

                const response = await fetch('../../api/turnos.php?action=assign_manual', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Turnos actualizados correctamente', 'success');
                    loadParticipants();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        function formatDate(dateStr) {
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const date = new Date(dateStr + 'T12:00:00');
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        }
    </script>
</body>

</html>
</body>

</html>