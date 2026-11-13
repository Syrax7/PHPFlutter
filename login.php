<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Validar petición de pre-vuelo (CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexión a la base de datos en la nube
include 'conexion.php';



if ($conexion->connect_error) {
    die(json_encode(array("status" => "error", "message" => "Error de conexión a la base de datos")));
}

// Recibir los datos POST
$cedula = $_POST['cedula'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

if (empty($cedula) || empty($contrasena)) {
    echo json_encode(array("status" => "error", "message" => "Campos vacíos en el servidor"));
    exit;
}

// 1. Buscar en tabla 'estudiante' haciendo JOIN con 'seccion' para obtener los datos reales
$queryEstudiante = "SELECT 
                        e.id_estudiante, 
                        e.nombres_apellidos, 
                        e.cedula, 
                        s.id_seccion,
                        s.nombre AS seccion_nombre, 
                        s.trayecto 
                    FROM estudiante e
                    LEFT JOIN seccion s ON e.id_seccion = s.id_seccion 
                    WHERE e.cedula = '$cedula' AND e.clave = '$contrasena'";

$resEstudiante = $conexion->query($queryEstudiante);

if ($resEstudiante && $resEstudiante->num_rows > 0) {
    $datosEstudiante = $resEstudiante->fetch_assoc();
    
    $id_estudiante = $datosEstudiante['id_estudiante'] ?? $datosEstudiante['id'] ?? 0;

    echo json_encode(array(
        "status"        => "success",
        "rol"           => "Estudiante",
        "id_estudiante" => $id_estudiante,                       
        "nombre"        => $datosEstudiante['nombres_apellidos'],
        "cedula"        => $datosEstudiante['cedula'],
        "id_seccion"    => $datosEstudiante['id_seccion'],
        "trayecto"      => $datosEstudiante['trayecto'],           
        "seccion"       => $datosEstudiante['seccion_nombre']   
    ));
    exit;
}

// =========================================================================
// 2. BUSCAR EN TABLA 'docente' (SOPORTA 'Admin' O CÉDULA NORMAL)
// =========================================================================
if (strcasecmp($cedula, "Admin") == 0) {
    // Si escribe "Admin", buscamos por la especialidad de administrador
    $queryDocente = "SELECT * FROM docente WHERE especialidad = 'Admin' AND clave = '$contrasena'";
} else {
    // Búsqueda normal por cédula de docente
    $queryDocente = "SELECT * FROM docente WHERE cedula = '$cedula' AND clave = '$contrasena'";
}

$resDocente = $conexion->query($queryDocente);

if ($resDocente && $resDocente->num_rows > 0) {
    $datosDocente = $resDocente->fetch_assoc();
    
    $id_docente = $datosDocente['id_docente'] ?? $datosDocente['id'] ?? 0;

    // Detectar si es el Administrador o un Docente común
    $rol = "Docente";
    if (trim($datosDocente['especialidad']) === 'Admin' || $id_docente == 1) {
        $rol = "Administrador";
    }

    echo json_encode(array(
        "status"     => "success",
        "rol"        => $rol, // 👈 Devolverá "Administrador" o "Docente"
        "id_docente" => $id_docente,
        "nombre"     => $datosDocente['nombres_apellidos'],
        "cedula"     => $datosDocente['cedula']
    ));
    exit;
}

// 3. Si no coincide ninguno
echo json_encode(array("status" => "error", "message" => "Usuario o contraseña incorrecta"));

$conexion->close();
?>