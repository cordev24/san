<?php
// Database Configuration - Soporta variables de entorno para Docker
$dbHost    = getenv('DB_HOST')    ?: 'db';  // Default to 'db' for Docker
$dbName    = getenv('DB_NAME')    ?: 'mysan';
$dbUser    = getenv('DB_USER')    ?: 'root';
$dbPass    = getenv('DB_PASS')    ?: 'rootpassword';
$dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

define('DB_HOST',    $dbHost);
define('DB_NAME',    $dbName);
define('DB_USER',    $dbUser);
define('DB_PASS',    $dbPass);
define('DB_CHARSET', $dbCharset);

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()
    ]));
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Helper function to require login
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Helper function to get current user
function getCurrentUser()
{
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, username, nombre, email FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * getBcvRate()
 *
 * Devuelve la tasa Bs/USD vigente con la siguiente prioridad:
 *   1. BD - tasa de hoy (evita llamadas repetidas a la API)
 *   2. API ve.dolarapi.com (si hoy no hay registro en BD, persiste el resultado)
 *   3. BD - última tasa conocida (si la API no responde)
 *   4. 0.0 — sin datos; el consumidor debe manejar este caso
 *
 * @return float
 */
function getBcvRate(): float
{
    static $cached_rate = null;
    if ($cached_rate !== null) {
        return $cached_rate;
    }

    global $pdo;
    try {
        $today = date('Y-m-d');

        // 1. Tasa de hoy en BD
        $stmt = $pdo->prepare("SELECT tasa FROM tasas_cambio WHERE fecha = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$today]);
        if ($row = $stmt->fetch()) {
            $cached_rate = (float)$row['tasa'];
            return $cached_rate;
        }

        // A. Verificar cooldown si la API está caída para evitar lentitud
        $apiStatus = 'up';
        $lastCheck = null;

        $stmtStatus = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'bcv_api_status'");
        if ($rowStatus = $stmtStatus->fetch()) {
            $apiStatus = $rowStatus['valor'];
        }
        $stmtCheck = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'bcv_api_last_check'");
        if ($rowCheck = $stmtCheck->fetch()) {
            $lastCheck = $rowCheck['valor'];
        }

        $skipApi = false;
        if ($apiStatus === 'down' && $lastCheck) {
            $diff = time() - strtotime($lastCheck);
            if ($diff < 900) { // 15 minutos de cooldown
                $skipApi = true;
            }
        }

        if (!$skipApi) {
            // 2. API externa — campo 'promedio' del BCV oficial
            $ch = curl_init('https://ve.dolarapi.com/v1/dolares/oficial');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data    = json_decode($response, true);
                $newRate = isset($data['promedio']) ? (float)$data['promedio'] : null;

                if ($newRate && $newRate > 0) {
                    // Intentar persistir — si falla (p.ej. UNIQUE KEY), igual retornamos el valor
                    try {
                        $insert = $pdo->prepare(
                            "INSERT INTO tasas_cambio (tasa, fecha, origen) VALUES (?, ?, 'auto')"
                        );
                        $insert->execute([$newRate, $today]);
                    } catch (Exception $insertEx) {
                        error_log("Error guardando tasa en BD: " . $insertEx->getMessage());
                    }

                    // Actualizar estado de la API a 'up' y limpiar notificaciones
                    try {
                        $stmtConfig = $pdo->prepare("
                            INSERT INTO configuracion (clave, valor, descripcion) 
                            VALUES ('bcv_api_status', 'up', 'Estado de la API del BCV') 
                            ON DUPLICATE KEY UPDATE valor = 'up'
                        ");
                        $stmtConfig->execute();

                        $stmtCheck = $pdo->prepare("
                            INSERT INTO configuracion (clave, valor, descripcion) 
                            VALUES ('bcv_api_last_check', NOW(), 'Último intento de consulta a la API BCV') 
                            ON DUPLICATE KEY UPDATE valor = NOW()
                        ");
                        $stmtCheck->execute();

                        // Limpiar notificaciones de error previas
                        $stmtClean = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE tipo = 'bcv_api_error'");
                        $stmtClean->execute();
                    } catch (Exception $confEx) {
                        error_log("Error actualizando configuracion BCV: " . $confEx->getMessage());
                    }

                    $cached_rate = $newRate;
                    return $cached_rate;
                }
            }

            // Si la petición a la API falló, marcar como caída y generar alertas
            try {
                $stmtConfig = $pdo->prepare("
                    INSERT INTO configuracion (clave, valor, descripcion) 
                    VALUES ('bcv_api_status', 'down', 'Estado de la API del BCV') 
                    ON DUPLICATE KEY UPDATE valor = 'down'
                ");
                $stmtConfig->execute();

                $stmtCheck = $pdo->prepare("
                    INSERT INTO configuracion (clave, valor, descripcion) 
                    VALUES ('bcv_api_last_check', NOW(), 'Último intento de consulta a la API BCV') 
                    ON DUPLICATE KEY UPDATE valor = NOW()
                ");
                $stmtCheck->execute();

                // Obtener última tasa histórica para el mensaje de notificación
                $lastRateVal = 75.00;
                $lastRateDate = date('Y-m-d');
                $stmtLastTasa = $pdo->query("SELECT tasa, fecha FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
                if ($rowLastTasa = $stmtLastTasa->fetch()) {
                    $lastRateVal = (float)$rowLastTasa['tasa'];
                    $lastRateDate = $rowLastTasa['fecha'];
                }

                // Obtener todos los administradores
                $stmtAdmins = $pdo->query("SELECT id FROM usuarios WHERE rol = 'admin'");
                $admins = $stmtAdmins->fetchAll();

                $msg = "La API para consultar la tasa BCV no está disponible. Se usará la última tasa registrada de Bs. " . number_format($lastRateVal, 2) . " ($lastRateDate). Por favor, realice el registro manual para evitar pérdidas.";
                $link = "/modules/comprobantes/index.php";

                $stmtCheckNotif = $pdo->prepare("
                    SELECT COUNT(*) FROM notificaciones 
                    WHERE usuario_id = ? AND tipo = 'bcv_api_error' AND leido = 0
                ");

                $stmtInsertNotif = $pdo->prepare("
                    INSERT INTO notificaciones (usuario_id, tipo, mensaje, link, leido, fecha) 
                    VALUES (?, 'bcv_api_error', ?, ?, 0, NOW())
                ");

                foreach ($admins as $admin) {
                    $stmtCheckNotif->execute([$admin['id']]);
                    if ((int)$stmtCheckNotif->fetchColumn() === 0) {
                        $stmtInsertNotif->execute([$admin['id'], $msg, $link]);
                    }
                }
            } catch (Exception $confEx) {
                error_log("Error actualizando configuracion BCV (fallo): " . $confEx->getMessage());
            }
        }

        // 3. Última tasa conocida en BD (puede ser de días anteriores)
        $stmtLast = $pdo->query("SELECT tasa FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
        if ($rowLast = $stmtLast->fetch()) {
            $cached_rate = (float)$rowLast['tasa'];
            return $cached_rate;
        }

    } catch (Exception $e) {
        // No propagamos para no romper la página
    }

    return 75.00; // Fallback hardcoded de último recurso
}

// JSON response helper
function jsonResponse($success, $message, $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

// Helper para formatear dinero en USD y Bs usando la tasa actual
function formatMoneyBcv($amountUsd)
{
    return formatMoneyCustomRate($amountUsd, getBcvRate());
}

// Helper para formatear usando una tasa específica (para comprobantes históricos)
function formatMoneyCustomRate($amountUsd, $rate)
{
    if (!is_numeric($amountUsd)) return '$0.00 (Bs 0.00)';
    $amountUsd = (float)$amountUsd;
    $rate      = (float)$rate;
    if ($rate <= 0) $rate = getBcvRate();
    $bs = $amountUsd * $rate;
    return '$' . number_format($amountUsd, 2) . ' (Bs ' . number_format($bs, 2) . ')';
}

/**
 * calcularEquivalenciaBCV()
 *
 * Convierte Bolivares (VES) a su equivalente en Dolares (USD) usando
 * la tasa oficial BCV registrada en el sistema para ese dia.
 *
 * @param  float  $monto_bs  Monto en Bs pagado por el participante
 * @param  float  $tasa_bcv  Tasa BCV del dia (Bs por 1 USD). 0 = obtener automaticamente
 * @return array  ['usd' => float, 'bs' => float, 'tasa' => float]
 */
function calcularEquivalenciaBCV(float $monto_bs, float $tasa_bcv = 0): array
{
    if ($tasa_bcv <= 0) {
        $tasa_bcv = getBcvRate();
    }
    $equivalente_usd = $tasa_bcv > 0 ? round($monto_bs / $tasa_bcv, 4) : 0.0;
    return [
        'usd'  => $equivalente_usd,
        'bs'   => $monto_bs,
        'tasa' => $tasa_bcv,
    ];
}

/**
 * registrarTasaManual()
 *
 * Permite al administrador ingresar o corregir la tasa BCV del dia
 * manualmente, cuando la fuente automatica no esta disponible o difiere.
 *
 * @param  float  $tasa   Tasa Bs/USD a registrar
 * @param  string $fecha  Fecha Y-m-d (default: hoy)
 * @return bool
 */
function registrarTasaManual(float $tasa, string $fecha = ''): bool
{
    global $pdo;
    if ($fecha === '') $fecha = date('Y-m-d');
    $sql = "INSERT INTO tasas_cambio (tasa, fecha, origen) VALUES (?, ?, 'manual')"
         . " ON DUPLICATE KEY UPDATE tasa = VALUES(tasa), origen = 'manual'";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$tasa, $fecha]);
}
?>