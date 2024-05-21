<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$database = "Ubook";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // Manejo de solicitud GET (Recuperar datos de libros)
    if (isset($_GET['id'])) {
        // Obtener un libro por ID
        $id = $_GET['id'];
        $sql = "SELECT * FROM libros WHERE ID = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $row;
        }
    } else {
        // Obtener todos los libros
        $sql = "SELECT * FROM libros";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $libros = array();
            while ($row = $result->fetch_assoc()) {
                $libros[] = $row;
            }
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $libros;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear un nuevo libro)
    $data = json_decode(file_get_contents("php://input"), true);

    // Asegúrate de que los campos necesarios estén presentes en $data
    $nombre = $data['nombre'];
    $autor = $data['autor'];
    $editorial = $data['editorial'];
    $genero = $data['genero'];
    $idioma = $data['idioma'];
    $nopagina = $data['nopagina'];
    $issn = $data['issn'];
    $fecha = $data['fecha'];
    $precio = $data['precio'];
    $estado = $data['estado']; // Nuevo atributo estado
    $imagen = $data['imagen'];

    // Verificar si el nombre no es solo espacios
    if (!empty(trim($nombre))) {
        // Verificar si la fecha no es mayor al día de hoy
        $today = date("Y-m-d");
        if ($fecha <= $today) {
            $sql = "INSERT INTO libros (nombre, autor, editorial, genero, idioma, nopagina, issn, fecha, precio, estado, imagen) 
                    VALUES ('$nombre', '$autor', '$editorial', '$genero', '$idioma', $nopagina, '$issn', '$fecha', $precio, '$estado', ?)";

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
            } else {
                $response['message'] = 'Error al ejecutar la consulta SQL';
            }
        } else {
            $response['message'] = 'La fecha no puede ser mayor al día de hoy';
        }
    } else {
        $response['message'] = 'El nombre del libro no puede ser solo espacios';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejo de solicitud PUT (Actualizar un libro por su ID)
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['ID'])) {
        $id = $data['ID'];
    }
    if (isset($data['id'])) {
        $id = $data['id'];
    }
    $nombre = $data['nombre'];
    $autor = $data['autor'];
    $editorial = $data['editorial'];
    $genero = $data['genero'];
    $idioma = $data['idioma'];
    $nopagina = $data['nopagina'];
    $issn = $data['issn'];
    $fecha = $data['fecha'];
    $precio = $data['precio'];
    $estado = $data['estado']; // Nuevo atributo estado
    $imagen = $data['imagen']; // Imagen tal como llega en el JSON

    // Verificar si el nombre no es solo espacios
    if (!empty(trim($nombre))) {
        // Verificar si la fecha no es mayor al día de hoy
        $today = date("Y-m-d");
        if ($fecha <= $today) {
            $sql = "UPDATE libros SET nombre = '$nombre', autor = '$autor', editorial = '$editorial', genero = '$genero', idioma = '$idioma', nopagina = $nopagina, issn = '$issn', fecha = '$fecha', precio = $precio, estado = '$estado', imagen = ? 
                    WHERE id = $id";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $imagen); // Usar "s" para datos de tipo cadena (string)

            if ($stmt->execute()) {
                // Consultar y devolver la información del libro actualizado
                $sql = "SELECT * FROM libros WHERE id = $id";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $response['status'] = 'success';
                    $response['message'] = 'Libro actualizado con éxito';
                    $response['data'] = $row; // Agregar los detalles del libro a la respuesta
                }
            } else {
                $response['message'] = 'Error al ejecutar la consulta SQL';
            }
        } else {
            $response['message'] = 'La fecha no puede ser mayor al día de hoy';
        }
    } else {
        $response['message'] = 'El nombre del libro no puede ser solo espacios';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar un libro por su ID)
    $id = $_GET['id'];
    // Verificar si el libro no está reservado ni vendido
    $checkSql = "SELECT * FROM libros WHERE ID = $id AND (estado = 'Disponible' OR estado IS NULL)";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        $sql = "DELETE FROM libros WHERE ID = $id";
        if ($conn->query($sql) === TRUE) {
            $response['status'] = 'success';
            $response['message'] = 'Libro eliminado con éxito';
        }
    } else {
        $response['message'] = 'No se puede eliminar el libro. Está reservado o vendido.';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
