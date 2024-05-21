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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Respuesta preflight para solicitudes CORS
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Max-Age: 3600");
    exit; // No proceses la solicitud en este caso
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Respuesta por defecto
$response = array('status' => 'error', 'message' => 'Acción no válida');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Manejo de solicitud GET (Recuperar datos)
    if (isset($_GET['id'])) {
        // Obtener un usuario por ID
        $id = $_GET['id'];
        $sql = "SELECT * FROM usuario WHERE ID = $id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $result->fetch_assoc();
        }
    } else {
        // Obtener todos los usuarios
        $sql = "SELECT * FROM usuario";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $usuarios = array();
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            $response['status'] = 'success';
            $response['message'] = '';
            $response['data'] = $usuarios;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear un nuevo usuario)
    $data = json_decode(file_get_contents("php://input"), true);
    $dni = $data['dni'];
    $nombre = $data['nombre'];
    $apellido = $data['apellido'];
    $correo = $data['correo'];
    $contrasena = $data['contrasena'];
    $fechaNacimiento = $data['fecha_nacimiento'];
    $lugarNacimiento = $data['lugar_nacimiento'];
    $genero = $data['genero'];
    $direccion = $data['direccion'];

    // Verificar edad del usuario (mayor de 14 años)
    $fechaActual = new DateTime();
    $fechaNacimientoObj = new DateTime($fechaNacimiento);
    $edad = $fechaActual->diff($fechaNacimientoObj)->y;

    if ($edad > 14) {
        // Verificar requisitos de complejidad de la contraseña
        if (verificarRequisitosContrasena($contrasena)) {
            $hashedPassword = $contrasena;

            $sql = "INSERT INTO usuario (dni, nombre, apellido, correo, contrasena, fecha_nacimiento, lugar_nacimiento, genero, direccion) 
                    VALUES ('$dni', '$nombre', '$apellido', '$correo', '$hashedPassword', '$fechaNacimiento', '$lugarNacimiento', '$genero', '$direccion')";

            if ($conn->query($sql) === TRUE) {
                $response['status'] = 'success';
                $response['message'] = 'Usuario creado con éxito';

                // Consultar y devolver la información del usuario recién creado
                $sql = "SELECT * FROM usuario WHERE ID = " . $conn->insert_id;
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $response['data'] = $result->fetch_assoc();
                }
            }
        } else {
            $response['message'] = 'La contraseña no cumple con los requisitos de complejidad.';
        }
    } else {
        $response['message'] = 'La edad del usuario debe ser mayor de 14 años.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejo de solicitud PUT (Actualizar un usuario por su ID)
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['ID'];
    $dni = $data['dni'];
    $nombre = $data['nombre'];
    $apellido = $data['apellido'];
    $correo = $data['correo'];
    $contrasena = $data['contrasena'];
    $fechaNacimiento = $data['fecha_nacimiento'];
    $lugarNacimiento = $data['lugar_nacimiento'];
    $genero = $data['genero'];
    $direccion = $data['direccion'];

    // Verificar edad del usuario (mayor de 14 años)
    $fechaActual = new DateTime();
    $fechaNacimientoObj = new DateTime($fechaNacimiento);
    $edad = $fechaActual->diff($fechaNacimientoObj)->y;

    if ($edad > 14) {
        // Verificar requisitos de complejidad de la contraseña
        if (verificarRequisitosContrasena($contrasena)) {
            $hashedPassword = $contrasena;

            $sql = "UPDATE usuario 
                    SET dni = '$dni', nombre = '$nombre', apellido = '$apellido', correo = '$correo', contrasena = '$hashedPassword',
                        fecha_nacimiento = '$fechaNacimiento', lugar_nacimiento = '$lugarNacimiento', genero = '$genero', direccion = '$direccion' 
                    WHERE ID = $id";

            if ($conn->query($sql) === TRUE) {
                $response['status'] = 'success';
                $response['message'] = 'Usuario actualizado con éxito';

                // Consultar y devolver la información del usuario actualizado
                $sql = "SELECT * FROM usuario WHERE ID = $id";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $response['data'] = $result->fetch_assoc();
                }
            }
        } else {
            $response['message'] = 'La contraseña no cumple con los requisitos de complejidad.';
        }
    } else {
        $response['message'] = 'La edad del usuario debe ser mayor de 14 años.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar un usuario por su ID)
    $id = $_GET['id'];
    $sql = "DELETE FROM usuario WHERE ID = $id";
    if ($conn->query($sql) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Usuario eliminado con éxito';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();

// Función para verificar los requisitos de complejidad de la contraseña
function verificarRequisitosContrasena($contrasena) {
    // Verificar longitud mínima
    if (strlen($contrasena) < 8) {
        return false;
    }

    // Verificar al menos una letra mayúscula
    if (!preg_match('/[A-Z]/', $contrasena)) {
        return false;
    }

    // Verificar al menos una letra minúscula
    if (!preg_match('/[a-z]/', $contrasena)) {
        return false;
    }

    // Verificar al menos un número
    if (!preg_match('/[0-9]/', $contrasena)) {
        return false;
    }

    // Verificar al menos un carácter especial
    // Se incluyeron más caracteres especiales en la expresión regular
    if (!preg_match('/[!@#$%^&*()_+{}:;<>,.?[\]~-]/', $contrasena)) {
        return false;
    }

    return true;
}
?>
