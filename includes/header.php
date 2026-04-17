<?php
/**
 * Header Component - Incluir en todas las páginas admin
 * 
 * Variables opcionales que se pueden definir antes de incluir:
 *   $showCrearUsuario (bool) - Mostrar botón "Crear Usuario". Default: false
 *   $headerBackUrl   (string) - URL del botón "Volver". Si se define, muestra el botón.
 *   $headerBackLabel (string) - Texto del botón "Volver". Default: "Volver"
 *
 * Requiere: $user (del getCurrentUser() ya ejecutado antes de incluir)
 * Requiere: feather-sprite.svg ya incluido antes o después (normalmente al inicio del body)
 *
 * Uso desde dashboard (raíz):
 *   $headerLogoHref = 'dashboard.php';
 *   $headerLogoutHref = 'logout.php';
 *   $headerCrearUsuarioHref = 'crear-usuario.php';
 *
 * Uso desde módulos (modules/xxx/):
 *   $headerLogoHref = '../../dashboard.php';
 *   $headerLogoutHref = '../../logout.php';
 *   $headerCrearUsuarioHref = '../../crear-usuario.php';
 */

$headerLogoHref         = $headerLogoHref ?? 'dashboard.php';
$headerLogoutHref       = $headerLogoutHref ?? 'logout.php';
$headerCrearUsuarioHref = $headerCrearUsuarioHref ?? 'crear-usuario.php';
$showCrearUsuario       = $showCrearUsuario ?? false;
$headerBackUrl          = $headerBackUrl ?? null;
$headerBackLabel        = $headerBackLabel ?? 'Volver';
?>
<header class="header">
    <div class="header-content">
        <a href="<?php echo $headerLogoHref; ?>" class="header-logo">MySan</a>

        <div class="header-user">
            <div class="user-info">
                <div class="user-name">
                    <?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?>
                </div>
                <div class="user-role">Administrador</div>
            </div>

            <?php if ($showCrearUsuario): ?>
                <a href="<?php echo $headerCrearUsuarioHref; ?>" class="btn btn-violeta">
                    <svg class="icon"><use href="#icon-user"></use></svg>
                    <span>Crear Usuario</span>
                </a>
            <?php endif; ?>

            <a href="<?php echo $headerLogoutHref; ?>" class="btn btn-outline">
                <svg class="icon"><use href="#icon-log-out"></use></svg>
                <span>Salir</span>
            </a>
        </div>
    </div>
</header>

<?php if ($headerBackUrl): ?>
<div style="padding: var(--space-4) var(--space-6);">
    <a href="<?php echo $headerBackUrl; ?>" class="btn btn-outline">
        <svg class="icon"><use href="#icon-arrow-left"></use></svg>
        <?php echo htmlspecialchars($headerBackLabel); ?>
    </a>
</div>
<?php endif; ?>
