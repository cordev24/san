<?php
/**
 * tests/Integration/TurnosTest.php
 * CI-08: Auto-asignación de turnos por orden de inscripción
 *
 * Prerrequisito: migración 003 aplicada (ALTER ENUM + backfill)
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class TurnosTest extends TestCase
{
    private HttpTestClient $http;

    public function __construct()
    {
        parent::__construct('Integration · Turnos');
        $this->http = new HttpTestClient();
        $this->http->login('admin', '1234');
    }

    /** Crea un grupo de prueba con cupo libre */
    private function crearGrupoPrueba(string $suffix = ''): int
    {
        global $pdo;
        $stmt = $pdo->query("SELECT id FROM productos LIMIT 1");
        $prod = $stmt->fetch();
        if (!$prod) {
            throw new RuntimeException('Sin productos en BD');
        }
        $tag = $suffix ?: strval(time());
        $stmt = $pdo->prepare("
            INSERT INTO grupos_san (producto_id, nombre, fecha_inicio, frecuencia, numero_cuotas,
                                    cupos_totales, cupos_ocupados, monto_cuota, estado)
            VALUES (?, 'TEST_CI08_Grupo_{$tag}', ?, 'mensual', 3, 10, 0, 100.00, 'abierto')
        ");
        $stmt->execute([$prod['id'], date('Y-m-d')]);
        return (int) $pdo->lastInsertId();
    }

    /** Inscribe un participante de prueba, retorna su id */
    private function inscribirParticipante(int $grupoId, string $cedula): array
    {
        $r = $this->http->post('/api/participantes.php', [
            'action'       => 'create',
            'grupo_san_id' => $grupoId,
            'nombre'       => 'Test',
            'apellido'     => 'Turnos',
            'cedula'       => $cedula,
            'telefono'     => '04120000001',
            'direccion'    => 'Test',
        ]);
        return $r;
    }

    /** Limpia participantes creados por cedula */
    private function limpiarParticipante(string $cedula): void
    {
        global $pdo;
        $pdo->prepare("DELETE FROM pagos WHERE participante_id IN (SELECT id FROM participantes WHERE cedula = ?)")->execute([$cedula]);
        $pdo->prepare("DELETE FROM turnos WHERE participante_id IN (SELECT id FROM participantes WHERE cedula = ?)")->execute([$cedula]);
        $pdo->prepare("DELETE FROM participantes WHERE cedula = ?")->execute([$cedula]);
        $pdo->prepare("DELETE FROM usuarios WHERE username = ?")->execute([$cedula]);
    }

    /** Genera cédula corta única */
    private function cedulaUnica(string $prefix): string
    {
        return $prefix . substr(uniqid(), -6);
    }

    /** CI-08a: Primer participante inscrito recibe turno #1 */
    public function testPrimerParticipanteTurno1(): void
    {
        global $pdo;
        $grupoId = $this->crearGrupoPrueba('a');
        $cedula  = $this->cedulaUnica('TA1');

        $r = $this->inscribirParticipante($grupoId, $cedula);
        $this->assertJsonSuccess($r, 'CI-08a', 'inscripción exitosa');

        // Verificar que se creó el turno #1
        $stmt = $pdo->prepare("SELECT numero_turno, metodo_asignacion FROM turnos t JOIN participantes p ON t.participante_id = p.id WHERE p.cedula = ?");
        $stmt->execute([$cedula]);
        $turno = $stmt->fetch();

        $this->assertTrue($turno !== false, 'CI-08a', 'turno existe para el participante');
        if ($turno) {
            $this->assertEquals(1, (int)$turno['numero_turno'], 'CI-08a', 'primer participante → turno #1');
            $this->assertEquals('orden_inscripcion', $turno['metodo_asignacion'], 'CI-08a', 'metodo_asignacion = orden_inscripcion');
        }

        // Limpiar
        $this->limpiarParticipante($cedula);
        $pdo->prepare("DELETE FROM grupos_san WHERE id = ?")->execute([$grupoId]);
    }

    /** CI-08b: Segundo participante recibe turno #2 */
    public function testSegundoParticipanteTurno2(): void
    {
        global $pdo;
        $grupoId = $this->crearGrupoPrueba('b');
        $cedula1 = $this->cedulaUnica('TB1');
        $cedula2 = $this->cedulaUnica('TB2');

        // Primer participante → turno #1
        $r1 = $this->inscribirParticipante($grupoId, $cedula1);
        $this->assertJsonSuccess($r1, 'CI-08b', 'primer inscrito ok');

        // Segundo participante → turno #2
        $r2 = $this->inscribirParticipante($grupoId, $cedula2);
        $this->assertJsonSuccess($r2, 'CI-08b', 'segundo inscrito ok');

        // Verificar turno #2
        $stmt = $pdo->prepare("SELECT numero_turno FROM turnos t JOIN participantes p ON t.participante_id = p.id WHERE p.cedula = ?");
        $stmt->execute([$cedula2]);
        $turno = $stmt->fetch();

        $this->assertTrue($turno !== false, 'CI-08b', 'turno existe para segundo participante');
        if ($turno) {
            $this->assertEquals(2, (int)$turno['numero_turno'], 'CI-08b', 'segundo participante → turno #2');
        }

        // Limpiar
        $this->limpiarParticipante($cedula1);
        $this->limpiarParticipante($cedula2);
        $pdo->prepare("DELETE FROM grupos_san WHERE id = ?")->execute([$grupoId]);
    }

    /** CI-08c: Eliminar participante → turno eliminado (no huérfano) */
    public function testDeleteParticipanteLimpiaTurno(): void
    {
        global $pdo;
        $grupoId = $this->crearGrupoPrueba('c');
        $cedula  = $this->cedulaUnica('TC1');

        // Inscribir
        $r = $this->inscribirParticipante($grupoId, $cedula);
        $this->assertJsonSuccess($r, 'CI-08c', 'inscripción exitosa');

        // Obtener id del participante
        $stmt = $pdo->prepare("SELECT id FROM participantes WHERE cedula = ?");
        $stmt->execute([$cedula]);
        $part = $stmt->fetch();
        $this->assertTrue($part !== false, 'CI-08c', 'participante existe');

        if (!$part) {
            $this->limpiarParticipante($cedula);
            $pdo->prepare("DELETE FROM grupos_san WHERE id = ?")->execute([$grupoId]);
            return;
        }

        $participanteId = (int)$part['id'];

        // Verificar turno existe antes de borrar
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM turnos WHERE participante_id = ?");
        $stmt->execute([$participanteId]);
        $this->assertEquals(1, (int)$stmt->fetch()['cnt'], 'CI-08c', 'turno existe antes de eliminar');

        // Eliminar via API
        $rDel = $this->http->post('/api/participantes.php', [
            'action' => 'delete',
            'id'     => $participanteId,
        ]);
        $this->assertJsonSuccess($rDel, 'CI-08c', 'eliminación exitosa');

        // Verificar turno NO existe
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM turnos WHERE participante_id = ?");
        $stmt->execute([$participanteId]);
        $this->assertEquals(0, (int)$stmt->fetch()['cnt'], 'CI-08c', 'turno eliminado después de borrar participante');

        // Limpiar
        $this->limpiarParticipante($cedula);
        $pdo->prepare("DELETE FROM grupos_san WHERE id = ?")->execute([$grupoId]);
    }

    public function run(): array
    {
        $this->testPrimerParticipanteTurno1();
        $this->testSegundoParticipanteTurno2();
        $this->testDeleteParticipanteLimpiaTurno();
        return $this->summary();
    }
}

$suite  = new TurnosTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
