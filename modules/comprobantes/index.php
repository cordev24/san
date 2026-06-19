<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

// ── Diagnóstico directo de la tasa BCV ───────────────────────────────────────
$diag = [
    'registro_hoy'    => null,
    'ultimo_registro' => null,
    'api_http_code'   => null,
    'api_error'       => null,
    'api_promedio'    => null,
    'curl_disponible' => function_exists('curl_init'),
];

$today = date('Y-m-d');

// 1. ¿Qué hay en la BD para hoy?
$stmtHoy = $pdo->prepare("SELECT tasa, fecha, origen FROM tasas_cambio WHERE fecha = ? ORDER BY id DESC LIMIT 1");
$stmtHoy->execute([$today]);
$diag['registro_hoy'] = $stmtHoy->fetch() ?: null;

// 2. ¿Cuál es el último registro en la BD?
$stmtUlt = $pdo->query("SELECT tasa, fecha, origen FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
$diag['ultimo_registro'] = $stmtUlt->fetch() ?: null;

// 3. ¿Puede el servidor llegar a la API?
if ($diag['curl_disponible']) {
    $ch = curl_init('https://ve.dolarapi.com/v1/dolares/oficial');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MySan/1.0');
    $apiRaw  = curl_exec($ch);
    $diag['api_http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $diag['api_error']     = curl_error($ch) ?: null;
    curl_close($ch);
    if ($apiRaw) {
        $apiData = json_decode($apiRaw, true);
        $diag['api_promedio'] = $apiData['promedio'] ?? null;
    }
}

// Intentar hacer una inserción de prueba a ver si hay un problema estructural en la BD
$error_db = null;
try {
    if ($diag['api_promedio'] > 0 && !$diag['registro_hoy']) {
        $insert = $pdo->prepare("INSERT INTO tasas_cambio (tasa, fecha, origen) VALUES (?, ?, 'auto')");
        $insert->execute([$diag['api_promedio'], $today]);
        // Volvemos a leer para el diagnóstico
        $stmtHoy = $pdo->prepare("SELECT tasa, fecha, origen FROM tasas_cambio WHERE fecha = ? ORDER BY id DESC LIMIT 1");
        $stmtHoy->execute([$today]);
        $diag['registro_hoy'] = $stmtHoy->fetch() ?: null;
    }
} catch (Exception $e) {
    $error_db = $e->getMessage();
}

// Tasa que se usará finalmente en el módulo
$tasaActual = getBcvRate();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="../../manifest.json">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MySan - Comprobantes</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .recibo-container {
            max-width: 400px;
            margin: 0 auto;
            background: var(--color-background);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            font-family: 'Courier New', monospace;
        }

        .recibo-header {
            text-align: center;
            border-bottom: 2px dashed var(--glass-border);
            padding-bottom: var(--space-4);
            margin-bottom: var(--space-4);
        }

        .recibo-title {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-2);
        }

        .recibo-subtitle {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
        }

        .recibo-row {
            display: flex;
            justify-content: space-between;
            padding: var(--space-2) 0;
            border-bottom: 1px dotted var(--glass-border);
        }

        .recibo-label {
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
        }

        .recibo-value {
            color: var(--color-text-primary);
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-sm);
        }

        .recibo-total {
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 2px solid var(--color-violeta);
        }

        .recibo-total .recibo-value {
            font-size: var(--font-size-xl);
            color: var(--color-violeta);
        }

        .qr-container {
            text-align: center;
            margin: var(--space-6) 0;
            padding: var(--space-4);
            background: white;
            border-radius: var(--radius-md);
        }

        #qrcode {
            display: inline-block;
        }

        .recibo-footer {
            text-align: center;
            margin-top: var(--space-4);
            padding-top: var(--space-4);
            border-top: 2px dashed var(--glass-border);
            color: var(--color-text-tertiary);
            font-size: var(--font-size-xs);
        }

        .search-results {
            margin-top: var(--space-4);
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
        }

        .result-item {
            padding: var(--space-3) var(--space-4);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background var(--transition-base);
        }

        .result-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .result-item:last-child {
            border-bottom: none;
        }

        .report-box {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
        }

        .report-box .icon-xl {
            stroke: var(--color-violeta);
            margin-bottom: var(--space-2);
        }
    </style>
</head>

<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>
    
    <div class="main-content">
        <?php
        $headerLogoHref     = '../../dashboard.php';
        $headerLogoutHref   = '../../logout.php';
        $headerBackUrl      = '../../dashboard.php';
        $headerBackLabel    = 'Volver al Dashboard';
        include '../../includes/header.php';
        ?>

        <div class="page-header" style="padding: var(--space-6); margin-bottom: var(--space-4); border-bottom: 1px solid var(--glass-border);">
            <h1 class="page-title" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-3);">
                <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-menta);">
                    <use href="#icon-printer"></use>
                </svg>
                Documentación y Finanzas
            </h1>
            <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                Genera recibos de pago, certificados de entrega y gestiona la tasa BCV del día.
            </p>
        </div>
    
        <div class="bento-container">
            <!-- Search Payments for Receipts -->
            <div class="bento-6">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Generar Recibo de Pago</div>
                    </div>
                    <div class="bento-content">
                        <div class="form-group">
                            <label class="form-label">Buscar Pago (Nombre o Cedula)</label>
                            <input type="text" id="searchPago" class="form-input" placeholder="Buscar..."
                                onkeyup="buscarPagos()">
                        </div>
                        <div id="pagoResults" class="search-results">
                            <div
                                style="padding: var(--space-4); text-align: center; color: var(--color-text-tertiary);">
                                Ingresa un nombre para buscar pagos realizados
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificates of Delivery -->
            <div class="bento-6">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Certificado de Entrega</div>
                    </div>
                    <div class="bento-content">
                        <div class="form-group">
                            <label class="form-label">Buscar Ganador (Nombre o Cedula)</label>
                            <input type="text" id="searchGanador" class="form-input" placeholder="Buscar..."
                                onkeyup="buscarGanadores()">
                        </div>
                        <div id="ganadorResults" class="search-results">
                            <div
                                style="padding: var(--space-4); text-align: center; color: var(--color-text-tertiary);">
                                Busca participantes que ya recibieron su producto
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel: Tasa BCV del Día (RF-4 informe §6.1.2) -->
            <div class="bento-12" id="tasa-bcv">
                <div class="bento-box">
                    <div class="bento-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div class="bento-title" style="display:flex; align-items:center; gap:var(--space-3);">
                            <svg style="width:20px;height:20px;stroke:var(--color-salmon);"><use href="#icon-trending-up"></use></svg>
                            Tasa BCV del Día
                        </div>
                        <div style="display:flex; align-items:center; gap:var(--space-3);">
                            <span id="tasaOrigenBadge" style="font-size:var(--font-size-xs);padding:3px 10px;border-radius:var(--radius-full);background:rgba(255,255,255,0.06);border:1px solid var(--glass-border);"></span>
                            <button id="btnActualizarBCV" class="btn btn-action" onclick="actualizarDesdeBCV()" title="Forzar actualización desde el BCV">
                                <svg class="icon"><use href="#icon-refresh-cw"></use></svg>
                                Actualizar desde BCV
                            </button>
                        </div>
                    </div>
                    <div class="bento-content">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);align-items:start;">

                            <!-- Tasa actual (renderizada desde PHP, no depende de AJAX) -->
                            <div>
                                <p style="color:var(--color-text-tertiary);font-size:var(--font-size-sm);margin-bottom:var(--space-2);">
                                    Tasa activa en el sistema:
                                </p>
                                <div id="tasaActualDisplay" style="font-size:var(--font-size-4xl);font-weight:var(--font-weight-bold);color:var(--color-salmon);">
                                    <?php
                                    if ($tasaActual > 0) {
                                        echo 'Bs ' . number_format($tasaActual, 2) . ' / $1';
                                    } else {
                                        echo 'Sin datos';
                                    }
                                    ?>
                                </div>
                                <p id="tasaFechaDisplay" style="color:var(--color-text-tertiary);font-size:var(--font-size-xs);margin-top:var(--space-1);">
                                    <?php if ($diag['ultimo_registro']): ?>
                                        Fecha: <?= htmlspecialchars($diag['ultimo_registro']['fecha']) ?>
                                    <?php endif; ?>
                                </p>
                                <p style="color:var(--color-text-tertiary);font-size:var(--font-size-xs);margin-top:var(--space-3);">
                                    Esta tasa se aplica automáticamente al aprobar comprobantes de pago.<br>
                                    Puedes actualizarla manualmente si la API del BCV no está disponible.
                                </p>

                                <!-- Panel diagnóstico -->
                                <details style="margin-top:var(--space-4);font-size:var(--font-size-xs);">
                                    <summary style="cursor:pointer;color:var(--color-text-tertiary);user-select:none;">Ver diagnóstico del sistema</summary>
                                    <div style="margin-top:var(--space-3);padding:var(--space-3);background:rgba(0,0,0,0.3);border-radius:var(--radius-sm);border:1px solid var(--glass-border);font-family:monospace;line-height:1.8;">
                                        <div>cURL disponible: <strong style="color:<?= $diag['curl_disponible'] ? 'var(--color-menta)' : 'var(--color-error)' ?>"><?= $diag['curl_disponible'] ? 'Sí' : 'NO' ?></strong></div>
                                        <div>API HTTP code: <strong style="color:<?= $diag['api_http_code'] == 200 ? 'var(--color-menta)' : 'var(--color-error)' ?>"><?= $diag['api_http_code'] ?? 'N/A' ?></strong></div>
                                        <?php if ($diag['api_error']): ?>
                                        <div>API error: <strong style="color:var(--color-error)"><?= htmlspecialchars($diag['api_error']) ?></strong></div>
                                        <?php endif; ?>
                                        <div>API promedio: <strong style="color:var(--color-menta)"><?= $diag['api_promedio'] !== null ? 'Bs ' . number_format($diag['api_promedio'], 2) : 'No obtenido' ?></strong></div>
                                        <div style="border-top:1px solid var(--glass-border);margin-top:var(--space-2);padding-top:var(--space-2);">
                                        BD hoy (<?= $today ?>): <strong style="color:<?= $diag['registro_hoy'] ? 'var(--color-menta)' : 'var(--color-salmon)' ?>">
                                            <?= $diag['registro_hoy'] ? 'Bs ' . number_format($diag['registro_hoy']['tasa'], 2) . ' [' . $diag['registro_hoy']['origen'] . ']' : 'Sin registro' ?></strong></div>
                                        <?php if ($error_db): ?>
                                        <div>DB error: <strong style="color:var(--color-error)"><?= htmlspecialchars($error_db) ?></strong></div>
                                        <?php endif; ?>
                                        <div>BD último: <strong style="color:var(--color-text-primary)"><?= $diag['ultimo_registro'] ? 'Bs ' . number_format($diag['ultimo_registro']['tasa'], 2) . ' [' . $diag['ultimo_registro']['fecha'] . '] [' . $diag['ultimo_registro']['origen'] . ']' : 'Tabla vacía' ?></strong></div>
                                        <div>getBcvRate() devolvió: <strong style="color:<?= $tasaActual > 0 ? 'var(--color-menta)' : 'var(--color-error)' ?>"><?= $tasaActual > 0 ? 'Bs ' . number_format($tasaActual, 2) : '0.0 (sin datos)' ?></strong></div>
                                    </div>
                                </details>
                            </div>

                            <!-- Formulario de registro manual -->
                            <div>
                                <p style="color:var(--color-text-secondary);font-size:var(--font-size-sm);margin-bottom:var(--space-3);font-weight:var(--font-weight-semibold);">
                                    Registrar Tasa Manualmente
                                </p>
                                <form id="tasaBcvForm">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);margin-bottom:var(--space-3);">
                                        <div class="form-group" style="margin:0;">
                                            <label class="form-label" for="inputTasa">Tasa Bs/USD *</label>
                                            <input type="number" id="inputTasa" name="tasa" class="form-input"
                                                   step="0.01" min="1" placeholder="Ej: 89.50" required>
                                        </div>
                                        <div class="form-group" style="margin:0;">
                                            <label class="form-label" for="inputFechaTasa">Fecha</label>
                                            <input type="date" id="inputFechaTasa" name="fecha" class="form-input"
                                                   value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div id="tasaAlert" style="display:none;padding:var(--space-2) var(--space-3);border-radius:var(--radius-sm);font-size:var(--font-size-xs);margin-bottom:var(--space-3);"></div>
                                    <button type="submit" class="btn btn-salmon" style="width:100%;">
                                        <svg class="icon"><use href="#icon-save"></use></svg>
                                        Guardar Tasa BCV
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Report -->
            <div class="bento-12">
                <div class="bento-box report-box" style="align-items: center; text-align: center;">
                    <svg class="icon-xl" style="width: 64px; height: 64px;">
                        <use href="#icon-trending-up"></use>
                    </svg>
                    <div>
                        <h2 style="font-size: var(--font-size-2xl); font-weight: bold;">Reporte Financiero General</h2>
                        <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                            Genera un balance detallado de ingresos por cuotas vs egresos por entregas para ver tus
                            ganancias reales.
                        </p>
                    </div>
                    <button class="btn btn-menta" onclick="generarReporteFinanciero()">
                        <svg class="icon">
                            <use href="#icon-file-text"></use>
                        </svg>
                        Descargar Reporte Completo (PDF)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/shared.js"></script>
    <script>
        let searchTimeout;

        function buscarPagos() {
            const query = document.getElementById('searchPago').value;
            if (query.length < 1) {
                document.getElementById('pagoResults').innerHTML = '<div style="padding: var(--space-4); text-align: center; color: var(--color-text-tertiary);">Ingresa un nombre para buscar pagos realizados</div>';
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                try {
                    // Using a generic search from pagos API
                    const response = await fetch(`../../api/pagos.php?action=list_all&search=${query}`);
                    const data = await response.json();
                    console.log('Pagos search response:', data);

                    const container = document.getElementById('pagoResults');
                    container.innerHTML = '';

                    if (data.success && data.data && data.data.pagos) {
                        const pagosPagados = data.data.pagos.filter(p => p.estado === 'pagado');
                        if (pagosPagados.length > 0) {
                            pagosPagados.forEach(p => {
                                const item = document.createElement('div');
                                item.className = 'result-item';
                                item.innerHTML = `
                                    <div>
                                        <div style="font-weight: bold;">${p.nombre_participante}</div>
                                        <div style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">
                                            Cuota #${p.numero_cuota} - ${p.fecha_pago}
                                        </div>
                                    </div>
                                    <button class="btn btn-action" onclick="imprimirRecibo(${p.id})">
                                        <svg class="icon"><use href="#icon-printer"></use></svg>
                                    </button>
                                `;
                                container.appendChild(item);
                            });
                        } else {
                            container.innerHTML = '<div style="padding: var(--space-4); text-align: center;">No se encontraron pagos con estado "pagado"</div>';
                        }
                    } else {
                        container.innerHTML = '<div style="padding: var(--space-4); text-align: center;">No se encontraron pagos</div>';
                    }
                } catch (error) {
                    console.error(error);
                }
            }, 300);
        }

        async function buscarGanadores() {
            const query = document.getElementById('searchGanador').value;
            if (query.length < 1) {
                document.getElementById('ganadorResults').innerHTML = '<div style="padding: var(--space-4); text-align: center; color: var(--color-text-tertiary);">Busca participantes que ya recibieron su producto</div>';
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                try {
                    // Search in participantes API
                    const response = await fetch(`../../api/participantes.php?action=list_all&search=${query}`);
                    const data = await response.json();
                    console.log('Ganadores search response:', data);

                    const container = document.getElementById('ganadorResults');
                    container.innerHTML = '';

                    if (data.success && data.data && data.data.participantes) {
                        const ganadores = data.data.participantes.filter(p => p.ha_recibido == 1);
                        if (ganadores.length > 0) {
                            ganadores.forEach(p => {
                                const item = document.createElement('div');
                                item.className = 'result-item';
                                const statusBadge = '<span style="color: var(--color-menta); font-size: 10px; margin-left: 5px;">(Entregado)</span>';
                                item.innerHTML = `
                                    <div>
                                        <div style="font-weight: bold;">${p.nombre} ${p.apellido} ${statusBadge}</div>
                                        <div style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">
                                            ID: ${p.cedula}
                                        </div>
                                    </div>
                                    <button class="btn btn-action" onclick="imprimirEntrega(${p.id})">
                                        <svg class="icon"><use href="#icon-file-text"></use></svg>
                                    </button>
                                `;
                                container.appendChild(item);
                            });
                        } else {
                            container.innerHTML = '<div style="padding: var(--space-4); text-align: center;">No se encontraron ganadores</div>';
                        }
                    } else {
                        container.innerHTML = '<div style="padding: var(--space-4); text-align: center;">No se encontraron participantes</div>';
                    }
                } catch (error) {
                    console.error(error);
                }
            }, 300);
        }

        function imprimirRecibo(id) {
            window.open(`../../api/comprobantes.php?action=recibo&id=${id}`, '_blank');
        }

        function imprimirEntrega(id) {
            window.open(`../../api/comprobantes.php?action=entrega&id=${id}`, '_blank');
        }

        function generarReporteFinanciero() {
            window.open(`../../api/comprobantes.php?action=reporte_financiero`, '_blank');
        }

        // ── Tasa BCV ──────────────────────────────────────────
        async function loadTasaActual() {
            try {
                const res  = await fetch('../../api/tasa_bcv.php?action=get_current');
                const data = await res.json();
                if (data.success) {
                    const d = data.data;
                    document.getElementById('tasaActualDisplay').textContent =
                        'Bs ' + parseFloat(d.tasa).toFixed(2) + ' / $1';
                    document.getElementById('tasaFechaDisplay').textContent =
                        'Fecha: ' + d.fecha;
                    const badge = document.getElementById('tasaOrigenBadge');
                    const origenMap = {
                        'manual':    { label: 'Ingresada manualmente', color: 'var(--color-salmon)' },
                        'api_auto':  { label: 'Obtenida via API BCV',  color: 'var(--color-menta)'  },
                        'sin_datos': { label: 'Sin datos disponibles', color: 'var(--color-error)'  },
                    };
                    const origen = origenMap[d.origen] ?? { label: d.origen, color: 'var(--color-text-tertiary)' };
                    badge.textContent  = origen.label;
                    badge.style.color  = origen.color;
                }
            } catch(e) {
                document.getElementById('tasaActualDisplay').textContent = 'Sin datos';
            }
        }

        async function actualizarDesdeBCV() {
            const btn = document.getElementById('btnActualizarBCV');
            btn.disabled = true;
            btn.innerHTML = '<svg class="icon"><use href="#icon-loader"></use></svg> Consultando…';
            try {
                const res  = await fetch('../../api/tasa_bcv.php?action=forzar_refresh');
                const data = await res.json();
                if (data.success) {
                    await loadTasaActual();
                } else {
                    alert('No se pudo obtener la tasa del BCV: ' + data.message);
                }
            } catch(e) {
                alert('Error de conexión con la API del BCV.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg class="icon"><use href="#icon-refresh-cw"></use></svg> Actualizar desde BCV';
            }
        }

        document.getElementById('tasaBcvForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const alert = document.getElementById('tasaAlert');
            const fd = new FormData(e.target);
            fd.append('action', 'register_manual');
            try {
                const res  = await fetch('../../api/tasa_bcv.php', { method: 'POST', body: fd });
                const data = await res.json();
                alert.style.display = 'block';
                if (data.success) {
                    alert.style.background = 'rgba(56,217,169,0.1)';
                    alert.style.border     = '1px solid var(--color-menta)';
                    alert.style.color      = 'var(--color-menta)';
                    alert.textContent      = '✓ ' + data.message;
                    loadTasaActual();
                } else {
                    alert.style.background = 'rgba(220,38,38,0.08)';
                    alert.style.border     = '1px solid rgba(220,38,38,0.3)';
                    alert.style.color      = 'var(--color-error)';
                    alert.textContent      = data.message;
                }
            } catch {
                alert.style.display  = 'block';
                alert.textContent    = 'Error de conexión';
            }
        });

        loadTasaActual();
    </script>
</body>
</html>