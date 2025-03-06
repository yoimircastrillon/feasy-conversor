<?php
// admin/reports.php
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

// Inicializar variables de filtrado
$userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
$startDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-t');

// Obtener usuarios para el filtro
$userManager = new User();
$users = $userManager->getAllUsers();

// Consulta de facturas procesadas con filtros
$query = "SELECT p.id, p.user_id, u.username, p.filename, p.original_size, p.invoice_count, 
          p.rows_generated, p.processing_time, p.created_at 
          FROM processed_invoices p
          LEFT JOIN users u ON p.user_id = u.id
          WHERE 1=1";

$params = [];

if ($userId !== null) {
    $query .= " AND p.user_id = :user_id";
    $params[':user_id'] = $userId;
}

$query .= " AND p.created_at BETWEEN :start_date AND :end_date";
$params[':start_date'] = $startDate . ' 00:00:00';
$params[':end_date'] = $endDate . ' 23:59:59';

$query .= " ORDER BY p.created_at DESC";

// Ejecutar consulta
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$totalInvoices = 0;
$totalRows = 0;
$totalFiles = count($invoices);
$avgTime = 0;
$userStats = [];

if ($totalFiles > 0) {
    foreach ($invoices as $invoice) {
        $totalInvoices += $invoice['invoice_count'];
        $totalRows += $invoice['rows_generated'];
        $avgTime += $invoice['processing_time'];
        
        // Estadísticas por usuario
        $userId = $invoice['user_id'];
        if (!isset($userStats[$userId])) {
            $userStats[$userId] = [
                'username' => $invoice['username'],
                'count' => 0,
                'invoices' => 0,
                'rows' => 0
            ];
        }
        
        $userStats[$userId]['count']++;
        $userStats[$userId]['invoices'] += $invoice['invoice_count'];
        $userStats[$userId]['rows'] += $invoice['rows_generated'];
    }
    
    $avgTime = $avgTime / $totalFiles;
}

// Ordenar estadísticas por número de facturas
uasort($userStats, function($a, $b) {
    return $b['invoices'] - $a['invoices'];
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo APP_NAME; ?></title>
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
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
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
        
        .filter-bar input[type="date"], .filter-bar select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-right: 16px;
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
        
        /* Report grid */
        .report-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 1024px) {
            .report-grid {
                grid-template-columns: 1fr;
            }
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
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-color);
        }
        
        .empty-state .material-icons {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--border-color);
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 24px;
        }
        
        /* Chart container */
        .chart-container {
            height: 300px;
            margin-bottom: 16px;
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
            margin-left: auto;
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
                    <a href="reports.php" class="active">
                        <span class="material-icons">assessment</span>
                        Reportes
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Reportes de Conversión</h1>
            
            <div class="filter-bar">
                <form action="" method="GET">
                    <label for="user_id">Usuario:</label>
                    <select name="user_id" id="user_id">
                        <option value="">Todos los usuarios</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo $user['username']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="start_date">Desde:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    
                    <label for="end_date">Hasta:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    
                    <button type="submit" class="filter-btn">Filtrar</button>
                </form>
                
                <?php if (count($invoices) > 0): ?>
                <button class="export-btn" id="exportBtn">
                    <span class="material-icons">download</span> Exportar CSV
                </button>
                <?php endif; ?>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($totalFiles); ?></div>
                    <div class="stat-label">Archivos Procesados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($totalInvoices); ?></div>
                    <div class="stat-label">Facturas Procesadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($totalRows); ?></div>
                    <div class="stat-label">Filas Generadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($avgTime, 2); ?>s</div>
                    <div class="stat-label">Tiempo Promedio</div>
                </div>
            </div>
            
            <div class="report-grid">
                <?php if (count($invoices) > 0): ?>
                <div class="card">
                    <h2 class="card-title">Estadísticas por Usuario</h2>
                    <div class="chart-container">
                        <canvas id="userChart"></canvas>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Archivos</th>
                                <th>Facturas</th>
                                <th>Filas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userStats as $stat): ?>
                            <tr>
                                <td><?php echo $stat['username']; ?></td>
                                <td><?php echo number_format($stat['count']); ?></td>
                                <td><?php echo number_format($stat['invoices']); ?></td>
                                <td><?php echo number_format($stat['rows']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <h2 class="card-title">Distribución por Usuario</h2>
                    <div class="chart-container">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2 class="card-title">Historial de Conversiones</h2>
                
                <?php if (count($invoices) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Archivo</th>
                                <th>Tamaño</th>
                                <th>Facturas</th>
                                <th>Filas</th>
                                <th>Tiempo (s)</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo $invoice['id']; ?></td>
                                <td><?php echo $invoice['username']; ?></td>
                                <td><?php echo $invoice['filename']; ?></td>
                                <td><?php echo formatFileSize($invoice['original_size']); ?></td>
                                <td><?php echo number_format($invoice['invoice_count']); ?></td>
                                <td><?php echo number_format($invoice['rows_generated']); ?></td>
                                <td><?php echo number_format($invoice['processing_time'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($invoice['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="material-icons">info</span>
                        <p>No se encontraron registros para los filtros seleccionados.</p>
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
            // Gráficos sólo si hay datos
            <?php if (count($userStats) > 0): ?>
            // Datos para los gráficos
            const userStats = <?php echo json_encode(array_values($userStats)); ?>;
            
            // Configurar gráfico de usuarios
            const userCtx = document.getElementById('userChart').getContext('2d');
            const userChart = new Chart(userCtx, {
                type: 'bar',
                data: {
                    labels: userStats.map(item => item.username),
                    datasets: [{
                        label: 'Facturas Procesadas',
                        data: userStats.map(item => item.invoices),
                        backgroundColor: 'rgba(66, 133, 244, 0.7)',
                        borderColor: 'rgb(66, 133, 244)',
                        borderWidth: 1
                    }, {
                        label: 'Archivos Procesados',
                        data: userStats.map(item => item.count),
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
            
            // Configurar gráfico de distribución
            const distributionCtx = document.getElementById('distributionChart').getContext('2d');
            const distributionChart = new Chart(distributionCtx, {
                type: 'pie',
                data: {
                    labels: userStats.map(item => item.username),
                    datasets: [{
                        data: userStats.map(item => item.invoices),
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
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} facturas (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Exportar a CSV
            const exportBtn = document.getElementById('exportBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
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
                    link.setAttribute('download', 'reporte_conversiones.csv');
                    link.style.display = 'none';
                    
                    // Agregar al DOM y disparar el clic
                    document.body.appendChild(link);
                    link.click();
                    
                    // Limpiar
                    document.body.removeChild(link);
                });
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Formatear tamaño de archivo
 */
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>