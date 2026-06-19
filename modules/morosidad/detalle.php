<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

$participante_id = (int)($_GET['participante'] ?? 0);
if ($participante_id <= 0) {
    header('Location: index.php');
    exit;
}

// ── Participant + Group info ──
$stmt = $pdo->prepare("
    SELECT p.*, g.nombre AS grupo_nombre, g.frecuencia, g.monto_cuota,
           g.cupos_ocupados, g.cupos_totales, g.estado AS grupo_estado, g.numero_cuotas,
           g.ronda_actual, g.id AS grupo_id,
           pr.nombre AS producto_nombre, pr.marca AS producto_marca,
           c.nombre AS categoria_nombre, c.color AS categoria_color, c.id AS categoria_id
    FROM participantes p
    JOIN grupos_san g ON p.grupo_san_id = g.id
    JOIN productos pr ON g.producto_id = pr.id
    JOIN categorias c ON pr.categoria_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$participante_id]);
$participante = $stmt->fetch();

if (!$participante) {
    header('Location: index.php');
    exit;
}

// ── Overdue payments ──
$stmtPagos = $pdo->prepare("
    SELECT id, numero_cuota, monto, fecha_vencimiento, fecha_pago, estado, metodo_pago,
           DATEDIFF(CURDATE(), fecha_vencimiento) AS dias_atraso
    FROM pagos
    WHERE participante_id = ? AND estado IN ('pendiente', 'atrasado') AND fecha_vencimiento < CURDATE()
    ORDER BY fecha_vencimiento ASC
");
$stmtPagos->execute([$participante_id]);
$pagos_vencidos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

// ── Payment summary ──
$stmtHist = $pdo->prepare("
    SELECT COUNT(*) AS total_cuotas,
           SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) AS cuotas_pagadas,
           SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) AS total_pagado,
           SUM(CASE WHEN estado IN ('pendiente','atrasado') AND fecha_vencimiento >= CURDATE() THEN 1 ELSE 0 END) AS cuotas_futuras
    FROM pagos
    WHERE participante_id = ?
");
$stmtHist->execute([$participante_id]);
$historial = $stmtHist->fetch(PDO::FETCH_ASSOC);

$total_adeudado_vencido = array_sum(array_column($pagos_vencidos, 'monto'));
$dias_peor_mora = !empty($pagos_vencidos) ? max(array_column($pagos_vencidos, 'dias_atraso')) : 0;

function severityClass($dias) {
    if ($dias > 30) return 'critico';
    if ($dias > 15) return 'alto';
    if ($dias > 7)  return 'medio';
    return 'bajo';
}
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
    <title>MySan — <?php echo htmlspecialchars($participante['nombre'] . ' ' . $participante['apellido']); ?> (Mora)</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-6) var(--space-10);
        }

        /* ── Hero card (like grupo.php) ── */
        .mora-hero {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: var(--space-6);
        }

        .mora-hero-banner {
            height: 8px;
            background: linear-gradient(90deg, hsl(47, 97%, 60%), hsl(38, 92%, 50%));
        }

        .mora-hero-body {
            padding: var(--space-6) var(--space-8);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-6);
            flex-wrap: wrap;
        }

        .mora-hero-left {
            display: flex;
            align-items: center;
            gap: var(--space-5);
        }

        .mora-hero-icon {
            width: 64px;
            height: 64px;
            background: hsl(47, 90%, 25%);
            border: 2px solid hsl(47, 97%, 60%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: hsl(47, 97%, 60%);
        }

        .mora-hero-icon svg {
            width: 32px;
            height: 32px;
        }

        .mora-hero-name {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            line-height: 1.2;
        }

        .mora-hero-sub {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            margin-top: var(--space-1);
        }

        .mora-hero-right {
            text-align: right;
        }

        .mora-hero-amount {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-accent-dim);
            line-height: 1.1;
        }

        .mora-hero-amount-label {
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-tertiary);
        }

        /* ── Summary cards grid (like morosidad/index.php) ── */
        .summary-grid {
            display: grid;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .summary-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .summary-card--salmon {
            border-color: var(--color-accent-dim);
            background: linear-gradient(135deg, var(--color-surface), hsl(47, 97%, 60%, 0.04));
        }

        .summary-card--blue {
            border-color: var(--color-primary);
            background: linear-gradient(135deg, var(--color-surface), var(--color-primary-tint));
        }

        .summary-card--green {
            border-color: var(--color-secondary);
            background: linear-gradient(135deg, var(--color-surface), var(--color-secondary-tint));
        }

        .summary-icon {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            padding: 10px;
            border-radius: var(--radius-md);
        }

        .summary-icon--salmon {
            background: hsl(47, 90%, 25%, 0.2);
            color: var(--color-accent-dim);
        }

        .summary-icon--blue {
            background: var(--color-primary-glow);
            color: var(--color-primary);
        }

        .summary-icon--green {
            background: var(--color-secondary-glow);
            color: var(--color-secondary);
        }

        .summary-info h3 {
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-tertiary);
            margin-bottom: var(--space-1);
        }

        .summary-info .value {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
        }

        .summary-info .value--salmon { color: var(--color-accent-dim); }
        .summary-info .value--blue   { color: var(--color-primary); }
        .summary-info .value--green  { color: var(--color-secondary); }

        /* ── Two-column grid ── */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-5);
            margin-bottom: var(--space-6);
        }

        /* ── Section card (like grupo.php) ── */
        .section-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: var(--space-5);
        }

        .section-card:last-child {
            margin-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-4) var(--space-6);
            border-bottom: 1px solid var(--glass-border);
            background: var(--color-background);
        }

        .section-title {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .section-title svg {
            width: 18px;
            height: 18px;
        }

        .section-title .count {
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-medium);
            color: var(--color-text-tertiary);
            margin-left: var(--space-1);
        }

        /* ── Info card (data rows) ── */
        .info-body {
            padding: var(--space-5) var(--space-6);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .info-item.full {
            grid-column: 1 / -1;
        }

        .info-item .ilabel {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-tertiary);
            font-weight: 600;
        }

        .info-item .ivalue {
            font-size: var(--font-size-sm);
            color: var(--color-text-primary);
            font-weight: var(--font-weight-medium);
        }

        .info-item .ivalue a {
            color: var(--color-primary);
            text-decoration: none;
        }

        .info-item .ivalue a:hover {
            text-decoration: underline;
        }

        .info-item .ivalue .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .pill-green  { background: var(--color-primary-tint);   color: var(--color-primary); }
        .pill-amber  { background: hsl(47, 90%, 25%, 0.15);     color: var(--color-accent-dim); }
        .pill-gray   { background: rgba(0,0,0,0.05);            color: var(--color-text-tertiary); }
        .pill-red    { background: hsl(0,100%,96%);              color: var(--color-error); }

        /* ── Table inside section card ── */
        .part-table {
            width: 100%;
            border-collapse: collapse;
        }

        .part-table th {
            padding: var(--space-3) var(--space-6);
            text-align: left;
            font-size: var(--font-size-xs);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-tertiary);
            background: var(--color-background);
            border-bottom: 1px solid var(--glass-border);
        }

        .part-table td {
            padding: var(--space-3) var(--space-6);
            border-bottom: 1px solid var(--glass-border);
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
            vertical-align: middle;
        }

        .part-table tr:last-child td { border-bottom: none; }
        .part-table tr:hover td { background: var(--color-surface-hover); }

        .part-table .num {
            font-weight: var(--font-weight-semibold);
            font-variant-numeric: tabular-nums;
        }

        .part-table .num--red { color: var(--color-error); }
        .part-table .num--amber { color: var(--color-accent-dim); }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: var(--space-10) var(--space-6);
            color: var(--color-text-tertiary);
        }

        .empty-state svg {
            width: 40px;
            height: 40px;
            margin-bottom: var(--space-3);
            opacity: 0.25;
        }

        /* ── Action bar ── */
        .action-bar {
            display: flex;
            gap: var(--space-3);
            flex-wrap: wrap;
            margin-top: var(--space-5);
        }

        /* ── Responsive ── */
        @media (max-width: 800px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .mora-hero-body {
                flex-direction: column;
                text-align: center;
            }
            .mora-hero-left {
                flex-direction: column;
                text-align: center;
            }
            .mora-hero-right {
                text-align: center;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <?php
        $headerLogoHref   = '../../dashboard.php';
        $headerLogoutHref = '../../logout.php';
        $headerBackUrl    = '../../modules/morosidad/index.php';
        $headerBackLabel  = 'Volver a Morosidad';
        include '../../includes/header.php';
        ?>

        <div class="page-wrap">

            <!-- ═══ HERO CARD ═══ -->
            <?php $sev = severityClass($dias_peor_mora); ?>
            <div class="mora-hero">
                <div class="mora-hero-banner"></div>
                <div class="mora-hero-body">
                    <div class="mora-hero-left">
                        <div class="mora-hero-icon">
                            <svg><use href="#icon-alert-triangle"></use></svg>
                        </div>
                        <div>
                            <div class="mora-hero-name">
                                <?php echo htmlspecialchars($participante['nombre'] . ' ' . $participante['apellido']); ?>
                            </div>
                            <div class="mora-hero-sub">
                                <?php echo htmlspecialchars($participante['cedula']); ?>
                                <?php if ($participante['telefono']): ?>
                                    &middot; <?php echo htmlspecialchars($participante['telefono']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mora-hero-right">
                        <div class="mora-hero-amount-label">Total Adeudado</div>
                        <div class="mora-hero-amount">$<?php echo number_format($total_adeudado_vencido, 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- ═══ SUMMARY CARDS ═══ -->
            <div class="summary-grid grid-responsive-3">
                <div class="summary-card summary-card--salmon">
                    <div class="summary-icon summary-icon--salmon">
                        <svg class="icon"><use href="#icon-alert-circle"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Cuotas Vencidas</h3>
                        <div class="value value--salmon"><?php echo count($pagos_vencidos); ?></div>
                    </div>
                </div>
                <div class="summary-card summary-card--blue">
                    <div class="summary-icon summary-icon--blue">
                        <svg class="icon"><use href="#icon-dollar-sign"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Adeudado</h3>
                        <div class="value value--blue">$<?php echo number_format($total_adeudado_vencido, 2); ?></div>
                    </div>
                </div>
                <div class="summary-card summary-card--green">
                    <div class="summary-icon summary-icon--green">
                        <svg class="icon"><use href="#icon-clock"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Peor Mora</h3>
                        <div class="value value--green"><?php echo $dias_peor_mora; ?> días</div>
                    </div>
                </div>
            </div>

            <!-- ═══ TWO-COLUMN GRID ═══ -->
            <div class="detail-grid">

                <!-- ─── COLUMN 1: Participant + Group Info ─── -->
                <div>
                    <!-- Participant Info -->
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <svg style="color:var(--color-primary);"><use href="#icon-user"></use></svg>
                                Datos del Participante
                            </div>
                        </div>
                        <div class="info-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="ilabel">Cédula</span>
                                    <span class="ivalue"><?php echo htmlspecialchars($participante['cedula']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Teléfono</span>
                                    <span class="ivalue"><?php echo htmlspecialchars($participante['telefono'] ?? '—'); ?></span>
                                </div>
                                <?php if ($participante['direccion']): ?>
                                <div class="info-item full">
                                    <span class="ilabel">Dirección</span>
                                    <span class="ivalue"><?php echo htmlspecialchars($participante['direccion']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <span class="ilabel">Inscripción</span>
                                    <span class="ivalue"><?php echo date('d/m/Y', strtotime($participante['fecha_inscripcion'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Recibió Fondo</span>
                                    <span class="ivalue">
                                        <?php if ($participante['ha_recibido']): ?>
                                            <span class="pill pill-green">Sí</span>
                                        <?php else: ?>
                                            <span class="pill pill-gray">No</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group Info -->
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <svg style="color:var(--color-primary);"><use href="#icon-users"></use></svg>
                                Grupo San
                            </div>
                            <a href="../../modules/categoria/grupo.php?id=<?php echo $participante['categoria_id']; ?>&grupo_id=<?php echo $participante['grupo_id']; ?>" class="btn btn-sm btn-outline">
                                <svg class="icon"><use href="#icon-eye"></use></svg>
                                Ir
                            </a>
                        </div>
                        <div class="info-body">
                            <div class="info-grid">
                                <div class="info-item full">
                                    <span class="ilabel">Nombre</span>
                                    <span class="ivalue">
                                        <a href="../../modules/categoria/grupo.php?id=<?php echo $participante['categoria_id']; ?>&grupo_id=<?php echo $participante['grupo_id']; ?>">
                                            <?php echo htmlspecialchars($participante['grupo_nombre']); ?>
                                        </a>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Producto</span>
                                    <span class="ivalue"><?php echo htmlspecialchars($participante['producto_nombre'] . ' ' . ($participante['producto_marca'] ?? '')); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Categoría</span>
                                    <span class="ivalue"><?php echo htmlspecialchars($participante['categoria_nombre']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Cuota</span>
                                    <span class="ivalue">$<?php echo number_format((float)$participante['monto_cuota'], 2); ?> / <?php echo ucfirst($participante['frecuencia']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Cupos</span>
                                    <span class="ivalue"><?php echo (int)$participante['cupos_ocupados']; ?>/<?php echo (int)$participante['cupos_totales']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Estado</span>
                                    <span class="ivalue">
                                        <?php
                                        $estado = $participante['grupo_estado'];
                                        $pill = match($estado) {
                                            'abierto'    => 'pill-green',
                                            'en_curso'   => 'pill-green',
                                            'finalizado' => 'pill-gray',
                                            default      => 'pill-amber'
                                        };
                                        ?>
                                        <span class="pill <?php echo $pill; ?>"><?php echo ucfirst($estado); ?></span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Ronda</span>
                                    <span class="ivalue"><?php echo (int)$participante['ronda_actual']; ?> / <?php echo (int)$participante['numero_cuotas']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── COLUMN 2: Overdue Payments + Summary ─── -->
                <div>
                    <!-- Overdue Payments Table -->
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <svg style="color:var(--color-accent-dim);"><use href="#icon-alert-triangle"></use></svg>
                                Cuotas Vencidas
                                <span class="count">(<?php echo count($pagos_vencidos); ?>)</span>
                            </div>
                        </div>

                        <?php if (!empty($pagos_vencidos)): ?>
                            <div class="table-responsive">
                            <table class="part-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Vencimiento</th>
                                        <th>Monto</th>
                                        <th>Días</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagos_vencidos as $p):
                                        $dias = (int)$p['dias_atraso'];
                                    ?>
                                        <tr>
                                            <td class="num"><?php echo (int)$p['numero_cuota']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($p['fecha_vencimiento'])); ?></td>
                                            <td class="num num--red">$<?php echo number_format((float)$p['monto'], 2); ?></td>
                                            <td>
                                                <span class="pill <?php
                                                    echo match(severityClass($dias)) {
                                                        'critico' => 'pill-red',
                                                        'alto'    => 'pill-amber',
                                                        'medio'   => 'pill-green',
                                                        default   => 'pill-gray',
                                                    };
                                                ?>"><?php echo $dias; ?>d</span>
                                            </td>
                                            <td>
                                                <span class="pill <?php echo $p['estado'] === 'atrasado' ? 'pill-red' : 'pill-gray'; ?>">
                                                    <?php echo $p['estado'] === 'atrasado' ? 'Atrasado' : 'Pendiente'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <svg><use href="#icon-check-circle"></use></svg>
                                <div>No hay cuotas vencidas</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Summary -->
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <svg style="color:var(--color-secondary);"><use href="#icon-credit-card"></use></svg>
                                Resumen de Pagos
                            </div>
                        </div>
                        <div class="info-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="ilabel">Total Cuotas</span>
                                    <span class="ivalue"><?php echo (int)$historial['total_cuotas']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Pagadas</span>
                                    <span class="ivalue" style="color:var(--color-secondary);font-weight:var(--font-weight-semibold);"><?php echo (int)$historial['cuotas_pagadas']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Total Pagado</span>
                                    <span class="ivalue" style="color:var(--color-secondary);font-weight:var(--font-weight-bold);">$<?php echo number_format((float)$historial['total_pagado'], 2); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="ilabel">Vencidas</span>
                                    <span class="ivalue" style="color:var(--color-error);font-weight:var(--font-weight-semibold);"><?php echo count($pagos_vencidos); ?></span>
                                </div>
                            </div>

                            <div class="action-bar">
                                <a href="../../modules/categoria/grupo.php?id=<?php echo $participante['categoria_id']; ?>&grupo_id=<?php echo $participante['grupo_id']; ?>" class="btn btn-violeta">
                                    <svg class="icon"><use href="#icon-users"></use></svg>
                                    Ir al Grupo
                                </a>
                                <a href="index.php" class="btn btn-outline" onclick="if(document.referrer) { window.history.back(); return false; }">
                                    <svg class="icon"><use href="#icon-arrow-left"></use></svg>
                                    Volver a Morosidad
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /detail-grid -->
        </div><!-- /page-wrap -->
    </div>

    <script src="../../assets/js/shared.js"></script>
</body>

</html>
