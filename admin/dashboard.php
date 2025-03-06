<?php
// admin/dashboard.php
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

// Obtener estadísticas generales
$logger = new Log();
$userManager = new User();

// Por defecto, mostrar estadísticas del mes actual
$startDate = date('Y-m-01'); // Primer día del mes actual
$endDate = date('Y-m-t');    // Último día del mes actual

// Si se proporcionan parámetros de fecha
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = sanitize($_GET['start_date']);
    $endDate = sanitize($_GET['end_date']);
}

$statistics = $logger->getInvoiceStatistics(null, $startDate, $endDate);
$userCount = $userManager->getUserCount();
$recentUsers = $userManager->getRecentUsers(5);
$recentLogs = $logger->getLogs(null, 1, 10);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --warning-color: #f9ab00;
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
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        /* Dashboard grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
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
        
        .filter-bar label {
            margin-right: 12px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .filter-bar input[type="date"] {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-right: 16px;
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
        
        /* Chart container */
        .chart-container {
            height: 300px;
            margin-bottom: 16px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            font-weight: 500;
            color: var(--gray-color);
            font-size: 14px;
        }
        
        .data-table td {
            font-size: 14px;
        }
        
        .data-table tbody tr:hover {
            background-color: var(--light-gray);
        }
        
        .data-table .badge {
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
        
        .badge-warning {
            background-color: rgba(249, 171, 0, 0.1);
            color: var(--warning-color);
        }
        
        .badge-error {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--error-color);
        }
        
        .badge-default {
            background-color: rgba(95, 99, 104, 0.1);
            color: var(--gray-color);
        }
        
        .view-all {
            display: block;
            text-align: center;
            padding: 8px;
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
            margin-top: 16px;
        }
        
        .view-all:hover {
            background-color: var(--light-gray);
            border-radius: 4px;
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
                    <a href="dashboard.php" class="active">
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
            <h1 class="page-title">Dashboard de Administración</h1>
            
            <div class="filter-bar">
                <form action="" method="GET">
                    <label for="start_date">Desde:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    
                    <label for="end_date">Hasta:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    
                    <button type="submit" class="filter-btn">Filtrar</button>
                </form>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $userCount; ?></div>
                    <div class="stat-label">Usuarios Totales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($statistics['totals']['total_processes'] ?? 0); ?></div>
                    <div class="stat-label">Archivos Procesados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($statistics['totals']['total_invoices'] ?? 0); ?></div>
                    <div class="stat-label">Facturas Procesadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($statistics['totals']['total_rows'] ?? 0); ?></div>
                    <div class="stat-label">Filas Generadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format(($statistics['totals']['avg_processing_time'] ?? 0), 2); ?>s</div>
                    <div class="stat-label">Tiempo Promedio</div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="card">
                    <h2 class="card-title">Actividad Diaria</h2>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <h2 class="card-title">Rendimiento por Usuario</h2>
                    <div class="chart-container">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="card">
                    <h2 class="card-title">Usuarios Recientes</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre Completo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['full_name']; ?></td>
                                <td><?php echo $user['role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="users.php" class="view-all">Ver todos los usuarios</a>
                </div>
                
                <div class="card">
                    <h2 class="card-title">Actividad Reciente</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs['logs'] as $log): ?>
                            <tr>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="logs.php" class="view-all">Ver todos los registros</a>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        &copy; <?php echo date('Y'); ?> FEASY SOFTWARE SOLUTIONS SAS - Todos los derechos reservados | <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
    </footer>
    
    <script>
        // Preparar datos para los gráficos
        const dailyData = <?php echo json_encode($statistics['daily']); ?>;
        const userData = <?php echo json_encode($statistics['by_user']); ?>;
        
        // Configurar gráfico diario
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
                }),
                datasets: [{
                    label: 'Facturas Procesadas',
                    data: dailyData.map(item => item.invoices),
                    backgroundColor: 'rgba(66, 133, 244, 0.7)',
                    borderColor: 'rgb(66, 133, 244)',
                    borderWidth: 1
                }, {
                    label: 'Archivos Procesados',
                    data: dailyData.map(item => item.processes),
                    backgroundColor: 'rgba(15, 157, 88, 0.7)',
                    borderColor: 'rgb(15, 157, 88)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Configurar gráfico de usuarios
        const userCtx = document.getElementById('userChart').getContext('2d');
        const userChart = new Chart(userCtx, {
            type: 'pie',
            data: {
                labels: userData.map(item => item.username),
                datasets: [{
                    data: userData.map(item => item.invoices),
                    backgroundColor: [
                        'rgba(66, 133, 244, 0.7)',
                        'rgba(15, 157, 88, 0.7)',
                        'rgba(219, 68, 55, 0.7)',
                        'rgba(244, 180, 0, 0.7)',
                        'rgba(66, 133, 244, 0.4)',
                        'rgba(15, 157, 88, 0.4)',
                        'rgba(219, 68, 55, 0.4)',
                        'rgba(244, 180, 0, 0.4)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value} facturas`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>