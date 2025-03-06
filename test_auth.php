<?php
// Guarda esto como test_auth.php en la raíz de tu proyecto

// Cargar configuraciones
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/Auth.php';

// Crear instancia de Auth
$auth = new Auth();

// Intentar verificar la contraseña manualmente
$username = 'admin';
$password = 'admin123';

// Obtener el hash de la contraseña de la base de datos
$db = new Database();
$conn = $db->getConnection();
$query = "SELECT id, username, password, is_active FROM users WHERE username = :username";
$stmt = $conn->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Usuario encontrado: " . $user['username'] . "<br>";
    echo "Estado de la cuenta: " . ($user['is_active'] ? 'Activa' : 'Inactiva') . "<br>";
    echo "Hash almacenado: " . $user['password'] . "<br>";
    
    // Verificar contraseña
    $passwordMatch = password_verify($password, $user['password']);
    echo "¿Contraseña coincide?: " . ($passwordMatch ? 'SÍ' : 'NO') . "<br>";
    
    // Probar el método login de Auth
    $loginResult = $auth->login($username, $password);
    echo "Resultado del login: " . ($loginResult ? 'Exitoso' : 'Fallido') . "<br>";
} else {
    echo "Usuario no encontrado";
}