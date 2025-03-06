<?php
// admin/users.php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/User.php';

// Verificar sesión
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect('login.php');
}

// Verificar si es administrador
if (!$auth->isAdmin()) {
    redirect('user/dashboard.php');
}

$userManager = new User();
$message = '';
$messageType = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Acción de crear usuario
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $userData = [
            'username' => sanitize($_POST['username']),
            'password' => $_POST['password'],
            'full_name' => sanitize($_POST['full_name']),
            'email' => sanitize($_POST['email']),
            'role' => sanitize($_POST['role']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($userManager->createUser($userData)) {
            $message = 'Usuario creado correctamente';
            $messageType = 'success';
        } else {
            $message = 'Error al crear el usuario. El nombre de usuario o email ya existe.';
            $messageType = 'error';
        }
    }
    
    // Acción de actualizar usuario
    elseif (isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        $userData = [
            'username' => sanitize($_POST['username']),
            'full_name' => sanitize($_POST['full_name']),
            'email' => sanitize($_POST['email']),
            'role' => sanitize($_POST['role']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Solo agregar contraseña si se proporciona
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        if ($userManager->updateUser($userId, $userData)) {
            $message = 'Usuario actualizado correctamente';
            $messageType = 'success';
        } else {
            $message = 'Error al actualizar el usuario';
            $messageType = 'error';
        }
    }
    
    // Acción de cambiar estado
    elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        $newStatus = $_POST['status'] === 'activate' ? true : false;
        
        if ($userManager->changeUserStatus($userId, $newStatus)) {
            $message = 'Estado del usuario actualizado correctamente';
            $messageType = 'success';
        } else {
            $message = 'Error al actualizar el estado del usuario';
            $messageType = 'error';
        }
    }
    
    // Acción de eliminar usuario
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        
        if ($userManager->deleteUser($userId)) {
            $message = 'Usuario eliminado correctamente';
            $messageType = 'success';
        } else {
            $message = 'Error al eliminar el usuario';
            $messageType = 'error';
        }
    }
}

// Obtener lista de usuarios
$users = $userManager->getAllUsers();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --hover-color: #0d62d1;
            --border-color: #dadce0;
            --text-color: #202124;
            --gray-color: #5f6368;
            --light-gray: #f1f3f4;
            --sidebar-width: 256px;
            --success-color: #0f9d58;
            --error-color: #ea4335;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header */
        header {
            background-color: white;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            height: 64px;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 12px;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: 500;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-info {
            margin-right: 16px;
            font-size: 14px;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-role {
            color: var(--gray-color);
        }
        
        .logout-btn {
            background-color: transparent;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 8px 16px;
            color: var(--gray-color);
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .logout-btn:hover {
            background-color: var(--light-gray);
        }
        
        /* Main layout */
        .layout {
            display: flex;
            margin-top: 64px;
            min-height: calc(100vh - 64px);
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: white;
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: calc(100vh - 64px);
            overflow-y: auto;
        }
        
        .nav-list {
            list-style: none;
            padding: 8px 0;
        }
        
        .nav-item a {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .nav-item a:hover {
            background-color: var(--light-gray);
        }
        
        .nav-item a.active {
            background-color: #e8f0fe;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .nav-item .material-icons {
            margin-right: 16px;
            color: var(--gray-color);
        }
        
        .nav-item a.active .material-icons {
            color: var(--primary-color);
        }
        
        .nav-category {
            padding: 16px 24px 8px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            color: var(--gray-color);
            letter-spacing: 0.5px;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 24px;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            margin-bottom: 24px;
            color: var(--text-color);
        }
        
        /* Cards */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 16px;
            color: var(--text-color);
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .data-table th, .data-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .data-table th {
            background-color: var(--light-gray);
            font-weight: 500;
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: rgba(15, 157, 88, 0.1);
            color: var(--success-color);
        }
        
        .badge-error {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--error-color);
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            border: none;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 18px;
            padding: 0;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--hover-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0b8a4b;
        }
        
        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--gray-color);
        }
        
        .btn-outline:hover {
            background-color: var(--light-gray);
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: border 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 24px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 16px;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 500;
        }
        
        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-color);
        }
        
        .modal-footer {
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Search */
        .search-container {
            display: flex;
            margin-bottom: 24px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: 4px 0 0 4px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            padding: 0 16px;
            cursor: pointer;
        }
        
        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        /* Messages */
        .message {
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .message-success {
            background-color: rgba(15, 157, 88, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .message-error {
            background-color: rgba(234, 67, 53, 0.1);
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }
        
        /* Footer */
        footer {
            text-align: center;
            padding: 16px;
            background-color: white;
            border-top: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--gray-color);
            margin-top: auto;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="https://esfeasy.com/web/image/website/1/logo/esfeasy?unique=c4e2bb3" alt="Feasy Logo">
            <span class="logo-text"><?php echo APP_NAME; ?> - Panel de Administración</span>
        </div>
        
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                <div class="user-role">Administrador</div>
            </div>
            <a href="../logout.php" class="logout-btn">Cerrar sesión</a>
        </div>
    </header>
    
    <div class="layout">
        <div class="sidebar">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="dashboard.php">
                        <span class="material-icons">dashboard</span>
                        Dashboard
                    </a>
                </li>
                
                <div class="nav-category">Gestión</div>
                
                <li class="nav-item">
                    <a href="users.php" class="active">
                        <span class="material-icons">people</span>
                        Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php">
                        <span class="material-icons">article</span>
                        Registros
                    </a>
                </li>
                
                <div class="nav-category">Conversor</div>
                
                <li class="nav-item">
                    <a href="converter.php">
                        <span class="material-icons">transform</span>
                        Conversor
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php">
                        <span class="material-icons">assessment</span>
                        Reportes
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Gestión de Usuarios</h1>
            
            <?php if (!empty($message)): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="toolbar">
                <div class="search-container">
                    <input type="text" class="search-input" id="searchInput" placeholder="Buscar usuario...">
                    <button class="search-btn" id="searchBtn">
                        <span class="material-icons">search</span>
                    </button>
                </div>
                
                <button class="btn btn-primary" id="newUserBtn">
                    <span class="material-icons">add</span> Nuevo Usuario
                </button>
            </div>
            
            <div class="card">
                <h2 class="card-title">Lista de Usuarios</h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['full_name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-error">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td class="actions">
                                <button class="btn btn-icon btn-outline edit-user" data-id="<?php echo $user['id']; ?>">
                                    <span class="material-icons">edit</span>
                                </button>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de cambiar el estado de este usuario?');">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>">
                                    
                                    <button type="submit" class="btn btn-icon btn-outline">
                                        <?php if ($user['is_active']): ?>
                                            <span class="material-icons" style="color: var(--error-color);">block</span>
                                        <?php else: ?>
                                            <span class="material-icons" style="color: var(--success-color);">check_circle</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    
                                    <button type="submit" class="btn btn-icon btn-outline">
                                        <span class="material-icons" style="color: var(--error-color);">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal para crear/editar usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Nuevo Usuario</h2>
                <span class="modal-close">&times;</span>
            </div>
            
            <form id="userForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="userId" value="">
                
                <div class="form-group">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nombre completo</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña <span id="passwordNote">(requerida)</span></label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="role">Rol</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <label for="is_active">Usuario activo</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelBtn">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        &copy; <?php echo date('Y'); ?> FEASY SOFTWARE SOLUTIONS SAS - Todos los derechos reservados | <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables para el modal
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const userId = document.getElementById('userId');
            const userForm = document.getElementById('userForm');
            const passwordField = document.getElementById('password');
            const passwordNote = document.getElementById('passwordNote');
            
            // Botones
            const newUserBtn = document.getElementById('newUserBtn');
            const closeBtn = document.querySelector('.modal-close');
            const cancelBtn = document.getElementById('cancelBtn');
            const searchBtn = document.getElementById('searchBtn');
            const searchInput = document.getElementById('searchInput');
            const editButtons = document.querySelectorAll('.edit-user');
            
            // Mostrar modal para nuevo usuario
            newUserBtn.addEventListener('click', function() {
                modalTitle.textContent = 'Nuevo Usuario';
                formAction.value = 'create';
                userId.value = '';
                userForm.reset();
                passwordField.required = true;
                passwordNote.textContent = '(requerida)';
                modal.style.display = 'block';
            });
            
            // Cerrar modal
            function closeModal() {
                modal.style.display = 'none';
            }
            
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            
            // Cerrar modal al hacer clic fuera de él
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            
            // Editar usuario
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    
                    // Aquí deberías obtener los datos del usuario desde el servidor
                    // mediante una petición AJAX, pero para simplificar,
                    // buscaremos la fila en la tabla
                    
                    const row = this.closest('tr');
                    const cells = row.cells;
                    
                    document.getElementById('username').value = cells[0].textContent;
                    document.getElementById('full_name').value = cells[1].textContent;
                    document.getElementById('email').value = cells[2].textContent;
                    document.getElementById('role').value = cells[3].textContent === 'Administrador' ? 'admin' : 'user';
                    document.getElementById('is_active').checked = cells[4].textContent.trim() === 'Activo';
                    
                    // Configurar el formulario para edición
                    modalTitle.textContent = 'Editar Usuario';
                    formAction.value = 'update';
                    document.getElementById('userId').value = userId;
                    passwordField.required = false;
                    passwordNote.textContent = '(dejar en blanco para mantener la actual)';
                    
                    modal.style.display = 'block';
                });
            });
            
            // Búsqueda de usuarios
            searchBtn.addEventListener('click', function() {
                const searchTerm = searchInput.value.toLowerCase();
                
                document.querySelectorAll('.data-table tbody tr').forEach(row => {
                    const username = row.cells[0].textContent.toLowerCase();
                    const fullName = row.cells[1].textContent.toLowerCase();
                    const email = row.cells[2].textContent.toLowerCase();
                    
                    if (username.includes(searchTerm) || fullName.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Buscar al presionar Enter
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    searchBtn.click();
                }
            });
        });
    </script>
</body>
</html>