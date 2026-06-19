<!DOCTYPE html>
<html lang="es">
<head>
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MySan — Términos y Condiciones</title>

    <link rel="stylesheet" href="assets/fonts/inter.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/bento-grid.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <style>
        body {
            background-color: var(--color-background);
            color: var(--color-text-primary);
        }
        .tos-container {
            max-width: 800px;
            margin: 0 auto;
            padding: var(--space-8);
        }
        .page-header {
            margin-bottom: var(--space-6);
            padding-bottom: var(--space-4);
            border-bottom: 1px solid var(--glass-border);
        }
        .page-title {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--space-2);
        }
        .page-subtitle {
            color: var(--color-text-secondary);
            font-size: var(--font-size-md);
        }
        .tos-section {
            margin-bottom: var(--space-6);
        }
        .tos-section h3 {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-bold);
            color: var(--color-violeta);
            margin-bottom: var(--space-2);
        }
        .tos-section p, .tos-section ul {
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
            line-height: 1.6;
        }
        .tos-section ul {
            padding-left: var(--space-4);
            margin-top: var(--space-2);
            list-style-type: disc;
        }
        .tos-section li {
            margin-bottom: var(--space-1);
        }
        @media (max-width: 600px) {
            .tos-container {
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <?php include 'assets/icons/feather-sprite.svg'; ?>

    <div class="tos-container">
        <div style="margin-bottom: var(--space-6);">
            <a href="index.php" class="btn btn-outline" style="display: inline-flex;">
                <svg class="icon"><use href="#icon-arrow-left"></use></svg>
                Volver
            </a>
        </div>

        <div class="bento-box">
            <div class="bento-content" style="padding: var(--space-8);">
                <div class="page-header">
                    <h1 class="page-title">Términos y Condiciones de Servicio</h1>
                    <p class="page-subtitle">Última actualización: <?php echo date('d/m/Y'); ?></p>
                </div>

                <div class="tos-section">
                    <h3>1. Naturaleza del Servicio</h3>
                    <p>MySan es una plataforma digital diseñada exclusivamente para la administración, organización y seguimiento de grupos de ahorro colaborativo (conocidos popularmente como "San", "Susu" o "Cuchubal"). El Sistema no es una entidad bancaria, financiera, de inversión ni de captación de fondos públicos. Actúa únicamente como una herramienta tecnológica para facilitar el control de los aportes realizados por los participantes.</p>
                </div>

                <div class="tos-section">
                    <h3>2. Compromiso de Participación</h3>
                    <p>Al unirse a un grupo de ahorro (San), el participante asume un compromiso moral y económico vinculante con el resto de los miembros. El participante se compromete a:</p>
                    <ul>
                        <li>Pagar el monto de la cuota acordada dentro de los plazos establecidos.</li>
                        <li>Reportar los pagos oportunamente al Administrador del grupo.</li>
                    </ul>
                </div>

                <div class="tos-section">
                    <h3>3. Penalizaciones y Atrasos</h3>
                    <p>El incumplimiento puntual de los pagos afecta directamente a los demás participantes. En caso de atraso:</p>
                    <ul>
                        <li>El Administrador se reserva el derecho de aplicar penalizaciones previas acordadas en la formación del grupo.</li>
                        <li>Si el participante se atrasa repetidamente sin justificación, el Administrador podrá suspender su turno o expulsarlo del grupo. En caso de expulsión, el reintegro de cuotas previas (si aplica) quedará a discreción de las reglas internas de cada Administrador.</li>
                    </ul>
                </div>

                <div class="tos-section">
                    <h3>4. Responsabilidad del Administrador</h3>
                    <p>El Administrador del grupo es el único responsable de la recolección, resguardo y entrega de los fondos o bienes materiales (ej. electrodomésticos, motocicletas) a los ganadores de cada turno. La plataforma (MySan) no se hace responsable por pérdidas, fraudes, malversación o conflictos financieros entre los Administradores y los Participantes.</p>
                </div>

                <div class="tos-section">
                    <h3>5. Veracidad de la Información</h3>
                    <p>El usuario declara que todos los datos proporcionados durante el registro (Cédula de Identidad, Nombre, Dirección, Teléfono) son veraces, exactos y le pertenecen. El uso de identidades falsas resultará en la eliminación inmediata de la cuenta y la posible toma de acciones legales.</p>
                </div>

                <div class="tos-section">
                    <h3>6. Cierre de Grupos y Cambios de Tasa</h3>
                    <p>Para grupos que operen bajo monedas fluctuantes, el Administrador calculará la equivalencia al momento del pago utilizando la tasa de referencia indicada en la plataforma (ej. Tasa BCV). Una vez asignado y entregado un turno, el participante está en la obligación irrenunciable de continuar pagando sus cuotas restantes hasta la finalización del ciclo del grupo.</p>
                </div>

                <div class="tos-section">
                    <h3>7. Aceptación</h3>
                    <p>Al marcar la casilla "He leído y acepto los Términos y Condiciones" durante el registro, usted reconoce haber leído, entendido y aceptado en su totalidad las reglas aquí descritas. El desconocimiento de estos términos no exime de su cumplimiento.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
