<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

$grupo_id    = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
$categoria_id = isset($_GET['id'])      ? (int)$_GET['id']      : 0;

// ── Grupo + producto + categoría ──────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT gs.*,
           p.nombre  AS producto_nombre,
           p.imagen  AS producto_imagen,
           p.marca   AS producto_marca,
           p.modelo  AS producto_modelo,
           p.valor_total,
           c.nombre  AS categoria_nombre,
           c.color   AS categoria_color,
           c.id      AS categoria_id
    FROM grupos_san gs
    JOIN productos  p  ON gs.producto_id  = p.id
    JOIN categorias c  ON p.categoria_id  = c.id
    WHERE gs.id = ?
");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch();

if (!$grupo) {
    header("Location: index.php?id=$categoria_id");
    exit;
}

// Use category from the group if not passed via URL
if (!$categoria_id) $categoria_id = $grupo['categoria_id'];

// ── Participantes + resumen de pagos por participante ────────────────────────
$stmt = $pdo->prepare("
    SELECT
        part.*,
        t.numero_turno,
        COUNT(pg.id)                                                    AS total_cuotas,
        SUM(CASE WHEN pg.estado = 'pagado'  THEN 1 ELSE 0 END)         AS cuotas_pagadas,
        SUM(CASE WHEN pg.estado = 'pendiente' AND pg.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) AS cuotas_atrasadas,
        SUM(CASE WHEN pg.estado = 'pendiente' AND pg.fecha_vencimiento >= CURDATE() THEN 1 ELSE 0 END) AS cuotas_pendientes,
        COALESCE(SUM(CASE WHEN pg.estado = 'pagado' THEN pg.monto ELSE 0 END), 0) AS monto_pagado
    FROM participantes part
    LEFT JOIN turnos t ON t.participante_id = part.id AND t.grupo_san_id = part.grupo_san_id
    LEFT JOIN pagos pg ON pg.participante_id = part.id
    WHERE part.grupo_san_id = ? AND part.activo = TRUE
    GROUP BY part.id, t.numero_turno
    ORDER BY t.numero_turno ASC, part.id ASC
");
$stmt->execute([$grupo_id]);
$participantes = $stmt->fetchAll();

// ── Participantes removidos ──────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT *
    FROM participantes 
    WHERE grupo_san_id = ? AND activo = 0 AND motivo_salida IS NOT NULL
    ORDER BY fecha_salida DESC, id DESC
");
$stmt->execute([$grupo_id]);
$participantes_removidos = $stmt->fetchAll();

// ── Stats globales del grupo ──────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        COUNT(*)                                                        AS total_pagos,
        COALESCE(SUM(CASE WHEN estado = 'pagado'  THEN 1 ELSE 0 END), 0) AS pagados,
        COALESCE(SUM(CASE WHEN estado = 'pendiente' AND fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS atrasados,
        COALESCE(SUM(CASE WHEN estado = 'pendiente' AND fecha_vencimiento >= CURDATE() THEN 1 ELSE 0 END), 0) AS pendientes,
        COALESCE(SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END), 0) AS total_recaudado,
        COALESCE(SUM(monto), 0) AS total_esperado
    FROM pagos p
    JOIN participantes part ON p.participante_id = part.id
    WHERE part.grupo_san_id = ?
");
$stmt->execute([$grupo_id]);
$stats = $stmt->fetch();

// ── Últimos 10 movimientos (pagos registrados) ────────────────────────────────
$stmt = $pdo->prepare("
    SELECT pg.*, part.nombre, part.apellido, part.cedula
    FROM pagos pg
    JOIN participantes part ON pg.participante_id = part.id
    WHERE part.grupo_san_id = ? AND pg.estado = 'pagado'
    ORDER BY pg.fecha_pago DESC
    LIMIT 10
");
$stmt->execute([$grupo_id]);
$ultimos_pagos = $stmt->fetchAll();

// ── Helpers ──────────────────────────────────────────────────────────────────
$pct_cupos     = $grupo['cupos_totales'] > 0
    ? round(($grupo['cupos_ocupados'] / $grupo['cupos_totales']) * 100) : 0;
$pct_recaudado = $stats['total_esperado'] > 0
    ? round(($stats['total_recaudado'] / $stats['total_esperado']) * 100) : 0;

$color = htmlspecialchars($grupo['categoria_color']);
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
    <title>MySan — <?php echo htmlspecialchars($grupo['nombre']); ?></title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        /* ── Hero card ─────────────────────────────────────────────── */
        .grupo-hero {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: var(--space-6);
        }

        .grupo-hero-banner {
            height: 8px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
        }

        .grupo-hero-body {
            padding: var(--space-6) var(--space-8);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-6);
            flex-wrap: wrap;
        }

        .grupo-hero-left {
            display: flex;
            align-items: center;
            gap: var(--space-5);
        }

        .grupo-hero-icon {
            width: 64px;
            height: 64px;
            background: var(--color-primary-tint);
            border: 2px solid var(--color-primary-glow);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .grupo-hero-name {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            line-height: 1.2;
        }

        .grupo-hero-sub {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            margin-top: var(--space-1);
        }

        .grupo-hero-badges {
            display: flex;
            gap: var(--space-2);
            margin-top: var(--space-3);
            flex-wrap: wrap;
        }

        .badge-info {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: var(--font-size-xs);
            font-weight: 600;
            background: var(--color-primary-tint);
            color: var(--color-primary);
            border: 1px solid var(--color-primary-glow);
        }

        .badge-warn {
            background: var(--color-secondary-tint);
            color: var(--color-secondary);
            border-color: var(--color-secondary-glow);
        }

        /* ── Summary stats ─────────────────────────────────────────── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .summary-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }

        .summary-card.green::before  { background: var(--color-primary-mid); }
        .summary-card.amber::before  { background: var(--color-secondary-mid); }
        .summary-card.red::before    { background: var(--color-error); }
        .summary-card.blue::before   { background: var(--color-electro); }

        .summary-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-tertiary);
            font-weight: 600;
            margin-bottom: var(--space-2);
        }

        .summary-value {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            line-height: 1.1;
        }

        .summary-sub {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
            margin-top: var(--space-1);
        }

        /* Progress bar */
        .prog-track {
            height: 5px;
            background: rgba(0,0,0,0.07);
            border-radius: 999px;
            overflow: hidden;
            margin-top: var(--space-3);
        }

        .prog-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
        }

        /* ── Participants table ────────────────────────────────────── */
        .section-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: var(--space-6);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-5) var(--space-6);
            border-bottom: 1px solid var(--glass-border);
            background: var(--color-background);
        }

        .section-title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .part-table {
            width: 100%;
            border-collapse: collapse;
        }

        .part-table th {
            padding: var(--space-3) var(--space-4);
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
            padding: var(--space-4);
            border-bottom: 1px solid var(--glass-border);
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
            vertical-align: middle;
        }

        .part-table tr:last-child td { border-bottom: none; }

        .part-table tr:hover td { background: var(--color-surface-hover); }

        .participant-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--color-primary-tint);
            border: 2px solid var(--color-primary-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--font-size-sm);
            color: var(--color-primary);
            flex-shrink: 0;
        }

        .participant-name-cell {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .mini-prog-track {
            height: 4px;
            background: rgba(0,0,0,0.07);
            border-radius: 999px;
            overflow: hidden;
            min-width: 60px;
            flex: 1;
        }

        .mini-prog-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .pill-green  { background: var(--color-primary-tint);   color: var(--color-primary); }
        .pill-amber  { background: var(--color-secondary-tint); color: var(--color-secondary); }
        .pill-red    { background: hsl(0,100%,96%);              color: var(--color-error); }

        /* ── Recent payments ──────────────────────────────────────── */
        .payment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-4) var(--space-6);
            border-bottom: 1px solid var(--glass-border);
            gap: var(--space-4);
            transition: background 0.15s;
        }

        .payment-row:last-child { border-bottom: none; }
        .payment-row:hover      { background: var(--color-surface-hover); }

        .payment-person {
            font-weight: 600;
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
        }

        .payment-cuota {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }

        .payment-amount {
            font-weight: 700;
            font-size: var(--font-size-sm);
            color: var(--color-primary);
        }

        .payment-date {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }

        /* ── Responsive ───────────────────────────────────────────── */
        @media (max-width: 768px) {
            .grupo-hero-body { flex-direction: column; }
            .summary-grid    { grid-template-columns: repeat(2, 1fr); }
            .part-table th:nth-child(n+4),
            .part-table td:nth-child(n+4) { display: none; }
        }
    </style>
</head>
<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <!-- Header -->
        <?php
        $headerLogoHref   = '../../dashboard.php';
        $headerLogoutHref = '../../logout.php';
        $headerBackUrl    = 'index.php?id=' . $categoria_id;
        $headerBackLabel  = 'Volver';
        include '../../includes/header.php';
        ?>

        <div style="padding: var(--space-8); max-width: 1200px; margin: 0 auto;">

            <!-- ══ HERO ══════════════════════════════════════════════════ -->
            <div class="grupo-hero">
                <div class="grupo-hero-banner"></div>
                <div class="grupo-hero-body">
                    <div class="grupo-hero-left">
                        <div class="grupo-hero-icon" style="overflow: hidden; background: var(--color-background); border: 2px solid var(--glass-border); position: relative; cursor: zoom-in; display: flex; align-items: center; justify-content: center; padding: 6px;"
                             onclick="event.stopPropagation(); viewGallery(<?php echo (int)$grupo['producto_id']; ?>, '<?php echo htmlspecialchars(addslashes($grupo['producto_nombre'])); ?>')">
                            <?php if (!empty($grupo['producto_imagen'])): ?>
                                <img src="../../<?php echo htmlspecialchars(ltrim($grupo['producto_imagen'] ?? '', '/')); ?>" alt="Producto" style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform .3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <span style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,.6);color:#fff;border-radius:50%;padding:4px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
                                    <svg style="width:12px;height:12px;stroke:#fff;stroke-width:2.5;"><use href="#icon-zoom-in"></use></svg>
                                </span>
                            <?php else: ?>
                            <svg style="width:32px;height:32px;stroke:var(--color-primary);stroke-width:1.8;">
                                <use href="#icon-users"></use>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="grupo-hero-name"><?php echo htmlspecialchars($grupo['nombre']); ?></div>
                            <div class="grupo-hero-sub">
                                <?php echo htmlspecialchars($grupo['producto_nombre']); ?>
                                <?php if ($grupo['producto_marca']): ?>
                                    · <?php echo htmlspecialchars($grupo['producto_marca']); ?>
                                <?php endif; ?>
                                <?php if ($grupo['producto_modelo']): ?>
                                    <?php echo htmlspecialchars($grupo['producto_modelo']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="grupo-hero-badges">
                                <span class="badge-info">
                                    <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-calendar"></use></svg>
                                    <?php echo ucfirst($grupo['frecuencia']); ?>
                                </span>
                                <span class="badge-info">
                                    <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-repeat"></use></svg>
                                    <?php echo $grupo['numero_cuotas']; ?> cuotas
                                </span>
                                <span class="badge-info badge-warn">
                                    <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-activity"></use></svg>
                                    <?php echo ucwords(str_replace('_', ' ', $grupo['estado'] ?? 'abierto')); ?>
                                </span>
                                <?php if ($grupo['fecha_inicio']): ?>
                                <span class="badge-info">
                                    <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-clock"></use></svg>
                                    Inicio: <?php echo date('d M Y', strtotime($grupo['fecha_inicio'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Quick action buttons -->
                    <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-self:center;">
                        <?php if ($grupo['estado'] !== 'finalizado' && $grupo['cupos_ocupados'] < $grupo['cupos_totales']): ?>
                        <button class="btn btn-menta" onclick="openModal('nuevoParticipanteModal')">
                            <svg class="icon"><use href="#icon-user-plus"></use></svg>
                            Inscribir
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-outline" onclick="editGrupo(<?php echo $grupo_id; ?>)">
                            <svg class="icon"><use href="#icon-edit"></use></svg>
                            Editar Grupo
                        </button>
                        <a href="pagos.php?id=<?php echo $categoria_id; ?>&grupo_id=<?php echo $grupo_id; ?>"
                           class="btn btn-violeta">
                            <svg class="icon"><use href="#icon-dollar"></use></svg>
                            Gestión de Pagos
                        </a>
                    </div>
                </div>
            </div>

            <!-- ══ STATS ══════════════════════════════════════════════════ -->
            <div class="summary-grid">
                <!-- Total Recaudado -->
                <div class="summary-card green">
                    <div class="summary-label">Total Recaudado</div>
                    <div class="summary-value" style="color:var(--color-primary);">
                        <?php echo formatMoneyBcv($stats['total_recaudado']); ?>
                    </div>
                    <div class="summary-sub"><?php echo $pct_recaudado; ?>% del total esperado</div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?php echo $pct_recaudado; ?>%;"></div>
                    </div>
                </div>

                <!-- Total Esperado -->
                <div class="summary-card blue">
                    <div class="summary-label">Total Esperado</div>
                    <div class="summary-value"><?php echo formatMoneyBcv($stats['total_esperado']); ?></div>
                    <div class="summary-sub">
                        Cuota: <?php echo formatMoneyBcv($grupo['monto_cuota']); ?>
                    </div>
                </div>

                <!-- Cupos -->
                <div class="summary-card amber">
                    <div class="summary-label">Cupos</div>
                    <div class="summary-value" style="color:var(--color-secondary);">
                        <?php echo $grupo['cupos_ocupados']; ?>
                        <span style="font-size:var(--font-size-lg);color:var(--color-text-tertiary);font-weight:400;">
                            / <?php echo $grupo['cupos_totales']; ?>
                        </span>
                    </div>
                    <div class="summary-sub"><?php echo $pct_cupos; ?>% ocupado</div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?php echo $pct_cupos; ?>%;
                            background:<?php echo $pct_cupos >= 100 ? 'var(--color-error)' : 'linear-gradient(90deg,var(--color-primary),var(--color-secondary))'; ?>;"></div>
                    </div>
                </div>

                <!-- Pagos atrasados -->
                <div class="summary-card red">
                    <div class="summary-label">Pagos</div>
                    <div style="display:flex;gap:var(--space-4);align-items:baseline;">
                        <div>
                            <div style="font-size:10px;color:var(--color-text-tertiary);text-transform:uppercase;letter-spacing:.05em;">Pendientes</div>
                            <div class="summary-value" style="color:var(--color-secondary);font-size:var(--font-size-xl);">
                                <?php echo $stats['pendientes']; ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:10px;color:var(--color-text-tertiary);text-transform:uppercase;letter-spacing:.05em;">Atrasados</div>
                            <div class="summary-value" style="color:var(--color-error);font-size:var(--font-size-xl);">
                                <?php echo $stats['atrasados']; ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:10px;color:var(--color-text-tertiary);text-transform:uppercase;letter-spacing:.05em;">Pagados</div>
                            <div class="summary-value" style="color:var(--color-primary);font-size:var(--font-size-xl);">
                                <?php echo $stats['pagados']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ══ PARTICIPANTES ════════════════════════════════════════ -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <svg style="width:20px;height:20px;stroke:var(--color-primary);stroke-width:2;">
                            <use href="#icon-users"></use>
                        </svg>
                        Participantes
                        <span style="background:var(--color-primary-tint);color:var(--color-primary);
                                     font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;">
                            <?php echo count($participantes); ?>
                        </span>
                    </div>
                    <a href="pagos.php?id=<?php echo $categoria_id; ?>&grupo_id=<?php echo $grupo_id; ?>"
                       class="btn btn-sm btn-outline" style="font-size:var(--font-size-xs);">
                        <svg class="icon"><use href="#icon-dollar"></use></svg>
                        Ver todos los pagos
                    </a>
                </div>

                <?php if (empty($participantes)): ?>
                    <div style="padding:var(--space-12);text-align:center;color:var(--color-text-tertiary);">
                        <svg style="width:40px;height:40px;stroke:currentColor;opacity:.4;margin-bottom:var(--space-3);">
                            <use href="#icon-users"></use>
                        </svg>
                        <p>No hay participantes inscritos en este grupo.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="part-table">
                            <thead>
                                <tr>
                                    <th>Turno</th>
                                    <th>Participante</th>
                                    <th>Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Progreso de Pago</th>
                                    <th>Estado</th>
                                    <th>Monto Pagado</th>
                                    <th style="text-align:right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participantes as $p):
                                    $pct_p = $p['total_cuotas'] > 0
                                        ? round(($p['cuotas_pagadas'] / $p['total_cuotas']) * 100) : 0;
                                    $initials = strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1));
                                    if ($p['cuotas_atrasadas'] > 0)   $estado_key = 'red';
                                    elseif ($pct_p >= 100)            $estado_key = 'green';
                                    else                              $estado_key = 'amber';
                                ?>
                                <tr>
                                    <td style="text-align:center;font-weight:700;font-size:var(--font-size-lg);color:var(--color-violeta);">
                                        <?php echo isset($p['numero_turno']) ? '#' . (int)$p['numero_turno'] : '-'; ?>
                                    </td>
                                    <td>
                                        <div class="participant-name-cell">
                                            <div class="participant-avatar"><?php echo $initials; ?></div>
                                            <div>
                                                <div style="font-weight:600;"><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?></div>
                                                <?php if ($p['direccion']): ?>
                                                <div style="font-size:11px;color:var(--color-text-tertiary);margin-top:1px;">
                                                    <?php echo htmlspecialchars(mb_strimwidth($p['direccion'], 0, 35, '…')); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($p['telefono'] ?: '—'); ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:var(--space-2);">
                                            <div class="mini-prog-track">
                                                <div class="mini-prog-fill"
                                                     style="width:<?php echo $pct_p; ?>%;
                                                            background:<?php echo $estado_key === 'red' ? 'var(--color-error)' : 'linear-gradient(90deg,var(--color-primary),var(--color-secondary))'; ?>;">
                                                </div>
                                            </div>
                                            <span style="font-size:11px;white-space:nowrap;color:var(--color-text-secondary);font-weight:600;">
                                                <?php echo $p['cuotas_pagadas']; ?>/<?php echo $p['total_cuotas']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($p['cuotas_atrasadas'] > 0): ?>
                                            <span class="pill pill-red">⚠ <?php echo $p['cuotas_atrasadas']; ?> atrasada<?php echo $p['cuotas_atrasadas'] > 1 ? 's' : ''; ?></span>
                                        <?php elseif ($pct_p >= 100): ?>
                                            <span class="pill pill-green">✓ Al día</span>
                                        <?php else: ?>
                                            <span class="pill pill-amber"><?php echo $p['cuotas_pendientes']; ?> pendiente<?php echo $p['cuotas_pendientes'] > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:700;color:var(--color-primary);">
                                        <?php echo formatMoneyBcv($p['monto_pagado']); ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <button class="btn-action" style="color:var(--color-salmon);" title="Remover participante" onclick="confirmarRemocion(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['nombre'] . ' ' . $p['apellido'])); ?>')">
                                            <svg class="icon"><use href="#icon-x"></use></svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($participantes_removidos) > 0): ?>
            <!-- ══ PARTICIPANTES REMOVIDOS ════════════════════════════════════════ -->
            <div class="section-card" style="border-left: 4px solid var(--color-salmon); margin-top: var(--space-6);">
                <div class="section-header">
                    <div class="section-title" style="color: var(--color-salmon);">
                        <svg style="width:20px;height:20px;stroke:currentColor;stroke-width:2;">
                            <use href="#icon-users"></use>
                        </svg>
                        Participantes Removidos
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="part-table">
                        <thead>
                            <tr>
                                <th>Participante</th>
                                <th>Cédula</th>
                                <th>Fecha Salida</th>
                                <th>Motivo de Remoción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participantes_removidos as $pr): 
                                $initials = strtoupper(substr($pr['nombre'], 0, 1) . substr($pr['apellido'], 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="participant-name-cell">
                                        <div class="participant-avatar" style="background:var(--color-surface);color:var(--color-text-secondary);"><?php echo $initials; ?></div>
                                        <div>
                                            <div style="font-weight:600;color:var(--color-text-secondary);"><?php echo htmlspecialchars($pr['nombre'] . ' ' . $pr['apellido']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="color:var(--color-text-secondary);"><?php echo htmlspecialchars($pr['cedula']); ?></td>
                                <td style="color:var(--color-text-secondary);"><?php echo date('d/m/Y', strtotime($pr['fecha_salida'])); ?></td>
                                <td style="color:var(--color-text-secondary);font-size:13px;max-width:300px;white-space:normal;line-height:1.4;">
                                    <?php echo nl2br(htmlspecialchars($pr['motivo_salida'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ══ ÚLTIMOS PAGOS ════════════════════════════════════════ -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <svg style="width:20px;height:20px;stroke:var(--color-secondary);stroke-width:2;">
                            <use href="#icon-clock"></use>
                        </svg>
                        Últimos Pagos Registrados
                    </div>
                    <a href="pagos.php?id=<?php echo $categoria_id; ?>&grupo_id=<?php echo $grupo_id; ?>"
                       class="btn btn-sm btn-violeta" style="font-size:var(--font-size-xs);">
                        Ver todos
                    </a>
                </div>

                <?php if (empty($ultimos_pagos)): ?>
                    <div style="padding:var(--space-8);text-align:center;color:var(--color-text-tertiary);">
                        No hay pagos registrados aún.
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimos_pagos as $pg): ?>
                    <div class="payment-row">
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div style="width:38px;height:38px;border-radius:50%;
                                        background:var(--color-primary-tint);
                                        border:2px solid var(--color-primary-glow);
                                        display:flex;align-items:center;justify-content:center;
                                        font-weight:700;font-size:var(--font-size-sm);
                                        color:var(--color-primary);flex-shrink:0;">
                                <?php echo strtoupper(substr($pg['nombre'], 0, 1) . substr($pg['apellido'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="payment-person">
                                    <?php echo htmlspecialchars($pg['nombre'] . ' ' . $pg['apellido']); ?>
                                </div>
                                <div class="payment-cuota">Cuota #<?php echo $pg['numero_cuota']; ?> · <?php echo htmlspecialchars($pg['metodo_pago'] ?? ''); ?></div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div class="payment-amount"><?php echo formatMoneyBcv($pg['monto']); ?></div>
                            <div class="payment-date">
                                <?php echo $pg['fecha_pago'] ? date('d M Y', strtotime($pg['fecha_pago'])) : '—'; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /.bento-container -->
    </div>

    <!-- Modal: Editar Grupo -->
    <div id="editarGrupoModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Editar Grupo San</h2>
                <button class="modal-close" onclick="closeModal('editarGrupoModal')">
                    <svg class="icon">
                        <use href="#icon-x"></use>
                    </svg>
                </button>
            </div>
            <form id="editarGrupoForm">
                <input type="hidden" id="edit_grupo_id" name="id">

                <div class="form-group">
                    <label class="form-label">Nombre del Grupo *</label>
                    <input type="text" id="edit_grupo_nombre" name="nombre" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de Inicio *</label>
                    <input type="date" id="edit_grupo_fecha_inicio" name="fecha_inicio" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="edit_grupo_estado" name="estado" class="form-select">
                        <option value="en_espera">En Espera</option>
                        <option value="abierto">Abierto</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editarGrupoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-violeta"><svg class="icon">
                            <use href="#icon-check-circle"></use>
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Inscribir Participante (búsqueda por cédula) -->
    <div id="nuevoParticipanteModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Inscribir Participante</h2>
                <button class="modal-close" onclick="closeModal('nuevoParticipanteModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>

            <form id="nuevoParticipanteForm">
                <input type="hidden" name="grupo_san_id" value="<?php echo $grupo_id; ?>">

                <!-- Alerta de error persistente en el modal -->
                <div id="form-alert" style="
                    display: none;
                    background: rgba(220,38,38,0.1);
                    border: 1px solid rgba(220,38,38,0.3);
                    color: #ff6464;
                    padding: var(--space-3) var(--space-4);
                    border-radius: var(--radius-md);
                    margin-bottom: var(--space-4);
                    font-size: var(--font-size-sm);
                    font-weight: var(--font-weight-semibold);
                    animation: fadeSlideUp 0.2s ease-out;
                "></div>

                <!-- STEP 1: Cédula lookup -->
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

                <!-- STEP 2a: Participante encontrado — tarjeta prefilled -->
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
                    <input type="hidden" name="nombre"    id="p_nombre">
                    <input type="hidden" name="apellido"  id="p_apellido">
                    <input type="hidden" name="cedula"    id="p_cedula">
                    <input type="hidden" name="telefono"  id="p_telefono">
                    <input type="hidden" name="direccion" id="p_direccion">

                    <div style="display: flex; gap: var(--space-4); margin-top: var(--space-4);">
                        <button type="button" class="btn btn-outline" style="flex:1;" onclick="resetInscripcionForm()">
                            <svg class="icon"><use href="#icon-arrow-left"></use></svg> Otra Cédula
                        </button>
                        <button type="submit" class="btn btn-menta" style="flex:1;">
                            <svg class="icon"><use href="#icon-user-plus"></use></svg> Inscribir
                        </button>
                    </div>
                </div>

                <!-- STEP 2b: Participante nuevo — formulario completo -->
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
                        <label class="form-label">Teléfono *</label>
                        <input type="text" id="new_telefono" name="telefono" class="form-input" required placeholder="04121234567" maxlength="15">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección *</label>
                        <textarea id="new_direccion" name="direccion" class="form-input" required rows="2" placeholder="Sector, Calle..."></textarea>
                    </div>

                    <div style="display: flex; gap: var(--space-4); margin-top: var(--space-4);">
                        <button type="button" class="btn btn-outline" style="flex:1;" onclick="resetInscripcionForm()">
                            <svg class="icon"><use href="#icon-arrow-left"></use></svg> Otra Cédula
                        </button>
                        <button type="submit" class="btn btn-menta" style="flex:1;">
                            <svg class="icon"><use href="#icon-user-plus"></use></svg> Inscribir
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- ══ MODAL REMOVER PARTICIPANTE ════════════════════════════════════════ -->
    <div id="removerParticipanteModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Remover Participante</h2>
                <button class="modal-close" onclick="closeModal('removerParticipanteModal')">
                    <svg class="icon"><use href="#icon-x"></use></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="removerParticipanteForm">
                    <input type="hidden" id="rem_participante_id" name="id">
                    
                    <p style="margin-bottom: var(--space-4); color: var(--color-text-secondary);">
                        ¿Estás seguro de que deseas remover a <strong id="rem_participante_nombre" style="color: var(--color-text-primary);"></strong> de este grupo?
                    </p>
                    
                    <div style="background: rgba(255,100,100,0.1); border-left: 4px solid var(--color-salmon); padding: var(--space-3); border-radius: var(--radius-sm); margin-bottom: var(--space-4);">
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0;">
                            <strong>Atención:</strong> Sus pagos completados se mantendrán en el historial, pero sus pagos pendientes, atrasados y turno serán cancelados. El cupo del grupo se liberará.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="rem_motivo">Motivo de la Salida *</label>
                        <textarea id="rem_motivo" name="motivo_salida" class="form-control" rows="3" required placeholder="Ej. No puede seguir pagando las cuotas..."></textarea>
                    </div>
                    
                    <div style="display:flex;gap:var(--space-2);margin-top:var(--space-5);">
                        <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeModal('removerParticipanteModal')">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="flex:1;background:var(--color-salmon);">Confirmar Remoción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/shared.js"></script>
    <script src="../../assets/js/grupos.js"></script>

    <script>
    /* ── Inscripción con búsqueda por cédula ─────────────────── */

    // Reset cada vez que se abre el modal
    const _origOpenDetalle = window.openModal;
    window.openModal = function(id) {
        if (id === 'nuevoParticipanteModal') resetInscripcionForm();
        _origOpenDetalle(id);
    };

    function resetInscripcionForm() {
        const alert = document.getElementById('form-alert');
        if (alert) { alert.style.display = 'none'; alert.textContent = ''; }
        const lookup = document.getElementById('lookup_cedula');
        if (lookup) lookup.value = '';
        const fb = document.getElementById('cedula-feedback');
        if (fb) fb.innerHTML = '';
        ['step-cedula','step-found','step-new'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = id === 'step-cedula' ? '' : 'none';
        });
        ['new_nombre','new_apellido','new_telefono','new_direccion'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        setTimeout(() => { const l = document.getElementById('lookup_cedula'); if (l) l.focus(); }, 150);
    }

    async function buscarCedula() {
        const cedula = document.getElementById('lookup_cedula').value.trim();
        if (!cedula) { setFeedback('Ingresa una cédula antes de buscar.', 'warn'); return; }

        const btn = document.getElementById('btn-buscar');
        btn.disabled = true;
        btn.innerHTML = '<svg class="icon" style="animation:spin 0.8s linear infinite"><use href="#icon-refresh-cw"></use></svg> Buscando...';

        // Ocultar alerta previa
        const alert = document.getElementById('form-alert');
        if (alert) { alert.style.display = 'none'; alert.textContent = ''; }

        try {
            const grupoId = document.querySelector('#nuevoParticipanteForm input[name="grupo_san_id"]').value;
            const res  = await fetch('../../api/participantes.php?action=get_by_cedula&cedula=' + encodeURIComponent(cedula) + '&grupo_id=' + grupoId);
            const data = await res.json();

            if (data.success) {
                // Verificar si ya está inscrito en ESTE grupo
                if (data.data.already_inscrito) {
                    if (alert) {
                        alert.textContent = 'Este participante ya está inscrito en este grupo.';
                        alert.style.display = '';
                        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="icon"><use href="#icon-search"></use></svg> Buscar';
                    return;
                }

                const p = data.data.participante;
                document.getElementById('p_nombre').value    = p.nombre;
                document.getElementById('p_apellido').value  = p.apellido;
                document.getElementById('p_cedula').value    = cedula;
                document.getElementById('p_telefono').value  = p.telefono  || '';
                document.getElementById('p_direccion').value = p.direccion || '';

                const initials = (p.nombre[0] || '') + (p.apellido[0] || '');
                document.getElementById('found-avatar').textContent       = initials.toUpperCase();
                document.getElementById('found-name').textContent         = p.nombre + ' ' + p.apellido;
                document.getElementById('found-cedula-display').textContent = cedula;
                document.getElementById('found-tel').textContent          = p.telefono || 'Sin teléfono';

                document.getElementById('step-cedula').style.display = 'none';
                document.getElementById('step-found').style.display  = '';
                document.getElementById('step-new').style.display    = 'none';
            } else {
                document.getElementById('new_cedula').value = cedula;
                document.getElementById('step-cedula').style.display = 'none';
                document.getElementById('step-found').style.display  = 'none';
                document.getElementById('step-new').style.display    = '';
                setFeedback('', '');
                setTimeout(() => { const n = document.getElementById('new_nombre'); if (n) n.focus(); }, 100);
            }
        } catch(e) {
            setFeedback('Error de conexión. Intenta de nuevo.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="icon"><use href="#icon-search"></use></svg> Buscar';
        }
    }

    function setFeedback(msg, type) {
        const el = document.getElementById('cedula-feedback');
        const colors = { warn: 'var(--color-salmon)', error: '#ff6464', ok: 'var(--color-menta)' };
        if (el) {
            el.style.color = colors[type] || 'var(--color-text-tertiary)';
            el.textContent = msg;
        }
    }

    // Spin keyframe
    const styleTagDetalle = document.createElement('style');
    styleTagDetalle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(styleTagDetalle);

    // Form submit
    document.getElementById('nuevoParticipanteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';

        const formData = new FormData(this);
        formData.set('action', 'create');

        try {
            const res  = await fetch('../../api/participantes.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showNotification('Participante inscrito exitosamente', 'success');
                closeModal('nuevoParticipanteModal');
                setTimeout(() => location.reload(), 1200);
            } else {
                const alert = document.getElementById('form-alert');
                if (alert) {
                    alert.textContent = data.message || 'Error al inscribir';
                    alert.style.display = '';
                    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        } catch(err) {
            showNotification('Error de conexión', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }
    });

    // Funciones para remover participante
    function confirmarRemocion(id, nombre) {
        document.getElementById('rem_participante_id').value = id;
        document.getElementById('rem_participante_nombre').textContent = nombre;
        document.getElementById('rem_motivo').value = '';
        openModal('removerParticipanteModal');
    }

    document.getElementById('removerParticipanteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';

        const formData = new FormData(this);
        formData.set('action', 'remove_from_group');

        try {
            const res = await fetch('../../api/participantes.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showNotification(data.message, 'success');
                closeModal('removerParticipanteModal');
                setTimeout(() => location.reload(), 1200);
            } else {
                showNotification(data.message || 'Error al remover', 'error');
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
