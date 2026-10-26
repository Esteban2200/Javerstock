<?php

/* =========================================================
   SISTEMA DE PERMISOS JAVERSTOCK
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

require_once __DIR__ . "/conexion.php";


/* =========================================================
   COMPROBAR PERMISO
   ========================================================= */

function tienePermiso($permiso)
{

    global $conn;


    /* =====================================================
       VALIDAR SESIÓN
       ===================================================== */

    if (
        !isset($_SESSION['id']) ||
        empty($_SESSION['id'])
    ) {

        return false;

    }


    /* =====================================================
       ADMINISTRADOR
       ===================================================== */

    $rol = strtolower(
        trim(
            (string)(
                $_SESSION['rol'] ?? ''
            )
        )
    );


    if (
        $rol === 'admin' ||
        $rol === 'administrador'
    ) {

        return true;

    }


    /* =====================================================
       USUARIO NORMAL
       ===================================================== */

    $usuario_id =
        (int)$_SESSION['id'];


    if ($usuario_id <= 0) {

        return false;

    }


    /* =====================================================
       CONSULTAR PERMISO
       ===================================================== */

    $sql = "
        SELECT 1
        FROM usuario_permisos AS up

        INNER JOIN permisos AS p
            ON p.id = up.permiso_id

        WHERE up.usuario_id = ?
        AND p.nombre = ?

        LIMIT 1
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        return false;

    }


    $stmt->bind_param(
        "is",
        $usuario_id,
        $permiso
    );


    $stmt->execute();


    $resultado =
        $stmt->get_result();


    $permitido =
        $resultado->num_rows > 0;


    $stmt->close();


    return $permitido;

}


/* =========================================================
   EXIGIR PERMISO
   ========================================================= */

function exigirPermiso($permiso)
{

    if (!tienePermiso($permiso)) {

        http_response_code(403);

        ?>

        <!DOCTYPE html>

        <html lang="es">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>
                Acceso denegado | JaverStock
            </title>

            <style>

                body {
                    margin: 0;
                    font-family: Arial, sans-serif;
                    background: #f4f6f9;
                }

                .acceso-denegado {
                    max-width: 500px;
                    margin: 100px auto;
                    padding: 40px;
                    text-align: center;
                    background: #fff;
                    border-radius: 15px;
                    box-shadow: 0 5px 25px rgba(0,0,0,.10);
                }

                .acceso-denegado h1 {
                    color: #dc3545;
                }

                .acceso-denegado p {
                    color: #6c757d;
                }

                .acceso-denegado a {
                    display: inline-block;
                    padding: 10px 20px;
                    color: white;
                    background: #0d6efd;
                    text-decoration: none;
                    border-radius: 8px;
                }

            </style>

        </head>

        <body>

            <div class="acceso-denegado">

                <h1>
                    Acceso denegado
                </h1>

                <p>
                    No tienes permiso para realizar esta acción.
                </p>

                <a href="javascript:history.back();">
                    Volver
                </a>

            </div>

        </body>

        </html>

        <?php

        exit;

    }

}

?>