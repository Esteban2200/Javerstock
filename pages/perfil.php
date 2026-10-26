<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/auth.php";
require_once "../includes/conexion.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

$idUsuario = (int) $_SESSION['id'];


/* =========================================================
   CONSULTAR USUARIO
========================================================= */

$sql = "SELECT
            id,
            nombre,
            usuario,
            rol,
            correo,
            fecha_cambio_password,
            debe_cambiar_password
        FROM usuarios
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error en la consulta: " . $conn->error);
}

$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();


if (!$usuario) {

    session_destroy();

    header("Location: ../login.php");

    exit;
}


/* =========================================================
   DATOS DEL USUARIO
========================================================= */

$nombre = $usuario['nombre'] ?? 'Usuario';

$nombreUsuario = $usuario['usuario'] ?? '';

$rol = $usuario['rol'] ?? 'Sin rol';

$correo = $usuario['correo'] ?? '';

$fechaCambioPassword =
    $usuario['fecha_cambio_password'] ?? null;

$debeCambiarPassword =
    (int)($usuario['debe_cambiar_password'] ?? 0);


/* =========================================================
   FECHA DE CAMBIO
========================================================= */

$fechaCambioTexto = 'No registrada';

if (!empty($fechaCambioPassword)) {

    $fechaCambioTexto = date(
        'd/m/Y H:i',
        strtotime($fechaCambioPassword)
    );
}


/* =========================================================
   VENCIMIENTO DE CONTRASEÑA
========================================================= */

$fechaVencimientoTexto = 'No disponible';

$passwordVencida = false;

$diasRestantes = null;


if (!empty($fechaCambioPassword)) {

    $fechaCambio = strtotime(
        $fechaCambioPassword
    );

    $fechaVencimiento = strtotime(
        '+90 days',
        $fechaCambio
    );

    $fechaVencimientoTexto = date(
        'd/m/Y',
        $fechaVencimiento
    );

    $ahora = time();


    if ($ahora >= $fechaVencimiento) {

        $passwordVencida = true;

        $diasRestantes = 0;

    } else {

        $diferencia =
            $fechaVencimiento - $ahora;

        $diasRestantes = ceil(
            $diferencia / 86400
        );
    }
}


/* =========================================================
   ESTADO DE CONTRASEÑA
========================================================= */

if (
    $debeCambiarPassword === 1 ||
    $passwordVencida
) {

    $estadoPassword = 'Debe cambiarla';

    $clasePassword = 'danger';

    $iconoPassword =
        'fa-triangle-exclamation';

} elseif (
    $diasRestantes !== null &&
    $diasRestantes <= 15
) {

    $estadoPassword = 'Próxima a vencer';

    $clasePassword = 'warning';

    $iconoPassword = 'fa-clock';

} else {

    $estadoPassword = 'Vigente';

    $clasePassword = 'success';

    $iconoPassword =
        'fa-circle-check';
}


/* =========================================================
   INICIAL
========================================================= */

$inicial = strtoupper(
    substr(
        trim($nombre),
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

    <title>Mi Perfil - JaverStock</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS PRINCIPAL -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- CSS INVENTARIO -->

    <link
        rel="stylesheet"
        href="../assets/css/inventario.css"
    >


    <style>

        /* =====================================================
           ESTRUCTURA PRINCIPAL
        ===================================================== */

        .main-content {

            margin-left: 260px !important;

            width: calc(100% - 260px) !important;

            min-height: 100vh;

            padding: 30px !important;

        }


        /* =====================================================
           PERFIL
        ===================================================== */

        .profile-page {

            width: 100% !important;

            max-width: 1250px;

            margin: 0 auto !important;

            padding: 0 10px 25px !important;

        }


        /* =====================================================
           ENCABEZADO
        ===================================================== */

        .profile-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 16px;

        }


        .profile-title {

            margin: 0;

            font-size: 25px;

            font-weight: 700;

            color: #1f2937;

        }


        .profile-subtitle {

            margin: 4px 0 0;

            font-size: 13px;

            color: #6b7280;

        }


        /* =====================================================
           TARJETA SUPERIOR
        ===================================================== */

        .profile-cover {

            min-height: 80px;

            border-radius: 14px;

            background: linear-gradient(
                135deg,
                #f1f5f9,
                #e2e8f0
            );

            position: relative;

            margin-bottom: 48px;

        }


        /* =====================================================
           AVATAR
        ===================================================== */

        .profile-avatar {

            width: 82px;

            height: 82px;

            border-radius: 50%;

            background: #ffffff;

            border: 5px solid #ffffff;

            box-shadow:
                0 5px 15px rgba(0,0,0,0.12);

            display: flex;

            align-items: center;

            justify-content: center;

            position: absolute;

            left: 28px;

            bottom: -42px;

            font-size: 30px;

            font-weight: 700;

            color: #334155;

        }


        /* =====================================================
           NOMBRE
        ===================================================== */

        .profile-name-area {

            position: absolute;

            left: 125px;

            bottom: -38px;

        }


        .profile-name {

            font-size: 20px;

            font-weight: 700;

            color: #1f2937;

            margin: 0;

        }


        .profile-role {

            font-size: 13px;

            color: #6b7280;

            margin-top: 2px;

        }


        /* =====================================================
           COLUMNAS
        ===================================================== */

        .profile-columns {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);

            gap: 16px;

        }


        /* =====================================================
           TARJETAS
        ===================================================== */

        .profile-card {

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.05);

            overflow: hidden;

        }


        .profile-card-header {

            padding: 12px 16px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            gap: 9px;

        }


        .profile-card-header i {

            font-size: 15px;

            color: #475569;

        }


        .profile-card-header h3 {

            font-size: 15px;

            font-weight: 700;

            margin: 0;

            color: #1f2937;

        }


        .profile-card-body {

            padding: 14px 16px;

        }


        /* =====================================================
           INFORMACIÓN
        ===================================================== */

        .info-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);

            gap: 12px 20px;

        }


        .info-item {

            display: flex;

            flex-direction: column;

            gap: 3px;

            min-width: 0;

        }


        .info-label {

            font-size: 11px;

            font-weight: 600;

            color: #6b7280;

            text-transform: uppercase;

            letter-spacing: .3px;

        }


        .info-value {

            font-size: 14px;

            color: #1f2937;

            font-weight: 500;

            word-break: break-word;

        }


        /* =====================================================
           ESTADOS
        ===================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            width: fit-content;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status-success {

            background: #dcfce7;

            color: #166534;

        }


        .status-warning {

            background: #fef3c7;

            color: #92400e;

        }


        .status-danger {

            background: #fee2e2;

            color: #991b1b;

        }


        /* =====================================================
           SEGURIDAD
        ===================================================== */

        .security-box {

            padding: 11px 12px;

            border-radius: 9px;

            background: #f8fafc;

            border: 1px solid #e5e7eb;

            margin-bottom: 10px;

        }


        .security-box:last-child {

            margin-bottom: 0;

        }


        .security-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

        }


        .security-title {

            font-size: 13px;

            font-weight: 600;

            color: #374151;

        }


        .security-value {

            font-size: 12px;

            color: #6b7280;

            margin-top: 2px;

        }


        /* =====================================================
           BOTÓN
        ===================================================== */

        .profile-actions {

            margin-top: 16px;

            display: flex;

            justify-content: flex-end;

        }


        .btn-password {

            border: none;

            background: #1f2937;

            color: #ffffff;

            padding: 9px 15px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 7px;

            transition: .2s;

        }


        .btn-password:hover {

            background: #111827;

            color: #ffffff;

            transform: translateY(-1px);

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1200px) {

            .main-content {

                margin-left: 220px !important;

                width:
                    calc(100% - 220px) !important;

            }

        }


        @media (max-width: 992px) {

            .main-content {

                margin-left: 80px !important;

                width:
                    calc(100% - 80px) !important;

                padding: 20px !important;

            }

        }


        @media (max-width: 900px) {

            .profile-columns {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 768px) {

            .main-content {

                margin-left: 80px !important;

                width:
                    calc(100% - 80px) !important;

                padding: 15px !important;

            }

        }


        @media (max-width: 600px) {

            .main-content {

                margin-left: 0 !important;

                width: 100% !important;

                padding: 15px !important;

            }


            .profile-page {

                padding: 0 !important;

            }


            .profile-header {

                align-items: flex-start;

            }


            .profile-title {

                font-size: 21px;

            }


            .profile-cover {

                margin-bottom: 58px;

            }


            .profile-avatar {

                left: 18px;

            }


            .profile-name-area {

                left: 112px;

                bottom: -48px;

            }


            .profile-name {

                font-size: 17px;

            }


            .info-grid {

                grid-template-columns: 1fr;

            }


            .security-row {

                flex-direction: column;

                align-items: flex-start;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php include "../includes/sidebar.php"; ?>


<!-- =====================================================
     CONTENIDO PRINCIPAL
===================================================== -->

<div class="main-content">


    <!-- HEADER -->

    <?php include "../includes/header.php"; ?>


    <!-- PERFIL -->

    <main class="profile-page">


        <!-- =================================================
             TÍTULO
        ================================================= -->

        <div class="profile-header">

            <div>

                <h1 class="profile-title">
                    Mi Perfil
                </h1>

                <p class="profile-subtitle">
                    Información de tu cuenta y seguridad
                </p>

            </div>

        </div>


        <!-- =================================================
             PERFIL SUPERIOR
        ================================================= -->

        <div class="profile-cover">


            <div class="profile-avatar">

                <?= htmlspecialchars(
                    $inicial,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>


            <div class="profile-name-area">

                <h2 class="profile-name">

                    <?= htmlspecialchars(
                        $nombre,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </h2>


                <div class="profile-role">

                    <?= htmlspecialchars(
                        ucfirst($rol),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>

            </div>


        </div>


        <!-- =================================================
             COLUMNAS
        ================================================= -->

        <div class="profile-columns">


            <!-- =================================================
                 INFORMACIÓN PERSONAL
            ================================================= -->

            <section class="profile-card">


                <div class="profile-card-header">

                    <i class="fas fa-user"></i>

                    <h3>
                        Información personal
                    </h3>

                </div>


                <div class="profile-card-body">


                    <div class="info-grid">


                        <!-- NOMBRE -->

                        <div class="info-item">

                            <span class="info-label">
                                Nombre completo
                            </span>

                            <span class="info-value">

                                <?= htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>


                        <!-- USUARIO -->

                        <div class="info-item">

                            <span class="info-label">
                                Usuario
                            </span>

                            <span class="info-value">

                                <?= htmlspecialchars(
                                    $nombreUsuario,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>


                        <!-- ROL -->

                        <div class="info-item">

                            <span class="info-label">
                                Rol
                            </span>

                            <span class="info-value">

                                <?= htmlspecialchars(
                                    ucfirst($rol),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>


                        <!-- CORREO -->

                        <div class="info-item">

                            <span class="info-label">
                                Correo electrónico
                            </span>

                            <span class="info-value">

                                <?php if (!empty($correo)): ?>

                                    <?= htmlspecialchars(
                                        $correo,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                <?php else: ?>

                                    No registrado

                                <?php endif; ?>

                            </span>

                        </div>


                        <!-- ESTADO -->

                        <div class="info-item">

                            <span class="info-label">
                                Estado de cuenta
                            </span>

                            <span class="status-badge status-success">

                                <i class="fas fa-circle-check"></i>

                                Activa

                            </span>

                        </div>


                    </div>


                </div>


            </section>


            <!-- =================================================
                 SEGURIDAD
            ================================================= -->

            <section class="profile-card">


                <div class="profile-card-header">

                    <i class="fas fa-shield-halved"></i>

                    <h3>
                        Seguridad
                    </h3>

                </div>


                <div class="profile-card-body">


                    <!-- ÚLTIMO CAMBIO -->

                    <div class="security-box">

                        <div class="security-row">

                            <div>

                                <div class="security-title">

                                    Último cambio de contraseña

                                </div>

                                <div class="security-value">

                                    <?= htmlspecialchars(
                                        $fechaCambioTexto,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- VENCIMIENTO -->

                    <div class="security-box">

                        <div class="security-row">

                            <div>

                                <div class="security-title">

                                    Vencimiento de contraseña

                                </div>

                                <div class="security-value">

                                    <?= htmlspecialchars(
                                        $fechaVencimientoTexto,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </div>

                            </div>


                            <?php if ($passwordVencida): ?>


                                <span class="status-badge status-danger">

                                    <i class="fas fa-triangle-exclamation"></i>

                                    Vencida

                                </span>


                            <?php elseif (
                                $diasRestantes !== null &&
                                $diasRestantes <= 15
                            ): ?>


                                <span class="status-badge status-warning">

                                    <i class="fas fa-clock"></i>

                                    <?= $diasRestantes; ?> días

                                </span>


                            <?php else: ?>


                                <span class="status-badge status-success">

                                    <i class="fas fa-circle-check"></i>

                                    <?= $diasRestantes ?? 0; ?> días

                                </span>


                            <?php endif; ?>


                        </div>

                    </div>


                    <!-- ESTADO -->

                    <div class="security-box">

                        <div class="security-row">

                            <div>

                                <div class="security-title">

                                    Estado de contraseña

                                </div>

                            </div>


                            <span class="status-badge status-<?= $clasePassword; ?>">

                                <i class="fas <?= $iconoPassword; ?>"></i>

                                <?= htmlspecialchars(
                                    $estadoPassword,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>


                        </div>

                    </div>


                </div>


            </section>


        </div>


        <!-- =================================================
             ACCIONES
        ================================================= -->

        <div class="profile-actions">

            <a
                href="cambiar_password.php"
                class="btn-password"
            >

                <i class="fas fa-key"></i>

                Cambiar contraseña

            </a>

        </div>


    </main>


</div>


<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>