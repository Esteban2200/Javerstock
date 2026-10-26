<?php

/* =========================================================
   AUTENTICACIÓN JAVERSTOCK
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

if (
    !isset($_SESSION['id']) ||
    empty($_SESSION['id'])
) {

    header("Location: ../login.php");
    exit;

}


/* =========================================================
   PÁGINA ACTUAL
   ========================================================= */

$paginaActual =
    basename($_SERVER['PHP_SELF']);


/* =========================================================
   CAMBIO DE CONTRASEÑA
   ========================================================= */

$paginaCambioPassword =
    ($paginaActual === 'cambiar_password.php');


/* =========================================================
   OBLIGAR CAMBIO DE CONTRASEÑA
   ========================================================= */

if (
    isset($_SESSION['debe_cambiar_password']) &&
    $_SESSION['debe_cambiar_password'] === true &&
    !$paginaCambioPassword
) {

    header(
        "Location: cambiar_password.php"
    );

    exit;

}

?>