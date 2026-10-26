<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/permisos.php";

exigirPermiso('gestionar_mantenimiento');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ../../pages/mantenimiento.php?msg=error");
    exit;

}


/* =========================================================
   DATOS
   ========================================================= */

$equipoId = (int)($_POST['equipo_id'] ?? 0);

$tipo = trim($_POST['tipo'] ?? 'correctivo');

$fechaInicio = trim($_POST['fecha_inicio'] ?? '');

$tecnico = trim($_POST['tecnico'] ?? '');

$problema = trim($_POST['problema'] ?? '');

$trabajoRealizado =
    trim($_POST['trabajo_realizado'] ?? '');

$costo = (float)($_POST['costo'] ?? 0);

$observaciones =
    trim($_POST['observaciones'] ?? '');


/* =========================================================
   VALIDAR
   ========================================================= */

if (
    $equipoId <= 0 ||
    $fechaInicio === ''
) {

    header(
        "Location: ../../pages/mantenimiento.php?msg=error"
    );

    exit;

}


if (
    $tipo !== 'preventivo' &&
    $tipo !== 'correctivo'
) {

    $tipo = 'correctivo';

}


/* =========================================================
   TRANSACCIÓN
   ========================================================= */

$conn->begin_transaction();


try {


    /* =====================================================
       REGISTRAR MANTENIMIENTO
       ===================================================== */

    $stmt = $conn->prepare("
        INSERT INTO mantenimientos
        (
            equipo_id,
            tipo,
            fecha_inicio,
            tecnico,
            problema,
            trabajo_realizado,
            costo,
            estado,
            observaciones
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 'en_proceso', ?)
    ");


    if (!$stmt) {

        throw new Exception(
            "Error preparando mantenimiento: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "issssdss",
        $equipoId,
        $tipo,
        $fechaInicio,
        $tecnico,
        $problema,
        $trabajoRealizado,
        $costo,
        $observaciones
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Error registrando mantenimiento: " .
            $stmt->error
        );

    }


    $stmt->close();


    /* =====================================================
       CAMBIAR ESTADO DEL EQUIPO
       ===================================================== */

    $stmt = $conn->prepare("
        UPDATE equipos
        SET estado = 'mantenimiento'
        WHERE id = ?
    ");


    if (!$stmt) {

        throw new Exception(
            "Error preparando actualización del equipo: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "i",
        $equipoId
    );


    if (!$stmt->execute()) {

        throw new Exception(
            "Error actualizando equipo: " .
            $stmt->error
        );

    }


    $stmt->close();


    /* =====================================================
       CONFIRMAR
       ===================================================== */

    $conn->commit();


    header(
        "Location: ../../pages/mantenimiento.php?msg=mantenimiento_creado"
    );

    exit;


} catch (Throwable $e) {


    $conn->rollback();


    die(
        "<h2>Error al registrar mantenimiento</h2>" .
        "<p>" .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        "</p>"
    );

}