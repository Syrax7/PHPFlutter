<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=UTF-8");

include 'conexion.php';

if (!isset($conexion) && isset($conn)) {
    $conexion = $conn;
}

// Obtener datos enviados desde la App (JSON)
$data = json_decode(file_get_contents("php://input"), true);

$id_estudiante = isset($data['id_estudiante']) ? intval($data['id_estudiante']) : 0;
$id_horario = isset($data['id_horario']) ? intval($data['id_horario']) : 0;
$id_dispositivo = isset($data['id_dispositivo']) ? $data['id_dispositivo'] : '';
$timestamp_qr = isset($data['timestamp']) ? intval($data['timestamp']) : 0;

$fecha = date('Y-m-d');

if ($id_estudiante <= 0 || $id_horario <= 0 || empty($id_dispositivo) || $timestamp_qr <= 0) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos. Faltan parámetros de seguridad."]);
    exit();
}

// ==========================================
// 1. VALIDACIÓN DE TIEMPO (QR DINÁMICO)
// ==========================================
$tiempo_actual_ms = round(microtime(true) * 1000);
$diferencia_segundos = ($tiempo_actual_ms - $timestamp_qr) / 1000;

// Límite de 90 segundos (el QR cambia cada 30s en la app del profesor)
if ($diferencia_segundos > 90) {
    echo json_encode([
        "status" => "error", 
        "message" => "El código QR ha expirado. Pídele al profesor que te muestre el código actual."
    ]);
    exit();
}

// ==========================================
// 2. VALIDACIÓN DEL DISPOSITIVO (Un celular = Un alumno por clase)
// ==========================================
$fecha = date('Y-m-d');

// Borrar registros de días anteriores automáticamente
mysqli_query($conexion, "DELETE FROM control_dispositivos_hoy WHERE fecha < '$fecha'");

// Buscamos si este teléfono ya fue usado hoy por algún estudiante
$queryCheck = "SELECT id_estudiante FROM control_dispositivos_hoy WHERE id_dispositivo = '$id_dispositivo' AND fecha = '$fecha'";
$resultadoDisp = mysqli_query($conexion, $queryCheck);

if (mysqli_num_rows($resultadoDisp) > 0) {
    $row = mysqli_fetch_assoc($resultadoDisp);
    
    // Si el teléfono fue usado por OTRO estudiante (no el que está escaneando ahora)
    if ($row['id_estudiante'] != $id_estudiante) {
        echo json_encode([
            "status" => "error", 
            "message" => "Este teléfono ya fue utilizado hoy para registrar la asistencia de otro estudiante."
        ]);
        exit();
    }
} else {
    // Si el teléfono no ha sido usado hoy, lo registramos
    // Primero verificamos si este alumno ya tiene un registro (por si acaso)
    $checkEstudiante = "SELECT id_control FROM control_dispositivos_hoy WHERE id_estudiante = '$id_estudiante' AND fecha = '$fecha'";
    $resEst = mysqli_query($conexion, $checkEstudiante);
    
    if (mysqli_num_rows($resEst) == 0) {
        mysqli_query($conexion, "INSERT INTO control_dispositivos_hoy (id_estudiante, id_dispositivo, fecha) VALUES ('$id_estudiante', '$id_dispositivo', '$fecha')");
    }
}

// ==========================================
// 3. VERIFICAR SI YA REGISTRÓ ASISTENCIA HOY
// ==========================================
$checkSql = "SELECT id_asistencia FROM asistencia WHERE id_estudiante = ? AND id_horario = ? AND fecha = ?";
$checkStmt = $conexion->prepare($checkSql);
$checkStmt->bind_param("iis", $id_estudiante, $id_horario, $fecha);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["status" => "warning", "message" => "Ya has registrado asistencia para esta clase hoy."]);
    $checkStmt->close();
    $conexion->close();
    exit();
}
$checkStmt->close();

// ==========================================
// 4. INSERTAR NUEVA ASISTENCIA
// ==========================================
$sql = "INSERT INTO asistencia (fecha, estado, id_estudiante, id_horario) VALUES (?, 'Presente', ?, ?)";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sii", $fecha, $id_estudiante, $id_horario);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "¡Asistencia registrada con éxito!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar registro."]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Error de consulta SQL: " . $conexion->error]);
}

$conexion->close();
?>