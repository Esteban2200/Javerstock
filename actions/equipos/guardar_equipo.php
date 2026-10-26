<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('crear_equipo');

/* =========================================================
   VALIDAR MÉTODO
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: ../../pages/equipos.php?error=acceso_invalido"
    );

    exit;
}

/* =========================================================
   DATOS DEL FORMULARIO
========================================================= */

$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$area = strtolower(trim($_POST['area'] ?? ''));

$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$numeroSerie = trim($_POST['numero_serie'] ?? '');

$edificio = trim($_POST['edificio'] ?? '');
$piso = trim($_POST['piso'] ?? '');
$salon = trim($_POST['salon'] ?? '');

$estado = strtolower(trim($_POST['estado'] ?? 'operativo'));

$descripcion = trim($_POST['descripcion'] ?? '');


/* =========================================================
   ÁREAS PERMITIDAS
========================================================= */

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

if (!in_array($area, $areasPermitidas, true)) {

    header(
        "Location: ../../pages/equipos.php?error=area_invalida"
    );

    exit;
}


/* =========================================================
   DATOS OBLIGATORIOS
========================================================= */

if (
    $codigo === '' ||
    $nombre === '' ||
    $categoria === ''
) {

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=datos_incompletos"
    );

    exit;
}


/* =========================================================
   ESTADOS PERMITIDOS
========================================================= */

$estadosPermitidos = [
    'operativo',
    'danado',
    'mantenimiento',
    'reparado',
    'entregado'
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'operativo';
}


/* =========================================================
   VERIFICAR CÓDIGO DUPLICADO
========================================================= */

$stmt = $conn->prepare(
    "SELECT id
     FROM equipos
     WHERE codigo = ?
     LIMIT 1"
);

if (!$stmt) {

    die(
        "Error preparando la consulta de verificación: " .
        htmlspecialchars($conn->error)
    );
}

$stmt->bind_param("s", $codigo);

if (!$stmt->execute()) {

    $errorBD = $stmt->error;

    $stmt->close();

    die(
        "Error ejecutando la consulta de verificación: " .
        htmlspecialchars($errorBD)
    );
}

$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {

    $stmt->close();

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=codigo_duplicado"
    );

    exit;
}

$stmt->close();


/* =========================================================
   INSERTAR EQUIPO
========================================================= */

$sql = "
    INSERT INTO equipos
    (
        codigo,
        nombre,
        categoria,
        area,
        marca,
        modelo,
        numero_serie,
        edificio,
        piso,
        salon,
        estado,
        descripcion
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";


/* =========================================================
   PREPARAR CONSULTA
========================================================= */

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "<h2>Error preparando el registro del equipo</h2>" .
        "<p><strong>Error MySQL:</strong> " .
        htmlspecialchars($conn->error) .
        "</p>" .
        "<hr>" .
        "<p><strong>Consulta:</strong></p>" .
        "<pre>" .
        htmlspecialchars($sql) .
        "</pre>"
    );
}


/* =========================================================
   ASIGNAR PARÁMETROS
========================================================= */

$stmt->bind_param(
    "ssssssssssss",
    $codigo,
    $nombre,
    $categoria,
    $area,
    $marca,
    $modelo,
    $numeroSerie,
    $edificio,
    $piso,
    $salon,
    $estado,
    $descripcion
);


/* =========================================================
   EJECUTAR
========================================================= */

if (!$stmt->execute()) {

    $errorBD = $stmt->error;

    $stmt->close();

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=" .
        urlencode($errorBD)
    );

    exit;
}


/* =========================================================
   CERRAR
========================================================= */

$stmt->close();


/* =========================================================
   REDIRECCIÓN EXITOSA
========================================================= */

header(
    "Location: ../../pages/equipos.php?area=" .
    urlencode($area) .
    "&msg=equipo_registrado"
);

exit;

?>