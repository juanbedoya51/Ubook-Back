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

// Permitir solicitudes desde tu aplicación Blazor WebAssembly
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
    // Manejo de solicitud POST (Reservar un libro)
    $data = json_decode(file_get_contents("php://input"), true);

    // Asegúrate de que los campos necesarios estén presentes en $data
    $id_libro = $data['id_libro'];
    $id_usuario = $data['id_usuario'];

    // Verificar si el libro está disponible
    $checkSql = "SELECT * FROM libros WHERE ID = $id_libro AND estado = 'Disponible'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        // Verificar si el usuario ha alcanzado el límite de reservas
        $countSql = "SELECT COUNT(*) as total FROM reservas WHERE id_usuario = $id_usuario";
        $countResult = $conn->query($countSql);
        $countRow = $countResult->fetch_assoc();

        if ($countRow['total'] < 3) {  // Puedes ajustar el límite según tus necesidades
            // Calcular fecha de vencimiento (1 minuto desde ahora)
            $fecha_reserva = date("Y-m-d H:i:s", strtotime("+1 minute"));

            // Iniciar transacción
            $conn->begin_transaction();

            try {
                // Actualizar el estado del libro a Reservado
                $updateSql = "UPDATE libros SET estado = 'Reservado' WHERE ID = $id_libro";
                $conn->query($updateSql);

                // Insertar la reserva en la tabla reservas
                $insertSql = "INSERT INTO reservas (id_usuario, id_libro, fecha_reserva) VALUES ($id_usuario, $id_libro, '$fecha_reserva')";
                $conn->query($insertSql);

                // Agregar la reserva al carrito
                $insertCarritoSql = "INSERT INTO carrito (id_usuario, id_libro) VALUES ($id_usuario, $id_libro)";
                $conn->query($insertCarritoSql);

                // Commit de la transacción
                $conn->commit();

                $response['status'] = 'success';
                $response['message'] = 'Libro reservado con éxito';
                $response['fecha_reserva'] = $fecha_reserva;
            } catch (Exception $e) {
                // Rollback en caso de error
                $conn->rollback();
                $response['message'] = 'Error al procesar la reserva. Inténtelo de nuevo: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Ha alcanzado el límite de reservas permitido.';
        }
    } else {
        $response['message'] = 'El libro no está disponible para reserva.';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
