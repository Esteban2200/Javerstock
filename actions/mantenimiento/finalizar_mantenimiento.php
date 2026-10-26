<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

/*
|--------------------------------------------------------------------------
| PERMISO
|--------------------------------------------------------------------------
*/
exigirPermiso('gestionar_mantenimiento');


/*
|--------------------------------------------------------------------------
| SOLO POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/mantenimiento.php?msg=error_metodo");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATOS RECIBIDOS
|--------------------------------------------------------------------------
| Aceptamos mantenimiento_id o id para evitar problemas si el formulario
| utiliza alguno de los dos nombres.
|--------------------------------------------------------------------------
*/

$mantenimientoId = (int) (
    $_POST['mantenimiento_id']
    ?? $_POST['id']
    ?? 0
);

$fechaFin = trim(
    $_POST['fecha_fin']
    ?? ''
);

$trabajoRealizado = trim(
    $_POST['trabajo_realizado']
    ?? ''
);

$observacionesFinalizacion = trim(
    $_POST['observaciones_finalizacion']
    ?? ''
);

$estadoEquipo = trim(
    $_POST['estado_equipo']
    ?? 'operativo'
);


/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if ($mantenimientoId <= 0) {

    header(
        "Location: ../../pages/mantenimiento.php?msg=error&id_invalido=1"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR FECHA
|--------------------------------------------------------------------------
*/

if ($fechaFin === '') {

    header(
        "Location: ../../pages/mantenimiento.php?msg=fecha_fin_requerida"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR FORMATO DE FECHA
|--------------------------------------------------------------------------
*/

$fechaValida = DateTime::createFromFormat(
    'Y-m-d',
    $fechaFin
);

if (
    !$fechaValida ||
    $fechaValida->format('Y-m-d') !== $fechaFin
) {

    header(
        "Location: ../../pages/mantenimiento.php?msg=fecha_invalida"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| TRABAJO REALIZADO
|--------------------------------------------------------------------------
| Si el formulario no lo envía, colocamos un texto por defecto.
| Así finalizar el mantenimiento no falla simplemente porque ese campo
| no exista en el formulario.
|--------------------------------------------------------------------------
*/

if ($trabajoRealizado === '') {
    $trabajoRealizado = 'Mantenimiento finalizado.';
}


/*
|--------------------------------------------------------------------------
| ESTADOS PERMITIDOS
|--------------------------------------------------------------------------
*/

$estadosPermitidos = [
    'operativo',
    'dañado',
    'mantenimiento'
];

if (!in_array($estadoEquipo, $estadosPermitidos, true)) {

    $estadoEquipo = 'operativo';
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
    | BUSCAR MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    $sqlBuscar = "
        SELECT
            id,
            equipo_id,
            fecha_inicio,
            fecha_fin
        FROM mantenimientos
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlBuscar);

    if (!$stmt) {
        throw new Exception(
            "No se pudo preparar la consulta del mantenimiento: " .
            $conn->error
        );
    }

    $stmt->bind_param(
        "i",
        $mantenimientoId
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No se pudo consultar el mantenimiento: " .
            $stmt->error
        );
    }

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {

        $stmt->close();

        throw new Exception(
            "El mantenimiento seleccionado no existe."
        );
    }

    $mantenimiento = $resultado->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | COMPROBAR SI YA ESTÁ FINALIZADO
    |--------------------------------------------------------------------------
    */

    if (
        isset($mantenimiento['fecha_fin']) &&
        $mantenimiento['fecha_fin'] !== null &&
        $mantenimiento['fecha_fin'] !== ''
    ) {

        throw new Exception(
            "Este mantenimiento ya fue finalizado anteriormente."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR EQUIPO
    |--------------------------------------------------------------------------
    */

    $equipoId = (int) (
        $mantenimiento['equipo_id']
        ?? 0
    );

    if ($equipoId <= 0) {

        throw new Exception(
            "El mantenimiento no tiene un equipo asociado."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR FECHA CONTRA FECHA DE INICIO
    |--------------------------------------------------------------------------
    */

    if (
        !empty($mantenimiento['fecha_inicio']) &&
        $fechaFin < $mantenimiento['fecha_inicio']
    ) {

        throw new Exception(
            "La fecha de finalización no puede ser anterior a la fecha de ingreso."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    $sqlActualizar = "
        UPDATE mantenimientos
        SET
            fecha_fin = ?,
            trabajo_realizado = ?,
            observaciones = CASE
                WHEN ? = '' THEN observaciones

                WHEN observaciones IS NULL
                     OR observaciones = ''
                THEN ?

                ELSE CONCAT(
                    observaciones,
                    '\n',
                    ?
                )
            END
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sqlActualizar);

    if (!$stmt) {

        throw new Exception(
            "No se pudo preparar la actualización del mantenimiento: " .
            $conn->error
        );
    }

    $stmt->bind_param(
        "sssssi",
        $fechaFin,
        $trabajoRealizado,
        $observacionesFinalizacion,
        $observacionesFinalizacion,
        $observacionesFinalizacion,
        $mantenimientoId
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No se pudo actualizar el mantenimiento: " .
            $stmt->error
        );
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADO DEL EQUIPO
    |--------------------------------------------------------------------------
    */

    $sqlEquipo = "
        UPDATE equipos
        SET estado = ?
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sqlEquipo);

    if (!$stmt) {

        throw new Exception(
            "No se pudo preparar la actualización del equipo: " .
            $conn->error
        );
    }

    $stmt->bind_param(
        "si",
        $estadoEquipo,
        $equipoId
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "No se pudo actualizar el estado del equipo: " .
            $stmt->error
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPROBAR QUE EL EQUIPO EXISTE
    |--------------------------------------------------------------------------
    */

    if ($stmt->affected_rows === 0) {

        /*
         * affected_rows puede ser 0 si el equipo ya tenía exactamente
         * el mismo estado. Por eso no lanzamos error automáticamente.
         */
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TODO
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | REDIRECCIÓN EXITOSA
    |--------------------------------------------------------------------------
    */

    header(
        "Location: ../../pages/mantenimiento.php?msg=finalizado"
    );

    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DESHACER CAMBIOS
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    /*
    |--------------------------------------------------------------------------
    | DEVOLVER ERROR A MANTENIMIENTO
    |--------------------------------------------------------------------------
    |
    | No usamos die(), para que el usuario vuelva a la pantalla normal
    | de mantenimiento.
    |--------------------------------------------------------------------------
    */

    $mensajeError = urlencode(
        $e->getMessage()
    );

    header(
        "Location: ../../pages/mantenimiento.php?msg=error&detalle=" .
        $mensajeError
    );

    exit;
}

?>
