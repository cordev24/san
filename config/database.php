<?php
// Database Configuration - Soporta variables de entorno para Docker
$dbHost = getenv('DB_HOST') ?: 'db';  // Default to 'db' for Docker
$dbName = getenv('DB_NAME') ?: 'mysan';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'rootpassword';
$dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', $dbCharset);

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
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

// Helper function to get current BCV rate
function getBcvRate()
{
    static $cached_rate = null;
    if ($cached_rate !== null) {
        return $cached_rate;
    }
    
    global $pdo;
    try {
        $today = date('Y-m-d');
        // Check if we have today's rate
        $stmt = $pdo->prepare("SELECT tasa FROM tasas_cambio WHERE fecha = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$today]);
        if ($row = $stmt->fetch()) {
            return (float)$row['tasa'];
        }
        
        // If not, try to fetch from API in real time (could delay slightly once a day)
        $ch = curl_init('https://ve.dolarapi.com/v1/dolares/oficial');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $newRate = null;
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['promedio'])) {
                $newRate = (float)$data['promedio'];
            }
        }
        
        if ($newRate) {
            $insert = $pdo->prepare("INSERT INTO tasas_cambio (tasa, fecha, origen) VALUES (?, ?, 'api_auto')");
            $insert->execute([$newRate, $today]);
            return $newRate;
        }
        
        // Fallback to last known rate
        $stmtLast = $pdo->query("SELECT tasa FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
        if ($rowLast = $stmtLast->fetch()) {
            return (float)$rowLast['tasa'];
        }
        
    } catch (Exception $e) {
        // Exception handling
    }
    return 75.00; // Hard fallback
}

// JSON response helper
function jsonResponse($success, $message, $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Helper para formatear dinero en USD y Bs
function formatMoneyBcv($amountUsd) {
    return formatMoneyCustomRate($amountUsd, getBcvRate());
}

// Helper para formatear usando una tasa específica (para comprobantes históricos)
function formatMoneyCustomRate($amountUsd, $rate) {
    if (!is_numeric($amountUsd)) return '$0.00 (Bs 0.00)';
    $amountUsd = (float)$amountUsd;
    $rate = (float)$rate;
    if ($rate <= 0) $rate = getBcvRate();
    $bs = $amountUsd * $rate;
    return '$' . number_format($amountUsd, 2) . ' (Bs ' . number_format($bs, 2) . ')';
}
?>