<?php
/**
 * API de Notificaciones
 * 
 * Endpoints:
 *   GET  ?action=contar         → JSON { count: N }
 *   POST ?action=marcar_leido   → marca una como leída (id por POST)
 *   POST ?action=marcar_todas   → marca todas como leídas
 */

require_once '../config/database.php';
requireLogin();
$user = getCurrentUser();
$usuario_id = $user['id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'contar':
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leido = 0");
        $stmt->execute([$usuario_id]);
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        break;

    case 'listar':
        $stmt = $pdo->prepare("
            SELECT id, tipo, mensaje, link, leido, 
                   DATE_FORMAT(fecha, '%Y-%m-%dT%H:%i:%s') as fecha,
                   TIMESTAMPDIFF(SECOND, fecha, NOW()) as segundos_diff
            FROM notificaciones
            WHERE usuario_id = ? AND leido = 0
            ORDER BY fecha DESC
            LIMIT 20
        ");
        $stmt->execute([$usuario_id]);
        $notificaciones = $stmt->fetchAll();

        // Helper: tiempo relativo
        foreach ($notificaciones as &$n) {
            $diff = (int)$n['segundos_diff'];
            if ($diff < 60) $n['tiempo'] = 'ahora';
            elseif ($diff < 3600) $n['tiempo'] = floor($diff / 60) . ' min';
            elseif ($diff < 86400) $n['tiempo'] = floor($diff / 3600) . ' h';
            elseif ($diff < 604800) $n['tiempo'] = floor($diff / 86400) . ' d';
            else $n['tiempo'] = date('d/m/Y', strtotime($n['fecha']));
        }
        unset($n);

        echo json_encode(['ok' => true, 'notificaciones' => $notificaciones]);
        break;

    case 'marcar_leido':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuario_id]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'marcar_todas':
        $stmt = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ? AND leido = 0");
        $stmt->execute([$usuario_id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
