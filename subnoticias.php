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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Respuesta preflight para solicitudes CORS
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 3600");
    exit; // No proceses la solicitud en este caso
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Respuesta por defecto
$response = array('status' => 'error', 'message' => 'Acción no válida');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Manejo de solicitud GET (Recuperar datos)
    if (isset($_GET['id'])) {
        // Obtener un usuario por ID
        $id = $_GET['id'];
        $sql = "SELECT ID, nombre, correo FROM subnoticias WHERE ID = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $result->fetch_assoc();
        }
    } else {
        // Obtener todos los usuarios
        $sql = "SELECT ID, nombre, correo FROM subnoticias";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $usuarios = array();
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $usuarios;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear un nuevo usuario)
    $data = json_decode(file_get_contents("php://input"), true);
    $nombre = $data['nombre'];
    $correo = $data['correo'];

    $sql = "INSERT INTO subnoticias (nombre, correo) VALUES ('$nombre', '$correo')";
    
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Usuario creado con éxito';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejo de solicitud PUT (Actualizar un usuario por su ID)
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'];
    $nombre = $data['nombre'];
    $correo = $data['correo'];

    $sql = "UPDATE subnoticias SET nombre = '$nombre', correo = '$correo' WHERE ID = $id";
    
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Usuario actualizado con éxito';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar un usuario por su ID)
    $id = $_GET['id'];
    $sql = "DELETE FROM subnoticias WHERE ID = $id";
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Usuario eliminado con éxito';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
