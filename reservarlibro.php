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
        // Manejo de solicitud POST (Reservar libro)
        $data = json_decode(file_get_contents("php://input"), true);

        // Verificar si se recibieron datos válidos
        if (isset($data['id_usuario']) && isset($data['id_libro'])) {
            $id_usuario = $data['id_usuario'];
            $id_libro = $data['id_libro'];

            // Verificar existencia del libro y cantidad disponible
            if (verificarDisponibilidadLibro($id_libro)) {
                // Puedes agregar más lógica según tus necesidades
                $sqlReserva = "INSERT INTO reservas (id_usuario, id_libro) VALUES ($id_usuario, $id_libro)";
                $sqlActualizarLibro = "UPDATE libros SET reservas = reservas + 1, stock = stock - 1 WHERE ID = $id_libro";

                if ($conn->query($sqlReserva) === TRUE && $conn->query($sqlActualizarLibro) === TRUE) {
                    // Añadir a la tabla histórico
                    $sqlHistorico = "INSERT INTO historico (id_usuario, id_libro, nombre_libro, reservado) 
                                    VALUES ($id_usuario, $id_libro, (SELECT nombre FROM libros WHERE ID = $id_libro), 'Si')";
                    $conn->query($sqlHistorico);

                    // Añadir automáticamente a la tabla carrito (opcional)
                    agregarAlCarrito($id_usuario, $id_libro);

                    $response['status'] = 'success';
                    $response['message'] = 'Libro reservado con éxito';
                } else {
                    $response['message'] = 'Error al ejecutar la consulta: ' . $conn->error;
                }
            } else {
                $response['message'] = 'El libro con ID ' . $id_libro . ' no está disponible para reservar.';
            }
        } else {
            $response['message'] = 'Datos de solicitud no válidos';
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

// Función para verificar la disponibilidad del libro
function verificarDisponibilidadLibro($id_libro) {
    global $conn;
    $sql = "SELECT stock FROM libros WHERE ID = $id_libro";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stock = $row['stock'];

        // Verificar si hay stock disponible
        return $stock > 0;
    }

    return false;
}

// Función para agregar el libro al carrito
function agregarAlCarrito($id_usuario, $id_libro) {
    global $conn;
    $sql = "INSERT INTO carrito (id_usuario, id_libro) VALUES ($id_usuario, $id_libro)";
    $conn->query($sql);
}
?>
