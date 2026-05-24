<?php
require_once 'config/database.php';
requireLogin();

$user = getCurrentUser();

// Role check — if admin, redirect to admin dashboard
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'participante') {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

// ── Get and validate grupo_id ─────────────────────────────────────────────
$grupo_id = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
if ($grupo_id <= 0) {
    header('Location: dashboard_participante.php');
    exit;
}

// ── Auth gate: verify the authenticated user is an active participant ─────
$stmt = $pdo->prepare("
    SELECT p.id as participante_id FROM participantes p
    WHERE p.grupo_san_id = ? AND p.usuario_id = ? AND p.activo = 1
");
$stmt->execute([$grupo_id, $user['id']]);
$participante = $stmt->fetch();

if (!$participante) {
    header('Location: dashboard_participante.php?error=acceso_denegado');
    exit;
}

$participante_id = $participante['participante_id'];

// ── Fetch group info ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT gs.*, pr.nombre as producto_nombre, c.nombre as categoria_nombre, c.color as categoria_color,
           (SELECT COUNT(*) FROM participantes WHERE grupo_san_id = gs.id AND activo = 1) as total_miembros
    FROM grupos_san gs
    JOIN productos pr ON gs.producto_id = pr.id
    JOIN categorias c ON pr.categoria_id = c.id
    WHERE gs.id = ?
");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch();

if (!$grupo) {
    header('Location: dashboard_participante.php?error=grupo_no_encontrado');
    exit;
}

// ── Fetch participant's payments ──────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id, numero_cuota, monto, fecha_vencimiento, fecha_pago, estado, metodo_pago, referencia_pago, monto_bs_pagado, notas, comprobante
    FROM pagos WHERE participante_id = ? ORDER BY numero_cuota ASC
");
$stmt->execute([$participante_id]);
$pagos = $stmt->fetchAll();

// ── Compute stats ─────────────────────────────────────────────────────────
$total_cuotas      = count($pagos);
$pagadas           = 0;
$pendientes        = 0;
$atrasadas         = 0;
$en_verificacion   = 0;
$total_pagado_usd  = 0.0;

foreach ($pagos as $p) {
    switch ($p['estado']) {
        case 'pagado':
            $pagadas++;
            $total_pagado_usd += (float)$p['monto'];
            break;
        case 'pendiente':
            $pendientes++;
            break;
        case 'atrasado':
            $atrasadas++;
            break;
        case 'pendiente_verificacion':
            $en_verificacion++;
            break;
    }
}

// ── Fetch turno info ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT numero_turno, estado, fecha_turno, metodo_asignacion
    FROM turnos WHERE participante_id = ? AND grupo_san_id = ?
    ORDER BY numero_turno DESC LIMIT 1
");
$stmt->execute([$participante_id, $grupo_id]);
$turno = $stmt->fetch();

// ── BCV rate ──────────────────────────────────────────────────────────────
$tasa_bcv_hoy = getBcvRate();

// ── Determine if "Reportar Pago" button should show ────────────────────
$can_report = ($pendientes + $atrasadas) > 0;

// Find first pending/atrasado pago to wire into the hero button
$primer_pago_pendiente = null;
foreach ($pagos as $p) {
    if ($p['estado'] === 'pendiente' || $p['estado'] === 'atrasado') {
        $primer_pago_pendiente = $p;
        break;
    }
}

// ── Estado label and color helpers ────────────────────────────────────────
$estado_labels = [
    'en_espera'  => 'En Espera',
    'abierto'    => 'Abierto',
    'en_curso'   => 'En Curso',
    'finalizado' => 'Finalizado',
];

$estado_badge_class = [
    'en_espera'  => 'badge-info',
    'abierto'    => 'badge-success',
    'en_curso'   => 'badge-warning',
    'finalizado' => '',
];

$estado_color = [
    'en_espera'  => 'var(--color-turnos)',
    'abierto'    => 'var(--color-success)',
    'en_curso'   => 'var(--color-warning)',
    'finalizado' => 'var(--color-text-tertiary)',
];

$grupo_estado_label = $estado_labels[$grupo['estado']] ?? ucfirst($grupo['estado']);
$grupo_estado_class = $estado_badge_class[$grupo['estado']] ?? 'badge-info';
$grupo_estado_color_hex = $estado_color[$grupo['estado']] ?? 'var(--color-text-tertiary)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan — <?php echo htmlspecialchars($grupo['nombre']); ?></title>

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        /* ── Header ─────────────────────────────────────────────── */
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

        /* ── Page container ─────────────────────────────────────── */
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-8);
        }

        /* ── Hero card ───────────────────────────────────────────── */
        .grupo-hero {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: var(--space-6);
        }

        .grupo-hero-banner {
            height: 8px;
            background: <?php echo $grupo_estado_color_hex; ?>;
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

        .badge-success {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: var(--font-size-xs);
            font-weight: 600;
            background: var(--color-menta-glow);
            color: var(--color-menta);
            border: 1px solid var(--color-menta);
        }

        .badge-warning {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: var(--font-size-xs);
            font-weight: 600;
            background: var(--color-salmon-glow);
            color: var(--color-salmon);
            border: 1px solid var(--color-salmon);
        }

        .badge-secondary {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: var(--font-size-xs);
            font-weight: 600;
            background: var(--color-surface-section);
            color: var(--color-text-tertiary);
            border: 1px solid var(--glass-border);
        }

        /* ── Summary stats grid ──────────────────────────────────── */
        .stats-grid {
            display: grid;
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
        .summary-card.amber::before  { background: var(--color-secondary); }
        .summary-card.red::before    { background: var(--color-error); }
        .summary-card.blue::before   { background: var(--color-electro); }
        .summary-card.gray::before   { background: var(--color-text-tertiary); }

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

        /* ── Section card ────────────────────────────────────────── */
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

        /* ── Payment table ───────────────────────────────────────── */
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

        /* Status pills */
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
        .pill-blue   { background: var(--color-turnos-tint);     color: var(--color-turnos); }

        /* Turno card */
        .turno-content {
            padding: var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-8);
            flex-wrap: wrap;
        }

        .turno-number {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-lg);
            background: var(--color-primary-tint);
            border: 2px solid var(--color-primary-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-primary);
            flex-shrink: 0;
        }

        .turno-info h3 {
            font-size: var(--font-size-xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-1);
        }

        .turno-info p {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
        }

        .turno-meta {
            display: flex;
            gap: var(--space-3);
            margin-top: var(--space-2);
            flex-wrap: wrap;
        }

        /* ── Responsive ──────────────────────────────────────────── */
        @media (max-width: 768px) {
            .grupo-hero-body { flex-direction: column; }
            .page-container { padding: var(--space-4); }

            .part-table th:nth-child(n+7),
            .part-table td:nth-child(n+7) { display: none; }
        }

        @media (max-width: 480px) {
            .turno-content { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <!-- ══ HEADER ══════════════════════════════════════════════════════════ -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard_participante.php" class="header-logo">MySan</a>

            <div class="user-nav">
                <!-- Back link -->
                <a href="dashboard_participante.php" class="btn btn-outline" style="font-size:var(--font-size-xs);padding:var(--space-2) var(--space-3);white-space:nowrap;text-decoration:none;">
                    <svg class="icon"><use href="#icon-arrow-left"></use></svg>
                    Volver
                </a>

                <!-- Notificaciones -->
                <?php include 'includes/notificaciones_participante.php'; ?>

                <div style="border-left:1px solid var(--glass-border);padding-left:var(--space-4);margin-left:var(--space-2);">
                    <div style="font-weight:bold;font-size:var(--font-size-sm);"><?php echo htmlspecialchars($user['nombre']); ?></div>
                    <a href="logout.php" style="color:var(--color-salmon);font-size:var(--font-size-xs);text-decoration:none;">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ══ MAIN CONTENT ═══════════════════════════════════════════════════ -->
    <div class="page-container">

        <!-- ══ HERO ══════════════════════════════════════════════════════ -->
        <div class="grupo-hero">
            <div class="grupo-hero-banner"></div>
            <div class="grupo-hero-body">
                <div class="grupo-hero-left">
                    <div class="grupo-hero-icon">
                        <svg style="width:32px;height:32px;stroke:var(--color-primary);stroke-width:1.8;">
                            <use href="#icon-grid"></use>
                        </svg>
                    </div>
                    <div>
                        <div class="grupo-hero-name"><?php echo htmlspecialchars($grupo['nombre']); ?></div>
                        <div class="grupo-hero-sub">
                            <?php echo htmlspecialchars($grupo['producto_nombre']); ?> ·
                            $<?php echo number_format($grupo['monto_cuota'], 2); ?> ·
                            <?php echo ucfirst($grupo['frecuencia']); ?> ·
                            Ronda <?php echo (int)$grupo['ronda_actual']; ?>
                        </div>
                        <div class="grupo-hero-badges">
                            <span class="badge-info">
                                <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-calendar"></use></svg>
                                <?php echo ucfirst($grupo['frecuencia']); ?>
                            </span>
                            <span class="badge-info">
                                <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-users"></use></svg>
                                <?php echo (int)$grupo['total_miembros']; ?> miembro<?php echo (int)$grupo['total_miembros'] !== 1 ? 's' : ''; ?>
                            </span>
                            <span class="<?php echo $grupo_estado_class; ?>">
                                <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-activity"></use></svg>
                                <?php echo $grupo_estado_label; ?>
                            </span>
                            <?php if ($grupo['fecha_inicio']): ?>
                            <span class="badge-info">
                                <svg style="width:11px;height:11px;stroke-width:2.5;"><use href="#icon-clock"></use></svg>
                                Inicio: <?php echo date('d/m/Y', strtotime($grupo['fecha_inicio'])); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($tasa_bcv_hoy > 0): ?>
                        <div style="margin-top:var(--space-2);font-size:var(--font-size-xs);color:var(--color-text-tertiary);opacity:0.65;">
                            Tasa BCV: Bs <?php echo number_format($tasa_bcv_hoy, 2); ?> / $
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-self:center;">
                    <button class="btn btn-violeta" onclick="openReportModal(<?php echo $primer_pago_pendiente ? $primer_pago_pendiente['id'] : 0; ?>, <?php echo $primer_pago_pendiente ? $primer_pago_pendiente['numero_cuota'] : ($total_cuotas + 1); ?>, <?php echo $primer_pago_pendiente ? $primer_pago_pendiente['monto'] : $grupo['monto_cuota']; ?>)">
                        <svg class="icon"><use href="#icon-download"></use></svg>
                        Reportar Pago
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ STATS GRID ══════════════════════════════════════════════ -->
        <div class="stats-grid grid-responsive-4">
            <div class="summary-card gray">
                <div class="summary-label">Total Cuotas</div>
                <div class="summary-value"><?php echo $total_cuotas; ?></div>
                <div class="summary-sub">Cuotas del grupo</div>
            </div>
            <div class="summary-card green">
                <div class="summary-label">Pagadas</div>
                <div class="summary-value" style="color:var(--color-primary);"><?php echo $pagadas; ?></div>
                <div class="summary-sub">$<?php echo number_format($total_pagado_usd, 2); ?> pagado</div>
            </div>
            <div class="summary-card amber">
                <div class="summary-label">Pendientes</div>
                <div class="summary-value" style="color:var(--color-secondary);"><?php echo $pendientes; ?></div>
                <div class="summary-sub">Esperando reporte</div>
            </div>
            <div class="summary-card red">
                <div class="summary-label">Atrasadas</div>
                <div class="summary-value" style="color:var(--color-error);"><?php echo $atrasadas; ?></div>
                <div class="summary-sub">Vencidas sin pago</div>
            </div>
            <div class="summary-card blue">
                <div class="summary-label">En Verificación</div>
                <div class="summary-value" style="color:var(--color-electro);"><?php echo $en_verificacion; ?></div>
                <div class="summary-sub">Pendiente de aprobar</div>
            </div>
        </div>

        <!-- ══ TURNO CARD ═══════════════════════════════════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <svg style="width:20px;height:20px;stroke:var(--color-turnos);stroke-width:2;">
                        <use href="#icon-dice"></use>
                    </svg>
                    Tu Turno
                </div>
            </div>
            <?php if ($turno): ?>
            <div class="turno-content">
                <div class="turno-number">#<?php echo (int)$turno['numero_turno']; ?></div>
                <div class="turno-info">
                    <h3>
                        Turno #<?php echo (int)$turno['numero_turno']; ?>
                        <span class="pill pill-<?php
                            echo $turno['estado'] === 'asignado' ? 'amber' : ($turno['estado'] === 'entregado' ? 'green' : 'blue');
                        ?>" style="margin-left:var(--space-2);">
                            <?php echo ucfirst($turno['estado']); ?>
                        </span>
                    </h3>
                    <div class="turno-meta">
                        <?php if ($turno['estado'] === 'pendiente'): ?>
                            <p>Esperando asignación. El administrador asignará los turnos mediante sorteo o asignación directa.</p>
                        <?php elseif ($turno['estado'] === 'asignado' && $turno['fecha_turno']): ?>
                            <p>📅 Fecha asignada: <?php echo date('d/m/Y', strtotime($turno['fecha_turno'])); ?></p>
                            <p>🎲 Método: <?php echo $turno['metodo_asignacion'] === 'aleatorio' ? 'Sorteo' : 'Asignación directa'; ?></p>
                        <?php elseif ($turno['estado'] === 'entregado' && $turno['fecha_turno']): ?>
                            <p>✅ Entregado el <?php echo date('d/m/Y', strtotime($turno['fecha_turno'])); ?></p>
                            <p>🎲 Método: <?php echo $turno['metodo_asignacion'] === 'aleatorio' ? 'Sorteo' : 'Asignación directa'; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="padding:var(--space-8);text-align:center;color:var(--color-text-tertiary);">
                <svg style="width:36px;height:36px;stroke:currentColor;opacity:0.4;margin-bottom:var(--space-3);">
                    <use href="#icon-dice"></use>
                </svg>
                <p>Aún no se te ha asignado un turno.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══ PAYMENT HISTORY ══════════════════════════════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <svg style="width:20px;height:20px;stroke:var(--color-primary);stroke-width:2;">
                        <use href="#icon-file-text"></use>
                    </svg>
                    Historial de Pagos
                    <?php if ($total_cuotas > 0): ?>
                    <span style="background:var(--color-primary-tint);color:var(--color-primary);
                                 font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px;">
                        <?php echo $total_cuotas; ?> cuota<?php echo $total_cuotas !== 1 ? 's' : ''; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($pagos)): ?>
            <div style="padding:var(--space-12);text-align:center;color:var(--color-text-tertiary);">
                <svg style="width:40px;height:40px;stroke:currentColor;opacity:0.4;margin-bottom:var(--space-3);">
                    <use href="#icon-credit-card"></use>
                </svg>
                <p>No hay pagos registrados para este grupo.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="part-table">
                    <thead>
                        <tr>
                            <th># Cuota</th>
                            <th>Monto ($)</th>
                            <th>Vencimiento</th>
                            <th>Fecha Pago</th>
                            <th>Estado</th>
                            <th>Referencia</th>
                            <th>Método</th>
                            <th>Recibo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $p): ?>
                        <?php
                            $estado_pill_class = match ($p['estado']) {
                                'pagado'                => 'pill-green',
                                'pendiente'             => 'pill-amber',
                                'atrasado'              => 'pill-red',
                                'pendiente_verificacion'=> 'pill-blue',
                                default                 => 'pill-amber',
                            };
                            $estado_display = match ($p['estado']) {
                                'pagado'                => 'Pagado',
                                'pendiente'             => 'Pendiente',
                                'atrasado'              => 'Atrasado',
                                'pendiente_verificacion'=> 'En Verificación',
                                default                 => $p['estado'],
                            };
                        ?>
                        <tr>
                            <td><?php echo (int)$p['numero_cuota']; ?></td>
                            <td><strong>$<?php echo number_format($p['monto'], 2); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($p['fecha_vencimiento'])); ?></td>
                            <td><?php echo $p['fecha_pago'] ? date('d/m/Y', strtotime($p['fecha_pago'])) : '—'; ?></td>
                            <td><span class="pill <?php echo $estado_pill_class; ?>"><?php echo $estado_display; ?></span></td>
                            <td style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">
                                <?php echo htmlspecialchars($p['referencia_pago'] ?? '—'); ?>
                            </td>
                            <td style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">
                                <?php echo htmlspecialchars($p['metodo_pago'] ?? '—'); ?>
                            </td>
                            <td>
                                <?php if ($p['estado'] === 'pagado'): ?>
                                    <a href="api/comprobantes.php?action=recibo&id=<?php echo $p['id']; ?>"
                                       target="_blank"
                                       style="color:var(--color-primary);text-decoration:none;font-weight:600;font-size:var(--font-size-xs);display:inline-flex;align-items:center;gap:4px;">
                                        <svg style="width:14px;height:14px;stroke:currentColor;stroke-width:2;"><use href="#icon-eye"></use></svg>
                                        Ver
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--color-text-tertiary);font-size:var(--font-size-xs);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ══ REPORT MODAL ═══════════════════════════════════════════════════ -->
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
                <input type="hidden" id="reportMontoCuota" name="monto">
                <input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-4);">
                    <div class="form-group">
                        <label class="form-label" for="reportNumeroCuota">Cuota # *</label>
                        <input type="number" id="reportNumeroCuota" name="numero_cuota" class="form-input"
                               min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reportMontoDisplay">Monto ($) *</label>
                        <input type="number" id="reportMontoDisplay" class="form-input"
                               step="0.01" min="0.01" readonly
                               style="background:var(--color-surface-section);cursor:not-allowed;">
                    </div>
                </div>

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

    <script>
        function openReportModal(pagoId, numeroCuota, monto) {
            document.getElementById('reportPagoId').value = pagoId;
            document.getElementById('reportNumeroCuota').value = numeroCuota;
            document.getElementById('reportMontoDisplay').value = parseFloat(monto).toFixed(2);
            document.getElementById('reportMontoCuota').value = parseFloat(monto).toFixed(2);

            if (pagoId > 0) {
                document.getElementById('reportNumeroCuota').readOnly = true;
                document.getElementById('reportNumeroCuota').style.background = 'var(--color-surface-section)';
                document.getElementById('reportNumeroCuota').style.cursor = 'not-allowed';
                document.getElementById('modalDesc').textContent =
                    'Cuota #' + numeroCuota + ' — $' + parseFloat(monto).toFixed(2) + '. Ingresa el número de referencia y adjunta tu comprobante.';
            } else {
                document.getElementById('reportNumeroCuota').readOnly = false;
                document.getElementById('reportNumeroCuota').style.background = '';
                document.getElementById('reportNumeroCuota').style.cursor = '';
                document.getElementById('modalDesc').textContent =
                    'Registra el pago de tu cuota. Indica el número de cuota que estás pagando (puede ser una cuota adelantada).';
            }

            document.getElementById('reportAlert').style.display = 'none';
            document.getElementById('reportForm').reset();
            document.getElementById('reportPagoId').value = pagoId;
            document.getElementById('reportNumeroCuota').value = numeroCuota;
            document.getElementById('reportMontoDisplay').value = parseFloat(monto).toFixed(2);
            document.getElementById('reportMontoCuota').value = parseFloat(monto).toFixed(2);
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
                    setTimeout(function () {
                        window.location.href = 'detalle-participante.php?grupo_id=<?php echo $grupo_id; ?>';
                    }, 1500);
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
