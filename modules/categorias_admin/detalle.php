<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header("Location: index.php");
    exit;
}

$stmtProd = $pdo->prepare("SELECT * FROM productos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre ASC");
$stmtProd->execute([$id]);
$productos = $stmtProd->fetchAll();
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
    <title>MySan - Detalles de Categoría</title>
    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        .page-header { padding: var(--space-8); max-width: 1600px; margin: 0 auto; }
        .page-title { font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-4); }
        .product-card {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all var(--transition-base);
        }
        .product-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .product-info { display: flex; flex-direction: column; gap: var(--space-1); }
        .product-name { font-weight: 600; font-size: var(--font-size-lg); }
        .product-meta { color: var(--color-text-secondary); font-size: var(--font-size-sm); }
        .btn-action-danger {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            border-radius: var(--radius-md);
            background: var(--glass-background);
            border: 1px solid var(--glass-border);
            color: var(--color-text-secondary);
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-action-danger:hover {
            border-color: #ff6464;
            color: #ff6464;
            background: rgba(255, 100, 100, 0.1);
        }
    </style>
</head>
<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>
    <div class="main-content">
        <?php
        $headerLogoHref     = '../../dashboard.php';
        $headerLogoutHref   = '../../logout.php';
        $headerBackUrl      = 'index.php';
        $headerBackLabel    = 'Volver a Categorías';
        include '../../includes/header.php';
        ?>

        <div class="page-header" style="padding: var(--space-6); margin-bottom: var(--space-4); border-bottom: 1px solid var(--glass-border);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 class="page-title" style="font-size: var(--font-size-3xl);">
                        <div class="category-icon" style="width:48px;height:48px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:var(--color-<?php echo htmlspecialchars($categoria['color']); ?>);background:var(--color-<?php echo htmlspecialchars($categoria['color']); ?>-tint); border: 1px solid var(--color-<?php echo htmlspecialchars($categoria['color']); ?>);">
                            <svg class="icon-lg" style="stroke: currentColor;"><use href="#icon-package"></use></svg>
                        </div>
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </h1>
                    <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                        <?php echo htmlspecialchars($categoria['descripcion']); ?>
                    </p>
                </div>
                <span class="badge" style="background: var(--color-<?php echo htmlspecialchars($categoria['color']); ?>-tint); color: var(--color-<?php echo htmlspecialchars($categoria['color']); ?>); padding: var(--space-2) var(--space-4); font-size: var(--font-size-sm); border-radius: var(--radius-full); border: 1px solid var(--color-<?php echo htmlspecialchars($categoria['color']); ?>);">
                    <?php echo count($productos); ?> Productos
                </span>
            </div>
        </div>

        <div class="bento-container">
            <div class="bento-12">
                <div class="bento-box">
                    <div class="bento-header">
                        <div class="bento-title">Productos Asociados</div>
                    </div>
                    <div class="bento-content">
                        <?php if (empty($productos)): ?>
                            <p style="text-align: center; color: var(--color-text-tertiary); padding: var(--space-8) 0;">
                                No hay productos en esta categoría.
                            </p>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-4);">
                                <?php foreach ($productos as $p): ?>
                                    <div class="product-card" id="prod-<?php echo $p['id']; ?>" style="flex-direction: column; align-items: stretch; padding: 0; overflow: hidden;">
                                        <?php if (!empty($p['imagen'])): ?>
                                            <div style="height: 160px; background: var(--color-background); border-bottom: 1px solid var(--glass-border);">
                                                <img src="../../<?php echo htmlspecialchars(ltrim($p['imagen'], '/')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        <?php else: ?>
                                            <div style="height: 160px; background: var(--glass-background); border-bottom: 1px solid var(--glass-border); display: flex; align-items: center; justify-content: center; color: var(--color-text-tertiary);">
                                                <svg class="icon" style="width: 48px; height: 48px; opacity: 0.3;"><use href="#icon-image"></use></svg>
                                            </div>
                                        <?php endif; ?>
                                        <div style="padding: var(--space-4); display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-3);">
                                            <div class="product-info" style="flex: 1;">
                                                <span class="product-name"><?php echo htmlspecialchars($p['nombre']); ?></span>
                                                <span class="product-meta"><?php echo htmlspecialchars($p['marca'] . ' ' . $p['modelo']); ?></span>
                                                <span style="display: block; font-weight: 700; color: var(--color-<?php echo htmlspecialchars($categoria['color']); ?>); margin-top: var(--space-2); font-size: var(--font-size-lg);">$<?php echo number_format($p['valor_total'], 2); ?></span>
                                            </div>
                                            <button class="btn-action-danger" onclick="detachProduct(<?php echo $p['id']; ?>)" title="Desvincular de la categoría">
                                                <svg class="icon"><use href="#icon-x"></use></svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/shared.js"></script>
    <script>
        async function detachProduct(id) {
            if (!confirm('¿Estás seguro de que quieres desvincular este producto? Quedará sin categoría.')) return;
            
            const formData = new FormData();
            formData.append('action', 'detach_categoria');
            formData.append('id', id);
            
            try {
                const response = await fetch('../../api/productos.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                    const card = document.getElementById('prod-' + id);
                    if(card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }
    </script>
</body>
</html>
