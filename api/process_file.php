<?php
// api/process_file.php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Log.php';

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

$logger = new Log();

// Verificar si se ha cargado un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se ha cargado ningún archivo o ha ocurrido un error']);
    exit;
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];

// Verificar extensión
$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
if (strtolower($fileExt) !== 'xlsx') {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo debe ser un archivo Excel (.xlsx)']);
    exit;
}

// Procesar el archivo XLSX con la biblioteca PHPSpreadsheet
require_once '../vendor/autoload.php';

try {
    $startTime = microtime(true);
    
    // Utilizar PHPSpreadsheet en lugar de la versión JS
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
    
    // Verificar si existe la hoja COLUMNA
    $sheetNames = $spreadsheet->getSheetNames();
    if (!in_array('COLUMNA', $sheetNames)) {
        throw new Exception('El archivo debe contener la pestaña "COLUMNA"');
    }
    
    // Obtener los datos de la hoja COLUMNA
    $worksheet = $spreadsheet->getSheetByName('COLUMNA');
    $columnaData = $worksheet->toArray(null, true, true, true);
    
    // Encontrar la fila de encabezados en COLUMNA
    $headerRowIndex = null;
    foreach ($columnaData as $index => $row) {
        if (in_array('NIT', $row)) {
            $headerRowIndex = $index;
            break;
        }
    }
    
    if ($headerRowIndex === null) {
        throw new Exception('No se encontró la fila de encabezados en la hoja COLUMNA');
    }
    
    // Convertir a array asociativo con claves de letras a índices numéricos
    $headers = $columnaData[$headerRowIndex];
    $headerIndexes = [];
    foreach ($headers as $col => $header) {
        $headerIndexes[$header] = $col;
    }
    
    // Definir encabezados para la hoja IMPORT
    $importHeaders = [
        "journal_id",
        "partner_id",
        "ref",
        "invoice_date",
        "invoice_line_ids/product_id",
        "invoice_line_ids/price_unit",
        "invoice_line_ids/tax_ids",
        "cufe_cuds_other_system"
    ];
    
    // Definir los tipos de items que se buscarán
    $itemTypes = ["ITEM EXCL", "ITEM 19%", "ITEM 5%", "ITEM IC", "ITEM IBUA", "ITEM ICUI", "ITEM OTRO"];
    
    // Mapeo de impuestos según el tipo de ítem
    $taxMapping = [
        "ITEM EXCL" => "0% Excluido Compras,2.5% RteFte Comp Dec",
        "ITEM 19%" => "19% IVA Compras,2.5% RteFte Comp Dec",
        "ITEM 5%" => "5% IVA Compras,2.5% RteFte Comp Dec",
        "ITEM IC" => "0% Excluido Compras",
        "ITEM IBUA" => "0% Excluido Compras",
        "ITEM ICUI" => "0% Excluido Compras",
        "ITEM OTRO" => "0% Excluido Compras"
    ];
    
    // Verificar que existan las columnas requeridas
    $requiredColumns = ["NIT", "Proveedor", "CONSECUTIVO", "Fecha", "CUFE"];
    foreach ($requiredColumns as $column) {
        if (!isset($headerIndexes[$column])) {
            throw new Exception("Falta la columna requerida: $column");
        }
    }
    
    // Extraer las filas de datos (sin encabezados)
    $dataRows = array_slice($columnaData, $headerRowIndex);
    
    // Crear nueva estructura para IMPORT
    $newImportData = [];
    $newImportData[] = $importHeaders;
    
    // Estadísticas para el registro
    $totalInvoices = 0;
    $totalRowsGenerated = 0;
    
    // Procesar cada fila de datos
    foreach (array_slice($dataRows, 1) as $row) {
        // Verificar si la fila tiene datos
        if (empty($row[$headerIndexes['NIT']])) {
            continue;
        }
        
        // Campos comunes para todas las filas de esta factura
        $commonFields = [
            'journal_id' => "Facturas de Proveedores",
            'partner_id' => $row[$headerIndexes['Proveedor']],
            'ref' => $row[$headerIndexes['CONSECUTIVO']],
            'invoice_date' => formatDate($row[$headerIndexes['Fecha']]),
            'cufe' => $row[$headerIndexes['CUFE']]
        ];
        
        // Para cada tipo de producto, crear una fila si tiene valor
        $isFirstRow = true;
        $rowsForThisInvoice = 0;
        
        foreach ($itemTypes as $itemType) {
            if (isset($headerIndexes[$itemType]) && !empty($row[$headerIndexes[$itemType]])) {
                $newRow = [];
                
                if ($isFirstRow) {
                    // Primera fila incluye todos los campos comunes
                    $newRow[] = $commonFields['journal_id'];
                    $newRow[] = $commonFields['partner_id'];
                    $newRow[] = $commonFields['ref'];
                    $newRow[] = $commonFields['invoice_date'];
                    $isFirstRow = false;
                } else {
                    // Filas subsiguientes tienen nulos en los campos comunes
                    $newRow[] = null;
                    $newRow[] = null;
                    $newRow[] = null;
                    $newRow[] = null;
                }
                
                // Agregar los campos específicos de cada producto
                $newRow[] = $itemType; // Nombre del producto (tipo de item)
                $newRow[] = $row[$headerIndexes[$itemType]]; // Valor del producto
                $newRow[] = $taxMapping[$itemType]; // Impuestos correspondientes
                
                // Agregar CUFE solo en la primera fila
                if ($newRow[0] !== null) {
                    $newRow[] = $commonFields['cufe'];
                } else {
                    $newRow[] = null;
                }
                
                $newImportData[] = $newRow;
                $rowsForThisInvoice++;
                $totalRowsGenerated++;
            }
        }
        
        if ($rowsForThisInvoice > 0) {
            $totalInvoices++;
        }
    }
    
    // Crear nuevo libro de Excel
    $newSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $newSheet = $newSpreadsheet->getActiveSheet();
    $newSheet->setTitle('IMPORT');
    
    // Agregar los datos a la hoja
    $row = 1;
    foreach ($newImportData as $rowData) {
        $col = 1;
        foreach ($rowData as $cellValue) {
            $newSheet->setCellValueByColumnAndRow($col, $row, $cellValue);
            $col++;
        }
        $row++;
    }
    
    // Crear el nombre del archivo de salida
    $outputFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_formato_import.xlsx';
    $outputFilePath = '../tmp/' . $outputFileName;
    
    // Asegurarse de que el directorio tmp existe
    if (!is_dir('../tmp')) {
        mkdir('../tmp', 0755, true);
    }
    
    // Guardar el archivo
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($newSpreadsheet, 'Xlsx');
    $writer->save($outputFilePath);
    
    $endTime = microtime(true);
    $processingTime = $endTime - $startTime;
    
    // Registrar la operación en la base de datos
    $logger->logProcessedInvoice(
        $_SESSION['user_id'],
        $fileName,
        $fileSize,
        $totalInvoices,
        $totalRowsGenerated,
        $processingTime
    );
    
    // Devolver resultado exitoso
    echo json_encode([
        'success' => true,
        'message' => 'Archivo procesado correctamente',
        'file' => [
            'name' => $outputFileName,
            'path' => $outputFilePath,
            'download_url' => str_replace('../', '../api/download.php?file=', $outputFilePath)
        ],
        'statistics' => [
            'invoice_count' => $totalInvoices,
            'rows_generated' => $totalRowsGenerated,
            'processing_time' => round($processingTime, 2)
        ],
        'preview' => array_slice($newImportData, 0, 10) // Primeras 10 filas para vista previa
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar el archivo: ' . $e->getMessage()]);
    
    // Registrar el error
    $logger->logActivity($_SESSION['user_id'], 'process_error', 'Error al procesar archivo: ' . $e->getMessage());
}

/**
 * Formatear fecha desde diversos formatos a YYYY-MM-DD
 * @param mixed $date Fecha en cualquier formato
 * @return string|null Fecha formateada o null
 */
function formatDate($date) {
    if (empty($date)) {
        return null;
    }
    
    // Si es string con formato DD-MM-YYYY
    if (is_string($date) && strpos($date, '-') !== false) {
        $parts = explode('-', $date);
        if (count($parts) === 3) {
            return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
        }
    }
    
    // Si es un objeto DateTime de PHPSpreadsheet
    if ($date instanceof \PhpOffice\PhpSpreadsheet\Shared\Date) {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
    }
    
    // Si es un número (fecha de Excel)
    if (is_numeric($date)) {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
    }
    
    // Intentar parsear el string como fecha
    $timestamp = strtotime($date);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return $date;
}
?>