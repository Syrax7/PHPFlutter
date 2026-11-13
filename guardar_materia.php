<?php
// Desactivar la salida directa de errores HTML de PHP para no romper el JSON
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de la petición previa (Preflight) de Chrome
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    include 'conexion.php';

    if (!isset($conn) || $conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Error de conexión a la Base de Datos"]);
        exit();
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    $nombre = trim($data['nombre'] ?? '');
    $trayecto = trim($data['trayecto'] ?? '');
    $seccion_nombre = trim($data['seccion'] ?? '');
    $dia = trim($data['dia'] ?? '');
    $hora = trim($data['hora'] ?? '');
    $id_docente = isset($data['id_docente']) ? intval($data['id_docente']) : 0;

    if (empty($nombre) || empty($trayecto) || empty($seccion_nombre) || $id_docente <= 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Faltan datos obligatorios o el ID del docente es inválido (ID recibido: $id_docente)"
        ]);
        exit();
    }

    // 1. Insertar o recuperar Materia
    $sql_materia = "SELECT id_materia FROM materia WHERE nombre = ? AND trayecto = ?";
    $stmt = $conn->prepare($sql_materia);
    $stmt->bind_param("ss", $nombre, $trayecto);
    $stmt->execute();
    $res_materia = $stmt->get_result();

    if ($res_materia->num_rows > 0) {
        $id_materia = $res_materia->fetch_assoc()['id_materia'];
    } else {
        $stmt_ins = $conn->prepare("INSERT INTO materia (nombre, trayecto) VALUES (?, ?)");
        $stmt_ins->bind_param("ss", $nombre, $trayecto);
        $stmt_ins->execute();
        $id_materia = $stmt_ins->insert_id;
    }

    // 2. Insertar o recuperar Sección
    $sql_seccion = "SELECT id_seccion FROM seccion WHERE nombre = ? AND trayecto = ?";
    $stmt2 = $conn->prepare($sql_seccion);
    $stmt2->bind_param("ss", $seccion_nombre, $trayecto);
    $stmt2->execute();
    $res_seccion = $stmt2->get_result();

    if ($res_seccion->num_rows > 0) {
        $id_seccion = $res_seccion->fetch_assoc()['id_seccion'];
    } else {
        $stmt_ins2 = $conn->prepare("INSERT INTO seccion (nombre, trayecto) VALUES (?, ?)");
        $stmt_ins2->bind_param("ss", $seccion_nombre, $trayecto);
        $stmt_ins2->execute();
        $id_seccion = $stmt_ins2->insert_id;
    }

    // 3. Crear el Registro en Horario
    $sql_horario = "INSERT INTO horario (dia, hora, id_docente, id_materia, id_seccion) VALUES (?, ?, ?, ?, ?)";
    $stmt3 = $conn->prepare($sql_horario);
    $stmt3->bind_param("ssiii", $dia, $hora, $id_docente, $id_materia, $id_seccion);

    if ($stmt3->execute()) {
        echo json_encode(["success" => true, "message" => "Materia registrada correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error en la consulta horario: " . $conn->error]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Excepción en servidor PHP: " . $e->getMessage()]);
}
?>