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
        // Manejo de solicitud GET (Recuperar información de tiendas)
        if (isset($_GET['id_tienda'])) {
            // Si se proporciona un ID de tienda, recupera solo esa tienda
            $id_tienda = $_GET['id_tienda'];
            
            // Utiliza una consulta preparada para evitar inyección SQL
            $sql = "SELECT * FROM tiendas WHERE id_tienda = ?";
            
            // Prepara la consulta
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                // Vincula el parámetro id_tienda
                $stmt->bind_param("i", $id_tienda);
                
                // Ejecuta la consulta
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $tienda = $result->fetch_assoc();
                        $response['status'] = 'success';
                        $response['data'] = $tienda;
                    } else {
                        $response['message'] = 'No se encontró ninguna tienda con ese ID.';
                    }
                } else {
                    $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
                }
                
                // Cierra la consulta preparada
                $stmt->close();
            } else {
                $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
            }
        } else {
            // Si no se proporciona un ID de tienda, recuperar todas las tiendas
            $sql = "SELECT * FROM tiendas";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $tiendas = array();
                while ($row = $result->fetch_assoc()) {
                    $tiendas[] = $row;
                }
                $response['status'] = 'success';
                $response['data'] = $tiendas;
            } else {
                $response['message'] = 'No hay tiendas registradas en la base de datos.';
            }
        }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejo de solicitud POST (Crear una nueva tienda)
    $data = json_decode(file_get_contents("php://input"), true);
    
    $nombre_tienda = $data['nombre_tienda'];
    $direccion = $data['direccion'];
    $horario_apertura = $data['horario_apertura'];
    $horario_cierre = $data['horario_cierre'];
    
    // Utilizar una consulta preparada para evitar inyección SQL
    $sql = "INSERT INTO tiendas (nombre_tienda, direccion, horario_apertura, horario_cierre) VALUES (?, ?, ?, ?)";
    
    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Vincular los parámetros
        $stmt->bind_param("ssss", $nombre_tienda, $direccion, $horario_apertura, $horario_cierre);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Tienda creada con éxito';
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
        }
        
        // Cerrar la consulta preparada
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejo de solicitud PUT (Actualizar información de la tienda)
    $data = json_decode(file_get_contents("php://input"), true);
    
    $id_tienda = $data['id_tienda'];
    $nombre_tienda = $data['nombre_tienda'];
    $direccion = $data['direccion'];
    $horario_apertura = $data['horario_apertura'];
    $horario_cierre = $data['horario_cierre'];
    
    // Utilizar una consulta preparada para evitar inyección SQL
    $sql = "UPDATE tiendas SET nombre_tienda = ?, direccion = ?, horario_apertura = ?, horario_cierre = ? WHERE id_tienda = ?";
    
    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Vincular los parámetros
        $stmt->bind_param("ssssi", $nombre_tienda, $direccion, $horario_apertura, $horario_cierre, $id_tienda);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Información de la tienda actualizada con éxito';
        } else {
            $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
        }
        
        // Cerrar la consulta preparada
        $stmt->close();
    } else {
        $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Manejo de solicitud DELETE (Eliminar una tienda por su ID)
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['id_tienda'])) {
        $id_tienda = $data['id_tienda'];
        
        // Utiliza una consulta preparada para evitar inyección SQL
        $sql = "DELETE FROM tiendas WHERE id_tienda = ?";
        
        // Prepara la consulta
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Vincula el parámetro id_tienda
            $stmt->bind_param("i", $id_tienda);
            
            // Ejecuta la consulta
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Tienda eliminada con éxito';
            } else {
                $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
            }
            
            // Cierra la consulta preparada
            $stmt->close();
        } else {
            $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Se requiere el ID de la tienda para eliminarla.';
    }
}

// Enviar respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar la conexión a la base de datos
$conn->close();
?>
