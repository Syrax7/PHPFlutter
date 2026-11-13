<?php
include 'conexion.php';

$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;

if ($id_horario <= 0) {
    die("ID de horario no válido");
}

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planilla de Control de Asistencia</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 10px; color: #333; }
        h2 { text-align: center; margin: 2px 0; color: #0F52BA; font-size: 16px; }
        h3 { text-align: center; margin: 2px 0 15px 0; color: #555; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: center; font-size: 11px; }
        th { background-color: #0F52BA; color: white; font-weight: bold; }
        td.nombre { text-align: left; white-space: nowrap; }
        .presente { color: green; font-weight: bold; }
        .ausente { color: red; font-weight: bold; }
        .total-col { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
    <h2>UNIVERSIDAD POLITÉCNICA TERRITORIAL DE LOS LLANOS</h2>
    <h3>Planilla de Consolidado y Control de Asistencia Estudiantil - Sede Calabozo</h3>
    
    <table>
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th style="width: 80px;">Cédula</th>
                <th style="width: 180px;">Nombre y Apellidos</th>
                <?php foreach ($fechas as $f): ?>
                    <th><?php echo date('d/m', strtotime($f)); ?></th>
                <?php endforeach; ?>
                <th style="width: 45px;">Total Asist.</th>
                <th style="width: 45px;">Total Inasist.</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($resultEstudiantes && $resultEstudiantes->num_rows > 0) {
                $contador = 1;
                while ($est = $resultEstudiantes->fetch_assoc()) {
                    $totalAsistencias = 0;
                    $totalInasistencias = 0;
                    
                    echo "<tr>";
                    echo "<td>{$contador}</td>";
                    echo "<td>V-{$est['cedula']}</td>";
                    echo "<td class='nombre'>{$est['nombres_apellidos']}</td>";
                    
                    // Celdas de fechas individuales
                    foreach ($fechas as $f) {
                        $estado = isset($matrizAsistencia[$est['id_estudiante']][$f]) ? $matrizAsistencia[$est['id_estudiante']][$f] : '';
                        
                        if ($estado == 'Presente') {
                            echo "<td class='presente'>P</td>";
                            $totalAsistencias++;
                        } elseif ($estado == 'Ausente') {
                            echo "<td class='ausente'>A</td>";
                            $totalInasistencias++;
                        } else {
                            echo "<td style='color: #ccc;'>-</td>";
                        }
                    }
                    
                    // Columnas de totales al final
                    echo "<td class='total-col' style='color: green;'>{$totalAsistencias}</td>";
                    echo "<td class='total-col' style='color: red;'>{$totalInasistencias}</td>";
                    echo "</tr>";
                    $contador++;
                }
            } else {
                $numCols = 5 + count($fechas);
                echo "<tr><td colspan='{$numCols}'>No hay registros de asistencia para esta materia.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>