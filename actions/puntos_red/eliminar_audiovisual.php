<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/permisos.php";

exigirPermiso('configuracion');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: ../../pages/puntos_red.php?area=audiovisuales&error=id_invalido");
    exit;
}

$stmt = $conn->prepare(
    "DELETE FROM puntos_red_audiovisuales WHERE id = ?"
);

if (!$stmt) {
    die("Error preparando consulta: " . $conn->error);
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    header(
        "Location: ../../pages/puntos_red.php?area=audiovisuales&msg=audiovisual_eliminado"
    );

} else {

    header(
        "Location: ../../pages/puntos_red.php?area=audiovisuales&error="
        . urlencode($stmt->error)
    );
}

$stmt->close();

exit;