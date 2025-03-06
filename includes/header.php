<?php
// includes/header.php
// Este archivo contiene el encabezado compartido para todas las páginas del sistema

// Asegurarse de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuraciones si no están cargadas
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

// Determinar la ruta base para los assets y links relativos
$basePath = '';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';

if (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/user/') !== false) {
    $basePath = '../';
} else {
    $basePath = './';
}

// Variables para personalización de la página
$pageTitle = isset($pageTitle) ? $pageTitle : APP_NAME;
$bodyClass = isset($bodyClass) ? $bodyClass : '';
$pageIcon = isset($pageIcon) ? $pageIcon : 'dashboard';
$showPageTitle = isset($showPageTitle) ? $showPageTitle : true;

// Inicializar arrays si no existen
if (!isset($pageStyles)) $pageStyles = array();
if (!isset($headerScripts)) $headerScripts = array();
if (!isset($footerScripts)) $footerScripts = array();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . APP_NAME); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="https://esfeasy.com/web/image/website/1/logo/esfeasy?unique=c4e2bb3" type="image/png">
    
    <!-- Fuentes y estilos de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Hojas de estilos CSS personalizadas -->
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-light: #e8f0fe;
            --hover-color: #0d62d1;
            --border-color: #dadce0;
            --text-color: #202124;
            --gray-color: #5f6368;
            --light-gray: #f1f3f4;
            --sidebar-width: 256px;
            --success-color: #0f9d58;
            --error-color: #ea4335;
            --warning-color: #f9ab00;
            --header-height: 64px;
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
            line-height: 1.5;
        }
        
        /* Header */
        header {
            background-color: white;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: var(--header-height);
        }
        
        .logo {
            display: flex;
            align-items: center;
            height: 100%;
        }
        
        .logo img {
            height: 40px;
            margin-right: 12px;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-info {
            margin-right: 16px;
            font-size: 14px;
            text-align: right;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-role {
            color: var(--gray-color);
            font-size: 12px;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            background-color: var(--light-gray);
        }
        
        .logout-btn .material-icons {
            font-size: 16px;
            margin-right: 4px;
        }
        
        /* Main layout */
        .layout {
            display: flex;
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
            position: relative;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        /* Page title */
        .page-title {
            font-size: 22px;
            font-weight: 400;
            margin-bottom: 24px;
            color: var(--text-color);
            display: flex;
            align-items: center;
        }
        
        .page-title .material-icons {
            margin-right: 10px;
            color: var(--primary-color);
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
            display: flex;
            align-items: center;
        }
        
        .card-title .material-icons {
            margin-right: 8px;
            font-size: 20px;
            color: var(--primary-color);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        
        .btn .material-icons {
            margin-right: 6px;
            font-size: 18px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--hover-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #0b8a4b;
        }
        
        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--gray-color);
        }
        
        .btn-outline:hover {
            background-color: var(--light-gray);
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: border 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
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
            color: var(--gray-color);
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        /* Messages */
        .message {
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 4px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .message .material-icons {
            margin-right: 8px;
        }
        
        .message-success {
            background-color: rgba(15, 157, 88, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .message-error {
            background-color: rgba(234, 67, 53, 0.1);
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }
        
        .message-warning {
            background-color: rgba(249, 171, 0, 0.1);
            border-left: 4px solid var(--warning-color);
            color: var(--warning-color);
        }
        
        .message-info {
            background-color: rgba(26, 115, 232, 0.1);
            border-left: 4px solid var(--primary-color);
            color: var(--primary-color);
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
        
        .badge-info {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
        }
        
        /* Load additional styles if defined */
        <?php if (isset($customStyles)): ?>
            <?php echo $customStyles; ?>
        <?php endif; ?>
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                z-index: 100;
                background-color: white;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .menu-toggle {
                display: inline-flex !important;
            }
            
            .logo-text {
                font-size: 16px;
            }
            
            .user-info {
                display: none;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            .card {
                padding: 16px;
            }
        }
    </style>
    
    <!-- Cargar estilos adicionales -->
    <?php if (!empty($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo $basePath . htmlspecialchars($style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Cargar scripts necesarios en el head -->
    <?php if (!empty($headerScripts)): ?>
        <?php foreach ($headerScripts as $script): ?>
            <script src="<?php echo $basePath . htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <header>
        <div class="logo">
            <span class="material-icons menu-toggle" id="menuToggle" style="margin-right: 16px; display: none; cursor: pointer;">menu</span>
            <img src="https://esfeasy.com/web/image/website/1/logo/esfeasy?unique=c4e2bb3" alt="Feasy Logo">
            <span class="logo-text">
                <?php echo htmlspecialchars(APP_NAME); ?>
                <?php if (isset($isAdmin) && $isAdmin): ?> - Panel de Administración<?php endif; ?>
            </span>
        </div>
        
        <div class="user-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></div>
                    <div class="user-role"><?php echo isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></div>
                </div>
                <a href="<?php echo $basePath; ?>logout.php" class="logout-btn">
                    <span class="material-icons">logout</span>
                    Cerrar sesión
                </a>
            <?php else: ?>
                <a href="<?php echo $basePath; ?>login.php" class="btn btn-primary">
                    <span class="material-icons">login</span>
                    Iniciar sesión
                </a>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="layout">
        <?php 
        // Incluir la barra lateral si el usuario está autenticado
        if (isset($_SESSION['user_id']) && file_exists(__DIR__ . '/sidebar.php')) {
            include_once __DIR__ . '/sidebar.php';
        }
        ?>
        
        <div class="main-content">
            <!-- Incluir mensajes del sistema -->
            <?php 
            if (file_exists(__DIR__ . '/messages.php')) {
                include_once __DIR__ . '/messages.php';
            }
            ?>
            
            <!-- Título de la página si está definido -->
            <?php if ($showPageTitle): ?>
            <h1 class="page-title">
                <span class="material-icons"><?php echo htmlspecialchars($pageIcon); ?></span>
                <?php echo htmlspecialchars($pageTitle); ?>
            </h1>
            <?php endif; ?>