<?php
// api/get_statistics.php
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

// Inicializar parámetros
$userId = null;
$startDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01'); // Primer día del mes actual
$endDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-t'); // Último día del mes actual

// Si no es admin, forzar que sólo vea sus propias estadísticas
if (!$auth->isAdmin()) {
    $userId = $_SESSION['user_id'];
} else if (isset($_GET['user_id'])) {
    // Si es admin y se proporciona un user_id, filtrar por ese usuario
    $userId = sanitize($_GET['user_id']);
}

// Obtener estadísticas
$logger = new Log();
$statistics = $logger->getInvoiceStatistics($userId, $startDate, $endDate);

// Devolver los datos
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $statistics,
    'params' => [
        'user_id' => $userId,
        'start_date' => $startDate,
        'end_date' => $endDate
    ]
]);
?>