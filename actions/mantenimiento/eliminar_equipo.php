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
| EQUIPO
|--------------------------------------------------------------------------
*/

$equipo_id = (int)($_POST['equipo_id'] ?? 0);


if ($equipo_id <= 0) {

    header(
        "Location: ../../pages/mantenimiento.php?msg=error"
    );

    exit;
}


$conn->begin_transaction();


try {

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR EQUIPO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id, area, nombre
         FROM equipos
         WHERE id = ?
         LIMIT 1"
    );


    if (!$stmt) {

        throw new Exception(
            "Error preparando consulta del equipo: " .
            $conn->error
        );
    }


    $stmt->bind_param(
        "i",
        $equipo_id
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Error consultando equipo: " .
            $stmt->error
        );
    }


    $resultado = $stmt->get_result();


    if ($resultado->num_rows === 0) {

        $stmt->close();

        throw new Exception(
            "El equipo no existe."
        );
    }


    $equipo = $resultado->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | VERIFICAR MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    $stmtMovimientos = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM movimientos
         WHERE producto_id = ?"
    );


    /*
    |--------------------------------------------------------------------------
    | SI movimientos USA producto_id
    |--------------------------------------------------------------------------
    |
    | Si la tabla movimientos de tu BD no tiene producto_id
    | y usa otro campo, esta parte debe adaptarse.
    |
    */

    if ($stmtMovimientos) {

        $stmtMovimientos->bind_param(
            "i",
            $equipo_id
        );

        if ($stmtMovimientos->execute()) {

            $resultadoMovimientos =
                $stmtMovimientos->get_result();

            $fila =
                $resultadoMovimientos->fetch_assoc();

            $totalMovimientos =
                (int)($fila['total'] ?? 0);

            $stmtMovimientos->close();


            if ($totalMovimientos > 0) {

                throw new Exception(
                    "No se puede eliminar este equipo porque tiene movimientos registrados."
                );
            }

        } else {

            $stmtMovimientos->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR MANTENIMIENTOS
    |--------------------------------------------------------------------------
    */

    $stmtMantenimiento = $conn->prepare(
        "DELETE FROM mantenimientos
         WHERE equipo_id = ?"
    );


    if (!$stmtMantenimiento) {

        throw new Exception(
            "Error preparando eliminación de mantenimientos: " .
            $conn->error
        );
    }


    $stmtMantenimiento->bind_param(
        "i",
        $equipo_id
    );


    if (!$stmtMantenimiento->execute()) {

        throw new Exception(
            "Error eliminando mantenimientos: " .
            $stmtMantenimiento->error
        );
    }


    $stmtMantenimiento->close();


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR EQUIPO
    |--------------------------------------------------------------------------
    */

    $stmtEquipo = $conn->prepare(
        "DELETE FROM equipos
         WHERE id = ?"
    );


    if (!$stmtEquipo) {

        throw new Exception(
            "Error preparando eliminación del equipo: " .
            $conn->error
        );
    }


    $stmtEquipo->bind_param(
        "i",
        $equipo_id
    );


    if (!$stmtEquipo->execute()) {

        throw new Exception(
            "Error eliminando equipo: " .
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
        "?area=" .
        urlencode($equipo['area']) .
        "&msg=eliminado"
    );

    exit;


} catch (Throwable $e) {

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
                    No fue posible eliminar el equipo
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

                <a href='../../pages/mantenimiento.php'>
                    Volver a mantenimiento
                </a>

            </div>

        </body>

        </html>"
    );
}

?>