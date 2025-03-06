<?php
// api/users.php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/User.php';

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

// Verificar si es administrador (todas las operaciones requieren permisos de administrador)
if (!$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene permisos para esta acción']);
    exit;
}

// Obtener la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

$userManager = new User();
$response = [];

switch ($action) {
    case 'get_users':
        // Obtener lista de usuarios
        if (isset($_GET['search']) && $_GET['search']) {
            // Buscar usuarios por término
            $searchTerm = sanitize($_GET['search']);
            $users = $userManager->searchUsers($searchTerm);
        } else {
            // Obtener todos los usuarios
            $users = $userManager->getAllUsers();
        }
        
        $response = ['users' => $users];
        break;
        
    case 'get_user':
        // Verificar si se proporcionó un ID
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            $response = ['error' => 'ID de usuario no especificado'];
            break;
        }
        
        $userId = (int)$_GET['id'];
        $user = $userManager->getUserById($userId);
        
        if ($user) {
            $response = ['user' => $user];
        } else {
            $response = ['error' => 'Usuario no encontrado'];
        }
        break;
        
    case 'create_user':
        // Verificar si la solicitud es POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response = ['error' => 'Método no permitido'];
            break;
        }
        
        // Obtener datos del formulario (desde JSON)
        $jsonData = file_get_contents('php://input');
        $userData = json_decode($jsonData, true);
        
        if (!$userData) {
            $response = ['error' => 'Datos inválidos'];
            break;
        }
        
        // Sanitizar datos
        $newUser = [
            'username' => sanitize($userData['username']),
            'password' => $userData['password'], // No sanitizar contraseñas
            'full_name' => sanitize($userData['full_name']),
            'email' => sanitize($userData['email']),
            'role' => sanitize($userData['role']),
            'is_active' => isset($userData['is_active']) ? (bool)$userData['is_active'] : true
        ];
        
        // Validaciones básicas
        if (empty($newUser['username']) || empty($newUser['password']) || 
            empty($newUser['full_name']) || empty($newUser['email'])) {
            $response = ['error' => 'Todos los campos son obligatorios'];
            break;
        }
        
        // Crear usuario
        if ($userManager->createUser($newUser)) {
            $response = ['success' => true, 'message' => 'Usuario creado correctamente'];
        } else {
            $response = ['error' => 'Error al crear el usuario. El nombre de usuario o email ya existe.'];
        }
        break;
        
    case 'update_user':
        // Verificar si la solicitud es POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response = ['error' => 'Método no permitido'];
            break;
        }
        
        // Obtener datos del formulario (desde JSON)
        $jsonData = file_get_contents('php://input');
        $userData = json_decode($jsonData, true);
        
        if (!$userData || !isset($userData['id'])) {
            $response = ['error' => 'Datos inválidos'];
            break;
        }
        
        $userId = (int)$userData['id'];
        
        // Sanitizar datos
        $updateUser = [
            'username' => sanitize($userData['username']),
            'full_name' => sanitize($userData['full_name']),
            'email' => sanitize($userData['email']),
            'role' => sanitize($userData['role']),
            'is_active' => isset($userData['is_active']) ? (bool)$userData['is_active'] : true
        ];
        
        // Si se proporciona una contraseña, incluirla
        if (!empty($userData['password'])) {
            $updateUser['password'] = $userData['password']; // No sanitizar contraseñas
        }
        
        // Validaciones básicas
        if (empty($updateUser['username']) || empty($updateUser['full_name']) || empty($updateUser['email'])) {
            $response = ['error' => 'Los campos username, full_name y email son obligatorios'];
            break;
        }
        
        // Actualizar usuario
        if ($userManager->updateUser($userId, $updateUser)) {
            $response = ['success' => true, 'message' => 'Usuario actualizado correctamente'];
        } else {
            $response = ['error' => 'Error al actualizar el usuario'];
        }
        break;
        
    case 'delete_user':
        // Verificar si la solicitud es POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response = ['error' => 'Método no permitido'];
            break;
        }
        
        // Obtener ID del usuario
        $jsonData = file_get_contents('php://input');
        $requestData = json_decode($jsonData, true);
        
        if (!$requestData || !isset($requestData['id'])) {
            $response = ['error' => 'ID de usuario no especificado'];
            break;
        }
        
        $userId = (int)$requestData['id'];
        
        // Verificar que no se esté eliminando a sí mismo
        if ($userId === $_SESSION['user_id']) {
            $response = ['error' => 'No puede eliminar su propio usuario'];
            break;
        }
        
        // Eliminar usuario
        if ($userManager->deleteUser($userId)) {
            $response = ['success' => true, 'message' => 'Usuario eliminado correctamente'];
        } else {
            $response = ['error' => 'Error al eliminar el usuario'];
        }
        break;
        
    case 'toggle_status':
        // Verificar si la solicitud es POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response = ['error' => 'Método no permitido'];
            break;
        }
        
        // Obtener datos
        $jsonData = file_get_contents('php://input');
        $requestData = json_decode($jsonData, true);
        
        if (!$requestData || !isset($requestData['id']) || !isset($requestData['status'])) {
            $response = ['error' => 'Datos inválidos'];
            break;
        }
        
        $userId = (int)$requestData['id'];
        $newStatus = (bool)$requestData['status'];
        
        // Verificar que no se esté desactivando a sí mismo
        if ($userId === $_SESSION['user_id'] && !$newStatus) {
            $response = ['error' => 'No puede desactivar su propio usuario'];
            break;
        }
        
        // Cambiar estado
        if ($userManager->changeUserStatus($userId, $newStatus)) {
            $response = ['success' => true, 'message' => 'Estado actualizado correctamente'];
        } else {
            $response = ['error' => 'Error al actualizar el estado'];
        }
        break;
        
    case 'count_users':
        // Obtener el total de usuarios
        $count = $userManager->getUserCount();
        $response = ['count' => $count];
        break;
        
    case 'recent_users':
        // Obtener usuarios recientes
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $recentUsers = $userManager->getRecentUsers($limit);
        $response = ['users' => $recentUsers];
        break;
        
    default:
        $response = ['error' => 'Acción no válida'];
        break;
}

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
?>