<?php
require_once '../config/database.php';
require_once '../fpdf/fpdf.php';
requireLogin();

$action = $_GET['action'] ?? '';

class MySanPDF extends FPDF
{
    protected $colors = [
        'violeta' => [132, 94, 247],
        'menta' => [56, 217, 169],
        'salmon' => [255, 128, 128],
        'dark' => [33, 37, 41],
        'gray' => [173, 181, 189],
        'light' => [248, 249, 250]
    ];

    function Header()
    {
        // Decorative Bar
        $this->SetFillColor($this->colors['violeta'][0], $this->colors['violeta'][1], $this->colors['violeta'][2]);
        $this->Rect(0, 0, 210, 15, 'F');

        // Logo / Title
        $this->SetY(20);
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor($this->colors['violeta'][0], $this->colors['violeta'][1], $this->colors['violeta'][2]);
        $this->Cell(0, 10, 'MySan', 0, 1, 'L');

        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor($this->colors['gray'][0], $this->colors['gray'][1], $this->colors['gray'][2]);
        $this->Cell(0, 5, 'Sistema de Administracion de Ahorros Grupales', 0, 1, 'L');

        $this->Ln(10);
        $this->SetDrawColor($this->colors['violeta'][0], $this->colors['violeta'][1], $this->colors['violeta'][2]);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor($this->colors['gray'][0], $this->colors['gray'][1], $this->colors['gray'][2]);
        $this->Cell(0, 10, 'Este documento es un comprobante oficial generado por MySan. Validado electronicamente.', 0, 1, 'C');

        $this->SetY(-15);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }

    function LabelValue($label, $value, $ln = 1)
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $this->Cell(50, 10, utf8_decode($label), 0, 0);

        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 10, utf8_decode($value), 0, $ln);
    }

    function SectionTitle($title)
    {
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->colors['light'][0], $this->colors['light'][1], $this->colors['light'][2]);
        $this->SetTextColor($this->colors['violeta'][0], $this->colors['violeta'][1], $this->colors['violeta'][2]);
        $this->Cell(0, 10, '  ' . utf8_decode($title), 0, 1, 'L', true);
        $this->Ln(3);
    }
}

try {
    switch ($action) {
        case 'recibo':
            generarRecibo();
            break;
        case 'entrega':
            generarCertificadoEntrega();
            break;
        case 'reporte_financiero':
            generarReporteFinanciero();
            break;
        default:
            die('Accion no valida');
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

function generarRecibo()
{
    global $pdo;
    $pago_id = $_GET['id'] ?? null;
    if (!$pago_id)
        die('ID de pago requerido');

    $stmt = $pdo->prepare("SELECT p.*, part.nombre, part.apellido, part.cedula, g.nombre as grupo_nombre 
                            FROM pagos p 
                            JOIN participantes part ON p.participante_id = part.id 
                            JOIN grupos_san g ON part.grupo_san_id = g.id 
                            WHERE p.id = ?");
    $stmt->execute([$pago_id]);
    $pago = $stmt->fetch();

    if (!$pago)
        die('Pago no encontrado');

    $pdf = new MySanPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Receipt Badge
    $pdf->SetFillColor(56, 217, 169); // Menta
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 10, utf8_decode('RECIBO DE PAGO DIGITAL'), 0, 1, 'C', true);

    $pdf->Ln(5);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Comprobante #' . str_pad($pago['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'R');
    $pdf->Ln(5);

    $pdf->SectionTitle('DETALLES DEL PARTICIPANTE');
    $pdf->LabelValue('Nombre:', $pago['nombre'] . ' ' . $pago['apellido']);
    $pdf->LabelValue('Cedula ID:', $pago['cedula']);
    $pdf->LabelValue('Grupo San:', $pago['grupo_nombre']);

    $pdf->SectionTitle('DETALLES DE LA TRANSACCION');
    $pdf->LabelValue('Concepto:', 'Pago de Cuota #' . $pago['numero_cuota']);
    $pdf->LabelValue('Metodo de Pago:', $pago['metodo_pago'] ?? 'N/D');
    $pdf->LabelValue('Fecha de Proceso:', $pago['fecha_pago'] ?? date('Y-m-d'));

    // Amount Box
    $pdf->Ln(10);
    $pdf->SetFillColor(132, 94, 247); // Violeta
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(100, 15, ' TOTAL PAGADO:', 0, 0, 'L', true);
    $pdf->Cell(0, 15, formatMoneyCustomRate($pago['monto'], $pago['tasa_aplicada'] ?? 0) . ' ', 0, 1, 'R', true);

    $pdf->Ln(30);
    // Signature
    $pdf->SetTextColor(33, 37, 41);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Electronizado por MySan Admin', 0, 1, 'C');
    $pdf->Cell(0, 5, date('d/m/Y H:i:s'), 0, 1, 'C');

    $pdf->Output('I', 'Recibo_' . $pago['id'] . '.pdf');
}

function generarCertificadoEntrega()
{
    global $pdo;
    $part_id = $_GET['id'] ?? null;
    if (!$part_id)
        die('ID de participante requerido');

    $stmt = $pdo->prepare("SELECT part.*, g.nombre as grupo_nombre, prod.nombre as producto_nombre, prod.marca, prod.modelo 
                            FROM participantes part 
                            JOIN grupos_san g ON part.grupo_san_id = g.id 
                            JOIN productos prod ON g.producto_id = prod.id 
                            WHERE part.id = ?");
    $stmt->execute([$part_id]);
    $part = $stmt->fetch();

    if (!$part)
        die('Participante no encontrado');

    $pdf = new MySanPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Title
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(132, 94, 247);
    $pdf->Cell(0, 20, 'ACTA DE ENTREGA FORMAL', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(33, 37, 41);

    $cuerpo = "Por medio del presente documento, MySan certifica que el beneficiario " .
        $part['nombre'] . " " . $part['apellido'] . ", identificado con la cedula " .
        $part['cedula'] . ", ha completado satisfactoriamente los requisitos del grupo de ahorro [" .
        $part['grupo_nombre'] . "] y recibe en este acto el producto detallado a continuacion:";

    $pdf->MultiCell(0, 8, utf8_decode($cuerpo), 0, 'J');
    $pdf->Ln(10);

    $pdf->SectionTitle('ESPECIFICACIONES DEL PRODUCTO');
    $pdf->LabelValue('Articulo:', $part['producto_nombre']);
    $pdf->LabelValue('Marca / Modelo:', $part['marca'] . ' ' . $part['modelo']);
    $pdf->LabelValue('Estado:', 'Nuevo / Operativo');
    $pdf->LabelValue('Fecha de Entrega:', $part['fecha_entrega'] ?? date('Y-m-d'));

    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->MultiCell(0, 6, utf8_decode("El beneficiario declara recibir el producto a su entera satisfaccion y conforme a las especificaciones acordadas al inicio del plan de ahorro."), 0, 'C');

    $pdf->Ln(40);
    // Signatures
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(90, 8, '__________________________', 0, 0, 'C');
    $pdf->Cell(90, 8, '__________________________', 0, 1, 'C');
    $pdf->Cell(90, 5, 'Por MySan (Sello y Firma)', 0, 0, 'C');
    $pdf->Cell(90, 5, 'El Beneficiario', 0, 1, 'C');

    $pdf->Output('I', 'Entrega_' . $part['id'] . '.pdf');
}

function generarReporteFinanciero()
{
    global $pdo;

    $stmt = $pdo->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'pagado'");
    $ingresos = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(prod.valor_total) as total 
                        FROM participantes part 
                        JOIN grupos_san g ON part.grupo_san_id = g.id 
                        JOIN productos prod ON g.producto_id = prod.id 
                        WHERE part.ha_recibido = 1");
    $egresos = $stmt->fetch()['total'] ?? 0;

    $ganancias = $ingresos - $egresos;

    $pdf = new MySanPDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->Cell(0, 15, 'ESTADO FINANCIERO CONSOLIDADO', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 5, 'Corte al: ' . date('d/m/Y H:i'), 0, 1, 'C');
    $pdf->Ln(15);

    $pdf->SectionTitle('RESUMEN DE OPERACIONES');

    // Table Header
    $pdf->SetFillColor(132, 94, 247);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(130, 12, ' Descripcion del Concepto', 1, 0, 'L', true);
    $pdf->Cell(60, 12, 'Monto ', 1, 1, 'R', true);

    // Table Content
    $pdf->SetTextColor(33, 37, 41);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetFillColor(255, 255, 255);

    $pdf->Cell(130, 10, ' Ingresos por Cuotas Consolidadas', 1, 0, 'L');
    $pdf->Cell(60, 10, number_format($ingresos, 2) . ' ', 1, 1, 'R');

    $pdf->Cell(130, 10, ' Egresos por Adquisicion de Productos', 1, 0, 'L');
    $pdf->Cell(60, 10, number_format($egresos, 2) . ' ', 1, 1, 'R');

    // Total Row
    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetFillColor(56, 217, 169);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(130, 15, ' UTILIDAD NETA DEL PERIODO', 0, 0, 'L', true);
    $pdf->Cell(60, 15, formatMoneyBcv($ganancias) . ' ', 0, 1, 'R', true);

    $pdf->Ln(20);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->MultiCell(0, 5, utf8_decode("Este reporte es confidencial y para uso exclusivo de la administracion de MySan. La informacion aqui contenida esta sujeta a auditoria."), 0, 'C');

    $pdf->Output('I', 'Reporte_Financiero.pdf');
}
