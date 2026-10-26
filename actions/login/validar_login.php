
<?php

/* =========================================================
   VALIDAR LOGIN - JAVERSTOCK
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

require_once __DIR__ . "/../../includes/conexion.php";


/* =========================================================
   VERIFICAR MÉTODO
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ../../login.php");
    exit;
}


/* =========================================================
   RECIBIR DATOS
   ========================================================= */

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';


/* =========================================================
   VALIDAR CAMPOS
   ========================================================= */

if ($usuario === '' || $password === '') {

    $_SESSION['error_login'] =
        "Por favor, ingresa el usuario y la contraseña.";

    header("Location: ../../login.php");
    exit;
}


/* =========================================================
   BUSCAR USUARIO
   ========================================================= */

$sql = "
    SELECT
        id,
        nombre,
        usuario,
        password,
        rol,
        fecha_cambio_password,
        debe_cambiar_password,
        password_temporal,
        fecha_expiracion_password_temporal
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    $_SESSION['error_login'] =
        "Ocurrió un error al procesar el inicio de sesión.";

    header("Location: ../../login.php");
    exit;
}


$stmt->bind_param("s", $usuario);

$stmt->execute();

$resultado = $stmt->get_result();


/* =========================================================
   USUARIO NO EXISTE
   ========================================================= */

if ($resultado->num_rows === 0) {

    $stmt->close();

    $_SESSION['error_login'] =
        "Usuario o contraseña incorrectos.";

    header("Location: ../../login.php");
    exit;
}


/* =========================================================
   OBTENER USUARIO
   ========================================================= */

$usuarioDB = $resultado->fetch_assoc();

$stmt->close();


/* =========================================================
   DETERMINAR SI LA CONTRASEÑA ES TEMPORAL
   ========================================================= */

$passwordTemporal = (
    isset($usuarioDB['password_temporal']) &&
    (int)$usuarioDB['password_temporal'] === 1
);


/* =========================================================
   VERIFICAR EXPIRACIÓN DE CONTRASEÑA TEMPORAL
   ========================================================= */

if ($passwordTemporal) {

    /*
    |---------------------------------------------------------
    | Debe existir una fecha de expiración
    |---------------------------------------------------------
    */

    if (
        empty($usuarioDB['fecha_expiracion_password_temporal'])
    ) {

        $_SESSION['error_login'] =
            "La contraseña temporal ya no es válida.";

        header("Location: ../../login.php");
        exit;
    }


    /*
    |---------------------------------------------------------
    | Convertir fecha de expiración
    |---------------------------------------------------------
    */

    $fechaExpiracion = strtotime(
        $usuarioDB['fecha_expiracion_password_temporal']
    );


    /*
    |---------------------------------------------------------
    | Verificar si ya expiró
    |---------------------------------------------------------
    */

    if (
        $fechaExpiracion === false ||
        time() >= $fechaExpiracion
    ) {

        /*
        |-----------------------------------------------------
        | Desactivar contraseña temporal
        |-----------------------------------------------------
        */

        $stmtExpiracion = $conn->prepare("
            UPDATE usuarios
            SET
                password_temporal = 0,
                fecha_expiracion_password_temporal = NULL,
                debe_cambiar_password = 0
            WHERE id = ?
        ");


        if ($stmtExpiracion) {

            $idExpirado = (int)$usuarioDB['id'];

            $stmtExpiracion->bind_param(
                "i",
                $idExpirado
            );

            $stmtExpiracion->execute();

            $stmtExpiracion->close();
        }


        /*
        |-----------------------------------------------------
        | Informar al usuario
        |-----------------------------------------------------
        */

        $_SESSION['error_login'] =
            "La contraseña temporal ha expirado. " .
            "Solicita al administrador que establezca una nueva contraseña.";

        header("Location: ../../login.php");
        exit;
    }
}


/* =========================================================
   VERIFICAR CONTRASEÑA
   ========================================================= */

if (
    empty($usuarioDB['password']) ||
    !password_verify(
        $password,
        $usuarioDB['password']
    )
) {

    $_SESSION['error_login'] =
        "Usuario o contraseña incorrectos.";

    header("Location: ../../login.php");
    exit;
}


/* =========================================================
   REGENERAR SESIÓN
   ========================================================= */

session_regenerate_id(true);


/* =========================================================
   GUARDAR DATOS DEL USUARIO
   ========================================================= */

$_SESSION['id'] =
    (int)$usuarioDB['id'];

$_SESSION['nombre'] =
    $usuarioDB['nombre'];

$_SESSION['usuario'] =
    $usuarioDB['usuario'];

$_SESSION['rol'] =
    $usuarioDB['rol'];


/* =========================================================
   GUARDAR ESTADO DE CONTRASEÑA TEMPORAL
   ========================================================= */

$_SESSION['password_temporal'] =
    $passwordTemporal;


/* =========================================================
   DETERMINAR CAMBIO DE CONTRASEÑA
   ========================================================= */

$debeCambiarPassword = false;


/* =========================================================
   CONTRASEÑA TEMPORAL
   ========================================================= */

if ($passwordTemporal) {

    /*
    |---------------------------------------------------------
    | Si la contraseña fue asignada por el administrador,
    | el usuario debe cambiarla inmediatamente.
    |---------------------------------------------------------
    */

    $debeCambiarPassword = true;
}


/* =========================================================
   CAMBIO OBLIGATORIO
   ========================================================= */

if (
    !$debeCambiarPassword &&
    isset($usuarioDB['debe_cambiar_password']) &&
    (int)$usuarioDB['debe_cambiar_password'] === 1
) {

    $debeCambiarPassword = true;
}


/* =========================================================
   REVISAR VENCIMIENTO DE 90 DÍAS
   ========================================================= */

if (!$debeCambiarPassword) {

    if (
        empty($usuarioDB['fecha_cambio_password'])
    ) {

        $debeCambiarPassword = true;

    } else {

        $fechaCambio = strtotime(
            $usuarioDB['fecha_cambio_password']
        );


        if ($fechaCambio !== false) {

            $fechaVencimiento = strtotime(
                '+90 days',
                $fechaCambio
            );


            if (
                time() >= $fechaVencimiento
            ) {

                $debeCambiarPassword = true;
            }
        }
    }
}


/* =========================================================
   GUARDAR ESTADO EN SESIÓN
   ========================================================= */

$_SESSION['debe_cambiar_password'] =
    $debeCambiarPassword;


/* =========================================================
   LIMPIAR ERROR DE LOGIN
   ========================================================= */

unset(
    $_SESSION['error_login']
);


/* =========================================================
   REDIRECCIÓN
   ========================================================= */

if ($debeCambiarPassword) {

    header(
        "Location: ../../pages/cambiar_password.php"
    );

    exit;
}


/* =========================================================
   DASHBOARD
   ========================================================= */

header(
    "Location: ../../pages/dashboard.php"
);

exit;

?>

