<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('editar_equipo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/equipos.php?error=acceso_invalido");
    exit;
}

/* =========================================================
   DATOS
========================================================= */

$id = (int)($_POST['id'] ?? 0);

$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');

$area = strtolower(
    trim($_POST['area'] ?? '')
);

$categoria = strtolower(
    trim($_POST['categoria'] ?? '')
);

$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');

$numeroSerie = trim(
    $_POST['numero_serie'] ?? ''
);

$referencia = trim(
    $_POST['referencia'] ?? ''
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
    trim($_POST['estado'] ?? 'operativo')
);

$descripcion = trim(
    $_POST['descripcion'] ?? ''
);


/* =========================================================
   VALIDAR ID
========================================================= */

if ($id <= 0) {

    header(
        "Location: ../../pages/equipos.php?error=id_invalido"
    );

    exit;
}


/* =========================================================
   VALIDAR ÁREA
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
   VALIDAR CATEGORÍA
========================================================= */

$categoriasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

if (!in_array($categoria, $categoriasPermitidas, true)) {

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=categoria_invalida"
    );

    exit;
}


/* =========================================================
   VALIDAR DATOS OBLIGATORIOS
========================================================= */

if ($codigo === '' || $nombre === '') {

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=datos_incompletos"
    );

    exit;
}


/* =========================================================
   VALIDAR ESTADO
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

$stmt = $conn->prepare("
    SELECT id
    FROM equipos
    WHERE codigo = ?
      AND id <> ?
    LIMIT 1
");

if (!$stmt) {

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=error_consulta"
    );

    exit;
}

$stmt->bind_param(
    "si",
    $codigo,
    $id
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {

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
   ACTUALIZAR EQUIPO
========================================================= */

$sql = "
    UPDATE equipos
    SET
        codigo = ?,
        nombre = ?,
        area = ?,
        categoria = ?,
        marca = ?,
        modelo = ?,
        numero_serie = ?,
        referencia = ?,
        edificio = ?,
        piso = ?,
        salon = ?,
        estado = ?,
        descripcion = ?
    WHERE id = ?
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    header(
        "Location: ../../pages/equipos.php?area=" .
        urlencode($area) .
        "&error=error_preparando"
    );

    exit;
}


/*
 * 13 campos de texto + 1 entero
 *
 * s = string
 * i = integer
 */

$stmt->bind_param(
    "sssssssssssssi",
    $codigo,
    $nombre,
    $area,
    $categoria,
    $marca,
    $modelo,
    $numeroSerie,
    $referencia,
    $edificio,
    $piso,
    $salon,
    $estado,
    $descripcion,
    $id
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


$stmt->close();


/* =========================================================
   REDIRECCIÓN
========================================================= */

header(
    "Location: ../../pages/equipos.php?area=" .
    urlencode($area) .
    "&msg=equipo_actualizado"
);

exit;

?>