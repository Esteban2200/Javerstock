<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header("Location: pages/dashboard.php");
    exit();
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

    <title>JaverStock | Iniciar Sesión</title>


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


    <!-- CSS -->
    <link
        rel="stylesheet"
        href="assets/css/login.css?v=50"
    >

</head>


<body>


<div class="login-container">


    <!-- =====================================================
         LADO IZQUIERDO
    ====================================================== -->

    <div class="login-left">


        <!-- Imagen de fondo -->

        <div class="login-background"></div>


        <!-- Capa azul -->

        <div class="login-overlay"></div>


        <!-- Contenido -->

        <div class="brand-content">


            <!-- =================================================
                 LOGO HORIZONTAL DE LA JAVERIANA
            ================================================== -->

            <div class="horizontal-logo">

                <img
                    src="assets/img/logo-javeriana-horizontal.png"
                    alt="Pontificia Universidad Javeriana Bogotá"
                >

            </div>


            <!-- =================================================
                 JAVERSTOCK
            ================================================== -->

            <h1>
                JaverStock
            </h1>


            <div class="gold-line"></div>


            <p class="brand-subtitle">

                Sistema Inteligente de Gestión de Inventario

            </p>


            <p class="brand-description">

                Una solución inteligente para optimizar,
                controlar y gestionar los recursos de la
                Universidad de manera eficiente.

            </p>


            <!-- =================================================
                 CARACTERÍSTICAS
            ================================================== -->

            <div class="features">


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fas fa-boxes-stacked"></i>

                    </div>

                    <div>

                        <strong>
                            Control del inventario
                        </strong>

                        <span>
                            Gestión organizada de recursos
                        </span>

                    </div>

                </div>


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fas fa-chart-line"></i>

                    </div>

                    <div>

                        <strong>
                            Información en tiempo real
                        </strong>

                        <span>
                            Consulta y seguimiento eficiente
                        </span>

                    </div>

                </div>


                <div class="feature">

                    <div class="feature-icon">

                        <i class="fas fa-shield-halved"></i>

                    </div>

                    <div>

                        <strong>
                            Gestión segura
                        </strong>

                        <span>
                            Información protegida y centralizada
                        </span>

                    </div>

                </div>


            </div>


        </div>


        <!-- Pie -->

        <div class="left-footer">

            Pontificia Universidad Javeriana · Bogotá

        </div>


    </div>



    <!-- =====================================================
         LADO DERECHO
    ====================================================== -->

    <div class="login-right">


        <!-- Decoraciones -->

        <div class="decoration decoration-one"></div>

        <div class="decoration decoration-two"></div>


        <!-- =================================================
             TARJETA
        ================================================== -->

        <div class="login-box">


            <!-- =================================================
                 LOGO AZUL
            ================================================== -->

            <div class="form-logo">

                <img
                    src="assets/img/logo-javeriana.png"
                    alt="Pontificia Universidad Javeriana"
                >

            </div>


            <!-- =================================================
                 TÍTULO
            ================================================== -->

            <h2>
                Iniciar Sesión
            </h2>


            <p class="welcome">
                Bienvenido nuevamente
            </p>


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if (isset($_SESSION['error_login'])): ?>

                <div class="login-alert">

                    <i class="fas fa-triangle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($_SESSION['error_login']); ?>
                    </span>

                </div>

                <?php unset($_SESSION['error_login']); ?>

            <?php endif; ?>


            <!-- =================================================
                 FORMULARIO
            ================================================== -->

            <form
                action="actions/login/validar_login.php"
                method="POST"
            >


                <!-- USUARIO -->

                <div class="form-group">

                    <label for="usuario">
                        Usuario
                    </label>


                    <div class="input-wrapper">

                        <div class="input-icon">

                            <i class="fas fa-user"></i>

                        </div>


                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingresa tu usuario"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>



                <!-- CONTRASEÑA -->

                <div class="form-group">

                    <label for="password">
                        Contraseña
                    </label>


                    <div class="input-wrapper">

                        <div class="input-icon">

                            <i class="fas fa-lock"></i>

                        </div>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            id="mostrarPassword"
                            class="password-button"
                            aria-label="Mostrar contraseña"
                        >

                            <i class="fas fa-eye"></i>

                        </button>

                    </div>

                </div>



                <!-- =================================================
                     BOTÓN
                ================================================== -->

                <button
                    type="submit"
                    class="btn-login"
                >

                    <i class="fas fa-right-to-bracket"></i>

                    Ingresar

                </button>


            </form>


            <!-- =================================================
                 SEGURIDAD
            ================================================== -->

            <div class="secure-login">

                <i class="fas fa-lock"></i>

                Acceso seguro al sistema

            </div>


        </div>


        <!-- =================================================
             TEXTO INFERIOR
        ================================================== -->

        <div class="right-footer">

            <span></span>

            <div>

                <strong>
                    JAVERSTOCK
                </strong>

                <small>
                    Gestión inteligente de inventario
                </small>

            </div>

            <span></span>

        </div>


    </div>

</div>



<script src="assets/js/login.js"></script>

</body>

</html>