<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=UTF-8");

include 'conexion.php';

if (!isset($conexion) && isset($conn)) {
    $conexion = $conn;
}

if (!isset($conexion) || $conexion === null || $conexion->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Error de conexión a la base de datos",
        "estudiantes" => []
    ]);
    exit();
}

// 1. Obtener id_seccion
// Reemplaza la línea 22 por esta:
$id_seccion = (isset($_GET['id_seccion']) && intval($_GET['id_seccion']) > 0) ? intval($_GET['id_seccion']) : 0;

if ($id_seccion <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "ID de sección no válido",
        "estudiantes" => []
    ]);
    exit();
}

// 2. Consulta a la tabla 'estudiante'
$sql = "SELECT id_estudiante, nombres_apellidos, cedula FROM estudiante WHERE id_seccion = ? ORDER BY nombres_apellidos ASC";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id_seccion);
    $stmt->execute();
    $result = $stmt->get_result();

    $estudiantes = array();
    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "estudiantes" => $estudiantes
    ]);

    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error SQL: " . $conexion->error,
        "estudiantes" => []
    ]);
}

$conexion->close();
?>