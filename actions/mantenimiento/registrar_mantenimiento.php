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
| DATOS DEL FORMULARIO
|--------------------------------------------------------------------------
*/

$equipo_id = (int)($_POST['equipo_id'] ?? 0);

$area = strtolower(
    trim($_POST['area'] ?? '')
);

$tipo_mantenimiento = strtolower(
    trim($_POST['tipo_mantenimiento'] ?? 'mantenimiento')
);

$fecha_inicio = trim(
    $_POST['fecha_inicio'] ?? ''
);

$tecnico = trim(
    $_POST['tecnico'] ?? ''
);

$proveedor = trim(
    $_POST['proveedor'] ?? ''
);

$ubicacion = trim(
    $_POST['ubicacion'] ?? ''
);

$diagnostico = trim(
    $_POST['diagnostico'] ?? ''
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


/*
|--------------------------------------------------------------------------
| ÁREAS PERMITIDAS
|--------------------------------------------------------------------------
*/

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];


/*
|--------------------------------------------------------------------------
| TIPOS DE MANTENIMIENTO
|--------------------------------------------------------------------------
*/

$tiposPermitidos = [
    'mantenimiento',
    'danado'
];


/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if (
    $equipo_id <= 0 ||
    $fecha_inicio === '' ||
    $diagnostico === '' ||
    !in_array($area, $areasPermitidas, true) ||
    !in_array($tipo_mantenimiento, $tiposPermitidos, true)
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
| INICIAR TRANSACCIÓN
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();


try {


    /*
    |--------------------------------------------------------------------------
    | REGISTRAR MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO mantenimientos
        (
            equipo_id,
            area,
            diagnostico,
            tecnico,
            proveedor,
            ubicacion,
            fecha_inicio,
            observaciones
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    if (!$stmt) {

        throw new Exception(
            "Error preparando mantenimiento: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "isssssss",
        $equipo_id,
        $area,
        $diagnostico,
        $tecnico,
        $proveedor,
        $ubicacion,
        $fecha_inicio,
        $observaciones
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Error registrando mantenimiento: " .
            $stmt->error
        );

    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADO DEL EQUIPO
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | La tabla equipos utiliza:
    |
    | operativo
    | danado
    | mantenimiento
    | reparado
    | entregado
    |
    | No se debe utilizar "dañado" con ñ.
    |--------------------------------------------------------------------------
    */

    if ($tipo_mantenimiento === 'danado') {

        $nuevoEstado = 'danado';

    } else {

        $nuevoEstado = 'mantenimiento';

    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR EQUIPO
    |--------------------------------------------------------------------------
    */

    $stmtEquipo = $conn->prepare(
        "UPDATE equipos
         SET estado = ?
         WHERE id = ?"
    );


    if (!$stmtEquipo) {

        throw new Exception(
            "Error preparando actualización del equipo: " .
            $conn->error
        );

    }


    $stmtEquipo->bind_param(
        "si",
        $nuevoEstado,
        $equipo_id
    );


    if (!$stmtEquipo->execute()) {

        throw new Exception(
            "Error actualizando estado del equipo: " .
            $stmtEquipo->error
        );

    }


    $stmtEquipo->close();


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | REGRESAR
    |--------------------------------------------------------------------------
    */

    header(
        "Location: ../../pages/mantenimiento.php" .
        "?area=" . urlencode($area) .
        "&msg=registrado"
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | DESHACER CAMBIOS
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    die(
        "<h2>Error al registrar mantenimiento</h2>" .
        "<p>" .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        "</p>"
    );
}

?>