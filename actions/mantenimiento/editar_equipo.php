<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

exigirPermiso('gestionar_mantenimiento');


/*
|--------------------------------------------------------------------------
| SOLO POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: ../../pages/mantenimiento.php?msg=error"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DATOS RECIBIDOS
|--------------------------------------------------------------------------
*/

$equipo_id = (int)($_POST['equipo_id'] ?? 0);

$codigo = trim(
    $_POST['codigo'] ?? ''
);

$nombre = trim(
    $_POST['nombre'] ?? ''
);

$marca = trim(
    $_POST['marca'] ?? ''
);

$modelo = trim(
    $_POST['modelo'] ?? ''
);

$edificio = trim(
    $_POST['edificio'] ?? ''
);

$piso = trim(
    $_POST['piso'] ?? ''
);

$salon = trim(
    $_POST['salon'] ?? ''
);

$estado = strtolower(
    trim($_POST['estado'] ?? '')
);

$area = strtolower(
    trim($_POST['area'] ?? '')
);

$descripcion_dano = trim(
    $_POST['descripcion_dano'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALIDACIÓN
|--------------------------------------------------------------------------
*/

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

$estadosPermitidos = [
    'operativo',
    'danado',
    'mantenimiento',
    'reparado',
    'entregado'
];

if (
    $equipo_id <= 0 ||
    $codigo === '' ||
    $nombre === '' ||
    !in_array($area, $areasPermitidas, true) ||
    !in_array($estado, $estadosPermitidos, true)
) {

    header(
        "Location: ../../pages/mantenimiento.php" .
        "?area=" . urlencode($area) .
        "&msg=error"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| ACTUALIZAR EQUIPO
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "UPDATE equipos
     SET
        codigo = ?,
        nombre = ?,
        marca = ?,
        modelo = ?,
        edificio = ?,
        piso = ?,
        salon = ?,
        estado = ?,
        area = ?,
        descripcion_dano = ?
     WHERE id = ?"
);


if (!$stmt) {

    die(
        "<h2>Error al editar equipo</h2>" .
        "<p>" .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        ) .
        "</p>"
    );
}


/*
|--------------------------------------------------------------------------
| BIND
|--------------------------------------------------------------------------
*/

$stmt->bind_param(
    "ssssssssssi",
    $codigo,
    $nombre,
    $marca,
    $modelo,
    $edificio,
    $piso,
    $salon,
    $estado,
    $area,
    $descripcion_dano,
    $equipo_id
);


/*
|--------------------------------------------------------------------------
| EJECUTAR
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    die(
        "<h2>Error al actualizar equipo</h2>" .
        "<p>" .
        htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) .
        "</p>"
    );
}


$stmt->close();


/*
|--------------------------------------------------------------------------
| REGRESAR
|--------------------------------------------------------------------------
*/

header(
    "Location: ../../pages/mantenimiento.php" .
    "?area=" . urlencode($area) .
    "&msg=actualizado"
);

exit;

?>