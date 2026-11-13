<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conexion.php';

$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;

if ($id_horario <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de horario inválido"]);
    exit();
}

$sql = "SELECT fecha, 
               SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) as total_presentes,
               SUM(CASE WHEN estado = 'Ausente' THEN 1 ELSE 0 END) as total_ausentes
        FROM asistencia 
        WHERE id_horario = $id_horario 
        GROUP BY fecha 
        ORDER BY fecha DESC";

$result = $conn->query($sql);

$historial = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $historial[] = [
            "fecha" => $row["fecha"],
            "total_presentes" => intval($row["total_presentes"]),
            "total_ausentes" => intval($row["total_ausentes"])
        ];
    }
    echo json_encode(["status" => "success", "historial" => $historial]);
} else {
    echo json_encode(["status" => "success", "historial" => []]);
}

$conn->close();
?>