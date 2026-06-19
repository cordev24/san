<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createPago();
            break;
        case 'list':
        case 'list_all':
            listPagos();
            break;
        case 'update':
            updatePago();
            break;
        case 'stats':
            getStats();
            break;
        case 'reportar_pago':
            reportarPago();
            break;
        case 'aprobar_pago':
            aprobarPago();
            break;
        case 'rechazar_pago':
            rechazarPago();
            break;
        case 'list_pendientes_verificacion':
            listPendientesVerificacion();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

function createPago()
{
    global $pdo;

    $pago_id = $_POST['pago_id'] ?? null;
    $fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
    $notas = trim($_POST['notas'] ?? '');

    if (!$pago_id) {
        jsonResponse(false, 'ID de pago requerido');
        return;
    }

    // Get payment info
    $stmt = $pdo->prepare("SELECT * FROM pagos WHERE id = ?");
    $stmt->execute([$pago_id]);
    $pago = $stmt->fetch();

    if (!$pago) {
        jsonResponse(false, 'Pago no encontrado');
        return;
    }

    if ($pago['estado'] === 'pagado') {
        jsonResponse(false, 'Este pago ya fue registrado');
        return;
    }

    $tasa_aplicada = getBcvRate();

    // Update payment
    $stmt = $pdo->prepare("
        UPDATE pagos 
        SET fecha_pago = ?, estado = 'pagado', metodo_pago = ?, notas = ?, tasa_aplicada = ? 
        WHERE id = ?
    ");

    $stmt->execute([
        $fecha_pago,
        $metodo_pago,
        $notas,
        $tasa_aplicada,
        $pago_id
    ]);

    jsonResponse(true, 'Pago registrado exitosamente');
}

function listPagos()
{
    global $pdo;

    $participante_id = $_GET['participante_id'] ?? null;
    $grupo_san_id = $_GET['grupo_san_id'] ?? null;
    $estado = $_GET['estado'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "
        SELECT p.*, 
               part.nombre, part.apellido, part.cedula,
               part.nombre as nombre_participante, part.apellido as apellido_participante, part.cedula as cedula_participante,
               gs.nombre as grupo_nombre, gs.monto_cuota,
               CASE 
                   WHEN p.estado = 'pendiente' AND p.fecha_vencimiento < CURDATE() THEN 'atrasado'
                   ELSE p.estado
               END as estado_real
        FROM pagos p
        JOIN participantes part ON p.participante_id = part.id
        JOIN grupos_san gs ON part.grupo_san_id = gs.id
        WHERE 1=1
    ";

    $params = [];

    if ($search) {
        $sql .= " AND (part.nombre LIKE ? OR part.apellido LIKE ? OR part.cedula LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($participante_id) {
        $sql .= " AND p.participante_id = ?";
        $params[] = $participante_id;
    }

    if ($grupo_san_id) {
        $sql .= " AND part.grupo_san_id = ?";
        $params[] = $grupo_san_id;
    }

    if ($estado) {
        if ($estado === 'atrasado') {
            $sql .= " AND p.estado = 'pendiente' AND p.fecha_vencimiento < CURDATE()";
        } else {
            $sql .= " AND p.estado = ?";
            $params[] = $estado;
        }
    }

    $sql .= " ORDER BY p.fecha_vencimiento ASC, p.numero_cuota ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pagos = $stmt->fetchAll();

    jsonResponse(true, 'Pagos obtenidos', ['pagos' => $pagos]);
}

function updatePago()
{
    global $pdo;

    $id = $_POST['id'] ?? null;
    $estado = $_POST['estado'] ?? null;

    if (!$id || !$estado) {
        jsonResponse(false, 'ID y estado requeridos');
        return;
    }

    $stmt = $pdo->prepare("UPDATE pagos SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    jsonResponse(true, 'Estado actualizado exitosamente');
}

function getStats()
{
    global $pdo;

    $grupo_san_id = $_GET['grupo_san_id'] ?? null;

    $sql = "
        SELECT 
            COUNT(*) as total_pagos,
            COALESCE(SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END), 0) as pagados,
            COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END), 0) as pendientes,
            COALESCE(SUM(CASE WHEN estado = 'pendiente_verificacion' THEN 1 ELSE 0 END), 0) as en_verificacion,
            COALESCE(SUM(CASE WHEN estado = 'pendiente' AND fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) as atrasados,
            COALESCE(SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END), 0) as total_recaudado,
            COALESCE(SUM(monto), 0) as total_esperado
        FROM pagos p
        JOIN participantes part ON p.participante_id = part.id
        WHERE 1=1
    ";

    $params = [];

    if ($grupo_san_id) {
        $sql .= " AND part.grupo_san_id = ?";
        $params[] = $grupo_san_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch();

    jsonResponse(true, 'Estadísticas obtenidas', ['stats' => $stats]);
}

// ================================================================
// FLUJO DE APROBACIÓN EN 2 PASOS
// ================================================================

/**
 * El PARTICIPANTE reporta un pago: sube el comprobante y referencia.
 * El pago queda en estado 'pendiente_verificacion' hasta que el admin apruebe.
 *
 * Soporta dos modos:
 *   1. Con pago_id existente (pendiente/atrasado) → flujo normal
 *   2. Sin pago_id o pago ya pagado → crea el pago si el número de cuota no existe
 */
function reportarPago()
{
    global $pdo;

    $pago_id          = $_POST['pago_id'] ?? null;
    $numero_cuota     = isset($_POST['numero_cuota']) ? (int)$_POST['numero_cuota'] : null;
    $monto            = isset($_POST['monto']) ? (float)$_POST['monto'] : null;
    $grupo_id         = isset($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : null;
    $referencia       = trim($_POST['referencia_pago'] ?? '');
    $monto_bs_pagado  = isset($_POST['monto_bs_pagado']) ? (float)$_POST['monto_bs_pagado'] : null;
    $notas            = trim($_POST['notas'] ?? '');
    $comprobante_path = null;

    $user = getCurrentUser();

    // ── Resolver participante_id ──────────────────────────────────────
    if ($pago_id) {
        // Vía pago_id — el JOIN con participantes ya valida ownership
        $stmt = $pdo->prepare("
            SELECT pg.participante_id, p.usuario_id, p.grupo_san_id
            FROM pagos pg
            JOIN participantes p ON pg.participante_id = p.id
            WHERE pg.id = ?
        ");
        $stmt->execute([$pago_id]);
        $rel = $stmt->fetch();
        if (!$rel || $rel['usuario_id'] != $user['id']) {
            jsonResponse(false, 'Pago no encontrado o no autorizado');
            return;
        }
        $participante_id = $rel['participante_id'];
        $grupo_id = $rel['grupo_san_id'] ?? $grupo_id;
    } elseif ($grupo_id) {
        // Vía grupo_id — verificar que el usuario es participante activo en ese grupo
        $stmt = $pdo->prepare("
            SELECT id FROM participantes
            WHERE usuario_id = ? AND grupo_san_id = ? AND activo = 1
            LIMIT 1
        ");
        $stmt->execute([$user['id'], $grupo_id]);
        $participante_id = $stmt->fetchColumn();

        if (!$participante_id) {
            jsonResponse(false, 'No eres participante activo en este grupo');
            return;
        }
    } else {
        // Sin pago_id ni grupo_id — buscar cualquier participante activo (legacy)
        $stmt = $pdo->prepare("SELECT id FROM participantes WHERE usuario_id = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$user['id']]);
        $participante_id = $stmt->fetchColumn();

        if (!$participante_id) {
            jsonResponse(false, 'Participante no encontrado');
            return;
        }
    }

    // ── Resolver el pago ──────────────────────────────────────────────
    $pago = null;

    // Modo 1: buscar por pago_id (si es válido y no está pagado)
    if ($pago_id) {
        $stmt = $pdo->prepare("
            SELECT pg.*, p.usuario_id
            FROM pagos pg
            JOIN participantes p ON pg.participante_id = p.id
            WHERE pg.id = ? AND pg.participante_id = ?
        ");
        $stmt->execute([$pago_id, $participante_id]);
        $pago = $stmt->fetch();

        if ($pago && $pago['estado'] === 'pagado') {
            // Ya está pagado — no se puede re-reportar, pero permitimos crear nueva cuota
            $pago = null;
        } elseif ($pago && $pago['estado'] === 'pendiente_verificacion') {
            jsonResponse(false, 'Ya reportaste este pago. Está en espera de verificación del administrador');
            return;
        }
    }

    // Modo 2: buscar por numero_cuota + participante (si no se encontró por ID)
    if (!$pago && $numero_cuota) {
        $stmt = $pdo->prepare("
            SELECT * FROM pagos
            WHERE participante_id = ? AND numero_cuota = ?
            LIMIT 1
        ");
        $stmt->execute([$participante_id, $numero_cuota]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['estado'] === 'pagado') {
                jsonResponse(false, 'La cuota #' . $numero_cuota . ' ya fue pagada y aprobada');
                return;
            }
            if ($existing['estado'] === 'pendiente_verificacion') {
                jsonResponse(false, 'Ya reportaste la cuota #' . $numero_cuota . '. Está en espera de verificación');
                return;
            }
            $pago = $existing;
            $pago_id = $pago['id'];
        }
    }

    // Modo 3: crear el pago si no existe
    if (!$pago) {
        if (!$numero_cuota || !$monto) {
            jsonResponse(false, 'Número de cuota y monto requeridos para registrar un nuevo pago');
            return;
        }

        // Calcular fecha de vencimiento según frecuencia del grupo
        $frecuencia = 'mensual'; // default
        if ($grupo_id) {
            $stmt = $pdo->prepare("SELECT frecuencia, monto_cuota FROM grupos_san WHERE id = ?");
            $stmt->execute([$grupo_id]);
            $g = $stmt->fetch();
            if ($g) {
                $frecuencia = $g['frecuencia'];
                // Usar el monto_cuota del grupo si el participante no envió uno
                if (!$monto) $monto = (float)$g['monto_cuota'];
            }
        }

        // Fecha de vencimiento: si el participante ya tiene cuotas, sumar según frecuencia
        $stmt = $pdo->prepare("
            SELECT MAX(fecha_vencimiento) as ultima_fecha FROM pagos WHERE participante_id = ?
        ");
        $stmt->execute([$participante_id]);
        $ultima = $stmt->fetchColumn();

        if ($ultima) {
            $intervalo = $frecuencia === 'quincenal' ? '+15 days' : '+1 month';
            $fecha_vencimiento = date('Y-m-d', strtotime($ultima . ' ' . $intervalo));
        } else {
            $fecha_vencimiento = date('Y-m-d', strtotime('+1 month'));
        }

        // Crear el pago
        $stmt = $pdo->prepare("
            INSERT INTO pagos (participante_id, numero_cuota, monto, fecha_vencimiento, estado)
            VALUES (?, ?, ?, ?, 'pendiente')
        ");
        $stmt->execute([$participante_id, $numero_cuota, $monto, $fecha_vencimiento]);
        $pago_id = $pdo->lastInsertId();
    }

    // ── Procesar el archivo de comprobante si viene adjunto ──────────
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $file_type = mime_content_type($_FILES['comprobante']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            jsonResponse(false, 'Solo se permiten imágenes JPG, PNG, WEBP o archivos PDF');
            return;
        }

        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['comprobante']['size'] > $max_size) {
            jsonResponse(false, 'El archivo no puede superar los 5MB');
            return;
        }

        $upload_dir = dirname(__DIR__) . '/uploads/comprobantes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
        $filename = 'comp_pago' . $pago_id . '_' . time() . '.' . $ext;
        $dest = $upload_dir . $filename;

        if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $dest)) {
            jsonResponse(false, 'Error al guardar el comprobante. Verifica permisos del servidor');
            return;
        }

        $comprobante_path = 'uploads/comprobantes/' . $filename;
    }

    // ── Actualizar estado a pendiente_verificacion ─────────────────────
    $stmt = $pdo->prepare("
        UPDATE pagos
        SET estado = 'pendiente_verificacion',
            referencia_pago = ?,
            monto_bs_pagado = COALESCE(?, monto_bs_pagado),
            notas = ?,
            comprobante = COALESCE(?, comprobante)
        WHERE id = ?
    ");
    $stmt->execute([$referencia ?: null, $monto_bs_pagado, $notas ?: null, $comprobante_path, $pago_id]);

    // ── Pre-calcular equivalencia para informar al participante ────────
    $equiv = $monto_bs_pagado ? calcularEquivalenciaBCV($monto_bs_pagado) : null;

    jsonResponse(true, 'Pago reportado correctamente. El administrador lo revisará pronto.', [
        'equivalencia_usd' => $equiv ? $equiv['usd'] : null,
        'tasa_referencia'  => $equiv ? $equiv['tasa'] : null,
    ]);
}

/**
 * El ADMINISTRADOR aprueba un pago reportado.
 * Cambia el estado a 'pagado' y registra la tasa BCV del momento.
 */
function aprobarPago()
{
    global $pdo;

    // Solo admins pueden aprobar
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        jsonResponse(false, 'Acceso no autorizado');
        return;
    }

    $pago_id    = $_POST['pago_id'] ?? null;
    $metodo     = trim($_POST['metodo_pago'] ?? 'Transferencia');
    $fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');

    if (!$pago_id) {
        jsonResponse(false, 'ID de pago requerido');
        return;
    }

    $stmt = $pdo->prepare("SELECT id, estado, monto_bs_pagado FROM pagos WHERE id = ?");
    $stmt->execute([$pago_id]);
    $pago = $stmt->fetch();

    if (!$pago) {
        jsonResponse(false, 'Pago no encontrado');
        return;
    }

    if ($pago['estado'] === 'pagado') {
        jsonResponse(false, 'Este pago ya fue aprobado');
        return;
    }

    // Aplicar calcularEquivalenciaBCV() para convertir Bs pagados a USD
    // y registrar la tasa oficial del dia (informe S6.3.2)
    $monto_bs = (float)($pago['monto_bs_pagado'] ?? 0);
    $equivalencia = calcularEquivalenciaBCV($monto_bs);
    $tasa = $equivalencia['tasa'];

    $stmt = $pdo->prepare("
        UPDATE pagos
        SET estado = 'pagado',
            fecha_pago = ?,
            metodo_pago = ?,
            tasa_aplicada = ?
        WHERE id = ?
    ");
    $stmt->execute([$fecha_pago, $metodo, $tasa, $pago_id]);

    jsonResponse(true, 'Pago aprobado exitosamente', [
        'tasa_aplicada'    => $tasa,
        'monto_bs'         => $monto_bs,
        'equivalente_usd'  => $equivalencia['usd'],
    ]);
}

/**
 * El ADMINISTRADOR rechaza un pago reportado, devolviéndolo a 'pendiente'.
 */
function rechazarPago()
{
    global $pdo;

    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        jsonResponse(false, 'Acceso no autorizado');
        return;
    }

    $pago_id = $_POST['pago_id'] ?? null;
    $motivo  = trim($_POST['motivo'] ?? '');

    if (!$pago_id) {
        jsonResponse(false, 'ID de pago requerido');
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE pagos
        SET estado = 'pendiente',
            comprobante = NULL,
            referencia_pago = NULL,
            notas = CONCAT(COALESCE(notas,''), IF(? != '', CONCAT(' [RECHAZADO: ', ?, ']'), ' [RECHAZADO]'))
        WHERE id = ?
    ");
    $stmt->execute([$motivo, $motivo, $pago_id]);

    jsonResponse(true, 'Pago rechazado. El participante deberá volver a reportarlo.');
}

/**
 * Lista todos los pagos en estado 'pendiente_verificacion' para el panel admin.
 */
function listPendientesVerificacion()
{
    global $pdo;

    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        jsonResponse(false, 'Acceso no autorizado');
        return;
    }

    $stmt = $pdo->query("
        SELECT 
            pg.id,
            pg.numero_cuota,
            pg.monto,
            pg.fecha_vencimiento,
            pg.referencia_pago,
            pg.comprobante,
            pg.notas,
            pg.created_at,
            part.nombre,
            part.apellido,
            part.cedula,
            part.telefono,
            gs.nombre  AS grupo_nombre,
            gs.id      AS grupo_id,
            prod.nombre AS producto_nombre
        FROM pagos pg
        JOIN participantes part ON pg.participante_id = part.id
        JOIN grupos_san gs     ON part.grupo_san_id = gs.id
        JOIN productos prod     ON gs.producto_id = prod.id
        WHERE pg.estado = 'pendiente_verificacion'
        ORDER BY pg.created_at ASC
    ");
    $pagos = $stmt->fetchAll();

    jsonResponse(true, 'Pagos pendientes de verificación', [
        'pagos' => $pagos,
        'total' => count($pagos)
    ]);
}
