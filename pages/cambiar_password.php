<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/auth.php";
require_once "../includes/permisos.php";

$nombreUsuario = $_SESSION['nombre']
    ?? $_SESSION['usuario']
    ?? 'Usuario';

$inicial = strtoupper(
    substr(
        trim($nombreUsuario),
        0,
        1
    )
);

if ($inicial === '') {
    $inicial = 'U';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cambiar contraseña - JaverStock</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "../includes/header.php"; ?>

    <div class="container-fluid py-4">

        <div class="row justify-content-center">

            <div class="col-lg-6 col-md-8">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white py-3">

                        <h4 class="mb-1">
                            <i class="fas fa-key me-2"></i>
                            Cambiar contraseña
                        </h4>

                        <p class="text-muted mb-0">
                            Actualiza la contraseña de tu cuenta.
                        </p>

                    </div>

                    <div class="card-body p-4">

                        <form
                            action="../actions/usuarios/cambiar_password.php"
                            method="POST"
                            id="formCambiarPassword"
                        >

                            <!-- CONTRASEÑA ACTUAL -->

                            <div class="mb-3">

                                <label
                                    for="password_actual"
                                    class="form-label"
                                >
                                    Contraseña actual
                                </label>

                                <div class="input-group">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password_actual"
                                        name="password_actual"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        onclick="mostrarPassword(
                                            'password_actual',
                                            this
                                        )"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>

                                </div>

                            </div>


                            <!-- NUEVA CONTRASEÑA -->

                            <div class="mb-3">

                                <label
                                    for="password_nueva"
                                    class="form-label"
                                >
                                    Nueva contraseña
                                </label>

                                <div class="input-group">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password_nueva"
                                        name="password_nueva"
                                        minlength="8"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        onclick="mostrarPassword(
                                            'password_nueva',
                                            this
                                        )"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>

                                </div>

                                <small class="text-muted">
                                    Mínimo 8 caracteres.
                                </small>

                            </div>


                            <!-- CONFIRMAR CONTRASEÑA -->

                            <div class="mb-4">

                                <label
                                    for="password_confirmar"
                                    class="form-label"
                                >
                                    Confirmar nueva contraseña
                                </label>

                                <div class="input-group">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password_confirmar"
                                        name="password_confirmar"
                                        minlength="8"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        onclick="mostrarPassword(
                                            'password_confirmar',
                                            this
                                        )"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>

                                </div>

                            </div>


                            <!-- INFORMACIÓN -->

                            <div class="alert alert-info">

                                <i class="fas fa-circle-info me-2"></i>

                                Recuerda que una vez cambies tu contraseña
                                deberás esperar <strong>24 horas</strong>
                                antes de poder realizar otro cambio.

                            </div>


                            <!-- BOTONES -->

                            <div class="d-flex justify-content-end gap-2">

                                <a
                                    href="dashboard.php"
                                    class="btn btn-secondary"
                                >
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Cancelar
                                </a>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    <i class="fas fa-save me-1"></i>
                                    Cambiar contraseña
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
></script>

<script>

function mostrarPassword(
    id,
    boton
) {

    const input = document.getElementById(id);

    const icono = boton.querySelector('i');

    if (input.type === 'password') {

        input.type = 'text';

        icono.classList.remove(
            'fa-eye'
        );

        icono.classList.add(
            'fa-eye-slash'
        );

    } else {

        input.type = 'password';

        icono.classList.remove(
            'fa-eye-slash'
        );

        icono.classList.add(
            'fa-eye'
        );
    }
}

</script>


<?php

/*
|--------------------------------------------------------------------------
| MENSAJES
|--------------------------------------------------------------------------
*/

if (isset($_GET['error'])):

?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const error =
            <?= json_encode($_GET['error']) ?>;

        <?php

        $horas = isset($_GET['horas'])
            ? (int) $_GET['horas']
            : 0;

        $minutos = isset($_GET['minutos'])
            ? (int) $_GET['minutos']
            : 0;

        ?>

        const horas =
            <?= $horas ?>;

        const minutos =
            <?= $minutos ?>;


        if (error === 'password_24h') {

            Swal.fire({

                icon: 'info',

                title:
                    'Cambio de contraseña no disponible',

                html:
                    `
                    <p>
                        Ya cambiaste tu contraseña
                        recientemente.
                    </p>

                    <p>
                        Debes esperar
                        <strong>
                            ${horas} horas
                            ${minutos} minutos
                        </strong>
                        para realizar un nuevo cambio.
                    </p>
                    `,

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'password_actual'
        ) {

            Swal.fire({

                icon: 'error',

                title:
                    'Contraseña incorrecta',

                text:
                    'La contraseña actual no es correcta.',

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'password_no_coincide'
        ) {

            Swal.fire({

                icon: 'error',

                title:
                    'Las contraseñas no coinciden',

                text:
                    'Verifica que hayas escrito la misma contraseña.',

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'password_igual'
        ) {

            Swal.fire({

                icon: 'warning',

                title:
                    'Contraseña no válida',

                text:
                    'La nueva contraseña debe ser diferente a la actual.',

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'password_corta'
        ) {

            Swal.fire({

                icon: 'warning',

                title:
                    'Contraseña demasiado corta',

                text:
                    'La contraseña debe tener mínimo 8 caracteres.',

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'campos_vacios'
        ) {

            Swal.fire({

                icon: 'warning',

                title:
                    'Campos incompletos',

                text:
                    'Debes completar todos los campos.',

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'error_bd'
        ) {

            Swal.fire({

                icon: 'error',

                title:
                    'Error',

                text:
                    'Ocurrió un error en la base de datos.',

                confirmButtonText:
                    'Entendido'

            });

        }


        else if (
            error === 'actualizacion'
        ) {

            Swal.fire({

                icon: 'error',

                title:
                    'No se pudo actualizar',

                text:
                    'No fue posible cambiar la contraseña.',

                confirmButtonText:
                    'Entendido'

            });

        }

    }
);

</script>

<?php endif; ?>


<?php if (
    isset($_GET['msg']) &&
    $_GET['msg'] === 'password_actualizada'
): ?>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        Swal.fire({

            icon: 'success',

            title:
                'Contraseña actualizada',

            text:
                'Tu contraseña fue cambiada correctamente. Podrás volver a cambiarla después de 24 horas.',

            confirmButtonText:
                'Continuar'

        });

    }
);

</script>

<?php endif; ?>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>