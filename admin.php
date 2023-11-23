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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Manejo de solicitud GET (Recuperar datos de administradores)
    if (isset($_GET['id'])) {
        // Obtener un administrador por ID
        $id = $_GET['id'];
        $sql = "SELECT ID, correo, documento, tipo_documento, nombre FROM administrador WHERE ID = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // No se convierte la contraseña a Base64 en este caso
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $row;
        }
    } else {
        // Obtener todos los administradores
        $sql = "SELECT ID, correo, documento, tipo_documento, nombre FROM administrador";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $administradores = array();
            while ($row = $result->fetch_assoc()) {
                // No se convierte la contraseña a Base64 en este caso
                $administradores[] = $row;
            }
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $administradores;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear un nuevo administrador)
    $data = json_decode(file_get_contents("php://input"), true);
    $correo = $data['correo'];
    $contrasena = $data['contrasena']; // Convertir la contraseña a Base64
    $documento = $data['documento'];
    $tipoDocumento = $data['tipo_documento'];
    $nombre = $data['nombre'];

    $sql = "INSERT INTO administrador (correo, contrasena, documento, tipo_documento, nombre) VALUES ('$correo', '$contrasena', '$documento', '$tipoDocumento', '$nombre')";
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Administrador creado con éxito';

         // Consultar y devolver la información del usuario recién creado
         $sql = "SELECT * FROM administrador WHERE ID = " . $conn->insert_id;
         $result = $conn->query($sql);
         if ($result->num_rows > 0) {
             $response['data'] = $result->fetch_assoc();
         }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejo de solicitud PUT (Actualizar un administrador)
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['ID'])) {
        $id = $data['ID'];
    }
    if (isset($data['id'])) {
        $id = $data['id'];
    }
    $correo = $data['correo'];
    $contrasena = $data['contrasena']; // Convertir la contraseña a Base64
    $documento = $data['documento'];
    $tipoDocumento = $data['tipo_documento'];
    $nombre = $data['nombre'];

    $sql = "UPDATE administrador SET correo = '$correo', contrasena = '$contrasena', documento = '$documento', tipo_documento = '$tipoDocumento', nombre = '$nombre' WHERE ID = $id";
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Administrador actualizado con éxito';

        // Consultar y devolver la información del usuario actualizado
        $sql = "SELECT * FROM administrador WHERE ID = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $response['data'] = $result->fetch_assoc();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar un administrador)
    $id = $_GET['id'];
    $sql = "DELETE FROM administrador WHERE ID = $id";
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Administrador eliminado con éxito';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
