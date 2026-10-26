<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('crear_producto');


/* =========================================================
   RECIBIR DATOS
========================================================= */

$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$area = strtolower(trim($_POST['area'] ?? ''));
$categoria = trim($_POST['categoria'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$referencia = trim($_POST['referencia'] ?? '');
$cantidad = (int)($_POST['cantidad'] ?? 0);
$stockMinimo = (int)($_POST['stock_minimo'] ?? 0);
$ubicacion = trim($_POST['ubicacion'] ?? '');


/* =========================================================
   VALIDAR ÁREA
========================================================= */

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

if (!in_array($area, $areasPermitidas, true)) {

    $area = 'audiovisuales';

}


/* =========================================================
   VALIDAR DATOS
========================================================= */

if (
    $codigo === '' ||
    $nombre === '' ||
    $categoria === ''
) {

    mostrarError(
        'Datos incompletos',
        'Debes completar código, producto y categoría.',
        $area
    );

}


/* =========================================================
   VERIFICAR CÓDIGO DUPLICADO
========================================================= */

$stmtDuplicado = $conn->prepare(
    "SELECT id
     FROM productos
     WHERE codigo = ?
     LIMIT 1"
);

if (!$stmtDuplicado) {

    mostrarError(
        'Error',
        'No se pudo verificar el código del producto.',
        $area
    );

}

$stmtDuplicado->bind_param(
    "s",
    $codigo
);

$stmtDuplicado->execute();

$resultadoDuplicado = $stmtDuplicado->get_result();

$codigoExiste = $resultadoDuplicado->num_rows > 0;

$stmtDuplicado->close();


if ($codigoExiste) {

    mostrarError(
        'Código duplicado',
        'El código "' . $codigo . '" ya existe en el inventario.',
        $area
    );

}


/* =========================================================
   ESTADO AUTOMÁTICO
========================================================= */

if ($cantidad <= 0) {

    $estado = 'Agotado';

} elseif ($cantidad <= $stockMinimo) {

    $estado = 'Bajo Stock';

} else {

    $estado = 'Disponible';

}


/* =========================================================
   IMAGEN
========================================================= */

$imagen = 'no-image.png';

if (
    isset($_FILES['imagen']) &&
    $_FILES['imagen']['error'] === UPLOAD_ERR_OK
) {

    $extension = strtolower(
        pathinfo(
            $_FILES['imagen']['name'],
            PATHINFO_EXTENSION
        )
    );

    $extensionesPermitidas = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];

    if (in_array($extension, $extensionesPermitidas, true)) {

        $nombreImagen =
            uniqid('producto_', true) .
            '.' .
            $extension;

        $directorio =
            "../../assets/uploads/productos/";

        $rutaImagen =
            $directorio .
            $nombreImagen;

        if (
            move_uploaded_file(
                $_FILES['imagen']['tmp_name'],
                $rutaImagen
            )
        ) {

            $imagen = $nombreImagen;

        }

    }

}


/* =========================================================
   INSERTAR PRODUCTO
========================================================= */

$sql = "
    INSERT INTO productos
    (
        codigo,
        nombre,
        area,
        categoria,
        marca,
        referencia,
        cantidad,
        stock_minimo,
        ubicacion,
        imagen,
        estado
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    mostrarError(
        'Error',
        'No se pudo preparar el registro: ' . $conn->error,
        $area
    );

}


/*
 * 6 strings:
 * codigo
 * nombre
 * area
 * categoria
 * marca
 * referencia
 *
 * 2 integers:
 * cantidad
 * stock_minimo
 *
 * 3 strings:
 * ubicacion
 * imagen
 * estado
 */

$stmt->bind_param(
    "ssssssiiiss",
    $codigo,
    $nombre,
    $area,
    $categoria,
    $marca,
    $referencia,
    $cantidad,
    $stockMinimo,
    $ubicacion,
    $imagen,
    $estado
);


if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    mostrarError(
        'No se pudo registrar',
        $error,
        $area
    );

}


$stmt->close();


/* =========================================================
   ÉXITO
========================================================= */

mostrarExito(
    'Producto registrado',
    'El producto fue agregado correctamente al inventario de ' .
    (
        $area === 'automatizacion'
            ? 'Automatización'
            : 'Audiovisuales'
    ) .
    '.',
    $area
);


/* =========================================================
   FUNCIONES SWEETALERT
========================================================= */

function mostrarExito(
    string $titulo,
    string $mensaje,
    string $area
): void {

    $tituloJS = json_encode(
        $titulo,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    $mensajeJS = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    $areaJS = json_encode(
        $area,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    ?>

    <!DOCTYPE html>

    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <title>Producto registrado</title>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>

    <body>

    <script>

    Swal.fire({

        icon: 'success',

        title: <?= $tituloJS; ?>,

        text: <?= $mensajeJS; ?>,

        confirmButtonText: 'Aceptar',

        confirmButtonColor: '#002855',

        allowOutsideClick: false,

        allowEscapeKey: false

    }).then(function () {

        window.location.href =
            '../../pages/inventario.php?area=' +
            encodeURIComponent(
                <?= $areaJS; ?>
            );

    });

    </script>

    </body>

    </html>

    <?php

    exit;
}


function mostrarError(
    string $titulo,
    string $mensaje,
    string $area
): void {

    $tituloJS = json_encode(
        $titulo,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    $mensajeJS = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    $areaJS = json_encode(
        $area ?: 'audiovisuales',
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    ?>

    <!DOCTYPE html>

    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <title>Error</title>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>

    <body>

    <script>

    Swal.fire({

        icon: 'error',

        title: <?= $tituloJS; ?>,

        text: <?= $mensajeJS; ?>,

        confirmButtonText: 'Aceptar',

        confirmButtonColor: '#002855',

        allowOutsideClick: false

    }).then(function () {

        window.location.href =
            '../../pages/inventario.php?area=' +
            encodeURIComponent(
                <?= $areaJS; ?>
            );

    });

    </script>

    </body>

    </html>

    <?php

    exit;
}