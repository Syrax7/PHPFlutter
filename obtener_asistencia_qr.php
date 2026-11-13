<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=UTF-8");

include 'conexion.php';

if (!isset($conexion) && isset($conn)) {
    $conexion = $conn;
}

$id_seccion = isset($_GET['id_seccion']) ? intval($_GET['id_seccion']) : 0;
$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0; // 👈 NUEVO
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Ahora validamos que vengan ambos datos
if ($id_seccion <= 0 || $id_horario <= 0) {
    echo json_encode(["status" => "error", "message" => "Sección o horario no válido", "estudiantes" => []]);
    exit();
}

// Consultar filtrando por sección, fecha Y HORARIO
$sql = "SELECT 
            e.id_estudiante, 
            e.nombres_apellidos, 
            e.cedula
        FROM asistencia a
        INNER JOIN estudiante e ON a.id_estudiante = e.id_estudiante
        WHERE e.id_seccion = ? AND a.fecha = ? AND a.id_horario = ? AND a.estado = 'Presente'
        ORDER BY a.id_asistencia DESC";

$stmt = $conexion->prepare($sql);

if ($stmt) {
    // "isi" significa: Entero (seccion), String (fecha), Entero (horario)
    $stmt->bind_param("isi", $id_seccion, $fecha, $id_horario); 
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
    echo json_encode(["status" => "error", "message" => "Error SQL: " . $conexion->error, "estudiantes" => []]);
}

$conexion->close();
?>