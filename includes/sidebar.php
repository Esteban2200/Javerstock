<?php
/* =========================================================
   JAVERSTOCK - SIDEBAR
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/permisos.php';

/* =========================================================
   PÁGINA ACTUAL
   ========================================================= */

$paginaActual = basename($_SERVER['PHP_SELF']);

/* =========================================================
   INVENTARIO
   ========================================================= */

$esInventarioGeneral = ($paginaActual === 'inventario.php');
$esInventarioTecnico = ($paginaActual === 'inventario_tecnico.php');

$enInventario = (
    $esInventarioGeneral ||
    $esInventarioTecnico
);

/* =========================================================
   FUNCIÓN MENÚ ACTIVO
   ========================================================= */

if (!function_exists('menuActivo')) {

    function menuActivo($pagina)
    {
        global $paginaActual;

        return ($paginaActual === $pagina) ? 'active' : '';
    }
}
?>

<style>

/* =========================================================
   VARIABLES
========================================================= */

:root {
    --sidebar-bg: #03285a;
    --sidebar-bg-dark: #021d42;

    --blue: #1683ff;
    --blue-light: #2e9cff;

    --yellow: #f2bd00;
    --yellow-light: #ffc928;

    --green: #19d3b5;
    --cyan: #10bde8;

    --purple: #7758ff;
    --pink: #ff3e78;
    --orange: #f6a900;

    --white: #ffffff;
    --text-muted: #9dbce0;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: 264px;
    height: 100vh;

    background:
        radial-gradient(
            circle at 0% 0%,
            rgba(25, 121, 255, .22),
            transparent 32%
        ),
        linear-gradient(
            180deg,
            #062f66 0%,
            #03275a 50%,
            #021d42 100%
        );

    color: #ffffff;

    overflow-y: auto;
    overflow-x: hidden;

    z-index: 2000;

    box-shadow:
        5px 0 25px rgba(0, 20, 50, .18);

    box-sizing: border-box;
}


/* =========================================================
   SCROLLBAR
========================================================= */

.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.20);
    border-radius: 20px;
}


/* =========================================================
   IMPORTANTE:
   ÁREA PRINCIPAL DE TODAS LAS PÁGINAS
========================================================= */

.main {

    margin-left: 264px !important;

    width: calc(100% - 264px) !important;

    min-height: 100vh;

    position: relative;

    box-sizing: border-box;

}


/* =========================================================
   LOGO
========================================================= */

.sidebar-brand {

    width: 100%;

    min-height: 122px;

    padding: 20px;

    display: flex;

    justify-content: center;

    align-items: center;

    border-bottom:
        1px solid rgba(255,255,255,.10);

    position: relative;

    box-sizing: border-box;
}


/* línea amarilla */

.sidebar-brand::after {

    content: "";

    position: absolute;

    left: 0;

    bottom: -1px;

    width: 72%;

    height: 3px;

    background:
        linear-gradient(
            90deg,
            #ffc400,
            #f0b900,
            transparent
        );
}


/* Logo */

.sidebar-logo {

    width: 210px;

    max-width: 100%;

    height: auto;

    display: block;

    object-fit: contain;
}


/* =========================================================
   MENÚ
========================================================= */

.sidebar-menu {

    list-style: none;

    padding: 18px 14px 25px;

    margin: 0;

    box-sizing: border-box;
}


/* =========================================================
   SECCIONES
========================================================= */

.menu-section {

    display: flex;

    align-items: center;

    gap: 9px;

    margin: 16px 12px 8px;

    color: #66aeea;

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;
}

.menu-section:first-child {
    margin-top: 2px;
}


/* círculo */

.menu-section-dot {

    width: 10px;
    height: 10px;

    min-width: 10px;

    border-radius: 50%;

    background: var(--blue);

    box-shadow:
        0 0 8px rgba(22,131,255,.65);
}


/* línea */

.menu-section-line {

    flex: 1;

    height: 1px;

    background:
        linear-gradient(
            90deg,
            rgba(22,131,255,.65),
            rgba(22,131,255,.15)
        );
}


/* colores */

.section-principal .menu-section-dot {

    background: #ffc400;

    box-shadow:
        0 0 8px rgba(255,196,0,.65);
}

.section-gestion .menu-section-dot {
    background: #1683ff;
}

.section-red .menu-section-dot {
    background: #16b9ed;
}

.section-admin .menu-section-dot {
    background: #795cff;
}


/* =========================================================
   ENLACES PRINCIPALES
========================================================= */

.sidebar-menu > a,
.sidebar-menu > .menu-item > .inventario-toggle {

    position: relative;

    width: 100%;

    min-height: 48px;

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 8px 12px;

    margin: 4px 0;

    border: 0;

    border-radius: 11px;

    background: transparent;

    color: #ffffff;

    text-decoration: none;

    font-family: inherit;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    text-align: left;

    transition:
        .22s ease;

    box-sizing: border-box;
}


/* =========================================================
   ICONOS
========================================================= */

.sidebar-icon {

    width: 38px;
    height: 38px;

    min-width: 38px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 16px;

    color: #ffffff;

    transition: .22s ease;

    box-shadow:
        inset 0 0 0 1px
        rgba(255,255,255,.08);
}


/* =========================================================
   COLORES ICONOS
========================================================= */

.icon-yellow {

    background:
        linear-gradient(
            135deg,
            #ffd43b,
            #e5a900
        );
}

.icon-blue {

    background:
        linear-gradient(
            135deg,
            #39a5ff,
            #126ce5
        );
}

.icon-orange {

    background:
        linear-gradient(
            135deg,
            #ffbd27,
            #e38c00
        );
}

.icon-purple {

    background:
        linear-gradient(
            135deg,
            #9274ff,
            #6445df
        );
}

.icon-pink {

    background:
        linear-gradient(
            135deg,
            #ff5c8d,
            #e62d63
        );
}

.icon-cyan {

    background:
        linear-gradient(
            135deg,
            #2ed8ee,
            #08a9ce
        );
}

.icon-green {

    background:
        linear-gradient(
            135deg,
            #26ddbd,
            #0bb49c
        );
}


/* =========================================================
   HOVER
========================================================= */

.sidebar-menu > a:hover,
.sidebar-menu > .menu-item > .inventario-toggle:hover {

    background:
        rgba(25,118,220,.18);

    color: #ffffff;

    transform:
        translateX(2px);
}


.sidebar-menu > a:hover .sidebar-icon,
.sidebar-menu > .menu-item > .inventario-toggle:hover .sidebar-icon {

    transform:
        scale(1.06);

    box-shadow:
        0 5px 15px rgba(0,0,0,.20);
}


/* =========================================================
   ELEMENTO ACTIVO
========================================================= */

.sidebar-menu > a.active {

    background:
        linear-gradient(
            90deg,
            #f1bb00,
            #e5ae00
        ) !important;

    color: #ffffff !important;

    box-shadow:
        0 5px 15px rgba(0,0,0,.18);
}


/* barra amarilla */

.sidebar-menu > a.active::before {

    content: "";

    position: absolute;

    left: -1px;

    top: 7px;
    bottom: 7px;

    width: 4px;

    border-radius:
        0 5px 5px 0;

    background:
        #ffd52f;
}


/* icono activo */

.sidebar-menu > a.active .sidebar-icon {

    background:
        rgba(255,255,255,.20);

    box-shadow:
        inset 0 0 0 1px
        rgba(255,255,255,.15);
}


/* =========================================================
   INVENTARIO PADRE
========================================================= */

.inventario-toggle {

    outline: none;

    appearance: none;

    -webkit-appearance: none;
}


/* inventario abierto */

.inventario-toggle.open {

    background:
        rgba(21,125,240,.18);
}


/* =========================================================
   FLECHA
========================================================= */

.inventario-toggle .arrow {

    margin-left: auto;

    font-size: 13px;

    color: #ffffff;

    transition:
        transform .25s ease;
}

.inventario-toggle.open .arrow {

    transform:
        rotate(180deg);
}


/* =========================================================
   SUBMENÚ
========================================================= */

.inventario-submenu {

    position: relative;

    display: none;

    margin:
        0 0 7px 30px;

    padding:
        3px 0 5px 17px;
}

.inventario-submenu.show {
    display: block;
}


/* línea vertical */

.inventario-submenu::before {

    content: "";

    position: absolute;

    left: 5px;

    top: 0;
    bottom: 5px;

    width: 2px;

    background:
        linear-gradient(
            180deg,
            #1683ff,
            rgba(22,131,255,.15)
        );

    border-radius: 5px;
}


/* =========================================================
   LINKS SUBMENÚ
========================================================= */

.inventario-submenu a {

    position: relative;

    min-height: 38px;

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 7px 8px;

    margin: 2px 0;

    border-radius: 8px;

    color: #b9d3ef;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    transition:
        .2s ease;

    box-sizing: border-box;
}


/* círculo */

.inventario-submenu a::before {

    content: "";

    width: 9px;
    height: 9px;

    min-width: 9px;

    border-radius: 50%;

    background:
        #1bd2b5;

    box-shadow:
        0 0 7px rgba(27,210,181,.5);
}


/* técnico */

.inventario-submenu a.tecnico::before {

    background:
        #765cff;

    box-shadow:
        0 0 7px rgba(118,92,255,.55);
}


/* hover */

.inventario-submenu a:hover {

    background:
        rgba(25,118,220,.16);

    color: #ffffff;

    transform:
        translateX(3px);
}


/* activo */

.inventario-submenu a.active {

    background:
        rgba(230,184,0,.18);

    color: #ffffff !important;
}


/* punto activo */

.inventario-submenu a.active::after {

    content: "";

    position: absolute;

    right: 6px;

    width: 6px;
    height: 6px;

    border-radius: 50%;

    background:
        #ffc400;

    box-shadow:
        0 0 8px
        rgba(255,196,0,.7);
}


/* =========================================================
   SEPARADOR
========================================================= */

.menu-separator {
    height: 8px;
}


/* =========================================================
   SISTEMA EN LÍNEA
========================================================= */

.sidebar-status {

    margin:
        16px 10px 8px;

    padding:
        12px;

    border:
        1px solid rgba(22,131,255,.40);

    border-radius:
        13px;

    background:
        linear-gradient(
            135deg,
            rgba(10,75,145,.35),
            rgba(2,37,82,.55)
        );

    display: flex;

    align-items: center;

    gap: 10px;

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.04);
}


.status-dot {

    width: 11px;
    height: 11px;

    min-width: 11px;

    border-radius: 50%;

    background:
        #17d9bd;

    box-shadow:
        0 0 9px
        rgba(23,217,189,.8);
}


.status-info {

    flex: 1;

    display: flex;

    flex-direction: column;

    line-height: 1.2;
}


.status-title {

    color: #dcecff;

    font-size: 11px;

    font-weight: 800;
}


.status-version {

    margin-top: 3px;

    color: #83a9d2;

    font-size: 10px;
}


.status-arrow {

    color: #1683ff;

    font-size: 17px;
}


/* =========================================================
   DECORACIÓN
========================================================= */

.sidebar-decoration {

    position: relative;

    height: 65px;

    overflow: hidden;

    margin-top: 8px;
}


.sidebar-decoration::before,
.sidebar-decoration::after {

    content: "";

    position: absolute;

    border-radius: 50%;

    border:
        18px solid
        rgba(20,111,216,.18);
}


.sidebar-decoration::before {

    width: 110px;
    height: 110px;

    left: -65px;
    bottom: -60px;
}


.sidebar-decoration::after {

    width: 75px;
    height: 75px;

    left: -22px;
    bottom: -48px;

    border-color:
        rgba(20,111,216,.20);
}


.sidebar-decoration-yellow {

    position: absolute;

    left: 7px;
    bottom: -4px;

    width: 40px;
    height: 40px;

    border-radius: 50%;

    background:
        #ffc400;

    box-shadow:
        0 0 15px
        rgba(255,196,0,.25);

    z-index: 2;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .sidebar {

        width: 250px;
    }

    .main {

        margin-left: 250px !important;

        width:
            calc(100% - 250px) !important;
    }

    .sidebar-logo {

        width: 195px;
    }
}

</style>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <!-- =====================================================
         LOGO
    ====================================================== -->

    <div class="sidebar-brand">

        <img
            src="../assets/img/logo-javeriana-horizontal.png"
            alt="Pontificia Universidad Javeriana"
            class="sidebar-logo"
        >

    </div>


    <!-- =====================================================
         MENÚ
    ====================================================== -->

    <nav class="sidebar-menu">


        <!-- =================================================
             PRINCIPAL
        ================================================== -->

        <div class="menu-section section-principal">

            <span class="menu-section-dot"></span>

            <span>Principal</span>

            <span class="menu-section-line"></span>

        </div>


        <!-- =================================================
             DASHBOARD
        ================================================== -->

        <?php if (tienePermiso('ver_dashboard')): ?>

            <a
                href="../pages/dashboard.php"
                class="<?= menuActivo('dashboard.php'); ?>"
            >

                <span class="sidebar-icon icon-yellow">

                    <i class="fas fa-house"></i>

                </span>

                <span>
                    Dashboard
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             GESTIÓN
        ================================================== -->

        <div class="menu-section section-gestion">

            <span class="menu-section-dot"></span>

            <span>Gestión</span>

            <span class="menu-section-line"></span>

        </div>


        <!-- =================================================
             INVENTARIO
        ================================================== -->

        <?php if (tienePermiso('ver_inventario')): ?>

            <div class="menu-item">

                <button
                    type="button"
                    id="inventarioToggle"
                    class="inventario-toggle <?= $enInventario ? 'open' : ''; ?>"
                >

                    <span class="sidebar-icon icon-blue">

                        <i class="fas fa-box"></i>

                    </span>

                    <span>
                        Inventario
                    </span>

                    <i class="fas fa-chevron-down arrow"></i>

                </button>


                <!-- SUBMENÚ -->

                <div
                    id="inventarioSubmenu"
                    class="inventario-submenu <?= $enInventario ? 'show' : ''; ?>"
                >

                    <!-- INVENTARIO GENERAL -->

                    <a
                        href="../pages/inventario.php"
                        class="<?= $esInventarioGeneral ? 'active' : ''; ?>"
                    >

                        <span>
                            Inventario General
                        </span>

                    </a>


                    <!-- INVENTARIO TÉCNICO -->

                    <a
                        href="../pages/inventario_tecnico.php"
                        class="tecnico <?= $esInventarioTecnico ? 'active' : ''; ?>"
                    >

                        <span>
                            Inventario Técnico
                        </span>

                    </a>

                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             EQUIPOS
        ================================================== -->

        <?php if (tienePermiso('ver_equipos')): ?>

            <a
                href="../pages/equipos.php"
                class="<?= menuActivo('equipos.php'); ?>"
            >

                <span class="sidebar-icon icon-orange">

                    <i class="fas fa-triangle-exclamation"></i>

                </span>

                <span>
                    Equipos
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             MANTENIMIENTO
        ================================================== -->

        <?php if (tienePermiso('ver_mantenimiento')): ?>

            <a
                href="../pages/mantenimiento.php"
                class="<?= menuActivo('mantenimiento.php'); ?>"
            >

                <span class="sidebar-icon icon-blue">

                    <i class="fas fa-screwdriver-wrench"></i>

                </span>

                <span>
                    Mantenimiento
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             UBICACIONES
        ================================================== -->

        <?php if (tienePermiso('ver_ubicaciones')): ?>

            <a
                href="../pages/ubicaciones.php"
                class="<?= menuActivo('ubicaciones.php'); ?>"
            >

                <span class="sidebar-icon icon-pink">

                    <i class="fas fa-location-dot"></i>

                </span>

                <span>
                    Ubicaciones
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             RED Y REPORTES
        ================================================== -->

        <div class="menu-section section-red">

            <span class="menu-section-dot"></span>

            <span>Red y Reportes</span>

            <span class="menu-section-line"></span>

        </div>


        <!-- =================================================
             PUNTOS DE RED
        ================================================== -->

        <?php if (tienePermiso('ver_puntos_red')): ?>

            <a
                href="../pages/puntos_red.php"
                class="<?= menuActivo('puntos_red.php'); ?>"
            >

                <span class="sidebar-icon icon-purple">

                    <i class="fas fa-shield-halved"></i>

                </span>

                <span>
                    Puntos de Red
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             REPORTES
        ================================================== -->

        <?php if (tienePermiso('ver_reportes')): ?>

            <a
                href="../pages/reportes.php"
                class="<?= menuActivo('reportes.php'); ?>"
            >

                <span class="sidebar-icon icon-cyan">

                    <i class="fas fa-chart-column"></i>

                </span>

                <span>
                    Reportes
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             ADMINISTRACIÓN
        ================================================== -->

        <div class="menu-section section-admin">

            <span class="menu-section-dot"></span>

            <span>Administración</span>

            <span class="menu-section-line"></span>

        </div>


        <!-- =================================================
             USUARIOS
        ================================================== -->

        <?php if (tienePermiso('gestionar_usuarios')): ?>

            <a
                href="../pages/gestion_usuarios.php"
                class="<?= menuActivo('gestion_usuarios.php'); ?>"
            >

                <span class="sidebar-icon icon-pink">

                    <i class="fas fa-users"></i>

                </span>

                <span>
                    Usuarios
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             CONFIGURACIÓN
        ================================================== -->

        <?php if (tienePermiso('configuracion')): ?>

            <a
                href="../pages/configuracion.php"
                class="<?= menuActivo('configuracion.php'); ?>"
            >

                <span class="sidebar-icon icon-green">

                    <i class="fas fa-gear"></i>

                </span>

                <span>
                    Configuración
                </span>

            </a>

        <?php endif; ?>


        <!-- =================================================
             ESTADO
        ================================================== -->

        <div class="sidebar-status">

            <span class="status-dot"></span>

            <div class="status-info">

                <span class="status-title">
                    Sistema en línea
                </span>

                <span class="status-version">
                    JaverStock v1.0
                </span>

            </div>

            <i class="fas fa-wave-square status-arrow"></i>

        </div>


        <!-- =================================================
             DECORACIÓN
        ================================================== -->

        <div class="sidebar-decoration">

            <span class="sidebar-decoration-yellow"></span>

        </div>


    </nav>

</aside>


<!-- =========================================================
     JAVASCRIPT SIDEBAR
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const toggle =
            document.getElementById(
                'inventarioToggle'
            );

        const submenu =
            document.getElementById(
                'inventarioSubmenu'
            );


        if (!toggle || !submenu) {
            return;
        }


        /* ================================================
           ABRIR / CERRAR INVENTARIO
        ================================================= */

        toggle.addEventListener(
            'click',
            function () {

                toggle.classList.toggle('open');

                submenu.classList.toggle('show');

            }
        );


        /* ================================================
           CERRAR AL ENTRAR A OTRO MENÚ
        ================================================= */

        const otrosMenus =
            document.querySelectorAll(
                '.sidebar-menu > a'
            );


        otrosMenus.forEach(
            function (menu) {

                menu.addEventListener(
                    'click',
                    function () {

                        toggle.classList.remove(
                            'open'
                        );

                        submenu.classList.remove(
                            'show'
                        );

                    }
                );

            }
        );

    }
);

</script>