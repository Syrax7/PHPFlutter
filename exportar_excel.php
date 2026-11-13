<?php
// Forzar codificación UTF-8 y descarga de archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_asistencia_consolidado.csv');

include 'conexion.php';

$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;

if ($id_horario <= 0) {
    die("ID de horario no válido");
}

$output = fopen('php://output', 'w');

// Añadir BOM de UTF-8 para que Excel reconozca tildes y eñes correctamente
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. Obtener todas las fechas únicas de asistencia ordenadas cronológicamente
$sqlFechas = "SELECT DISTINCT fecha FROM asistencia WHERE id_horario = $id_horario ORDER BY fecha ASC";
$resultFechas = $conn->query($sqlFechas);
$fechas = [];
while ($row = $resultFechas->fetch_assoc()) {
    $fechas[] = $row['fecha'];
}

// 2. Obtener la lista única de estudiantes inscritos en este horario
$sqlEstudiantes = "SELECT DISTINCT e.id_estudiante, e.cedula, e.nombres_apellidos 
                   FROM asistencia a 
                   INNER JOIN estudiante e ON a.id_estudiante = e.id_estudiante 
                   WHERE a.id_horario = $id_horario 
                   ORDER BY e.nombres_apellidos ASC";
$resultEstudiantes = $conn->query($sqlEstudiantes);

// 3. Mapear todos los estados de asistencia por [id_estudiante][fecha]
$sqlAsistencias = "SELECT id_estudiante, fecha, estado FROM asistencia WHERE id_horario = $id_horario";
$resultAsistencias = $conn->query($sqlAsistencias);
$matrizAsistencia = [];
while ($row = $resultAsistencias->fetch_assoc()) {
    $matrizAsistencia[$row['id_estudiante']][$row['fecha']] = $row['estado'];
}

// 4. Construir la cabecera del CSV
$encabezado = ['#', 'Cedula', 'Estudiante'];
foreach ($fechas as $f) {
    $encabezado[] = $f; // Añade cada fecha como una columna separada
}
$encabezado[] = 'Total Asistencias';
$encabezado[] = 'Total Inasistencias';

// Escribir la cabecera usando punto y coma (;) como separador para Excel en español
fputcsv($output, $encabezado, ';');

// 5. Rellenar los datos de cada estudiante
if ($resultEstudiantes && $resultEstudiantes->num_rows > 0) {
    $contador = 1;
    while ($est = $resultEstudiantes->fetch_assoc()) {
        $fila = [
            $contador,
            'V-' . $est['cedula'],
            $est['nombres_apellidos']
        ];

        $totalAsistencias = 0;
        $totalInasistencias = 0;

        // Agregar el estado correspondiente a cada fecha
        foreach ($fechas as $f) {
            $estado = isset($matrizAsistencia[$est['id_estudiante']][$f]) ? $matrizAsistencia[$est['id_estudiante']][$f] : '';
            
            if ($estado == 'Presente') {
                $fila[] = 'P';
                $totalAsistencias++;
            } elseif ($estado == 'Ausente') {
                $fila[] = 'A';
                $totalInasistencias++;
            } else {
                $fila[] = '-';
            }
        }

        // Agregar los totales al final de la fila
        $fila[] = $totalAsistencias;
        $fila[] = $totalInasistencias;

        // Escribir la fila en el archivo CSV
        fputcsv($output, $fila, ';');
        $contador++;
    }
}

fclose($output);
exit();
?>