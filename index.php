<?php
// index.php modificado para evitar el conflicto de clases

/**
 * index.php - Punto de entrada principal del sistema
 * 
 * Este archivo es el punto de entrada principal de la aplicación.
 * Carga las configuraciones necesarias y redirige al usuario
 * según su estado de autenticación y rol.
 * 
 * @package FEASY Conversor
 * @version 2.0
 */

// Incluir archivo de configuración
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    die("Error: Archivo de configuración no encontrado.");
}
require_once $configFile;

// Comprobar si existe el archivo database.php
$databaseConfigFile = __DIR__ . '/config/database.php';
if (!file_exists($databaseConfigFile)) {
    die("Error: Archivo de configuración de base de datos no encontrado.");
}
require_once $databaseConfigFile;

// Comprobar si existe la clase Auth
$authFile = __DIR__ . '/classes/Auth.php';
if (!file_exists($authFile)) {
    die("Error: Clase Auth.php no encontrada.");
}
require_once $authFile;

// Verificar conexión a la base de datos
try {
    $database = new Database();
    $connection = $database->getConnection();
} catch (Exception $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Inicializar autenticación
$auth = new Auth();

// Registrar visita en el log si está habilitado y el archivo existe
$logFile = __DIR__ . '/classes/Log.php';
if (defined('LOG_ENABLED') && LOG_ENABLED && file_exists($logFile)) {
    require_once $logFile;
    try {
        $logger = new Log();
        
        if ($auth->isLoggedIn()) {
            $logger->logActivity($_SESSION['user_id'], 'page_visit', 'Visita a la página principal');
        } else {
            $logger->logActivity(null, 'page_visit', 'Visita anónima a la página principal');
        }
    } catch (Exception $e) {
        // Ignorar errores de logging para no interrumpir la navegación
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

// Si ya está autenticado, redirigir según el rol
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
} else {
    // Si no está autenticado, redirigir a login
    redirect('login.php');
}

// Este código nunca debería ejecutarse debido a las redirecciones
exit("Redirección fallida. Por favor, contacte al administrador del sistema.");