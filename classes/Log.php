<?php
// classes/Log.php
require_once __DIR__ . '/../config/database.php';

class Log {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Registrar una actividad en el sistema
     * @param int|null $userId ID del usuario (puede ser null para usuarios no autenticados)
     * @param string $action Tipo de acción realizada
     * @param string $description Descripción detallada de la acción
     * @return boolean Resultado de la operación
     */
    public function logActivity($userId, $action, $description = '') {
        try {
            $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                      VALUES (:user_id, :action, :description, :ip_address, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            
            // Obtener IP y agente de usuario
            $ipAddress = $this->getClientIp();
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
            
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error al registrar actividad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener logs del sistema con paginación
     * @param int $userId ID de usuario específico o null para todos
     * @param int $page Número de página
     * @param int $recordsPerPage Registros por página
     * @return array Logs encontrados
     */
    public function getLogs($userId = null, $page = 1, $recordsPerPage = 20) {
        try {
            $start = ($page - 1) * $recordsPerPage;
            
            $whereClause = "";
            $params = array();
            
            if ($userId !== null) {
                $whereClause = "WHERE user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            // Consulta para obtener el total de registros
            $countQuery = "SELECT COUNT(*) as total FROM activity_logs $whereClause";
            $countStmt = $this->conn->prepare($countQuery);
            
            if ($userId !== null) {
                $countStmt->bindParam(':user_id', $userId);
            }
            
            $countStmt->execute();
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Consulta para obtener los registros con paginación
            $query = "SELECT l.*, u.username 
                      FROM activity_logs l
                      LEFT JOIN users u ON l.user_id = u.id
                      $whereClause
                      ORDER BY l.created_at DESC
                      LIMIT :start, :records_per_page";
            
            $stmt = $this->conn->prepare($query);
            
            if ($userId !== null) {
                $stmt->bindParam(':user_id', $userId);
            }
            
            $stmt->bindParam(':start', $start, PDO::PARAM_INT);
            $stmt->bindParam(':records_per_page', $recordsPerPage, PDO::PARAM_INT);
            $stmt->execute();
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'logs' => $logs,
                'total' => $totalRecords,
                'pages' => ceil($totalRecords / $recordsPerPage),
                'current_page' => $page
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener logs: " . $e->getMessage());
            return [
                'logs' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $page
            ];
        }
    }
    
    /**
     * Registrar factura procesada
     * @param int $userId ID del usuario
     * @param string $filename Nombre del archivo
     * @param int $originalSize Tamaño del archivo original
     * @param int $invoiceCount Número de facturas procesadas
     * @param int $rowsGenerated Número de filas generadas
     * @param float $processingTime Tiempo de procesamiento en segundos
     * @return boolean Resultado de la operación
     */
    public function logProcessedInvoice($userId, $filename, $originalSize, $invoiceCount, $rowsGenerated, $processingTime) {
        try {
            $query = "INSERT INTO processed_invoices 
                      (user_id, filename, original_size, invoice_count, rows_generated, processing_time) 
                      VALUES (:user_id, :filename, :original_size, :invoice_count, :rows_generated, :processing_time)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':original_size', $originalSize);
            $stmt->bindParam(':invoice_count', $invoiceCount);
            $stmt->bindParam(':rows_generated', $rowsGenerated);
            $stmt->bindParam(':processing_time', $processingTime);
            
            if ($stmt->execute()) {
                // Registrar también como actividad
                $this->logActivity($userId, 'process_file', "Archivo procesado: $filename, Facturas: $invoiceCount");
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al registrar facturas procesadas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de facturas procesadas
     * @param int $userId ID del usuario o null para todos
     * @param string $startDate Fecha de inicio (YYYY-MM-DD)
     * @param string $endDate Fecha de fin (YYYY-MM-DD)
     * @return array Estadísticas encontradas
     */
    public function getInvoiceStatistics($userId = null, $startDate = null, $endDate = null) {
        try {
            $whereClause = array();
            $params = array();
            
            if ($userId !== null) {
                $whereClause[] = "user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            if ($startDate !== null) {
                $whereClause[] = "created_at >= :start_date";
                $params[':start_date'] = $startDate . ' 00:00:00';
            }
            
            if ($endDate !== null) {
                $whereClause[] = "created_at <= :end_date";
                $params[':end_date'] = $endDate . ' 23:59:59';
            }
            
            $whereStr = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
            
            // Obtener totales
            $query = "SELECT 
                        COUNT(*) as total_processes,
                        SUM(invoice_count) as total_invoices,
                        SUM(rows_generated) as total_rows,
                        AVG(processing_time) as avg_processing_time
                      FROM processed_invoices
                      $whereStr";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener datos por día
            $query = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as processes,
                        SUM(invoice_count) as invoices,
                        SUM(rows_generated) as rows
                      FROM processed_invoices
                      $whereStr
                      GROUP BY DATE(created_at)
                      ORDER BY DATE(created_at) DESC
                      LIMIT 30";
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener datos por usuario (solo para administradores)
            $userStats = [];
            
            if ($userId === null) {
                $query = "SELECT 
                            u.id, u.username, u.full_name,
                            COUNT(p.id) as processes,
                            SUM(p.invoice_count) as invoices,
                            SUM(p.rows_generated) as rows
                          FROM users u
                          LEFT JOIN processed_invoices p ON u.id = p.user_id
                          GROUP BY u.id
                          ORDER BY invoices DESC";
                
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return [
                'totals' => $totals,
                'daily' => $dailyStats,
                'by_user' => $userStats
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return [
                'totals' => [
                    'total_processes' => 0,
                    'total_invoices' => 0,
                    'total_rows' => 0,
                    'avg_processing_time' => 0
                ],
                'daily' => [],
                'by_user' => []
            ];
        }
    }
    
    /**
     * Obtener la dirección IP del cliente
     * @return string Dirección IP
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
?>