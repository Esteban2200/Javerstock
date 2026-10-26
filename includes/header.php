<?php
/* =========================================================
   JAVERSTOCK - HEADER INSTITUCIONAL
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   PÁGINA ACTUAL
========================================================= */

$paginaActual = basename($_SERVER['PHP_SELF']);

$esDashboard =
    ($paginaActual === 'dashboard.php');


/* =========================================================
   USUARIO
========================================================= */

$nombreUsuario =
    $_SESSION['nombre']
    ?? $_SESSION['usuario_nombre']
    ?? $_SESSION['usuario']
    ?? 'Usuario';

$rolUsuario =
    $_SESSION['rol']
    ?? 'Usuario';


/* =========================================================
   INICIAL
========================================================= */

$inicial = strtoupper(
    mb_substr(
        trim($nombreUsuario),
        0,
        1,
        'UTF-8'
    )
);


/* =========================================================
   SALUDO
========================================================= */

$hora = (int) date('H');

if ($hora >= 5 && $hora < 12) {

    $saludoHeader = 'Buenos días';

} elseif ($hora >= 12 && $hora < 19) {

    $saludoHeader = 'Buenas tardes';

} else {

    $saludoHeader = 'Buenas noches';

}

?>


<!-- =========================================================
     FUENTES
========================================================= -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,500;1,600&display=swap"
    rel="stylesheet"
>


<!-- =========================================================
     BOOTSTRAP 5
========================================================= -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- =========================================================
     FONT AWESOME
========================================================= -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* =========================================================
   HEADER GENERAL
========================================================= */

.topbar {

    position: relative;

    width: 100%;

    box-sizing: border-box;

}


/* =========================================================
   HEADER DASHBOARD
========================================================= */

.topbar.topbar-dashboard {

    position: relative;

    width: 100%;

    height: 210px;

    min-height: 210px;

    padding: 0;

    overflow: hidden;

    color: #ffffff;

    background: #062e5b;

    border-top:
        4px solid #f5c400;

    box-shadow:
        0 5px 25px
        rgba(0,40,85,.18);

    isolation: isolate;
}


/* =========================================================
   FONDO
========================================================= */

.topbar-background {

    position: absolute;

    inset: 0;

    background-image:
        url("../img/fondo-navbar.jpg");

    background-size: cover;

    background-position: center center;

    background-repeat: no-repeat;

    transform:
        scale(1.02);

    z-index: 0;
}


/* =========================================================
   OVERLAY
========================================================= */

.topbar-overlay {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(0,40,85,.94) 0%,
            rgba(0,40,85,.84) 35%,
            rgba(0,40,85,.68) 68%,
            rgba(0,40,85,.76) 100%
        );

    z-index: 1;
}


/* =========================================================
   CONTENIDO
========================================================= */

.topbar-content {

    position: relative;

    z-index: 5;

    background:  #031c6dd8;;

    width: 100%;

    height: 100%;

    padding:
        18px 38px 20px;

    display: flex;

    flex-direction: column;

    box-sizing: border-box;
}


/* =========================================================
   FILA SUPERIOR
========================================================= */

.topbar-row {

    position: relative;

    z-index: 10;

    width: 100%;

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 25px;
}


/* =========================================================
   FRASE INSTITUCIONAL
========================================================= */

.topbar-institucional {

    display: flex;

    flex-direction: column;

    align-items: flex-start;
    

    font-family:
        Georgia,
        "Times New Roman",
        serif;

    font-style: italic;

    line-height: 1.05;
}


.institucional-linea {

    color: #ffffff;

    font-size: 20px;

    font-weight: 700;

    letter-spacing: -.2px;

    text-shadow:
        0 2px 8px
        rgba(0,0,0,.30);
}


.institucional-dorado {

    display: block;

    width: 56px;

    height: 3px;

    margin-top: 9px;

    border-radius: 10px;

    background:
        #f5c400;

    box-shadow:
        0 2px 8px
        rgba(245,196,0,.25);
}


/* =========================================================
   PARTE DERECHA
========================================================= */

.topbar-right {

    position: relative;

    z-index: 20;

    display: flex;

    align-items: center;

    gap: 10px;
}


/* =========================================================
   BOTONES CIRCULARES
========================================================= */

.topbar-icon {

    width: 44px;
    height: 44px;

    flex: 0 0 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid
        rgba(255,255,255,.35);

    border-radius: 50%;

    color:
        #062e5b;

    background:
        rgba(255,255,255,.94);

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.15);

    cursor: pointer;

    transition:
        transform .2s ease,
        background .2s ease,
        box-shadow .2s ease;
}


.topbar-icon i {

    font-size: 17px;
}


.topbar-icon:hover {

    transform:
        translateY(-2px);

    background:
        #f5c400;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.20);
}


/* =========================================================
   USUARIO
========================================================= */

.topbar-user {

    min-height: 52px;

    padding:
        6px 12px 6px 7px;

    display: flex;

    align-items: center;

    gap: 10px;

    border:
        1px solid
        rgba(255,255,255,.35);

    border-radius: 30px;

    background:
        rgba(255,255,255,.96);

    color:
        #062e5b;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.16);

    backdrop-filter:
        blur(10px);

    -webkit-backdrop-filter:
        blur(10px);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.topbar-user:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 22px
        rgba(0,0,0,.20);
}


/* =========================================================
   AVATAR
========================================================= */

.topbar-avatar {

    width: 40px;
    height: 40px;

    flex: 0 0 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        #f5c400;

    color:
        #062e5b;

    font-size: 16px;

    font-weight: 800;

    box-shadow:
        0 2px 7px
        rgba(0,0,0,.12);
}


/* =========================================================
   INFORMACIÓN USUARIO
========================================================= */

.topbar-user-info {

    display: flex;

    flex-direction: column;

    min-width: 90px;
}


/* NOMBRE */

.topbar-user-name {

    color:
        #062e5b;

    font-size: 14px;

    font-weight: 800;

    line-height: 1.1;
}


/* ROL */

.topbar-user-role {

    margin-top: 3px;

    color:
        #7a8795;

    font-size: 11px;

    font-weight: 500;

    line-height: 1.1;
}


/* =========================================================
   BOTÓN DROPDOWN
========================================================= */

.topbar-dropdown-button {

    border: 0;

    background:
        transparent;

    width: 18px;

    height: 30px;

    padding: 0;

    color:
        #062e5b;

    cursor: pointer;

    box-shadow: none;
}


.topbar-dropdown-button::after {

    margin-left: 0;

}


/* =========================================================
   DROPDOWN
========================================================= */

.topbar-user .dropdown-menu {

    min-width: 220px;

    margin-top: 10px !important;

    padding: 8px;

    border: 0;

    border-radius: 16px;

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.15);
}


.topbar-user .dropdown-item {

    padding:
        10px 12px;

    border-radius: 9px;

    color:
        #062e5b;

    font-size: 14px;

    transition:
        .2s ease;
}


.topbar-user .dropdown-item:hover {

    background:
        #f1f5f9;

    color:
        #062e5b;
}


.topbar-user .dropdown-divider {

    margin:
        7px 0;
}


/* =========================================================
   BIENVENIDA
========================================================= */

.topbar-welcome {

    position: relative;

    z-index: 10;

    margin-top: auto;

    max-width: 700px;

    padding-bottom: 0;
}


/* =========================================================
   SALUDO
========================================================= */

.topbar-welcome-small {

    margin-bottom: 6px;

    color:
        rgba(255,255,255,.95);

    font-size: 13px;

    font-weight: 500;

    line-height: 1.2;

    text-shadow:
        0 2px 7px
        rgba(0,0,0,.30);
}


/* =========================================================
   TÍTULO
========================================================= */

.topbar-welcome h1 {

    margin: 0;

    color:
        #ffffff;

    font-size: 36px;

    line-height: 1.05;

    font-weight: 800;

    letter-spacing: -.7px;

    text-shadow:
        0 3px 12px
        rgba(0,0,0,.35);
}


/* =========================================================
   SUBTÍTULO
========================================================= */

.topbar-welcome h2 {

    position: relative;

    margin:
        6px 0 0;

    color:
        #f5c400;

    font-size: 17px;

    line-height: 1.2;

    font-weight: 650;

    text-shadow:
        0 2px 8px
        rgba(0,0,0,.30);
}


.topbar-welcome h2::after {

    content: "";

    display: block;

    width: 52px;

    height: 3px;

    margin-top: 9px;

    border-radius: 10px;

    background:
        #f5c400;

    box-shadow:
        0 2px 8px
        rgba(245,196,0,.30);
}


/* =========================================================
   DESCRIPCIÓN
========================================================= */

.topbar-welcome p {

    margin:
        7px 0 0;

    max-width: 600px;

    color:
        rgba(255,255,255,.88);

    font-size: 12px;

    line-height: 1.4;

    text-shadow:
        0 2px 7px
        rgba(0,0,0,.30);
}


/* =========================================================
   DECORACIONES
========================================================= */

.topbar.topbar-dashboard::before {

    content: "";

    position: absolute;

    width: 270px;
    height: 270px;

    top: -175px;

    right: 130px;

    border:
        2px solid
        rgba(255,255,255,.10);

    border-radius: 50%;

    z-index: 2;

    pointer-events: none;

    animation:
        movimientoCirculo
        7s ease-in-out infinite;
}


.topbar.topbar-dashboard::after {

    content: "";

    position: absolute;

    width: 390px;
    height: 390px;

    right: -145px;

    bottom: -305px;

    border:
        2px solid
        rgba(245,196,0,.14);

    border-radius: 50%;

    z-index: 2;

    pointer-events: none;
}


.topbar-content::before {

    content: "";

    position: absolute;

    width: 125px;
    height: 125px;

    right: 320px;

    top: 75px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid
        rgba(255,255,255,.08);

    z-index: 2;

    pointer-events: none;
}


.topbar-content::after {

    content: "";

    position: absolute;

    width: 260px;
    height: 260px;

    right: 25px;

    top: -120px;

    border:
        1px solid
        rgba(255,255,255,.08);

    transform:
        rotate(35deg);

    z-index: 2;

    pointer-events: none;
}


/* =========================================================
   PUNTOS
========================================================= */

.topbar-welcome::before {

    content: "";

    position: absolute;

    width: 6px;
    height: 6px;

    left: -18px;

    top: 22px;

    border-radius: 50%;

    background:
        #f5c400;

    box-shadow:
        28px 38px 0
        rgba(255,255,255,.22),

        65px -15px 0
        rgba(255,255,255,.12),

        105px 52px 0
        rgba(245,196,0,.15);

    z-index: 3;

    pointer-events: none;
}


/* =========================================================
   LÍNEA SUPERIOR
========================================================= */

.topbar-row::after {

    content: "";

    position: absolute;

    right: 0;

    top: -14px;

    width: 85px;

    height: 3px;

    border-radius: 10px;

    background:
        #f5c400;

    opacity: .9;
}


/* =========================================================
   ANIMACIÓN
========================================================= */

@keyframes movimientoCirculo {

    0% {
        transform:
            translate3d(0,0,0);
    }

    50% {
        transform:
            translate3d(0,6px,0);
    }

    100% {
        transform:
            translate3d(0,0,0);
    }

}


/* =========================================================
   HEADER OTRAS PÁGINAS
========================================================= */

.topbar:not(.topbar-dashboard) {

    position: relative;

    min-height: 75px;

    display: flex;

    align-items: center;

    background:
        #ffffff;

    border-bottom:
        1px solid #e5e9ef;

    box-shadow:
        0 2px 10px
        rgba(0,40,85,.08);

    z-index: 100;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .topbar-content {

        padding-left: 30px;

        padding-right: 30px;
    }

    .topbar-welcome h1 {

        font-size: 33px;
    }

    .topbar-institucional
    .institucional-linea {

        font-size: 18px;
    }

}


@media (max-width: 900px) {

    .topbar.topbar-dashboard {

        height: 200px;

        min-height: 200px;
    }

    .topbar-content {

        padding:
            16px 24px 18px;
    }

    .topbar-user-info {

        display: none;
    }

    .topbar-user {

        padding-right: 8px;
    }

    .topbar-welcome h1 {

        font-size: 30px;
    }

    .topbar-welcome h2 {

        font-size: 16px;
    }

}


@media (max-width: 768px) {

    .topbar.topbar-dashboard {

        height: 190px;

        min-height: 190px;
    }

    .topbar-content {

        padding:
            14px 18px 16px;
    }

    .topbar-row {

        gap: 10px;
    }

    .topbar-institucional
    .institucional-linea {

        font-size: 16px;
    }

    .institucional-dorado {

        width: 45px;

        height: 2px;
    }

    .topbar-right {

        gap: 6px;
    }

    .topbar-icon {

        width: 38px;
        height: 38px;

        flex-basis: 38px;
    }

    .topbar-user {

        min-height: 44px;

        padding:
            4px 7px 4px 5px;
    }

    .topbar-avatar {

        width: 34px;
        height: 34px;

        flex-basis: 34px;
    }

    .topbar-welcome h1 {

        font-size: 26px;

        letter-spacing: -.4px;
    }

    .topbar-welcome h2 {

        font-size: 14px;
    }

    .topbar-welcome-small {

        font-size: 12px;
    }

}


@media (max-width: 520px) {

    .topbar.topbar-dashboard {

        height: 180px;

        min-height: 180px;
    }

    .topbar-institucional {

        max-width: 150px;
    }

    .topbar-institucional
    .institucional-linea {

        font-size: 14px;
    }

    .topbar-icon {

        width: 35px;
        height: 35px;

        flex-basis: 35px;
    }

    .topbar-icon i {

        font-size: 14px;
    }

    .topbar-user {

        border-radius: 50%;

        width: 35px;
        height: 35px;

        padding: 0;

        justify-content: center;
    }

    .topbar-user
    .topbar-avatar {

        width: 35px;
        height: 35px;

        flex-basis: 35px;
    }

    .topbar-user
    .topbar-dropdown-button {

        display: none;
    }

    .topbar-welcome h1 {

        font-size: 23px;
    }

    .topbar-welcome h2 {

        font-size: 13px;
    }

    .topbar-welcome p {

        display: none;
    }

}

</style>


<!-- =========================================================
     HEADER
========================================================= -->

<header
    class="topbar <?= $esDashboard ? 'topbar-dashboard' : ''; ?>"
>


    <?php if ($esDashboard): ?>

        <!-- ================================================
             FONDO DASHBOARD
        ================================================= -->

        <div class="topbar-background"></div>

        <div class="topbar-overlay"></div>

    <?php endif; ?>


    <div class="topbar-content">


        <!-- ================================================
             FILA SUPERIOR
        ================================================= -->

        <div class="topbar-row">


            <!-- FRASE -->

            <div class="topbar-left">

                <div class="topbar-institucional">

                    <span class="institucional-linea">
                        Más tecnología
                    </span>

                    <span class="institucional-linea">
                        para una mejor Universidad
                    </span>

                    <span class="institucional-dorado"></span>

                </div>

            </div>


            <!-- ============================================
                 CONTROLES
            ============================================= -->

            <div class="topbar-right">


                <!-- NOTIFICACIONES -->

                <button
                    type="button"
                    class="topbar-icon"
                    title="Notificaciones"
                >

                    <i class="fas fa-bell"></i>

                </button>


                <!-- CONFIGURACIÓN -->

                <button
                    type="button"
                    class="topbar-icon"
                    title="Configuración"
                >

                    <i class="fas fa-gear"></i>

                </button>


                <!-- ========================================
                     USUARIO
                ========================================= -->

                <div
                    class="dropdown topbar-user"
                >


                    <!-- AVATAR -->

                    <div class="topbar-avatar">

                        <?= htmlspecialchars($inicial); ?>

                    </div>


                    <!-- INFORMACIÓN -->

                    <div class="topbar-user-info">

                        <span class="topbar-user-name">

                            <?= htmlspecialchars($nombreUsuario); ?>

                        </span>

                        <span class="topbar-user-role">

                            <?= htmlspecialchars($rolUsuario); ?>

                        </span>

                    </div>


                    <!-- BOTÓN DROPDOWN -->

                    <button
                        type="button"
                        class="dropdown-toggle topbar-dropdown-button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Menú de usuario"
                    >
                    </button>


                    <!-- ====================================
                         MENÚ USUARIO
                    ===================================== -->

                    <ul
                        class="dropdown-menu dropdown-menu-end"
                    >

                        <li>

                            <a
                                class="dropdown-item"
                                href="../pages/perfil.php"
                            >

                                <i
                                    class="fas fa-user me-2"
                                ></i>

                                Mi Perfil

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="../pages/cambiar_password.php"
                            >

                                <i
                                    class="fas fa-lock me-2"
                                ></i>

                                Cambiar contraseña

                            </a>

                        </li>


                        <li>

                            <hr
                                class="dropdown-divider"
                            >

                        </li>


                        <li>

                            <a
                                class="dropdown-item text-danger"
                                href="../logout.php"
                            >

                                <i
                                    class="fas fa-right-from-bracket me-2"
                                ></i>

                                Cerrar sesión

                            </a>

                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <?php if ($esDashboard): ?>

            <!-- ============================================
                 BIENVENIDA
            ============================================= -->

            <div class="topbar-welcome">


                <div class="topbar-welcome-small">

                    <?= htmlspecialchars($saludoHeader); ?>,

                    <strong>
                        <?= htmlspecialchars($nombreUsuario); ?>
                    </strong>

                    <span class="wave">
                        👋
                    </span>

                </div>


                <h1>
                    Bienvenido a JaverStock
                </h1>


                <h2>
                    Sistema de Gestión de Inventario
                </h2>


                <p>
                    Gestiona, controla y optimiza los recursos
                    de la Universidad de manera eficiente y segura.
                </p>


            </div>

        <?php endif; ?>


    </div>

</header>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>