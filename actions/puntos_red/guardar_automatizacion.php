<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

exigirPermiso('configuracion');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/puntos_red.php?area=automatizacion");
    exit;
}

$sistema = trim($_POST['sistema'] ?? '');
$item = trim($_POST['item'] ?? '');
$edificio = trim($_POST['edificio'] ?? '');
$zona = trim($_POST['zona'] ?? '');
$ip = trim($_POST['ip'] ?? '');
$mac = trim($_POST['mac'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$servidor = trim($_POST['servidor'] ?? '');
$administracion = trim($_POST['administracion'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$camara_server = trim($_POST['camara_server'] ?? '');
$gateway = trim($_POST['gateway'] ?? '');
$dns = trim($_POST['dns'] ?? '');
$mascara = trim($_POST['mascara'] ?? '');
$rack = trim($_POST['rack'] ?? '');
$switch = trim($_POST['switch'] ?? '');
$sw_port = trim($_POST['sw_port'] ?? '');
$vlan = trim($_POST['vlan'] ?? '');
$subsistema = trim($_POST['subsistema'] ?? '');
$estado = trim($_POST['estado'] ?? 'Activo');

if ($sistema === '' || $edificio === '') {
    header(
        "Location: ../../pages/puntos_red.php?area=automatizacion&error=campos_obligatorios"
    );
    exit;
}

$sql = "
    INSERT INTO puntos_seguridad
    (
        sistema,
        item,
        edificio,
        zona,
        ip,
        mac,
        ubicacion,
        servidor,
        administracion,
        observaciones,
        camara_server,
        gateway,
        dns,
        mascara,
        rack,
        switch,
        sw_port,
        vlan,
        subsistema,
        estado
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparando consulta: " . $conn->error);
}

$stmt->bind_param(
    "ssssssssssssssssssss",
    $sistema,
    $item,
    $edificio,
    $zona,
    $ip,
    $mac,
    $ubicacion,
    $servidor,
    $administracion,
    $observaciones,
    $camara_server,
    $gateway,
    $dns,
    $mascara,
    $rack,
    $switch,
    $sw_port,
    $vlan,
    $subsistema,
    $estado
);

if ($stmt->execute()) {

    header(
        "Location: ../../pages/puntos_red.php?area=automatizacion&msg=automatizacion_creada"
    );

} else {

    header(
        "Location: ../../pages/puntos_red.php?area=automatizacion&error="
        . urlencode($stmt->error)
    );
}

$stmt->close();

exit;