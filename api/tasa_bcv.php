<?php
/**
 * api/tasa_bcv.php
 * Endpoints para gestión de la Tasa BCV.
 * Permite al administrador registrar/corregir la tasa del día manualmente
 * y consultar el historial de tasas. (Informe §6.1.2 RF-4)
 */
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_current':
            getCurrentRate();
            break;
        case 'register_manual':
            registerManual();
            break;
        case 'history':
            getHistory();
            break;
        case 'diagnostico':
            diagnosticoBCV();
            break;
        case 'forzar_refresh':
            forzarRefresh();
            break;
        default:
            jsonResponse(false, 'Acción no válida');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Error del servidor: ' . $e->getMessage());
}

function getCurrentRate()
{
    global $pdo;

    // getBcvRate() tiene toda la lógica: BD de hoy → API → última conocida
    // Lo llamamos SIEMPRE para que refresque desde la API si hoy no hay registro.
    $tasa = getBcvRate();

    // Ahora leemos el registro completo de BD para obtener fecha y origen reales
    $stmt = $pdo->query("SELECT tasa, fecha, origen FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
    $row  = $stmt->fetch();

    if ($row) {
        jsonResponse(true, 'Tasa actual', [
            'tasa'   => (float)$row['tasa'],
            'fecha'  => $row['fecha'],
            'origen' => $row['origen'],
        ]);
        return;
    }

    // Si la BD sigue vacía (API falló y no había nada previo)
    jsonResponse($tasa > 0, $tasa > 0 ? 'Tasa obtenida (no persistida)' : 'Sin tasa disponible', [
        'tasa'   => $tasa,
        'fecha'  => date('Y-m-d'),
        'origen' => 'sin_datos',
    ]);
}

function registerManual()
{
    global $pdo;
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        jsonResponse(false, 'Acceso no autorizado');
        return;
    }
    $tasa  = isset($_POST['tasa'])  ? (float)$_POST['tasa']  : 0;
    $fecha = isset($_POST['fecha']) ? trim($_POST['fecha'])   : date('Y-m-d');
    if ($tasa <= 0) {
        jsonResponse(false, 'La tasa debe ser un valor positivo mayor que 0');
        return;
    }
    $ok = registrarTasaManual($tasa, $fecha);
    if ($ok) {
        // Limpiar notificaciones de error de la API y actualizar estado
        try {
            $pdo->prepare("UPDATE configuracion SET valor = 'up' WHERE clave = 'bcv_api_status'")->execute();
            $pdo->prepare("UPDATE configuracion SET valor = NOW() WHERE clave = 'bcv_api_last_check'")->execute();
            $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE tipo = 'bcv_api_error'")->execute();
        } catch (Exception $ex) {}

        jsonResponse(true, 'Tasa BCV registrada correctamente', ['tasa' => $tasa, 'fecha' => $fecha]);
    } else {
        jsonResponse(false, 'No se pudo guardar la tasa.');
    }
}

function getHistory()
{
    global $pdo;
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        jsonResponse(false, 'Acceso no autorizado');
        return;
    }
    $stmt = $pdo->query("SELECT tasa, fecha, origen, created_at FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 30");
    jsonResponse(true, 'Historial de tasas', ['tasas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function forzarRefresh()
{
    global $pdo;

    // Eliminar el registro de hoy para forzar una nueva llamada a la API
    $today = date('Y-m-d');
    $del   = $pdo->prepare("DELETE FROM tasas_cambio WHERE fecha = ?");
    $del->execute([$today]);
    $deleted = $del->rowCount();

    // Llamar a la API directamente
    $ch = curl_init('https://ve.dolarapi.com/v1/dolares/oficial');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MySan/1.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        // Guardar estado de caída y crear alertas para admins
        try {
            $pdo->prepare("UPDATE configuracion SET valor = 'down' WHERE clave = 'bcv_api_status'")->execute();
            $pdo->prepare("UPDATE configuracion SET valor = NOW() WHERE clave = 'bcv_api_last_check'")->execute();

            $lastRateVal = 75.00;
            $lastRateDate = date('Y-m-d');
            $stmtLastTasa = $pdo->query("SELECT tasa, fecha FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
            if ($rowLastTasa = $stmtLastTasa->fetch()) {
                $lastRateVal = (float)$rowLastTasa['tasa'];
                $lastRateDate = $rowLastTasa['fecha'];
            }

            $stmtAdmins = $pdo->query("SELECT id FROM usuarios WHERE rol = 'admin'");
            $admins = $stmtAdmins->fetchAll();

            $msg = "La API para consultar la tasa BCV no está disponible (forzado). Se usará la última tasa registrada de Bs. " . number_format($lastRateVal, 2) . " ($lastRateDate). Por favor, realice el registro manual para evitar pérdidas.";
            $link = "/modules/comprobantes/index.php";

            $stmtCheckNotif = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND tipo = 'bcv_api_error' AND leido = 0");
            $stmtInsertNotif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, link, leido, fecha) VALUES (?, 'bcv_api_error', ?, ?, 0, NOW())");

            foreach ($admins as $admin) {
                $stmtCheckNotif->execute([$admin['id']]);
                if ((int)$stmtCheckNotif->fetchColumn() === 0) {
                    $stmtInsertNotif->execute([$admin['id'], $msg, $link]);
                }
            }
        } catch (Exception $ex) {}

        jsonResponse(false, 'API no disponible. Registros eliminados: ' . $deleted, [
            'http_code'  => $httpCode,
            'curl_error' => $curlError ?: null,
        ]);
        return;
    }

    $data    = json_decode($response, true);
    $newRate = isset($data['promedio']) ? (float)$data['promedio'] : null;

    if (!$newRate || $newRate <= 0) {
        // Guardar estado de caída y crear alertas
        try {
            $pdo->prepare("UPDATE configuracion SET valor = 'down' WHERE clave = 'bcv_api_status'")->execute();
            $pdo->prepare("UPDATE configuracion SET valor = NOW() WHERE clave = 'bcv_api_last_check'")->execute();

            $lastRateVal = 75.00;
            $lastRateDate = date('Y-m-d');
            $stmtLastTasa = $pdo->query("SELECT tasa, fecha FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
            if ($rowLastTasa = $stmtLastTasa->fetch()) {
                $lastRateVal = (float)$rowLastTasa['tasa'];
                $lastRateDate = $rowLastTasa['fecha'];
            }

            $stmtAdmins = $pdo->query("SELECT id FROM usuarios WHERE rol = 'admin'");
            $admins = $stmtAdmins->fetchAll();

            $msg = "La API respondió pero sin promedio válido (forzado). Se usará la última tasa registrada de Bs. " . number_format($lastRateVal, 2) . " ($lastRateDate). Por favor, realice el registro manual para evitar pérdidas.";
            $link = "/modules/comprobantes/index.php";

            $stmtCheckNotif = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND tipo = 'bcv_api_error' AND leido = 0");
            $stmtInsertNotif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, link, leido, fecha) VALUES (?, 'bcv_api_error', ?, ?, 0, NOW())");

            foreach ($admins as $admin) {
                $stmtCheckNotif->execute([$admin['id']]);
                if ((int)$stmtCheckNotif->fetchColumn() === 0) {
                    $stmtInsertNotif->execute([$admin['id'], $msg, $link]);
                }
            }
        } catch (Exception $ex) {}

        jsonResponse(false, 'La API respondió pero sin campo promedio válido', [
            'respuesta' => $data,
        ]);
        return;
    }

    $insert = $pdo->prepare(
        "INSERT INTO tasas_cambio (tasa, fecha, origen) VALUES (?, ?, 'auto')"
    );
    $insert->execute([$newRate, $today]);

    // Actualizar estado de la API a 'up' y limpiar notificaciones
    try {
        $pdo->prepare("UPDATE configuracion SET valor = 'up' WHERE clave = 'bcv_api_status'")->execute();
        $pdo->prepare("UPDATE configuracion SET valor = NOW() WHERE clave = 'bcv_api_last_check'")->execute();
        $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE tipo = 'bcv_api_error'")->execute();
    } catch (Exception $ex) {}

    jsonResponse(true, 'Tasa actualizada desde la API BCV', [
        'tasa_anterior_eliminada' => $deleted > 0,
        'tasa_nueva'  => $newRate,
        'fecha'       => $today,
        'fecha_api'   => $data['fechaActualizacion'] ?? null,
    ]);
}

function diagnosticoBCV()
{
    $url = 'https://ve.dolarapi.com/v1/dolares/oficial';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MySan/1.0');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    $decoded = $response ? json_decode($response, true) : null;

    jsonResponse(true, 'Diagnóstico BCV', [
        'curl_disponible' => function_exists('curl_init'),
        'url'             => $url,
        'http_code'       => $httpCode,
        'curl_errno'      => $curlErrno,
        'curl_error'      => $curlError ?: null,
        'respuesta_cruda' => $response ?: null,
        'promedio_leido'  => $decoded['promedio'] ?? null,
        'fecha_api'       => $decoded['fechaActualizacion'] ?? null,
    ]);
}
