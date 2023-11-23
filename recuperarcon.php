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
$response = array('status' => 'error', 'message' => 'Acción no válida');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Consultar un usuario por correo)
    $data = json_decode(file_get_contents("php://input"), true);
    $correo = $data['correo'];

    $sql = "SELECT ID, pregunta FROM usuario WHERE correo = '$correo'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response['status'] = 'success';
        $response['message'] = '';
        $response['data'] = $result->fetch_assoc();
    }else {
	    $response['message'] = 'El correo no existe';
    }
}elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Recibir datos JSON del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['ID'])) {
        $id = $data['ID'];
    }
    if (isset($data['id'])) {
        $id = $data['id'];
    }
        $respuesta = $data['respuesta'];

        // Consulta para obtener la respuesta almacenada en la tabla "usuario" para el ID dado
        $sql = "SELECT * FROM usuario WHERE ID = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $respuesta_almacenada = $row['respuesta'];

            // Comparar la respuesta proporcionada con la respuesta almacenada
            if ($respuesta === $respuesta_almacenada) {
                $response = array('status' => 'success', 'message' => 'La respuesta coincide.');
                $response['data'] = $row;
            } else {
                $response = array('status' => 'error', 'message' => 'La respuesta no coincide.');
            }
        }
}


// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
