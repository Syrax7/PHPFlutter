<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

// Recogemos cualquier posible variable que envíe la app
$cedula = $_POST['cedula'] ?? '';
$id_estudiante = $_POST['id_estudiante'] ?? '';
$contrasena_actual = $_POST['contrasena_actual'] ?? '';
$contrasena_nueva = $_POST['contrasena_nueva'] ?? '';

// Si ambas variables de identificación están vacías o faltan las contraseñas
if ((empty($cedula) && empty($id_estudiante)) || empty($contrasena_actual) || empty($contrasena_nueva)) {
    echo json_encode([
        "status" => "error", 
        "message" => "Faltan datos obligatorios",
        "debug_post" => $_POST // Esto te mostrará exactamente qué llegó al servidor si vuelve a fallar
    ]);
    exit();
}

// Buscar al estudiante por cédula o por ID en la base de datos
if (!empty($cedula)) {
    $sql = "SELECT clave FROM estudiante WHERE cedula = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cedula);
} else {
    $sql = "SELECT clave FROM estudiante WHERE id_estudiante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_estudiante);
}

$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Validar contraseña actual (puedes cambiar a password_verify si usas hash)
    if ($contrasena_actual === $row['clave']) {
        
        if (!empty($cedula)) {
            $update_sql = "UPDATE estudiante SET clave = ? WHERE cedula = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $contrasena_nueva, $cedula);
        } else {
            $update_sql = "UPDATE estudiante SET clave = ? WHERE id_estudiante = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $contrasena_nueva, $id_estudiante);
        }
        
        if ($update_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "¡Contraseña actualizada con éxito!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al actualizar en la base de datos"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "La contraseña actual es incorrecta"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Estudiante no encontrado"]);
}

$conn->close();
?>