<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

exigirPermiso('configuracion');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/puntos_red.php?area=audiovisuales");
    exit;
}

$edificio = trim($_POST['edificio'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$aula = trim($_POST['aula'] ?? '');
$equipo = trim($_POST['equipo'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$sitio_instalacion = trim($_POST['sitio_instalacion'] ?? '');
$tipo_puerto = trim($_POST['tipo_puerto'] ?? '');
$mac = trim($_POST['mac'] ?? '');
$ip = trim($_POST['ip'] ?? '');
$mascara = trim($_POST['mascara'] ?? '');
$serial = trim($_POST['serial'] ?? '');
$punto = trim($_POST['punto'] ?? '');
$switch = trim($_POST['switch'] ?? '');
$puerto = trim($_POST['puerto'] ?? '');
$estado = trim($_POST['estado'] ?? 'Activo');

if ($edificio === '' || $equipo === '') {
    header(
        "Location: ../../pages/puntos_red.php?area=audiovisuales&error=campos_obligatorios"
    );
    exit;
}

$sql = "
    INSERT INTO puntos_red_audiovisuales
    (
        edificio,
        ubicacion,
        aula,
        equipo,
        marca,
        sitio_instalacion,
        tipo_puerto,
        mac,
        ip,
        mascara,
        serial,
        punto,
        switch,
        puerto,
        estado
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparando consulta: " . $conn->error);
}

$stmt->bind_param(
    "sssssssssssssss",
    $edificio,
    $ubicacion,
    $aula,
    $equipo,
    $marca,
    $sitio_instalacion,
    $tipo_puerto,
    $mac,
    $ip,
    $mascara,
    $serial,
    $punto,
    $switch,
    $puerto,
    $estado
);

if ($stmt->execute()) {

    header(
        "Location: ../../pages/puntos_red.php?area=audiovisuales&msg=audiovisual_creado"
    );

} else {

    header(
        "Location: ../../pages/puntos_red.php?area=audiovisuales&error="
        . urlencode($stmt->error)
    );
}

$stmt->close();

exit;