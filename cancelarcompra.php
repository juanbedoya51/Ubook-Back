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

// Respuesta por defecto
$response = array('status' => 'error', 'message' => 'Acción no válida');

// Validar sesión (puedes implementar esta función según tu estructura actual)
if (validarSesion()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Manejo de solicitud POST (Cancelar compra)
        $data = json_decode(file_get_contents("php://input"), true);
        $id_usuario = $data['id_usuario'];
        
        // Puedes agregar más lógica según tus necesidades
        $sql = "UPDATE compra SET estado = 'Cancelada' WHERE id_usuario = $id_usuario AND estado = 'En proceso'";
        
        if ($conn->query($sql) === TRUE) {
            $response['status'] = 'success';
            $response['message'] = 'Compra cancelada con éxito';
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Método no permitido';
    }
} else {
    $response['message'] = 'Sesión no válida';
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();

// Función para validar la sesión (simplificada, ajusta según tu estructura)
function validarSesion() {
    // Aquí debes implementar la lógica de validación de sesión
    // Devuelve true si la sesión es válida, de lo contrario, false.
    return true;
}
?>
