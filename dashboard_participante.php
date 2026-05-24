<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'participante') {
    // If admin, send to admin dashboard
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch participant records for this user (they might be in multiple groups)
$stmt = $pdo->prepare("
    SELECT p.id as participante_id, p.nombre, p.apellido, gs.id as grupo_id, gs.nombre as grupo_nombre, 
           gs.monto_cuota, gs.frecuencia, gs.ronda_actual, prod.nombre as producto_nombre
    FROM participantes p
    JOIN grupos_san gs ON p.grupo_san_id = gs.id
    JOIN productos prod ON gs.producto_id = prod.id
    WHERE p.usuario_id = ? AND p.activo = 1
");
$stmt->execute([$user['id']]);
$mis_sanes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tasa BCV del día para mostrar equivalencias en Bs (informe §6.2.5)
$tasa_bcv_hoy = getBcvRate();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - MySan</title>
    
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        .header {
            padding: var(--space-4) var(--space-8);
            background: var(--glass-background);
            backdrop-filter: var(--glass-blur);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-logo {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            background: linear-gradient(135deg, var(--color-violeta), var(--color-menta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .header {
                padding: var(--space-2) var(--space-3);
            }
            .header-content {
                gap: var(--space-2);
            }
            .header-logo {
                font-size: var(--font-size-lg);
            }
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-8);
        }

        .san-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-6);
        }

        .san-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: var(--space-4);
            margin-bottom: var(--space-4);
        }
        
        .san-title {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-primary);
        }

        .payment-status {
            display: flex;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }
        
        .payment-box {
            background: var(--color-surface-section);
            border: 1px solid var(--glass-border);
            padding: var(--space-4);
            border-radius: var(--radius-md);
            flex: 1;
            text-align: center;
        }

        .payment-box.alert {
            background: rgba(239, 68, 68, 0.08); /* light red */
            border-color: var(--color-error);
        }

        .payment-box.success {
            background: var(--color-primary-glow);
            border-color: var(--color-success);
        }
        
        .value-large {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            margin: var(--space-2) 0;
            color: var(--color-text-primary);
        }
    </style>
</head>
<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <header class="header">
        <div class="header-content">
            <a href="dashboard_participante.php" class="header-logo">MySan</a>
            
            <div class="user-nav">
                <!-- Unirse a un San -->
                <a href="sanes-disponibles.php" class="btn btn-menta" style="font-size:var(--font-size-xs); padding: var(--space-2) var(--space-3); white-space:nowrap; text-decoration:none;">
                    <svg class="icon"><use href="#icon-user-plus"></use></svg>
                    Unirse a un San
                </a>

                <!-- Notificaciones -->
                <?php include 'includes/notificaciones_participante.php'; ?>
                
                <div style="border-left: 1px solid var(--glass-border); padding-left: var(--space-4); margin-left: var(--space-2);">
                    <div style="font-weight: bold; font-size: var(--font-size-sm);"><?php echo htmlspecialchars($user['nombre']); ?></div>
                    <a href="logout.php" style="color: var(--color-salmon); font-size: var(--font-size-xs); text-decoration: none;">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <div class="page-container">
        <!-- Page header with greeting -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-4);margin-bottom:var(--space-8);">
            <div>
                <h1 style="font-size:var(--font-size-3xl);font-weight:800;color:var(--color-text-primary);letter-spacing:-0.025em;margin:0;">
                    Hola, <?php echo htmlspecialchars(explode(' ', $user['nombre'])[0]); ?>
                </h1>
                <p style="color:var(--color-text-tertiary);margin-top:var(--space-1);font-size:var(--font-size-base);">
                    <?php if (empty($mis_sanes)): ?>
                        Aún no formas parte de ningún grupo de ahorro.
                    <?php else: ?>
                        Tienes <strong style="color:var(--color-primary);"><?php echo count($mis_sanes); ?> San<?php echo count($mis_sanes) !== 1 ? 'es' : ''; ?></strong> activo<?php echo count($mis_sanes) !== 1 ? 's' : ''; ?>.
                        Revisa tus pagos y próximos vencimientos.
                    <?php endif; ?>
                </p>
            </div>
            <?php if (!empty($mis_sanes)): ?>
            <a href="sanes-disponibles.php" class="btn btn-menta" style="text-decoration:none;flex-shrink:0;">
                <svg class="icon"><use href="#icon-user-plus"></use></svg>
                Unirse a otro San
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($mis_sanes)): ?>
        <!-- Empty state — redesigned -->
        <div style="
            text-align: center;
            padding: var(--space-16) var(--space-8);
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            position: relative;
            overflow: hidden;
        ">
            <!-- Decorative gradient blobs -->
            <div style="
                position:absolute;top:-60px;right:-60px;width:200px;height:200px;
                border-radius:50%;background:var(--color-primary-glow);pointer-events:none;
            "></div>
            <div style="
                position:absolute;bottom:-40px;left:-40px;width:160px;height:160px;
                border-radius:50%;background:var(--color-secondary-glow);pointer-events:none;
            "></div>

            <!-- Icon group -->
            <div style="position:relative;z-index:1;">
                <div style="
                    width:80px;height:80px;border-radius:var(--radius-xl);margin:0 auto var(--space-6);
                    background:linear-gradient(135deg, var(--color-primary-glow), var(--color-secondary-glow));
                    display:flex;align-items:center;justify-content:center;
                    border:1px solid rgba(0,0,0,0.05);
                ">
                    <svg style="width:36px;height:36px;stroke:var(--color-primary);"><use href="#icon-users"></use></svg>
                </div>

                <h2 style="font-size:var(--font-size-2xl);font-weight:700;color:var(--color-text-primary);margin-bottom:var(--space-2);">
                    Aún no perteneces a ningún San
                </h2>
                <p style="color:var(--color-text-tertiary);max-width:420px;margin:0 auto var(--space-6);font-size:var(--font-size-base);line-height:1.6;">
                    Explora los grupos disponibles. Compara planes, revisa los participantes y elige el San que mejor se adapte a ti.
                </p>

                <div style="display:flex;gap:var(--space-3);justify-content:center;flex-wrap:wrap;">
                    <a href="sanes-disponibles.php" class="btn btn-violeta" style="text-decoration:none;padding:var(--space-4) var(--space-8);font-size:var(--font-size-base);">
                        <svg class="icon"><use href="#icon-search"></use></svg>
                        Ver Sanes Disponibles
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($mis_sanes as $san): 
                // Fetch next pending payment
                $stmtPago = $pdo->prepare("
                    SELECT id, monto, fecha_vencimiento, estado, numero_cuota 
                    FROM pagos 
                    WHERE participante_id = ? AND estado IN ('pendiente', 'atrasado') 
                    ORDER BY numero_cuota ASC LIMIT 1
                ");
                $stmtPago->execute([$san['participante_id']]);
                $prox_pago = $stmtPago->fetch(PDO::FETCH_ASSOC);

                // Fetch payment history stats
                $stmtStats = $pdo->prepare("
                    SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as pagadas
                    FROM pagos WHERE participante_id = ?
                ");
                $stmtStats->execute([$san['participante_id']]);
                $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="san-card">
                    <div class="san-header">
                        <div>
                            <div class="san-title"><?php echo htmlspecialchars($san['grupo_nombre']); ?></div>
                            <div style="font-size:var(--font-size-sm);color:var(--color-text-tertiary);margin-top:var(--space-1);">
                                <?php echo htmlspecialchars($san['producto_nombre']); ?> · 
                                $<?php echo number_format($san['monto_cuota'], 2); ?> · 
                                <?php echo ucfirst($san['frecuencia']); ?> · 
                                Ronda <?php echo (int)$san['ronda_actual']; ?>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">
                                <?php echo (int)$stats['pagadas']; ?>/<?php echo (int)$stats['total']; ?> cuotas pagadas
                            </div>
                            <a href="detalle-participante.php?grupo_id=<?php echo $san['grupo_id']; ?>" class="btn btn-sm btn-outline" style="font-size:var(--font-size-xs);text-decoration:none;white-space:nowrap;">
                                <svg style="width:12px;height:12px;"><use href="#icon-eye"></use></svg>
                                Ver Detalle
                            </a>
                        </div>
                    </div>

                    <div class="payment-status">
                        <?php if ($prox_pago): ?>
                            <div class="payment-box <?php echo $prox_pago['estado'] == 'atrasado' ? 'alert' : ''; ?>" style="border-radius:var(--radius-lg);padding:var(--space-5);">
                                <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-2);">
                                    <svg style="width:16px;height:16px;stroke:var(--color-text-tertiary);"><use href="#icon-<?php echo $prox_pago['estado'] == 'atrasado' ? 'alert-triangle' : 'calendar'; ?>"></use></svg>
                                    <span style="color:var(--color-text-tertiary);font-size:var(--font-size-sm);font-weight:500;">Próxima Cuota (#<?php echo $prox_pago['numero_cuota']; ?>)</span>
                                </div>
                                <div style="display:flex;align-items:baseline;gap:var(--space-2);margin-bottom:var(--space-1);">
                                    <span class="value-large" style="margin:0;font-size:var(--font-size-4xl);">$<?php echo number_format($prox_pago['monto'], 2); ?></span>
                                    <span style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">≈ Bs <?php echo number_format($prox_pago['monto'] * $tasa_bcv_hoy, 2); ?></span>
                                </div>
                                <div style="font-size:var(--font-size-sm);color:<?php
                                    if ($prox_pago['estado'] == 'atrasado') echo 'var(--color-error)';
                                    else echo 'var(--color-text-secondary)';
                                ?>;display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-3);">
                                    <svg style="width:14px;height:14px;"><use href="#icon-clock"></use></svg>
                                    Vence: <?php echo date('d/m/Y', strtotime($prox_pago['fecha_vencimiento'])); ?>
                                    <?php if ($prox_pago['estado'] == 'atrasado'): ?> · <strong>Atrasado</strong><?php endif; ?>
                                </div>
                                <button
                                    class="btn btn-violeta"
                                    style="width:100%;font-size:var(--font-size-sm);padding:var(--space-3);"
                                    onclick="openReportModal(<?php echo $prox_pago['id']; ?>, <?php echo $prox_pago['numero_cuota']; ?>, <?php echo $prox_pago['monto']; ?>, <?php echo $san['grupo_id']; ?>)">
                                    <svg style="width:14px;height:14px;"><use href="#icon-download"></use></svg>
                                    Reportar Pago
                                </button>
                                <?php if ($tasa_bcv_hoy > 0): ?>
                                <div style="margin-top:var(--space-2);font-size:var(--font-size-xs);color:var(--color-text-tertiary);opacity:0.7;">
                                    Tasa BCV: Bs <?php echo number_format($tasa_bcv_hoy, 2); ?> / $
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="payment-box success" style="border-radius:var(--radius-lg);padding:var(--space-5);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--space-2);">
                                <svg style="width:40px;height:40px;stroke:var(--color-success);"><use href="#icon-check"></use></svg>
                                <div style="color:var(--color-success);font-weight:700;font-size:var(--font-size-lg);">¡Estás al día!</div>
                                <div style="font-size:var(--font-size-sm);color:var(--color-text-tertiary);">No tienes cuotas pendientes.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Reportar Pago -->
    <div id="reportModal" style="
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.45); backdrop-filter: blur(6px);
        z-index: var(--z-modal); align-items: center; justify-content: center;
    ">
        <div style="
            background: var(--color-surface); border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl); padding: var(--space-8);
            max-width: 440px; width: 92%; box-shadow: var(--shadow-lg);
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-5);">
                <h2 style="font-size: var(--font-size-xl); font-weight: var(--font-weight-bold);">Reportar Pago</h2>
                <button onclick="closeReportModal()" style="background:none; border:none; cursor:pointer; color: var(--color-text-tertiary);">
                    <svg style="width:20px;height:20px;"><use href="#icon-x"></use></svg>
                </button>
            </div>

            <p id="modalDesc" style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-bottom: var(--space-4);"></p>

            <form id="reportForm" enctype="multipart/form-data">
                <input type="hidden" id="reportPagoId" name="pago_id">
                <input type="hidden" id="reportNumeroCuota" name="numero_cuota">
                <input type="hidden" id="reportMontoCuota" name="monto">
                <input type="hidden" id="reportGrupoId" name="grupo_id">

                <div class="form-group">
                    <label class="form-label" for="referenciaPago">
                        Número de Referencia *
                    </label>
                    <input type="text" id="referenciaPago" name="referencia_pago" class="form-input"
                           placeholder="Ej: 0123456789" required>
                    <p style="font-size: var(--font-size-xs); color: var(--color-text-tertiary); margin-top:4px;">
                        Número de confirmación del banco o billetera electrónica.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="montoBsPagado">
                        Monto Pagado en Bolívares (Bs) *
                    </label>
                    <input type="number" id="montoBsPagado" name="monto_bs_pagado" class="form-input"
                           step="0.01" min="0.01" placeholder="Ej: 8950.00" required>
                    <p style="font-size: var(--font-size-xs); color: var(--color-text-tertiary); margin-top:4px;">
                        Monto exacto en Bs que transferiste. El administrador verificará la equivalencia en USD con la tasa BCV del día.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="comprobanteFile">
                        Comprobante (imagen o PDF)
                    </label>
                    <input type="file" id="comprobanteFile" name="comprobante" class="form-input"
                           accept="image/*,.pdf" style="padding: var(--space-2);">
                    <p style="font-size: var(--font-size-xs); color: var(--color-text-tertiary); margin-top:4px;">
                        Sube la captura del pago. Máx 5MB.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notasPago">Nota adicional (opcional)</label>
                    <input type="text" id="notasPago" name="notas" class="form-input"
                           placeholder="Ej: Pago móvil Bancamiga">
                </div>

                <div id="reportAlert" style="display:none; background: rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.25); color: var(--color-error); padding: var(--space-3); border-radius: var(--radius-md); font-size: var(--font-size-sm); margin-bottom: var(--space-4);"></div>

                <button type="submit" id="reportSubmitBtn" class="btn btn-violeta" style="width:100%;">
                    Enviar Reporte
                    <svg class="icon"><use href="#icon-check"></use></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Join button now links to sanes-disponibles.php -->

    <script>
        function openReportModal(pagoId, numeroCuota, monto, grupoId) {
            document.getElementById('reportPagoId').value = pagoId;
            document.getElementById('reportNumeroCuota').value = numeroCuota;
            document.getElementById('reportMontoCuota').value = parseFloat(monto).toFixed(2);
            document.getElementById('reportGrupoId').value = grupoId;
            document.getElementById('modalDesc').textContent =
                'Cuota #' + numeroCuota + ' — $' + parseFloat(monto).toFixed(2) + '. Ingresa el número de referencia y adjunta tu comprobante.';
            document.getElementById('reportAlert').style.display = 'none';
            document.getElementById('reportForm').reset();
            document.getElementById('reportPagoId').value = pagoId;
            document.getElementById('reportNumeroCuota').value = numeroCuota;
            document.getElementById('reportMontoCuota').value = parseFloat(monto).toFixed(2);
            document.getElementById('reportGrupoId').value = grupoId;
            const modal = document.getElementById('reportModal');
            modal.style.display = 'flex';
        }

        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        document.getElementById('reportModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeReportModal();
        });

        document.getElementById('reportForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('reportSubmitBtn');
            const alertBox = document.getElementById('reportAlert');
            btn.disabled = true;
            btn.textContent = 'Enviando…';
            alertBox.style.display = 'none';

            const fd = new FormData(e.target);
            fd.append('action', 'reportar_pago');

            try {
                const res  = await fetch('api/pagos.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    closeReportModal();
                    showNotification('Pago reportado correctamente. El administrador lo revisará pronto.', 'success');
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    alertBox.textContent = data.message;
                    alertBox.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = 'Enviar Reporte <svg class="icon"><use href="#icon-check"></use></svg>';
                }
            } catch {
                alertBox.textContent = 'Error de conexión. Intenta nuevamente.';
                alertBox.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Enviar Reporte <svg class="icon"><use href="#icon-check"></use></svg>';
            }
        });
    </script>
    <script src="assets/js/shared.js"></script>
</body>
</html>
