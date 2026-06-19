<?php
require_once 'config/database.php';
requireLogin();
$user = getCurrentUser();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'participante') {
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch available groups (open, with spots, not already joined by this user)
$stmtAvail = $pdo->prepare("
    SELECT gs.*, p.nombre AS producto_nombre, p.imagen AS producto_imagen, p.marca, c.nombre AS categoria_nombre, c.color AS categoria_color
    FROM grupos_san gs
    JOIN productos p ON gs.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    WHERE gs.estado = 'abierto'
      AND gs.cupos_ocupados < gs.cupos_totales
      AND gs.id NOT IN (
          SELECT grupo_san_id FROM participantes WHERE usuario_id = ? AND activo = 1
      )
    ORDER BY gs.fecha_inicio ASC
");
$stmtAvail->execute([$user['id']]);
$grupos_disponibles = $stmtAvail->fetchAll();

// Load participants for each available group
$participantes_por_grupo = [];
$categorias = [];
if (!empty($grupos_disponibles)) {
    $ids = array_column($grupos_disponibles, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmtPart = $pdo->prepare("
        SELECT grupo_san_id, nombre, apellido
        FROM participantes
        WHERE grupo_san_id IN ($placeholders) AND activo = 1
        ORDER BY created_at ASC
    ");
    $stmtPart->execute($ids);
    while ($row = $stmtPart->fetch()) {
        $gid = $row['grupo_san_id'];
        if (!isset($participantes_por_grupo[$gid])) $participantes_por_grupo[$gid] = [];
        $participantes_por_grupo[$gid][] = ['nombre' => $row['nombre'], 'apellido' => $row['apellido']];
    }

    // Collect unique categories for the filter
    foreach ($grupos_disponibles as $g) {
        $catKey = $g['categoria_nombre'];
        if (!isset($categorias[$catKey])) {
            $categorias[$catKey] = $g['categoria_color'];
        }
    }
    ksort($categorias);
}

// Icon mapping for categories (matches feather sprite available icons)
$cat_icon_map = [
    'Electrodomésticos' => 'cpu',
    'Telefonía'         => 'smartphone',
    'Motocicletas'       => 'motorcycle',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sanes Disponibles - MySan</title>
    
    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        /* ───── Header ───── */
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
            writing-mode: horizontal-tb;
            transform: none;
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

        /* ───── Hero ───── */
        .hero-join {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-16) var(--space-8) var(--space-10);
            text-align: center;
            position: relative;
        }
        .hero-join::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--color-primary-glow), transparent);
        }
        .hero-join h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--color-text-primary);
            margin-bottom: var(--space-3);
            letter-spacing: -0.025em;
        }
        .hero-join h1 span {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-join p {
            font-size: var(--font-size-lg);
            color: var(--color-text-tertiary);
            max-width: 540px;
            margin: 0 auto var(--space-8);
        }

        /* ───── Toolbar ───── */
        .toolbar {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-8) var(--space-6);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            flex-wrap: wrap;
        }
        .toolbar .search-wrap {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .toolbar .search-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--color-text-tertiary);
            pointer-events: none;
        }
        .toolbar .search-wrap input {
            width: 100%;
            padding: 10px 14px 10px 42px;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            background: var(--color-surface);
            font-size: var(--font-size-sm);
            color: var(--color-text-primary);
            transition: all var(--transition-base);
        }
        .toolbar .search-wrap input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-glow);
        }
        .toolbar .search-wrap input::placeholder {
            color: var(--color-text-tertiary);
        }
        .toolbar select {
            padding: 10px 14px;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            background: var(--color-surface);
            font-size: var(--font-size-sm);
            color: var(--color-text-primary);
            cursor: pointer;
            transition: all var(--transition-base);
            min-width: 140px;
        }
        .toolbar select:focus {
            outline: none;
            border-color: var(--color-primary);
        }
        .toolbar .result-count {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            white-space: nowrap;
        }

        /* ───── Cards Grid ───── */
        .cards-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-8) var(--space-16);
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: var(--space-5);
        }

        /* ───── Single Card ───── */
        .join-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all var(--transition-base);
        }
        .join-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }
        .join-card .card-img-strip {
            height: 140px;
            width: 100%;
            overflow: hidden;
            background: var(--color-background);
            border-bottom: 1px solid var(--glass-border);
        }
        .join-card .card-img-strip img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-base);
        }
        .join-card:hover .card-img-strip img {
            transform: scale(1.05);
        }
        .join-card .color-strip {
            height: 5px;
        }
        .join-card .card-body {
            padding: var(--space-6);
        }

        .card-head {
            display: flex;
            align-items: flex-start;
            gap: var(--space-4);
            margin-bottom: var(--space-5);
        }
        .card-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0,0,0,0.06);
            background: rgba(0,0,0,0.02);
        }
        .card-icon-wrap svg {
            width: 28px;
            height: 28px;
        }
        .card-info {
            flex: 1;
            min-width: 0;
        }
        .card-info h3 {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            margin: 0;
        }
        .card-info .card-sub {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
            margin-top: 2px;
        }
        .card-info .card-sub .cat-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 600;
        }

        .card-progress {
            margin-bottom: var(--space-5);
        }
        .card-progress .cp-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: var(--font-size-xs);
            margin-bottom: var(--space-2);
        }
        .card-progress .cp-head .cp-label {
            color: var(--color-text-secondary);
            font-weight: 500;
        }
        .card-progress .cp-head .cp-val {
            font-weight: var(--font-weight-semibold);
        }
        .card-progress .cp-bar {
            width: 100%;
            height: 7px;
            border-radius: 4px;
            background: rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-progress .cp-bar .cp-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
            margin-bottom: var(--space-5);
        }
        .card-details .det-section h4 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-text-tertiary);
            font-weight: 600;
            margin-bottom: var(--space-3);
        }
        .card-details .det-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-1) var(--space-2);
            font-size: var(--font-size-sm);
        }
        .card-details .det-grid .dg-label {
            color: var(--color-text-tertiary);
        }
        .card-details .det-grid .dg-val {
            font-weight: 500;
            color: var(--color-text-primary);
        }
        .card-details .det-grid .dg-val.price {
            font-weight: 600;
            color: var(--color-primary);
        }
        .card-details .det-grid .dg-val.total {
            font-weight: 600;
            color: var(--color-secondary);
        }

        .card-participants {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding-top: var(--space-4);
            margin-bottom: var(--space-5);
        }
        .card-participants .cp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-3);
        }
        .card-participants .cp-header h4 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-text-tertiary);
            font-weight: 600;
        }
        .card-participants .cp-header .cp-free {
            font-size: var(--font-size-xs);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 20px;
            padding: 3px 10px 3px 3px;
            font-size: var(--font-size-xs);
            color: var(--color-text-secondary);
        }
        .pill .pill-av {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
        }
        .pill-extra {
            display: inline-flex;
            align-items: center;
            border: 1px dashed rgba(0,0,0,0.10);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }
        .pill-empty {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
            font-style: italic;
        }

        .card-action {
            display: flex;
            gap: var(--space-3);
        }
        .card-action .btn {
            flex: 1;
        }

        /* ───── Empty state ───── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: var(--space-16) var(--space-8);
            color: var(--color-text-tertiary);
        }
        .empty-state svg {
            width: 56px;
            height: 56px;
            opacity: 0.25;
            margin-bottom: var(--space-5);
            color: var(--color-text-tertiary);
        }
        .empty-state h3 {
            font-size: var(--font-size-xl);
            color: var(--color-text-secondary);
            margin-bottom: var(--space-2);
        }

        /* ───── Join alert floating ───── */
        .join-toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            z-index: var(--z-toast);
            padding: 14px 28px;
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-sm);
            box-shadow: var(--shadow-lg);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
            pointer-events: none;
            max-width: 90%;
        }
        .join-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            pointer-events: auto;
        }
        .join-toast.success {
            background: var(--color-secondary);
            color: #fff;
        }
        .join-toast.error {
            background: var(--color-error);
            color: #fff;
        }

        /* ───── Responsive ───── */
        @media (max-width: 1100px) {
            .cards-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 700px) {
            .cards-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .hero-join { padding: var(--space-8) var(--space-4) var(--space-6); }
            .hero-join h1 { font-size: 1.5rem; }
            .cards-grid { padding: 0 var(--space-4) var(--space-10); gap: var(--space-4); }
            .join-card .card-body { padding: var(--space-4); }
            .card-details { grid-template-columns: 1fr; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar .search-wrap { min-width: 0; }
            .toolbar .result-count { text-align: center; }
        }
    </style>
</head>
<body>

    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <!-- ═══════════ Header ═══════════ -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard_participante.php" class="header-logo">MySan</a>
            
            <div class="user-nav">
                <!-- Mi Panel -->
                <a href="dashboard_participante.php" class="btn btn-outline" style="font-size:var(--font-size-xs); padding: var(--space-2) var(--space-3); white-space:nowrap; text-decoration:none;">
                    <svg class="icon"><use href="#icon-grid"></use></svg>
                    Mi Panel
                </a>

                <!-- Notificaciones -->
                <?php include 'includes/notificaciones_participante.php'; ?>
                
                <div style="border-left: 1px solid var(--glass-border); padding-left: var(--space-4); margin-left: var(--space-2);">
                    <div style="font-weight: bold; font-size: var(--font-size-sm);"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></div>
                    <a href="logout.php" style="color: var(--color-salmon); font-size: var(--font-size-xs); text-decoration: none;">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════ Hero ═══════════ -->
    <section class="hero-join">
        <h1>Explora <span>Sanes Disponibles</span></h1>
        <p>Encuentra un grupo de ahorro que se adapte a ti. Compara planes, revisa los participantes y únete con un solo clic.</p>
        
        <!-- Stats bar -->
        <div style="display:flex;justify-content:center;gap:var(--space-8);flex-wrap:wrap;margin-top:var(--space-6);">
            <div style="text-align:center;">
                <div style="font-size:var(--font-size-3xl);font-weight:800;color:var(--color-primary);"><?php echo count($grupos_disponibles); ?></div>
                <div style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">Grupos disponibles</div>
            </div>
            <div style="width:1px;background:var(--glass-border);"></div>
            <div style="text-align:center;">
                <div style="font-size:var(--font-size-3xl);font-weight:800;color:var(--color-secondary);">
                    <?php
                        $total_spots = 0;
                        foreach ($grupos_disponibles as $g) $total_spots += $g['cupos_totales'] - $g['cupos_ocupados'];
                        echo $total_spots;
                    ?>
                </div>
                <div style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">Cupos libres totales</div>
            </div>
            <div style="width:1px;background:var(--glass-border);"></div>
            <div style="text-align:center;">
                <div style="font-size:var(--font-size-3xl);font-weight:800;color:var(--color-accent-dim);"><?php echo count($categorias); ?></div>
                <div style="font-size:var(--font-size-xs);color:var(--color-text-tertiary);">Categorías</div>
            </div>
        </div>
    </section>

    <!-- ═══════════ Toolbar ═══════════ -->
    <div class="toolbar" id="toolbar">
        <div class="search-wrap">
            <svg><use href="#icon-search"></use></svg>
            <input type="text" id="searchInput" placeholder="Buscar grupo..." oninput="filterCards()">
        </div>
        <select id="catFilter" onchange="filterCards()">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $catName => $catColor): ?>
            <option value="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="sortFilter" onchange="filterCards()">
            <option value="date">Más nuevos</option>
            <option value="spots">Más cupos</option>
            <option value="price">Menor cuota</option>
        </select>
        <span class="result-count" id="resultCount"><?php echo count($grupos_disponibles); ?> grupo<?php echo count($grupos_disponibles) !== 1 ? 's' : ''; ?></span>
    </div>

    <!-- ═══════════ Cards Grid ═══════════ -->
    <div class="cards-grid" id="cardsGrid">
        <?php if (empty($grupos_disponibles)): ?>
            <div class="empty-state">
                <svg><use href="#icon-users"></use></svg>
                <h3>No hay Sanes disponibles</h3>
                <p>Vuelve más tarde o consulta con el administrador.</p>
            </div>
        <?php else:
            foreach ($grupos_disponibles as $g):
                $cupos_libres = $g['cupos_totales'] - $g['cupos_ocupados'];
                $pct_ocupados = $g['cupos_totales'] > 0 ? round(($g['cupos_ocupados'] / $g['cupos_totales']) * 100) : 0;
                $participantes = $participantes_por_grupo[$g['id']] ?? [];
                $fecha_inicio = new DateTime($g['fecha_inicio']);
                $dias_entre = ($g['frecuencia'] === 'quincenal') ? 15 : 30;
                $total_pagar = $g['monto_cuota'] * $g['numero_cuotas'];
                $cat_color = $g['categoria_color'];
                $cat_icon = $cat_icon_map[$g['categoria_nombre']] ?? 'users';
                $proxima = clone $fecha_inicio;

                // Gradient for progress bar fill
                $gradient_colors = [
                    'violeta' => 'linear-gradient(90deg, var(--color-primary), var(--color-secondary))',
                    'menta'   => 'linear-gradient(90deg, var(--color-secondary), var(--color-primary))',
                    'salmon'  => 'linear-gradient(90deg, var(--color-accent-dim), var(--color-accent))',
                ];
                $bar_fill = $gradient_colors[$cat_color] ?? 'var(--color-primary)';
        ?>
        <div class="join-card" data-categoria="<?php echo htmlspecialchars($g['categoria_nombre']); ?>"
             data-nombre="<?php echo htmlspecialchars(strtolower($g['nombre'])); ?>"
             data-spots="<?php echo $cupos_libres; ?>"
             data-price="<?php echo $g['monto_cuota']; ?>"
             data-date="<?php echo $g['fecha_inicio']; ?>">
            <div class="color-strip" style="background:var(--color-<?php echo $cat_color; ?>);"></div>
            <?php if (!empty($g['producto_imagen'])): ?>
            <div class="card-img-strip" style="position:relative;cursor:zoom-in; padding:12px; background:var(--color-background); display:flex; align-items:center; justify-content:center; height:180px;"
                 onclick="event.stopPropagation(); viewGallery(<?php echo (int)$g['producto_id']; ?>, '<?php echo htmlspecialchars(addslashes($g['producto_nombre'])); ?>')">
                <img src="<?php echo htmlspecialchars(ltrim($g['producto_imagen'] ?? '', '/')); ?>" alt="<?php echo htmlspecialchars($g['producto_nombre']); ?>" loading="lazy" style="max-width:100%; max-height:100%; object-fit:contain;">
                <span style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;backdrop-filter:blur(4px);display:flex;align-items:center;gap:4px;">
                    <svg style="width:11px;height:11px;stroke:#fff;stroke-width:2.5;"><use href="#icon-image"></use></svg>Ver galería
                </span>
            </div>
            <?php endif; ?>

            <div class="card-body">

                <!-- Head -->
                <div class="card-head">
                    <div class="card-icon-wrap" style="border-color:var(--color-<?php echo $cat_color; ?>);">
                        <svg style="stroke:var(--color-<?php echo $cat_color; ?>);"><use href="#icon-<?php echo $cat_icon; ?>"></use></svg>
                    </div>
                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($g['nombre']); ?></h3>
                        <div class="card-sub">
                            <?php echo htmlspecialchars($g['producto_nombre']); ?>
                            <?php if ($g['marca']): ?> · <?php echo htmlspecialchars($g['marca']); ?><?php endif; ?>
                            · <span class="cat-badge" style="background:var(--color-<?php echo $cat_color; ?>-tint);color:var(--color-<?php echo $cat_color; ?>);">
                                <?php echo htmlspecialchars($g['categoria_nombre']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="card-progress">
                    <div class="cp-head">
                        <span class="cp-label">Cupos disponibles</span>
                        <span class="cp-val">
                            <span style="color:var(--color-<?php echo $cupos_libres > 2 ? 'secondary' : 'error'; ?>);">
                                <?php echo $cupos_libres; ?>
                            </span>
                            / <?php echo $g['cupos_totales']; ?>
                        </span>
                    </div>
                    <div class="cp-bar">
                        <div class="cp-fill" style="width:<?php echo $pct_ocupados; ?>%;background:<?php echo $bar_fill; ?>;"></div>
                    </div>
                </div>

                <!-- Details grid -->
                <div class="card-details">
                    <div class="det-section">
                        <h4>Plan de pago</h4>
                        <div class="det-grid">
                            <span class="dg-label">Cuota</span>
                            <span class="dg-val price">$<?php echo number_format($g['monto_cuota'], 2); ?></span>
                            <span class="dg-label">Frecuencia</span>
                            <span class="dg-val"><?php echo ucfirst($g['frecuencia']); ?></span>
                            <span class="dg-label">N° cuotas</span>
                            <span class="dg-val"><?php echo $g['numero_cuotas']; ?></span>
                            <span class="dg-label">Total</span>
                            <span class="dg-val total">$<?php echo number_format($total_pagar, 2); ?></span>
                        </div>
                    </div>
                    <div class="det-section">
                        <h4>Cronograma</h4>
                        <div class="det-grid">
                            <span class="dg-label">Inicio</span>
                            <span class="dg-val"><?php echo $fecha_inicio->format('d/m/Y'); ?></span>
                            <span class="dg-label">Próxima</span>
                            <span class="dg-val"><?php echo $proxima->format('d/m/Y'); ?></span>
                            <span class="dg-label">Tipo</span>
                            <span class="dg-val" style="text-transform:capitalize;"><?php echo $g['frecuencia']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                <div class="card-participants">
                    <div class="cp-header">
                        <h4>Participantes <span style="font-weight:400;color:var(--color-text-secondary);text-transform:none;letter-spacing:0;">(<?php echo $g['cupos_ocupados']; ?>)</span></h4>
                        <?php if ($cupos_libres > 0): ?>
                        <span class="cp-free" style="color:var(--color-secondary);">
                            <svg style="width:12px;height:12px;"><use href="#icon-plus"></use></svg>
                            <?php echo $cupos_libres; ?> cupo<?php echo $cupos_libres !== 1 ? 's' : ''; ?> libre<?php echo $cupos_libres !== 1 ? 's' : ''; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($participantes)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:var(--space-2);">
                        <?php
                        $max_v = 6;
                        $extra = count($participantes) - $max_v;
                        foreach (array_slice($participantes, 0, $max_v) as $p):
                            $initial = mb_strtoupper(mb_substr($p['nombre'], 0, 1)) . mb_strtoupper(mb_substr($p['apellido'], 0, 1));
                            $fullName = htmlspecialchars($p['nombre'] . ' ' . $p['apellido']);
                        ?>
                        <span class="pill" title="<?php echo $fullName; ?>">
                            <span class="pill-av" style="background:linear-gradient(135deg, var(--color-<?php echo $cat_color; ?>), color-mix(in srgb, var(--color-<?php echo $cat_color; ?>) 40%, #888));"><?php echo $initial; ?></span>
                            <?php echo $fullName; ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if ($extra > 0): ?>
                        <span class="pill-extra">+<?php echo $extra; ?> más</span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="pill-empty">Aún no hay participantes. ¡Sé el primero!</span>
                    <?php endif; ?>
                </div>

                <!-- CTA -->
                <div class="card-action">
                    <button class="btn btn-violeta" onclick="joinGroup(<?php echo $g['id']; ?>, '<?php echo htmlspecialchars($g['nombre'], ENT_QUOTES); ?>')">
                        Unirse a este San
                        <svg class="icon"><use href="#icon-user-plus"></use></svg>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- ═══════════ Floating Toast ═══════════ -->
    <div id="joinToast" class="join-toast"></div>

    <!-- ═══════════ JavaScript ═══════════ -->
    <script src="assets/js/shared.js"></script>
    <script>
        // ── Filter & Sort ──
        function filterCards() {
            const q        = document.getElementById('searchInput').value.toLowerCase().trim();
            const cat      = document.getElementById('catFilter').value;
            const sort     = document.getElementById('sortFilter').value;
            const cards    = Array.from(document.querySelectorAll('.join-card'));
            const grid     = document.getElementById('cardsGrid');
            let visible    = 0;

            cards.forEach(c => {
                const nome    = c.dataset.nombre;
                const nomeRaw = c.dataset.nombre; // already lowercased
                const catVal  = c.dataset.categoria;

                const matchSearch = !q || nomeRaw.includes(q);
                const matchCat    = !cat || catVal === cat;

                c.style.display = (matchSearch && matchCat) ? '' : 'none';
                if (matchSearch && matchCat) visible++;
            });

            // Sort visible cards
            const sorter = {
                date:  (a,b) => b.dataset.date.localeCompare(a.dataset.date),
                spots: (a,b) => b.dataset.spots - a.dataset.spots,
                price: (a,b) => a.dataset.price - b.dataset.price,
            };
            const fn = sorter[sort] || sorter.date;

            const visibleEls = cards.filter(c => c.style.display !== 'none').sort(fn);
            visibleEls.forEach(el => grid.appendChild(el));

            // Update count
            document.getElementById('resultCount').textContent = visible + ' grupo' + (visible !== 1 ? 's' : '');
        }

        // ── Join ──
        let toastTimer = null;

        function showToast(msg, type) {
            const t = document.getElementById('joinToast');
            t.textContent = msg;
            t.className = 'join-toast ' + type + ' show';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { t.classList.remove('show'); }, 3500);
        }

        async function joinGroup(grupoId, grupoNombre) {
            const fd = new FormData();
            fd.append('action', 'join_group');
            fd.append('grupo_id', grupoId);

            try {
                const res  = await fetch('api/participantes.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    showToast('Te has unido a "' + grupoNombre + '" correctamente.', 'success');
                    // Remove the card with animation
                    const card = document.querySelector(`.join-card[data-nombre="${grupoNombre.toLowerCase()}"]`);
                    if (card) {
                        card.style.transition = 'all 0.4s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            card.remove();
                            filterCards(); // update count
                        }, 420);
                    }
                    // Reload after 2.5s to reflect changes
                    setTimeout(() => location.reload(), 2500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch {
                showToast('Error de conexión. Intenta nuevamente.', 'error');
            }
        }

        // Fade in cards on load
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.join-card');
            cards.forEach((card, i) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 80 + i * 100);
            });
        });
    </script>

</body>
</html>
