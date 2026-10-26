<?php

session_start();

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('salida_stock');

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
    ?? 'Salida de inventario';

/* =========================================================
   CONSULTAR PRODUCTO Y STOCK
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
   ÁREA REAL DEL PRODUCTO
========================================================= */

$areaProducto = strtolower(trim($producto['area'] ?? ''));

if (
    $areaProducto !== 'audiovisuales' &&
    $areaProducto !== 'automatizacion'
) {
    $areaProducto = $area ?: 'audiovisuales';
}

/* =========================================================
   VALIDAR STOCK
========================================================= */

$stockActual = (int)$producto['cantidad'];

if ($cantidad > $stockActual) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($areaProducto) .
        "&error=stock_insuficiente"
    );

    exit;
}

/* =========================================================
   DESCONTAR STOCK
========================================================= */

$stmt = $conn->prepare("
    UPDATE productos
    SET cantidad = cantidad - ?
    WHERE id = ?
      AND cantidad >= ?
");

if (!$stmt) {
    die("Error preparando actualización: " . $conn->error);
}

$stmt->bind_param(
    "iii",
    $cantidad,
    $producto_id,
    $cantidad
);

if (!$stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($areaProducto) .
        "&error=salida_error"
    );

    exit;
}

$filasAfectadas = $stmt->affected_rows;

$stmt->close();

/* =========================================================
   VERIFICAR QUE REALMENTE SE DESCONTÓ
========================================================= */

if ($filasAfectadas !== 1) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($areaProducto) .
        "&error=stock_error"
    );

    exit;
}

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
        'Salida',
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
    "&msg=salida_ok"
);

exit;