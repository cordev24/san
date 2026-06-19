<?php
/**
 * tests/bootstrap.php
 * Inicializa el entorno de pruebas:
 *   - Carga config/database.php del proyecto
 *   - Inyecta sesión PHP simulada (admin)
 *   - Provee helpers: withTransaction(), HttpTestClient
 */

define('PROJECT_ROOT', dirname(__DIR__));
define('TEST_SERVER_PORT', 8765);
define('TEST_SERVER_URL', 'http://localhost:' . TEST_SERVER_PORT);

// Cargar PDO + funciones del proyecto (getBcvRate, calcularEquivalenciaBCV, etc.)
require_once PROJECT_ROOT . '/config/database.php';

// Simular sesión autenticada como administrador
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id']  = 1;
$_SESSION['rol']      = 'admin';
$_SESSION['username'] = 'admin';

/**
 * Ejecuta un callable dentro de una transacción BD y hace ROLLBACK al finalizar.
 * Garantiza que ningún test de función directa ensucie la BD de producción.
 */
function withTransaction(callable $fn): void
{
    global $pdo;
    $pdo->beginTransaction();
    try {
        $fn();
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

/**
 * Cliente HTTP liviano para tests de integración y UAT.
 * Usa cURL con jar de cookies por proceso → simula una sesión de navegador real.
 */
class HttpTestClient
{
    private string $cookieFile;
    private string $baseUrl;

    public function __construct(string $baseUrl = TEST_SERVER_URL)
    {
        $this->baseUrl    = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . '/mysan_test_' . getmypid() . '.txt';
    }

    public function post(string $path, array $data = []): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw ?: '{}', true) ?? [];
    }

    public function get(string $path, array $params = []): array
    {
        $url = $this->baseUrl . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw ?: '{}', true) ?? [];
    }

    /** Atajo: login y retorna la respuesta */
    public function login(string $username, string $password): array
    {
        return $this->post('/api/auth.php', [
            'action'   => 'login',
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }
}
