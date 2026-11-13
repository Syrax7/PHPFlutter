<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

include 'conexion.php';

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Error de conexión"]);
    exit();
}

$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;

if ($id_horario <= 0) {
    echo json_encode(["status" => "error", "message" => "Horario no especificado"]);
    exit();
}

// Consultar todas las asistencias registradas para esta materia/horario
$sql = "SELECT 
            a.id_asistencia,
            a.fecha,
            a.estado,
            e.nombres_apellidos,
            e.cedula
        FROM asistencia a
        INNER JOIN estudiante e ON a.id_estudiante = e.id_estudiante
        WHERE a.id_horario = $id_horario
        ORDER BY a.fecha DESC, e.nombres_apellidos ASC";

$result = $conn->query($sql);

$registros = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $registros[] = [
            "id_asistencia"    => $row['id_asistencia'],
            "fecha"            => $row['fecha'],
            "estado"           => $row['estado'],
            "nombres_apellidos"=> $row['nombres_apellidos'],
            "cedula"           => $row['cedula']
        ];
    }
}

echo json_encode([
    "status" => "success",
    "data"   => $registros
]);
exit();
?>