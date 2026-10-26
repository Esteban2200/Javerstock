<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

/*
|--------------------------------------------------------------------------
| PERMISO
|--------------------------------------------------------------------------
*/

exigirPermiso('gestionar_mantenimiento');


/*
|--------------------------------------------------------------------------
| SOLO SE PERMITE POST
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
| DATOS RECIBIDOS
|--------------------------------------------------------------------------
*/

$id = (int) ($_POST['id'] ?? 0);

$equipo_id = (int) ($_POST['equipo_id'] ?? 0);

$area = strtolower(
    trim($_POST['area'] ?? '')
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
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if (
    $id <= 0 ||
    $equipo_id <= 0 ||
    $fecha_inicio === '' ||
    $diagnostico === '' ||
    !in_array($area, $areasPermitidas, true)
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
    | VERIFICAR QUE EL MANTENIMIENTO EXISTA
    |--------------------------------------------------------------------------
    */

    $stmtVerificar = $conn->prepare(
        "SELECT id
         FROM mantenimientos
         WHERE id = ?
         LIMIT 1"
    );

    if (!$stmtVerificar) {

        throw new Exception(
            "Error preparando verificación del mantenimiento: " .
            $conn->error
        );
    }

    $stmtVerificar->bind_param(
        "i",
        $id
    );

    if (!$stmtVerificar->execute()) {

        throw new Exception(
            "Error verificando el mantenimiento: " .
            $stmtVerificar->error
        );
    }

    $resultado = $stmtVerificar->get_result();

    if ($resultado->num_rows === 0) {

        $stmtVerificar->close();

        throw new Exception(
            "El mantenimiento que intenta editar no existe."
        );
    }

    $stmtVerificar->close();


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR QUE EL EQUIPO EXISTA
    |--------------------------------------------------------------------------
    */

    $stmtEquipoExiste = $conn->prepare(
        "SELECT id
         FROM equipos
         WHERE id = ?
         LIMIT 1"
    );

    if (!$stmtEquipoExiste) {

        throw new Exception(
            "Error preparando verificación del equipo: " .
            $conn->error
        );
    }

    $stmtEquipoExiste->bind_param(
        "i",
        $equipo_id
    );

    if (!$stmtEquipoExiste->execute()) {

        throw new Exception(
            "Error verificando el equipo: " .
            $stmtEquipoExiste->error
        );
    }

    $resultadoEquipo = $stmtEquipoExiste->get_result();

    if ($resultadoEquipo->num_rows === 0) {

        $stmtEquipoExiste->close();

        throw new Exception(
            "El equipo seleccionado no existe."
        );
    }

    $stmtEquipoExiste->close();


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE mantenimientos
         SET
            equipo_id = ?,
            area = ?,
            diagnostico = ?,
            tecnico = ?,
            proveedor = ?,
            ubicacion = ?,
            fecha_inicio = ?,
            observaciones = ?
         WHERE id = ?"
    );

    if (!$stmt) {

        throw new Exception(
            "Error preparando actualización del mantenimiento: " .
            $conn->error
        );
    }

    $stmt->bind_param(
        "isssssssi",
        $equipo_id,
        $area,
        $diagnostico,
        $tecnico,
        $proveedor,
        $ubicacion,
        $fecha_inicio,
        $observaciones,
        $id
    );

    if (!$stmt->execute()) {

        throw new Exception(
            "Error actualizando el mantenimiento: " .
            $stmt->error
        );
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | EL EQUIPO SIGUE EN MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    $stmtEquipo = $conn->prepare(
        "UPDATE equipos
         SET estado = 'mantenimiento'
         WHERE id = ?"
    );

    if (!$stmtEquipo) {

        throw new Exception(
            "Error preparando actualización del estado del equipo: " .
            $conn->error
        );
    }

    $stmtEquipo->bind_param(
        "i",
        $equipo_id
    );

    if (!$stmtEquipo->execute()) {

        throw new Exception(
            "Error actualizando el estado del equipo: " .
            $stmtEquipo->error
        );
    }

    $stmtEquipo->close();


    /*
    |--------------------------------------------------------------------------
    | GUARDAR CAMBIOS
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | REGRESAR A MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    header(
        "Location: ../../pages/mantenimiento.php" .
        "?area=" . urlencode($area) .
        "&msg=actualizado"
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
        "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f4f6f9;
                    padding: 40px;
                }

                .error {
                    max-width: 700px;
                    margin: auto;
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 5px 20px rgba(0,0,0,.10);
                }

                h2 {
                    color: #b91c1c;
                }

                p {
                    color: #334155;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 18px;
                    background: #002855;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                }
            </style>
        </head>

        <body>

            <div class='error'>

                <h2>
                    Error al editar mantenimiento
                </h2>

                <p>"
                . htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . "</p>

                <a href='../../pages/mantenimiento.php'>
                    Volver a mantenimiento
                </a>

            </div>

        </body>
        </html>"
    );
}

?>