<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

include 'conexion.php';

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos"]);
    exit();
}

$id_seccion = isset($_POST['id_seccion']) ? intval($_POST['id_seccion']) : 0;
$id_horario = isset($_POST['id_horario']) ? intval($_POST['id_horario']) : 0;
$fecha = isset($_POST['fecha']) ? $conn->real_escape_string($_POST['fecha']) : '';

if ($id_seccion <= 0 || $id_horario <= 0 || empty($fecha)) {
    echo json_encode(["status" => "error", "message" => "Parámetros incompletos"]);
    exit();
}

// 1. Obtener todos los estudiantes de la sección
$sql_estudiantes = "SELECT id_estudiante FROM estudiante WHERE id_seccion = $id_seccion";
$result_estudiantes = $conn->query($sql_estudiantes);

$contadorInasistencias = 0;

if ($result_estudiantes && $result_estudiantes->num_rows > 0) {
    while ($row = $result_estudiantes->fetch_assoc()) {
        $id_estudiante = $row['id_estudiante'];

        // 2. Verificar si ya tiene asistencia registrada para este horario y fecha
        $sql_check = "SELECT id_asistencia FROM asistencia WHERE id_estudiante = $id_estudiante AND id_horario = $id_horario && fecha = '$fecha'";
        $result_check = $conn->query($sql_check);

        // 3. Si no tiene registro, lo marcamos como 'Ausente'
        if ($result_check && $result_check->num_rows == 0) {
            $sql_insert = "INSERT INTO asistencia (id_estudiante, id_horario, fecha, estado) VALUES ($id_estudiante, $id_horario, '$fecha', 'Ausente')";
            if ($conn->query($sql_insert)) {
                $contadorInasistencias++;
            }
        }
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Asistencia cerrada. Se registraron $contadorInasistencias inasistencias."
]);
exit();
?>