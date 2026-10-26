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
| DATOS
|--------------------------------------------------------------------------
*/

$equipo_id = (int)($_POST['equipo_id'] ?? 0);

$fecha_fin = trim(
    $_POST['fecha_fin'] ?? ''
);

$tecnico = trim(
    $_POST['tecnico'] ?? ''
);

$trabajo_realizado = trim(
    $_POST['trabajo_realizado'] ?? ''
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALIDACIÓN
|--------------------------------------------------------------------------
*/

if (
    $equipo_id <= 0 ||
    $fecha_fin === '' ||
    $trabajo_realizado === ''
) {

    header(
        "Location: ../../pages/mantenimiento.php?msg=error"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| TRANSACCIÓN
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();


try {


    /*
    |--------------------------------------------------------------------------
    | 1. BUSCAR EL EQUIPO
    |--------------------------------------------------------------------------
    */

    $stmtEquipo = $conn->prepare(
        "SELECT
            id,
            codigo,
            nombre,
            area,
            estado
         FROM equipos
         WHERE id = ?
         LIMIT 1"
    );


    if (!$stmtEquipo) {

        throw new Exception(
            "Error preparando consulta del equipo: " .
            $conn->error
        );
    }


    $stmtEquipo->bind_param(
        "i",
        $equipo_id
    );


    if (!$stmtEquipo->execute()) {

        throw new Exception(
            "Error consultando equipo: " .
            $stmtEquipo->error
        );
    }


    $resultadoEquipo =
        $stmtEquipo->get_result();


    if ($resultadoEquipo->num_rows === 0) {

        $stmtEquipo->close();

        throw new Exception(
            "El equipo no existe."
        );
    }


    $equipo =
        $resultadoEquipo->fetch_assoc();


    $stmtEquipo->close();


    /*
    |--------------------------------------------------------------------------
    | 2. BUSCAR MANTENIMIENTO ABIERTO
    |--------------------------------------------------------------------------
    */

    $stmtMantenimiento = $conn->prepare(
        "SELECT
            id,
            equipo_id,
            area,
            diagnostico,
            tecnico,
            proveedor,
            ubicacion,
            fecha_inicio,
            fecha_fin,
            observaciones
         FROM mantenimientos
         WHERE equipo_id = ?
           AND fecha_fin IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );


    if (!$stmtMantenimiento) {

        throw new Exception(
            "Error preparando búsqueda del mantenimiento: " .
            $conn->error
        );
    }


    $stmtMantenimiento->bind_param(
        "i",
        $equipo_id
    );


    if (!$stmtMantenimiento->execute()) {

        throw new Exception(
            "Error buscando mantenimiento: " .
            $stmtMantenimiento->error
        );
    }


    $resultadoMantenimiento =
        $stmtMantenimiento->get_result();


    /*
    |--------------------------------------------------------------------------
    | 3. SI EXISTE MANTENIMIENTO ABIERTO
    |--------------------------------------------------------------------------
    */

    if ($resultadoMantenimiento->num_rows > 0) {

        $mantenimiento =
            $resultadoMantenimiento->fetch_assoc();

        $mantenimiento_id =
            (int)$mantenimiento['id'];


        /*
        |--------------------------------------------------------------------------
        | VALIDAR FECHA
        |--------------------------------------------------------------------------
        */

        if (
            !empty($mantenimiento['fecha_inicio']) &&
            $fecha_fin < $mantenimiento['fecha_inicio']
        ) {

            $stmtMantenimiento->close();

            throw new Exception(
                "La fecha de reparación no puede ser anterior a la fecha de ingreso."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | USAR TÉCNICO EXISTENTE SI NO SE ESCRIBIÓ UNO
        |--------------------------------------------------------------------------
        */

        if ($tecnico === '') {

            $tecnico =
                $mantenimiento['tecnico'] ?? '';
        }


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR MANTENIMIENTO
        |--------------------------------------------------------------------------
        */

        $stmtActualizar = $conn->prepare(
            "UPDATE mantenimientos
             SET
                fecha_fin = ?,
                tecnico = ?,
                trabajo_realizado = ?,
                observaciones = ?
             WHERE id = ?"
        );


        if (!$stmtActualizar) {

            $stmtMantenimiento->close();

            throw new Exception(
                "Error preparando finalización: " .
                $conn->error
            );
        }


        $stmtActualizar->bind_param(
            "ssssi",
            $fecha_fin,
            $tecnico,
            $trabajo_realizado,
            $observaciones,
            $mantenimiento_id
        );


        if (!$stmtActualizar->execute()) {

            $error =
                $stmtActualizar->error;

            $stmtActualizar->close();
            $stmtMantenimiento->close();

            throw new Exception(
                "Error finalizando mantenimiento: " .
                $error
            );
        }


        $stmtActualizar->close();


        /*
        |--------------------------------------------------------------------------
        | ÁREA DEL MANTENIMIENTO
        |--------------------------------------------------------------------------
        */

        $area =
            $mantenimiento['area'] ??
            $equipo['area'];

    }

    /*
    |--------------------------------------------------------------------------
    | 4. NO EXISTE MANTENIMIENTO ABIERTO
    |--------------------------------------------------------------------------
    |
    | Esto ocurre cuando el equipo está "dañado", pero todavía
    | no tiene un registro de mantenimiento abierto.
    |
    | En ese caso creamos un historial de reparación
    | directamente finalizado.
    |
    |--------------------------------------------------------------------------
    */

    else {

        $area =
            strtolower(
                trim(
                    $equipo['area'] ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | DIAGNÓSTICO
        |--------------------------------------------------------------------------
        */

        $diagnostico =
            "Reparación de equipo";


        /*
        |--------------------------------------------------------------------------
        | FECHA DE INICIO
        |--------------------------------------------------------------------------
        */

        $fecha_inicio =
            $fecha_fin;


        /*
        |--------------------------------------------------------------------------
        | TÉCNICO
        |--------------------------------------------------------------------------
        */

        if ($tecnico === '') {

            $tecnico = 'No especificado';
        }


        /*
        |--------------------------------------------------------------------------
        | PROVEEDOR
        |--------------------------------------------------------------------------
        */

        $proveedor = 'Interno';


        /*
        |--------------------------------------------------------------------------
        | UBICACIÓN
        |--------------------------------------------------------------------------
        */

        $ubicacion = '';


        /*
        |--------------------------------------------------------------------------
        | CREAR HISTORIAL FINALIZADO
        |--------------------------------------------------------------------------
        */

        $stmtNuevo = $conn->prepare(
            "INSERT INTO mantenimientos
            (
                equipo_id,
                area,
                diagnostico,
                tecnico,
                proveedor,
                ubicacion,
                fecha_inicio,
                fecha_fin,
                trabajo_realizado,
                observaciones
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );


        if (!$stmtNuevo) {

            $stmtMantenimiento->close();

            throw new Exception(
                "Error preparando nuevo historial: " .
                $conn->error
            );
        }


        $stmtNuevo->bind_param(
            "isssssssss",
            $equipo_id,
            $area,
            $diagnostico,
            $tecnico,
            $proveedor,
            $ubicacion,
            $fecha_inicio,
            $fecha_fin,
            $trabajo_realizado,
            $observaciones
        );


        if (!$stmtNuevo->execute()) {

            $error =
                $stmtNuevo->error;

            $stmtNuevo->close();
            $stmtMantenimiento->close();

            throw new Exception(
                "Error creando historial de reparación: " .
                $error
            );
        }


        $stmtNuevo->close();
    }


    $stmtMantenimiento->close();


    /*
    |--------------------------------------------------------------------------
    | 5. MARCAR EQUIPO COMO OPERATIVO
    |--------------------------------------------------------------------------
    */

    $nuevoEstado = 'operativo';


    $stmtEstado = $conn->prepare(
        "UPDATE equipos
         SET estado = ?
         WHERE id = ?"
    );


    if (!$stmtEstado) {

        throw new Exception(
            "Error preparando cambio de estado: " .
            $conn->error
        );
    }


    $stmtEstado->bind_param(
        "si",
        $nuevoEstado,
        $equipo_id
    );


    if (!$stmtEstado->execute()) {

        throw new Exception(
            "Error actualizando estado del equipo: " .
            $stmtEstado->error
        );
    }


    $stmtEstado->close();


    /*
    |--------------------------------------------------------------------------
    | 6. CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | 7. REGRESAR A MANTENIMIENTO
    |--------------------------------------------------------------------------
    */

    header(
        "Location: ../../pages/mantenimiento.php" .
        "?area=" .
        urlencode($area) .
        "&msg=finalizado"
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | CANCELAR
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    die(
        "<!DOCTYPE html>
        <html lang='es'>

        <head>

            <meta charset='UTF-8'>

            <title>
                Error al reparar equipo
            </title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    background: #f4f6f9;
                    padding: 40px;
                }

                .error {
                    max-width: 700px;
                    margin: auto;
                    background: #fff;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow:
                        0 5px 20px
                        rgba(0,0,0,.10);
                }

                h2 {
                    color: #b91c1c;
                }

                p {
                    color: #334155;
                    line-height: 1.6;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 11px 18px;
                    background: #002855;
                    color: #fff;
                    text-decoration: none;
                    border-radius: 8px;
                }

            </style>

        </head>

        <body>

            <div class='error'>

                <h2>
                    Error al reparar equipo
                </h2>

                <p>
                    " .
                    htmlspecialchars(
                        $e->getMessage(),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . "
                </p>

                <a
                    href='../../pages/mantenimiento.php'>
                    Volver a mantenimiento
                </a>

            </div>

        </body>

        </html>"
    );
}

?>