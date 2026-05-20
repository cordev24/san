<?php
/**
 * tests/run.php — Runner principal de la suite de pruebas MySan
 *
 * Uso:
 *   php tests/run.php           → ejecuta todos los tests
 *   php tests/run.php --unit    → solo Nivel 1 (unitarios)
 *   php tests/run.php --int     → solo Nivel 2 (integración)
 *   php tests/run.php --uat     → solo Nivel 3 (aceptación)
 *
 * Requiere: PHP CLI con extensiones pdo_mysql y curl.
 * El servidor PHP integrado (php -S) se levanta automáticamente en el puerto 8765.
 */

define('TESTS_DIR',    __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));
define('SERVER_PORT',  8765);
define('SERVER_URL',   'http://localhost:' . SERVER_PORT);
define('SERVER_PID_FILE', sys_get_temp_dir() . '/mysan_test_server.pid');

// ── Filtros de suite por argumento ────────────────────────────────────────────
$runUnit = true;
$runInt  = true;
$runUat  = true;
if (in_array('--unit', $argv ?? [])) { $runInt = false; $runUat = false; }
if (in_array('--int',  $argv ?? [])) { $runUnit = false; $runUat = false; }
if (in_array('--uat',  $argv ?? [])) { $runUnit = false; $runInt = false; }

// ── Cabecera ──────────────────────────────────────────────────────────────────
echo "\033[1;36m";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  MySan — Suite de Pruebas Automatizadas\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\033[0m";

// ── Iniciar servidor PHP integrado ───────────────────────────────────────────
$serverProcess = null;
$serverStarted = false;

function startServer()
{
    $cmd = sprintf(
        'php -S localhost:%d -t %s > %s 2>&1',
        SERVER_PORT,
        escapeshellarg(PROJECT_ROOT),
        escapeshellarg(sys_get_temp_dir() . '/mysan_server.log')
    );

    $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($proc)) {
        echo "\033[31mERROR: No se pudo iniciar el servidor PHP en el puerto " . SERVER_PORT . ".\033[0m\n";
        return null;
    }

    // Esperar a que el servidor esté listo
    $ready = false;
    for ($i = 0; $i < 20; $i++) {
        usleep(300000); // 300ms
        $ch = curl_init(SERVER_URL . '/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code > 0) { $ready = true; break; }
    }

    if ($ready) {
        echo "\033[32m✓ Servidor PHP integrado iniciado en " . SERVER_URL . "\033[0m\n";
    } else {
        echo "\033[33m⚠ Servidor iniciado pero sin confirmar respuesta (puede continuar).\033[0m\n";
    }

    return $proc;
}

function stopServer($proc): void
{
    if ($proc && is_resource($proc)) {
        proc_terminate($proc);
        proc_close($proc);
    }
    $logFile = sys_get_temp_dir() . '/mysan_server.log';
    if (file_exists($logFile)) @unlink($logFile);
}

// ── Ejecutar un archivo de test como subproceso ───────────────────────────────
function runTestFile(string $file, ?string $arg = null): array
{
    $cmd = 'php ' . escapeshellarg($file);
    if ($arg) $cmd .= ' ' . escapeshellarg($arg);

    ob_start();
    passthru($cmd . ' 2>&1', $exitCode);
    $output = ob_get_clean();

    echo $output;
    return ['exit' => $exitCode];
}

// ── Totales globales ──────────────────────────────────────────────────────────
$totalPassed = 0;
$totalFailed = 0;
$suites      = [];

// ─────────────────────────────────────────────────────────────────────────────
// NIVEL 1 — Pruebas Unitarias (se ejecutan sin servidor HTTP)
// ─────────────────────────────────────────────────────────────────────────────
if ($runUnit) {
    echo "\n\033[1;35m╔══ NIVEL 1 — Pruebas Unitarias ══╗\033[0m\n";

    // CU-01 y CU-02 pueden ir en un proceso (no tocan caché estática de getBcvRate)
    // CU-05, CU-06, CU-07 necesitan procesos separados (caché estática fresca)
    $unitFile = TESTS_DIR . '/Unit/FinancialModuleTest.php';

    // Agrupar: CU-01..04 juntos, CU-05..07 separados
    $groups = [
        [null,   'CU-01 a CU-04 (sin caché estática)'],
        ['CU-05', 'CU-05 (getBcvRate con API disponible)'],
        ['CU-06', 'CU-06 (getBcvRate fallback BD)'],
        ['CU-07', 'CU-07 (getBcvRate fallback 75.00)'],
    ];

    foreach ($groups as [$arg, $label]) {
        $res = runTestFile($unitFile, $arg);
        if ($res['exit'] === 0) $totalPassed += ($arg ? 1 : 4);
        else                    $totalFailed += ($arg ? 1 : 4);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// NIVEL 2 — Pruebas de Integración (requieren servidor HTTP)
// ─────────────────────────────────────────────────────────────────────────────
if ($runInt || $runUat) {
    echo "\n\033[1;35m╔══ Iniciando servidor HTTP para tests de integración/UAT ══╗\033[0m\n";
    $serverProcess = startServer();
}

if ($runInt) {
    echo "\n\033[1;35m╔══ NIVEL 2 — Pruebas de Integración ══╗\033[0m\n";

    $integrationSuites = [
        TESTS_DIR . '/Integration/AuthTest.php'          => 'CI-01, CI-02',
        TESTS_DIR . '/Integration/GruposTest.php'        => 'CI-03, CI-04, CI-09',
        TESTS_DIR . '/Integration/ParticipantesTest.php' => 'CI-05',
        TESTS_DIR . '/Integration/PagosTest.php'         => 'CI-06, CI-07',
        TESTS_DIR . '/Integration/TurnosTest.php'        => 'CI-08',
        TESTS_DIR . '/Integration/ComprobantesTest.php'  => 'CI-10',
        TESTS_DIR . '/Integration/DetalleParticipanteTest.php' => 'CI-11, CI-12, CI-13, CI-14, CI-15',
    ];

    foreach ($integrationSuites as $file => $label) {
        $res = runTestFile($file);
        // Contar según número de tests en cada suite
        $numTests = substr_count($label, ',') + 1;
        if ($res['exit'] === 0) $totalPassed += $numTests;
        else                    $totalFailed += $numTests;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// NIVEL 3 — Pruebas de Aceptación
// ─────────────────────────────────────────────────────────────────────────────
if ($runUat) {
    echo "\n\033[1;35m╔══ NIVEL 3 — Pruebas de Aceptación (UAT) ══╗\033[0m\n";
    $res = runTestFile(TESTS_DIR . '/Acceptance/UserAcceptanceTest.php');
    if ($res['exit'] === 0) $totalPassed += 8;
    else                    $totalFailed += 8;
}

// ── Detener servidor ──────────────────────────────────────────────────────────
if ($serverProcess) {
    stopServer($serverProcess);
    echo "\n\033[90m✓ Servidor PHP integrado detenido.\033[0m\n";
}

// ── Resumen global ────────────────────────────────────────────────────────────
$total   = $totalPassed + $totalFailed;
$allPass = $totalFailed === 0;
$color   = $allPass ? "\033[1;32m" : "\033[1;31m";
$icon    = $allPass ? "✓" : "✗";

echo "\n\033[1;36m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
echo "{$color}  {$icon} Resultados Finales\033[0m\n";
echo "  Total ejecutados : {$total}\n";
echo "  \033[32mPASAN\033[0m : {$totalPassed}\n";
if ($totalFailed > 0) {
    echo "  \033[31mFALLAN\033[0m: {$totalFailed}\n";
}
echo "\033[1;36m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n\n";

exit($totalFailed > 0 ? 1 : 0);
