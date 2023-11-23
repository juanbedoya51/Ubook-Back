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
                    $response['message'] = '';
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
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['id_usuario'])) {
        $id_usuario = $data['id_usuario'];
        
        // Utiliza una consulta preparada para evitar inyección SQL
        $sql = "DELETE FROM tarjetcompra WHERE id_usuario = ?";
        
        // Prepara la consulta
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Vincula el parámetro id_usuario
            $stmt->bind_param("i", $id_usuario);
            
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
