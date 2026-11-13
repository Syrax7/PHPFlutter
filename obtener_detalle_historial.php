<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conexion.php';

$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;
$fecha = isset($_GET['fecha']) ? $conn->real_escape_string($_GET['fecha']) : '';

if ($id_horario <= 0 || empty($fecha)) {
    echo json_encode(["status" => "error", "message" => "Parámetros incompletos"]);
    exit();
}

// ⚠️ Cambia 'estudiante' por el nombre exacto de tu tabla en la base de datos si es diferente
$sql = "SELECT e.nombres_apellidos, e.cedula, a.estado 
        FROM asistencia a 
        INNER JOIN estudiante e ON a.id_estudiante = e.id_estudiante 
        WHERE a.id_horario = $id_horario AND a.fecha = '$fecha'
        ORDER BY e.nombres_apellidos ASC";

$result = $conn->query($sql);

$detalles = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $detalles[] = [
            "nombres_apellidos" => $row["nombres_apellidos"],
            "cedula" => $row["cedula"],
            "estado" => $row["estado"]
        ];
    }
    echo json_encode(["status" => "success", "detalles" => $detalles]);
} else {
    echo json_encode(["status" => "success", "detalles" => []]);
}

$conn->close();
?>