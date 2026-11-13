<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'conexion.php';

$id_docente = $_POST['id_docente'] ?? '';
$contrasena_actual = $_POST['contrasena_actual'] ?? '';
$contrasena_nueva = $_POST['contrasena_nueva'] ?? '';

if (empty($id_docente) || empty($contrasena_actual) || empty($contrasena_nueva)) {
    echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
    exit();
}

// 1. Consultar usando la columna 'clave' que tienes en tu tabla 'docente'
$sql = "SELECT clave FROM docente WHERE id_docente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_docente);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Compara con la clave actual de la base de datos
    if ($contrasena_actual === $row['clave']) {
        
        // 2. Actualizar la nueva contraseña en la columna 'clave'
        $update_sql = "UPDATE docente SET clave = ? WHERE id_docente = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $contrasena_nueva, $id_docente);
        
        if ($update_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "¡Contraseña actualizada con éxito!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al actualizar en la base de datos"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "La contraseña actual es incorrecta"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Docente no encontrado"]);
}

$conn->close();
?>