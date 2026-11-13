<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

// Consultamos todas las secciones de la tabla 'seccion'
$querySecciones = "SELECT * FROM seccion";
$resSecciones = $conexion->query($querySecciones);

$secciones = array();

if ($resSecciones && $resSecciones->num_rows > 0) {
    while ($sec = $resSecciones->fetch_assoc()) {
        $id_seccion = $sec['id_seccion'];
        
        // Obtenemos el nombre y trayecto de la sección
        $nombre_seccion = $sec['nombre'] ?? 'Sección';
        $trayecto = $sec['trayecto'] ?? '';

        // SE AGREGO h.id_horARIO EN EL SELECT
        $queryMaterias = "SELECT h.id_horario, h.id_materia, m.nombre AS nombre_materia, d.nombres_apellidos AS docente, h.dia, h.hora 
                  FROM horario h
                  INNER JOIN materia m ON h.id_materia = m.id_materia
                  LEFT JOIN docente d ON h.id_docente = d.id_docente
                  WHERE h.id_seccion = $id_seccion";
                    
        $resMaterias = $conexion->query($queryMaterias);
        $materias = array();
        
        if ($resMaterias) {
            while ($mat = $resMaterias->fetch_assoc()) {
                $materias[] = $mat;
            }
        }
        
        // Estructuramos el arreglo que recibirá Flutter
        $secciones[] = array(
            "id_seccion" => $id_seccion,
            "nombre" => $nombre_seccion,
            "trayecto" => $trayecto,
            "materias" => $materias
        );
    }
    echo json_encode(array("status" => "success", "secciones" => $secciones));
} else {
    echo json_encode(array("status" => "success", "secciones" => array()));
}
$conexion->close();
?>