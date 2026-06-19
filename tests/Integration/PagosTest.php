<?php
/**
 * tests/Integration/PagosTest.php
 * CI-06: POST reportar_pago con pago ya en pendiente_verificacion → success=false
 * CI-07: POST aprobar_pago con rol=participante → success=false, Acceso no autorizado
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class PagosTest extends TestCase
{
    private HttpTestClient $httpAdmin;
    private HttpTestClient $httpParticipante;

    public function __construct()
    {
        parent::__construct('Integration · Pagos');
        $this->httpAdmin = new HttpTestClient();
        $this->httpAdmin->login('admin', '1234');

        // Cliente separado para el participante (cookie jar distinto)
        $this->httpParticipante = new HttpTestClient();
    }

    /**
     * Crea un pago en estado 'pendiente_verificacion' directamente en BD
     * y retorna [pago_id, usuario_id] para poder loguearse como el dueño.
     */
    private function crearPagoEnVerificacion(): array
    {
        global $pdo;

        // Obtener cualquier participante activo
        $stmt = $pdo->query("SELECT p.id as participante_id, p.usuario_id FROM participantes p LIMIT 1");
        $part = $stmt->fetch();
        if (!$part) {
            throw new RuntimeException('Sin participantes en BD para CI-06');
        }

        $stmt = $pdo->prepare("
            INSERT INTO pagos (participante_id, numero_cuota, monto, fecha_vencimiento, estado)
            VALUES (?, 99, 100.00, ?, 'pendiente_verificacion')
        ");
        $stmt->execute([$part['participante_id'], date('Y-m-d', strtotime('+30 days'))]);
        $pagoId = (int) $pdo->lastInsertId();

        // Obtener el username del usuario para login
        $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ?");
        $stmt->execute([$part['usuario_id']]);
        $username = $stmt->fetchColumn();

        return ['pago_id' => $pagoId, 'username' => $username];
    }

    /** CI-06 */
    public function testReportarPagoYaReportado(): void
    {
        global $pdo;
        $data = $this->crearPagoEnVerificacion();
        $pagoId = $data['pago_id'];
        $username = $data['username'];

        // Login como el participante dueño del pago
        $client = new HttpTestClient();
        $client->login($username, $username);

        // Intentar reportar de nuevo un pago que ya está en verificación
        $r = $client->post('/api/pagos.php', [
            'action'  => 'reportar_pago',
            'pago_id' => $pagoId,
        ]);

        $this->assertJsonFailure($r, 'CI-06', 'reportar pago ya en pendiente_verificacion → success=false');
        $this->assertContains('verificaci', strtolower($r['message'] ?? ''), 'CI-06', 'mensaje menciona verificación');

        // Limpiar
        $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$pagoId]);
    }

    /**
     * CI-08: Flujo completo reporte → verificación → aprobación → recibo PDF.
     *
     * 1. Crear pago 'pendiente' vía PDO
     * 2. Login como participante → POST reportar_pago → success
     * 3. Login como admin → POST aprobar_pago → success
     * 4. GET recibo → assert Content-Type: application/pdf
     */
    public function testFullFlowReportarAprobarRecibo(): void
    {
        global $pdo;

        // ── 1. Crear pago pendiente para Juan ──
        $stmt = $pdo->prepare("
            INSERT INTO pagos (participante_id, numero_cuota, monto, fecha_vencimiento, estado)
            VALUES ((SELECT id FROM participantes WHERE usuario_id = (SELECT id FROM usuarios WHERE username = '12345678') LIMIT 1),
                    99, 100.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'pendiente')
        ");
        $stmt->execute();
        $pagoId = $pdo->lastInsertId();

        if (!$pagoId) {
            $this->fail('CI-08', 'No se pudo crear pago de prueba', 'id valido', 'null');
            return;
        }

        // ── 2. Login como participante y reportar pago ──
        $clientPart = new HttpTestClient();
        $loginPart = $clientPart->login('12345678', '12345678');
        if (!($loginPart['success'] ?? false)) {
            $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$pagoId]);
            $this->fail('CI-08', 'No se pudo autenticar como participante.', 'login success=true', 'login success=false');
            return;
        }

        $rpt = $clientPart->post('/api/pagos.php', [
            'action'          => 'reportar_pago',
            'pago_id'         => $pagoId,
            'referencia_pago' => 'FLOW-TEST-999',
            'monto_bs_pagado' => '5000.00',
            'notas'           => 'Test flujo completo',
        ]);

        $this->assertTrue($rpt['success'] ?? false, 'CI-08', 'Participante reporta pago → success=true');
        $this->assertContains('correctamente', strtolower($rpt['message'] ?? ''), 'CI-08', 'Mensaje: reportado correctamente');

        // ── 3. Login como admin y aprobar pago ──
        $clientAdmin = new HttpTestClient();
        $loginAdmin = $clientAdmin->login('admin', '1234');
        if (!($loginAdmin['success'] ?? false)) {
            $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$pagoId]);
            $this->fail('CI-08', 'No se pudo autenticar como admin.', 'login success=true', 'login success=false');
            return;
        }

        $apr = $clientAdmin->post('/api/pagos.php', [
            'action'  => 'aprobar_pago',
            'pago_id' => $pagoId,
            'metodo_pago' => 'Transferencia',
        ]);

        $this->assertTrue($apr['success'] ?? false, 'CI-08', 'Admin aprueba pago → success=true');

        // ── 4. GET recibo PDF y verificar Content-Type ──
        $url = TEST_SERVER_URL . '/api/comprobantes.php?action=recibo&id=' . $pagoId;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEFILE     => sys_get_temp_dir() . '/mysan_test_' . getmypid() . '.txt',
            CURLOPT_TIMEOUT        => 10,
        ]);
        // Login de admin para el recibo (admin puede ver cualquier recibo)
        $clientAdmin->post('/api/auth.php', ['action' => 'login', 'username' => 'admin', 'password' => '1234']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode, 'CI-08', 'Recibo PDF — HTTP 200 (obtenido: ' . $httpCode . ')');
        $this->assertContains('application/pdf', strtolower($contentType ?? ''), 'CI-08', 'Content-Type es application/pdf');

        // ── Limpiar ──
        $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$pagoId]);
    }

    /** CI-07 */
    public function testAprobarPagoSinPermiso(): void
    {
        global $pdo;
        $data = $this->crearPagoEnVerificacion();
        $pagoId = $data['pago_id'];

        // Buscar o crear un usuario participante de prueba
        $stmt  = $pdo->query("SELECT username FROM usuarios WHERE rol = 'participante' LIMIT 1");
        $uPart = $stmt->fetch();

        if ($uPart) {
            // Login como participante (contraseña = username = cedula)
            $cedula = $uPart['username'];
            $loginOk = $this->httpParticipante->login($cedula, $cedula);
        } else {
            // No hay participante: el cliente sin sesión tampoco puede aprobar
            // Usar cliente fresco sin cookie de login
            $loginOk = ['success' => false];
        }

        $r = $this->httpParticipante->post('/api/pagos.php', [
            'action'  => 'aprobar_pago',
            'pago_id' => $pagoId,
        ]);

        $this->assertJsonFailure($r, 'CI-07', 'aprobar_pago con rol=participante (o sin sesión) → success=false');
        $this->assertContains('autorizado', strtolower($r['message'] ?? ''), 'CI-07', 'mensaje: Acceso no autorizado');

        // Limpiar
        $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$pagoId]);
    }

    public function run(): array
    {
        $this->testReportarPagoYaReportado();
        $this->testAprobarPagoSinPermiso();
        $this->testFullFlowReportarAprobarRecibo();
        return $this->summary();
    }
}

$suite  = new PagosTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
