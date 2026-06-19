<?php
/**
 * tests/TestCase.php
 * Mini framework de assertions para la suite de pruebas MySan.
 * No requiere dependencias externas (Composer / PHPUnit).
 */
class TestCase
{
    protected int    $passed    = 0;
    protected int    $failed    = 0;
    protected string $suiteName;

    public function __construct(string $suiteName)
    {
        $this->suiteName = $suiteName;
        echo "\n\033[1;34m[{$suiteName}]\033[0m\n";
    }

    // ── Assertions ────────────────────────────────────────────────────────────

    protected function assertEquals($expected, $actual, string $id, string $desc = ''): void
    {
        // Comparación no estricta para floats redondeados
        $ok = (is_float($expected) || is_float($actual))
            ? abs((float)$expected - (float)$actual) < 0.0001
            : $expected == $actual;

        $ok ? $this->pass($id, $desc)
            : $this->fail($id, $desc, var_export($expected, true), var_export($actual, true));
    }

    protected function assertStrictEquals($expected, $actual, string $id, string $desc = ''): void
    {
        $expected === $actual
            ? $this->pass($id, $desc)
            : $this->fail($id, $desc, var_export($expected, true), var_export($actual, true));
    }

    protected function assertTrue($value, string $id, string $desc = ''): void
    {
        $value ? $this->pass($id, $desc)
               : $this->fail($id, $desc, 'true', var_export($value, true));
    }

    protected function assertFalse($value, string $id, string $desc = ''): void
    {
        !$value ? $this->pass($id, $desc)
                : $this->fail($id, $desc, 'false', var_export($value, true));
    }

    protected function assertGreaterThan($min, $actual, string $id, string $desc = ''): void
    {
        $actual > $min
            ? $this->pass($id, $desc)
            : $this->fail($id, $desc, "> {$min}", (string)$actual);
    }

    protected function assertJsonSuccess(array $r, string $id, string $desc = ''): void
    {
        !empty($r['success'])
            ? $this->pass($id, $desc)
            : $this->fail($id, $desc, 'success=true', 'success=false — ' . ($r['message'] ?? ''));
    }

    protected function assertJsonFailure(array $r, string $id, string $desc = ''): void
    {
        isset($r['success']) && $r['success'] === false
            ? $this->pass($id, $desc)
            : $this->fail($id, $desc, 'success=false', 'success=true');
    }

    protected function assertContains(string $needle, string $haystack, string $id, string $desc = ''): void
    {
        str_contains($haystack, $needle)
            ? $this->pass($id, $desc)
            : $this->fail($id, $desc, "contiene '{$needle}'", "no encontrado");
    }

    protected function assertNotContains(string $needle, string $haystack, string $id, string $desc = ''): void
    {
        !str_contains($haystack, $needle)
            ? $this->pass($id, $desc)
            : $this->fail($id, $desc, "NO contiene '{$needle}'", "encontrado");
    }

    // ── Salida ────────────────────────────────────────────────────────────────

    private function pass(string $id, string $desc): void
    {
        $this->passed++;
        $label = $desc ? "{$id} — {$desc}" : $id;
        echo "  \033[32m✓\033[0m {$label}\n";
    }

    protected function fail(string $id, string $desc, string $expected, string $actual): void
    {
        $this->failed++;
        $label = $desc ? "{$id} — {$desc}" : $id;
        echo "  \033[31m✗\033[0m {$label}\n";
        echo "    \033[33m↳ Esperado: {$expected} | Obtenido: {$actual}\033[0m\n";
    }

    public function summary(): array
    {
        return [
            'suite'  => $this->suiteName,
            'passed' => $this->passed,
            'failed' => $this->failed,
        ];
    }
}
