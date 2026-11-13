<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

include 'conexion.php';

if (!isset($conexion) && isset($conn)) {
    $conexion = $conn;
}

// Configurar fecha actual de Venezuela
date_default_timezone_set('America/Caracas');

// Recibir JSON desde Flutter
$data = json_decode(file_get_contents("php://input"), true);

$id_horario  = isset($data['id_horario']) ? intval($data['id_horario']) : 0;
$fecha       = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d');
$asistencias = isset($data['asistencias']) ? $data['asistencias'] : [];

if ($id_horario <= 0 || empty($asistencias)) {
    echo json_encode([
        "status" => "error",
        "message" => "Datos incompletos para guardar asistencia"
    ]);
    exit();
}

$insertados = 0;

// Insertar registros en la tabla 'asistencia'
$sql = "INSERT INTO asistencia (fecha, estado, id_estudiante, id_horario) VALUES (?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    foreach ($asistencias as $item) {
        $id_estudiante = intval($item['id_estudiante']);
        // Aseguramos que empiece con Mayúscula ('Presente' o 'Ausente') como en tu BD
        $estado = ucfirst(strtolower(trim($item['estado']))); 

        $stmt->bind_param("ssii", $fecha, $estado, $id_estudiante, $id_horario);
        if ($stmt->execute()) {
            $insertados++;
        }
    }
    $stmt->close();

    echo json_encode([
        "status" => "success",
        "message" => "Asistencia guardada con éxito ($insertados registros)."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al preparar la consulta: " . $conexion->error
    ]);
}

$conexion->close();
?>