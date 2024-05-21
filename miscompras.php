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
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 3600");
    exit; // No proceses la solicitud en este caso
}

// Permitir solicitudes desde tu aplicación Blazor WebAssembly
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Respuesta por defecto
$response = array('status' => 'error', 'message' => 'Acción no válida');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Manejo de solicitud GET (Recuperar compras de un usuario)
    if (isset($_GET['id_usuario'])) {
        $id_usuario = $_GET['id_usuario'];

        // Utilizar una consulta preparada para evitar inyección SQL
        $sql = "SELECT historico.*, libros.nombre AS nombre_libro, libros.imagen AS imagen, libros.nuevo AS nuevo, libros.precio AS precio
                FROM historico
                JOIN libros ON historico.id_libro = libros.ID
                WHERE historico.id_usuario = ?";

        // Preparar la consulta
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Vincular el parámetro id_usuario
            $stmt->bind_param("i", $id_usuario);

            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Obtener resultados
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $compras = array();
                    while ($row = $result->fetch_assoc()) {
                        $compras[] = $row;
                    }
                    $response['status'] = 'success';
                    $response['message'] = 'Compras recuperadas con éxito';
                    $response['data'] = $compras;
                } else {
                    $response['message'] = 'No se encontraron compras para el usuario con ID ' . $id_usuario;
                }
            } else {
                $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
            }

            // Cerrar la consulta preparada
            $stmt->close();
        } else {
            $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Se requiere el ID de usuario para recuperar las compras.';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
