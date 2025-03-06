// assets/js/conversor.js
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let uploadedWorkbook = null;
    let fileName = '';
    
    // Elementos DOM
    const fileInput = document.getElementById('fileInput');
    const uploadArea = document.getElementById('uploadArea');
    const processBtn = document.getElementById('processBtn');
    const loader = document.getElementById('loader');
    const result = document.getElementById('result');
    const errorMsg = document.getElementById('errorMsg');
    const successMsg = document.getElementById('successMsg');
    const fileDetails = document.getElementById('fileDetails');
    const summary = document.getElementById('summary');
    const progressBar = document.getElementById('progressBar');
    const previewHeader = document.getElementById('previewHeader');
    const previewBody = document.getElementById('previewBody');
    const downloadBtn = document.getElementById('downloadBtn');
    
    // Event Listeners
    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('dragleave', handleDragLeave);
    uploadArea.addEventListener('drop', handleDrop);
    fileInput.addEventListener('change', handleFileSelect);
    processBtn.addEventListener('click', processConversion);
    downloadBtn.addEventListener('click', downloadExcel);
    document.getElementById('resetBtn').addEventListener('click', resetProcess);
    
    // Funciones para el manejo de archivos
    function handleDragOver(e) {
        e.preventDefault();
        uploadArea.classList.add('active');
    }
    
    function handleDragLeave(e) {
        e.preventDefault();
        uploadArea.classList.remove('active');
    }
    
    function handleDrop(e) {
        e.preventDefault();
        uploadArea.classList.remove('active');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect();
        }
    }
    
    function handleFileSelect() {
        const file = fileInput.files[0];
        if (!file) return;
        
        // Verificar extensión
        const fileExt = file.name.split('.').pop().toLowerCase();
        if (fileExt !== 'xlsx') {
            showError('Por favor, selecciona un archivo Excel (.xlsx)');
            return;
        }
        
        fileName = file.name;
        fileDetails.textContent = `Archivo seleccionado: ${file.name} (${formatFileSize(file.size)})`;
        uploadArea.classList.add('active');
        
        // Leer el archivo
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                uploadedWorkbook = XLSX.read(data, { type: 'array' });
                
                // Verificar si existe la hoja COLUMNA
                if (!uploadedWorkbook.SheetNames.includes('COLUMNA')) {
                    showError('El archivo debe contener la pestaña "COLUMNA"');
                    return;
                }
                
                hideError();
                showSuccess('Archivo cargado correctamente. Haz clic en "Procesar Conversión".');
                processBtn.disabled = false;
                processBtn.classList.remove('btn-disabled');
            } catch (error) {
                showError('Error al leer el archivo: ' + error.message);
                console.error(error);
            }
        };
        
        reader.onerror = function() {
            showError('Error al leer el archivo');
        };
        
        reader.readAsArrayBuffer(file);
    }
    
    function processConversion() {
        if (!uploadedWorkbook) {
            showError('No se ha cargado ningún archivo');
            return;
        }
        
        hideError();
        hideSuccess();
        loader.style.display = 'block';
        processBtn.disabled = true;
        processBtn.classList.add('btn-disabled');
        
        // Crear FormData para enviar el archivo al servidor
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        
        // Enviar el archivo al servidor para su procesamiento
        fetch('api/process_file.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Error en el servidor');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Actualizar el resumen
                summary.innerHTML = `
                    <p><strong>Facturas procesadas:</strong> ${data.statistics.invoice_count}</p>
                    <p><strong>Filas generadas:</strong> ${data.statistics.rows_generated}</p>
                    <p><strong>Tiempo de procesamiento:</strong> ${data.statistics.processing_time} segundos</p>
                `;
                
                // Actualizar la vista previa
                updatePreview(data.preview);
                
                // Configurar botón de descarga
                downloadBtn.setAttribute('data-file', data.file.download_url);
                
                // Mostrar los resultados
                loader.style.display = 'none';
                result.style.display = 'block';
                showSuccess('Conversión completada correctamente');
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        })
        .catch(error => {
            loader.style.display = 'none';
            processBtn.disabled = false;
            processBtn.classList.remove('btn-disabled');
            showError('Error durante la conversión: ' + error.message);
            console.error(error);
        });
    }
    
    function updatePreview(data) {
        // Limpiar la vista previa
        previewHeader.innerHTML = '';
        previewBody.innerHTML = '';
        
        // Agregar encabezados
        if (data.length > 0) {
            data[0].forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                previewHeader.appendChild(th);
            });
        }
        
        // Agregar datos (saltando la primera fila que son los encabezados)
        for (let i = 1; i < data.length; i++) {
            const tr = document.createElement('tr');
            
            data[i].forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell !== null ? cell : '';
                tr.appendChild(td);
            });
            
            previewBody.appendChild(tr);
        }
    }
    
    function downloadExcel() {
        const downloadUrl = downloadBtn.getAttribute('data-file');
        if (downloadUrl) {
            window.location.href = downloadUrl;
        } else {
            showError('No hay archivo disponible para descargar');
        }
    }
    
    // Funciones de utilidad
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function showError(message) {
        errorMsg.textContent = message;
        errorMsg.style.display = 'block';
        successMsg.style.display = 'none';
    }
    
    function hideError() {
        errorMsg.style.display = 'none';
    }
    
    function showSuccess(message) {
        successMsg.textContent = message;
        successMsg.style.display = 'block';
        errorMsg.style.display = 'none';
    }
    
    function hideSuccess() {
        successMsg.style.display = 'none';
    }
    
    function resetProcess() {
        // Reiniciar variables
        uploadedWorkbook = null;
        fileName = '';
        
        // Reiniciar interfaz
        fileInput.value = '';
        fileDetails.textContent = '';
        uploadArea.classList.remove('active');
        
        // Ocultar resultados y mensajes
        result.style.display = 'none';
        hideError();
        hideSuccess();
        
        // Reiniciar tabla de vista previa
        previewHeader.innerHTML = '';
        previewBody.innerHTML = '';
        
        // Reiniciar progreso
        progressBar.style.width = '0%';
        
        // Desactivar botón de proceso
        processBtn.disabled = true;
        processBtn.classList.add('btn-disabled');
        
        // Mostrar mensaje de reinicio
        showSuccess('Proceso reiniciado. Por favor, carga un nuevo archivo.');
        
        // Hacer scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});