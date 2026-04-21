<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// Fetch ALL active groups with their category and payment stats
$stmt = $pdo->query("
    SELECT 
        gs.id as grupo_id,
        gs.nombre as grupo_nombre,
        gs.estado,
        gs.monto_cuota,
        gs.frecuencia,
        gs.cupos_ocupados,
        gs.cupos_totales,
        gs.ronda_actual,
        gs.numero_cuotas,
        p.id as producto_id,
        p.nombre as producto_nombre,
        c.id as categoria_id,
        c.nombre as categoria_nombre,
        c.color as categoria_color,
        (SELECT COUNT(*) FROM pagos pa JOIN participantes pt ON pa.participante_id = pt.id WHERE pt.grupo_san_id = gs.id AND pa.estado = 'pendiente') as pagos_pendientes,
        (SELECT COUNT(*) FROM pagos pa JOIN participantes pt ON pa.participante_id = pt.id WHERE pt.grupo_san_id = gs.id AND pa.estado = 'atrasado')  as pagos_atrasados,
        (SELECT COUNT(*) FROM pagos pa JOIN participantes pt ON pa.participante_id = pt.id WHERE pt.grupo_san_id = gs.id AND pa.estado = 'pagado')    as pagos_pagados
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    WHERE gs.estado != 'finalizado'
    ORDER BY gs.fecha_inicio DESC
");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Global summary stats
$stmtStats = $pdo->query("
    SELECT 
        COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as total_pendientes,
        COUNT(CASE WHEN estado = 'atrasado'  THEN 1 END) as total_atrasados,
        SUM(CASE WHEN estado = 'pagado'      THEN monto ELSE 0 END) as total_recaudado
    FROM pagos
");
$globalStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Gestión de Pagos</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-8);
        }

        .summary-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
        }

        .summary-card .label {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            margin-bottom: var(--space-2);
        }

        .summary-card .value {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: var(--space-4);
        }

        .group-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            transition: all var(--transition-base);
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .group-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent-color, var(--color-menta));
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .group-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-4);
        }

        .group-card-title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
        }

        .group-card-subtitle {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            margin-top: 2px;
        }

        .payment-pills {
            display: flex;
            gap: var(--space-2);
            flex-wrap: wrap;
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 1px solid var(--glass-border);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
        }

        .pill-green  { background: rgba(100,255,150,0.1); color: var(--color-menta);  border: 1px solid var(--color-menta); }
        .pill-yellow { background: rgba(255,200,100,0.1); color: var(--color-salmon); border: 1px solid var(--color-salmon); }
        .pill-red    { background: rgba(255,100,100,0.1); color: #ff6464;             border: 1px solid #ff6464; }

        .btn-pagos {
            margin-top: var(--space-4);
            width: 100%;
            text-align: center;
            padding: var(--space-3);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            transition: all var(--transition-base);
        }

        .group-card:hover .btn-pagos {
            background: rgba(0, 203, 169, 0.1);
            border-color: var(--color-menta);
            color: var(--color-menta);
        }
    </style>
</head>
<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <?php
    $headerLogoHref  = '../../dashboard.php';
    $headerLogoutHref = '../../logout.php';
    $headerBackUrl   = '../../dashboard.php';
    $headerBackLabel = 'Volver al Dashboard';
    include '../../includes/header.php';
    ?>

    <div class="main-content">
        <div style="padding: var(--space-8); max-width: 1400px; margin: 0 auto;">

            <!-- Page Title -->
            <div style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-6);">
                <svg style="width:36px;height:36px;stroke:var(--color-menta);flex-shrink:0;">
                    <use href="#icon-credit-card"></use>
                </svg>
                <div>
                    <h1 style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary);">
                        Gestión de Pagos
                    </h1>
                    <p style="color: var(--color-text-tertiary); margin-top: 2px;">
                        Selecciona un grupo San para registrar o revisar sus pagos de cuotas.
                    </p>
                </div>
            </div>

            <!-- Global Summary -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="label">Total Recaudado</div>
                    <div class="value" style="color: var(--color-menta);">
                        $<?php echo number_format($globalStats['total_recaudado'] ?? 0, 2); ?>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="label">Cuotas Pendientes</div>
                    <div class="value" style="color: var(--color-salmon);">
                        <?php echo $globalStats['total_pendientes']; ?>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="label">Cuotas Atrasadas</div>
                    <div class="value" style="color: #ff6464;">
                        <?php echo $globalStats['total_atrasados']; ?>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="label">Grupos Activos</div>
                    <div class="value">
                        <?php echo count($grupos); ?>
                    </div>
                </div>
            </div>

            <!-- Groups Grid -->
            <?php if (empty($grupos)): ?>
                <div class="bento-box" style="text-align: center; padding: var(--space-10); color: var(--color-text-tertiary);">
                    <svg style="width:48px;height:48px;opacity:0.3;margin:0 auto var(--space-4);display:block;">
                        <use href="#icon-credit-card"></use>
                    </svg>
                    <p>No hay grupos San activos. Crea uno desde el módulo de Gestión de Grupos.</p>
                </div>
            <?php else: ?>
                <div class="groups-grid">
                    <?php foreach ($grupos as $g):
                        $color = htmlspecialchars($g['categoria_color']);
                        $css_var = "var(--color-{$color})";
                        $pagos_url = "../categoria/pagos.php?id={$g['categoria_id']}&grupo_id={$g['grupo_id']}";
                    ?>
                    <a href="<?php echo $pagos_url; ?>" class="group-card"
                       style="--accent-color: <?php echo $css_var; ?>;">
                        <div class="group-card-header">
                            <div>
                                <div class="group-card-title"><?php echo htmlspecialchars($g['grupo_nombre']); ?></div>
                                <div class="group-card-subtitle">
                                    <?php echo htmlspecialchars($g['categoria_nombre']); ?> &rsaquo;
                                    <?php echo htmlspecialchars($g['producto_nombre']); ?>
                                </div>
                            </div>
                            <span class="badge <?php echo $g['estado'] == 'abierto' ? 'badge-success' : 'badge-warning'; ?>">
                                <span class="badge-dot"></span>
                                <?php echo ucfirst($g['estado']); ?>
                            </span>
                        </div>

                        <div style="display:flex; gap: var(--space-4); font-size: var(--font-size-sm); color: var(--color-text-secondary);">
                            <span>
                                <svg style="width:13px;height:13px;stroke:<?php echo $css_var;?>;vertical-align:middle;">
                                    <use href="#icon-users"></use>
                                </svg>
                                <?php echo $g['cupos_ocupados']; ?>/<?php echo $g['cupos_totales']; ?> cupos
                            </span>
                            <span>
                                <svg style="width:13px;height:13px;stroke:<?php echo $css_var;?>;vertical-align:middle;">
                                    <use href="#icon-refresh-cw"></use>
                                </svg>
                                Ronda <?php echo $g['ronda_actual']; ?>/<?php echo $g['numero_cuotas']; ?>
                            </span>
                            <span>
                                <svg style="width:13px;height:13px;stroke:<?php echo $css_var;?>;vertical-align:middle;">
                                    <use href="#icon-dollar-sign"></use>
                                </svg>
                                $<?php echo number_format($g['monto_cuota'], 2); ?>
                            </span>
                        </div>

                        <div class="payment-pills">
                            <?php if ($g['pagos_pagados'] > 0): ?>
                            <span class="pill pill-green">
                                <svg style="width:10px;height:10px;"><use href="#icon-check"></use></svg>
                                <?php echo $g['pagos_pagados']; ?> pagados
                            </span>
                            <?php endif; ?>
                            <?php if ($g['pagos_pendientes'] > 0): ?>
                            <span class="pill pill-yellow">
                                <svg style="width:10px;height:10px;"><use href="#icon-clock"></use></svg>
                                <?php echo $g['pagos_pendientes']; ?> pendientes
                            </span>
                            <?php endif; ?>
                            <?php if ($g['pagos_atrasados'] > 0): ?>
                            <span class="pill pill-red">
                                <svg style="width:10px;height:10px;"><use href="#icon-alert-triangle"></use></svg>
                                <?php echo $g['pagos_atrasados']; ?> atrasados
                            </span>
                            <?php endif; ?>
                            <?php if ($g['pagos_pagados'] == 0 && $g['pagos_pendientes'] == 0 && $g['pagos_atrasados'] == 0): ?>
                            <span class="pill" style="color:var(--color-text-tertiary);border-color:var(--glass-border);">Sin cuotas registradas</span>
                            <?php endif; ?>
                        </div>

                        <div class="btn-pagos">
                            Ver y registrar pagos &rarr;
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="../../assets/js/shared.js"></script>
</body>
</html>
