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

// Conexión local a XAMPP
$servidor   = "localhost"; 
$usuario    = "root"; 
$clave      = ""; 
$base_datos = "control_asistencia"; 

$conexion = new mysqli($servidor, $usuario, $clave, $base_datos);

if ($conexion->connect_error) {
    die(json_encode(array("status" => "error", "message" => "Error de conexión local")));
}

// Recibir los datos desde Flutter
$cedula            = $_POST['cedula'] ?? '';
$contrasena        = $_POST['contrasena'] ?? '';
$nombres_apellidos = $_POST['nombres_apellidos'] ?? '';
$telefono          = $_POST['telefono'] ?? '';
$seccion_raw       = trim($_POST['seccion'] ?? '');  
$trayecto_raw      = trim($_POST['trayecto'] ?? ''); 

// Validar que no lleguen campos vacíos
if (empty($cedula) || empty($contrasena) || empty($nombres_apellidos) || empty($telefono) || empty($seccion_raw) || empty($trayecto_raw)) {
    echo json_encode(array("status" => "error", "message" => "Por favor, llene todos los campos"));
    exit;
}

// 1. Verificar si la cédula ya existe en la tabla estudiante
$checkCedula = "SELECT id_estudiante FROM estudiante WHERE cedula = '$cedula'";
$resultadoCheck = $conexion->query($checkCedula);

if ($resultadoCheck && $resultadoCheck->num_rows > 0) {
    echo json_encode(array("status" => "error", "message" => "Esta cédula ya se encuentra registrada"));
    exit;
}

// 2. Normalizar entradas: si escriben sólo "1", lo convierte a variaciones con "Trayecto" y "Seccion"
$trayecto_fmt = (is_numeric($trayecto_raw)) ? "Trayecto " . $trayecto_raw : $trayecto_raw;
$seccion_fmt  = (is_numeric($seccion_raw))  ? "Seccion " . $seccion_raw  : $seccion_raw;

// 3. BUSCAR EL ID_SECCION VINCULADO
// Busca tanto por texto formateado ("Seccion 1") como por variaciones en minúsculas
$querySeccion = "SELECT id_seccion FROM seccion 
                 WHERE (nombre = '$seccion_fmt' OR nombre = '$seccion_raw' OR nombre = 'Sección $seccion_raw') 
                 AND (trayecto = '$trayecto_fmt' OR trayecto = '$trayecto_raw' OR trayecto = 'trayecto $trayecto_raw')";

$resSeccion = $conexion->query($querySeccion);

if ($resSeccion && $resSeccion->num_rows > 0) {
    $filaSeccion = $resSeccion->fetch_assoc();
    $id_seccion_real = $filaSeccion['id_seccion']; 
} else {
    echo json_encode(array("status" => "error", "message" => "La sección o trayecto ingresado no existen en el sistema"));
    exit;
}

// 4. Insertar el nuevo estudiante
$sqlInsert = "INSERT INTO estudiante (nombres_apellidos, clave, cedula, telefono, id_seccion) 
              VALUES ('$nombres_apellidos', '$contrasena', '$cedula', '$telefono', '$id_seccion_real')";

if ($conexion->query($sqlInsert) === TRUE) {
    echo json_encode(array("status" => "success", "message" => "¡Estudiante registrado con éxito!"));
} else {
    echo json_encode(array("status" => "error", "message" => "Error al guardar: " . $conexion->error));
}

$conexion->close();
?>