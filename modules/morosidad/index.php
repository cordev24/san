<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// --- Filtros ---
$filtro_grupo     = $_GET['grupo'] ?? '';
$filtro_mora      = $_GET['mora'] ?? '';
$filtro_search    = trim($_GET['search'] ?? '');
$auto_participante = (int)($_GET['participante'] ?? 0);

// --- Summary totals ---
$sqlTotal = "SELECT COUNT(DISTINCT p.id) AS total_deudores,
                    COALESCE(SUM(pg.sub), 0) AS total_adeudado,
                    COALESCE(MAX(pg.dias), 0) AS peor_dias
             FROM (
                 SELECT p.id, pg.monto AS sub,
                        DATEDIFF(CURDATE(), pg.fecha_vencimiento) AS dias
                 FROM participantes p
                 JOIN pagos pg ON pg.participante_id = p.id
                 WHERE pg.estado IN ('pendiente', 'atrasado')
                   AND pg.fecha_vencimiento < CURDATE()
                   AND p.activo = 1
             ) pg
             JOIN participantes p ON p.id = pg.id";
$stmtTotal = $pdo->query($sqlTotal);
$totals = $stmtTotal->fetch(PDO::FETCH_ASSOC);
$totalDeudores = (int)($totals['total_deudores'] ?? 0);
$totalAdeudado = (float)($totals['total_adeudado'] ?? 0);
$peorDias      = (int)($totals['peor_dias'] ?? 0);

// --- Deudor list ---
$where  = "pg.estado IN ('pendiente', 'atrasado') AND pg.fecha_vencimiento < CURDATE() AND p.activo = 1";
$params = [];

if ($filtro_grupo !== '') {
    $where   .= " AND g.id = ?";
    $params[] = $filtro_grupo;
}
if ($filtro_mora !== '') {
    switch ($filtro_mora) {
        case '0-7':
            $where .= " AND DATEDIFF(CURDATE(), pg.fecha_vencimiento) BETWEEN 0 AND 7"; break;
        case '8-15':
            $where .= " AND DATEDIFF(CURDATE(), pg.fecha_vencimiento) BETWEEN 8 AND 15"; break;
        case '16-30':
            $where .= " AND DATEDIFF(CURDATE(), pg.fecha_vencimiento) BETWEEN 16 AND 30"; break;
        case '30+':
            $where .= " AND DATEDIFF(CURDATE(), pg.fecha_vencimiento) > 30"; break;
    }
}

$sql = "SELECT p.id AS participante_id,
               p.nombre, p.apellido, p.cedula, p.telefono,
               g.id AS grupo_id, g.nombre AS grupo_nombre,
               c.id AS categoria_id,
               COUNT(pg.id) AS cuotas_atrasadas,
               SUM(pg.monto) AS total_adeudado,
               MIN(pg.fecha_vencimiento) AS primer_vencimiento,
               MAX(pg.fecha_vencimiento) AS ultimo_vencimiento,
               DATEDIFF(CURDATE(), MIN(pg.fecha_vencimiento)) AS dias_mora
        FROM participantes p
        JOIN grupos_san g ON p.grupo_san_id = g.id
        JOIN productos pr ON g.producto_id = pr.id
        JOIN categorias c ON pr.categoria_id = c.id
        JOIN pagos pg ON pg.participante_id = p.id
        WHERE $where
        GROUP BY p.id, p.nombre, p.apellido, p.cedula, p.telefono, g.id, g.nombre, c.id
        ORDER BY dias_mora DESC, total_adeudado DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deudores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Client-side search filter
if ($filtro_search !== '') {
    $searchLower = mb_strtolower($filtro_search, 'UTF-8');
    $deudores = array_filter($deudores, function ($d) use ($searchLower) {
        return mb_strpos(mb_strtolower($d['nombre'] . ' ' . $d['apellido'], 'UTF-8'), $searchLower) !== false
            || mb_strpos($d['cedula'], $searchLower) !== false;
    });
}

// --- Fetch all overdue payments (for detail rows) ---
$deudorIds = array_column($deudores, 'participante_id');
$pagos_mora = [];
if (!empty($deudorIds)) {
    $placeholders = implode(',', array_fill(0, count($deudorIds), '?'));
    $stmtPagos = $pdo->prepare("
        SELECT pg.id, pg.participante_id, pg.numero_cuota, pg.monto,
               pg.fecha_vencimiento, pg.estado,
               DATEDIFF(CURDATE(), pg.fecha_vencimiento) AS dias_atraso
        FROM pagos pg
        WHERE pg.participante_id IN ($placeholders)
          AND pg.estado IN ('pendiente', 'atrasado')
          AND pg.fecha_vencimiento < CURDATE()
        ORDER BY pg.participante_id, pg.fecha_vencimiento
    ");
    $stmtPagos->execute($deudorIds);
    $rows = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $pid = $row['participante_id'];
        if (!isset($pagos_mora[$pid])) {
            $pagos_mora[$pid] = [];
        }
        $pagos_mora[$pid][] = $row;
    }
}

// --- Groups for filter dropdown ---
$stmtGrupos = $pdo->query("SELECT id, nombre FROM grupos_san WHERE estado != 'finalizado' ORDER BY nombre");
$grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

// Count groups with delinquency
$stmtGruposMora = $pdo->query("
    SELECT COUNT(DISTINCT g.id)
    FROM grupos_san g
    JOIN participantes p ON p.grupo_san_id = g.id
    JOIN pagos pg ON pg.participante_id = p.id
    WHERE pg.estado IN ('pendiente','atrasado')
      AND pg.fecha_vencimiento < CURDATE()
      AND p.activo = 1
");
$gruposConMora = (int)$stmtGruposMora->fetchColumn();
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
    <title>MySan - Morosidad</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        /* ---------- Summary cards ---------- */
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
            border-color: var(--color-salmon);
            background: linear-gradient(135deg, var(--color-surface), color-mix(in srgb, var(--color-salmon) 6%, var(--color-surface)));
        }

        .summary-card--violeta {
            border-color: var(--color-violeta);
            background: linear-gradient(135deg, var(--color-surface), color-mix(in srgb, var(--color-violeta) 6%, var(--color-surface)));
        }

        .summary-card--menta {
            border-color: var(--color-menta);
            background: linear-gradient(135deg, var(--color-surface), color-mix(in srgb, var(--color-menta) 6%, var(--color-surface)));
        }

        .summary-icon {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            padding: 10px;
            border-radius: var(--radius-md);
        }

        .summary-icon--salmon {
            background: color-mix(in srgb, var(--color-salmon) 20%, transparent);
            color: var(--color-salmon);
        }

        .summary-icon--violeta {
            background: color-mix(in srgb, var(--color-violeta) 20%, transparent);
            color: var(--color-violeta);
        }

        .summary-icon--menta {
            background: color-mix(in srgb, var(--color-menta) 20%, transparent);
            color: var(--color-menta);
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

        .summary-info .value--salmon { color: var(--color-salmon); }
        .summary-info .value--violeta { color: var(--color-violeta); }
        .summary-info .value--menta { color: var(--color-menta); }

        /* ---------- Filters ---------- */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-3);
            margin-bottom: var(--space-5);
            align-items: center;
        }

        .filters-bar .filter-group {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .filters-bar label {
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
            white-space: nowrap;
        }

        .filters-bar select,
        .filters-bar input[type="text"] {
            padding: var(--space-2) var(--space-3);
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            background: var(--color-surface);
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
            min-width: 160px;
        }

        .filters-bar select:focus,
        .filters-bar input[type="text"]:focus {
            outline: none;
            border-color: var(--color-violeta);
        }

        .btn-clear {
            padding: var(--space-2) var(--space-4);
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            background: transparent;
            color: var(--color-text-secondary);
            cursor: pointer;
            font-size: var(--font-size-sm);
            text-decoration: none;
        }

        .btn-clear:hover {
            background: var(--color-surface-hover);
            color: var(--color-text-primary);
        }

        /* ---------- Table ---------- */
        .table-container {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .table-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--glass-border);
        }

        .table-header-row .count-badge {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
        }

        .table-header-row .count-badge strong {
            color: var(--color-text-primary);
        }

        .morosidad-table {
            width: 100%;
            border-collapse: collapse;
        }

        .morosidad-table th {
            text-align: left;
            padding: var(--space-3) var(--space-5);
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-tertiary);
            font-weight: var(--font-weight-semibold);
            border-bottom: 1px solid var(--glass-border);
            background: var(--color-surface-hover);
        }

        .morosidad-table td {
            padding: var(--space-3) var(--space-5);
            border-bottom: 1px solid var(--glass-border);
            font-size: var(--font-size-sm);
            color: var(--color-text-primary);
            vertical-align: middle;
        }

        .morosidad-table tr:last-child td {
            border-bottom: none;
        }

        .morosidad-table tr.detail-row td {
            padding: 0;
            border-bottom: 1px solid var(--glass-border);
            background: color-mix(in srgb, var(--color-salmon) 4%, var(--color-surface));
        }

        .morosidad-table tr.detail-row:last-child td {
            border-bottom: 1px solid var(--glass-border);
        }

        .detail-inner {
            padding: var(--space-4) var(--space-5) var(--space-4) calc(var(--space-5) + 40px);
            display: none;
        }

        .detail-inner.open {
            display: block;
        }

        .detail-title {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-3);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .detail-title svg {
            color: var(--color-salmon);
            width: 18px;
            height: 18px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--space-3);
        }

        .detail-pago-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            padding: var(--space-3) var(--space-4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-3);
        }

        .detail-pago-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .detail-pago-info .cuota-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
        }

        .detail-pago-info .cuota-sub {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }

        .detail-pago-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }

        .detail-pago-right .monto {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-bold);
            color: var(--color-salmon);
        }

        .detail-pago-right .dias-atraso {
            font-size: var(--font-size-xs);
            padding: 1px 6px;
            border-radius: var(--radius-full);
            font-weight: var(--font-weight-semibold);
        }

        .morosidad-table tr:hover td {
            background: var(--color-surface-hover);
        }

        .morosidad-table tr.detail-row:hover td {
            background: color-mix(in srgb, var(--color-salmon) 4%, var(--color-surface));
        }

        .participant-info .name {
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
        }

        .participant-info .cedula {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }

        .group-link {
            color: var(--color-violeta);
            text-decoration: none;
            font-weight: var(--font-weight-medium);
        }

        .group-link:hover {
            text-decoration: underline;
        }

        .dias-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
        }

        .dias-badge--critico { background: color-mix(in srgb, var(--color-salmon) 20%, transparent); color: var(--color-salmon); }
        .dias-badge--alto    { background: color-mix(in srgb, var(--color-violeta) 20%, transparent); color: var(--color-violeta); }
        .dias-badge--medio   { background: color-mix(in srgb, var(--color-menta-glow) 30%, transparent); color: var(--color-menta); }
        .dias-badge--bajo    { background: color-mix(in srgb, var(--color-text-tertiary) 20%, transparent); color: var(--color-text-secondary); }

        .amount {
            font-weight: var(--font-weight-semibold);
            font-variant-numeric: tabular-nums;
        }

        .amount--salmon { color: var(--color-salmon); }

        .cuotas-cell .vencimientos {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }

        .btn-ver {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            border: none;
            background: color-mix(in srgb, var(--color-violeta) 15%, transparent);
            color: var(--color-violeta);
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .btn-ver:hover {
            background: color-mix(in srgb, var(--color-violeta) 25%, transparent);
        }

        .btn-ver.active {
            background: color-mix(in srgb, var(--color-salmon) 20%, transparent);
            color: var(--color-salmon);
        }

        .btn-ver svg {
            width: 14px;
            height: 14px;
        }

        .empty-state {
            text-align: center;
            padding: var(--space-10) var(--space-5);
        }

        .empty-state .empty-icon {
            width: 64px;
            height: 64px;
            color: var(--color-menta);
            margin-bottom: var(--space-4);
        }

        .empty-state h3 {
            font-size: var(--font-size-xl);
            color: var(--color-text-primary);
            margin-bottom: var(--space-2);
        }

        .empty-state p {
            color: var(--color-text-tertiary);
            font-size: var(--font-size-sm);
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 700px) {
            .filters-bar { flex-direction: column; align-items: stretch; }
            .filters-bar select,
            .filters-bar input[type="text"] { min-width: unset; width: 100%; }
            .morosidad-table th:nth-child(4),
            .morosidad-table td:nth-child(4),
            .morosidad-table th:nth-child(6),
            .morosidad-table td:nth-child(6) { display: none; }
        }

        @media (max-width: 500px) {
            .morosidad-table th:nth-child(3),
            .morosidad-table td:nth-child(3) { display: none; }
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
        $headerBackUrl    = '../../dashboard.php';
        $headerBackLabel  = 'Volver al Dashboard';
        include '../../includes/header.php';
        ?>

        <!-- Page Header -->
        <div class="page-header" style="padding: var(--space-6); margin-bottom: var(--space-4); border-bottom: 1px solid var(--glass-border);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
                <div>
                    <h1 class="page-title" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-3);">
                        <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-salmon);">
                            <use href="#icon-alert-triangle"></use>
                        </svg>
                        Morosidad
                    </h1>
                    <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                        Control de deudores y cuotas vencidas en todos los grupos San.
                    </p>
                </div>
            </div>
        </div>

        <div style="max-width: 1600px; margin: 0 auto; padding: 0 var(--space-6);">

            <!-- Summary Cards -->
            <div class="summary-grid grid-responsive-4">
                <div class="summary-card summary-card--salmon">
                    <div class="summary-icon summary-icon--salmon">
                        <svg class="icon" style="width:24px;height:24px;"><use href="#icon-alert-triangle"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Total Deudores</h3>
                        <span class="value value--salmon"><?php echo $totalDeudores; ?></span>
                    </div>
                </div>

                <div class="summary-card summary-card--violeta">
                    <div class="summary-icon summary-icon--violeta">
                        <svg class="icon" style="width:24px;height:24px;"><use href="#icon-dollar-sign"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Total Adeudado</h3>
                        <span class="value value--violeta">$<?php echo number_format($totalAdeudado, 2); ?></span>
                    </div>
                </div>

                <div class="summary-card summary-card--menta">
                    <div class="summary-icon summary-icon--menta">
                        <svg class="icon" style="width:24px;height:24px;"><use href="#icon-users"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Grupos con Mora</h3>
                        <span class="value value--menta"><?php echo $gruposConMora; ?></span>
                    </div>
                </div>

                <div class="summary-card summary-card--salmon">
                    <div class="summary-icon summary-icon--salmon">
                        <svg class="icon" style="width:24px;height:24px;"><use href="#icon-clock"></use></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Peor Mora</h3>
                        <span class="value value--salmon"><?php echo $peorDias; ?> dias</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="filters-bar">
                <div class="filter-group">
                    <label for="grupo">Grupo:</label>
                    <select name="grupo" id="grupo" onchange="this.form.submit()">
                        <option value="">Todos los grupos</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?php echo $g['id']; ?>" <?php echo $filtro_grupo == $g['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="mora">Dias de Mora:</label>
                    <select name="mora" id="mora" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="0-7" <?php echo $filtro_mora === '0-7' ? 'selected' : ''; ?>>0 - 7 dias</option>
                        <option value="8-15" <?php echo $filtro_mora === '8-15' ? 'selected' : ''; ?>>8 - 15 dias</option>
                        <option value="16-30" <?php echo $filtro_mora === '16-30' ? 'selected' : ''; ?>>16 - 30 dias</option>
                        <option value="30+" <?php echo $filtro_mora === '30+' ? 'selected' : ''; ?>>Mas de 30 dias</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search">Buscar:</label>
                    <input type="text" name="search" id="search" placeholder="Nombre o cedula..."
                        value="<?php echo htmlspecialchars($filtro_search); ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-sm" style="display:none;">Filtrar</button>

                <?php if ($filtro_grupo !== '' || $filtro_mora !== '' || $filtro_search !== ''): ?>
                    <a href="?" class="btn-clear">Limpiar filtros</a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="table-container table-responsive">
                <div class="table-header-row">
                    <span class="count-badge">
                        <strong><?php echo count($deudores); ?></strong> deudor<?php echo count($deudores) !== 1 ? 'es' : ''; ?> encontrado<?php echo count($deudores) !== 1 ? 's' : ''; ?>
                    </span>
                </div>

                <?php if (count($deudores) > 0): ?>
                    <table class="morosidad-table">
                        <thead>
                            <tr>
                                <th>Participante</th>
                                <th>Contacto</th>
                                <th>Grupo</th>
                                <th>Cuotas</th>
                                <th>Monto</th>
                                <th>Dias de Mora</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deudores as $d): 
                                $dias = (int)$d['dias_mora'];
                                if ($dias > 30):
                                    $badge = 'dias-badge--critico';
                                elseif ($dias > 15):
                                    $badge = 'dias-badge--alto';
                                elseif ($dias > 7):
                                    $badge = 'dias-badge--medio';
                                else:
                                    $badge = 'dias-badge--bajo';
                                endif;
                                $pid = $d['participante_id'];
                                $detalles = $pagos_mora[$pid] ?? [];
                            ?>
                                <tr class="main-row" data-participante="<?php echo $pid; ?>">
                                    <td>
                                        <div class="participant-info">
                                            <span class="name"><?php echo htmlspecialchars($d['nombre'] . ' ' . $d['apellido']); ?></span>
                                            <span class="cedula"><?php echo htmlspecialchars($d['cedula']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($d['telefono']): ?>
                                            <span style="color:var(--color-text-secondary);font-size:var(--font-size-sm);">
                                                <?php echo htmlspecialchars($d['telefono']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:var(--color-text-tertiary);font-size:var(--font-size-xs);">Sin registro</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../../modules/categoria/grupo.php?id=<?php echo $d['categoria_id']; ?>&grupo_id=<?php echo $d['grupo_id']; ?>" class="group-link">
                                            <?php echo htmlspecialchars($d['grupo_nombre']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="cuotas-cell">
                                            <span class="amount amount--salmon"><?php echo (int)$d['cuotas_atrasadas']; ?> cuota<?php echo (int)$d['cuotas_atrasadas'] !== 1 ? 's' : ''; ?></span>
                                            <span class="vencimientos"><?php echo date('d/m/Y', strtotime($d['primer_vencimiento'])); ?> - <?php echo date('d/m/Y', strtotime($d['ultimo_vencimiento'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="amount amount--salmon">$<?php echo number_format((float)$d['total_adeudado'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="dias-badge <?php echo $badge; ?>">
                                            <?php echo $dias; ?> dias
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-ver" onclick="toggleDetalle(<?php echo $pid; ?>)">
                                            <svg class="icon"><use href="#icon-eye"></use></svg>
                                            <span>Ver</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="detail-row" data-participante="<?php echo $pid; ?>">
                                    <td colspan="7">
                                        <div class="detail-inner" id="detalle-<?php echo $pid; ?>">
                                            <div class="detail-title">
                                                <svg><use href="#icon-alert-circle"></use></svg>
                                                Cuotas vencidas
                                            </div>
                                            <div class="detail-grid">
                                                <?php foreach ($detalles as $pago): 
                                                    $dias_atraso = (int)$pago['dias_atraso'];
                                                    if ($dias_atraso > 30):
                                                        $dias_class = 'dias-badge--critico';
                                                    elseif ($dias_atraso > 15):
                                                        $dias_class = 'dias-badge--alto';
                                                    elseif ($dias_atraso > 7):
                                                        $dias_class = 'dias-badge--medio';
                                                    else:
                                                        $dias_class = 'dias-badge--bajo';
                                                    endif;
                                                ?>
                                                    <div class="detail-pago-card">
                                                        <div class="detail-pago-info">
                                                            <span class="cuota-label">Cuota #<?php echo (int)$pago['numero_cuota']; ?></span>
                                                            <span class="cuota-sub">Vencimiento: <?php echo date('d/m/Y', strtotime($pago['fecha_vencimiento'])); ?></span>
                                                        </div>
                                                        <div class="detail-pago-right">
                                                            <span class="monto">$<?php echo number_format((float)$pago['monto'], 2); ?></span>
                                                            <span class="dias-atraso <?php echo $dias_class; ?>">
                                                                <?php echo $dias_atraso; ?> dias
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <svg class="icon empty-icon"><use href="#icon-check-circle"></use></svg>
                        <h3>No hay deudores</h3>
                        <p>Todos los participantes estan al dia con sus pagos.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="../../assets/js/shared.js"></script>
    <script>
        function toggleDetalle(participanteId) {
            var inner = document.getElementById('detalle-' + participanteId);
            var btn = document.querySelector('.main-row[data-participante="' + participanteId + '"] .btn-ver');
            var btnText = btn.querySelector('span');

            if (!inner) return;

            inner.classList.toggle('open');

            if (inner.classList.contains('open')) {
                btn.classList.add('active');
                btn.querySelector('use').setAttribute('href', '#icon-x');
                btnText.textContent = 'Cerrar';
            } else {
                btn.classList.remove('active');
                btn.querySelector('use').setAttribute('href', '#icon-eye');
                btnText.textContent = 'Ver';
            }
        }
    </script>

    <?php if ($auto_participante > 0): ?>
    <script>
        // Auto-expand detail row when arriving from notification
        document.addEventListener('DOMContentLoaded', function () {
            var pid = <?php echo $auto_participante; ?>;
            toggleDetalle(pid);
            // Scroll to the row
            var row = document.querySelector('.main-row[data-participante="' + pid + '"]');
            if (row) {
                setTimeout(function () {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.style.transition = 'background 0.5s';
                    row.style.background = 'color-mix(in srgb, var(--color-violeta) 15%, transparent)';
                    setTimeout(function () {
                        row.style.background = '';
                    }, 2000);
                }, 300);
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>
