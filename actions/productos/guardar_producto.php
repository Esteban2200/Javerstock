<?php

/* =========================================================
   GUARDAR PRODUCTO - JAVERSTOCK
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

exigirPermiso('crear_producto');


/* =========================================================
   SOLO POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: ../../pages/inventario.php?error=acceso_invalido"
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
   DATOS DEL FORMULARIO
========================================================= */

$codigo = limpiar($_POST['codigo'] ?? '');

$nombre = limpiar($_POST['nombre'] ?? '');

$descripcion = limpiar($_POST['descripcion'] ?? '');

$area = strtolower(
    limpiar($_POST['area'] ?? 'audiovisuales')
);

$categoria = (int)($_POST['categoria'] ?? 0);

$cantidad = (int)($_POST['cantidad'] ?? 0);

$stockMinimo = (int)(
    $_POST['stock_minimo'] ?? 0
);

$ubicacion = limpiar(
    $_POST['ubicacion'] ?? ''
);

$estado = strtolower(
    limpiar($_POST['estado'] ?? 'activo')
);


/* =========================================================
   VALIDAR DATOS
========================================================= */

if (
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

if (!in_array($area, $areasPermitidas, true)) {

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
   VALIDAR CÓDIGO DUPLICADO
========================================================= */

$verificar = $conn->prepare("
    SELECT id
    FROM productos
    WHERE codigo = ?
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
    "s",
    $codigo
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
   IMAGEN
========================================================= */

$imagen = '';


/* =========================================================
   SI SE SUBIÓ UNA IMAGEN
========================================================= */

if (
    isset($_FILES['imagen']) &&
    $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE
) {

    /* -----------------------------------------------------
       VALIDAR ERROR
    ----------------------------------------------------- */

    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=error_imagen"
        );

        exit;
    }


    /* -----------------------------------------------------
       VALIDAR TAMAÑO
       Máximo 5 MB
    ----------------------------------------------------- */

    $tamanoMaximo = 5 * 1024 * 1024;

    if ($_FILES['imagen']['size'] > $tamanoMaximo) {

        header(
            "Location: ../../pages/inventario.php?area=" .
            urlencode($area) .
            "&error=imagen_muy_grande"
        );

        exit;
    }


    /* -----------------------------------------------------
       VALIDAR EXTENSIÓN
    ----------------------------------------------------- */

    $extensionesPermitidas = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];

    $nombreOriginal = $_FILES['imagen']['name'];

    $extension = strtolower(
        pathinfo(
            $nombreOriginal,
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
       VALIDAR TIPO REAL DEL ARCHIVO
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

    $carpeta = __DIR__ .
        "/../../assets/uploads/productos/";


    /* -----------------------------------------------------
       CREAR CARPETA SI NO EXISTE
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
            bin2hex(random_bytes(8));

    } catch (Exception $e) {

        $identificador =
            uniqid();
    }


    $nombreImagen =
        'producto_' .
        date('Ymd_His') .
        '_' .
        $identificador .
        '.' .
        $extension;


    $rutaDestino =
        $carpeta .
        $nombreImagen;


    /* -----------------------------------------------------
       MOVER ARCHIVO
    ----------------------------------------------------- */

    if (
        !move_uploaded_file(
            $_FILES['imagen']['tmp_name'],
            $rutaDestino
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
       GUARDAR NOMBRE
       EN LA VARIABLE PARA MYSQL
    ----------------------------------------------------- */

    $imagen = $nombreImagen;
}


/* =========================================================
   INSERTAR PRODUCTO
========================================================= */

$sql = "
    INSERT INTO productos
    (
        codigo,
        nombre,
        descripcion,
        area,
        categoria,
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
        ?
    )
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    /* Si falló el INSERT y había imagen,
       eliminarla para no dejar archivos huérfanos */

    if (
        $imagen !== '' &&
        isset($rutaDestino) &&
        is_file($rutaDestino)
    ) {

        @unlink($rutaDestino);
    }


    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&error=error_registrar"
    );

    exit;
}


$stmt->bind_param(
    "ssssiiisss",
    $codigo,
    $nombre,
    $descripcion,
    $area,
    $categoria,
    $cantidad,
    $stockMinimo,
    $ubicacion,
    $imagen,
    $estado
);


/* =========================================================
   EJECUTAR
========================================================= */

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../../pages/inventario.php?area=" .
        urlencode($area) .
        "&msg=producto_registrado"
    );

    exit;
}


/* =========================================================
   SI FALLÓ EL INSERT
========================================================= */

$stmt->close();


/* Eliminar imagen si ya había sido subida */

if (
    $imagen !== '' &&
    isset($rutaDestino) &&
    is_file($rutaDestino)
) {

    @unlink($rutaDestino);
}


header(
    "Location: ../../pages/inventario.php?area=" .
    urlencode($area) .
    "&error=error_registrar"
);

exit;

?>