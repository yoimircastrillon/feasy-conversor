<?php
// Guarda este archivo como reset_password.php en la raíz del proyecto

// Cargar configuraciones
require_once 'config/config.php';
require_once 'config/database.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Configuración para el usuario admin
$username = 'admin';
$new_password = 'admin123';

// Generar un nuevo hash de contraseña
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Mostrar información para depuración
echo "Generando nueva contraseña para el usuario: " . $username . "<br>";
echo "Nueva contraseña: " . $new_password . "<br>";
echo "Nuevo hash generado: " . $new_hash . "<br>";

// Actualizar la contraseña en la base de datos
$query = "UPDATE users SET password = :password WHERE username = :username";
$stmt = $conn->prepare($query);
$stmt->bindParam(':password', $new_hash);
$stmt->bindParam(':username', $username);

if ($stmt->execute()) {
    echo "<br><strong>¡Éxito!</strong> La contraseña se ha actualizado correctamente.<br>";
    echo "Ahora intenta iniciar sesión con:<br>";
    echo "Usuario: " . $username . "<br>";
    echo "Contraseña: " . $new_password;
} else {
    echo "<br><strong>Error:</strong> No se pudo actualizar la contraseña.";
}

// Verificar que el hash nuevo funciona
echo "<br><br>Verificando el nuevo hash:<br>";
$verify_result = password_verify($new_password, $new_hash);
echo "¿La verificación funciona? " . ($verify_result ? "SÍ" : "NO");