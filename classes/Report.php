<?php
// classes/Report.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Log.php';

class Report {
    private $conn;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->logger = new Log();
    }
    
    /**
     * Obtener resumen de actividad de facturas procesadas
     * @param int|null $userId ID del usuario o null para todos
     * @param string|null $startDate Fecha de inicio (YYYY-MM-DD)
     * @param string|null $endDate Fecha de fin (YYYY-MM-DD)
     * @param string $groupBy Agrupación: 'day', 'week', 'month'
     * @return array Datos del reporte
     */
    public function getActivityReport($userId = null, $startDate = null, $endDate = null, $groupBy = 'day') {
        try {
            $whereClause = array();
            $params = array();
            
            if ($userId !== null) {
                $whereClause[] = "p.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            if ($startDate !== null) {
                $whereClause[] = "p.created_at >= :start_date";
                $params[':start_date'] = $startDate . ' 00:00:00';
            }
            
            if ($endDate !== null) {
                $whereClause[] = "p.created_at <= :end_date";
                $params[':end_date'] = $endDate . ' 23:59:59';
            }
            
            $whereStr = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
            
            // Definir el formato de agrupación según el parámetro
            $groupFormat = "";
            switch ($groupBy) {
                case 'week':
                    $groupFormat = "YEARWEEK(p.created_at, 1)"; // ISO week (week starts on Monday)
                    $dateFormat = "CONCAT(YEAR(p.created_at), '-W', LPAD(WEEK(p.created_at, 1), 2, '0'))"; // Format: YYYY-WNN
                    break;
                case 'month':
                    $groupFormat = "DATE_FORMAT(p.created_at, '%Y-%m')";
                    $dateFormat = "DATE_FORMAT(p.created_at, '%Y-%m')"; // Format: YYYY-MM
                    break;
                case 'day':
                default:
                    $groupFormat = "DATE(p.created_at)";
                    $dateFormat = "DATE(p.created_at)"; // Format: YYYY-MM-DD
                    break;
            }
            
            // Consulta para obtener datos agrupados
            $query = "SELECT 
                        $dateFormat as period,
                        COUNT(p.id) as total_processes,
                        SUM(p.invoice_count) as total_invoices,
                        SUM(p.rows_generated) as total_rows,
                        AVG(p.processing_time) as avg_processing_time
                      FROM processed_invoices p
                      $whereStr
                      GROUP BY $groupFormat
                      ORDER BY $groupFormat DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener totales generales
            $query = "SELECT 
                        COUNT(p.id) as total_processes,
                        SUM(p.invoice_count) as total_invoices,
                        SUM(p.rows_generated) as total_rows,
                        AVG(p.processing_time) as avg_processing_time
                      FROM processed_invoices p
                      $whereStr";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener datos por usuario si no se especificó un usuario concreto
            $userStats = [];
            
            if ($userId === null) {
                $query = "SELECT 
                            u.id, u.username, u.full_name,
                            COUNT(p.id) as total_processes,
                            SUM(p.invoice_count) as total_invoices,
                            SUM(p.rows_generated) as total_rows,
                            AVG(p.processing_time) as avg_processing_time
                          FROM users u
                          LEFT JOIN processed_invoices p ON u.id = p.user_id
                          " . ($whereStr ? str_replace("p.user_id", "u.id", $whereStr) : "") . "
                          GROUP BY u.id
                          ORDER BY total_invoices DESC";
                
                $stmt = $this->conn->prepare($query);
                
                foreach ($params as $key => $value) {
                    if ($key !== ':user_id') { // Excluir user_id si está presente
                        $stmt->bindValue($key, $value);
                    }
                }
                
                $stmt->execute();
                $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return [
                'periods' => $reportData,
                'totals' => $totals,
                'by_user' => $userStats,
                'params' => [
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'group_by' => $groupBy
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error al generar reporte de actividad: " . $e->getMessage());
            return [
                'periods' => [],
                'totals' => [
                    'total_processes' => 0,
                    'total_invoices' => 0,
                    'total_rows' => 0,
                    'avg_processing_time' => 0
                ],
                'by_user' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener reporte detallado de archivos procesados
     * @param int|null $userId ID del usuario o null para todos
     * @param string|null $startDate Fecha de inicio (YYYY-MM-DD)
     * @param string|null $endDate Fecha de fin (YYYY-MM-DD)
     * @param int $page Número de página
     * @param int $recordsPerPage Registros por página
     * @return array Datos del reporte
     */
    public function getDetailedReport($userId = null, $startDate = null, $endDate = null, $page = 1, $recordsPerPage = 20) {
        try {
            $whereClause = array();
            $params = array();
            
            if ($userId !== null) {
                $whereClause[] = "p.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            if ($startDate !== null) {
                $whereClause[] = "p.created_at >= :start_date";
                $params[':start_date'] = $startDate . ' 00:00:00';
            }
            
            if ($endDate !== null) {
                $whereClause[] = "p.created_at <= :end_date";
                $params[':end_date'] = $endDate . ' 23:59:59';
            }
            
            $whereStr = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
            
            // Consulta para obtener el total de registros
            $countQuery = "SELECT COUNT(*) as total FROM processed_invoices p $whereStr";
            $countStmt = $this->conn->prepare($countQuery);
            
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            $countStmt->execute();
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calcular el total de páginas
            $totalPages = ceil($totalRecords / $recordsPerPage);
            $page = max(1, min($page, $totalPages)); // Asegurar que la página está dentro del rango
            $offset = ($page - 1) * $recordsPerPage;
            
            // Consulta para obtener los registros detallados
            $query = "SELECT 
                        p.id, p.filename, p.original_size, p.invoice_count, 
                        p.rows_generated, p.processing_time, p.created_at,
                        u.username, u.full_name
                      FROM processed_invoices p
                      JOIN users u ON p.user_id = u.id
                      $whereStr
                      ORDER BY p.created_at DESC
                      LIMIT :offset, :records_per_page";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':records_per_page', $recordsPerPage, PDO::PARAM_INT);
            
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'records' => $records,
                'total' => $totalRecords,
                'page' => $page,
                'total_pages' => $totalPages,
                'params' => [
                    'user_id' => $userId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'records_per_page' => $recordsPerPage
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error al generar reporte detallado: " . $e->getMessage());
            return [
                'records' => [],
                'total' => 0,
                'page' => $page,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Exportar reporte a formato CSV
     * @param array $reportData Datos del reporte (de getDetailedReport)
     * @return string Ruta al archivo generado o null si falla
     */
    public function exportToCSV($reportData) {
        try {
            if (empty($reportData['records'])) {
                return null;
            }
            
            // Crear nombre de archivo
            $fileName = 'reporte_facturas_' . date('Y-m-d_H-i-s') . '.csv';
            $filePath = '../tmp/' . $fileName;
            
            // Asegurarse de que el directorio existe
            if (!is_dir('../tmp')) {
                mkdir('../tmp', 0755, true);
            }
            
            // Abrir archivo
            $fp = fopen($filePath, 'w');
            
            // Escribir encabezados
            fputcsv($fp, [
                'ID', 'Usuario', 'Nombre', 'Archivo', 'Tamaño Original (bytes)', 
                'Facturas Procesadas', 'Filas Generadas', 
                'Tiempo Procesamiento (s)', 'Fecha y Hora'
            ]);
            
            // Escribir datos
            foreach ($reportData['records'] as $record) {
                fputcsv($fp, [
                    $record['id'],
                    $record['username'],
                    $record['full_name'],
                    $record['filename'],
                    $record['original_size'],
                    $record['invoice_count'],
                    $record['rows_generated'],
                    $record['processing_time'],
                    $record['created_at']
                ]);
            }
            
            fclose($fp);
            
            // Registrar la exportación
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $this->logger->logActivity($userId, 'export_report', "Exportación de reporte: $fileName");
            
            return $filePath;
            
        } catch (Exception $e) {
            error_log("Error al exportar reporte a CSV: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener estadísticas comparativas
     * @param string $period1Start Fecha de inicio del primer período (YYYY-MM-DD)
     * @param string $period1End Fecha de fin del primer período (YYYY-MM-DD)
     * @param string $period2Start Fecha de inicio del segundo período (YYYY-MM-DD)
     * @param string $period2End Fecha de fin del segundo período (YYYY-MM-DD)
     * @param int|null $userId ID del usuario o null para todos
     * @return array Datos de comparación
     */
    public function getComparisonReport($period1Start, $period1End, $period2Start, $period2End, $userId = null) {
        try {
            // Obtener datos del primer período
            $period1 = $this->getActivityReport($userId, $period1Start, $period1End);
            
            // Obtener datos del segundo período
            $period2 = $this->getActivityReport($userId, $period2Start, $period2End);
            
            // Calcular diferencias y porcentajes
            $comparison = [
                'period1' => [
                    'start' => $period1Start,
                    'end' => $period1End,
                    'data' => $period1['totals']
                ],
                'period2' => [
                    'start' => $period2Start,
                    'end' => $period2End,
                    'data' => $period2['totals']
                ],
                'differences' => [],
                'percentages' => []
            ];
            
            // Calcular diferencias
            foreach ($period1['totals'] as $key => $value) {
                if (is_numeric($value)) {
                    $period1Value = (float)$value;
                    $period2Value = (float)($period2['totals'][$key] ?? 0);
                    
                    $comparison['differences'][$key] = $period2Value - $period1Value;
                    
                    // Evitar división por cero
                    if ($period1Value != 0) {
                        $comparison['percentages'][$key] = round(($period2Value - $period1Value) / $period1Value * 100, 2);
                    } else {
                        $comparison['percentages'][$key] = $period2Value > 0 ? 100 : 0;
                    }
                }
            }
            
            return $comparison;
            
        } catch (Exception $e) {
            error_log("Error al generar reporte comparativo: " . $e->getMessage());
            return [
                'period1' => [
                    'start' => $period1Start,
                    'end' => $period1End,
                    'data' => []
                ],
                'period2' => [
                    'start' => $period2Start,
                    'end' => $period2End,
                    'data' => []
                ],
                'differences' => [],
                'percentages' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener métricas de eficiencia por usuario
     * @param string|null $startDate Fecha de inicio (YYYY-MM-DD)
     * @param string|null $endDate Fecha de fin (YYYY-MM-DD)
     * @return array Métricas de eficiencia
     */
    public function getEfficiencyMetrics($startDate = null, $endDate = null) {
        try {
            $whereClause = array();
            $params = array();
            
            if ($startDate !== null) {
                $whereClause[] = "p.created_at >= :start_date";
                $params[':start_date'] = $startDate . ' 00:00:00';
            }
            
            if ($endDate !== null) {
                $whereClause[] = "p.created_at <= :end_date";
                $params[':end_date'] = $endDate . ' 23:59:59';
            }
            
            $whereStr = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
            
            $query = "SELECT 
                        u.id, u.username, u.full_name,
                        COUNT(p.id) as total_processes,
                        SUM(p.invoice_count) as total_invoices,
                        SUM(p.rows_generated) as total_rows,
                        AVG(p.invoice_count) as avg_invoices_per_file,
                        AVG(p.processing_time) as avg_processing_time,
                        SUM(p.invoice_count) / SUM(p.processing_time) as invoices_per_second,
                        SUM(p.rows_generated) / SUM(p.invoice_count) as rows_per_invoice
                      FROM users u
                      JOIN processed_invoices p ON u.id = p.user_id
                      $whereStr
                      GROUP BY u.id
                      ORDER BY invoices_per_second DESC";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular promedios generales
            $query = "SELECT 
                        AVG(p.invoice_count) as avg_invoices_per_file,
                        AVG(p.processing_time) as avg_processing_time,
                        SUM(p.invoice_count) / SUM(p.processing_time) as invoices_per_second,
                        SUM(p.rows_generated) / SUM(p.invoice_count) as rows_per_invoice
                      FROM processed_invoices p
                      $whereStr";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $averages = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'user_metrics' => $metrics,
                'averages' => $averages,
                'params' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener métricas de eficiencia: " . $e->getMessage());
            return [
                'user_metrics' => [],
                'averages' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
?>