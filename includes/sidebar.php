<?php
// includes/sidebar.php
// Este archivo contiene la barra lateral de navegación diferenciada por rol

// Asegurarse de que hay una sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario tiene sesión
if (!isset($_SESSION['user_id'])) {
    return; // No mostrar la barra lateral si no hay usuario
}

// Determinar la ruta actual para marcar el elemento activo
$currentPage = basename($_SERVER['PHP_SELF']);

// Determinar si es administrador
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Establecer las rutas base según el rol y la ubicación actual
$basePath = '';

if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $basePath = './'; // Ya estamos en admin/
} elseif (strpos($_SERVER['PHP_SELF'], '/user/') !== false) {
    if ($isAdmin) {
        $basePath = '../admin/'; // Ir a admin desde user/
    } else {
        $basePath = './'; // Ya estamos en user/
    }
} else {
    // Estamos en la raíz
    if ($isAdmin) {
        $basePath = 'admin/';
    } else {
        $basePath = 'user/';
    }
}

// Función para determinar si una página está activa
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}

// Definir los elementos de menú según el rol
if ($isAdmin) {
    // Menú para administradores
    $menuItems = [
        [
            'page' => 'dashboard.php',
            'icon' => 'dashboard',
            'text' => 'Dashboard',
            'description' => 'Panel principal con estadísticas generales'
        ],
        [
            'page' => 'users.php',
            'icon' => 'people',
            'text' => 'Usuarios',
            'description' => 'Gestión de usuarios del sistema',
            'category' => 'Gestión'
        ],
        [
            'page' => 'logs.php',
            'icon' => 'history',
            'text' => 'Registros',
            'description' => 'Historial de actividades del sistema'
        ],
        [
            'page' => 'converter.php',
            'icon' => 'transform',
            'text' => 'Conversor',
            'description' => 'Herramienta de conversión de archivos',
            'category' => 'Conversor'
        ],
        [
            'page' => 'reports.php',
            'icon' => 'assessment',
            'text' => 'Reportes',
            'description' => 'Informes y estadísticas detalladas'
        ]
    ];
} else {
    // Menú para usuarios regulares
    $menuItems = [
        [
            'page' => 'dashboard.php',
            'icon' => 'dashboard',
            'text' => 'Dashboard',
            'description' => 'Panel principal con tus estadísticas'
        ],
        [
            'page' => 'converter.php',
            'icon' => 'transform',
            'text' => 'Conversor',
            'description' => 'Herramienta de conversión de archivos'
        ],
        [
            'page' => 'my_reports.php',
            'icon' => 'assessment',
            'text' => 'Mis Reportes',
            'description' => 'Tus informes y actividades'
        ]
    ];
}
?>

<div class="sidebar">
    <div class="user-sidebar-info">
        <div class="user-avatar">
            <span class="material-icons">account_circle</span>
        </div>
        <div class="user-details">
            <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
            <div class="user-role"><?php echo $isAdmin ? 'Administrador' : 'Usuario'; ?></div>
        </div>
    </div>
    
    <ul class="nav-list">
        <?php 
        $currentCategory = null;
        foreach ($menuItems as $item): 
            // Verificar si hay cambio de categoría
            if (isset($item['category']) && $currentCategory !== $item['category']):
                $currentCategory = $item['category'];
        ?>
                <div class="nav-category"><?php echo $currentCategory; ?></div>
        <?php endif; ?>
        
        <li class="nav-item">
            <a href="<?php echo $basePath . $item['page']; ?>" 
               class="<?php echo isActive($item['page']); ?>"
               title="<?php echo isset($item['description']) ? $item['description'] : $item['text']; ?>">
                <span class="material-icons"><?php echo $item['icon']; ?></span>
                <span class="nav-text"><?php echo $item['text']; ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    
    <div class="sidebar-footer">
        <div class="app-version">
            <span class="material-icons">info</span>
            <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
        </div>
    </div>
</div>

<style>
    /* Sidebar styles */
    .sidebar {
        width: var(--sidebar-width);
        background-color: white;
        border-right: 1px solid var(--border-color);
        position: fixed;
        height: calc(100vh - var(--header-height));
        overflow-y: auto;
        top: var(--header-height);
        left: 0;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease;
        z-index: 900;
    }
    
    .user-sidebar-info {
        display: flex;
        align-items: center;
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .user-avatar {
        background-color: var(--light-gray);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
    }
    
    .user-avatar .material-icons {
        font-size: 24px;
        color: var(--primary-color);
    }
    
    .user-details {
        flex: 1;
        overflow: hidden;
    }
    
    .user-details .user-name {
        font-weight: 500;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .user-details .user-role {
        font-size: 12px;
        color: var(--gray-color);
    }
    
    .nav-list {
        list-style: none;
        padding: 8px 0;
        flex: 1;
    }
    
    .nav-item a {
        display: flex;
        align-items: center;
        padding: 12px 24px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 14px;
        transition: background-color 0.2s;
        border-radius: 0 24px 24px 0;
        margin-right: 8px;
    }
    
    .nav-item a:hover {
        background-color: var(--light-gray);
    }
    
    .nav-item a.active {
        background-color: var(--primary-light);
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
    
    .nav-text {
        flex: 1;
    }
    
    .nav-category {
        padding: 16px 24px 8px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        color: var(--gray-color);
        letter-spacing: 0.5px;
    }
    
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid var(--border-color);
        font-size: 12px;
        color: var(--gray-color);
    }
    
    .app-version {
        display: flex;
        align-items: center;
    }
    
    .app-version .material-icons {
        font-size: 16px;
        margin-right: 8px;
    }
    
    /* Responsive sidebar */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0 !important;
        }
    }
</style>

<script>
    // Script para manejar el menú en dispositivos móviles
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (window.innerWidth <= 768) {
            if (menuToggle) {
                menuToggle.style.display = 'inline-flex';
            }
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                if (menuToggle) {
                    menuToggle.style.display = 'inline-flex';
                }
            } else {
                if (menuToggle) {
                    menuToggle.style.display = 'none';
                }
                if (sidebar) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });
            
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(e.target) && e.target !== menuToggle) {
                        sidebar.classList.remove('active');
                    }
                }
            });
        }
    });
</script>