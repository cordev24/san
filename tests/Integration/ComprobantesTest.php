<?php
/**
 * tests/Integration/ComprobantesTest.php
 * CI-10: GET recibo con pago_id inexistente → error "Pago no encontrado"
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class ComprobantesTest extends TestCase
{
    private HttpTestClient $http;
    private string $cookieFile;

    public function __construct()
    {
        parent::__construct('Integration · Comprobantes');
        $this->http = new HttpTestClient();
        $this->http->login('admin', '1234');
        $this->cookieFile = sys_get_temp_dir() . '/mysan_test_' . getmypid() . '.txt';
    }

    /**
     * Helper: make an HTTP GET request that returns [httpCode, headers, body].
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

        $parts = explode("\r\n\r\n", $response, 2);
        $headers = $parts[0] ?? '';
        $body    = $parts[1] ?? '';

        return [$httpCode, $headers, $body];
    }

    /** CI-10 */
    public function testReciboIdInexistente(): void
    {
        // ID 999999 es prácticamente imposible que exista
        $r = $this->http->get('/api/comprobantes.php', [
            'action'  => 'get_recibo',
            'pago_id' => 999999,
        ]);

        $this->assertJsonFailure($r, 'CI-10', 'GET recibo con pago_id inexistente → success=false');
        $this->assertContains('encontrad', strtolower($r['message'] ?? ''), 'CI-10', 'mensaje: Pago no encontrado');
    }

    /**
     * CI-11: Ownership gate — participante NO puede ver recibo de OTRO participante.
     *
     * Login como Juan (12345678), busca un pago que pertenezca a otro usuario,
     * intenta GET del recibo → debe responder HTTP 403 "Acceso denegado".
     */
    public function testOwnershipGateBlocksOtherParticipant(): void
    {
        global $pdo;

        // Login como Juan (participante)
        $login = $this->http->login('12345678', '12345678');
        if (!($login['success'] ?? false)) {
            $this->fail('CI-11', 'No se pudo autenticar como participante 12345678.', 'login success=true', 'login success=false');
            return;
        }

        // Copiar cookie al helper local para httpGet
        $localCookie = sys_get_temp_dir() . '/mysan_test_own_' . getmypid() . '.txt';
        $srcCookie = sys_get_temp_dir() . '/mysan_test_' . getmypid() . '.txt';
        if (file_exists($srcCookie)) {
            copy($srcCookie, $localCookie);
        }
        $this->cookieFile = $localCookie;

        // Encontrar un pago pagado que pertenezca a OTRO usuario
        $stmt = $pdo->prepare("
            SELECT pg.id FROM pagos pg
            JOIN participantes p ON pg.participante_id = p.id
            WHERE p.usuario_id != (SELECT id FROM usuarios WHERE username = '12345678')
              AND pg.estado = 'pagado'
            LIMIT 1
        ");
        $stmt->execute();
        $otherPaymentId = $stmt->fetchColumn();

        $created = false;
        if (!$otherPaymentId) {
            // Crear un pago pagado para otro participante
            $stmt = $pdo->prepare("
                SELECT p.id FROM participantes p
                WHERE p.usuario_id != (SELECT id FROM usuarios WHERE username = '12345678')
                LIMIT 1
            ");
            $stmt->execute();
            $otherPartId = $stmt->fetchColumn();

            if (!$otherPartId) {
                $this->fail('CI-11', 'No hay otro participante en BD para probar ownership gate.', 'participante id', 'null');
                @unlink($localCookie);
                return;
            }

            $stmt = $pdo->prepare("
                INSERT INTO pagos (participante_id, numero_cuota, monto, fecha_vencimiento, estado, fecha_pago)
                VALUES (?, 1, 100.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'pagado', CURDATE())
            ");
            $stmt->execute([$otherPartId]);
            $otherPaymentId = $pdo->lastInsertId();
            $created = true;
        }

        // Intentar obtener el recibo de ese pago (debe fallar con 403)
        [$httpCode, , $body] = $this->httpGet('/api/comprobantes.php?action=recibo&id=' . $otherPaymentId);

        $this->assertEquals(403, $httpCode, 'CI-11', 'HTTP 403 — Acceso denegado al recibo de otro participante (obtenido: ' . $httpCode . ')');
        $this->assertContains('Acceso denegado', $body, 'CI-11', 'Cuerpo contiene "Acceso denegado"');

        // Limpiar si creamos el pago
        if ($created) {
            $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$otherPaymentId]);
        }

        @unlink($localCookie);
    }

    public function run(): array
    {
        $this->testReciboIdInexistente();
        $this->testOwnershipGateBlocksOtherParticipant();
        return $this->summary();
    }
}

$suite  = new ComprobantesTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
