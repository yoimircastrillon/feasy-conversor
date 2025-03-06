<?php
// includes/auth_check.php
// Este archivo verifica la autenticación y puede ser incluido en cualquier página que requiera login

// Asegurarse de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuraciones
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

// Cargar clases necesarias
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Log.php';

// Inicializar objetos
$auth = new Auth();
$logger = new Log();

// Verificar si el usuario está autenticado
if (!$auth->isLoggedIn()) {
    // Registrar intento de acceso no autorizado
    $logger->logActivity(null, 'unauthorized_access', 'Intento de acceso sin autenticación: ' . $_SERVER['REQUEST_URI']);
    
    // Determinar la ruta de redirección según dónde estamos
    $redirectPath = 'login.php';
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    
    if (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
        $redirectPath = '../login.php';
    }
    
    // Redirigir al login
    redirect($redirectPath);
    exit;
}

// Determinar si es administrador
$isAdmin = $auth->isAdmin();

// Verificar acceso a área de administración
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptPath, '/admin/') !== false && !$isAdmin) {
    // Registrar intento de acceso a área administrativa sin permisos
    $logger->logActivity($_SESSION['user_id'], 'permission_denied', 
        'Intento de acceso a área de administración sin permisos: ' . $_SERVER['REQUEST_URI']);
    
    // Redirigir a dashboard de usuario
    redirect('../user/dashboard.php');
    exit;
}

// Verificar si la cuenta está activa
$userId = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT is_active FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($user['is_active'])) {
            // Si la cuenta está desactivada, cerrar sesión
            $logger->logActivity($userId, 'account_disabled', 'Intento de acceso con cuenta desactivada');
            $auth->logout();
            
            // Determinar la ruta de redirección
            $redirectPath = 'login.php?error=account_disabled';
            if (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
                $redirectPath = '../login.php?error=account_disabled';
            }
            
            redirect($redirectPath);
            exit;
        }
    } else {
        // Si el usuario no existe en la base de datos
        $logger->logActivity($userId, 'account_not_found', 'Intento de acceso con cuenta inexistente');
        $auth->logout();
        
        // Determinar la ruta de redirección
        $redirectPath = 'login.php?error=account_not_found';
        if (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
            $redirectPath = '../login.php?error=account_not_found';
        }
        
        redirect($redirectPath);
        exit;
    }
} catch (PDOException $e) {
    // Loggear el error
    error_log("Error en verificación de autenticación: " . $e->getMessage());
    $logger->logActivity($userId, 'auth_error', 'Error en la verificación de autenticación');
}

// Registrar última actividad para mantener la sesión activa
$_SESSION['last_activity'] = time();

// Verificar tiempo de inactividad (30 minutos = 1800 segundos)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    $logger->logActivity($userId, 'session_timeout', 'Cierre de sesión por inactividad');
    $auth->logout();
    
    // Determinar la ruta de redirección
    $redirectPath = 'login.php?error=session_timeout';
    if (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
        $redirectPath = '../login.php?error=session_timeout';
    }
    
    redirect($redirectPath);
    exit;
}

// Si todo está bien, registrar actividad en algunas páginas importantes
$importantPages = [
    'dashboard.php', 'converter.php', 'reports.php', 'users.php', 'logs.php', 'my_reports.php'
];

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (in_array($currentPage, $importantPages)) {
    $logger->logActivity($userId, 'page_access', 'Acceso a página: ' . $currentPage);
}

// Establecer variables para usar en las plantillas
$fullName = $_SESSION['full_name'] ?? 'Usuario';
$userRole = $_SESSION['user_role'] ?? 'user';
$username = $_SESSION['username'] ?? '';

// Función para determinar si una página está activa
function isActivePage($pageName) {
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return $currentPage === $pageName;
}