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
    // Manejo de solicitud GET (Recuperar tarjetas de crédito de un usuario)
    if (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        
        // Utilizar una consulta preparada para evitar inyección SQL
        $sql = "SELECT * FROM tarjetcompra WHERE id_usuario = ?";
        
        // Preparar la consulta
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Vincular el parámetro user_id
            $stmt->bind_param("i", $user_id);
            
            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Obtener resultados
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $tarjetas = array();
                    while ($row = $result->fetch_assoc()) {
                        $tarjetas[] = $row;
                    }
                    $response['status'] = 'success';
                    $response['message'] = 'Tarjetas de crédito recuperadas con éxito';
                    $response['data'] = $tarjetas;
                }
            } else {
                $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
            }
            
            // Cerrar la consulta preparada
            $stmt->close();
        } else {
            $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear una nueva tarjeta de crédito para un usuario)
    $data = json_decode(file_get_contents("php://input"), true);
    
    $user_id = $data['id_usuario'];
    $numero = $data['numero'];
    $fecha_vencimiento = $data['fecha_vencimiento'];
    $titular = $data['titular'];
    $saldo = $data['saldo'];
    $cvc = $data['cvc'];
    
    

    // Utilizar una consulta preparada para evitar inyección SQL
    $sql = "INSERT INTO tarjetcompra (id_usuario, numero, fecha_vencimiento, titular, saldo, cvc) VALUES (?, ?, ?, ?, ?, ?)";
    
    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Vincular los parámetros
        $stmt->bind_param("isssis", $user_id, $numero, $fecha_vencimiento, $titular, $saldo, $cvc);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Tarjeta de crédito creada con éxito';
            
            // Obtener el ID de la tarjeta recién creada
            $tarjeta_id = $stmt->insert_id;
            
            // Agregar datos de la tarjeta a la respuesta
            $response['data'] = array(
                'id_usuario' => $user_id,
                'numero' => $numero,
                'fecha_vencimiento' => $fecha_vencimiento,
                'titular' => $titular,
                'saldo' => $saldo,
                'cvc' => $cvc
            );
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
        }
        
        // Cerrar la consulta preparada
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar tarjeta de crédito por su ID)
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Utiliza una consulta preparada para evitar inyección SQL
        $sql = "DELETE FROM tarjetcompra WHERE id_tarjeta = ?";
        
        // Prepara la consulta
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Vincula el parámetro id_usuario
            $stmt->bind_param("i", $id);
            
            // Ejecuta la consulta
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Tarjeta de crédito eliminada con éxito';
            } else {
                $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
            }
            
            // Cierra la consulta preparada
            $stmt->close();
        } else {
            $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Se requiere el ID de la tarjeta para eliminarla.';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>

