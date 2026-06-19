<?php
/**
 * modules/pagos/verificacion.php
 * Panel administrativo de verificación y aprobación de comprobantes de pago.
 * Solo accesible por administradores.
 */
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Solo admins
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

// Obtener pagos pendientes de verificación
$stmt = $pdo->query("
    SELECT
        pg.id,
        pg.numero_cuota,
        pg.monto,
        pg.tasa_aplicada,
        pg.fecha_vencimiento,
        pg.referencia_pago,
        pg.comprobante,
        pg.notas,
        pg.created_at,
        part.id   AS participante_id,
        part.nombre,
        part.apellido,
        part.cedula,
        part.telefono,
        gs.id     AS grupo_id,
        gs.nombre AS grupo_nombre,
        gs.monto_cuota,
        prod.nombre AS producto_nombre,
        c.nombre    AS categoria_nombre,
        c.color     AS categoria_color
    FROM pagos pg
    JOIN participantes part ON pg.participante_id = part.id
    JOIN grupos_san gs      ON part.grupo_san_id = gs.id
    JOIN productos prod      ON gs.producto_id = prod.id
    JOIN categorias c        ON prod.categoria_id = c.id
    WHERE pg.estado = 'pendiente_verificacion'
    ORDER BY pg.created_at ASC
");
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pendientes = count($pagos);

// Tasa BCV actual para mostrar equivalente en Bs
$tasa_bcv = getBcvRate();
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
    <title>MySan — Verificación de Comprobantes</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .verif-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: var(--space-6);
        }

        .verif-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: box-shadow var(--transition-base);
        }

        .verif-card:hover {
            box-shadow: var(--shadow-md);
        }

        .verif-card-header {
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .verif-card-body {
            padding: var(--space-5);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: var(--space-2) 0;
            font-size: var(--font-size-sm);
            border-bottom: 1px solid var(--glass-border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--color-text-tertiary);
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-value {
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
            text-align: right;
        }

        .comprobante-preview {
            width: 100%;
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            margin: var(--space-4) 0;
            cursor: zoom-in;
            max-height: 200px;
            object-fit: cover;
            transition: max-height var(--transition-base);
        }

        .comprobante-preview.expanded {
            max-height: 600px;
            object-fit: contain;
            cursor: zoom-out;
        }

        .no-comprobante {
            background: var(--color-surface-section);
            border: 1px dashed var(--glass-border);
            border-radius: var(--radius-md);
            padding: var(--space-6);
            text-align: center;
            margin: var(--space-4) 0;
            color: var(--color-text-tertiary);
            font-size: var(--font-size-sm);
        }

        .action-bar {
            display: flex;
            gap: var(--space-3);
            padding: var(--space-4) var(--space-5);
            border-top: 1px solid var(--glass-border);
            background: var(--color-surface-section);
        }

        .btn-approve {
            flex: 1;
            padding: var(--space-3);
            background: var(--color-secondary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: all var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
        }

        .btn-approve:hover {
            background: var(--color-secondary-mid);
            transform: translateY(-1px);
        }

        .btn-reject {
            flex: 1;
            padding: var(--space-3);
            background: rgba(220,38,38,0.08);
            color: var(--color-error);
            border: 1px solid rgba(220,38,38,0.25);
            border-radius: var(--radius-md);
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: all var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
        }

        .btn-reject:hover {
            background: rgba(220,38,38,0.15);
        }

        .badge-pending {
            background: rgba(252,211,77,0.15);
            color: var(--color-accent-dim);
            border: 1px solid rgba(252,211,77,0.3);
            padding: 3px 10px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
        }

        /* Modal de Rechazo */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: var(--z-modal);
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            max-width: 420px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: var(--space-6);
            right: var(--space-6);
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            z-index: var(--z-toast);
            opacity: 0;
            transform: translateY(20px);
            transition: all var(--transition-base);
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success {
            background: var(--color-secondary);
            color: white;
        }

        .toast-error {
            background: var(--color-error);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: var(--space-16);
            color: var(--color-text-tertiary);
        }
    </style>
</head>
<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <?php
    $headerLogoHref   = '../../dashboard.php';
    $headerLogoutHref = '../../logout.php';
    $headerBackUrl    = '../../modules/pagos/index.php';
    $headerBackLabel  = 'Volver a Pagos';
    include '../../includes/header.php';
    ?>

    <div class="main-content">
        <div style="padding: var(--space-8); max-width: 1400px; margin: 0 auto;">

            <!-- Título -->
            <div style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-6);">
                <div style="position: relative;">
                    <svg style="width:36px; height:36px; stroke: var(--color-accent-dim); flex-shrink:0;">
                        <use href="#icon-check-square"></use>
                    </svg>
                    <?php if ($total_pendientes > 0): ?>
                    <span style="
                        position: absolute; top: -6px; right: -8px;
                        background: var(--color-error); color: white;
                        font-size: 10px; font-weight: bold;
                        padding: 2px 6px; border-radius: 10px;
                        border: 2px solid var(--color-surface);
                    "><?php echo $total_pendientes; ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary);">
                        Verificación de Comprobantes
                    </h1>
                    <p style="color: var(--color-text-tertiary); margin-top: 2px;">
                        <?php if ($total_pendientes > 0): ?>
                            <?php echo $total_pendientes; ?> pago<?php echo $total_pendientes !== 1 ? 's' : ''; ?> esperando verificación. Tasa BCV actual: <strong>Bs <?php echo number_format($tasa_bcv, 2); ?></strong>
                        <?php else: ?>
                            No hay comprobantes pendientes de verificación en este momento.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (empty($pagos)): ?>
            <div class="bento-box empty-state">
                <svg style="width:56px; height:56px; opacity:0.25; margin: 0 auto var(--space-4); display:block;">
                    <use href="#icon-check-circle"></use>
                </svg>
                <h3 style="font-size: var(--font-size-xl); margin-bottom: var(--space-2);">¡Todo al día!</h3>
                <p>No hay comprobantes pendientes de revisión. Los pagos reportados por los participantes aparecerán aquí.</p>
            </div>
            <?php else: ?>

            <div class="verif-grid" id="verifGrid">
                <?php foreach ($pagos as $p):
                    $color_var = 'var(--color-' . htmlspecialchars($p['categoria_color']) . ')';
                    $bs_equiv = $p['monto'] * $tasa_bcv;
                    $tiene_comprobante = !empty($p['comprobante']);
                    $comp_ext = $tiene_comprobante ? strtolower(pathinfo($p['comprobante'], PATHINFO_EXTENSION)) : '';
                    $es_imagen = in_array($comp_ext, ['jpg','jpeg','png','webp','gif']);
                ?>
                <div class="verif-card" id="card-<?php echo $p['id']; ?>" data-pago-id="<?php echo $p['id']; ?>">

                    <!-- Header de la tarjeta -->
                    <div class="verif-card-header">
                        <div>
                            <div style="font-weight: var(--font-weight-semibold); color: var(--color-text-primary);">
                                <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?>
                            </div>
                            <div style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">
                                C.I. <?php echo htmlspecialchars($p['cedula']); ?>
                                <?php if ($p['telefono']): ?>
                                 · <?php echo htmlspecialchars($p['telefono']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge-pending">
                            <svg style="width:10px;height:10px;vertical-align:middle;margin-right:3px;"><use href="#icon-clock"></use></svg>
                            En verificación
                        </span>
                    </div>

                    <!-- Cuerpo -->
                    <div class="verif-card-body">

                        <!-- Info del grupo y cuota -->
                        <div class="info-row">
                            <span class="info-label">Grupo San</span>
                            <span class="info-value" style="color: <?php echo $color_var; ?>;">
                                <?php echo htmlspecialchars($p['grupo_nombre']); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cuota</span>
                            <span class="info-value">
                                #<?php echo $p['numero_cuota']; ?> — $<?php echo number_format($p['monto'], 2); ?>
                                <span style="color: var(--color-text-tertiary); font-weight:normal; font-size: var(--font-size-xs);">
                                    (Bs <?php echo number_format($bs_equiv, 2); ?>)
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Vencimiento</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($p['fecha_vencimiento'])); ?></span>
                        </div>
                        <?php if (!empty($p['referencia_pago'])): ?>
                        <div class="info-row">
                            <span class="info-label">Referencia</span>
                            <span class="info-value" style="font-family: monospace; font-size: var(--font-size-xs);">
                                <?php echo htmlspecialchars($p['referencia_pago']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($p['notas'])): ?>
                        <div class="info-row">
                            <span class="info-label">Nota</span>
                            <span class="info-value" style="font-size: var(--font-size-xs); max-width: 60%;">
                                <?php echo htmlspecialchars($p['notas']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row" style="border:none; padding-top: var(--space-1);">
                            <span class="info-label">Reportado</span>
                            <span class="info-value" style="font-size:var(--font-size-xs);">
                                <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?>
                            </span>
                        </div>

                        <!-- Comprobante -->
                        <?php if ($tiene_comprobante): ?>
                            <?php if ($es_imagen): ?>
                                <img
                                    src="../../<?php echo htmlspecialchars($p['comprobante']); ?>"
                                    alt="Comprobante de pago"
                                    class="comprobante-preview"
                                    id="img-<?php echo $p['id']; ?>"
                                    onclick="toggleImg(<?php echo $p['id']; ?>)"
                                    title="Click para ampliar"
                                >
                            <?php else: ?>
                                <a href="../../<?php echo htmlspecialchars($p['comprobante']); ?>"
                                   target="_blank" rel="noopener"
                                   class="btn" style="display:block; text-align:center; margin: var(--space-4) 0;">
                                    <svg class="icon"><use href="#icon-file-text"></use></svg>
                                    Ver comprobante PDF
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-comprobante">
                                <svg style="width:24px;height:24px;margin-bottom:8px;opacity:0.4;">
                                    <use href="#icon-image"></use>
                                </svg>
                                <p>El participante no adjuntó imagen de comprobante.<br>Verificar por WhatsApp.</p>
                            </div>
                        <?php endif; ?>

                    </div><!-- /verif-card-body -->

                    <!-- Acciones -->
                    <div class="action-bar">
                        <button class="btn-approve" onclick="aprobarPago(<?php echo $p['id']; ?>)">
                            <svg style="width:14px;height:14px;"><use href="#icon-check"></use></svg>
                            Aprobar
                        </button>
                        <button class="btn-reject" onclick="openRejectModal(<?php echo $p['id']; ?>)">
                            <svg style="width:14px;height:14px;"><use href="#icon-x"></use></svg>
                            Rechazar
                        </button>
                    </div>

                </div><!-- /verif-card -->
                <?php endforeach; ?>
            </div><!-- /verif-grid -->
            <?php endif; ?>

        </div>
    </div>

    <!-- Modal de Rechazo -->
    <div class="modal-overlay" id="rejectModal">
        <div class="modal-box">
            <h3 style="font-size: var(--font-size-xl); margin-bottom: var(--space-4);">Rechazar Comprobante</h3>
            <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--space-4);">
                El pago volverá a estado "pendiente" y el participante tendrá que reportarlo nuevamente.
            </p>
            <input type="hidden" id="rejectPagoId">
            <div class="form-group">
                <label class="form-label" for="motivoRechazo">Motivo (opcional)</label>
                <input type="text" id="motivoRechazo" class="form-input"
                       placeholder="Ej: Imagen ilegible, referencia incorrecta…">
            </div>
            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-5);">
                <button class="btn btn-violeta" style="flex:1;" onclick="confirmarRechazo()">Confirmar Rechazo</button>
                <button class="btn" style="flex:1;" onclick="closeRejectModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        function toggleImg(id) {
            const img = document.getElementById('img-' + id);
            img.classList.toggle('expanded');
        }

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = 'toast toast-' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3500);
        }

        function removeCard(id) {
            const card = document.getElementById('card-' + id);
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            card.style.transition = 'all 0.3s ease';
            setTimeout(() => {
                card.remove();
                // Si no quedan tarjetas, recargar para mostrar estado vacío
                if (document.querySelectorAll('.verif-card').length === 0) {
                    location.reload();
                }
            }, 300);
        }

        async function aprobarPago(pagoId) {
            const btn = document.querySelector(`#card-${pagoId} .btn-approve`);
            btn.disabled = true;
            btn.textContent = 'Aprobando…';

            const fd = new FormData();
            fd.append('action', 'aprobar_pago');
            fd.append('pago_id', pagoId);

            try {
                const res  = await fetch('../../api/pagos.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    showToast('✓ Pago aprobado correctamente');
                    removeCard(pagoId);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<svg style="width:14px;height:14px;"><use href="#icon-check"></use></svg> Aprobar';
                }
            } catch {
                showToast('Error de conexión', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg style="width:14px;height:14px;"><use href="#icon-check"></use></svg> Aprobar';
            }
        }

        function openRejectModal(pagoId) {
            document.getElementById('rejectPagoId').value = pagoId;
            document.getElementById('motivoRechazo').value = '';
            document.getElementById('rejectModal').classList.add('show');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('show');
        }

        async function confirmarRechazo() {
            const pagoId = document.getElementById('rejectPagoId').value;
            const motivo = document.getElementById('motivoRechazo').value;

            const fd = new FormData();
            fd.append('action', 'rechazar_pago');
            fd.append('pago_id', pagoId);
            fd.append('motivo', motivo);

            try {
                const res  = await fetch('../../api/pagos.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    closeRejectModal();
                    showToast('Comprobante rechazado. El participante deberá reportarlo nuevamente.', 'error');
                    removeCard(pagoId);
                } else {
                    showToast(data.message, 'error');
                }
            } catch {
                showToast('Error de conexión', 'error');
            }
        }

        // Cerrar modal al hacer click fuera
        document.getElementById('rejectModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeRejectModal();
        });
    </script>
</body>
</html>
