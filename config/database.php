<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'compras_feasy';
    private $username = 'root2';
    private $password = 'Bogota2024*';
    private $conn;
    
    // Método para conectar a la base de datos
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
        }
        
        return $this->conn;
    }
}
?>