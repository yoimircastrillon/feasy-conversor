<?php
// api/logs.php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Log.php';

// Verificar solicitud AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso prohibido']);
    exit;
}

// Verificar sesión
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar si es administrador para algunas operaciones
$isAdmin = $auth->isAdmin();

// Obtener la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

$logger = new Log();
$response = [];

switch ($action) {
    case 'get_logs':
        // Solo administradores pueden ver todos los logs
        if (!$isAdmin && (!isset($_GET['user_id']) || (int)$_GET['user_id'] !== $_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'No tiene permisos para esta acción']);
            exit;
        }
        
        // Obtener parámetros
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $recordsPerPage = isset($_GET['records_per_page']) ? (int)$_GET['records_per_page'] : 20;
        
        // Si no es administrador, forzar a ver solo sus propios logs
        if (!$isAdmin) {
            $userId = $_SESSION['user_id'];
        }
        
        // Obtener logs
        $logs = $logger->getLogs($userId, $page, $recordsPerPage);
        $response = $logs;
        
        break;
        
    case 'export_logs':
        // Solo administradores pueden exportar logs
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['error' => 'No tiene permisos para esta acción']);
            exit;
        }
        
        // Obtener parámetros
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        
        // Obtener todos los logs para exportar (sin paginación)
        $allLogs = $logger->getLogs($userId, 1, 10000);
        
        if (empty($allLogs['logs'])) {
            $response = ['error' => 'No hay logs para exportar'];
        } else {
            // Preparar datos para CSV
            $csvData = [];
            
            // Encabezados CSV
            $csvData[] = ['ID', 'Usuario', 'Acción', 'Descripción', 'Dirección IP', 'Agente Usuario', 'Fecha y Hora'];
            
            // Datos
            foreach ($allLogs['logs'] as $log) {
                $csvData[] = [
                    $log['id'],
                    $log['username'] ?? 'Anónimo',
                    $log['action'],
                    $log['description'],
                    $log['ip_address'],
                    $log['user_agent'],
                    $log['created_at']
                ];
            }
            
            // Crear el nombre del archivo
            $fileName = 'logs_' . date('Y-m-d_H-i-s') . '.csv';
            $filePath = '../tmp/' . $fileName;
            
            // Escribir CSV
            $fp = fopen($filePath, 'w');
            foreach ($csvData as $line) {
                fputcsv($fp, $line);
            }
            fclose($fp);
            
            // Preparar respuesta
            $response = [
                'success' => true,
                'fileName' => $fileName,
                'downloadUrl' => '../api/download.php?file=' . urlencode($filePath)
            ];
        }
        
        break;
        
    case 'get_stats':
        // Si no es administrador, solo puede ver sus propias estadísticas
        $statUserId = null;
        if (!$isAdmin) {
            $statUserId = $_SESSION['user_id'];
        } else if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
            $statUserId = (int)$_GET['user_id'];
        }
        
        // Fechas
        $startDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : null;
        $endDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : null;
        
        // Obtener estadísticas
        $stats = $logger->getInvoiceStatistics($statUserId, $startDate, $endDate);
        $response = $stats;
        
        break;
        
    default:
        $response = ['error' => 'Acción no válida'];
        break;
}

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
?>