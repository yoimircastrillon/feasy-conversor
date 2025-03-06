<?php
// config/config.php

// Configuración de sesión (debe estar antes de iniciar sesión)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar el protocolo (http o https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

// Detectar el host (dominio)
$host = $_SERVER['HTTP_HOST'];

// Detectar la ruta base
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = trim(substr(dirname($script_name), 0, strrpos(dirname($script_name), '/feasy-conversor') + 15), '/');
$base_url = $protocol . $host . '/' . $base_dir;

// Definir constante con la URL correcta
define('BASE_URL', $protocol . $host . '/' . $base_dir);

// Agregar constante adicional para URL relativas (para mayor seguridad)
define('APP_PATH', '/feasy-conversor');

// Ruta absoluta del directorio raíz (para inclusión de archivos)
define('ROOT_DIR', str_replace('\\', '/', dirname(__DIR__)));

// Información de la aplicación
define('APP_NAME', 'FEASY Conversor');
define('APP_VERSION', '2.0');

// Configuración de logs
define('LOG_ENABLED', true);

// Configuración de zonas horarias
date_default_timezone_set('America/Bogota');

// Función para manejar errores
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = "Error [$errno] $errstr - $errfile:$errline";
    
    // Log del error
    error_log($error);
    
    // Si es un error fatal, terminar la ejecución
    if ($errno == E_USER_ERROR) {
        exit(1);
    }
    
    // Devolver true para evitar el manejo estándar de errores de PHP
    return true;
}

// Establecer el manejador de errores
set_error_handler('handleError');

/**
 * Función para redirigir usando URLs dinámicas
 * @param string $path Ruta relativa (sin barra inicial)
 * @return void
 */
function redirect($path) {
    $cleanPath = ltrim($path, '/');
    header("Location: " . BASE_URL . "/" . $cleanPath);
    exit;
}

/**
 * Función para incluir archivos con ruta absoluta
 * @param string $path Ruta relativa desde la raíz del proyecto
 * @return bool Éxito de la inclusión
 */
function includeFile($path) {
    $fullPath = ROOT_DIR . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        include_once $fullPath;
        return true;
    }
    return false;
}

/**
 * Generar URL para assets
 * @param string $path Ruta relativa del asset
 * @return string URL completa del asset
 */
function asset($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Sanitizar entradas
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Verificar si estamos en desarrollo o producción
 * @return bool Verdadero si estamos en desarrollo
 */
function isDevelopment() {
    $host = $_SERVER['HTTP_HOST'];
    return (strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false || 
            strpos($host, '.test') !== false ||
            strpos($host, '.local') !== false);
}

// Configuración específica para entorno
if (isDevelopment()) {
    // Modo desarrollo - mostrar todos los errores
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    // Modo producción - ocultar errores
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}