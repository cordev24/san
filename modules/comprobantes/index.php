<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>

<style>
    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: var(--space-4);
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
        <div class="page-header">
            <div class="breadcrumb">
                <a href="../../dashboard.php">Dashboard</a>
                <span>/</span>
                <span>Comprobantes y Reportes</span>
            </div>
            <h1 class="page-title">
                <svg class="icon-xl" style="stroke: var(--color-menta);">
                    <use href="#icon-printer"></use>
                </svg>
                Documentación y Finanzas
            </h1>
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

                    if (data.success && data.data.pagos.length > 0) {
                        data.data.pagos.filter(p => p.estado === 'pagado').forEach(p => {
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
                                    <svg class="icon-sm"><use href="#icon-printer"></use></svg>
                                </button>
                            `;
                            container.appendChild(item);
                        });
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

                    if (data.success && data.data.participantes.length > 0) {
                        data.data.participantes.forEach(p => {
                            const item = document.createElement('div');
                            item.className = 'result-item';
                            const statusBadge = p.ha_recibido == 1 
                                ? '<span style="color: var(--color-menta); font-size: 10px; margin-left: 5px;">(Entregado)</span>' 
                                : '';
                            item.innerHTML = `
                                <div>
                                    <div style="font-weight: bold;">${p.nombre} ${p.apellido} ${statusBadge}</div>
                                    <div style="font-size: var(--font-size-xs); color: var(--color-text-tertiary);">
                                        ID: ${p.cedula}
                                    </div>
                                </div>
                                <button class="btn btn-action" onclick="imprimirEntrega(${p.id})">
                                    <svg class="icon-sm"><use href="#icon-file-text"></use></svg>
                                </button>
                            `;
                            container.appendChild(item);
                        });
                    } else {
                        container.innerHTML = '<div style="padding: var(--space-4); text-align: center;">No se encontraron ganadores</div>';
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
    </script>
</body>

</html>

</html>