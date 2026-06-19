<?php
/**
 * tests/Integration/ParticipantesTest.php
 * CI-05: POST create con cédula duplicada → success=false, "cédula ya registrada"
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class ParticipantesTest extends TestCase
{
    private HttpTestClient $http;

    public function __construct()
    {
        parent::__construct('Integration · Participantes');
        $this->http = new HttpTestClient();
        $this->http->login('admin', '1234');
    }

    /** Crea un grupo de prueba con cupo libre y retorna su id */
    private function crearGrupoPrueba(): int
    {
        global $pdo;
        $stmt = $pdo->query("SELECT id FROM productos LIMIT 1");
        $prod = $stmt->fetch();
        if (!$prod) {
            throw new RuntimeException('Sin productos en BD');
        }
        $stmt = $pdo->prepare("
            INSERT INTO grupos_san (producto_id, nombre, fecha_inicio, frecuencia, numero_cuotas,
                                    cupos_totales, cupos_ocupados, monto_cuota, estado)
            VALUES (?, 'TEST_CI05_Grupo', ?, 'mensual', 5, 5, 0, 50.00, 'abierto')
        ");
        $stmt->execute([$prod['id'], date('Y-m-d')]);
        return (int) $pdo->lastInsertId();
    }

    /** CI-05 */
    public function testCedulaDuplicada(): void
    {
        global $pdo;
        $grupoId    = $this->crearGrupoPrueba();
        $cedulaTest = 'V99887766_TEST';

        // Limpiar cédula de prueba anterior si quedó
        $pdo->prepare("DELETE FROM participantes WHERE cedula = ?")->execute([$cedulaTest]);
        $pdo->prepare("DELETE FROM usuarios WHERE username = ?")->execute([$cedulaTest]);

        $datos = [
            'action'      => 'create',
            'grupo_san_id'=> $grupoId,
            'nombre'      => 'Juan',
            'apellido'    => 'Perez',
            'cedula'      => $cedulaTest,
            'telefono'    => '04120000001',
            'direccion'   => 'Calle Test 1',
        ];

        // Primer registro → debe pasar
        $r1 = $this->http->post('/api/participantes.php', $datos);
        $this->assertJsonSuccess($r1, 'CI-05', 'primera inscripción con cédula nueva → success=true');

        // Segundo registro con la misma cédula → debe fallar
        $r2 = $this->http->post('/api/participantes.php', $datos);
        $this->assertJsonFailure($r2, 'CI-05', 'cédula duplicada en mismo grupo → success=false');
        $this->assertContains('cédula', strtolower($r2['message'] ?? ''), 'CI-05', 'mensaje menciona cédula');

        // Limpiar
        $pdo->prepare("DELETE FROM participantes WHERE cedula = ?")->execute([$cedulaTest]);
        $pdo->prepare("DELETE FROM pagos WHERE participante_id NOT IN (SELECT id FROM participantes)")->execute();
        $pdo->prepare("DELETE FROM usuarios WHERE username = ?")->execute([$cedulaTest]);
        $pdo->prepare("DELETE FROM grupos_san WHERE id = ?")->execute([$grupoId]);
    }

    public function run(): array
    {
        $this->testCedulaDuplicada();
        return $this->summary();
    }
}

$suite  = new ParticipantesTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
