
<?php

/* =========================================================
   ACTUALIZAR USUARIO - JAVERSTOCK
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CONEXIÓN Y SEGURIDAD
   ========================================================= */

require_once __DIR__ . "/../../includes/conexion.php";
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/permisos.php";


/* =========================================================
   PERMISO NECESARIO
   ========================================================= */

exigirPermiso('gestionar_usuarios');


/* =========================================================
   RECIBIR DATOS
   ========================================================= */

$id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

$nombre = trim(
    $_POST['nombre'] ?? ''
);

$usuario = trim(
    $_POST['usuario'] ?? ''
);

$correo = trim(
    $_POST['correo'] ?? ''
);

$rol = trim(
    $_POST['rol'] ?? ''
);

$password = $_POST['password'] ?? '';

$permisos = $_POST['permisos'] ?? [];


/* =========================================================
   VALIDAR ID
   ========================================================= */

if ($id <= 0) {

    header(
        "Location: ../../pages/gestion_usuarios.php?error=usuario_invalido"
    );

    exit;
}


/* =========================================================
   VALIDAR CAMPOS
   ========================================================= */

if (
    $nombre === '' ||
    $usuario === '' ||
    $rol === ''
) {

    header(
        "Location: ../../pages/gestion_usuarios.php?error=campos_vacios"
    );

    exit;
}


/* =========================================================
   VALIDAR ROL
   ========================================================= */

$rolesPermitidos = [
    'admin',
    'empleado'
];

if (!in_array(
    $rol,
    $rolesPermitidos,
    true
)) {

    header(
        "Location: ../../pages/gestion_usuarios.php?error=rol_invalido"
    );

    exit;
}


/* =========================================================
   VALIDAR CORREO
   ========================================================= */

if (
    $correo !== '' &&
    !filter_var(
        $correo,
        FILTER_VALIDATE_EMAIL
    )
) {

    header(
        "Location: ../../pages/gestion_usuarios.php?error=correo_invalido"
    );

    exit;
}


/* =========================================================
   VALIDAR CONTRASEÑA
   ========================================================= */

if (
    $password !== '' &&
    strlen($password) < 8
) {

    header(
        "Location: ../../pages/gestion_usuarios.php?error=password_corta"
    );

    exit;
}


/* =========================================================
   IDENTIFICAR USUARIO ACTUAL
   ========================================================= */

$usuarioSesionId = isset($_SESSION['id'])
    ? (int) $_SESSION['id']
    : 0;

$esUsuarioActual = (
    $usuarioSesionId > 0 &&
    $usuarioSesionId === $id
);


/* =========================================================
   CONSULTAR USUARIO ACTUAL
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        nombre,
        usuario,
        rol,
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
        "Location: ../../pages/gestion_usuarios.php?error=error_bd"
    );

    exit;
}


$stmt->bind_param(
    "i",
    $id
);


$stmt->execute();


$resultado = $stmt->get_result();


/* =========================================================
   USUARIO NO EXISTE
   ========================================================= */

if ($resultado->num_rows === 0) {

    $stmt->close();

    header(
        "Location: ../../pages/gestion_usuarios.php?error=usuario_no_existe"
    );

    exit;
}


$usuarioActual = $resultado->fetch_assoc();

$stmt->close();


/* =========================================================
   RESTRICCIÓN DE 24 HORAS
   =========================================================
   
   IMPORTANTE:
   
   Si el administrador está cambiando la contraseña
   de OTRO usuario, NO se aplica la restricción de 24 horas.
   
   Esa nueva contraseña será temporal.
   
   Si el usuario está modificando SU PROPIA contraseña,
   sí se mantiene la restricción de 24 horas.
   ========================================================= */

if (
    $password !== '' &&
    $esUsuarioActual
) {

    if (
        !empty(
            $usuarioActual['fecha_cambio_password']
        )
    ) {

        $ultimaFechaCambio = strtotime(
            $usuarioActual['fecha_cambio_password']
        );


        if ($ultimaFechaCambio !== false) {

            $ahora = time();

            $tiempoTranscurrido =
                $ahora - $ultimaFechaCambio;

            $periodo24Horas = 86400;


            if (
                $tiempoTranscurrido < $periodo24Horas
            ) {

                $segundosRestantes =
                    $periodo24Horas -
                    $tiempoTranscurrido;


                $horasRestantes = floor(
                    $segundosRestantes / 3600
                );


                $minutosRestantes = floor(
                    ($segundosRestantes % 3600) / 60
                );


                header(
                    "Location: ../../pages/gestion_usuarios.php" .
                    "?error=password_24h" .
                    "&horas=" . $horasRestantes .
                    "&minutos=" . $minutosRestantes
                );

                exit;
            }
        }
    }
}


/* =========================================================
   VERIFICAR USUARIO DUPLICADO
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id
    FROM usuarios
    WHERE usuario = ?
    AND id <> ?
    LIMIT 1
");


if (!$stmt) {

    header(
        "Location: ../../pages/gestion_usuarios.php?error=error_bd"
    );

    exit;
}


$stmt->bind_param(
    "si",
    $usuario,
    $id
);


$stmt->execute();


$resultado = $stmt->get_result();


if ($resultado->num_rows > 0) {

    $stmt->close();

    header(
        "Location: ../../pages/gestion_usuarios.php?error=usuario_existente"
    );

    exit;
}


$stmt->close();


/* =========================================================
   NORMALIZAR PERMISOS
   ========================================================= */

if (!is_array($permisos)) {
    $permisos = [];
}


$permisos = array_unique(
    array_map(
        'intval',
        $permisos
    )
);


/* =========================================================
   INICIAR TRANSACCIÓN
   ========================================================= */

$conn->begin_transaction();


try {

    /* =====================================================
       SI SE CAMBIA LA CONTRASEÑA
       ===================================================== */

    if ($password !== '') {

        /*
         * Generar hash seguro.
         */

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        if ($passwordHash === false) {

            throw new Exception(
                "No se pudo generar la contraseña."
            );
        }


        /*
         * ==================================================
         * CONTRASEÑA ASIGNADA POR ADMINISTRADOR
         * ==================================================
         *
         * Si el administrador cambia la contraseña de OTRO
         * usuario:
         *
         * password_temporal = 1
         * debe_cambiar_password = 1
         * fecha_expiracion_password_temporal = NOW() + 24h
         *
         * El usuario podrá entrar inmediatamente y cambiar
         * la contraseña sin esperar las 24 horas.
         *
         * ==================================================
         * CONTRASEÑA PROPIA
         * ==================================================
         *
         * Si el usuario modifica su propia contraseña:
         *
         * password_temporal = 0
         * debe_cambiar_password = 0
         * fecha_expiracion_password_temporal = NULL
         * fecha_cambio_password = NOW()
         *
         * ==================================================
         */

        if ($esUsuarioActual) {

            /*
             * El usuario está estableciendo su propia
             * contraseña definitiva.
             */

            $debeCambiarPassword = 0;

            $passwordTemporal = 0;


            /*
             * No existe expiración para una contraseña
             * definitiva.
             */

            $fechaExpiracionTemporal = null;


            /*
             * La fecha de cambio comienza a contar
             * desde este momento.
             */

            $fechaCambioPassword = "NOW()";


        } else {

            /*
             * El administrador está estableciendo la
             * contraseña de otro usuario.
             */

            $debeCambiarPassword = 1;

            $passwordTemporal = 1;


            /*
             * La contraseña temporal tendrá 24 horas
             * de vigencia.
             */

            $fechaExpiracionTemporal =
                date(
                    'Y-m-d H:i:s',
                    time() + 86400
                );


            /*
             * IMPORTANTE:
             *
             * No actualizamos fecha_cambio_password
             * porque esta fecha corresponde a la última
             * contraseña DEFINITIVA establecida por el usuario.
             */

            $fechaCambioPassword = null;
        }


        /* =================================================
           ACTUALIZAR USUARIO
           ================================================= */

        if ($esUsuarioActual) {

            /*
             * Usuario cambiando su propia contraseña.
             */

            $stmtUsuario = $conn->prepare("
                UPDATE usuarios
                SET
                    nombre = ?,
                    usuario = ?,
                    correo = ?,
                    rol = ?,
                    password = ?,
                    fecha_cambio_password = NOW(),
                    debe_cambiar_password = 0,
                    password_temporal = 0,
                    fecha_expiracion_password_temporal = NULL
                WHERE id = ?
            ");


            if (!$stmtUsuario) {

                throw new Exception(
                    "No se pudo preparar la actualización del usuario."
                );
            }


            $stmtUsuario->bind_param(
                "sssssi",
                $nombre,
                $usuario,
                $correo,
                $rol,
                $passwordHash,
                $id
            );


        } else {

            /*
             * Administrador cambiando la contraseña
             * de otro usuario.
             *
             * La contraseña será TEMPORAL durante 24 horas.
             */

            $stmtUsuario = $conn->prepare("
                UPDATE usuarios
                SET
                    nombre = ?,
                    usuario = ?,
                    correo = ?,
                    rol = ?,
                    password = ?,
                    debe_cambiar_password = 1,
                    password_temporal = 1,
                    fecha_expiracion_password_temporal = ?
                WHERE id = ?
            ");


            if (!$stmtUsuario) {

                throw new Exception(
                    "No se pudo preparar la actualización del usuario."
                );
            }


            $stmtUsuario->bind_param(
                "ssssssi",
                $nombre,
                $usuario,
                $correo,
                $rol,
                $passwordHash,
                $fechaExpiracionTemporal,
                $id
            );
        }


    } else {

        /* =================================================
           ACTUALIZAR SIN CAMBIAR CONTRASEÑA
           ================================================= */

        $stmtUsuario = $conn->prepare("
            UPDATE usuarios
            SET
                nombre = ?,
                usuario = ?,
                correo = ?,
                rol = ?
            WHERE id = ?
        ");


        if (!$stmtUsuario) {

            throw new Exception(
                "No se pudo preparar la actualización del usuario."
            );
        }


        $stmtUsuario->bind_param(
            "ssssi",
            $nombre,
            $usuario,
            $correo,
            $rol,
            $id
        );
    }


    /* =====================================================
       EJECUTAR ACTUALIZACIÓN
       ===================================================== */

    if (!$stmtUsuario->execute()) {

        throw new Exception(
            "No se pudo actualizar el usuario."
        );
    }


    $stmtUsuario->close();


    /* =====================================================
       ELIMINAR PERMISOS ANTERIORES
       ===================================================== */

    $stmtEliminar = $conn->prepare("
        DELETE FROM usuario_permisos
        WHERE usuario_id = ?
    ");


    if (!$stmtEliminar) {

        throw new Exception(
            "No se pudo preparar la eliminación de permisos."
        );
    }


    $stmtEliminar->bind_param(
        "i",
        $id
    );


    if (!$stmtEliminar->execute()) {

        throw new Exception(
            "No se pudieron eliminar los permisos anteriores."
        );
    }


    $stmtEliminar->close();


    /* =====================================================
       INSERTAR NUEVOS PERMISOS
       ===================================================== */

    if (!empty($permisos)) {

        /*
         * Preparar comprobación de permisos.
         */

        $stmtExiste = $conn->prepare("
            SELECT
                id
            FROM permisos
            WHERE id = ?
            LIMIT 1
        ");


        if (!$stmtExiste) {

            throw new Exception(
                "No se pudo verificar los permisos."
            );
        }


        /*
         * Preparar inserción.
         */

        $stmtPermiso = $conn->prepare("
            INSERT INTO usuario_permisos
            (
                usuario_id,
                permiso_id
            )
            VALUES (?, ?)
        ");


        if (!$stmtPermiso) {

            throw new Exception(
                "No se pudo preparar el registro de permisos."
            );
        }


        foreach ($permisos as $permiso_id) {

            $permiso_id = (int) $permiso_id;


            if ($permiso_id <= 0) {
                continue;
            }


            /* =============================================
               COMPROBAR QUE EL PERMISO EXISTA
               ============================================= */

            $stmtExiste->bind_param(
                "i",
                $permiso_id
            );


            $stmtExiste->execute();


            $resultadoPermiso =
                $stmtExiste->get_result();


            if (
                $resultadoPermiso->num_rows === 0
            ) {

                continue;
            }


            /* =============================================
               INSERTAR PERMISO
               ============================================= */

            $stmtPermiso->bind_param(
                "ii",
                $id,
                $permiso_id
            );


            if (!$stmtPermiso->execute()) {

                throw new Exception(
                    "No se pudo guardar uno de los permisos."
                );
            }
        }


        $stmtExiste->close();

        $stmtPermiso->close();
    }


    /* =====================================================
       CONFIRMAR TRANSACCIÓN
       ===================================================== */

    $conn->commit();


    /* =====================================================
       ACTUALIZAR SESIÓN
       ===================================================== */

    if ($esUsuarioActual) {

        $_SESSION['id'] =
            $id;

        $_SESSION['nombre'] =
            $nombre;

        $_SESSION['usuario'] =
            $usuario;

        $_SESSION['rol'] =
            $rol;


        /*
         * Si el propio usuario cambió su contraseña,
         * ya no necesita cambiarla.
         */

        if ($password !== '') {

            $_SESSION['debe_cambiar_password'] =
                false;

            $_SESSION['password_temporal'] =
                false;
        }
    }


    /* =====================================================
       REDIRECCIÓN EXITOSA
       ===================================================== */

    header(
        "Location: ../../pages/gestion_usuarios.php?msg=usuario_actualizado"
    );

    exit;


} catch (Exception $e) {

    /* =====================================================
       DESHACER CAMBIOS
       ===================================================== */

    $conn->rollback();


    header(
        "Location: ../../pages/gestion_usuarios.php?error=actualizacion_fallida"
    );

    exit;
}

?>

