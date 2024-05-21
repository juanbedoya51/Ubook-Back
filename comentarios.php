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

// Habilitar CORS solo para tu aplicación (ajusta según tus necesidades)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Respuesta por defecto
$response = array('status' => 'error', 'message' => 'Acción no válida');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id_usuario'])) {
        $id_usuario = $_GET['id_usuario'];
    // Operación de Lectura (Read)
    $sql = "SELECT * FROM comentarios WHERE idusuario = $id_usuario";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $comentarios = array();

        while ($row = $result->fetch_assoc()) {
            $comentarios[] = $row;
        }

        $response['status'] = 'success';
        $response['data'] = $comentarios;
    } else {
        $response['message'] = 'No hay comentarios en la base de datos.';
    }
}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Operación de Creación (Create)
    $data = json_decode(file_get_contents("php://input"), true);

    // Asegúrate de que las claves sean correctas según tu aplicación
    $idusuario = $data['idusuario'];
    $fecha = date('Y-m-d H:i:s'); // Obtiene la fecha y hora actual
    $comentario = $data['comentario'];

    // Utilizar una consulta preparada para evitar inyección SQL
    $sql = "INSERT INTO comentarios (idusuario, fecha, comentario) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("iss", $idusuario, $fecha, $comentario);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Comentario creado con éxito';
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Operación de Actualización (Update)
    $data = json_decode(file_get_contents("php://input"), true);

    $id = $data['id'];
    $idusuario = $data['idusuario'];
    $fecha = $data['fecha'];
    $comentario = $data['comentario'];

    $sql = "UPDATE comentarios SET idusuario = ?, fecha = ?, comentario = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("issi", $idusuario, $fecha, $comentario, $id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Comentario actualizado con éxito';
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Operación de Eliminación (Delete)
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

    $sql = "DELETE FROM comentarios WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Comentario eliminado con éxito';
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    }
}
} else {
    $response['message'] = 'Acción no válida';
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
