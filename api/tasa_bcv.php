<?php
require_once '../config/database.php';
header('Content-Type: application/json');

function fetchBcvRate() {
    // Intentar obtener la tasa de ve.dolarapi.com
    $ch = curl_init('https://ve.dolarapi.com/v1/dolares/oficial');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['promedio'])) {
            return (float)$data['promedio'];
        }
    }
    
    return null;
}

try {
    $today = date('Y-m-d');
    
    // 1. Revisar si ya tenemos la tasa de hoy guardada
    $stmt = $pdo->prepare("SELECT * FROM tasas_cambio WHERE fecha = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$today]);
    $rateToday = $stmt->fetch();
    
    if ($rateToday) {
        jsonResponse(true, 'Tasa actual', [
            'tasa' => (float)$rateToday['tasa'],
            'fecha' => $rateToday['fecha'],
            'origen' => $rateToday['origen']
        ]);
    }
    
    // 2. Si no la tenemos, buscarla
    $newRate = fetchBcvRate();
    
    if ($newRate) {
        // Guardar la nueva tasa
        $insertStmt = $pdo->prepare("INSERT INTO tasas_cambio (tasa, fecha, origen) VALUES (?, ?, 'api_auto')");
        $insertStmt->execute([$newRate, $today]);
        
        jsonResponse(true, 'Tasa actualizada desde API', [
            'tasa' => $newRate,
            'fecha' => $today,
            'origen' => 'api_auto'
        ]);
    } else {
        // 3. Fallback: devolver la última tasa guardada
        $stmtLast = $pdo->query("SELECT * FROM tasas_cambio ORDER BY fecha DESC, id DESC LIMIT 1");
        $lastRate = $stmtLast->fetch();
        
        if ($lastRate) {
            jsonResponse(true, 'Tasa anterior (API no disponible)', [
                'tasa' => (float)$lastRate['tasa'],
                'fecha' => $lastRate['fecha'],
                'origen' => $lastRate['origen'] . '_fallback'
            ]);
        } else {
            jsonResponse(false, 'No se pudo obtener la tasa y no hay histórico');
        }
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
