<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/auth.php";

/*
|--------------------------------------------------------------------------
| VERIFICAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: ../../login.php");
    exit;
}

$usuario_id = (int) $_SESSION['id'];

/*
|--------------------------------------------------------------------------
| RECIBIR DATOS
|--------------------------------------------------------------------------
*/

$passwordActual    = $_POST['password_actual'] ?? '';
$passwordNueva     = $_POST['password_nueva'] ?? '';
$passwordConfirmar = $_POST['password_confirmar'] ?? '';

/*
|--------------------------------------------------------------------------
| VALIDAR CAMPOS
|--------------------------------------------------------------------------
*/

if (
    $passwordActual === '' ||
    $passwordNueva === '' ||
    $passwordConfirmar === ''
) {
    header(
        "Location: ../../pages/cambiar_password.php?error=campos_vacios"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR LONGITUD
|--------------------------------------------------------------------------
*/

if (strlen($passwordNueva) < 8) {
    header(
        "Location: ../../pages/cambiar_password.php?error=password_corta"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR CONFIRMACIÓN
|--------------------------------------------------------------------------
*/

if ($passwordNueva !== $passwordConfirmar) {
    header(
        "Location: ../../pages/cambiar_password.php?error=password_no_coincide"
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| CONSULTAR USUARIO
|--------------------------------------------------------------------------
|
| Se agregan:
|
| password_temporal
| fecha_expiracion_password_temporal
|
*/

$stmt = $conn->prepare("
    SELECT
        id,
        password,
        fecha_cambio_password,
        debe_cambiar_password,
        password_temporal,
        fecha_expiracion_password_temporal
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    header(
        "Location: ../../pages/cambiar_password.php?error=error_bd"
    );
    exit;
}

$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {

    $stmt->close();

    header(
        "Location: ../../pages/cambiar_password.php?error=usuario_no_existe"
    );
    exit;
}

$usuario = $resultado->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| DETERMINAR SI LA CONTRASEÑA ES TEMPORAL
|--------------------------------------------------------------------------
*/

$passwordTemporal = (
    isset($usuario['password_temporal']) &&
    (int) $usuario['password_temporal'] === 1
);

/*
|--------------------------------------------------------------------------
| VERIFICAR EXPIRACIÓN DE CONTRASEÑA TEMPORAL
|--------------------------------------------------------------------------
*/

if ($passwordTemporal) {

    if (
        empty($usuario['fecha_expiracion_password_temporal']) ||
        strtotime($usuario['fecha_expiracion_password_temporal']) <= time()
    ) {

        /*
        |----------------------------------------------------------------------
        | LA CONTRASEÑA TEMPORAL YA EXPIRÓ
        |----------------------------------------------------------------------
        */

        header(
            "Location: ../../login.php?error=password_temporal_expirada"
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| VERIFICAR CONTRASEÑA ACTUAL
|--------------------------------------------------------------------------
*/

if (!password_verify(
    $passwordActual,
    $usuario['password']
)) {

    header(
        "Location: ../../pages/cambiar_password.php?error=password_actual"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| EVITAR USAR LA MISMA CONTRASEÑA
|--------------------------------------------------------------------------
*/

if (
    password_verify(
        $passwordNueva,
        $usuario['password']
    )
) {

    header(
        "Location: ../../pages/cambiar_password.php?error=password_igual"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| RESTRICCIÓN DE 24 HORAS
|--------------------------------------------------------------------------
|
| IMPORTANTE:
|
| Si la contraseña fue asignada temporalmente por un administrador,
| NO se aplica la restricción de 24 horas.
|
| El usuario puede cambiar inmediatamente la contraseña temporal.
|
*/

if (!$passwordTemporal) {

    if (!empty($usuario['fecha_cambio_password'])) {

        $ultimaFechaCambio = strtotime(
            $usuario['fecha_cambio_password']
        );

        $ahora = time();

        $tiempoTranscurrido =
            $ahora - $ultimaFechaCambio;

        /*
        |----------------------------------------------------------------------
        | 24 HORAS = 86.400 SEGUNDOS
        |----------------------------------------------------------------------
        */

        $periodo24Horas = 86400;

        if ($tiempoTranscurrido < $periodo24Horas) {

            $segundosRestantes =
                $periodo24Horas - $tiempoTranscurrido;

            $horasRestantes = floor(
                $segundosRestantes / 3600
            );

            $minutosRestantes = floor(
                ($segundosRestantes % 3600) / 60
            );

            header(
                "Location: ../../pages/cambiar_password.php" .
                "?error=password_24h" .
                "&horas=" . $horasRestantes .
                "&minutos=" . $minutosRestantes
            );

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| GENERAR HASH
|--------------------------------------------------------------------------
*/

$passwordHash = password_hash(
    $passwordNueva,
    PASSWORD_DEFAULT
);

if ($passwordHash === false) {

    header(
        "Location: ../../pages/cambiar_password.php?error=error_password"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ACTUALIZAR CONTRASEÑA
|--------------------------------------------------------------------------
|
| Después de cambiar la contraseña:
|
| password_temporal = 0
| fecha_expiracion_password_temporal = NULL
| fecha_cambio_password = NOW()
| debe_cambiar_password = 0
|
| Esto convierte la contraseña temporal en una contraseña definitiva.
|
*/

$stmt = $conn->prepare("
    UPDATE usuarios
    SET
        password = ?,
        fecha_cambio_password = NOW(),
        debe_cambiar_password = 0,
        password_temporal = 0,
        fecha_expiracion_password_temporal = NULL
    WHERE id = ?
");

if (!$stmt) {

    header(
        "Location: ../../pages/cambiar_password.php?error=error_bd"
    );

    exit;
}

$stmt->bind_param(
    "si",
    $passwordHash,
    $usuario_id
);

if (!$stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../../pages/cambiar_password.php?error=actualizacion"
    );

    exit;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| ACTUALIZAR SESIÓN
|--------------------------------------------------------------------------
*/

$_SESSION['debe_cambiar_password'] = false;
$_SESSION['password_temporal'] = false;

/*
|--------------------------------------------------------------------------
| REDIRECCIONAR AL DASHBOARD
|--------------------------------------------------------------------------
*/

header(
    "Location: ../../pages/dashboard.php?msg=password_actualizada"
);

exit;