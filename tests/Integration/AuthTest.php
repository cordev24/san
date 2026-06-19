<?php
/**
 * tests/Integration/AuthTest.php
 * CI-01: Login con credenciales válidas → success=true, rol=admin
 * CI-02: Login con password incorrecto  → success=false
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/TestCase.php';

class AuthTest extends TestCase
{
    private HttpTestClient $http;

    public function __construct()
    {
        parent::__construct('Integration · Auth');
        $this->http = new HttpTestClient();
    }

    /** CI-01 */
    public function testLoginValido(): void
    {
        $r = $this->http->login('admin', '1234');
        $this->assertJsonSuccess($r, 'CI-01', 'login con credenciales válidas retorna success=true');
        $this->assertEquals('admin', $r['data']['rol'] ?? '', 'CI-01', 'rol retornado = admin');
    }

    /** CI-02 */
    public function testLoginInvalido(): void
    {
        $r = $this->http->login('admin', 'password_incorrecto_xyz');
        $this->assertJsonFailure($r, 'CI-02', 'login con password incorrecto retorna success=false');
    }

    public function run(): array
    {
        $this->testLoginValido();
        $this->testLoginInvalido();
        return $this->summary();
    }
}

$suite  = new AuthTest();
$result = $suite->run();
$status = $result['failed'] === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m";
echo "  {$status} ({$result['passed']} pasan / {$result['failed']} fallan)\n";
exit($result['failed'] > 0 ? 1 : 0);
