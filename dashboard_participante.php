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

// Count unread notifications
$stmtNoti = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leido = 0");
$stmtNoti->execute([$user['id']]);
$unread_notis = $stmtNoti->fetchColumn();

// Count unread messages
$stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM mensajes WHERE receiver_id = ? AND leido = 0");
$stmtMsg->execute([$user['id']]);
$unread_msgs = $stmtMsg->fetchColumn();
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

        .user-nav {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
            color: var(--color-text-secondary);
            transition: color 0.2s;
        }

        .notification-icon:hover {
            color: var(--color-primary);
        }

        .badge-count {
            position: absolute;
            top: -5px;
            right: -8px;
            background: var(--color-error);
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            border: 2px solid var(--color-surface);
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
            <div style="font-size: var(--font-size-xl); font-weight: bold; color: var(--color-text-primary);">
                Mi Portal MySan
            </div>
            
            <div class="user-nav">
                <!-- Mensajes -->
                <a href="modules/mensajeria/inbox.php" class="notification-icon" title="Mensajes">
                    <svg class="icon"><use href="#icon-mail"></use></svg>
                    <?php if ($unread_msgs > 0): ?>
                        <span class="badge-count"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Notificaciones -->
                <div class="notification-icon" title="Notificaciones" onclick="alert('Notificaciones en desarrollo')">
                    <svg class="icon"><use href="#icon-bell"></use></svg>
                    <?php if ($unread_notis > 0): ?>
                        <span class="badge-count"><?php echo $unread_notis; ?></span>
                    <?php endif; ?>
                </div>
                
                <div style="border-left: 1px solid var(--glass-border); padding-left: var(--space-4); margin-left: var(--space-2);">
                    <div style="font-weight: bold; font-size: var(--font-size-sm);"><?php echo htmlspecialchars($user['nombre']); ?></div>
                    <a href="logout.php" style="color: var(--color-salmon); font-size: var(--font-size-xs); text-decoration: none;">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <div class="page-container">
        
        <h1 style="font-size: var(--font-size-3xl); margin-bottom: var(--space-8);">Resumen de mis Sanes</h1>

        <?php if (empty($mis_sanes)): ?>
            <div style="text-align: center; padding: 4rem; background: var(--color-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-lg);">
                <svg class="icon" style="width: 48px; height: 48px; color: var(--color-text-tertiary); margin-bottom: 1rem;"><use href="#icon-info"></use></svg>
                <h3 style="font-size: var(--font-size-xl); margin-bottom: 0.5rem;">Aún no perteneces a ningún San</h3>
                <p style="color: var(--color-text-secondary);">Comunícate con el administrador para que te asigne a tus grupos activos.</p>
            </div>
        <?php else: ?>
            <?php foreach ($mis_sanes as $san): 
                // Fetch next pending payment
                $stmtPago = $pdo->prepare("
                    SELECT monto, fecha_vencimiento, estado, numero_cuota 
                    FROM pagos 
                    WHERE participante_id = ? AND estado IN ('pendiente', 'atrasado') 
                    ORDER BY numero_cuota ASC LIMIT 1
                ");
                $stmtPago->execute([$san['participante_id']]);
                $prox_pago = $stmtPago->fetch(PDO::FETCH_ASSOC);
                
                // Fetch turn info
                $stmtTurno = $pdo->prepare("
                    SELECT numero_turno, fecha_turno, estado 
                    FROM turnos 
                    WHERE participante_id = ?
                ");
                $stmtTurno->execute([$san['participante_id']]);
                $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="san-card">
                <div class="san-header">
                    <div>
                        <h2 class="san-title"><?php echo htmlspecialchars($san['grupo_nombre']); ?></h2>
                        <div style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-top: 4px;">
                            Meta: <?php echo htmlspecialchars($san['producto_nombre']); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: var(--font-size-sm); color: var(--color-text-tertiary);">Ronda Actual</div>
                        <div style="font-size: var(--font-size-xl); font-weight: bold; color: var(--color-violeta);">#<?php echo $san['ronda_actual']; ?></div>
                    </div>
                </div>

                <div class="payment-status">
                    <?php if ($prox_pago): ?>
                        <div class="payment-box <?php echo $prox_pago['estado'] == 'atrasado' ? 'alert' : ''; ?>">
                            <div style="color: var(--color-text-tertiary); font-size: var(--font-size-sm);">Próxima Cuota (#<?php echo $prox_pago['numero_cuota']; ?>)</div>
                            <div class="value-large">$<?php echo number_format($prox_pago['monto'], 2); ?></div>
                            <div style="font-size: var(--font-size-sm); color: <?php echo $prox_pago['estado'] == 'atrasado' ? 'var(--color-error)' : 'var(--color-text-secondary)'; ?>;">
                                Vence: <?php echo date('d/m/Y', strtotime($prox_pago['fecha_vencimiento'])); ?>
                                <?php if ($prox_pago['estado'] == 'atrasado') echo " (Atrasado)"; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="payment-box success">
                            <svg class="icon" style="width: 32px; height: 32px; color: var(--color-success); margin-bottom: 8px;"><use href="#icon-check-circle"></use></svg>
                            <div style="color: var(--color-success); font-weight: bold;">¡Estás al día!</div>
                            <div style="font-size: var(--font-size-sm); color: var(--color-text-secondary);">No tienes cuotas pendientes en este momento.</div>
                        </div>
                    <?php endif; ?>

                    <div class="payment-box">
                        <div style="color: var(--color-text-tertiary); font-size: var(--font-size-sm);">Mi Sorteo / Turno</div>
                        <?php if ($turno): ?>
                            <div class="value-large" style="color: var(--color-electro);">#<?php echo $turno['numero_turno']; ?></div>
                            <div style="font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                                Estado: <?php echo ucfirst($turno['estado']); ?>
                            </div>
                        <?php else: ?>
                            <div class="value-large" style="font-size: var(--font-size-lg); color: var(--color-text-secondary);">Pendiente</div>
                            <div style="font-size: var(--font-size-sm); color: var(--color-text-tertiary);">Aún no se ha sorteado</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
