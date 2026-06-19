<?php
/**
 * Componente de Notificaciones - Incluir en cualquier página admin
 * 
 * Requiere: $pdo (DB), $user (getCurrentUser()) definidos antes de incluir
 * Requiere: feather-sprite.svg ya incluido en el <body>
 * 
 * Uso:
 *   <?php include 'includes/notificaciones.php'; ?>
 * 
 * Colocar dentro del <header> o <div class="header-user">, antes del botón de salir.
 */

$usuario_id = $user['id'] ?? 0;

// ──────────────────────────────────────────────────────────
// 1. Generar notificaciones de mora (si hace falta)
// ──────────────────────────────────────────────────────────

// Get all participants with overdue payments
$stmtMorosos = $pdo->query("
    SELECT p.id AS participante_id,
           p.nombre, p.apellido,
           g.id AS grupo_id,
           g.nombre AS grupo_nombre,
           COUNT(pg.id) AS cuotas_vencidas,
           SUM(pg.monto) AS total_adeudado
    FROM participantes p
    JOIN grupos_san g ON p.grupo_san_id = g.id
    JOIN pagos pg ON pg.participante_id = p.id
    WHERE pg.estado IN ('pendiente', 'atrasado')
      AND pg.fecha_vencimiento < CURDATE()
      AND p.activo = 1
    GROUP BY p.id, p.nombre, p.apellido, g.id, g.nombre
");
$morosos = $stmtMorosos->fetchAll(PDO::FETCH_ASSOC);

foreach ($morosos as $m) {
    $link_ref = 'participante=' . $m['participante_id'] . '&grupo=' . $m['grupo_id'];

    // Check if an unread notification already exists for this participant's debt
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM notificaciones
        WHERE usuario_id = ? AND tipo = 'mora' AND link LIKE ? AND leido = 0
    ");
    $stmtCheck->execute([$usuario_id, '%' . $link_ref . '%']);
    $yaExiste = (int)$stmtCheck->fetchColumn() > 0;

    if (!$yaExiste) {
        $cuotas = (int)$m['cuotas_vencidas'];
        $total  = number_format((float)$m['total_adeudado'], 2);
        $nombre_persona = htmlspecialchars($m['nombre'] . ' ' . $m['apellido']);
        $nombre_grupo  = htmlspecialchars($m['grupo_nombre']);

        $mensaje = "$nombre_persona tiene $cuotas cuota" . ($cuotas !== 1 ? 's' : '') . " vencida" . ($cuotas !== 1 ? 's' : '') . " en $nombre_grupo (\$$total)";
        $link    = '/modules/morosidad/detalle.php?' . $link_ref;

        $stmtIns = $pdo->prepare("
            INSERT INTO notificaciones (usuario_id, tipo, mensaje, link, leido, fecha)
            VALUES (?, 'mora', ?, ?, 0, NOW())
        ");
        $stmtIns->execute([$usuario_id, $mensaje, $link]);
    }
}

// Auto-marcar como leídas notificaciones de mora que ya no aplican
$stmtNotis = $pdo->prepare("SELECT id, link FROM notificaciones WHERE usuario_id = ? AND tipo = 'mora' AND leido = 0");
$stmtNotis->execute([$usuario_id]);
$notisActivas = $stmtNotis->fetchAll(PDO::FETCH_ASSOC);
foreach ($notisActivas as $n) {
    // Extract participante_id from link
    preg_match('/participante=(\d+)/', $n['link'], $matches);
    if (!empty($matches[1])) {
        $pid = (int)$matches[1];
        $stmtVerif = $pdo->prepare("
            SELECT COUNT(*) FROM pagos
            WHERE participante_id = ? AND estado IN ('pendiente', 'atrasado') AND fecha_vencimiento < CURDATE()
        ");
        $stmtVerif->execute([$pid]);
        $sigueEnMora = (int)$stmtVerif->fetchColumn() > 0;

        if (!$sigueEnMora) {
            $stmtUpd = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE id = ?");
            $stmtUpd->execute([$n['id']]);
        }
    }
}

// Auto-marcar como leídas las notificaciones de error de la API si esta ya se encuentra activa (up)
$stmtApiStatusCheck = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'bcv_api_status'");
$apiStatusRow = $stmtApiStatusCheck->fetch();
if ($apiStatusRow && $apiStatusRow['valor'] === 'up') {
    $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ? AND tipo = 'bcv_api_error'")->execute([$usuario_id]);
}

// ──────────────────────────────────────────────────────────
// 2. Fetch unread notifications
// ──────────────────────────────────────────────────────────
$stmtList = $pdo->prepare("
    SELECT id, tipo, mensaje, link, leido, fecha
    FROM notificaciones
    WHERE usuario_id = ? AND leido = 0
    ORDER BY fecha DESC
    LIMIT 20
");
$stmtList->execute([$usuario_id]);
$notificaciones = $stmtList->fetchAll(PDO::FETCH_ASSOC);

$totalNoLeidas = count($notificaciones);

// Helper: tiempo relativo
function tiempoRelativo($fecha) {
    $diff = time() - strtotime($fecha);
    if ($diff < 60) return 'ahora';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' d';
    return date('d/m/Y', strtotime($fecha));
}
?>
<style>
    .notif-trigger {
        position: relative;
        cursor: pointer;
        color: var(--color-text-secondary);
        transition: color 0.2s;
        display: flex;
        align-items: center;
        padding: var(--space-2);
        border-radius: var(--radius-md);
    }

    .notif-trigger:hover {
        color: var(--color-violeta);
        background: var(--color-surface-hover);
    }

    .notif-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--color-salmon);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        pointer-events: none;
    }

    .notif-dropdown {
        position: absolute;
        bottom: 0;
        left: calc(100% + 16px);
        width: 380px;
        max-height: 420px;
        background: var(--color-surface);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        z-index: var(--z-modal, 1000);
        display: none;
        overflow: hidden;
    }

    .notif-dropdown.open {
        display: block;
    }

    .notif-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-3) var(--space-4);
        border-bottom: 1px solid var(--glass-border);
    }

    .notif-header h4 {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
    }

    .notif-header .btn-mark-all {
        font-size: var(--font-size-xs);
        color: var(--color-violeta);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .notif-header .btn-mark-all:hover {
        text-decoration: underline;
    }

    .notif-list {
        overflow-y: auto;
        max-height: 340px;
    }

    .notif-item {
        display: flex;
        gap: var(--space-3);
        padding: var(--space-3) var(--space-4);
        border-bottom: 1px solid var(--glass-border);
        cursor: pointer;
        transition: background 0.15s;
        text-decoration: none;
        color: inherit;
    }

    .notif-item:last-child {
        border-bottom: none;
    }

    .notif-item:hover {
        background: var(--color-surface-hover);
    }

    .notif-item-icon {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--color-salmon) 18%, transparent);
        color: var(--color-salmon);
    }

    .notif-item-icon svg {
        width: 16px;
        height: 16px;
    }

    .notif-item-body {
        flex: 1;
        min-width: 0;
    }

    .notif-item-body .mensaje {
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notif-item-body .tiempo {
        font-size: var(--font-size-xs);
        color: var(--color-text-tertiary);
        margin-top: 2px;
    }

    .notif-empty {
        padding: var(--space-8) var(--space-4);
        text-align: center;
        color: var(--color-text-tertiary);
        font-size: var(--font-size-sm);
    }

    .notif-empty svg {
        width: 36px;
        height: 36px;
        margin-bottom: var(--space-2);
        opacity: 0.3;
    }

    /* Overlay to close dropdown */
    .notif-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: calc(var(--z-modal, 1000) - 1);
    }

    .notif-overlay.open {
        display: block;
    }
</style>

<div class="notif-wrapper" style="position:relative;">
    <div class="notif-trigger" onclick="toggleNotificaciones()" title="Notificaciones">
        <svg class="icon" style="width:20px;height:20px;"><use href="#icon-alert-circle"></use></svg>
        <?php if ($totalNoLeidas > 0): ?>
            <span class="notif-badge"><?php echo $totalNoLeidas > 99 ? '99+' : $totalNoLeidas; ?></span>
        <?php endif; ?>
    </div>

    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
            <h4>Notificaciones</h4>
            <?php if ($totalNoLeidas > 0): ?>
                <button class="btn-mark-all" onclick="marcarTodasLeidas(event)">Marcar todas como leidas</button>
            <?php endif; ?>
        </div>

        <div class="notif-list">
            <?php if (empty($notificaciones)): ?>
                <div class="notif-empty">
                    <svg class="icon"><use href="#icon-check-circle"></use></svg>
                    <div>No hay notificaciones</div>
                </div>
            <?php else: ?>
                <?php foreach ($notificaciones as $n): ?>
                    <?php 
                // Fix relative paths dynamically
                $relLink = ltrim($n['link'], '/');
                $finalLink = str_replace('dashboard.php', $relLink, $headerLogoHref);
                ?>
                <a href="<?php echo htmlspecialchars($finalLink); ?>" class="notif-item" onclick="marcarLeida(event, <?php echo $n['id']; ?>)">
                        <div class="notif-item-icon">
                            <svg><use href="#icon-alert-triangle"></use></svg>
                        </div>
                        <div class="notif-item-body">
                            <div class="mensaje"><?php echo htmlspecialchars($n['mensaje']); ?></div>
                            <div class="tiempo"><?php echo tiempoRelativo($n['fecha']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="notif-overlay" id="notifOverlay" onclick="toggleNotificaciones()"></div>
</div>

<script>
    var notifOpen = false;

    function toggleNotificaciones() {
        notifOpen = !notifOpen;
        document.getElementById('notifDropdown').classList.toggle('open', notifOpen);
        document.getElementById('notifOverlay').classList.toggle('open', notifOpen);
    }

    function marcarLeida(e, id) {
        e.preventDefault();
        var href = e.currentTarget.getAttribute('href');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo str_replace("dashboard.php", "api/notificaciones.php", $headerLogoHref); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            window.location.href = href;
        };
        xhr.onerror = function () {
            window.location.href = href;
        };
        xhr.send('action=marcar_leido&id=' + id);
    }

    function marcarTodasLeidas(e) {
        e.stopPropagation();
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo str_replace("dashboard.php", "api/notificaciones.php", $headerLogoHref); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            document.getElementById('notifDropdown').classList.remove('open');
            notifOpen = false;
            // Remove badge and refresh
            location.reload();
        };
        xhr.send('action=marcar_todas');
    }
</script>
