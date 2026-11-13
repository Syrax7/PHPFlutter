<?php
$host = "rwrv36.h.filess.io";
$user = "control_asistencia_planebroke";
$password = "bdee8321206fb2405cf39637ce76cd21770c48c5";
$db = "control_asistencia_planebroke";
$port = 3307;

$conn = new mysqli($host, $user, $password, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Forzar charset utf8mb4 para las tildes y caracteres especiales
$conn->set_charset("utf8mb4");

// Creamos un alias para que funcione tanto con $conn como con $conexion
$conexion = $conn;
?>