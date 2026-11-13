<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$cedula = $_POST['cedula'] ?? '';
$nombres = $_POST['nombres_apellidos'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$especialidad = $_POST['especialidad'] ?? ''; // <-- 1. Recogemos la especialidad

if (empty($cedula) || empty($nombres)) {
    echo json_encode(array("status" => "error", "message" => "Faltan campos obligatorios"));
    exit;
}

// 2. Incluimos 'especialidad' en la consulta SQL de inserción
$query = "INSERT INTO docente (cedula, nombres_apellidos, clave, telefono, especialidad) 
          VALUES ('$cedula', '$nombres', '$contrasena', '$telefono', '$especialidad')";

if ($conexion->query($query) === TRUE) {
    echo json_encode(array("status" => "success", "message" => "Profesor registrado exitosamente"));
} else {
    echo json_encode(array("status" => "error", "message" => "Error al registrar: " . $conexion->error));
}
$conexion->close();
?>