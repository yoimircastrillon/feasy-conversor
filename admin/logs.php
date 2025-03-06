<?php
// admin/logs.php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Log.php';
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

$logger = new Log();
$userManager = new User();

// Inicializar variables de filtrado
$userId = null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 20;

// Aplicar filtros si se proporcionan
if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
    $userId = (int)$_GET['user_id'];
}

// Obtener logs con paginación
$logs = $logger->getLogs($userId, $page, $recordsPerPage);

// Obtener lista de usuarios para el filtro
$users = $userManager->getAllUsers();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros de Actividad - <?php echo APP_NAME; ?></title>
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
            --warning-color: #f9ab00;
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
        
        /* Filter bar */
        .filter-bar {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 16px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }
        
        .filter-group label {
            margin-right: 8px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .filter-btn:hover {
            background-color: var(--hover-color);
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
        
        .badge-warning {
            background-color: rgba(249, 171, 0, 0.1);
            color: var(--warning-color);
        }
        
        .badge-default {
            background-color: rgba(95, 99, 104, 0.1);
            color: var(--gray-color);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background-color: var(--light-gray);
        }
        
        .pagination .active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination .disabled {
            color: var(--gray-color);
            pointer-events: none;
            background-color: var(--light-gray);
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
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--hover-color);
        }
        
        /* Export button */
        .export-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .export-btn:hover {
            background-color: #0b8a4b;
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
                    <a href="users.php">
                        <span class="material-icons">people</span>
                        Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php" class="active">
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
            <h1 class="page-title">Registros de Actividad</h1>
            
            <div class="filter-bar">
                <form action="" method="GET">
                    <div class="filter-group">
                        <label for="user_id">Usuario:</label>
                        <select name="user_id" id="user_id">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo $user['username']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="filter-btn">Filtrar</button>
                </form>
                
                <button class="export-btn" id="exportBtn" style="margin-left: auto;">
                    <span class="material-icons">download</span> Exportar CSV
                </button>
            </div>
            
            <div class="card">
                <h2 class="card-title">Historial de Actividades</h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>Dirección IP</th>
                            <th>Fecha y Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs['logs'] as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo $log['username'] ?? 'Anónimo'; ?></td>
                            <td>
                                <?php 
                                $badgeClass = 'badge-default';
                                if (strpos($log['action'], 'login') !== false) {
                                    $badgeClass = 'badge-success';
                                } elseif (strpos($log['action'], 'error') !== false || strpos($log['action'], 'failed') !== false) {
                                    $badgeClass = 'badge-error';
                                } elseif (strpos($log['action'], 'process') !== false) {
                                    $badgeClass = 'badge-warning';
                                }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $log['action']; ?>
                                </span>
                            </td>
                            <td><?php echo $log['description']; ?></td>
                            <td><?php echo $log['ip_address']; ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($logs['pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $userId ? '&user_id=' . $userId : ''; ?>">Anterior</a>
                    <?php else: ?>
                        <a href="#" class="disabled">Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $logs['pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $userId ? '&user_id=' . $userId : ''; ?>" 
                           class="<?php echo $i == $page ? 'active' : ''; ?>">
                           <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $logs['pages']): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $userId ? '&user_id=' . $userId : ''; ?>">Siguiente</a>
                    <?php else: ?>
                        <a href="#" class="disabled">Siguiente</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        &copy; <?php echo date('Y'); ?> FEASY SOFTWARE SOLUTIONS SAS - Todos los derechos reservados | <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Exportar registros a CSV
            document.getElementById('exportBtn').addEventListener('click', function() {
                // Obtener los datos de la tabla
                const table = document.querySelector('.data-table');
                let csv = [];
                
                // Agregar encabezados
                const headers = [];
                const headerCells = table.querySelectorAll('thead th');
                headerCells.forEach(cell => {
                    headers.push(cell.textContent.trim());
                });
                csv.push(headers.join(','));
                
                // Agregar filas
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const rowData = [];
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        // Si el texto contiene comas, encerrarlo en comillas
                        let text = cell.textContent.trim();
                        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                            text = '"' + text.replace(/"/g, '""') + '"';
                        }
                        rowData.push(text);
                    });
                    csv.push(rowData.join(','));
                });
                
                // Crear el CSV como blob
                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                
                // Crear un enlace para descargar
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', 'registro_actividades.csv');
                link.style.display = 'none';
                
                // Agregar al DOM y disparar el clic
                document.body.appendChild(link);
                link.click();
                
                // Limpiar
                document.body.removeChild(link);
            });
        });
    </script>
</body>
</html>