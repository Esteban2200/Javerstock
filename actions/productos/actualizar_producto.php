<?php

/* =========================================================
   ACTUALIZAR PRODUCTO - JAVERSTOCK
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

exigirPermiso('editar_producto');


/* =========================================================
   SOLO POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: ../../pages/inventario.php"
    );

    exit;
}


/* =========================================================
   FUNCIÓN LIMPIAR
========================================================= */

function limpiar($valor): string
{
    return trim((string)($valor ?? ''));
}


/* =========================================================
   DATOS
========================================================= */

$id = (int)($_POST['id'] ?? 0);

$codigo = limpiar(
    $_POST['codigo'] ?? ''
);

$nombre = limpiar(
    $_POST['nombre'] ?? ''
);

$descripcion = limpiar(
    $_POST['descripcion'] ?? ''
);

$area = strtolower(
    limpiar(
        $_POST['area'] ?? 'audiovisuales'
    )
);

$categoria = (int)(
    $_POST['categoria'] ?? 0
);

$cantidad = (int)(
    $_POST['cantidad'] ?? 0
);

$stockMinimo = (int)(
    $_POST['stock_minimo'] ?? 0
);

$ubicacion = limpiar(
    $_POST['ubicacion'] ?? ''
);

$estado = strtolower(
    limpiar(
        $_POST['estado'] ?? 'activo'
    )
);


/* =========================================================
   VALIDACIONES
========================================================= */

if (
    $id <= 0 ||
    $codigo === '' ||
    $nombre === '' ||
    $area === ''
) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area ?: 'audiovisuales') .
        "&error=datos_invalidos"
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

if (
    !in_array(
        $area,
        $areasPermitidas,
        true
    )
) {

    header(
        "Location: ../../pages/inventario.php?error=area_invalida"
    );

    exit;
}


/* =========================================================
   VALIDAR NÚMEROS
========================================================= */

if ($cantidad < 0) {
    $cantidad = 0;
}

if ($stockMinimo < 0) {
    $stockMinimo = 0;
}


/* =========================================================
   VERIFICAR QUE EL PRODUCTO EXISTA
========================================================= */

$buscarProducto = $conn->prepare("
    SELECT id, imagen
    FROM productos
    WHERE id = ?
    LIMIT 1
");

if (!$buscarProducto) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&error=error_consulta"
    );

    exit;
}

$buscarProducto->bind_param(
    "i",
    $id
);

$buscarProducto->execute();

$resultadoProducto =
    $buscarProducto->get_result();

$productoActual =
    $resultadoProducto->fetch_assoc();

$buscarProducto->close();


if (!$productoActual) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&error=producto_no_encontrado"
    );

    exit;
}


/* =========================================================
   IMAGEN ACTUAL
========================================================= */

$imagenActual =
    trim(
        (string)(
            $productoActual['imagen'] ?? ''
        )
    );

$imagen =
    $imagenActual;


/* =========================================================
   VERIFICAR CÓDIGO DUPLICADO
========================================================= */

$verificar = $conn->prepare("
    SELECT id
    FROM productos
    WHERE codigo = ?
      AND id <> ?
    LIMIT 1
");

if (!$verificar) {

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&error=error_consulta"
    );

    exit;
}

$verificar->bind_param(
    "si",
    $codigo,
    $id
);

$verificar->execute();

$verificar->store_result();

if ($verificar->num_rows > 0) {

    $verificar->close();

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&error=codigo_ya_existe"
    );

    exit;
}

$verificar->close();


/* =========================================================
   SUBIR NUEVA IMAGEN
========================================================= */

$nuevaImagenSubida = false;

$rutaNuevaImagen = '';

if (
    isset($_FILES['imagen']) &&
    $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE
) {

    /* -----------------------------------------------------
       ERROR DE SUBIDA
    ----------------------------------------------------- */

    if (
        $_FILES['imagen']['error'] !==
        UPLOAD_ERR_OK
    ) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=error_imagen"
        );

        exit;
    }


    /* -----------------------------------------------------
       TAMAÑO MÁXIMO: 5 MB
    ----------------------------------------------------- */

    $tamanoMaximo =
        5 * 1024 * 1024;

    if (
        $_FILES['imagen']['size'] >
        $tamanoMaximo
    ) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=imagen_muy_grande"
        );

        exit;
    }


    /* -----------------------------------------------------
       EXTENSIONES
    ----------------------------------------------------- */

    $extensionesPermitidas = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];

    $extension = strtolower(
        pathinfo(
            $_FILES['imagen']['name'],
            PATHINFO_EXTENSION
        )
    );

    if (
        !in_array(
            $extension,
            $extensionesPermitidas,
            true
        )
    ) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=formato_imagen"
        );

        exit;
    }


    /* -----------------------------------------------------
       VALIDAR MIME REAL
    ----------------------------------------------------- */

    $tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    $tipoMime = '';

    if (
        function_exists('mime_content_type')
    ) {

        $tipoMime = mime_content_type(
            $_FILES['imagen']['tmp_name']
        );
    }

    if (
        $tipoMime !== '' &&
        !in_array(
            $tipoMime,
            $tiposPermitidos,
            true
        )
    ) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=imagen_invalida"
        );

        exit;
    }


    /* -----------------------------------------------------
       CARPETA
    ----------------------------------------------------- */

    $carpeta =
        __DIR__ .
        "/../../assets/uploads/productos/";


    /* -----------------------------------------------------
       CREAR CARPETA
    ----------------------------------------------------- */

    if (!is_dir($carpeta)) {

        if (!mkdir($carpeta, 0777, true)) {

            header(
                "Location: ../../pages/inventario.php?area=" .
                urlencode($area) .
                "&error=carpeta_imagen"
            );

            exit;
        }
    }


    /* -----------------------------------------------------
       GENERAR NOMBRE ÚNICO
    ----------------------------------------------------- */

    try {

        $identificador =
            bin2hex(
                random_bytes(8)
            );

    } catch (Exception $e) {

        $identificador =
            uniqid();
    }


    $nombreNuevaImagen =
        'producto_' .
        date('Ymd_His') .
        '_' .
        $identificador .
        '.' .
        $extension;


    $rutaNuevaImagen =
        $carpeta .
        $nombreNuevaImagen;


    /* -----------------------------------------------------
       MOVER ARCHIVO
    ----------------------------------------------------- */

    if (
        !move_uploaded_file(
            $_FILES['imagen']['tmp_name'],
            $rutaNuevaImagen
        )
    ) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=error_subir_imagen"
        );

        exit;
    }


    /* -----------------------------------------------------
       USAR NUEVA IMAGEN
    ----------------------------------------------------- */

    $imagen =
        $nombreNuevaImagen;

    $nuevaImagenSubida = true;
}


/* =========================================================
   ACTUALIZAR PRODUCTO
========================================================= */

$sql = "
    UPDATE productos
    SET
        codigo = ?,
        nombre = ?,
        descripcion = ?,
        area = ?,
        categoria = ?,
        cantidad = ?,
        stock_minimo = ?,
        ubicacion = ?,
        imagen = ?,
        estado = ?
    WHERE id = ?
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    /* Si se subió una nueva imagen
       y falló el UPDATE, eliminarla */

    if (
        $nuevaImagenSubida &&
        $rutaNuevaImagen !== '' &&
        is_file($rutaNuevaImagen)
    ) {

        @unlink($rutaNuevaImagen);
    }


    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&error=error_actualizar"
    );

    exit;
}


$stmt->bind_param(
    "ssssiiisssi",
    $codigo,
    $nombre,
    $descripcion,
    $area,
    $categoria,
    $cantidad,
    $stockMinimo,
    $ubicacion,
    $imagen,
    $estado,
    $id
);


/* =========================================================
   EJECUTAR
========================================================= */

if ($stmt->execute()) {

    $stmt->close();


    /* -----------------------------------------------------
       ELIMINAR IMAGEN ANTERIOR
       SOLO DESPUÉS DE ACTUALIZAR CORRECTAMENTE
    ----------------------------------------------------- */

    if (
        $nuevaImagenSubida &&
        $imagenActual !== '' &&
        $imagenActual !== 'no-image.png'
    ) {

        $rutaAnterior =
            __DIR__ .
            "/../../assets/uploads/productos/" .
            basename($imagenActual);

        if (
            is_file($rutaAnterior) &&
            $rutaAnterior !== $rutaNuevaImagen
        ) {

            @unlink($rutaAnterior);
        }
    }


    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&msg=producto_actualizado"
    );

    exit;
}


/* =========================================================
   ERROR
========================================================= */

$stmt->close();


/* Eliminar nueva imagen si el UPDATE falló */

if (
    $nuevaImagenSubida &&
    $rutaNuevaImagen !== '' &&
    is_file($rutaNuevaImagen)
) {

    @unlink($rutaNuevaImagen);
}


header(
    "Location: ../../pages/inventario.php?area=" .
    urlencode($area) .
    "&error=error_actualizar"
);

exit;

?>