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

// Validar sesión (puedes implementar esta función según tu estructura actual)
if (validarSesion()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Manejo de solicitud POST (Comprar libro)
        $data = json_decode(file_get_contents("php://input"), true);

        // Verificar si se recibieron datos válidos
        if (isset($data['id_usuario']) && isset($data['id_libro']) && isset($data['id_tarjeta'])) {
            $id_usuario = $data['id_usuario'];
            $id_libro = $data['id_libro'];
            $id_tarjeta = $data['id_tarjeta'];

            // Obtener información de la tarjeta
            $tarjeta_info = obtenerInformacionTarjeta($id_tarjeta);

            // Verificar si la tarjeta pertenece al usuario
            if ($tarjeta_info['id_usuario'] == $id_usuario) {
                // Verificar disponibilidad del libro
                if (verificarDisponibilidadLibro($id_libro)) {
                    // Verificar saldo suficiente en la tarjeta
                    if ($tarjeta_info['saldo'] >= obtenerPrecioLibro($id_libro)) {
                        // Actualizar el saldo de la tarjeta
                        if (actualizarSaldoTarjeta($id_tarjeta, obtenerPrecioLibro($id_libro))) {
                            // Actualizar el estado del libro a 'Vendido'
                            if (actualizarEstadoLibro($id_libro, 'Vendido')) {
                                // Agregar la compra al historico con estado_envio 'preparacion'
                                if (agregarCompraHistorico($id_usuario, $id_libro, $id_tarjeta, 'preparacion')) {
                                    $response['status'] = 'success';
                                    $response['message'] = 'Libro comprado con éxito';
                                    $response['data'] = obtenerDetalleCompraHistorico($id_usuario, $id_libro);
                                } else {
                                    $response['message'] = 'Error al agregar la compra al historico';
                                }
                            } else {
                                $response['message'] = 'Error al actualizar el estado del libro';
                            }
                        } else {
                            $response['message'] = 'Error al actualizar el saldo de la tarjeta';
                        }
                    } else {
                        $response['message'] = 'Saldo insuficiente en la tarjeta';
                    }
                } else {
                    $response['message'] = 'El libro no está disponible para la compra';
                }
            } else {
                $response['message'] = 'La tarjeta no pertenece al usuario';
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
    $sql = "SELECT estado FROM libros WHERE ID = $id_libro";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['estado'] === 'Disponible';
    }

    return false;
}

// Función para obtener el precio del libro
function obtenerPrecioLibro($id_libro) {
    global $conn;
    $sql = "SELECT precio FROM libros WHERE ID = $id_libro";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['precio'];
    }

    return 0;
}

// Función para actualizar el estado del libro
function actualizarEstadoLibro($id_libro, $estado) {
    global $conn;
    $sql = "UPDATE libros SET estado = '$estado' WHERE ID = $id_libro";
    return $conn->query($sql);
}

// Función para obtener información de la tarjeta
function obtenerInformacionTarjeta($id_tarjeta) {
    global $conn;
    $sql = "SELECT * FROM tarjetcompra WHERE id_tarjeta = $id_tarjeta"; // Cambiado 'id_usuario' a 'id_tarjeta'
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return array();
}

// Función para actualizar el saldo de la tarjeta
function actualizarSaldoTarjeta($id_tarjeta, $monto) {
    global $conn;
    $sql = "UPDATE tarjetcompra SET saldo = saldo - $monto WHERE id_tarjeta = $id_tarjeta"; // Cambiado 'id_usuario' a 'id_tarjeta'
    return $conn->query($sql);
}

// Función para agregar la compra al historico con estado_envio 'preparacion'
function agregarCompraHistorico($id_usuario, $id_libro, $id_tarjeta, $estado_envio) {
    global $conn;
    $nombre_libro = obtenerNombreLibro($id_libro);
    $fecha_compra = date('Y-m-d');
    $sql = "INSERT INTO historico (id_usuario, id_libro, id_tarjeta, nombre_libro, compra_cancelada, estado_envio, direccion, fecha_compra) 
            VALUES ($id_usuario, $id_libro, $id_tarjeta, '$nombre_libro', 'No', '$estado_envio', 'direccion_del_usuario', CURDATE())";
    return $conn->query($sql);
}

// Función para obtener el nombre del libro
function obtenerNombreLibro($id_libro) {
    global $conn;
    $sql = "SELECT nombre FROM libros WHERE ID = $id_libro";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['nombre'];
    }

    return '';
}

// Función para obtener detalles de la compra en el historico
function obtenerDetalleCompraHistorico($id_usuario, $id_libro) {
    global $conn;
    $sql = "SELECT * FROM historico WHERE id_usuario = $id_usuario AND id_libro = $id_libro";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return array();
}
?>