<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');
include 'conexion.php';

$id_estudiante = isset($_GET['id_estudiante']) ? intval($_GET['id_estudiante']) : 0;

if ($id_estudiante <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de estudiante no válido"]);
    exit();
}

// 1. Obtener la sección del estudiante
$sqlEst = "SELECT id_seccion FROM estudiante WHERE id_estudiante = $id_estudiante";
$resEst = $conn->query($sqlEst);
if (!$resEst || $resEst->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Estudiante no encontrado"]);
    exit();
}
$rowEst = $resEst->fetch_assoc();
$id_seccion = $rowEst['id_seccion'];

// 2. Obtener TODAS las materias y horarios de esa sección haciendo JOIN con la tabla materia y seccion
$sqlHorarios = "SELECT h.id_horario, h.dia, h.hora, m.nombre AS materia, m.trayecto, s.nombre AS seccion 
                FROM horario h
                INNER JOIN materia m ON h.id_materia = m.id_materia
                INNER JOIN seccion s ON h.id_seccion = s.id_seccion
                WHERE h.id_seccion = $id_seccion";

$resHorarios = $conn->query($sqlHorarios);

$materias = [];
if ($resHorarios && $resHorarios->num_rows > 0) {
    while ($horario = $resHorarios->fetch_assoc()) {
        $id_horario = $horario['id_horario'];
        
        // 3. Buscar el historial de asistencia del alumno para este horario específico
        $sqlAsis = "SELECT fecha, estado FROM asistencia 
                    WHERE id_horario = $id_horario AND id_estudiante = $id_estudiante 
                    ORDER BY fecha DESC";
        $resAsis = $conn->query($sqlAsis);
        
        $registros = [];
        $presentes = 0;
        
        if ($resAsis && $resAsis->num_rows > 0) {
            while ($asis = $resAsis->fetch_assoc()) {
                $registros[] = [
                    "fecha" => $asis['fecha'],
                    "estado" => $asis['estado']
                ];
                if ($asis['estado'] == 'Presente') $presentes++;
            }
        }
        
        $materias[] = [
            "id_horario" => $id_horario,
            "materia" => $horario['materia'],
            "codigo" => "MAT-" . $horario['id_horario'], // Código de referencia
            "dia" => $horario['dia'],
            "hora" => $horario['hora'],
            "trayecto" => $horario['trayecto'],
            "seccion" => $horario['seccion'],
            "total_asistencias" => $presentes,
            "registros" => $registros
        ];
    }
}

echo json_encode([
    "status" => "success",
    "materias" => $materias
]);
exit();
?>