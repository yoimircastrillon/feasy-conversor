/**
 * dashboard.js
 * Script para manejar la funcionalidad del panel de control
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initCharts();
    initDateFilters();
    setupEventListeners();
    initDropdowns();
    
    // Mostrar mensaje de bienvenida con el nombre del usuario
    showWelcomeMessage();
});

/**
 * Inicializar gráficos utilizando Chart.js
 */
function initCharts() {
    // Verificar si Chart.js está disponible
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado. Los gráficos no se inicializarán.');
        return;
    }
    
    // Comprobar si los elementos de gráficos existen en la página
    const activityChartEl = document.getElementById('activityChart');
    const userStatsChartEl = document.getElementById('userStatsChart');
    
    // Configuración común para gráficos
    Chart.defaults.font.family = "'Roboto', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#5f6368';
    Chart.defaults.scale.grid.color = '#f1f3f4';
    
    // Inicializar gráfico de actividad si existe el elemento
    if (activityChartEl) {
        initActivityChart(activityChartEl);
    }
    
    // Inicializar gráfico de estadísticas de usuario si existe el elemento
    if (userStatsChartEl) {
        initUserStatsChart(userStatsChartEl);
    }
    
    // Actualizar tamaño de gráficos al cambiar el tamaño de la ventana
    window.addEventListener('resize', function() {
        Chart.instances.forEach(chart => {
            chart.resize();
        });
    });
}

/**
 * Inicializar gráfico de actividad diaria
 * @param {HTMLElement} canvas Elemento canvas para el gráfico
 */
function initActivityChart(canvas) {
    // Datos de ejemplo (deberían ser reemplazados con datos reales de la API)
    const dates = [];
    const invoiceData = [];
    const fileData = [];
    
    // Generar datos de ejemplo para los últimos 10 días
    for (let i = 9; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        dates.push(date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }));
        
        // Datos aleatorios para ejemplo
        invoiceData.push(Math.floor(Math.random() * 50) + 10);
        fileData.push(Math.floor(Math.random() * 10) + 1);
    }
    
    // Crear gráfico
    const activityChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Facturas Procesadas',
                    data: invoiceData,
                    backgroundColor: 'rgba(26, 115, 232, 0.7)',
                    borderColor: 'rgb(26, 115, 232)',
                    borderWidth: 1
                },
                {
                    label: 'Archivos Procesados',
                    data: fileData,
                    backgroundColor: 'rgba(52, 168, 83, 0.7)',
                    borderColor: 'rgb(52, 168, 83)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Guardar referencia al gráfico para acceso global si es necesario
    window.activityChart = activityChart;
}

/**
 * Inicializar gráfico de estadísticas por usuario
 * @param {HTMLElement} canvas Elemento canvas para el gráfico
 */
function initUserStatsChart(canvas) {
    // Datos de ejemplo (deberían ser reemplazados con datos reales de la API)
    const userData = [
        { username: 'usuario1', invoices: 120 },
        { username: 'usuario2', invoices: 85 },
        { username: 'usuario3', invoices: 65 },
        { username: 'usuario4', invoices: 45 },
        { username: 'otros', invoices: 30 }
    ];
    
    // Crear gráfico
    const userStatsChart = new Chart(canvas, {
        type: 'pie',
        data: {
            labels: userData.map(item => item.username),
            datasets: [{
                data: userData.map(item => item.invoices),
                backgroundColor: [
                    'rgba(26, 115, 232, 0.7)',
                    'rgba(52, 168, 83, 0.7)',
                    'rgba(251, 188, 5, 0.7)',
                    'rgba(234, 67, 53, 0.7)',
                    'rgba(154, 160, 166, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} facturas (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Guardar referencia al gráfico para acceso global si es necesario
    window.userStatsChart = userStatsChart;
}

/**
 * Inicializar selectores de fecha
 */
function initDateFilters() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (!startDateInput || !endDateInput) return;
    
    // Establecer fechas por defecto si no tienen valor
    if (!startDateInput.value) {
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 30); // Último mes
        startDateInput.value = startDate.toISOString().split('T')[0];
    }
    
    if (!endDateInput.value) {
        const endDate = new Date();
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
    
    // Asegurar que la fecha de fin no sea anterior a la fecha de inicio
    startDateInput.addEventListener('change', function() {
        if (endDateInput.value < startDateInput.value) {
            endDateInput.value = startDateInput.value;
        }
        endDateInput.min = startDateInput.value;
    });
    
    // Establecer fecha mínima para endDate
    endDateInput.min = startDateInput.value;
}

/**
 * Configurar escuchadores de eventos para elementos interactivos
 */
function setupEventListeners() {
    // Botón de actualizar datos
    const refreshBtn = document.getElementById('refreshData');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            refreshDashboardData();
        });
    }
    
    // Filtro de usuario (para administradores)
    const userFilter = document.getElementById('userFilter');
    if (userFilter) {
        userFilter.addEventListener('change', function() {
            filterDashboardByUser(this.value);
        });
    }
    
    // Botón de exportar datos
    const exportBtn = document.getElementById('exportData');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportDashboardData();
        });
    }
    
    // Botones de acción en las tablas
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const id = this.dataset.id;
            
            if (action && id) {
                handleTableAction(action, id);
            }
        });
    });
}

/**
 * Inicializar menus desplegables
 */
function initDropdowns() {
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = this.closest('.dropdown');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            // Cerrar todos los dropdown abiertos
            document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
                if (openMenu !== menu) {
                    openMenu.classList.remove('show');
                }
            });
            
            // Alternar el estado del menú actual
            menu.classList.toggle('show');
        });
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Manejar clics en elementos del dropdown
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            const action = this.dataset.action;
            if (action) {
                handleDropdownAction(action, this.dataset);
            }
            
            // Cerrar el menú después de la acción
            this.closest('.dropdown-menu').classList.remove('show');
        });
    });
}

/**
 * Mostrar mensaje de bienvenida con el nombre del usuario
 */
function showWelcomeMessage() {
    const welcomeMessage = document.getElementById('welcomeMessage');
    const username = document.getElementById('userName');
    
    if (welcomeMessage && username) {
        const hour = new Date().getHours();
        let greeting = 'Bienvenido';
        
        if (hour < 12) {
            greeting = 'Buenos días';
        } else if (hour < 18) {
            greeting = 'Buenas tardes';
        } else {
            greeting = 'Buenas noches';
        }
        
        welcomeMessage.textContent = `${greeting}, ${username.textContent}`;
    }
}

/**
 * Recargar datos del dashboard (para botón de actualizar)
 */
function refreshDashboardData() {
    // Mostrar indicador de carga
    document.querySelectorAll('.chart-container').forEach(container => {
        container.classList.add('loading');
    });
    
    // En una implementación real, aquí se llamaría a una API para obtener datos nuevos
    // Por ahora, simularemos una carga con un temporizador
    setTimeout(function() {
        // Actualizar gráficos con nuevos datos
        if (window.activityChart) {
            // Generar nuevos datos aleatorios
            window.activityChart.data.datasets.forEach(dataset => {
                dataset.data = dataset.data.map(() => Math.floor(Math.random() * 50) + 10);
            });
            window.activityChart.update();
        }
        
        if (window.userStatsChart) {
            window.userStatsChart.data.datasets[0].data = window.userStatsChart.data.datasets[0].data.map(
                () => Math.floor(Math.random() * 100) + 10
            );
            window.userStatsChart.update();
        }
        
        // Quitar indicador de carga
        document.querySelectorAll('.chart-container').forEach(container => {
            container.classList.remove('loading');
        });
        
        // Mostrar notificación de actualización
        showNotification('Datos actualizados correctamente', 'success');
    }, 1000);
}

/**
 * Filtrar dashboard por usuario seleccionado
 * @param {string} userId ID del usuario seleccionado
 */
function filterDashboardByUser(userId) {
    if (!userId) return;
    
    // En una implementación real, se llamaría a una API con el ID del usuario
    // Por ahora, solo mostraremos una notificación
    showNotification(`Filtrando por usuario ID: ${userId}`, 'info');
    
    // Simular actualización de datos
    setTimeout(function() {
        if (window.activityChart) {
            // Actualizar con datos ficticios para el usuario seleccionado
            window.activityChart.data.datasets.forEach(dataset => {
                dataset.data = dataset.data.map(() => Math.floor(Math.random() * 30) + 5);
            });
            window.activityChart.update();
        }
        
        // Actualizar estadísticas mostradas
        document.querySelectorAll('.stat-value').forEach(stat => {
            // Reducir los valores para simular filtrado por usuario
            const currentValue = parseInt(stat.textContent.replace(/[^\d]/g, ''));
            const newValue = Math.floor(currentValue * 0.3); // 30% del valor original
            stat.textContent = newValue.toLocaleString();
        });
    }, 500);
}

/**
 * Exportar datos del dashboard
 */
function exportDashboardData() {
    // En una implementación real, esto llamaría a una API para generar el reporte
    // Por ahora, mostraremos un mensaje
    showNotification('Generando reporte para descarga...', 'info');
    
    // Simular tiempo de procesamiento
    setTimeout(function() {
        // Crear un link de descarga ficticio
        const a = document.createElement('a');
        a.href = '#';
        a.download = 'dashboard_report_' + new Date().toISOString().split('T')[0] + '.xlsx';
        a.style.display = 'none';
        
        // En una implementación real, a.href sería una URL a un archivo real
        document.body.appendChild(a);
        
        // Simular clic (en una implementación real esto descargaría el archivo)
        try {
            // a.click(); // Comentado para evitar comportamiento no deseado en el ejemplo
            showNotification('Reporte generado correctamente', 'success');
        } catch (err) {
            showNotification('Error al generar el reporte', 'error');
        } finally {
            document.body.removeChild(a);
        }
    }, 1500);
}

/**
 * Mostrar notificación en la pantalla
 * @param {string} message Mensaje a mostrar
 * @param {string} type Tipo de notificación: 'success', 'error', 'warning', 'info'
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Crear icono según el tipo
    let icon = 'info';
    switch (type) {
        case 'success': icon = 'check_circle'; break;
        case 'error': icon = 'error'; break;
        case 'warning': icon = 'warning'; break;
    }
    
    // Agregar contenido
    notification.innerHTML = `
        <span class="material-icons">${icon}</span>
        <span class="notification-message">${message}</span>
    `;
    
    // Agregar al DOM
    const container = document.querySelector('.notification-container') || document.body;
    container.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto ocultar después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        notification.addEventListener('transitionend', () => {
            notification.remove();
        });
    }, 3000);
}

/**
 * Manejar acciones en tablas
 * @param {string} action Acción a realizar
 * @param {string} id ID del elemento
 */
function handleTableAction(action, id) {
    switch (action) {
        case 'view':
            showNotification(`Viendo detalles del elemento ID: ${id}`, 'info');
            break;
        case 'edit':
            showNotification(`Editando elemento ID: ${id}`, 'info');
            break;
        case 'delete':
            confirmDelete(id);
            break;
        default:
            console.warn(`Acción no reconocida: ${action}`);
    }
}

/**
 * Confirmar eliminación de un elemento
 * @param {string} id ID del elemento a eliminar
 */
function confirmDelete(id) {
    if (confirm(`¿Está seguro de eliminar el elemento con ID: ${id}?`)) {
        // En una implementación real, aquí se llamaría a una API para eliminar
        showNotification(`Elemento ID: ${id} eliminado correctamente`, 'success');
        
        // Opcional: Eliminar la fila de la tabla visualmente
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            row.style.opacity = '0.5';
            setTimeout(() => {
                row.remove();
            }, 500);
        }
    }
}

/**
 * Manejar acciones de menús desplegables
 * @param {string} action Acción seleccionada
 * @param {Object} data Datos adicionales como atributos data-*
 */
function handleDropdownAction(action, data) {
    switch (action) {
        case 'export-csv':
            showNotification('Exportando a CSV...', 'info');
            break;
        case 'export-excel':
            showNotification('Exportando a Excel...', 'info');
            break;
        case 'export-pdf':
            showNotification('Exportando a PDF...', 'info');
            break;
        case 'print':
            window.print();
            break;
        default:
            console.warn(`Acción de dropdown no reconocida: ${action}`);
    }
}

// Agregar estilos para notificaciones
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .notification {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 4px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
            margin-bottom: 10px;
            padding: 12px 16px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            min-width: 250px;
            max-width: 450px;
        }
        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        .notification .material-icons {
            margin-right: 12px;
        }
        .notification-success {
            border-left: 4px solid #34a853;
        }
        .notification-error {
            border-left: 4px solid #ea4335;
        }
        .notification-warning {
            border-left: 4px solid #fbbc05;
        }
        .notification-info {
            border-left: 4px solid #1a73e8;
        }
        .notification-success .material-icons {
            color: #34a853;
        }
        .notification-error .material-icons {
            color: #ea4335;
        }
        .notification-warning .material-icons {
            color: #fbbc05;
        }
        .notification-info .material-icons {
            color: #1a73e8;
        }
        
        /* Estilo para elementos en carga */
        .chart-container.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        .chart-container.loading::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1a73e8;
            animation: spin 1s linear infinite;
            z-index: 11;
        }
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    // Crear contenedor de notificaciones si no existe
    if (!document.querySelector('.notification-container')) {
        const container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
})();