<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/permisos.php";


// ---------------------------------------------------------
// PERMISO
// ---------------------------------------------------------

exigirPermiso('ver_inventario');

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>JaverStock | Configuración</title>

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

    <!-- CSS general -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        body {
            background: #f4f7fb;
        }

        .configuracion-page {
            padding-bottom: 60px;
        }

        .configuracion-header {
            margin-bottom: 30px;
        }

        .configuracion-header h2 {
            margin: 0;
            color: #002855;
            font-size: 32px;
            font-weight: 800;
        }

        .configuracion-header p {
            margin-top: 7px;
            color: #64748b;
        }


        /* =====================================================
           GRID
        ====================================================== */

        .configuracion-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(280px, 1fr)
                );

            gap: 22px;
        }


        /* =====================================================
           TARJETAS
        ====================================================== */

        .configuracion-card {

            position: relative;

            display: flex;

            flex-direction: column;

            background: #fff;

            border: 1px solid #e5ebf2;

            border-radius: 20px;

            padding: 26px;

            text-decoration: none;

            color: #334155;

            box-shadow:
                0 8px 25px rgba(0,0,0,.06);

            overflow: hidden;

            transition:
                transform .22s ease,
                box-shadow .22s ease,
                border-color .22s ease;
        }


        .configuracion-card::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;
            height: 5px;

            background:
                linear-gradient(
                    90deg,
                    #002855,
                    #1677c8
                );
        }


        .configuracion-card:hover {

            transform:
                translateY(-6px);

            border-color:
                #cbd8e6;

            box-shadow:
                0 16px 35px
                rgba(0,40,85,.12);

            color: #334155;
        }


        /* =====================================================
           ICONO
        ====================================================== */

        .configuracion-icono {

            width: 64px;
            height: 64px;

            border-radius: 17px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eaf3fc;

            color: #002855;

            font-size: 27px;

            margin-bottom: 20px;
        }


        /* =====================================================
           TEXTO
        ====================================================== */

        .configuracion-card h4 {

            color: #002855;

            font-size: 19px;

            font-weight: 800;

            margin-bottom: 9px;
        }


        .configuracion-card p {

            margin: 0 0 22px;

            color: #64748b;

            font-size: 14px;

            line-height: 1.5;
        }


        /* =====================================================
           FOOTER CARD
        ====================================================== */

        .configuracion-card-footer {

            margin-top: auto;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .configuracion-card-footer span {

            color: #0056a6;

            font-size: 13px;

            font-weight: 800;
        }


        .configuracion-card-footer i {

            color: #0056a6;

            transition:
                transform .2s ease;
        }


        .configuracion-card:hover
        .configuracion-card-footer i {

            transform:
                translateX(5px);
        }


        /* =====================================================
           REGRESAR
        ====================================================== */

        .btn-volver-dashboard {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-top: 28px;

            padding: 10px 16px;

            border-radius: 10px;

            background: #fff;

            border: 1px solid #dbe3ec;

            color: #002855;

            text-decoration: none;

            font-weight: 700;

            transition: .2s ease;
        }


        .btn-volver-dashboard:hover {

            background: #eef5fc;

            color: #002855;
        }


        @media(max-width: 768px) {

            .configuracion-header h2 {
                font-size: 26px;
            }

        }

    </style>

</head>


<body>


<div class="main configuracion-page">

<?php include("../includes/sidebar.php"); ?>
    <?php include("../includes/header.php"); ?>


    <div class="content">


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="configuracion-header">

            <h2>

                <i class="fas fa-gear"></i>

                Configuración

            </h2>


            <p>

                Administra las opciones generales
                del sistema JaverStock.

            </p>

        </div>


        <!-- =====================================================
             TARJETAS
        ====================================================== -->

        <div class="configuracion-grid">


            <!-- USUARIOS -->

            <a
                href="gestion_usuarios.php"
                class="configuracion-card"
            >

                <div class="configuracion-icono">

                    <i class="fas fa-users"></i>

                </div>


                <h4>

                    Usuarios y permisos

                </h4>


                <p>

                    Administra usuarios, roles y
                    permisos de acceso al sistema.

                </p>


                <div
                    class="configuracion-card-footer"
                >

                    <span>
                        Administrar
                    </span>

                    <i
                        class="fas fa-arrow-right"
                    ></i>

                </div>

            </a>


            <!-- CATEGORÍAS -->

            <a
                href="categorias.php"
                class="configuracion-card"
            >

                <div class="configuracion-icono">

                    <i class="fas fa-tags"></i>

                </div>


                <h4>

                    Categorías

                </h4>


                <p>

                    Crea, modifica y administra
                    las categorías de inventario.

                </p>


                <div
                    class="configuracion-card-footer"
                >

                    <span>
                        Administrar
                    </span>

                    <i
                        class="fas fa-arrow-right"
                    ></i>

                </div>

            </a>


            <!-- UBICACIONES -->

            <a
                href="ubicaciones.php"
                class="configuracion-card"
            >

                <div class="configuracion-icono">

                    <i class="fas fa-building"></i>

                </div>


                <h4>

                    Ubicaciones

                </h4>


                <p>

                    Administra edificios,
                    pisos y ubicaciones utilizadas
                    por los equipos.

                </p>


                <div
                    class="configuracion-card-footer"
                >

                    <span>
                        Administrar
                    </span>

                    <i
                        class="fas fa-arrow-right"
                    ></i>

                </div>

            </a>


            <!-- PUNTOS DE RED -->

            <a
                href="puntos_red.php"
                class="configuracion-card"
            >

                <div class="configuracion-icono">

                    <i class="fas fa-network-wired"></i>

                </div>


                <h4>

                    Puntos de Red

                </h4>


                <p>

                    Consulta y administra los puntos
                    de red de Audiovisuales y
                    Automatización.

                </p>


                <div
                    class="configuracion-card-footer"
                >

                    <span>
                        Administrar
                    </span>

                    <i
                        class="fas fa-arrow-right"
                    ></i>

                </div>

            </a>


        </div>


        <!-- VOLVER -->

        <a
            href="dashboard.php"
            class="btn-volver-dashboard"
        >

            <i class="fas fa-arrow-left"></i>

            Volver al Dashboard

        </a>


    </div>

</div>


<?php include("../includes/footer.php"); ?>


</body>

</html>