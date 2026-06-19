<?php
require_once 'config/database.php';

// ── Query: Grupos San abiertos con cupos disponibles ──
$stmt = $pdo->query("
    SELECT
        gs.*,
        p.nombre       as producto_nombre,
        p.marca        as producto_marca,
        p.modelo       as producto_modelo,
        p.valor_total  as producto_valor,
        p.imagen       as producto_imagen,
        c.id           as categoria_id,
        c.nombre       as categoria_nombre,
        c.color        as categoria_color
    FROM grupos_san gs
    JOIN productos p    ON gs.producto_id = p.id
    JOIN categorias c   ON p.categoria_id = c.id
    WHERE gs.estado = 'abierto'
      AND gs.cupos_ocupados < gs.cupos_totales
    ORDER BY gs.created_at DESC
");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Categorías para el filtro ──
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$tasa_bcv = getBcvRate();

// ── Paletas e iconos por categoría ──
$color_map = [
    'violeta' => ['bg' => '#F0EBFF', 'accent' => '#6D28D9', 'text' => '#4C1D95'],
    'menta'   => ['bg' => '#ECFDF5', 'accent' => '#059669', 'text' => '#065F46'],
    'salmon'  => ['bg' => '#FFF7ED', 'accent' => '#EA580C', 'text' => '#9A3412'],
    'electro' => ['bg' => '#EFF6FF', 'accent' => '#2563EB', 'text' => '#1E3A8A'],
];
$default_palette = ['bg' => '#FEF9C3', 'accent' => '#CA8A04', 'text' => '#713F12'];

function get_palette(string $name): array {
    global $color_map, $default_palette;
    return $color_map[$name] ?? $default_palette;
}

$icon_map = [
    'electrodomesticos' => 'zap',
    'electrodomésticos' => 'zap',
    'telefonia'         => 'smartphone',
    'telefonía'         => 'smartphone',
    'motocicletas'      => 'navigation',
];
function get_icon(string $cat): string {
    global $icon_map;
    return $icon_map[mb_strtolower(trim($cat))] ?? 'package';
}
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
    <title>Grupos San Disponibles — MySan</title>
    <meta name="description" content="Explora los grupos San activos con cupos disponibles. Unite, pagá cuotas cómodas y recibí tu producto mediante sorteos transparentes.">

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">

    <style>
        /* ══════════════════════════════════
           DESIGN TOKENS — Warm Prosperity
        ══════════════════════════════════ */
        :root {
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

            --bg-base:    #FAF7F2;
            --bg-section: #F3EEE7;

            --brand-emerald:     #065F46;
            --brand-emerald-mid: #059669;
            --brand-emerald-lt:  #D1FAE5;
            --brand-gold:        #B45309;
            --brand-gold-lt:     #FEF3C7;
            --brand-gold-glow:   #FCD34D;

            --txt-heading:   #1C1917;
            --txt-body:      #44403C;
            --txt-muted:     #78716C;
            --txt-on-dark:   #FAFAF9;

            --card-bg:      #FFFFFF;
            --card-border:  rgba(0,0,0,.07);
            --card-shadow:  0 2px 8px rgba(0,0,0,.06), 0 12px 32px rgba(0,0,0,.06);
            --card-shadow-hover: 0 8px 24px rgba(0,0,0,.1), 0 24px 56px rgba(0,0,0,.1);

            --r-sm:  10px;
            --r-md:  16px;
            --r-lg:  24px;
            --r-xl:  32px;
            --r-pill:9999px;

            --t: 260ms cubic-bezier(.22,1,.36,1);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font);
            background-color: var(--bg-base);
            color: var(--txt-body);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ══ NAV ══ */
        .nav {
            position: sticky; top: 0; z-index: 200;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 clamp(1.25rem, 5vw, 4rem);
            height: 68px;
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 1px 20px rgba(0,0,0,.05);
        }

        .nav-logo {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            text-decoration: none;
            color: var(--brand-emerald);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-logo-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--brand-gold-glow);
            display: inline-block;
        }

        .nav-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: .5rem 1.25rem;
            border-radius: var(--r-pill);
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid var(--brand-emerald);
            color: var(--brand-emerald);
            transition: all var(--t);
        }

        .nav-pill:hover {
            background: var(--brand-emerald);
            color: #fff;
        }

        .nav-pill svg {
            width: 14px; height: 14px;
            stroke: currentColor; fill: none; stroke-width: 2.5;
        }

        /* ══ HERO ══ */
        .hero {
            position: relative;
            overflow: hidden;
            padding: clamp(5rem,10vw,8rem) clamp(1.25rem,5vw,4rem) clamp(3rem,6vw,5rem);
            text-align: center;

            background: linear-gradient(160deg,
                #064E3B 0%,
                #065F46 35%,
                #0D9488 65%,
                #1D4ED8 100%);
        }

        .hero::before, .hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .hero::before {
            width: 500px; height: 500px;
            top: -180px; right: -120px;
            background: radial-gradient(circle, rgba(252,211,77,.18), transparent 70%);
        }
        .hero::after {
            width: 350px; height: 350px;
            bottom: -100px; left: -80px;
            background: radial-gradient(circle, rgba(110,231,183,.15), transparent 70%);
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 680px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: .4rem 1.1rem;
            border-radius: var(--r-pill);
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.25);
            color: rgba(255,255,255,.9);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-bottom: 1.75rem;
        }

        .hero-badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #FCD34D;
            animation: blink 2.4s ease-in-out infinite;
        }

        @keyframes blink {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.4; transform:scale(1.5); }
        }

        .hero-title {
            font-size: clamp(2.2rem, 5.5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.06;
            letter-spacing: -0.04em;
            color: #fff;
            margin-bottom: 1.25rem;
        }

        .hero-title .highlight {
            color: #FCD34D;
        }

        .hero-sub {
            font-size: clamp(.95rem, 2vw, 1.15rem);
            line-height: 1.7;
            color: rgba(255,255,255,.78);
            max-width: 540px;
            margin: 0 auto 2.25rem;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: clamp(1.5rem, 4vw, 3rem);
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-num {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #FCD34D;
            line-height: 1;
        }

        .stat-label {
            font-size: .78rem;
            color: rgba(255,255,255,.65);
            margin-top: .25rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 600;
        }

        .stat-divider {
            width: 1px;
            background: rgba(255,255,255,.2);
            align-self: stretch;
        }

        /* ══ FILTER ROW ══ */
        .filter-section {
            padding: 2.5rem clamp(1.25rem,5vw,4rem) 0;
            max-width: 1300px;
            margin: 0 auto;
        }

        .filter-label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--txt-muted);
            margin-bottom: .85rem;
        }

        .filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .625rem;
        }

        .chip {
            padding: .5rem 1.25rem;
            border-radius: var(--r-pill);
            font-size: .875rem;
            font-weight: 600;
            border: 1.5px solid rgba(0,0,0,.12);
            background: #fff;
            color: var(--txt-body);
            cursor: pointer;
            transition: all var(--t);
            outline: none;
            user-select: none;
        }

        .chip:hover {
            border-color: var(--brand-emerald-mid);
            color: var(--brand-emerald);
            background: var(--brand-emerald-lt);
        }

        .chip.active {
            background: var(--brand-emerald);
            border-color: var(--brand-emerald);
            color: #fff;
            box-shadow: 0 4px 16px rgba(6,95,70,.3);
        }

        /* ══ SEARCH BAR ══ */
        .search-bar {
            position: relative;
            margin-bottom: 1.35rem;
        }

        .search-bar svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            stroke: var(--txt-muted);
            fill: none;
            stroke-width: 2;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: .85rem 1rem .85rem 2.75rem;
            border-radius: var(--r-pill);
            border: 1.5px solid rgba(0,0,0,.1);
            background: #fff;
            font-size: .95rem;
            font-family: var(--font);
            color: var(--txt-heading);
            transition: all var(--t);
            outline: none;
        }

        .search-input::placeholder {
            color: var(--txt-muted);
            opacity: .7;
        }

        .search-input:focus {
            border-color: var(--brand-emerald-mid);
            box-shadow: 0 0 0 4px rgba(5,150,105,.12);
        }

        .search-clear {
            position: absolute;
            right: .75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: #F5F5F4;
            color: var(--txt-muted);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all var(--t);
            font-size: 1rem;
            line-height: 1;
        }

        .search-clear.visible {
            display: flex;
        }

        .search-clear:hover {
            background: #E7E5E4;
            color: var(--txt-heading);
        }

        /* ══ CATALOG GRID ══ */
        .catalog-section {
            padding: 2rem clamp(1.25rem,5vw,4rem) 5rem;
            max-width: 1300px;
            margin: 0 auto;
        }

        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 1.5rem;
        }

        /* ══ GROUP CARD ══ */
        .group-card {
            position: relative;
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--r-md);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform var(--t), box-shadow var(--t);
            animation: card-in .5s cubic-bezier(.22,1,.36,1) backwards;
        }

        .group-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }

        @keyframes card-in {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* Colored top strip */
        .card-strip {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--c-accent, var(--brand-emerald));
            border-radius: var(--r-md) var(--r-md) 0 0;
        }

        /* Card body: horizontal layout */
        .group-card .card-content {
            display: flex;
            gap: .85rem;
            padding: .85rem;
            flex: 1;
        }

        /* Left: square product image */
        .card-product-img {
            width: 80px;
            min-width: 80px;
            height: 80px;
            border-radius: var(--r-sm);
            overflow: hidden;
            background: var(--c-bg, #F3F4F6);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--card-border);
            flex-shrink: 0;
            align-self: flex-start;
            margin-top: .15rem;
        }

        .card-product-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
            transition: transform var(--t);
        }

        .group-card:hover .card-product-img img {
            transform: scale(1.06);
        }

        .card-product-img-placeholder svg {
            width: 32px; height: 32px;
            stroke: var(--c-accent, var(--brand-emerald));
            fill: none; stroke-width: 1.5;
            opacity: .3;
        }

        /* Right: info column */
        .card-info-col {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .card-cat {
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--c-accent, var(--brand-emerald));
            margin-bottom: .1rem;
        }

        .card-name {
            font-size: .88rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            line-height: 1.25;
            color: var(--txt-heading);
            margin-bottom: .1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-product {
            font-size: .7rem;
            color: var(--txt-muted);
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .card-product svg {
            width: 11px; height: 11px;
            stroke: var(--txt-muted);
            fill: none; stroke-width: 2;
            flex-shrink: 0;
        }

        /* Inline row: badge + price */
        .card-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            margin-bottom: .45rem;
        }

        /* Spots badge */
        .spots-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: .15rem .55rem;
            border-radius: var(--r-pill);
            font-size: .62rem;
            font-weight: 700;
        }

        .spots-badge.available {
            background: var(--brand-emerald-lt);
            color: var(--brand-emerald);
        }

        .spots-badge.low {
            background: #FEF3C7;
            color: #B45309;
        }

        .spots-badge svg {
            width: 11px; height: 11px;
            stroke: currentColor; fill: none; stroke-width: 2.5;
        }

        /* Inline price */
        .price-inline {
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--c-accent, var(--brand-emerald));
            line-height: 1;
        }

        .price-inline .price-curr {
            font-size: .65rem;
            font-weight: 700;
            vertical-align: super;
        }

        .price-inline .price-freq-label {
            font-size: .62rem;
            font-weight: 600;
            color: var(--txt-muted);
            margin-left: 2px;
        }

        .price-note {
            font-size: .62rem;
            color: var(--txt-muted);
            margin-bottom: .4rem;
            opacity: .75;
        }

        /* Detail chips */
        .card-details {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
        }

        .detail-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: .62rem;
            font-weight: 600;
            color: var(--txt-muted);
            padding: .15rem .5rem;
            border-radius: var(--r-pill);
            background: #F5F5F4;
            border: 1px solid rgba(0,0,0,.05);
        }

        .detail-chip svg {
            width: 10px; height: 10px;
            stroke: currentColor; fill: none; stroke-width: 2.5;
        }

        /* CTA button — bottom, full width */
        .card-footer {
            padding: 0 .85rem .85rem;
        }

        .btn-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: .55rem 1rem;
            border-radius: var(--r-sm);
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .01em;
            cursor: pointer;
            text-decoration: none;
            border: none;
            background: var(--c-accent, var(--brand-emerald));
            color: #fff;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
        }

        .btn-cta::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0);
            transition: background var(--t);
        }

        .btn-cta:hover::after { background: rgba(0,0,0,.12); }

        .btn-cta svg {
            width: 16px; height: 16px;
            stroke: currentColor; fill: none; stroke-width: 2.5;
            position: relative; z-index: 1;
        }

        .btn-cta span { position: relative; z-index: 1; }

        /* ══ EMPTY STATE ══ */
        .empty-state {
            grid-column: 1/-1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 5rem 2rem;
            background: #fff;
            border-radius: var(--r-lg);
            border: 1px dashed rgba(0,0,0,.12);
            color: var(--txt-muted);
            gap: 1rem;
            text-align: center;
        }

        /* ══ MODAL ══ */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(28,25,23,.6);
            backdrop-filter: blur(8px);
            z-index: 500;
            display: none;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 0;
        }

        @media (min-width: 640px) {
            .modal-overlay { align-items: center; }
        }

        .modal-overlay.open { display: flex; animation: fade-in .2s ease; }

        @keyframes fade-in { from { opacity:0; } to { opacity:1; } }

        .modal-panel {
            position: relative;
            background: #fff;
            border-radius: var(--r-xl) var(--r-xl) 0 0;
            padding: 2.5rem 2rem 2.5rem;
            width: 100%;
            max-width: 480px;
            text-align: center;
            animation: slide-up .3s cubic-bezier(.22,1,.36,1);
        }

        @media (min-width: 640px) {
            .modal-panel { border-radius: var(--r-xl); }
        }

        @keyframes slide-up {
            from { opacity:0; transform:translateY(32px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .modal-close {
            position: absolute; top: 1rem; right: 1rem;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #F5F5F4;
            border: none;
            cursor: pointer;
            font-size: 1.3rem; line-height: 1;
            color: var(--txt-muted);
            display: flex; align-items: center; justify-content: center;
            transition: all var(--t);
        }

        .modal-close:hover { background: #E7E5E4; color: var(--txt-heading); }

        .modal-wa-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: #DCFCE7;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            border: 1px solid #BBF7D0;
        }

        .modal-wa-icon svg {
            width: 36px; height: 36px;
            stroke: #16A34A; fill: none;
            stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round;
        }

        .modal-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--txt-heading);
            margin-bottom: .75rem;
        }

        .modal-body {
            font-size: .9rem;
            color: var(--txt-body);
            line-height: 1.7;
            margin-bottom: 1.75rem;
        }

        .modal-product-name { font-weight: 700; color: var(--brand-emerald); }

        .btn-wa {
            display: flex;
            align-items: center; justify-content: center;
            gap: 10px;
            width: 100%;
            padding: .9rem 1.5rem;
            border-radius: var(--r-md);
            background: #16A34A;
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            text-decoration: none;
            transition: background var(--t), transform var(--t);
        }

        .btn-wa:hover { background: #15803D; transform: scale(1.02); }

        .btn-wa svg {
            width: 20px; height: 20px;
            stroke: currentColor; fill: none; stroke-width: 2;
        }

        /* ══ FOOTER ══ */
        .footer {
            border-top: 1px solid rgba(0,0,0,.07);
            background: #fff;
            padding: 2rem clamp(1.25rem,5vw,4rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer p { font-size: .8rem; color: var(--txt-muted); }

        .footer a { color: var(--brand-emerald); text-decoration: none; font-weight: 600; }
        .footer a:hover { text-decoration: underline; }

        /* ══ NO RESULTS ══ */
        .no-results {
            grid-column: 1/-1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 4rem 2rem;
            background: #fff;
            border-radius: var(--r-lg);
            border: 1px dashed rgba(0,0,0,.1);
            color: var(--txt-muted);
            gap: .75rem;
            text-align: center;
            animation: fade-in .3s ease;
        }

        @media (max-width: 640px) {
            .footer { justify-content: center; text-align: center; }
            .stat-divider { display: none; }
        }
    </style>
</head>
<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <!-- NAV -->
    <nav class="nav">
        <a href="catalogo.php" class="nav-logo">
            <span class="nav-logo-dot"></span>
            MySan
        </a>
        <a href="login.php" class="nav-pill">
            <svg><use href="#icon-log-in"></use></svg>
            Ingresar
        </a>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Sistema de ahorros San — 100% comunitario
            </div>
            <h1 class="hero-title">
                Grupos San con cupos<br>
                <span class="highlight">disponibles ahora</span>
            </h1>
            <p class="hero-sub">
                Elegí un grupo abierto, unite, pagá cuotas accesibles junto a tu comunidad y participá en los sorteos de entrega. Sin intereses, sin bancos, sin complicaciones.
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-num">0%</div>
                    <div class="stat-label">Intereses</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-num"><?php echo count($grupos); ?></div>
                    <div class="stat-label">Grupos activos</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-num">100%</div>
                    <div class="stat-label">Transparente</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FILTER -->
    <div class="filter-section">
        <div class="search-bar">
            <svg><use href="#icon-search"></use></svg>
            <input type="text" class="search-input" id="searchInput"
                   placeholder="Buscá por grupo, producto o categoría..."
                   autocomplete="off" spellcheck="false">
            <button class="search-clear" id="searchClear" onclick="clearSearch()" aria-label="Limpiar búsqueda">&times;</button>
        </div>

        <p class="filter-label">Filtrar por categoría</p>
        <div class="filter-chips" id="filterChips">
            <button class="chip active" data-filter="all">Todos los grupos</button>
            <?php foreach ($categorias as $cat): ?>
                <button class="chip" data-filter="<?php echo $cat['id']; ?>">
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CATALOG -->
    <section class="catalog-section">
        <div class="catalog-grid" id="catalogGrid">
            <?php if (empty($grupos)): ?>
                <div class="empty-state">
                    <svg style="width:48px;height:48px;stroke:currentColor;fill:none;stroke-width:1.2;opacity:.3;"><use href="#icon-users"></use></svg>
                    <h3 style="font-size:1.1rem;font-weight:700;color:#57534E;">Sin grupos disponibles</h3>
                    <p style="font-size:.9rem;">Todos los grupos están completos por ahora. Volvé pronto, abrimos nuevos grupos regularmente.</p>
                </div>
            <?php else: ?>
                <?php
                // Calculate total available spots across all groups
                $total_cupos = 0;
                foreach ($grupos as $g) {
                    $total_cupos += $g['cupos_totales'] - $g['cupos_ocupados'];
                }
                ?>
                <?php foreach ($grupos as $i => $g):
                    $pal        = get_palette($g['categoria_color'] ?? '');
                    $icon       = get_icon($g['categoria_nombre'] ?? '');
                    $delay      = ($i % 9) * 60;
                    $disponibles = $g['cupos_totales'] - $g['cupos_ocupados'];
                    $spots_class = $disponibles <= 2 ? 'low' : 'available';
                ?>
                    <div class="group-card"
                         data-category="<?php echo $g['categoria_id']; ?>"
                         data-search="<?php
                            echo htmlspecialchars(mb_strtolower($g['nombre'] . ' ' . $g['producto_nombre'] . ' ' . ($g['producto_marca'] ?? '') . ' ' . ($g['producto_modelo'] ?? '') . ' ' . $g['categoria_nombre']), ENT_QUOTES, 'UTF-8');
                         ?>"
                         style="
                            --c-bg:     <?php echo $pal['bg']; ?>;
                            --c-accent: <?php echo $pal['accent']; ?>;
                            --c-text:   <?php echo $pal['text']; ?>;
                            animation-delay: <?php echo $delay; ?>ms;
                         ">

                        <div class="card-strip"></div>

                        <div class="card-content">

                            <!-- Left: product image -->
                            <div class="card-product-img">
                                <?php if (!empty($g['producto_imagen'])): ?>
                                    <img src="<?php echo htmlspecialchars(ltrim($g['producto_imagen'], '/')); ?>"
                                         alt="<?php echo htmlspecialchars($g['producto_nombre']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="card-product-img-placeholder">
                                        <svg><use href="#icon-package"></use></svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right: info column -->
                            <div class="card-info-col">
                                <div class="card-cat"><?php echo htmlspecialchars($g['categoria_nombre']); ?></div>
                                <h3 class="card-name"><?php echo htmlspecialchars($g['nombre']); ?></h3>

                                <div class="card-product">
                                    <svg><use href="#icon-package"></use></svg>
                                    <?php echo htmlspecialchars($g['producto_nombre']); ?>
                                    <?php if ($g['producto_marca']): ?> — <?php echo htmlspecialchars($g['producto_marca']); ?><?php endif; ?>
                                </div>

                                <!-- Badge + price inline -->
                                <div class="card-row">
                                    <div class="spots-badge <?php echo $spots_class; ?>">
                                        <svg><use href="#icon-user-plus"></use></svg>
                                        <?php echo $disponibles; ?> cupo<?php echo $disponibles !== 1 ? 's' : ''; ?>
                                    </div>
                                    <span class="price-inline">
                                        <span class="price-curr">$</span><?php echo number_format($g['monto_cuota'], 2); ?>
                                        <span class="price-freq-label"><?php echo $g['frecuencia']; ?></span>
                                    </span>
                                </div>

                                <?php if ($tasa_bcv && $tasa_bcv > 0): ?>
                                <div class="price-note">Aprox. Bs. <?php echo number_format($g['monto_cuota'] * $tasa_bcv, 2, ',', '.'); ?> (BCV)</div>
                                <?php endif; ?>

                                <div class="card-details">
                                    <span class="detail-chip">
                                        <svg><use href="#icon-calendar"></use></svg>
                                        <?php echo $g['numero_cuotas']; ?> cuotas
                                    </span>
                                    <span class="detail-chip">
                                        <svg><use href="#icon-users"></use></svg>
                                        <?php echo $g['cupos_totales']; ?> cupos
                                    </span>
                                </div>
                            </div>

                        </div><!-- /.card-content -->

                        <div class="card-footer">
                            <a href="registro.php?grupo_id=<?php echo $g['id']; ?>" class="btn-cta">
                                <span>Quiero unirme</span>
                                <svg><use href="#icon-arrow-right"></use></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Dynamic no-results (shown/hidden via JS) -->
            <div class="no-results" id="noResults" style="display:none;">
                <svg style="width:48px;height:48px;stroke:currentColor;fill:none;stroke-width:1.2;opacity:.3;"><use href="#icon-search"></use></svg>
                <h3 style="font-size:1.1rem;font-weight:700;color:#57534E;">Sin resultados</h3>
                <p style="font-size:.9rem;" id="noResultsMsg">
                    No hay grupos abiertos que coincidan con tu búsqueda. Probá con otro término o revisá los filtros de categoría.
                </p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> MySan — Sistema de Ahorros Grupales</p>
        <?php if (isLoggedIn()): ?>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <p><a href="dashboard.php">Panel de Administración</a></p>
            <?php else: ?>
                <p><a href="dashboard_participante.php">Mi Panel de Ahorros</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </footer>

    <script>
        /* ── Combined Filter: search + category ── */
        const chips       = document.querySelectorAll('.chip[data-filter]');
        const cards       = document.querySelectorAll('.group-card');
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.getElementById('searchClear');

        function applyFilters() {
            const q    = searchInput.value.trim().toLowerCase();
            const chip = document.querySelector('.chip.active');
            const f    = chip ? chip.dataset.filter : 'all';
            let visible = 0;

            // Show/hide clear button
            searchClear.classList.toggle('visible', q.length > 0);

            cards.forEach(card => {
                const catMatch   = f === 'all' || card.dataset.category === f;
                const searchable = card.dataset.search || '';
                const textMatch  = q === '' || searchable.indexOf(q) !== -1;
                const show       = catMatch && textMatch;

                card.style.display = show ? 'flex' : 'none';
                if (show) {
                    card.style.animation = 'none';
                    void card.offsetWidth;
                    card.style.animationDelay = (visible % 9 * 60) + 'ms';
                    card.style.animation = 'card-in .45s cubic-bezier(.22,1,.36,1) backwards';
                    visible++;
                }
            });

            // Toggle no-results message
            const noResults = document.getElementById('noResults');
            const allCards  = cards.length;
            if (noResults) {
                if (allCards > 0 && visible === 0) {
                    noResults.style.display = 'flex';
                } else {
                    noResults.style.display = 'none';
                }
            }
        }

        // Category chips
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                applyFilters();
            });
        });

        // Search input with debounce
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyFilters, 200);
        });

        // Clear search
        function clearSearch() {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        }

        /* ── (modal de WhatsApp eliminado — ahora redirige a registro.php) ── */
    </script>
</body>
</html>
