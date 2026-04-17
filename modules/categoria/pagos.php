<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Get category ID from URL
$categoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header("Location: ../../dashboard.php");
    exit;
}

// Get all grupos for this category
$stmt = $pdo->prepare("
    SELECT gs.id, gs.nombre, gs.ronda_actual, gs.numero_cuotas
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    WHERE p.categoria_id = ? AND gs.estado != 'finalizado'
    ORDER BY gs.nombre
");
$stmt->execute([$categoria_id]);
$grupos = $stmt->fetchAll();

// Handle Advance Round
if (isset($_POST['action']) && $_POST['action'] === 'avanzar_ronda') {
    $grupo_id_avanzar = (int)$_POST['grupo_id'];
    $ronda_actual_avanzar = (int)$_POST['ronda_actual'];
    $max_cuotas_avanzar = (int)$_POST['numero_cuotas'];
    
    if ($ronda_actual_avanzar < $max_cuotas_avanzar) {
        $stmtAdv = $pdo->prepare("UPDATE grupos_san SET ronda_actual = ronda_actual + 1 WHERE id = ?");
        $stmtAdv->execute([$grupo_id_avanzar]);
    }
    
    header("Location: pagos.php?id=" . $categoria_id . "&grupo_id=" . $grupo_id_avanzar);
    exit;
}

// Get selected group
$grupo_seleccionado = $_GET['grupo_id'] ?? $_GET['grupo'] ?? ($grupos[0]['id'] ?? null);

// Get participants for selected group
$participantes = [];
$grupo_actual_datos = null;
if ($grupo_seleccionado) {
    foreach($grupos as $g) {
        if ($g['id'] == $grupo_seleccionado) {
            $grupo_actual_datos = $g;
            break;
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT id, nombre, apellido, cedula
        FROM participantes
        WHERE grupo_san_id = ? AND activo = TRUE
        ORDER BY nombre, apellido
    ");
    $stmt->execute([$grupo_seleccionado]);
    $participantes = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Pagos <?php echo htmlspecialchars($categoria['nombre']); ?></title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--space-4);
        }

        .payment-table th {
            background: var(--color-surface);
            padding: var(--space-3);
            text-align: left;
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
            border-bottom: 1px solid var(--glass-border);
        }

        .payment-table td {
            padding: var(--space-3);
            border-bottom: 1px solid var(--glass-border);
            color: var(--color-text-primary);
        }

        .payment-table tr:hover {
            background: var(--color-surface);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
        }

        .status-pagado {
            background: rgba(100, 255, 150, 0.1);
            color: var(--color-menta);
            border: 1px solid var(--color-menta);
        }

        .status-pendiente {
            background: rgba(255, 180, 120, 0.1);
            color: var(--color-salmon);
            border: 1px solid var(--color-salmon);
        }

        .status-atrasado {
            background: rgba(255, 100, 100, 0.1);
            color: #ff6464;
            border: 1px solid #ff6464;
        }

        .filter-bar {
            display: flex;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .stat-card {
            background: var(--color-surface);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
        }

        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            margin-bottom: var(--space-2);
        }

        .stat-value {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
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
        $headerBackUrl      = 'index.php?id=' . $categoria_id;
        $headerBackLabel    = 'Volver a ' . htmlspecialchars($categoria['nombre']);
        include '../../includes/header.php';
        ?>

        <div class="page-header" style="justify-content: space-between; align-items: center; display: flex;">
            <div>
                <div class="breadcrumb">
                    <a href="../../dashboard.php">Dashboard</a>
                    <span>/</span>
                    <a href="index.php?id=<?php echo $categoria_id; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></a>
                    <span>/</span>
                    <span>Pagos</span>
                </div>
                <h1 class="page-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg class="icon-xl" style="stroke: var(--color-<?php echo htmlspecialchars($categoria['color']); ?>);">
                        <use href="#icon-dollar"></use>
                    </svg>
                    Gestión de Pagos
                    <?php if ($grupo_actual_datos): ?>
                        <span class="badge" style="background: rgba(175, 100, 255, 0.2); color: var(--color-violeta); font-size: var(--font-size-md);">
                            Ronda #<?php echo $grupo_actual_datos['ronda_actual']; ?> / <?php echo $grupo_actual_datos['numero_cuotas']; ?>
                        </span>
                    <?php endif; ?>
                </h1>
            </div>
            <?php if ($grupo_actual_datos && $grupo_actual_datos['ronda_actual'] < $grupo_actual_datos['numero_cuotas']): ?>
                <form method="POST" style="margin: 0;" onsubmit="return confirm('¿Estás seguro que deseas cerrar la ronda actual? Esto avanzará a todos los participantes a la siguiente cuota.');">
                    <input type="hidden" name="action" value="avanzar_ronda">
                    <input type="hidden" name="grupo_id" value="<?php echo $grupo_actual_datos['id']; ?>">
                    <input type="hidden" name="ronda_actual" value="<?php echo $grupo_actual_datos['ronda_actual']; ?>">
                    <input type="hidden" name="numero_cuotas" value="<?php echo $grupo_actual_datos['numero_cuotas']; ?>">
                    <button type="submit" class="btn btn-outline" style="border-color: var(--color-violeta); color: var(--color-violeta);">
                        <svg class="icon"><use href="#icon-arrow-right"></use></svg>
                        Cerrar Ronda Actual
                    </button>
                </form>
            <?php elseif ($grupo_actual_datos && $grupo_actual_datos['ronda_actual'] == $grupo_actual_datos['numero_cuotas']): ?>
                <div class="badge" style="background: rgba(100,255,150,0.1); color: var(--color-menta); font-size: var(--font-size-sm); border: 1px solid var(--color-menta);">
                    Última Ronda
                </div>
            <?php endif; ?>
        </div>

        <div class="bento-container">
            <!-- Statistics -->
            <div class="bento-12">
                <div class="stats-grid" id="statsGrid">
                    <div class="stat-card">
                        <div class="stat-label">Total Recaudado</div>
                        <div class="stat-value" style="color: var(--color-menta);" id="totalRecaudado">$0.00</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pagos Pendientes</div>
                        <div class="stat-value" style="color: var(--color-salmon);" id="pagosPendientes">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pagos Atrasados</div>
                        <div class="stat-value" style="color: #ff6464;" id="pagosAtrasados">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Esperado</div>
                        <div class="stat-value" id="totalEsperado">$0.00</div>
                    </div>
                </div>
            </div>

            <!-- Filters and Payment Table -->
            <div class="bento-12">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Historial de Pagos</div>
                        <button class="btn btn-violeta" onclick="openRegistrarPagoModal()">
                            <svg class="icon">
                                <use href="#icon-plus"></use>
                            </svg>
                            Registrar Pago
                        </button>
                    </div>

                    <div class="bento-content">
                        <div class="filter-bar">
                            <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Grupo San</label>
                                <select id="grupoFilter" class="form-select" onchange="filterPagos()">
                                    <?php foreach ($grupos as $grupo): ?>
                                        <option value="<?php echo $grupo['id']; ?>" <?php echo $grupo['id'] == $grupo_seleccionado ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                                <label class="form-label">Participante</label>
                                <select id="participanteFilter" class="form-select" onchange="filterPagos()">
                                    <option value="">Todos</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin: 0; flex: 1; min-width: 150px;">
                                <label class="form-label">Estado</label>
                                <select id="estadoFilter" class="form-select" onchange="filterPagos()">
                                    <option value="">Todos</option>
                                    <option value="pagado">Pagado</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="atrasado">Atrasado</option>
                                </select>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Participante</th>
                                        <th>Cédula</th>
                                        <th>Cuota #</th>
                                        <th>Monto</th>
                                        <th>Vencimiento</th>
                                        <th>Fecha Pago</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="pagosTableBody">
                                    <tr>
                                        <td colspan="8"
                                            style="text-align: center; padding: var(--space-8); color: var(--color-text-tertiary);">
                                            Cargando pagos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Registrar Pago -->
    <div id="registrarPagoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Registrar Pago</h2>
                <button class="modal-close" onclick="closeModal('registrarPagoModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="registrarPagoForm">
                <div class="form-group">
                    <label class="form-label">Participante *</label>
                    <select id="pago_participante" name="participante_id" class="form-select" required
                        onchange="loadPagosPendientes()">
                        <option value="">-- Selecciona un participante --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Cuota Pendiente *</label>
                    <select id="pago_id" name="pago_id" class="form-select" required>
                        <option value="">-- Selecciona una cuota --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Pago *</label>
                    <input type="date" name="fecha_pago" class="form-input" required
                        value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Método de Pago *</label>
                    <select name="metodo_pago" class="form-select" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pago Móvil">Pago Móvil</option>
                        <option value="Tarjeta">Tarjeta</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-input" rows="3"
                        placeholder="Notas adicionales (opcional)"></textarea>
                </div>

                <div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-violeta" style="flex: 1;">
                        <svg class="icon">
                            <use href="#icon-check-circle"></use>
                        </svg>
                        Registrar Pago
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('registrarPagoModal')"
                        style="flex: 1;">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/shared.js"></script>
    <script>
        let currentGrupoId = <?php echo $grupo_seleccionado ?? 'null'; ?>;

        // Load initial data
        document.addEventListener('DOMContentLoaded', function () {
            if (currentGrupoId) {
                document.getElementById('grupoFilter').value = currentGrupoId;
                loadParticipantesForFilter();
                loadPagos();
                loadStats();
            }
        });

        async function filterPagos() {
            currentGrupoId = document.getElementById('grupoFilter').value;
            await loadParticipantesForFilter();
            await loadPagos();
            await loadStats();
        }

        async function loadParticipantesForFilter() {
            const select = document.getElementById('participanteFilter');
            const pagoSelect = document.getElementById('pago_participante');

            select.innerHTML = '<option value="">Todos</option>';
            pagoSelect.innerHTML = '<option value="">-- Selecciona un participante --</option>';

            if (!currentGrupoId) return;

            try {
                const response = await fetch(`../../api/participantes.php?action=list&grupo_san_id=${currentGrupoId}`);
                const data = await response.json();

                if (data.success) {
                    data.data.participantes.forEach(p => {
                        const option = new Option(`${p.nombre} ${p.apellido} - ${p.cedula}`, p.id);
                        select.add(option.cloneNode(true));
                        pagoSelect.add(option);
                    });
                }
            } catch (error) {
                console.error('Error loading participantes:', error);
            }
        }

        // Function to open modal and load participants
        function openRegistrarPagoModal() {
            loadParticipantesForFilter();
            openModal('registrarPagoModal');
        }

        async function loadPagos() {
            const tbody = document.getElementById('pagosTableBody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: var(--space-8);">Cargando...</td></tr>';

            const participanteId = document.getElementById('participanteFilter').value;
            const estado = document.getElementById('estadoFilter').value;

            let url = `../../api/pagos.php?action=list&grupo_san_id=${currentGrupoId}`;
            if (participanteId) url += `&participante_id=${participanteId}`;
            if (estado) url += `&estado=${estado}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success && data.data.pagos.length > 0) {
                    tbody.innerHTML = data.data.pagos.map(pago => `
                        <tr>
                            <td>${pago.nombre} ${pago.apellido}</td>
                            <td>${pago.cedula}</td>
                            <td>${pago.numero_cuota}</td>
                            <td>${formatCurrency(pago.monto)}</td>
                            <td>${formatDate(pago.fecha_vencimiento)}</td>
                            <td>${pago.fecha_pago ? formatDate(pago.fecha_pago) : '-'}</td>
                            <td>
                                <span class="status-badge status-${pago.estado_real}">
                                    ${pago.estado_real.charAt(0).toUpperCase() + pago.estado_real.slice(1)}
                                </span>
                            </td>
                            <td>
                                ${pago.estado === 'pendiente' ? `
                                    <button class="btn btn-sm btn-menta" onclick="marcarComoPagado(${pago.id})">
                                        <svg class="icon">
                                            <use href="#icon-check"></use>
                                        </svg>
                                    </button>
                                ` : '-'}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: var(--space-8); color: var(--color-text-tertiary);">No hay pagos registrados</td></tr>';
                }
            } catch (error) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: var(--space-8); color: #ff6464;">Error al cargar pagos</td></tr>';
            }
        }

        async function loadStats() {
            try {
                const response = await fetch(`../../api/pagos.php?action=stats&grupo_san_id=${currentGrupoId}`);
                const data = await response.json();

                if (data.success) {
                    const stats = data.data.stats;
                    document.getElementById('totalRecaudado').textContent = formatCurrency(stats.total_recaudado);
                    document.getElementById('pagosPendientes').textContent = stats.pendientes;
                    document.getElementById('pagosAtrasados').textContent = stats.atrasados;
                    document.getElementById('totalEsperado').textContent = formatCurrency(stats.total_esperado);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadPagosPendientes() {
            const participanteId = document.getElementById('pago_participante').value;
            const pagoSelect = document.getElementById('pago_id');

            pagoSelect.innerHTML = '<option value="">-- Cargando cuotas... --</option>';

            if (!participanteId) {
                pagoSelect.innerHTML = '<option value="">-- Selecciona un participante primero --</option>';
                return;
            }

            try {
                const response = await fetch(`../../api/pagos.php?action=list&participante_id=${participanteId}&estado=pendiente`);
                const data = await response.json();

                pagoSelect.innerHTML = '';

                if (data.success && data.data.pagos.length > 0) {
                    pagoSelect.add(new Option('-- Selecciona una cuota --', ''));
                    data.data.pagos.forEach(pago => {
                        const optionText = `Cuota #${pago.numero_cuota} - ${formatCurrency(pago.monto)} - Vencimiento: ${formatDate(pago.fecha_vencimiento)}`;
                        pagoSelect.add(new Option(optionText, pago.id));
                    });
                } else {
                    pagoSelect.innerHTML = '<option value="">-- No hay cuotas pendientes --</option>';
                    if (data.success) {
                        showNotification('Este participante ya está al día con sus cuotas', 'info');
                    }
                }
            } catch (error) {
                console.error('Error loading pagos pendientes:', error);
                pagoSelect.innerHTML = '<option value="">-- Error al cargar cuotas --</option>';
            }
        }

        // Handle form submission
        document.getElementById('registrarPagoForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('../../api/pagos.php?action=create', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Pago registrado exitosamente', 'success');
                    closeModal('registrarPagoModal');
                    this.reset();
                    loadPagos();
                    loadStats();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        });

        async function marcarComoPagado(pagoId) {
            if (!confirm('¿Marcar este pago como pagado?')) return;

            const formData = new FormData();
            formData.append('pago_id', pagoId);
            formData.append('fecha_pago', new Date().toISOString().split('T')[0]);
            formData.append('metodo_pago', 'Efectivo');

            try {
                const response = await fetch('../../api/pagos.php?action=create', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Pago registrado', 'success');
                    loadPagos();
                    loadStats();
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