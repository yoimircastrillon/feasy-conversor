/**
 * login.js
 * Script para manejar la validación y funcionalidad de la página de inicio de sesión
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initLoginForm();
    setupPasswordVisibility();
    setupRememberMe();
    handleUrlParameters();
    setupAutoFocus();
});

/**
 * Inicializar y validar el formulario de inicio de sesión
 */
function initLoginForm() {
    const loginForm = document.getElementById('loginForm');
    
    if (!loginForm) return;
    
    loginForm.addEventListener('submit', function(e) {
        // Prevenir envío del formulario por defecto para hacer validación primero
        e.preventDefault();
        
        // Obtener referencias a los campos
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const errorContainer = document.getElementById('errorMsg');
        
        // Limpiar errores previos
        clearErrors();
        
        // Validar campos
        let isValid = true;
        
        if (!usernameInput.value.trim()) {
            showFieldError(usernameInput, 'El nombre de usuario es requerido');
            isValid = false;
        }
        
        if (!passwordInput.value) {
            showFieldError(passwordInput, 'La contraseña es requerida');
            isValid = false;
        }
        
        // Si hay errores, no continuar
        if (!isValid) {
            return false;
        }
        
        // Mostrar indicador de carga
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Iniciando sesión...';
        
        // En una implementación real, el formulario se enviaría normalmente
        // Aquí simulamos un retraso para mostrar la animación de carga
        if (window.submitForm === false) { // Para pruebas, evitar envío real
            setTimeout(function() {
                // Restablecer botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                // Mostrar éxito para demostración
                if (errorContainer) {
                    errorContainer.innerHTML = ''; // Limpiar mensajes previos
                }
                showSuccessMessage('Inicio de sesión exitoso. Redirigiendo...');
                
                // Simular redirección
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 1000);
            }, 1500);
        } else {
            // Envío real del formulario
            this.submit();
        }
    });
}

/**
 * Configurar visibilidad de contraseña
 */
function setupPasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    
    if (!passwordInput || !toggleBtn) return;
    
    toggleBtn.addEventListener('click', function() {
        // Cambiar tipo de campo entre password y text
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Cambiar ícono según estado
        this.innerHTML = type === 'password' 
            ? '<span class="material-icons">visibility</span>'
            : '<span class="material-icons">visibility_off</span>';
        
        // Dar foco al campo de contraseña
        passwordInput.focus();
    });
}

/**
 * Configurar funcionalidad de "Recordarme"
 */
function setupRememberMe() {
    const rememberCheck = document.getElementById('rememberMe');
    
    if (!rememberCheck) return;
    
    // Cargar estado guardado
    const remembered = localStorage.getItem('remember_username');
    if (remembered) {
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.value = remembered;
            rememberCheck.checked = true;
        }
    }
    
    // Guardar preferencia al cambiar
    rememberCheck.addEventListener('change', function() {
        if (!this.checked) {
            localStorage.removeItem('remember_username');
        }
    });
    
    // Cuando se envía el formulario, guardar el nombre de usuario si la opción está marcada
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const usernameInput = document.getElementById('username');
            if (rememberCheck.checked && usernameInput && usernameInput.value) {
                localStorage.setItem('remember_username', usernameInput.value);
            }
        });
    }
}

/**
 * Manejar parámetros de URL para mostrar mensajes
 */
function handleUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Manejar errores
    if (urlParams.has('error')) {
        const errorType = urlParams.get('error');
        let errorMessage = '';
        
        switch (errorType) {
            case 'invalid_credentials':
                errorMessage = 'Usuario o contraseña incorrectos.';
                break;
            case 'account_disabled':
                errorMessage = 'Su cuenta ha sido desactivada. Contacte con el administrador.';
                break;
            case 'session_timeout':
                errorMessage = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
                break;
            case 'account_not_found':
                errorMessage = 'La cuenta no existe o ha sido eliminada.';
                break;
            default:
                errorMessage = 'Ocurrió un error al intentar iniciar sesión. Por favor, inténtelo de nuevo.';
        }
        
        showErrorMessage(errorMessage);
    }
    
    // Manejar mensajes de éxito
    if (urlParams.has('success')) {
        const successType = urlParams.get('success');
        let successMessage = '';
        
        switch (successType) {
            case 'logout':
                successMessage = 'Ha cerrado sesión correctamente.';
                break;
            case 'password_reset':
                successMessage = 'Su contraseña ha sido restablecida. Por favor, inicie sesión con su nueva contraseña.';
                break;
            default:
                successMessage = 'Operación completada con éxito.';
        }
        
        showSuccessMessage(successMessage);
    }
}

/**
 * Configurar autoenfoque en el primer campo del formulario
 */
function setupAutoFocus() {
    // Dar foco al campo de usuario si está vacío, de lo contrario al campo de contraseña
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    if (usernameInput && passwordInput) {
        if (!usernameInput.value) {
            usernameInput.focus();
        } else {
            passwordInput.focus();
        }
    }
}

/**
 * Mostrar errores de campo
 * @param {HTMLElement} field Campo con error
 * @param {string} message Mensaje de error
 */
function showFieldError(field, message) {
    // Añadir clase de error al campo
    field.classList.add('error');
    
    // Crear y mostrar mensaje de error
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    // Insertar después del campo
    field.parentNode.insertBefore(errorElement, field.nextSibling);
    
    // Quitar mensaje cuando el usuario comience a escribir
    field.addEventListener('input', function() {
        this.classList.remove('error');
        if (errorElement.parentNode) {
            errorElement.parentNode.removeChild(errorElement);
        }
    }, { once: true });
}

/**
 * Limpiar todos los errores del formulario
 */
function clearErrors() {
    // Quitar clases de error de los campos
    document.querySelectorAll('.error').forEach(function(element) {
        element.classList.remove('error');
    });
    
    // Eliminar mensajes de error de campos
    document.querySelectorAll('.field-error').forEach(function(element) {
        element.parentNode.removeChild(element);
    });
    
    // Limpiar mensaje de error general
    const errorContainer = document.getElementById('errorMsg');
    if (errorContainer) {
        errorContainer.innerHTML = '';
        errorContainer.style.display = 'none';
    }
    
    // Limpiar mensaje de éxito
    const successContainer = document.getElementById('successMsg');
    if (successContainer) {
        successContainer.innerHTML = '';
        successContainer.style.display = 'none';
    }
}

/**
 * Mostrar mensaje de error general
 * @param {string} message Mensaje de error
 */
function showErrorMessage(message) {
    const errorContainer = document.getElementById('errorMsg');
    if (errorContainer) {
        errorContainer.innerHTML = `
            <span class="material-icons">error</span>
            <span>${message}</span>
        `;
        errorContainer.style.display = 'flex';
        
        // Scroll al mensaje si está fuera de la vista
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/**
 * Mostrar mensaje de éxito
 * @param {string} message Mensaje de éxito
 */
function showSuccessMessage(message) {
    const successContainer = document.getElementById('successMsg');
    if (successContainer) {
        successContainer.innerHTML = `
            <span class="material-icons">check_circle</span>
            <span>${message}</span>
        `;
        successContainer.style.display = 'flex';
        
        // Scroll al mensaje si está fuera de la vista
        successContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Agregar estilos para elementos adicionales
(function() {
    const style = document.createElement('style');
    style.textContent = `
        /* Estilos para campos con error */
        .error {
            border-color: #ea4335 !important;
            box-shadow: 0 0 0 1px #ea4335 !important;
        }
        
        .field-error {
            color: #ea4335;
            font-size: 0.75rem;
            margin-top: 4px;
            display: block;
        }
        
        /* Contenedores de mensajes */
        #errorMsg, #successMsg {
            display: none;
            align-items: center;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        #errorMsg {
            background-color: rgba(234, 67, 53, 0.1);
            border-left: 4px solid #ea4335;
            color: #d32f2f;
        }
        
        #successMsg {
            background-color: rgba(15, 157, 88, 0.1);
            border-left: 4px solid #0f9d58;
            color: #0b8a4b;
        }
        
        #errorMsg .material-icons, #successMsg .material-icons {
            margin-right: 8px;
        }
        
        /* Botón para mostrar/ocultar contraseña */
        .password-container {
            position: relative;
        }
        
        #togglePassword {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #5f6368;
            display: flex;
            align-items: center;
            justify-content: center;
            outline: none;
        }
        
        #togglePassword:hover {
            color: #1a73e8;
        }
        
        /* Estilos para el contenedor de "Recordarme" */
        .remember-container {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .remember-container input {
            margin-right: 8px;
        }
        
        /* Animación de carga */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Animaciones */
        .login-container {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Efecto de enfoque mejorado */
        .form-control:focus {
            transition: all 0.2s ease;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 115, 232, 0.1), 0 0 0 2px rgba(26, 115, 232, 0.2) !important;
        }
    `;
    document.head.appendChild(style);
})();