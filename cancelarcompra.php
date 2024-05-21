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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Cancelar una compra)
    $data = json_decode(file_get_contents("php://input"), true);

    // Asegúrate de que los campos necesarios estén presentes en $data
    if (isset($data['id_historico'])) {
        $id_historico = $data['id_historico'];

        // Verificar si la compra está en estado 'preparacion'
        $checkSql = "SELECT * FROM historico WHERE id_historico = $id_historico AND estado_envio = 'preparacion'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            // Obtener información de la compra
            $historicoInfo = $checkResult->fetch_assoc();
            $id_usuario = $historicoInfo['id_usuario'];
            $id_libro = $historicoInfo['id_libro'];
            $id_tarjeta = $historicoInfo['id_tarjeta'];

            // Actualizar el estado de la compra a 'cancelado' y compra_cancelada a 'Si'
            $updateSql = "UPDATE historico SET estado_envio = 'cancelado', compra_cancelada = 'Si' WHERE id_historico = $id_historico";
            if ($conn->query($updateSql) === TRUE) {
                // Devolver el libro a 'Disponible'
                $updateLibroSql = "UPDATE libros SET estado = 'Disponible' WHERE ID = $id_libro";
                $conn->query($updateLibroSql);

                // Devolver el saldo a la tarjeta
                $updateTarjetaSql = "UPDATE tarjetcompra SET saldo = saldo + (SELECT precio FROM libros WHERE ID = $id_libro) WHERE id_tarjeta = $id_tarjeta";
                $conn->query($updateTarjetaSql);

                // Consultar y devolver la información de la compra cancelada
                $selectSQL = "SELECT * FROM historico WHERE id_historico = $id_historico";
                $result = $conn->query($selectSQL);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $response['status'] = 'success';
                    $response['message'] = 'Compra cancelada con éxito';
                    $response['data'] = $row; // Agregar los detalles de la compra a la respuesta
                } else {
                    $response['message'] = 'Error al obtener información de la compra cancelada';
                }
            } else {
                $response['message'] = 'Error al cancelar la compra';
            }
        } else {
            $response['message'] = 'La compra no está en estado de preparación';
        }
    } else {
        $response['message'] = 'Datos de solicitud no válidos';
    }
} else {
    $response['message'] = 'Método no permitido';
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>