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
    // Manejo de solicitud GET (Recuperar datos de libros, compras, ventas y reservas)
    if (isset($_GET['entity'])) {
        $entity = $_GET['entity'];
        if ($entity === 'libros') {
            // Obtener todos los libros
            $sql = "SELECT * FROM libros";
            $result = $conn->query($sql);
        } elseif ($entity === 'compras') {
            // Obtener historial de compras
            $sql = "SELECT * FROM compras";
            $result = $conn->query($sql);
        } elseif ($entity === 'ventas') {
            // Obtener historial de ventas
            $sql = "SELECT * FROM ventas";
            $result = $conn->query($sql);
        } elseif ($entity === 'reservas') {
            // Obtener historial de reservas
            $sql = "SELECT * FROM reservas";
            $result = $conn->query($sql);
        }

        if ($result->num_rows > 0) {
            $data = array();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $data;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear un nuevo registro)
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['entity'])) {
        $entity = $data['entity'];
        if ($entity === 'libros') {
            // Crear un nuevo libro
            // Asegúrate de que los campos necesarios estén presentes en $data
            $nombre = $data['nombre'];
            $autor = $data['autor'];
            // ... (otros campos)
            $imagen = $data['imagen'];

            $sql = "INSERT INTO libros (nombre, autor, imagen) 
                VALUES ('$nombre', '$autor', ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $imagen);

            if ($stmt->execute()) {
                // Consultar y devolver la información del libro creado
                $lastInsertID = $stmt->insert_id;
                $selectSQL = "SELECT * FROM libros WHERE ID = $lastInsertID";
                $result = $conn->query($selectSQL);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $response['status'] = 'success';
                    $response['message'] = 'Libro creado con éxito';
                    $response['data'] = $row; // Agregar los detalles del libro a la respuesta
                }
            }
        } elseif ($entity === 'compras') {
            // Crear un nuevo registro de compra
            // Asegúrate de que los campos necesarios estén presentes en $data
            // ...
        } elseif ($entity === 'ventas') {
            // Crear un nuevo registro de venta
            // Asegúrate de que los campos necesarios estén presentes en $data
            // ...
        } elseif ($entity === 'reservas') {
            // Crear un nuevo registro de reserva
            // Asegúrate de que los campos necesarios estén presentes en $data
            // ...
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejo de solicitud PUT (Actualizar un registro por su ID)
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['entity'])) {
        $entity = $data['entity'];
        if ($entity === 'libros') {
            // Actualizar un libro
            // Asegúrate de que los campos necesarios estén presentes en $data
            $id = $data['ID'];
            $nombre = $data['nombre'];
            $autor = $data['autor'];
            // ... (otros campos)
            $imagen = $data['imagen'];

            $sql = "UPDATE libros SET nombre = '$nombre', autor = '$autor', imagen = ? 
                    WHERE ID = $id";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $imagen);

            if ($stmt->execute()) {
                // Consultar y devolver la información del libro actualizado
                $selectSQL = "SELECT * FROM libros WHERE ID = $id";
                $result = $conn->query($selectSQL);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $response['status'] = 'success';
                    $response['message'] = 'Libro actualizado con éxito';
                    $response['data'] = $row; // Agregar los detalles del libro a la respuesta
                }
            }
        } elseif ($entity === 'compras') {
            // Actualizar un registro de compra
            // Asegúrate de que los campos necesarios estén presentes en $data
            // ...
        } elseif ($entity === 'ventas') {
            // Actualizar un registro de venta
            // Asegúrate de que los campos necesarios estén presentes en $data
            // ...
        } elseif ($entity === 'reservas') {
            // Actualizar un registro de reserva
            // Asegúrate de que los campos necesarios estén presentes en $data
            // ...
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar un registro por su ID)
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['entity']) && isset($data['id'])) {
        $entity = $data['entity'];
        $id = $data['id'];
        if ($entity === 'libros') {
            $sql = "DELETE FROM libros WHERE ID = $id";
        } elseif ($entity === 'compras') {
            // Eliminar un registro de compra
            // ...
        } elseif ($entity === 'ventas') {
            // Eliminar un registro de venta
            // ...
        } elseif ($entity === 'reservas') {
            // Eliminar un registro de reserva
            // ...
        }

        if ($conn->query($sql) === TRUE) {
            $response['status'] = 'success';
            $response['message'] = $entity . ' eliminado con éxito';
        }
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>