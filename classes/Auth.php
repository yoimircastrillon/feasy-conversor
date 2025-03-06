<?php
// classes/Auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Log.php';

class Auth {
    private $conn;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->logger = new Log();
    }
    
    /**
     * Autenticar usuario
     * @param string $username Nombre de usuario
     * @param string $password Contraseña sin encriptar
     * @return array|boolean Datos del usuario o false si falla la autenticación
     */
    public function login($username, $password) {
        try {
            // Preparar la consulta
            $query = "SELECT id, username, password, full_name, email, role, is_active 
                      FROM users 
                      WHERE username = :username";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si la cuenta está activa
                if (!$user['is_active']) {
                    $this->logger->logActivity(null, 'login_attempt', "Intento de acceso a cuenta inactiva: $username");
                    return false;
                }
                
                // Verificar la contraseña
                if (password_verify($password, $user['password'])) {
                    // Registrar actividad de inicio de sesión
                    $this->logger->logActivity($user['id'], 'login', 'Inicio de sesión exitoso');
                    
                    // Eliminar la contraseña del array antes de devolverlo
                    unset($user['password']);
                    return $user;
                }
            }
            
            // Registrar intento fallido
            $this->logger->logActivity(null, 'login_failed', "Intento fallido para el usuario: $username");
            return false;
            
        } catch (PDOException $e) {
            error_log("Error en la autenticación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear un nuevo usuario
     * @param array $userData Datos del usuario a crear
     * @return boolean Resultado de la operación
     */
    public function register($userData) {
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
            $stmt->bindParam(':is_active', $userData['is_active']);
            
            if ($stmt->execute()) {
                $userId = $this->conn->lastInsertId();
                $this->logger->logActivity($userId, 'user_created', "Usuario {$userData['username']} creado");
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al registrar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el usuario tiene una sesión activa
     * @return boolean Estado de la sesión
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Verificar si el usuario tiene permisos de administrador
     * @return boolean Es administrador o no
     */
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Cerrar la sesión del usuario
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $this->logger->logActivity($userId, 'logout', 'Cierre de sesión');
            
            // Limpiar todas las variables de sesión
            $_SESSION = array();
            
            // Destruir la cookie de sesión
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Destruir la sesión
            session_destroy();
        }
    }
    
    /**
     * Cambiar la contraseña de un usuario
     * @param int $userId ID del usuario
     * @param string $newPassword Nueva contraseña sin encriptar
     * @return boolean Resultado de la operación
     */
    public function changePassword($userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                $this->logger->logActivity($userId, 'password_changed', 'Contraseña actualizada');
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Error al cambiar contraseña: " . $e->getMessage());
            return false;
        }
    }
}
?>