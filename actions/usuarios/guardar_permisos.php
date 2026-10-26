<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/auth.php";
require_once "../../includes/permisos.php";


/*
|--------------------------------------------------------------------------
| Verificar permiso para gestionar usuarios
|--------------------------------------------------------------------------
*/

exigirPermiso('gestionar_usuarios');


/*
|--------------------------------------------------------------------------
| Recibir usuario
|--------------------------------------------------------------------------
*/

$usuario_id = isset($_POST['usuario_id'])
    ? (int) $_POST['usuario_id']
    : 0;


/*
|--------------------------------------------------------------------------
| Verificar usuario
|--------------------------------------------------------------------------
*/

if ($usuario_id <= 0) {

    header("Location: ../../pages/gestion_usuarios.php?error=usuario_invalido");
    exit;
}


$stmt = $conn->prepare("
    SELECT id
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {

    header("Location: ../../pages/gestion_usuarios.php?error=usuario_no_existe");
    exit;
}


/*
|--------------------------------------------------------------------------
| Obtener permisos seleccionados
|--------------------------------------------------------------------------
*/

$permisos = $_POST['permisos'] ?? [];


/*
|--------------------------------------------------------------------------
| Eliminar permisos anteriores
|--------------------------------------------------------------------------
*/

$stmtEliminar = $conn->prepare("
    DELETE FROM usuario_permisos
    WHERE usuario_id = ?
");

$stmtEliminar->bind_param("i", $usuario_id);
$stmtEliminar->execute();


/*
|--------------------------------------------------------------------------
| Guardar nuevos permisos
|--------------------------------------------------------------------------
*/

if (!empty($permisos)) {

    $stmtInsertar = $conn->prepare("
        INSERT INTO usuario_permisos
        (usuario_id, permiso_id)
        VALUES (?, ?)
    ");

    foreach ($permisos as $permiso_id) {

        $permiso_id = (int) $permiso_id;

        if ($permiso_id <= 0) {
            continue;
        }

        $stmtInsertar->bind_param(
            "ii",
            $usuario_id,
            $permiso_id
        );

        $stmtInsertar->execute();
    }
}


/*
|--------------------------------------------------------------------------
| Regresar
|--------------------------------------------------------------------------
*/

header(
    "Location: ../../pages/gestion_usuarios.php?msg=permisos_actualizados"
);

exit;