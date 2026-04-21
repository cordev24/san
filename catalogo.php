<?php
require_once 'config/database.php';

$stmt = $pdo->query("
    SELECT p.*, c.nombre as categoria_nombre, c.color
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = TRUE
    ORDER BY p.created_at DESC
");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$tasa_bcv = getBcvRate();

// Category palette: warm, prosperous, trust-building
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos — MySan</title>
    <meta name="description" content="Explora el catálogo de productos financiables a través del sistema San. Sin intereses ocultos, pagos cómodos y sorteos transparentes.">

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">

    <style>
        /* ══════════════════════════════════
           DESIGN TOKENS — Warm Prosperity
        ══════════════════════════════════ */
        :root {
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

            /* Background: warm parchment, not cold black */
            --bg-base:    #FAF7F2;
            --bg-section: #F3EEE7;

            /* Brand: rich emerald (trust) + gold (prosperity) */
            --brand-emerald:     #065F46;
            --brand-emerald-mid: #059669;
            --brand-emerald-lt:  #D1FAE5;
            --brand-gold:        #B45309;
            --brand-gold-lt:     #FEF3C7;
            --brand-gold-glow:   #FCD34D;

            /* Text */
            --txt-heading:   #1C1917;
            --txt-body:      #44403C;
            --txt-muted:     #78716C;
            --txt-on-dark:   #FAFAF9;

            /* Surfaces */
            --card-bg:      #FFFFFF;
            --card-border:  rgba(0,0,0,.07);
            --card-shadow:  0 2px 8px rgba(0,0,0,.06), 0 12px 32px rgba(0,0,0,.06);
            --card-shadow-hover: 0 8px 24px rgba(0,0,0,.1), 0 24px 56px rgba(0,0,0,.1);

            /* Radii */
            --r-sm:  10px;
            --r-md:  16px;
            --r-lg:  24px;
            --r-xl:  32px;
            --r-pill:9999px;

            /* Transitions */
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

            /* Warm emerald-to-gold gradient */
            background: linear-gradient(160deg,
                #064E3B 0%,
                #065F46 35%,
                #0D9488 65%,
                #1D4ED8 100%);
        }

        /* Decorative circles */
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
            max-width: 520px;
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

        /* ══ CATALOG GRID ══ */
        .catalog-section {
            padding: 2rem clamp(1.25rem,5vw,4rem) 5rem;
            max-width: 1300px;
            margin: 0 auto;
        }

        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        /* ══ PRODUCT CARD ══ */
        .product-card {
            position: relative;
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--r-lg);
            padding: 1.75rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform var(--t), box-shadow var(--t);
            animation: card-in .5s cubic-bezier(.22,1,.36,1) backwards;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--card-shadow-hover);
        }

        @keyframes card-in {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* Colored top strip */
        .card-strip {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--c-accent, var(--brand-emerald));
            border-radius: var(--r-lg) var(--r-lg) 0 0;
        }

        /* Colored icon chip inside card */
        .card-icon-wrap {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: var(--c-bg, var(--brand-emerald-lt));
            margin-bottom: 1.25rem;
            flex-shrink: 0;
        }

        .card-icon-wrap svg {
            width: 24px; height: 24px;
            stroke: var(--c-accent, var(--brand-emerald));
            fill: none; stroke-width: 2;
        }

        .card-cat {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--c-accent, var(--brand-emerald));
            margin-bottom: .4rem;
        }

        .card-name {
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.3;
            color: var(--txt-heading);
            margin-bottom: .75rem;
        }

        .card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-bottom: .9rem;
        }

        .tag {
            padding: .2rem .7rem;
            border-radius: var(--r-pill);
            font-size: .7rem;
            font-weight: 600;
            background: var(--c-bg, var(--brand-emerald-lt));
            color: var(--c-text, var(--brand-emerald));
            border: 1px solid rgba(0,0,0,.06);
        }

        .card-desc {
            font-size: .875rem;
            color: var(--txt-muted);
            line-height: 1.6;
            flex-grow: 1;
            margin-bottom: 1.25rem;
        }

        /* Price box */
        .price-box {
            border-radius: var(--r-sm);
            padding: 1rem 1.2rem;
            margin-bottom: 1.2rem;
            background: var(--c-bg, var(--brand-emerald-lt));
            border: 1px solid rgba(0,0,0,.05);
        }

        .price-sublabel {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--c-text, var(--brand-emerald));
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .price-amount {
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
            color: var(--c-accent, var(--brand-emerald));
        }

        .price-currency {
            font-size: 1.1rem;
            font-weight: 700;
            vertical-align: super;
            margin-right: 2px;
        }

        .price-note {
            font-size: .72rem;
            color: var(--c-text, var(--brand-emerald));
            margin-top: .4rem;
            opacity: .7;
        }

        /* CTA button */
        .btn-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: .85rem 1.25rem;
            border-radius: var(--r-md);
            font-size: .9rem;
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
                Obtén lo que quieres,<br>
                <span class="highlight">pagando poco a poco</span>
            </h1>
            <p class="hero-sub">
                Unite a un Grupo San, paga cuotas cómodas junto a tu comunidad y participa en los sorteos de entrega. Sin intereses, sin bancos, sin complicaciones.
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-num">0%</div>
                    <div class="stat-label">Intereses</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-num"><?php echo count($productos); ?></div>
                    <div class="stat-label">Productos</div>
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
        <p class="filter-label">Filtrar por categoria</p>
        <div class="filter-chips" id="filterChips">
            <button class="chip active" data-filter="all">Todos los productos</button>
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
            <?php if (empty($productos)): ?>
                <div class="empty-state">
                    <svg style="width:48px;height:48px;stroke:currentColor;fill:none;stroke-width:1.2;opacity:.3;"><use href="#icon-package"></use></svg>
                    <h3 style="font-size:1.1rem;font-weight:700;color:#57534E;">Sin productos disponibles</h3>
                    <p style="font-size:.9rem;">Pronto agregaremos nuevos productos al catálogo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($productos as $i => $p):
                    $pal   = get_palette($p['color'] ?? '');
                    $icon  = get_icon($p['categoria_nombre'] ?? '');
                    $delay = ($i % 9) * 60;
                ?>
                    <div class="product-card"
                         data-category="<?php echo $p['categoria_id']; ?>"
                         style="
                            --c-bg:     <?php echo $pal['bg']; ?>;
                            --c-accent: <?php echo $pal['accent']; ?>;
                            --c-text:   <?php echo $pal['text']; ?>;
                            animation-delay: <?php echo $delay; ?>ms;
                         ">

                        <div class="card-strip"></div>

                        <div class="card-icon-wrap">
                            <svg><use href="#icon-<?php echo htmlspecialchars($icon); ?>"></use></svg>
                        </div>

                        <div class="card-cat"><?php echo htmlspecialchars($p['categoria_nombre']); ?></div>
                        <h3 class="card-name"><?php echo htmlspecialchars($p['nombre']); ?></h3>

                        <?php if ($p['marca'] || $p['modelo']): ?>
                            <div class="card-tags">
                                <?php if ($p['marca']): ?>
                                    <span class="tag"><?php echo htmlspecialchars($p['marca']); ?></span>
                                <?php endif; ?>
                                <?php if ($p['modelo']): ?>
                                    <span class="tag"><?php echo htmlspecialchars($p['modelo']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <p class="card-desc">
                            <?php echo !empty($p['descripcion']) ? htmlspecialchars($p['descripcion']) : 'Producto financiable a través del sistema San comunitario.'; ?>
                        </p>

                        <div class="price-box">
                            <div class="price-sublabel">Valor total del paquete</div>
                            <div class="price-amount">
                                <span class="price-currency">$</span><?php echo number_format($p['valor_total'], 2); ?>
                            </div>
                            <?php if ($tasa_bcv && $tasa_bcv > 0): ?>
                                <div class="price-note">
                                    Aprox. Bs. <?php echo number_format($p['valor_total'] * $tasa_bcv, 2, ',', '.'); ?> (Tasa BCV)
                                </div>
                            <?php endif; ?>
                        </div>

                        <button class="btn-cta"
                                onclick="openModal('<?php echo htmlspecialchars(addslashes($p['nombre'])); ?>')">
                            <span>Me interesa este producto</span>
                            <svg><use href="#icon-arrow-right"></use></svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> MySan — Sistema de Ahorros Grupales</p>
        <p><a href="login.php">Acceso de administradores</a></p>
    </footer>

    <!-- MODAL WHATSAPP -->
    <div class="modal-overlay" id="contactModal">
        <div class="modal-panel">
            <button class="modal-close" onclick="closeModal()">&times;</button>

            <div class="modal-wa-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
            </div>

            <h2 class="modal-title">Consulta sin compromiso</h2>
            <p class="modal-body">
                Para unirte a un Grupo San y financiar tu
                <span class="modal-product-name" id="modalProductName">...</span>,
                escríbenos por WhatsApp. Te contamos sobre cuotas, sorteos y cupos disponibles.
            </p>

            <a href="#" id="whatsappBtn" target="_blank" rel="noopener" class="btn-wa">
                <svg viewBox="0 0 24 24">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
                Escribir por WhatsApp
            </a>
        </div>
    </div>

    <script>
        /* ── Filter ── */
        const chips = document.querySelectorAll('.chip[data-filter]');
        const cards = document.querySelectorAll('.product-card');

        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                const f = chip.dataset.filter;
                let visible = 0;

                cards.forEach(card => {
                    const match = f === 'all' || card.dataset.category === f;
                    card.style.display = match ? 'flex' : 'none';
                    if (match) {
                        card.style.animation = 'none';
                        void card.offsetWidth;
                        card.style.animationDelay = (visible % 9 * 60) + 'ms';
                        card.style.animation = 'card-in .45s cubic-bezier(.22,1,.36,1) backwards';
                        visible++;
                    }
                });
            });
        });

        /* ── Modal ── */
        const modal     = document.getElementById('contactModal');
        const nameEl    = document.getElementById('modalProductName');
        const waBtn     = document.getElementById('whatsappBtn');
        const phone     = '584243074602'; // Reemplaza con tu número

        function openModal(name) {
            nameEl.textContent = name;
            const msg = encodeURIComponent(`Hola, me interesa el producto "${name}" a través del sistema San. ¿Tienen cupos disponibles?`);
            waBtn.href = `https://wa.me/${phone}?text=${msg}`;
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }

        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    </script>
</body>
</html>
