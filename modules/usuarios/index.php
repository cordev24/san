<?php
require_once '../../config/database.php';
requireLogin();
$user = getCurrentUser();

$mensaje = '';
$mensaje_tipo = '';

// ─── Handle POST actions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Create user ──
    if ($action === 'crear') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $nombre   = trim($_POST['nombre']);
        $email    = trim($_POST['email']);
        $rol      = $_POST['rol'] ?? 'admin';

        $errors = [];
        if (empty($username)) $errors[] = 'El usuario es requerido';
        if (empty($password)) $errors[] = 'La contraseña es requerida';
        if (empty($nombre))   $errors[] = 'El nombre es requerido';
        if (empty($email))    $errors[] = 'El email es requerido';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'El usuario ya existe';
            }
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, email, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed, $nombre, $email, $rol]);
            $mensaje = 'Usuario creado exitosamente';
            $mensaje_tipo = 'success';
        } else {
            $mensaje = implode('<br>', $errors);
            $mensaje_tipo = 'error';
        }
    }

    // ── Edit user ──
    elseif ($action === 'editar') {
        $id     = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);
        $email  = trim($_POST['email']);
        $rol    = $_POST['rol'] ?? 'admin';

        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?");
        $stmt->execute([$nombre, $email, $rol, $id]);

        if (!empty(trim($_POST['password'] ?? ''))) {
            $hashed = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);
        }

        $mensaje = 'Usuario actualizado exitosamente';
        $mensaje_tipo = 'success';
    }

    // ── Delete user ──
    elseif ($action === 'eliminar') {
        $id = (int)$_POST['id'];
        if ($id === $user['id']) {
            $mensaje = 'No puedes eliminar tu propio usuario';
            $mensaje_tipo = 'error';
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = 'Usuario eliminado';
            $mensaje_tipo = 'success';
        }
    }
}

// ─── Fetch all users ──────────────────────────────────────
$stmt = $pdo->query("SELECT id, username, nombre, email, rol, created_at, updated_at FROM usuarios ORDER BY id ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySan - Gestión de Usuarios</title>

    <link rel="stylesheet" href="../../assets/fonts/inter.css">
    <link rel="stylesheet" href="../../assets/css/reset.css">
    <link rel="stylesheet" href="../../assets/css/variables.css">
    <link rel="stylesheet" href="../../assets/css/bento-grid.css">
    <link rel="stylesheet" href="../../assets/css/main.css">

    <style>
        .page-actions {
            display: flex;
            gap: var(--space-3);
        }

        .table-container {
            background: var(--color-surface);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .table-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-4) var(--space-5);
            border-bottom: 1px solid var(--glass-border);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: var(--space-3) var(--space-5);
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-tertiary);
            font-weight: var(--font-weight-semibold);
            border-bottom: 1px solid var(--glass-border);
            background: var(--color-surface-hover);
        }

        .users-table td {
            padding: var(--space-3) var(--space-5);
            border-bottom: 1px solid var(--glass-border);
            font-size: var(--font-size-sm);
            color: var(--color-text-primary);
            vertical-align: middle;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover td {
            background: var(--color-surface-hover);
        }

        .rol-badge {
            display: inline-flex;
            padding: 2px 10px;
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
        }

        .rol-badge--admin {
            background: color-mix(in srgb, var(--color-violeta) 20%, transparent);
            color: var(--color-violeta);
        }

        .rol-badge--participante {
            background: color-mix(in srgb, var(--color-menta) 20%, transparent);
            color: var(--color-menta);
        }

        .date-cell {
            font-size: var(--font-size-xs);
            color: var(--color-text-tertiary);
        }

        .msg {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-4);
            font-size: var(--font-size-sm);
        }
        .msg--success {
            background: color-mix(in srgb, var(--color-menta) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-menta) 30%, transparent);
            color: var(--color-menta);
        }
        .msg--error {
            background: color-mix(in srgb, var(--color-salmon) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--color-salmon) 30%, transparent);
            color: var(--color-salmon);
        }

        .action-group {
            display: flex;
            gap: var(--space-2);
        }

        .btn-sm-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            background: transparent;
            color: var(--color-text-secondary);
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .btn-sm-icon:hover {
            border-color: var(--color-violeta);
            color: var(--color-violeta);
        }

        .btn-sm-icon--danger:hover {
            border-color: var(--color-salmon);
            color: var(--color-salmon);
        }

        /* ── Form styles ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: var(--font-size-sm);
            color: var(--color-text-secondary);
            margin-bottom: var(--space-1);
            font-weight: var(--font-weight-medium);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: var(--space-2) var(--space-3);
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
            background: var(--color-surface);
            color: var(--color-text-primary);
            font-size: var(--font-size-sm);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-violeta);
        }

        @media (max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-grid .full {
                grid-column: 1;
            }
            .users-table th:nth-child(4),
            .users-table td:nth-child(4) {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php include '../../assets/icons/feather-sprite.svg'; ?>

    <div class="main-content">
        <?php
        $headerLogoHref   = '../../dashboard.php';
        $headerLogoutHref = '../../logout.php';
        include '../../includes/header.php';
        ?>

        <!-- Page Header -->
        <div class="page-header" style="padding: var(--space-6); margin-bottom: var(--space-4); border-bottom: 1px solid var(--glass-border);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
                <div>
                    <h1 class="page-title" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); display: flex; align-items: center; gap: var(--space-3);">
                        <svg class="icon-xl" style="width: 40px; height: 40px; stroke: var(--color-violeta);">
                            <use href="#icon-users"></use>
                        </svg>
                        Gestión de Usuarios
                    </h1>
                    <p style="color: var(--color-text-secondary); margin-top: var(--space-2);">
                        Administra los usuarios del sistema, crea, edita y elimina cuentas.
                    </p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-violeta" onclick="openModal('crearModal')">
                        <svg class="icon"><use href="#icon-user-plus"></use></svg>
                        Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>

        <div style="max-width: 1600px; margin: 0 auto; padding: 0 var(--space-6);">

            <?php if ($mensaje): ?>
                <div class="msg msg--<?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <!-- Table -->
            <div class="table-container table-responsive">
                <div class="table-header-row">
                    <span style="font-size:var(--font-size-sm);color:var(--color-text-tertiary);">
                        <strong><?php echo count($usuarios); ?></strong> usuario<?php echo count($usuarios) !== 1 ? 's' : ''; ?> registrado<?php echo count($usuarios) !== 1 ? 's' : ''; ?>
                    </span>
                </div>

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Registrado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                                <td>
                                    <span class="rol-badge rol-badge--<?php echo $u['rol']; ?>">
                                        <?php echo htmlspecialchars($u['rol']); ?>
                                    </span>
                                </td>
                                <td class="date-cell"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button class="btn-sm-icon" title="Editar" onclick="editarUsuario(<?php echo $u['id']; ?>)">
                                            <svg class="icon" style="width:15px;height:15px;"><use href="#icon-edit"></use></svg>
                                        </button>
                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <button class="btn-sm-icon btn-sm-icon--danger" title="Eliminar" onclick="eliminarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>')">
                                                <svg class="icon" style="width:15px;height:15px;"><use href="#icon-trash-2"></use></svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- ── CREATE MODAL ── -->
    <div id="crearModal" class="modal-overlay">
        <div class="modal-content" style="max-width:560px;">
            <div class="modal-header">
                <h2 class="modal-title">Nuevo Usuario</h2>
                <button class="modal-close" onclick="closeModal('crearModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="crear">
                <div class="form-grid" style="padding:var(--space-5);">
                    <div class="form-group">
                        <label for="c_username">Usuario *</label>
                        <input type="text" name="username" id="c_username" required placeholder="Ej: jperez">
                    </div>
                    <div class="form-group">
                        <label for="c_password">Contraseña *</label>
                        <input type="password" name="password" id="c_password" required placeholder="Min. 4 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="c_nombre">Nombre Completo *</label>
                        <input type="text" name="nombre" id="c_nombre" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label for="c_email">Email *</label>
                        <input type="email" name="email" id="c_email" required placeholder="Ej: juan@ejemplo.com">
                    </div>
                    <div class="form-group full">
                        <label for="c_rol">Rol</label>
                        <select name="rol" id="c_rol">
                            <option value="admin">Administrador</option>
                            <option value="participante">Participante</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:var(--space-3);padding:0 var(--space-5) var(--space-5);">
                    <button type="submit" class="btn btn-violeta" style="flex:1;">Crear Usuario</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('crearModal')" style="flex:1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── EDIT MODAL ── -->
    <div id="editarModal" class="modal-overlay">
        <div class="modal-content" style="max-width:560px;">
            <div class="modal-header">
                <h2 class="modal-title">Editar Usuario</h2>
                <button class="modal-close" onclick="closeModal('editarModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" id="e_id">
                <div class="form-grid" style="padding:var(--space-5);">
                    <div class="form-group full">
                        <label>Usuario</label>
                        <input type="text" id="e_username_display" disabled style="opacity:0.6;">
                    </div>
                    <div class="form-group">
                        <label for="e_nombre">Nombre Completo *</label>
                        <input type="text" name="nombre" id="e_nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="e_email">Email *</label>
                        <input type="email" name="email" id="e_email" required>
                    </div>
                    <div class="form-group full">
                        <label for="e_password">Nueva Contraseña <small style="color:var(--color-text-tertiary);">(dejar vacío para no cambiar)</small></label>
                        <input type="password" name="password" id="e_password" placeholder="Nueva contraseña">
                    </div>
                    <div class="form-group full">
                        <label for="e_rol">Rol</label>
                        <select name="rol" id="e_rol">
                            <option value="admin">Administrador</option>
                            <option value="participante">Participante</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:var(--space-3);padding:0 var(--space-5) var(--space-5);">
                    <button type="submit" class="btn btn-violeta" style="flex:1;">Guardar Cambios</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editarModal')" style="flex:1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── DELETE FORM (hidden, submitted via JS) ── -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="eliminar">
        <input type="hidden" name="id" id="d_id">
    </form>

    <script src="../../assets/js/shared.js"></script>
    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function editarUsuario(id) {
            fetch('../../api/usuarios.php?action=get&id=' + id)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.error); return; }
                    document.getElementById('e_id').value = data.id;
                    document.getElementById('e_username_display').value = data.username;
                    document.getElementById('e_nombre').value = data.nombre;
                    document.getElementById('e_email').value = data.email || '';
                    document.getElementById('e_rol').value = data.rol;
                    document.getElementById('e_password').value = '';
                    openModal('editarModal');
                })
                .catch(function () { alert('Error al cargar datos del usuario'); });
        }

        function eliminarUsuario(id, username) {
            if (confirm('¿Estás seguro de eliminar al usuario "' + username + '"?\nEsta acción no se puede deshacer.')) {
                document.getElementById('d_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    </script>
</body>

</html>
