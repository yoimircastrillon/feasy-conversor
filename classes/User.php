<?php
// classes/User.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Log.php';

class User {
    private $conn;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->logger = new Log();
    }
    
    /**
     * Obtener todos los usuarios
     * @return array Lista de usuarios
     */
    public function getAllUsers() {
        try {
            $query = "SELECT id, username, full_name, email, role, is_active, created_at, updated_at 
                      FROM users 
                      ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener un usuario por su ID
     * @param int $id ID del usuario
     * @return array|bool Datos del usuario o false si no existe
     */
    public function getUserById($id) {
        try {
            $query = "SELECT id, username, full_name, email, role, is_active, created_at, updated_at 
                      FROM users 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al obtener usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear un nuevo usuario
     * @param array $userData Datos del usuario a crear
     * @return bool Resultado de la operación
     */
    public function createUser($userData) {
        try {
            // Validar si el usuario ya existe
            $query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return false; // Usuario o email ya existe
            }
            
            // Encriptar la contraseña
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insertar el nuevo usuario
            $query = "INSERT INTO users (username, password, full_name, email, role, is_active) 
                      VALUES (:username, :password, :full_name, :email, :role, :is_active)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':role', $userData['role']);
            
            $isActive = isset($userData['is_active']) ? $userData['is_active'] : 1;
            $stmt->bindParam(':is_active', $isActive);
            
            if ($stmt->execute()) {
                $userId = $this->conn->lastInsertId();
                $this->logger->logActivity($_SESSION['user_id'], 'user_created', "Usuario {$userData['username']} creado");
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar un usuario existente
     * @param int $id ID del usuario
     * @param array $userData Datos del usuario a actualizar
     * @return bool Resultado de la operación
     */
    public function updateUser($id, $userData) {
        try {
            // Verificar que el usuario existe
            $query = "SELECT id FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return false; // Usuario no existe
            }
            
            // Verificar que el username y email no están en uso por otro usuario
            $query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return false; // Username o email ya existe en otro usuario
            }
            
            // Preparar la consulta base de actualización
            $query = "UPDATE users SET 
                      username = :username, 
                      full_name = :full_name, 
                      email = :email, 
                      role = :role, 
                      is_active = :is_active 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':is_active', $userData['is_active']);
            $stmt->bindParam(':id', $id);
            
            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Si se proporcionó una nueva contraseña, actualizarla por separado
                if (!empty($userData['password'])) {
                    $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
                    
                    $query = "UPDATE users SET password = :password WHERE id = :id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':password', $passwordHash);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                }
                
                $this->logger->logActivity($_SESSION['user_id'], 'user_updated', "Usuario {$userData['username']} actualizado");
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cambiar el estado de un usuario (activar/desactivar)
     * @param int $id ID del usuario
     * @param bool $status Nuevo estado (true=activo, false=inactivo)
     * @return bool Resultado de la operación
     */
    public function changeUserStatus($id, $status) {
        try {
            $query = "UPDATE users SET is_active = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $statusInt = $status ? 1 : 0;
            $stmt->bindParam(':status', $statusInt);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $action = $status ? 'user_activated' : 'user_deactivated';
                $this->logger->logActivity($_SESSION['user_id'], $action, "Usuario ID $id " . ($status ? 'activado' : 'desactivado'));
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al cambiar estado de usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar un usuario
     * @param int $id ID del usuario
     * @return bool Resultado de la operación
     */
    public function deleteUser($id) {
        try {
            // Primero obtener el nombre de usuario para el registro
            $query = "SELECT username FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return false; // Usuario no existe
            }
            
            $username = $stmt->fetch(PDO::FETCH_ASSOC)['username'];
            
            // Eliminar el usuario
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $this->logger->logActivity($_SESSION['user_id'], 'user_deleted', "Usuario $username eliminado");
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener el número total de usuarios
     * @return int Número de usuarios
     */
    public function getUserCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM users";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (PDOException $e) {
            error_log("Error al contar usuarios: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener los usuarios más recientes
     * @param int $limit Número de usuarios a obtener
     * @return array Lista de usuarios
     */
    public function getRecentUsers($limit = 5) {
        try {
            $query = "SELECT id, username, full_name, email, role, is_active, created_at 
                      FROM users 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al obtener usuarios recientes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar usuarios por nombre o email
     * @param string $searchTerm Término de búsqueda
     * @return array Lista de usuarios que coinciden
     */
    public function searchUsers($searchTerm) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            
            $query = "SELECT id, username, full_name, email, role, is_active, created_at 
                      FROM users 
                      WHERE username LIKE :term OR full_name LIKE :term OR email LIKE :term
                      ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':term', $searchTerm);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al buscar usuarios: " . $e->getMessage());
            return [];
        }
    }
}
?>