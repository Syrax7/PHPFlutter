<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$query = "SELECT e.id_estudiante, e.nombres_apellidos, e.cedula, s.nombre AS seccion, s.trayecto 
          FROM estudiante e 
          LEFT JOIN seccion s ON e.id_seccion = s.id_seccion";

$resultado = $conexion->query($query);
$estudiantes = array();

if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $estudiantes[] = $row;
    }
    echo json_encode(array("status" => "success", "estudiantes" => $estudiantes));
} else {
    echo json_encode(array("status" => "success", "estudiantes" => array()));
}
$conexion->close();
?>