<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('eliminar_equipo');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {

    header(
        "Location: ../../pages/equipos.php?error=ID de equipo inválido"
    );

    exit;
}


/* Verificar que exista */

$stmt = $conn->prepare(
    "SELECT id
     FROM equipos
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {

    $stmt->close();

    header(
        "Location: ../../pages/equipos.php?error=El equipo no existe"
    );

    exit;
}

$stmt->close();


/*
 * Verificar si tiene mantenimientos asociados.
 * Esto evita romper las relaciones de la base de datos.
 */

$stmt = $conn->prepare(
    "SELECT id
     FROM mantenimientos
     WHERE equipo_id = ?
     LIMIT 1"
);

if ($stmt) {

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {

        $stmt->close();

        header(
            "Location: ../../pages/equipos.php?error=" .
            urlencode("equipo_tiene_mantenimiento")
        );

        exit;
    }

    $stmt->close();
}


/* Eliminar */

$stmt = $conn->prepare(
    "DELETE FROM equipos
     WHERE id = ?"
);

if (!$stmt) {

    header(
        "Location: ../../pages/equipos.php?error=" .
        urlencode($conn->error)
    );

    exit;
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {

    $error = $stmt->error;

    $stmt->close();

    header(
        "Location: ../../pages/equipos.php?error=" .
        urlencode($error)
    );

    exit;
}

$stmt->close();

header(
    "Location: ../../pages/equipos.php?msg=equipo_eliminado"
);

exit;
?>