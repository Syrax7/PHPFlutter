<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$id_horario = $_GET['id_horario'] ?? '';

if (empty($id_horario)) {
    echo json_encode(array("status" => "error", "message" => "Faltan parámetros", "asistencias" => array()));
    exit;
}

// Consultamos los registros de asistencia de todos los estudiantes para este horario/materia
$query = "SELECT a.fecha, a.estado, e.nombres_apellidos AS estudiante, e.cedula 
          FROM asistencia a
          INNER JOIN estudiante e ON a.id_estudiante = e.id_estudiante
          WHERE a.id_horario = $id_horario
          ORDER BY a.fecha DESC, e.nombres_apellidos ASC";

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