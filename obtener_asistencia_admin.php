<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$id_estudiante = $_GET['id_estudiante'] ?? '';
$id_materia = $_GET['id_materia'] ?? '';

if (empty($id_estudiante) || empty($id_materia)) {
    echo json_encode(array("status" => "error", "message" => "Faltan parámetros"));
    exit;
}

$query = "SELECT a.fecha, a.estado, h.dia, h.hora, m.nombre AS nombre_materia 
          FROM asistencia a
          INNER JOIN horario h ON a.id_horario = h.id_horario
          INNER JOIN materia m ON h.id_materia = m.id_materia
          WHERE a.id_estudiante = $id_estudiante AND h.id_materia = $id_materia
          ORDER BY a.fecha DESC";

$resultado = $conexion->query($query);
$asistencias = array();

if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $asistencias[] = $row;
    }
    echo json_encode(array("status" => "success", "asistencias" => $asistencias));
} else {
    echo json_encode(array("status" => "success", "asistencias" => array()));
}
$conexion->close();
?>