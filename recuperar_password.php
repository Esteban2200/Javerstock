
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/conexion.php";

/*
|--------------------------------------------------------------------------
| SI EL USUARIO YA ESTÁ LOGUEADO
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['usuario'])) {
    header("Location: pages/dashboard.php");
    exit;
}

$mensaje = '';
$tipoMensaje = '';

/*
|--------------------------------------------------------------------------
| PROCESAR FORMULARIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES
    |--------------------------------------------------------------------------
    */

    if ($usuario === '' || $correo === '') {

        $mensaje = 'Por favor completa todos los campos.';
        $tipoMensaje = 'danger';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'El correo electrónico no tiene un formato válido.';
        $tipoMensaje = 'danger';

    } else {

        /*
        |--------------------------------------------------------------------------
        | BUSCAR USUARIO
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            SELECT id, nombre, usuario, correo
            FROM usuarios
            WHERE usuario = ?
              AND correo = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "ss",
                $usuario,
                $correo
            );

            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {

                $usuarioDB = $resultado->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | GENERAR CÓDIGO DE RECUPERACIÓN
                |--------------------------------------------------------------------------
                |
                | Código de 6 dígitos.
                |
                */

                $codigo = (string)random_int(100000, 999999);

                /*
                |--------------------------------------------------------------------------
                | HASH DEL CÓDIGO
                |--------------------------------------------------------------------------
                */

                $codigoHash = password_hash(
                    $codigo,
                    PASSWORD_DEFAULT
                );

                /*
                |--------------------------------------------------------------------------
                | EXPIRACIÓN
                |--------------------------------------------------------------------------
                |
                | El código será válido durante 15 minutos.
                |
                */

                $fechaExpiracion = date(
                    'Y-m-d H:i:s',
                    strtotime('+15 minutes')
                );

                /*
                |--------------------------------------------------------------------------
                | CREAR TABLA SI NO EXISTE
                |--------------------------------------------------------------------------
                |
                | Esto facilita la instalación inicial.
                |
                */

                $crearTabla = mysqli_query(
                    $conn,
                    "
                    CREATE TABLE IF NOT EXISTS recuperaciones_password (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        codigo_hash VARCHAR(255) NOT NULL,
                        fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        fecha_expiracion DATETIME NOT NULL,
                        usado TINYINT(1) NOT NULL DEFAULT 0,
                        FOREIGN KEY (usuario_id)
                            REFERENCES usuarios(id)
                            ON DELETE CASCADE
                    )
                    "
                );

                if (!$crearTabla) {

                    $mensaje = 'No fue posible preparar la recuperación de contraseña.';
                    $tipoMensaje = 'danger';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | INVALIDAR CÓDIGOS ANTERIORES
                    |--------------------------------------------------------------------------
                    */

                    $stmtInvalidar = $conn->prepare("
                        UPDATE recuperaciones_password
                        SET usado = 1
                        WHERE usuario_id = ?
                          AND usado = 0
                    ");

                    if ($stmtInvalidar) {

                        $idUsuario = (int)$usuarioDB['id'];

                        $stmtInvalidar->bind_param(
                            "i",
                            $idUsuario
                        );

                        $stmtInvalidar->execute();
                        $stmtInvalidar->close();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | GUARDAR NUEVO CÓDIGO
                    |--------------------------------------------------------------------------
                    */

                    $stmtGuardar = $conn->prepare("
                        INSERT INTO recuperaciones_password
                        (
                            usuario_id,
                            codigo_hash,
                            fecha_expiracion,
                            usado
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            0
                        )
                    ");

                    if ($stmtGuardar) {

                        $idUsuario = (int)$usuarioDB['id'];

                        $stmtGuardar->bind_param(
                            "iss",
                            $idUsuario,
                            $codigoHash,
                            $fechaExpiracion
                        );

                        if ($stmtGuardar->execute()) {

                            /*
                            |--------------------------------------------------------------------------
                            | GUARDAR DATOS EN SESIÓN
                            |--------------------------------------------------------------------------
                            |
                            | Se utilizarán en el siguiente paso.
                            |
                            */

                            $_SESSION['recuperacion_usuario_id'] =
                                $idUsuario;

                            $_SESSION['recuperacion_usuario'] =
                                $usuarioDB['usuario'];

                            $_SESSION['recuperacion_correo'] =
                                $usuarioDB['correo'];

                            $_SESSION['recuperacion_codigo'] =
                                $codigo;

                            /*
                            |--------------------------------------------------------------------------
                            | POR AHORA
                            |--------------------------------------------------------------------------
                            |
                            | Mostramos el código para poder probar el sistema
                            | antes de configurar SMTP/correo.
                            |
                            */

                            $mensaje =
                                'Usuario y correo verificados correctamente. '
                                . 'Tu código de recuperación es: '
                                . '<strong style="font-size:22px;letter-spacing:4px;">'
                                . htmlspecialchars(
                                    $codigo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                . '</strong>'
                                . '<br><small>Este código vence en 15 minutos.</small>';

                            $tipoMensaje = 'success';

                        } else {

                            $mensaje =
                                'No fue posible generar el código de recuperación.';

                            $tipoMensaje = 'danger';
                        }

                        $stmtGuardar->close();

                    } else {

                        $mensaje =
                            'No fue posible preparar el código de recuperación.';

                        $tipoMensaje = 'danger';
                    }
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | USUARIO O CORREO INCORRECTO
                |--------------------------------------------------------------------------
                */

                $mensaje =
                    'El usuario y el correo electrónico no coinciden con una cuenta registrada.';

                $tipoMensaje = 'danger';
            }

            $stmt->close();

        } else {

            $mensaje =
                'Ocurrió un error al consultar la cuenta.';

            $tipoMensaje = 'danger';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>JaverStock | Recuperar contraseña</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link
    rel="stylesheet"
    href="assets/css/login.css">

<style>

.recuperar-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f4f7fb;
    padding: 30px;
}

.recuperar-box {
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 45px rgba(0,0,0,.10);
}

.recuperar-logo {
    display: block;
    width: 120px;
    max-height: 100px;
    object-fit: contain;
    margin: 0 auto 20px;
}

.recuperar-box h2 {
    color: #002855;
    font-weight: 700;
    text-align: center;
    margin-bottom: 8px;
}

.recuperar-descripcion {
    text-align: center;
    color: #64748b;
    margin-bottom: 28px;
}

.recuperar-box .form-label {
    font-weight: 600;
    color: #334155;
}

.recuperar-box .input-group-text {
    background: #f8fafc;
    color: #002855;
}

.btn-recuperar {
    background: #002855;
    border: none;
    color: #fff;
    height: 48px;
    border-radius: 10px;
    font-weight: 700;
}

.btn-recuperar:hover {
    background: #003b78;
    color: #fff;
}

.volver-login {
    text-align: center;
    margin-top: 24px;
}

.volver-login a {
    color: #002855;
    font-weight: 600;
    text-decoration: none;
}

.volver-login a:hover {
    text-decoration: underline;
}

.codigo-prueba {
    margin-top: 15px;
    padding: 15px;
    background: #fff8e1;
    border: 1px solid #ffe082;
    border-radius: 10px;
    text-align: center;
}

</style>

</head>

<body>

<div class="recuperar-container">

    <div class="recuperar-box">

        <img
            src="assets/img/logo-javeriana.png"
            class="recuperar-logo"
            alt="Logo">

        <h2>
            <i class="fas fa-key"></i>
            Recuperar contraseña
        </h2>

        <p class="recuperar-descripcion">
            Ingresa tu usuario y el correo electrónico
            asociado a tu cuenta.
        </p>

        <?php if ($mensaje !== ''): ?>

            <div class="alert alert-<?= $tipoMensaje ?>">

                <i class="fas fa-<?= 
                    $tipoMensaje === 'success'
                        ? 'check-circle'
                        : 'triangle-exclamation'
                ?>"></i>

                <?= $mensaje ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Usuario
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>

                    <input
                        type="text"
                        name="usuario"
                        class="form-control"
                        placeholder="Ingresa tu usuario"
                        value="<?= htmlspecialchars(
                            $_POST['usuario'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required>

                </div>

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Correo electrónico
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>

                    <input
                        type="email"
                        name="correo"
                        class="form-control"
                        placeholder="correo@ejemplo.com"
                        value="<?= htmlspecialchars(
                            $_POST['correo'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required>

                </div>

            </div>

            <button
                type="submit"
                class="btn btn-recuperar w-100">

                <i class="fas fa-paper-plane"></i>
                Recuperar contraseña

            </button>

        </form>

        <div class="volver-login">

            <a href="login.php">
                <i class="fas fa-arrow-left"></i>
                Volver al inicio de sesión
            </a>

        </div>

    </div>

</div>

</body>

</html>

