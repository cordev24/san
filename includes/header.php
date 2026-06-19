<?php
/**
 * App Navigation Component
 */
$headerLogoHref   = $headerLogoHref ?? 'dashboard.php';
$headerLogoutHref = $headerLogoutHref ?? 'logout.php';
$headerBackUrl    = $headerBackUrl ?? null;
$headerBackLabel  = $headerBackLabel ?? 'Volver';

$is_dashboard = strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false;
$is_catalog   = strpos($_SERVER['PHP_SELF'], 'categorias_admin') !== false || strpos($_SERVER['PHP_SELF'], 'productos') !== false;
$is_groups    = strpos($_SERVER['PHP_SELF'], 'grupos') !== false;
$is_payments  = strpos($_SERVER['PHP_SELF'], 'pagos') !== false;
?>

<!-- Desktop & Mobile App Navigation (App-like layout) -->
<nav class="app-navigation">
    <div class="nav-brand">
        <a href="<?php echo $headerLogoHref; ?>" class="header-logo">MySan</a>
    </div>

    <div class="nav-menu">
        <a href="<?php echo $is_dashboard ? '#' : $headerLogoHref; ?>" class="nav-item <?php echo $is_dashboard ? 'active' : ''; ?>">
            <svg class="icon"><use href="#icon-home"></use></svg>
            <span>Inicio</span>
        </a>
        <a href="<?php echo str_replace('dashboard.php', 'modules/categorias_admin/index.php', $headerLogoHref); ?>" class="nav-item <?php echo $is_catalog ? 'active' : ''; ?>">
            <svg class="icon"><use href="#icon-grid"></use></svg>
            <span>Catálogo</span>
        </a>
        <a href="<?php echo str_replace('dashboard.php', 'modules/grupos/index.php', $headerLogoHref); ?>" class="nav-item <?php echo $is_groups ? 'active' : ''; ?>">
            <svg class="icon"><use href="#icon-users"></use></svg>
            <span>Grupos</span>
        </a>
        <a href="<?php echo str_replace('dashboard.php', 'modules/pagos/index.php', $headerLogoHref); ?>" class="nav-item <?php echo $is_payments ? 'active' : ''; ?>">
            <svg class="icon"><use href="#icon-credit-card"></use></svg>
            <span>Pagos</span>
        </a>
    </div>

    <div class="nav-actions">
        <?php include __DIR__ . '/notificaciones.php'; ?>
        <a href="<?php echo (strpos($headerLogoHref, '../../') !== false) ? '../../perfil.php' : 'perfil.php'; ?>" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'perfil.php') !== false ? 'active' : ''; ?>">
            <svg class="icon"><use href="#icon-user"></use></svg>
            <span>Perfil</span>
        </a>
        <a href="<?php echo $headerLogoutHref; ?>" class="nav-item">
            <svg class="icon"><use href="#icon-log-out"></use></svg>
            <span>Salir</span>
        </a>
    </div>
</nav>

<!-- Top App Bar for mobile / desktop -->
<header class="app-topbar">
    <?php if ($headerBackUrl): ?>
        <a href="<?php echo $headerBackUrl; ?>" class="btn-icon" onclick="if(document.referrer) { window.history.back(); return false; }">
            <svg class="icon"><use href="#icon-arrow-left"></use></svg>
        </a>
    <?php else: ?>
        <div class="user-greeting">
            Hola, <a href="<?php echo (strpos($headerLogoHref, '../../') !== false) ? '../../perfil.php' : 'perfil.php'; ?>" style="color: inherit; text-decoration: underline;"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></a>
        </div>
    <?php endif; ?>
    
    <div class="topbar-actions">
        <!-- Optional top bar actions like search or notifications on mobile -->
        <a href="<?php echo $headerLogoutHref; ?>" class="btn-icon mobile-only-logout">
            <svg class="icon"><use href="#icon-log-out"></use></svg>
        </a>
    </div>
</header>
