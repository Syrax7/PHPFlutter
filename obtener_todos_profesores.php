<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

// Excluimos al Administrador (id_docente = 1)
$queryDocentes = "SELECT * FROM docente WHERE id_docente != 1";
$resDocentes = $conexion->query($queryDocentes);

$profesores = array();

if ($resDocentes && $resDocentes->num_rows > 0) {
    while ($doc = $resDocentes->fetch_assoc()) {
        $id_docente = $doc['id_docente'];
        
        // Consultamos las materias y secciones unidas a través de la tabla 'horario'
        $queryMaterias = "SELECT h.id_horario, m.nombre AS nombre_materia, m.trayecto, s.nombre AS seccion, h.dia, h.hora 
                  FROM horario h
                  INNER JOIN materia m ON h.id_materia = m.id_materia
                  INNER JOIN seccion s ON h.id_seccion = s.id_seccion
                  WHERE h.id_docente = $id_docente";
                          
        $resMaterias = $conexion->query($queryMaterias);
        $materias = array();
        
        if ($resMaterias) {
            while ($mat = $resMaterias->fetch_assoc()) {
                $materias[] = $mat;
            }
        }
        
        $doc['materias'] = $materias;
        $profesores[] = $doc;
    }
    echo json_encode(array("status" => "success", "profesores" => $profesores));
} else {
    echo json_encode(array("status" => "success", "profesores" => array()));
}
$conexion->close();
?>