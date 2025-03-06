<?php
/**
 * logout.php - Gestiona el cierre de sesión de usuarios
 * 
 * Este archivo maneja la finalización segura de la sesión del usuario
 * y lo redirige a la página de inicio de sesión.
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuraciones necesarias
require_once 'config/config.php';
require_once 'classes/Auth.php';
require_once 'classes/Log.php';

// Crear instancias de clases necesarias
$auth = new Auth();
$logger = new Log();

// Verificar si hay una sesión activa
$userId = $_SESSION['user_id'] ?? null;

// Verificar token CSRF para prevenir ataques de falsificación
$validRequest = true;
if (isset($_GET['token'])) {
    // Si se proporciona un token, validarlo
    $token = $_GET['token'];
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        $validRequest = false;
    }
}

// Solo proceder si la petición es válida
if ($validRequest) {
    // Registrar actividad de cierre de sesión si hay un usuario identificado
    if ($userId) {
        $logger->logActivity($userId, 'logout', 'Cierre de sesión manual');
    }
    
    // Realizar el cierre de sesión
    $auth->logout();
    
    // Mensaje de éxito
    $successMessage = 'Ha cerrado sesión correctamente.';
    
    // Redirigir a la página de inicio de sesión con mensaje de éxito
    redirect('login.php?success=logout');
} else {
    // Si la solicitud no es válida, registrar el intento
    if ($userId) {
        $logger->logActivity($userId, 'invalid_logout', 'Intento de cierre de sesión inválido (posible CSRF)');
    }
    
    // Redirigir a la página principal
    if ($userId) {
        // Si sigue con sesión activa, redirigir según su rol
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
        if ($isAdmin) {
            redirect('admin/dashboard.php');
        } else {
            redirect('user/dashboard.php');
        }
    } else {
        // Si no hay sesión, simplemente redirigir al login
        redirect('login.php');
    }
}

// Esta línea nunca debería ejecutarse debido a la redirección, pero por seguridad terminamos la ejecución
exit();
?>