<?php

/* =========================================================
   ELIMINAR PRODUCTO - JAVERSTOCK
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/permisos.php';

exigirPermiso('eliminar_producto');


/* =========================================================
   ID
========================================================= */

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {

    header(
        'Location: ../../pages/inventario.php?error=producto_no_encontrado'
    );

    exit;
}


/* =========================================================
   BUSCAR PRODUCTO
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        area,
        imagen
    FROM productos
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {

    header(
        'Location: ../../pages/inventario.php?error=error_eliminar'
    );

    exit;
}


$stmt->bind_param(
    'i',
    $id
);

$stmt->execute();

$resultado =
    $stmt->get_result();

$producto =
    $resultado->fetch_assoc();

$stmt->close();


/* =========================================================
   PRODUCTO NO EXISTE
========================================================= */

if (!$producto) {

    header(
        'Location: ../../pages/inventario.php?error=producto_no_encontrado'
    );

    exit;
}


/* =========================================================
   ÁREA
========================================================= */

$area = strtolower(
    trim(
        $producto['area'] ?? 'audiovisuales'
    )
);

if (
    !in_array(
        $area,
        [
            'audiovisuales',
            'automatizacion'
        ],
        true
    )
) {

    $area = 'audiovisuales';
}


/* =========================================================
   VERIFICAR MOVIMIENTOS
========================================================= */

$stmtMov = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM movimientos
    WHERE producto_id = ?
");

if (!$stmtMov) {

    header(
        'Location: ../../pages/inventario.php?area=' .
        urlencode($area) .
        '&error=error_eliminar'
    );

    exit;
}


$stmtMov->bind_param(
    'i',
    $id
);

$stmtMov->execute();

$movimientos =
    $stmtMov
        ->get_result()
        ->fetch_assoc();

$stmtMov->close();


/* =========================================================
   NO ELIMINAR SI TIENE MOVIMIENTOS
========================================================= */

if (
    (int)($movimientos['total'] ?? 0) > 0
) {

    header(
        'Location: ../../pages/inventario.php?area=' .
        urlencode($area) .
        '&error=producto_tiene_movimientos'
    );

    exit;
}


/* =========================================================
   GUARDAR NOMBRE DE IMAGEN
========================================================= */

$imagenProducto =
    trim(
        (string)(
            $producto['imagen'] ?? ''
        )
    );


/* =========================================================
   ELIMINAR PRODUCTO
========================================================= */

$stmtDelete = $conn->prepare("
    DELETE FROM productos
    WHERE id = ?
    LIMIT 1
");

if (!$stmtDelete) {

    header(
        'Location: ../../pages/inventario.php?area=' .
        urlencode($area) .
        '&error=error_eliminar'
    );

    exit;
}


$stmtDelete->bind_param(
    'i',
    $id
);


/* =========================================================
   EJECUTAR
========================================================= */

if (!$stmtDelete->execute()) {

    $stmtDelete->close();

    header(
        'Location: ../../pages/inventario.php?area=' .
        urlencode($area) .
        '&error=error_eliminar'
    );

    exit;
}


$eliminado =
    ($stmtDelete->affected_rows > 0);

$stmtDelete->close();


/* =========================================================
   ELIMINAR IMAGEN DEL SERVIDOR
========================================================= */

if (
    $eliminado &&
    $imagenProducto !== '' &&
    $imagenProducto !== 'no-image.png'
) {

    $nombreImagen =
        basename($imagenProducto);

    $rutaImagen =
        __DIR__ .
        '/../../assets/uploads/productos/' .
        $nombreImagen;


    if (
        is_file($rutaImagen)
    ) {

        @unlink($rutaImagen);
    }
}


/* =========================================================
   REDIRECCIÓN
========================================================= */

if ($eliminado) {

    header(
        'Location: ../../pages/inventario.php?area=' .
        urlencode($area) .
        '&msg=producto_eliminado'
    );

    exit;
}


header(
    'Location: ../../pages/inventario.php?area=' .
    urlencode($area) .
    '&error=error_eliminar'
);

exit;

?>