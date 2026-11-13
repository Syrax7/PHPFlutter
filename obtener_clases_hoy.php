<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. Configurar zona horaria de Venezuela
date_default_timezone_set('America/Caracas');

// 2. Incluir archivo de conexión
include 'conexion.php';

// Compatibilidad por si usas $conn o $conexion
if (!isset($conexion) && isset($conn)) {
    $conexion = $conn;
}

if (!isset($conexion) || $conexion === null || $conexion->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Error de conexión a la base de datos",
        "clases" => []
    ]);
    exit();
}

// 3. Obtener el ID del docente enviado desde el navegador / Flutter
$id_docente = isset($_GET['id_docente']) ? intval($_GET['id_docente']) : 0;

if ($id_docente <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "ID de docente no válido ($id_docente)",
        "clases" => []
    ]);
    exit();
}

// 4. Determinar el día de la semana sin tildes (para coincidir con 'Miercoles' y 'Sabado' de tu BD)
$dias_semana = array("Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado");
$dia_hoy = $dias_semana[date('w')];

// 5. Consulta SQL exacta apuntando a tus tablas reales: horario, materia y seccion
$sql = "SELECT 
            h.id_horario,
            h.id_seccion,
            h.dia,
            h.hora,
            m.nombre AS nombre_materia,
            s.nombre AS seccion,
            s.trayecto
        FROM horario h
        LEFT JOIN materia m ON h.id_materia = m.id_materia
        LEFT JOIN seccion s ON h.id_seccion = s.id_seccion
        WHERE h.id_docente = ? AND LOWER(TRIM(h.dia)) = LOWER(TRIM(?))";

$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("is", $id_docente, $dia_hoy);
    $stmt->execute();
    $result = $stmt->get_result();
$clases = array();
while ($row = $result->fetch_assoc()) {
    // Separamos "10:00-12:00" para enviarle a Flutter hora_inicio y hora_fin
    $partes_hora = explode('-', $row['hora']);
    $row['hora_inicio'] = isset($partes_hora[0]) ? trim($partes_hora[0]) : $row['hora'];
    $row['hora_fin']    = isset($partes_hora[1]) ? trim($partes_hora[1]) : '';

    // 2. Unir Trayecto y Sección (ej: "Trayecto 3" + " " + "Seccion 3")
    $row['seccion'] = trim($row['trayecto']) . " " . trim($row['seccion']);

    $clases[] = $row;
}

    echo json_encode([
        "status" => "success",
        "dia_consultado" => $dia_hoy,
        "clases" => $clases
    ]);

    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error SQL: " . $conexion->error,
        "clases" => []
    ]);
}

$conexion->close();
?>