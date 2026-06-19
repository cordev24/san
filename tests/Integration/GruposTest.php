<?php
/**
 * tests/Integration/GruposTest.php
 * CI-03: POST create con campos completos → success=true, id del grupo
 * CI-04: POST create con campos incompletos → success=false, error validación
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class GruposTest extends TestCase
{
    private HttpTestClient $http;
    private int $grupoCreado = 0;

    public function __construct()
    {
        parent::__construct('Integration · Grupos');
        $this->http = new HttpTestClient();
        $this->http->login('admin', '1234');
    }

    /** Obtiene un producto existente o lanza excepción */
    private function getProductoId(): int
    {
        global $pdo;
        $stmt = $pdo->query("SELECT id FROM productos LIMIT 1");
        $row  = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('No hay productos en BD para CI-03. Ejecuta las migraciones primero.');
        }
        return (int) $row['id'];
    }

    /** CI-03 */
    public function testCreateGrupoValido(): void
    {
        $productoId = $this->getProductoId();

        $r = $this->http->post('/api/grupos.php', [
            'action'         => 'create',
            'producto_id'    => $productoId,
            'nombre'         => 'TEST_CI03_Grupo_' . time(),
            'fecha_inicio'   => date('Y-m-d'),
            'frecuencia'     => 'mensual',
            'numero_cuotas'  => 5,
            'cupos_totales'  => 5,
        ]);

        $this->assertJsonSuccess($r, 'CI-03', 'crear grupo con campos completos → success=true');
        $this->assertTrue(isset($r['data']['id']) && $r['data']['id'] > 0, 'CI-03', 'retorna id del grupo creado');

        // Guardar para CI-09
        if (!empty($r['data']['id'])) {
            $this->grupoCreado = (int) $r['data']['id'];
        }
    }

    /** CI-04 */
    public function testCreateGrupoSinCampos(): void
    {
        $r = $this->http->post('/api/grupos.php', [
            'action' => 'create',
            // Sin producto_id, nombre, etc.
        ]);
        $this->assertJsonFailure($r, 'CI-04', 'campos incompletos → success=false con error de validación');
    }

    public function run(): array
    {
        $this->testCreateGrupoValido();
        $this->testCreateGrupoSinCampos();

        // Limpiar grupo creado en CI-03
        if ($this->grupoCreado > 0) {
            global $pdo;
            $pdo->prepare("DELETE FROM grupos_san WHERE id = ?")->execute([$this->grupoCreado]);
        }

        return $this->summary();
    }
}

$suite  = new GruposTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
