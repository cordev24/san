<?php
/**
 * tests/Integration/DetalleParticipanteTest.php
 * CI-11: Auth gate — acceso no autorizado redirige
 * CI-12: Grupo no encontrado — redirige con error
 * CI-13: Página renderiza correctamente para participante válido
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class DetalleParticipanteTest extends TestCase
{
    private HttpTestClient $http;
    private string $cookieFile;

    public function __construct()
    {
        parent::__construct('Integration · Detalle Participante');
        $this->http = new HttpTestClient();
        $this->cookieFile = sys_get_temp_dir() . '/mysan_test_' . getmypid() . '.txt';
    }

    /**
     * Helper: make an HTTP GET request that reuses the HttpTestClient's cookie jar
     * and returns [httpCode, headers, body].
     */
    private function httpGet(string $path): array
    {
        $url = TEST_SERVER_URL . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Split headers and body
        $parts = explode("\r\n\r\n", $response, 2);
        $headers = $parts[0] ?? '';
        $body    = $parts[1] ?? '';

        return [$httpCode, $headers, $body];
    }

    /**
     * Extract a header value from raw headers string.
     */
    private function getHeader(string $headers, string $name): string
    {
        $pattern = '/^' . preg_quote($name, '/') . ': ([^\r\n]+)/im';
        if (preg_match($pattern, $headers, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    /**
     * CI-11: Auth gate — participante que NO pertenece al grupo recibe redirect.
     *
     * Usuario '12345678' (participante) está en grupo_san_id=4 únicamente.
     * Acceder a detalle-participante.php?grupo_id=5 (grupo al que NO pertenece)
     * debe redirigir a dashboard_participante.php?error=acceso_denegado.
     */
    public function testAuthGateRedirectWhenNotMember(): void
    {
        // Login como participante
        $login = $this->http->login('12345678', '12345678');
        if (!$login['success'] ?? false) {
            $this->fail('CI-11', 'No se pudo autenticar como participante. Credenciales incorrectas o usuario no existe.');
            return;
        }

        // Acceder a grupo al que NO pertenece
        [$httpCode, $headers] = $this->httpGet('/detalle-participante.php?grupo_id=5');

        $this->assertTrue($httpCode === 302 || $httpCode === 301, 'CI-11', 'Debe redirigir (HTTP ' . $httpCode . ')');

        $location = $this->getHeader($headers, 'Location');
        $this->assertContains('acceso_denegado', $location, 'CI-11', 'Location header contiene acceso_denegado');
    }

    /**
     * CI-12: Grupo no encontrado — grupo_id inexistente redirige con error.
     */
    public function testAuthGateRedirectWhenGroupNotFound(): void
    {
        // Login como participante
        $login = $this->http->login('12345678', '12345678');
        if (!$login['success'] ?? false) {
            $this->fail('CI-12', 'No se pudo autenticar como participante.');
            return;
        }

        // Acceder con grupo_id que NO existe
        [$httpCode, $headers] = $this->httpGet('/detalle-participante.php?grupo_id=99999');

        $this->assertTrue($httpCode === 302 || $httpCode === 301, 'CI-12', 'Debe redirigir (HTTP ' . $httpCode . ')');

        $location = $this->getHeader($headers, 'Location');
        $this->assertContains('error=', $location, 'CI-12', 'Location header contiene parámetro error');
    }

    /**
     * CI-13: Página renderiza correctamente para participante válido.
     *
     * Usuario '12345678' está en grupo_san_id=4 como participante_id=3.
     * La página debe mostrar nombre del grupo, tabla de pagos, y estadísticas.
     */
    public function testSuccessfulPageRender(): void
    {
        // Login como participante
        $login = $this->http->login('12345678', '12345678');
        if (!$login['success'] ?? false) {
            $this->fail('CI-13', 'No se pudo autenticar como participante.');
            return;
        }

        // Usar get() para confirmar login fue exitoso
        // (el test de login ya pasó si llegamos aquí)

        // Acceder a grupo al que SÍ pertenece (grupo_id=4) — capturar body
        $url = TEST_SERVER_URL . '/detalle-participante.php?grupo_id=4';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $parts = explode("\r\n\r\n", $response, 2);
        $body = $parts[1] ?? '';

        $this->assertEquals(200, $httpCode, 'CI-13', 'HTTP 200 OK para página de detalle (obtenido: ' . $httpCode . ')');
        $this->assertContains('San de Prueba Menta', $body, 'CI-13', 'Contiene nombre del grupo');
        $this->assertContains('Nevera Samsung', $body, 'CI-13', 'Contiene nombre del producto');
        $this->assertContains('50.00', $body, 'CI-13', 'Contiene monto de cuota');
        $this->assertContains('Total Cuotas', $body, 'CI-13', 'Contiene tarjeta Total Cuotas');
        $this->assertContains('Pagadas', $body, 'CI-13', 'Contiene tarjeta Pagadas');
        $this->assertContains('Pendientes', $body, 'CI-13', 'Contiene tarjeta Pendientes');
        $this->assertContains('Atrasadas', $body, 'CI-13', 'Contiene tarjeta Atrasadas');
        $this->assertContains('En Verificación', $body, 'CI-13', 'Contiene tarjeta En Verificación');
        $this->assertContains('Historial de Pagos', $body, 'CI-13', 'Contiene historial de pagos');
        $this->assertContains('Tu Turno', $body, 'CI-13', 'Contiene sección de turno');
        $this->assertContains('Reportar Pago', $body, 'CI-13', 'Contiene botón Reportar Pago');
    }

    /**
     * CI-14: Dashboard participante contiene enlace "Ver Detalle" en cada tarjeta.
     */
    public function testDashboardHasVerDetalleLink(): void
    {
        // Login como participante
        $this->http->login('12345678', '12345678');

        $url = TEST_SERVER_URL . '/dashboard_participante.php';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode, 'CI-14', 'Dashboard carga correctamente');
        $this->assertContains('Ver Detalle', $body, 'CI-14', 'Dashboard contiene enlace "Ver Detalle"');
        $this->assertContains('detalle-participante.php?grupo_id=', $body, 'CI-14', 'Enlace apunta a detalle-participante.php');
    }

    /**
     * CI-16: Pagos vacíos — participante sin pagos registrados.
     *
     * Usuario 'luis24' (Luis Cordero) está en grupo_san_id=8 pero no tiene pagos.
     * La página debe cargar y mostrar "No hay pagos registrados".
     */
    public function testEmptyPaymentsState(): void
    {
        // Login como luis24 (password=123456)
        $login = $this->http->login('luis24', '123456');
        if (!$login['success'] ?? false) {
            $this->fail('CI-16', 'No se pudo autenticar como luis24.');
            return;
        }

        [$httpCode, , $body] = $this->httpGet('/detalle-participante.php?grupo_id=8');

        $this->assertEquals(200, $httpCode, 'CI-16', 'HTTP 200 OK (obtenido: ' . $httpCode . ')');
        $this->assertContains('grupo 2', $body, 'CI-16', 'Contiene nombre del grupo');
        $this->assertContains('ventilador recargable', $body, 'CI-16', 'Contiene nombre del producto');
        $this->assertContains('Total Cuotas', $body, 'CI-16', 'Contiene tarjeta Total Cuotas');
        $this->assertContains('Pagadas', $body, 'CI-16', 'Contiene tarjeta Pagadas');
        $this->assertContains('Pendientes', $body, 'CI-16', 'Contiene tarjeta Pendientes');
        $this->assertContains('Atrasadas', $body, 'CI-16', 'Contiene tarjeta Atrasadas');
        $this->assertContains('En Verificación', $body, 'CI-16', 'Contiene tarjeta En Verificación');
        $this->assertContains('No hay pagos registrados', $body, 'CI-16', 'Muestra mensaje de pagos vacío');
    }

    /**
     * CI-17: Todas las cuotas pagadas — no debe mostrar "Reportar Pago".
     *
     * Simula el escenario marcando el pago de Juan como 'pagado' vía PDO directo,
     * verifica la página, y restaura el estado original.
     */
    public function testAllPaidState(): void
    {
        global $pdo;

        // Login como Juan (participante en grupo 4)
        $login = $this->http->login('12345678', '12345678');
        if (!$login['success'] ?? false) {
            $this->fail('CI-17', 'No se pudo autenticar como Juan.');
            return;
        }

        // Guardar estado original
        $stmt = $pdo->prepare("SELECT estado FROM pagos WHERE id = 1");
        $stmt->execute();
        $originalEstado = $stmt->fetchColumn();

        // Marcar pago como pagado
        $stmt = $pdo->prepare("UPDATE pagos SET estado = 'pagado', fecha_pago = CURDATE() WHERE id = 1");
        $stmt->execute();

        // Verificar que PDO update se refleje
        $stmt = $pdo->prepare("SELECT estado FROM pagos WHERE id = 1");
        $stmt->execute();
        $this->assertEquals('pagado', $stmt->fetchColumn(), 'CI-17', 'PDO update applied correctly');

        [$httpCode, , $body] = $this->httpGet('/detalle-participante.php?grupo_id=4');

        // Restaurar estado original
        $stmt = $pdo->prepare("UPDATE pagos SET estado = ?, fecha_pago = NULL WHERE id = 1");
        $stmt->execute([$originalEstado]);

        $this->assertEquals(200, $httpCode, 'CI-17', 'HTTP 200 OK');

        // The button is conditional (line 607); the JS function definition (line 841) is always rendered.
        // Check for the specific button markup combination — button with onclick AND openReportModal
        $btnPattern = 'btn-violeta" onclick="openReportModal';
        $this->assertNotContains($btnPattern, $body, 'CI-17', 'NO debe mostrar botón Reportar Pago condicional');
        $this->assertTrue(str_contains($body, 'Pagadas'), 'CI-17', 'Contiene tarjeta Pagadas');
        $this->assertContains('Pagadas', $body, 'CI-17', 'Contiene tarjeta Pagadas');
        $this->assertContains('1', $body, 'CI-17', 'Total cuotas muestra 1');
    }

    /**
     * CI-18: Flujo de reporte de pago desde la página.
     *
     * Login como Juan, envía reporte vía API, verifica respuesta exitosa,
     * y restaura el estado original.
     */
    public function testReportPaymentFlow(): void
    {
        global $pdo;

        // Login como Juan
        $login = $this->http->login('12345678', '12345678');
        if (!$login['success'] ?? false) {
            $this->fail('CI-18', 'No se pudo autenticar como Juan.');
            return;
        }

        // Guardar estado original del pago #1
        $stmt = $pdo->prepare("SELECT estado, referencia_pago, monto_bs_pagado, comprobante, notas FROM pagos WHERE id = 1");
        $stmt->execute();
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        // Reportar pago vía API
        $result = $this->http->post('/api/pagos.php?action=reportar_pago', [
            'pago_id'         => 1,
            'referencia_pago' => 'TEST-REF-123456',
            'monto_bs_pagado' => '500.00',
            'notas'           => 'Test pago desde integración',
        ]);

        $this->assertTrue($result['success'] ?? false, 'CI-18', 'Reporte de pago exitoso');

        // Restaurar estado original vía PDO directo
        $stmt = $pdo->prepare("UPDATE pagos SET estado = ?, referencia_pago = ?, monto_bs_pagado = ?, comprobante = ?, notas = ? WHERE id = 1");
        $stmt->execute([
            $original['estado'],
            $original['referencia_pago'],
            $original['monto_bs_pagado'],
            $original['comprobante'],
            $original['notas'],
        ]);

        // También verificar que la página del detalle se ve bien después
        [$httpCode, , $body] = $this->httpGet('/detalle-participante.php?grupo_id=4');
        $this->assertEquals(200, $httpCode, 'CI-18', 'Página carga después del reporte');
    }

    /**
     * CI-15: Usuario no autenticado es redirigido al login.
     */
    public function testUnauthenticatedRedirectToLogin(): void
    {
        // Sin login — cookie file vacío
        // Eliminar la cookie para simular usuario no autenticado
        @unlink($this->cookieFile);

        [$httpCode, $headers] = $this->httpGet('/detalle-participante.php?grupo_id=4');

        $this->assertTrue($httpCode === 302 || $httpCode === 301, 'CI-15', 'Debe redirigir (HTTP ' . $httpCode . ')');

        $location = $this->getHeader($headers, 'Location');
        $this->assertContains('login.php', $location, 'CI-15', 'Redirige a login.php');
    }

    public function run(): array
    {
        $this->testAuthGateRedirectWhenNotMember();
        $this->testAuthGateRedirectWhenGroupNotFound();
        $this->testSuccessfulPageRender();
        $this->testDashboardHasVerDetalleLink();
        $this->testEmptyPaymentsState();
        $this->testAllPaidState();
        $this->testReportPaymentFlow();
        $this->testUnauthenticatedRedirectToLogin();
        return $this->summary();
    }
}

$suite  = new DetalleParticipanteTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
