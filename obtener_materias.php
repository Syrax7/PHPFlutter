<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    include 'conexion.php';

    if (!isset($conn) || $conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Error de conexión a la BD"]);
        exit();
    }

    $raw_id = $_GET['id_docente'] ?? 0;
    $id_docente = intval($raw_id);

    if ($id_docente <= 0) {
        echo json_encode(["success" => false, "message" => "ID de docente no válido ($raw_id)"]);
        exit();
    }

    $sql = "SELECT 
                h.id_horario,
                m.id_materia,
                m.nombre AS materia,
                m.trayecto,
                s.nombre AS seccion,
                h.dia,
                h.hora
            FROM horario h
            INNER JOIN materia m ON h.id_materia = m.id_materia
            INNER JOIN seccion s ON h.id_seccion = s.id_seccion
            WHERE h.id_docente = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $result = $stmt->get_result();

    $materias = array();
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }

    echo json_encode(["success" => true, "materias" => $materias]);
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error en servidor: " . $e->getMessage()]);
}
?>