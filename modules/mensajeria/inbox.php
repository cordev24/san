<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

$rol = $_SESSION['rol'] ?? 'admin';
$user_id = $user['id'];

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send') {
    $receiver_id = $_POST['receiver_id'];
    $asunto = trim($_POST['asunto']);
    $cuerpo = trim($_POST['cuerpo']);
    
    $stmt = $pdo->prepare("INSERT INTO mensajes (sender_id, receiver_id, asunto, cuerpo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $receiver_id, $asunto, $cuerpo]);
    
    // Redirect to avoid form resubmission
    header("Location: inbox.php?sent=1");
    exit;
}

// Mark message as read
if (isset($_GET['read_id'])) {
    $stmt = $pdo->prepare("UPDATE mensajes SET leido = 1 WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$_GET['read_id'], $user_id]);
    header("Location: inbox.php");
    exit;
}

// Fetch messages received
$stmt = $pdo->prepare("
    SELECT m.*, u.nombre as sender_name, u.rol as sender_rol 
    FROM mensajes m
    JOIN usuarios u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.fecha DESC
");
$stmt->execute([$user_id]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch possible recipients
if ($rol == 'admin') {
    // Admin can msg any user that is a participant
    $stmtR = $pdo->query("SELECT id, nombre, username as subtitle FROM usuarios WHERE rol = 'participante'");
    $recipients = $stmtR->fetchAll();
} else {
    // Participant can only msg admin
    $stmtR = $pdo->query("SELECT id, nombre, 'Administrador' as subtitle FROM usuarios WHERE rol = 'admin'");
    $recipients = $stmtR->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajería - MySan</title>
    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <style>
        .page-header { padding: var(--space-8); max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-text-primary); display: flex; align-items: center; gap: var(--space-3); }

        .inbox-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: var(--space-6);
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-8);
            height: calc(100vh - 200px);
        }

        .messages-list {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .message-item {
            padding: var(--space-4);
            border-bottom: 1px solid var(--glass-border);
            cursor: pointer;
            transition: background 0.2s;
        }

        .message-item:hover {
            background: rgba(255,255,255,0.03);
        }
        
        .message-item.unread {
            background: rgba(175, 100, 255, 0.05);
            border-left: 3px solid var(--color-violeta);
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
            margin-bottom: var(--space-2);
        }

        .message-subject {
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-1);
        }

        .message-preview {
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-view, .compose-view {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            display: none;
            flex-direction: column;
        }

        .message-view.active, .compose-view.active {
            display: flex;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--color-text-tertiary);
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
        }
    </style>
</head>
<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <!-- Header -->
    <?php
    $headerLogoutHref = '../../logout.php';
    if ($rol == 'admin') {
        $headerLogoHref = '../../dashboard.php';
        $headerBackUrl = '../../dashboard.php';
        $headerBackLabel = 'Volver al Admin';
    } else {
        $headerLogoHref = '../../dashboard_participante.php';
        $headerBackUrl = '../../dashboard_participante.php';
        $headerBackLabel = 'Volver al Dashboard';
    }
    include '../../includes/header.php';
    ?>

    <div class="page-header">
        <h1 class="page-title">
            <svg class="icon-xl" style="stroke: var(--color-violeta);"><use href="#icon-mail"></use></svg>
            Bandeja de Entrada
        </h1>
        <button class="btn btn-violeta" onclick="showCompose()">
            <svg class="icon"><use href="#icon-edit-3"></use></svg> Redactar Mensaje
        </button>
    </div>

    <div class="inbox-layout">
        <!-- Sidebar -->
        <div class="messages-list">
            <?php if (empty($mensajes)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--color-text-tertiary);">No tienes mensajes</div>
            <?php else: ?>
                <?php foreach ($mensajes as $m): ?>
                    <div class="message-item <?php echo $m['leido'] ? '' : 'unread'; ?>" 
                         onclick="viewMessage(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                        <div class="message-meta">
                            <span style="font-weight:bold; color: var(--color-text-secondary);"><?php echo htmlspecialchars($m['sender_name']); ?></span>
                            <span><?php echo date('d M, Y', strtotime($m['fecha'])); ?></span>
                        </div>
                        <div class="message-subject"><?php echo htmlspecialchars($m['asunto']); ?></div>
                        <div class="message-preview"><?php echo htmlspecialchars($m['cuerpo']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Editor -->
        <div class="compose-view" id="composeView">
            <h2 style="font-size: var(--font-size-xl); margin-bottom: var(--space-6); color: var(--color-text-primary);">Nuevo Mensaje</h2>
            <form method="POST">
                <input type="hidden" name="action" value="send">
                
                <div class="form-group">
                    <label class="form-label">Destinatario</label>
                    <select name="receiver_id" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php foreach($recipients as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['nombre']); ?> (<?php echo htmlspecialchars($r['subtitle']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Asunto</label>
                    <input type="text" name="asunto" class="form-input" required>
                </div>

                <div class="form-group" style="flex-grow: 1; display:flex; flex-direction:column;">
                    <label class="form-label">Mensaje</label>
                    <textarea name="cuerpo" class="form-input" style="flex-grow: 1; resize:none;" required></textarea>
                </div>

                <div style="margin-top: var(--space-4); text-align:right;">
                    <button type="submit" class="btn btn-violeta">Enviar Mensaje</button>
                    <button type="button" class="btn btn-outline" onclick="hideAllViews()">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Viewer -->
        <div class="message-view" id="messageView">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--glass-border); padding-bottom:var(--space-4); margin-bottom:var(--space-4);">
                <div>
                    <h2 id="viewSubject" style="font-size: var(--font-size-2xl); color: var(--color-text-primary); margin-bottom:var(--space-2);">...</h2>
                    <div style="color: var(--color-text-tertiary); font-size: var(--font-size-sm);">De: <strong id="viewSender" style="color: var(--color-text-secondary);">...</strong></div>
                </div>
                <div id="viewDate" style="color: var(--color-text-tertiary); font-size: var(--font-size-sm);">...</div>
            </div>
            
            <div id="viewBody" style="color: var(--color-text-primary); line-height: 1.6; white-space: pre-wrap; font-size: var(--font-size-md); flex-grow: 1; overflow-y: auto;">
                ...
            </div>
            
            <div style="margin-top: var(--space-6); text-align:right; border-top: 1px solid var(--glass-border); padding-top:var(--space-4);">
                <button class="btn btn-outline" id="btnMarkRead" style="display:none;" onclick="markRead()">Marcar como leído</button>
                <button class="btn btn-violeta" onclick="replyMessage()">Responder</button>
            </div>
        </div>

        <!-- Default State -->
        <div class="empty-state" id="emptyState">
            <svg class="icon-xl" style="opacity:0.2; margin-bottom: var(--space-4);"><use href="#icon-message-square"></use></svg>
            <p>Selecciona un mensaje para leerlo o redacta uno nuevo.</p>
        </div>
    </div>

    <script>
        let currentMessageId = null;

        function hideAllViews() {
            document.getElementById('composeView').classList.remove('active');
            document.getElementById('messageView').classList.remove('active');
            document.getElementById('emptyState').style.display = 'flex';
        }

        function showCompose() {
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('messageView').classList.remove('active');
            document.getElementById('composeView').classList.add('active');
        }

        function viewMessage(msg) {
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('composeView').classList.remove('active');
            
            document.getElementById('viewSubject').innerText = msg.asunto;
            document.getElementById('viewSender').innerText = msg.sender_name + " (" + msg.sender_rol + ")";
            document.getElementById('viewDate').innerText = new Date(msg.fecha).toLocaleString();
            document.getElementById('viewBody').innerText = msg.cuerpo;
            
            currentMessageId = msg.id;
            
            if (msg.leido == 0) {
                document.getElementById('btnMarkRead').style.display = 'inline-flex';
            } else {
                document.getElementById('btnMarkRead').style.display = 'none';
            }

            // Quick reply setup
            document.querySelector('[name="receiver_id"]').value = msg.sender_id;
            document.querySelector('[name="asunto"]').value = "RE: " + msg.asunto;

            document.getElementById('messageView').classList.add('active');
        }

        function markRead() {
            if (currentMessageId) {
                window.location.href = "inbox.php?read_id=" + currentMessageId;
            }
        }

        function replyMessage() {
            showCompose();
        }
        
        <?php if(isset($_GET['sent'])): ?>
            alert("Mensaje enviado exitosamente.");
            window.history.replaceState({}, document.title, "inbox.php");
        <?php endif; ?>
    </script>
</body>
</html>
