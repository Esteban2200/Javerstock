<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";


/*
|--------------------------------------------------------------------------
| Verificar permiso
|--------------------------------------------------------------------------
*/

exigirPermiso('gestionar_usuarios');


/*
|--------------------------------------------------------------------------
| ID
|--------------------------------------------------------------------------
*/

$id = (int) ($_GET['id'] ?? 0);


if ($id <= 0) {

    header("Location: ../../pages/usuarios.php?msg=error");
    exit;

}


/*
|--------------------------------------------------------------------------
| No permitir eliminar al usuario que está conectado
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['id']) &&
    $id === (int) $_SESSION['id']
) {

    header(
        "Location: ../../pages/usuarios.php?msg=no_puede_eliminarse"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Comprobar usuario
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id
     FROM usuarios
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows === 0) {

    header(
        "Location: ../../pages/usuarios.php?msg=error"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Eliminar permisos del usuario
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "DELETE FROM usuario_permisos
     WHERE usuario_id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();


/*
|--------------------------------------------------------------------------
| Eliminar usuario
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "DELETE FROM usuarios
     WHERE id = ?"
);

$stmt->bind_param("i", $id);


if (!$stmt->execute()) {

    die(
        "Error al eliminar usuario: " .
        $stmt->error
    );

}


/*
|--------------------------------------------------------------------------
| Regresar
|--------------------------------------------------------------------------
*/

header(
    "Location: ../../pages/usuarios.php?msg=usuario_eliminado"
);

exit;