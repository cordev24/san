<?php
require_once '../../config/database.php';
requireLogin();

$cedula = $_GET['cedula'] ?? null;

if (!$cedula) {
    header("Location: index.php");
    exit;
}

// Fetch master identity
$stmt = $pdo->prepare("
    SELECT nombre, apellido, cedula, telefono, direccion, MAX(fecha_inscripcion) as joined_date
    FROM participantes
    WHERE cedula = ?
    GROUP BY cedula, nombre, apellido, telefono, direccion
");
$stmt->execute([$cedula]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    header("Location: index.php");
    exit;
}

// Fetch groups membership
$stmtGrupos = $pdo->prepare("
    SELECT 
        p.id as participante_id, p.activo, p.ha_recibido, p.fecha_entrega,
        gs.id as grupo_id, gs.nombre as grupo_nombre, gs.estado as grupo_estado, 
        gs.monto_cuota, gs.frecuencia,
        pr.nombre as producto_nombre
    FROM participantes p
    JOIN grupos_san gs ON p.grupo_san_id = gs.id
    JOIN productos pr ON gs.producto_id = pr.id
    WHERE p.cedula = ?
    ORDER BY gs.created_at DESC
");
$stmtGrupos->execute([$cedula]);
$membresias = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Perfil Participante</title>

    <!-- Offline Styles -->
    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: var(--space-6);
            padding: var(--space-8);
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            margin-bottom: var(--space-8);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-violeta));
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--color-primary), var(--color-violeta));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-2xl);
            box-shadow: 0 4px 20px rgba(0, 203, 169, 0.3);
        }

        .profile-info h1 {
            font-size: 28px;
            font-weight: var(--font-weight-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-2);
        }
        
        .profile-info p {
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-1);
        }

        .membership-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-bottom: var(--space-4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .membership-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.1);
        }

        .membership-details h3 {
            font-size: var(--font-size-lg);
            color: var(--color-text-primary);
            margin-bottom: var(--space-1);
            font-weight: var(--font-weight-semibold);
        }

        .membership-details p {
            font-size: var(--font-size-sm);
            color: var(--color-text-tertiary);
        }
    </style>
</head>
<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <?php
    $headerLogoHref   = 'index.php';
    $headerActionText = 'Volver al Directorio';
    $headerActionHref = 'index.php';
    include '../../includes/header.php';
    ?>

    <div class="main-content">
        <div style="padding: var(--space-8); max-width: 1000px; margin: 0 auto;">
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($perfil['nombre'], 0, 1) . substr($perfil['apellido'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($perfil['nombre'] . ' ' . $perfil['apellido']); ?></h1>
                    <p>
                        <svg style="width:16px;height:16px;color:var(--color-primary);"><use href="#icon-hash"></use></svg>
                        CI: <?php echo htmlspecialchars($perfil['cedula']); ?>
                    </p>
                    <p>
                        <svg style="width:16px;height:16px;color:var(--color-text-tertiary);"><use href="#icon-phone"></use></svg>
                        <?php echo htmlspecialchars($perfil['telefono'] ?: 'No registrado'); ?>
                    </p>
                    <p>
                        <svg style="width:16px;height:16px;color:var(--color-text-tertiary);"><use href="#icon-map-pin"></use></svg>
                        <?php echo htmlspecialchars($perfil['direccion'] ?: 'No registrada'); ?>
                    </p>
                </div>
            </div>

            <h2 style="font-size: var(--font-size-xl); font-weight: var(--font-weight-bold); margin-bottom: var(--space-4); color: var(--color-text-primary);">
                Grupos Suscritos (<?php echo count($membresias); ?>)
            </h2>

            <?php if(empty($membresias)): ?>
                <p style="color: var(--color-text-tertiary);">No pertenece a ningún grupo actualmente.</p>
            <?php else: ?>
                <?php foreach($membresias as $mem): ?>
                    <div class="membership-card">
                        <div class="membership-details">
                            <h3><?php echo htmlspecialchars($mem['grupo_nombre']); ?></h3>
                            <p>Producto: <?php echo htmlspecialchars($mem['producto_nombre']); ?></p>
                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                <span class="badge badge-info">Cuota: $<?php echo number_format($mem['monto_cuota'], 2); ?></span>
                                <?php if($mem['ha_recibido']): ?>
                                    <span class="badge badge-success">Sorteo Recibido: <?php echo date('d M Y', strtotime($mem['fecha_entrega'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="text-align: right;">
                            <div style="margin-bottom: 8px;">
                                <span class="badge <?php echo $mem['grupo_estado'] == 'abierto' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $mem['grupo_estado'])); ?>
                                </span>
                                <?php if(!$mem['activo']): ?>
                                    <span class="badge" style="background:#ff6464;color:white;">Inhabilitado</span>
                                <?php endif; ?>
                            </div>
                            <a href="../categoria/pagos.php?grupo_id=<?php echo $mem['grupo_id']; ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">
                                Ver Pagos del Participante
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
