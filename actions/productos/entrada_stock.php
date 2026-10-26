<?php

session_start();

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('entrada_stock');

/* =========================================================
   RECIBIR DATOS
========================================================= */

$producto_id = intval($_POST['producto_id'] ?? $_POST['producto'] ?? 0);
$cantidad    = intval($_POST['cantidad'] ?? 0);
$area        = strtolower(trim($_POST['area'] ?? ''));

/* =========================================================
   VALIDACIONES
========================================================= */

if ($producto_id <= 0 || $cantidad <= 0) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area ?: 'audiovisuales') .
        "&error=datos_invalidos"
    );

    exit;
}

/* =========================================================
   DATOS DEL USUARIO
========================================================= */

$usuario =
    $_SESSION['nombre']
    ?? $_SESSION['usuario']
    ?? 'Sistema';

$dependencia =
    $_POST['dependencia']
    ?? '';

$observacion =
    $_POST['observacion']
    ?? 'Entrada de inventario';

/* =========================================================
   CONSULTAR PRODUCTO
========================================================= */

$stmt = $conn->prepare("
    SELECT id, area, cantidad
    FROM productos
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Error preparando consulta: " . $conn->error);
}

$stmt->bind_param("i", $producto_id);
$stmt->execute();

$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();

$stmt->close();

if (!$producto) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area ?: 'audiovisuales') .
        "&error=producto_no_encontrado"
    );

    exit;
}

/* =========================================================
   USAR EL ÁREA REAL DEL PRODUCTO
========================================================= */

$areaProducto = strtolower(trim($producto['area'] ?? ''));

if (
    $areaProducto !== 'audiovisuales' &&
    $areaProducto !== 'automatizacion'
) {
    $areaProducto = $area ?: 'audiovisuales';
}

/* =========================================================
   ACTUALIZAR STOCK
========================================================= */

$stmt = $conn->prepare("
    UPDATE productos
    SET cantidad = cantidad + ?
    WHERE id = ?
");

if (!$stmt) {
    die("Error preparando actualización: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $cantidad,
    $producto_id
);

if (!$stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($areaProducto) .
        "&error=entrada_error"
    );

    exit;
}

$stmt->close();

/* =========================================================
   REGISTRAR MOVIMIENTO
========================================================= */

$stmt = $conn->prepare("
    INSERT INTO movimientos
    (
        producto_id,
        tipo,
        cantidad,
        usuario,
        dependencia,
        observacion,
        fecha
    )
    VALUES
    (
        ?,
        'Entrada',
        ?,
        ?,
        ?,
        ?,
        NOW()
    )
");

if (!$stmt) {
    die("Error preparando movimiento: " . $conn->error);
}

$stmt->bind_param(
    "iisss",
    $producto_id,
    $cantidad,
    $usuario,
    $dependencia,
    $observacion
);

if (!$stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($areaProducto) .
        "&error=movimiento_error"
    );

    exit;
}

$stmt->close();

/* =========================================================
   REGRESAR AL INVENTARIO CORRECTO
========================================================= */

header(
    "Location: ../../pages/inventario.php?area=" .
    urlencode($areaProducto) .
    "&msg=entrada_ok"
);

exit;