<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Fijar la zona horaria correcta para Venezuela
date_default_timezone_set('America/Caracas');

include 'conexion.php';

$id_estudiante = isset($_GET['id_estudiante']) ? intval($_GET['id_estudiante']) : 0;

if ($id_estudiante <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de estudiante inválido"]);
    exit();
}

$fecha_hoy = date('Y-m-d'); // Fecha exacta local

// Mapeo seguro para el día actual en español
$dias_ingles = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$dias_espanol = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
$dia_actual = str_replace($dias_ingles, $dias_espanol, date('l'));

$registros_hoy = [];

// 1. Obtener la sección del estudiante
$sql_est = "SELECT id_seccion FROM estudiante WHERE id_estudiante = $id_estudiante";
$res_est = $conn->query($sql_est);
$id_seccion = 0;
if ($res_est && $row_est = $res_est->fetch_assoc()) {
    $id_seccion = intval($row_est['id_seccion']);
}

// 2. Obtener las asistencias registradas exclusivamente el día de hoy
$asistencias_hoy = [];
$sql_asist_hoy = "SELECT id_horario, estado FROM asistencia WHERE id_estudiante = $id_estudiante AND fecha = '$fecha_hoy'";
$res_asist_hoy = $conn->query($sql_asist_hoy);
if ($res_asist_hoy && $res_asist_hoy->num_rows > 0) {
    while ($row_asist = $res_asist_hoy->fetch_assoc()) {
        $asistencias_hoy[intval($row_asist['id_horario'])] = $row_asist['estado'];
    }
}

$sql_horarios = "SELECT h.id_horario, m.nombre AS nombre_materia, s.nombre AS nombre_seccion, h.hora 
                     FROM horario h
                     INNER JOIN materia m ON h.id_materia = m.id_materia
                     INNER JOIN seccion s ON h.id_seccion = s.id_seccion
                     WHERE h.id_seccion = $id_seccion 
                     AND h.dia LIKE '%$dia_actual%'";

    $res_h = $conn->query($sql_horarios);
    if ($res_h && $res_h->num_rows > 0) {
        while($h_row = $res_h->fetch_assoc()) {
            $id_h = intval($h_row['id_horario']);
            
            // Si ya registró asistencia hoy, muestra su estado; si no, queda como "Pendiente"
            $estado_actual = isset($asistencias_hoy[$id_h]) ? $asistencias_hoy[$id_h] : "Pendiente";

            $registros_hoy[] = [
                "materia" => $h_row["nombre_materia"],
                "seccion" => $h_row["nombre_seccion"],
                "fecha" => $fecha_hoy . ' - ' . $h_row["hora"],
                "estado" => $estado_actual
            ];
        }
    }


echo json_encode([
    "status" => "success", 
    "registros" => $registros_hoy
]);

$conn->close();
?>