<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$database = "Ubook";

// Conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $database);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Habilitar CORS solo para tu aplicación Blazor WebAssembly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Respuesta preflight para solicitudes CORS
    header("Access-Control-Allow-Origin: http://localhost:5017");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 3600");
    exit; // No proceses la solicitud en este caso
}

// Permitir solicitudes desde tu aplicación Blazor WebAssembly
header("Access-Control-Allow-Origin: http://localhost:5017");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Respuesta por defecto
$response = array('status' => 'error', 'message' => 'Credenciales incorrectas');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Inicio de sesión)
    $data = json_decode(file_get_contents("php://input"), true);
    $correo = $data['correo'];
    $contrasena = $data['contrasena'];

    // Verificar las credenciales en la base de datos
    $sql = "SELECT ID, dni, nombre, apellido, correo, fecha_nacimiento, lugar_nacimiento, genero, direccion FROM usuario WHERE correo = '$correo' AND contrasena = '$contrasena'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Credenciales válidas, se encontró un usuario
        $usuario = $result->fetch_assoc();
        $response['status'] = 'success';
        $response['message'] = 'Inicio de sesión exitoso';
        $response['data'] = $usuario;
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>