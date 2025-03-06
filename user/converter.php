<?php
// user/converter.php
require_once '../config/config.php';
require_once '../classes/Auth.php';

// Verificar sesión
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect('login.php');
}

// Si es administrador, redirigir al dashboard admin
if ($auth->isAdmin()) {
    redirect('admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversor - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
        
        /* Upload area */
        .upload-area {
            border: 2px dashed var(--primary-color);
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
            background-color: rgba(26, 115, 232, 0.05);
        }
        
        .upload-area:hover {
            background-color: rgba(26, 115, 232, 0.1);
        }
        
        .upload-area.active {
            border-color: var(--success-color);
            background-color: rgba(15, 157, 88, 0.05);
        }
        
        .upload-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 16px;
        }
        
        .upload-text {
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .upload-format {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        #fileInput {
            display: none;
        }
        
        /* Messages */
        .error-message {
            background-color: rgba(234, 67, 53, 0.1);
            border-left: 4px solid var(--error-color);
            padding: 16px;
            margin: 16px 0;
            border-radius: 4px;
            display: none;
        }
        
        .success-message {
            background-color: rgba(15, 157, 88, 0.1);
            border-left: 4px solid var(--success-color);
            padding: 16px;
            margin: 16px 0;
            border-radius: 4px;
            display: none;
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            border: none;
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
        
        .btn-disabled {
            background-color: var(--gray-color);
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* Loader */
        .loader {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Progress */
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #f1f1f1;
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            width: 0%;
            transition: width 0.3s;
        }
        
        /* Result area */
        .result {
            display: none;
            margin-top: 30px;
        }
        
        .file-info {
            margin: 16px 0;
            font-size: 14px;
            color: var(--gray-color);
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
        
        /* Summary */
        .summary {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        
        .summary p {
            margin: 8px 0;
        }
        
        /* Preview container */
        .preview-container {
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        /* Buttons container */
        .buttons-container {
            display: flex;
            gap: 16px;
            margin-top: 20px;
        }
        
        /* Instructions */
        .instructions {
            margin: 20px 0;
        }
        
        .instructions ol {
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 8px 0;
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
            <span class="logo-text"><?php echo APP_NAME; ?></span>
        </div>
        
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                <div class="user-role"><?php echo $_SESSION['user_role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></div>
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
                <li class="nav-item">
                    <a href="converter.php" class="active">
                        <span class="material-icons">transform</span>
                        Conversor
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_reports.php">
                        <span class="material-icons">assessment</span>
                        Mis Reportes
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Conversor de Compras: Columnas a Filas</h1>
            
            <div class="card">
                <h2 class="card-title">Instrucciones</h2>
                <div class="instructions">
                    <ol>
                        <li>Selecciona un archivo Excel que contenga la pestaña <strong>COLUMNA</strong>.</li>
                        <li>El sistema convertirá los datos al formato <strong>IMPORT</strong> automáticamente.</li>
                        <li>Se generará un nuevo archivo Excel con la conversión realizada.</li>
                    </ol>
                </div>
                
                <div class="error-message" id="errorMsg"></div>
                <div class="success-message" id="successMsg"></div>
                
                <div class="upload-area" id="uploadArea">
                    <span class="material-icons upload-icon">cloud_upload</span>
                    <p class="upload-text"><strong>Haz clic aquí o arrastra un archivo Excel</strong></p>
                    <p class="upload-format">Formato: .xlsx</p>
                    <input type="file" id="fileInput" accept=".xlsx">
                </div>
                
                <div class="file-info" id="fileDetails"></div>
                
                <div class="loader" id="loader">
                    <div class="spinner"></div>
                    <p>Procesando archivo...</p>
                    <div class="progress-container">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button id="processBtn" class="btn btn-primary btn-disabled" disabled>Procesar Conversión</button>
                </div>
                
                <div class="result" id="result">
                    <h2 class="card-title">Resultados de la Conversión</h2>
                    <div class="summary" id="summary"></div>
                    
                    <h3 class="card-title">Vista previa de los datos convertidos</h3>
                    <div class="preview-container">
                        <table class="data-table" id="previewTable">
                            <thead>
                                <tr id="previewHeader"></tr>
                            </thead>
                            <tbody id="previewBody"></tbody>
                        </table>
                    </div>
                    
                    <div class="buttons-container">
                        <button id="downloadBtn" class="btn btn-success">Descargar Excel Convertido</button>
                        <button id="resetBtn" class="btn btn-danger">Reiniciar Proceso</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        &copy; <?php echo date('Y'); ?> FEASY SOFTWARE SOLUTIONS SAS - Todos los derechos reservados | <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
    </footer>
    
    <script src="../assets/js/conversor.js"></script>
</body>
</html>