<?php
/**
 * Componente de Notificaciones para el header del participante.
 * 
 * Requiere: $pdo (DB), $user (getCurrentUser()) definidos antes de incluir
 * Requiere: feather-sprite.svg ya incluido en el <body>
 * 
 * Uso:
 *   <?php include 'includes/notificaciones_participante.php'; ?>
 * 
 * Colocar dentro del <header> o <div class="user-nav">, antes del botón de salir.
 */

$usuario_id = $user['id'] ?? 0;

// Fetch unread notifications count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leido = 0");
$stmtCount->execute([$usuario_id]);
$totalNoLeidas = (int)$stmtCount->fetchColumn();
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
        top: calc(100% + 8px);
        right: 0;
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
        background: color-mix(in srgb, var(--color-violeta) 18%, transparent);
        color: var(--color-violeta);
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

    .notif-loader {
        padding: var(--space-8);
        text-align: center;
        color: var(--color-text-tertiary);
        font-size: var(--font-size-sm);
    }

    .notif-loader::after {
        content: '';
        display: inline-block;
        width: 16px;
        height: 16px;
        margin-left: var(--space-2);
        border: 2px solid var(--glass-border);
        border-top-color: var(--color-violeta);
        border-radius: 50%;
        animation: notifSpin 0.6s linear infinite;
        vertical-align: middle;
    }

    @keyframes notifSpin {
        to { transform: rotate(360deg); }
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
    <div class="notif-trigger" onclick="toggleNotifParticipante()" title="Notificaciones">
        <svg class="icon" style="width:20px;height:20px;"><use href="#icon-bell"></use></svg>
        <?php if ($totalNoLeidas > 0): ?>
            <span class="notif-badge" id="notifBadgeParticipante"><?php echo $totalNoLeidas > 99 ? '99+' : $totalNoLeidas; ?></span>
        <?php endif; ?>
    </div>

    <div class="notif-dropdown" id="notifDropdownParticipante">
        <div class="notif-header">
            <h4>Notificaciones</h4>
            <button class="btn-mark-all" id="btnMarkAllPart" onclick="marcarTodasParticipante(event)" style="display:none;">Marcar todas como leídas</button>
        </div>

        <div class="notif-list" id="notifListParticipante">
            <div class="notif-loader">Cargando</div>
        </div>
    </div>

    <div class="notif-overlay" id="notifOverlayParticipante" onclick="toggleNotifParticipante()"></div>
</div>

<script>
    var notifOpenParticipante = false;
    var notifLoadedParticipante = false;

    function toggleNotifParticipante() {
        notifOpenParticipante = !notifOpenParticipante;
        document.getElementById('notifDropdownParticipante').classList.toggle('open', notifOpenParticipante);
        document.getElementById('notifOverlayParticipante').classList.toggle('open', notifOpenParticipante);

        if (notifOpenParticipante && !notifLoadedParticipante) {
            cargarNotificacionesParticipante();
            notifLoadedParticipante = true;
        }
    }

    function cargarNotificacionesParticipante() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/notificaciones.php?action=listar', true);
        xhr.onload = function () {
            var list = document.getElementById('notifListParticipante');
            var btnMarkAll = document.getElementById('btnMarkAllPart');

            try {
                var data = JSON.parse(xhr.responseText);
            } catch (e) {
                list.innerHTML = '<div class="notif-empty">Error al cargar notificaciones</div>';
                return;
            }

            if (!data.ok || !data.notificaciones || data.notificaciones.length === 0) {
                list.innerHTML = '<div class="notif-empty"><svg class="icon"><use href="#icon-check-circle"></use></svg><div>No tienes notificaciones</div></div>';
                btnMarkAll.style.display = 'none';
                return;
            }

            btnMarkAll.style.display = 'block';
            var html = '';
            for (var i = 0; i < data.notificaciones.length; i++) {
                var n = data.notificaciones[i];
                html += '<div class="notif-item" onclick="marcarLeidaParticipante(event, ' + n.id + ')">';
                html += '    <div class="notif-item-icon">';
                html += '        <svg><use href="#icon-bell"></use></svg>';
                html += '    </div>';
                html += '    <div class="notif-item-body">';
                html += '        <div class="mensaje">' + escapeHtml(n.mensaje) + '</div>';
                html += '        <div class="tiempo">' + (n.tiempo || '') + '</div>';
                html += '    </div>';
                html += '</div>';
            }
            list.innerHTML = html;
        };
        xhr.onerror = function () {
            document.getElementById('notifListParticipante').innerHTML = '<div class="notif-empty">Error de conexión</div>';
        };
        xhr.send();
    }

    function marcarLeidaParticipante(e, id) {
        e.stopPropagation();
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/notificaciones.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status < 200 || xhr.status >= 300) {
                showNotification('Error al marcar notificación como leída', 'error');
                return;
            }
            // Close dropdown
            toggleNotifParticipante();
            // Decrement badge
            actualizarBadgeParticipante();
        };
        xhr.onerror = function () {
            showNotification('Error de conexión al marcar notificación', 'error');
        };
        xhr.send('action=marcar_leido&id=' + id);
    }

    function marcarTodasParticipante(e) {
        e.stopPropagation();
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/notificaciones.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            document.getElementById('notifDropdownParticipante').classList.remove('open');
            notifOpenParticipante = false;
            notifLoadedParticipante = false;
            // Remove badge
            var badge = document.getElementById('notifBadgeParticipante');
            if (badge) badge.remove();
            location.reload();
        };
        xhr.send('action=marcar_todas');
    }

    function actualizarBadgeParticipante() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/notificaciones.php?action=contar', true);
        xhr.onload = function () {
            try {
                var data = JSON.parse(xhr.responseText);
                var badge = document.getElementById('notifBadgeParticipante');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                    } else {
                        var trigger = document.querySelector('.notif-trigger');
                        if (trigger) {
                            var newBadge = document.createElement('span');
                            newBadge.className = 'notif-badge';
                            newBadge.id = 'notifBadgeParticipante';
                            newBadge.textContent = data.count > 99 ? '99+' : data.count;
                            trigger.appendChild(newBadge);
                        }
                    }
                } else {
                    if (badge) badge.remove();
                }
            } catch (e) {}
        };
        xhr.send();
    }
</script>
