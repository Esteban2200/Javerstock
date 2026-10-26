<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/conexion.php";
require_once "../../includes/auth.php";
require_once "../../includes/permisos.php";

exigirPermiso('gestionar_usuarios');

/*
|--------------------------------------------------------------------------
| VALIDAR MÉTODO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/gestion_usuarios.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| RECIBIR DATOS
|--------------------------------------------------------------------------
*/

$nombre = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$rol = trim($_POST['rol'] ?? 'empleado');

$permisos = $_POST['permisos'] ?? [];

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if ($nombre === '' || $usuario === '' || $password === '') {
    header("Location: ../../pages/gestion_usuarios.php?msg=error");
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR CORREO
|--------------------------------------------------------------------------
|
| Lo dejamos obligatorio para preparar la recuperación de contraseña.
|
*/

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../../pages/gestion_usuarios.php?msg=correo_invalido");
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR ROL
|--------------------------------------------------------------------------
*/

$rolesPermitidos = ['empleado', 'admin'];

if (!in_array($rol, $rolesPermitidos, true)) {
    $rol = 'empleado';
}

/*
|--------------------------------------------------------------------------
| COMPROBAR SI EL USUARIO YA EXISTE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");

if (!$stmt) {
    header("Location: ../../pages/gestion_usuarios.php?msg=error");
    exit;
}

$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $stmt->close();

    header("Location: ../../pages/gestion_usuarios.php?msg=usuario_existente");
    exit;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| COMPROBAR SI EL CORREO YA EXISTE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id
    FROM usuarios
    WHERE correo = ?
    LIMIT 1
");

if (!$stmt) {
    header("Location: ../../pages/gestion_usuarios.php?msg=error");
    exit;
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $stmt->close();

    header("Location: ../../pages/gestion_usuarios.php?msg=correo_existente");
    exit;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| ENCRIPTAR CONTRASEÑA
|--------------------------------------------------------------------------
*/

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

if ($passwordHash === false) {
    header("Location: ../../pages/gestion_usuarios.php?msg=error");
    exit;
}

/*
|--------------------------------------------------------------------------
| INICIAR TRANSACCIÓN
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | CREAR USUARIO
    |--------------------------------------------------------------------------
    |
    | fecha_cambio_password = NOW()
    | debe_cambiar_password = 0
    |
    | De esta manera el usuario recién creado comienza con sus 90 días.
    |
    */

    $stmt = $conn->prepare("
        INSERT INTO usuarios
        (
            nombre,
            usuario,
            correo,
            password,
            rol,
            fecha_cambio_password,
            debe_cambiar_password
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            0
        )
    ");

    if (!$stmt) {
        throw new Exception("Error preparando creación de usuario.");
    }

    $stmt->bind_param(
        "sssss",
        $nombre,
        $usuario,
        $correo,
        $passwordHash,
        $rol
    );

    if (!$stmt->execute()) {
        throw new Exception("Error creando usuario.");
    }

    $usuarioId = $stmt->insert_id;

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | GUARDAR PERMISOS
    |--------------------------------------------------------------------------
    */

    if (is_array($permisos) && count($permisos) > 0) {

        $stmtPermiso = $conn->prepare("
            INSERT INTO usuario_permisos
            (
                usuario_id,
                permiso_id
            )
            VALUES
            (
                ?,
                ?
            )
        ");

        if (!$stmtPermiso) {
            throw new Exception("Error preparando permisos.");
        }

        foreach ($permisos as $permisoId) {

            $permisoId = (int)$permisoId;

            if ($permisoId <= 0) {
                continue;
            }

            $stmtPermiso->bind_param(
                "ii",
                $usuarioId,
                $permisoId
            );

            if (!$stmtPermiso->execute()) {
                throw new Exception("Error guardando permisos.");
            }
        }

        $stmtPermiso->close();
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    header(
        "Location: ../../pages/gestion_usuarios.php?msg=usuario_creado"
    );
    exit;

} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | DESHACER CAMBIOS SI OCURRIÓ UN ERROR
    |--------------------------------------------------------------------------
    */

    $conn->rollback();

    header(
        "Location: ../../pages/gestion_usuarios.php?msg=error"
    );
    exit;
}

