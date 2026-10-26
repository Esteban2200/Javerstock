<?php
/* =========================================================
   JAVERSTOCK - INVENTARIO TÉCNICO
   Página principal: selección de hojas
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('ver_inventario');


/* =========================================================
   HOJAS REGISTRADAS
   ========================================================= */

$hojas = [];
$totalRegistros = 0;

$sqlHojas = "
    SELECT
        hoja_origen,
        COUNT(*) AS total
    FROM inventario_tecnico
    WHERE hoja_origen IS NOT NULL
      AND TRIM(hoja_origen) <> ''
    GROUP BY hoja_origen
    ORDER BY hoja_origen ASC
";

$resultadoHojas = $conn->query($sqlHojas);

if ($resultadoHojas) {

    while ($fila = $resultadoHojas->fetch_assoc()) {

        $hojas[] = $fila;

        $totalRegistros += (int) $fila['total'];
    }
}


/* =========================================================
   NORMALIZAR NOMBRE DE HOJA
   ========================================================= */

function normalizarNombreHoja($texto)
{
    $texto = trim((string) $texto);

    if (function_exists('iconv')) {

        $normalizado = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $texto
        );

        if ($normalizado !== false) {
            $texto = $normalizado;
        }
    }

    return strtolower($texto);
}


/* =========================================================
   CONFIGURACIÓN VISUAL DE LAS HOJAS
   ========================================================= */

function configuracionHoja($nombre)
{
    $n = normalizarNombreHoja($nombre);

    $config = [
        'icono' => 'fa-file-excel',
        'clase' => 'hoja-default',
        'descripcion' => 'Registros del inventario técnico.'
    ];


    /* CCTV */

    if (strpos($n, 'cctv') !== false) {

        $config = [
            'icono' => 'fa-video',
            'clase' => 'hoja-cctv',
            'descripcion' => 'Cámaras y equipos de videovigilancia.'
        ];


    /* CONTROL */

    } elseif (strpos($n, 'control') !== false) {

        $config = [
            'icono' => 'fa-door-open',
            'clase' => 'hoja-control',
            'descripcion' => 'Controladores, lectores y paneles de acceso.'
        ];


    /* TORNIQUETES */

    } elseif (strpos($n, 'torniquete') !== false) {

        $config = [
            'icono' => 'fa-arrows-left-right',
            'clase' => 'hoja-torniquete',
            'descripcion' => 'Torniquetes y accesos peatonales.'
        ];


    /* PERIFÉRICOS */

    } elseif (strpos($n, 'perifer') !== false) {

        $config = [
            'icono' => 'fa-microchip',
            'clase' => 'hoja-periferico',
            'descripcion' => 'Periféricos y accesorios de control de acceso.'
        ];


    /* INCENDIO */

    } elseif (
        strpos($n, 'incendio') !== false ||
        strpos($n, 'deteccion') !== false
    ) {

        $config = [
            'icono' => 'fa-fire',
            'clase' => 'hoja-incendio',
            'descripcion' => 'Sistemas de detección y alarma.'
        ];


    /* INTRUSIÓN */

    } elseif (strpos($n, 'intrusion') !== false) {

        $config = [
            'icono' => 'fa-shield-halved',
            'clase' => 'hoja-intrusion',
            'descripcion' => 'Alarmas y sistemas de intrusión.'
        ];


    /* DATACENTER */

    } elseif (
        strpos($n, 'datacenter') !== false ||
        strpos($n, 'data center') !== false
    ) {

        $config = [
            'icono' => 'fa-server',
            'clase' => 'hoja-datacenter',
            'descripcion' => 'Equipos y servidores del centro de datos.'
        ];


    /* BIBLIOTECA */

    } elseif (strpos($n, 'biblioteca') !== false) {

        $config = [
            'icono' => 'fa-book',
            'clase' => 'hoja-biblioteca',
            'descripcion' => 'Equipos registrados en la biblioteca.'
        ];


    /* BIOTERIO */

    } elseif (strpos($n, 'bioterio') !== false) {

        $config = [
            'icono' => 'fa-flask',
            'clase' => 'hoja-bioterio',
            'descripcion' => 'Equipos del área de bioterio.'
        ];


    /* INGENIERÍA */

    } elseif (strpos($n, 'ingenier') !== false) {

        $config = [
            'icono' => 'fa-building',
            'clase' => 'hoja-ingenieria',
            'descripcion' => 'Equipos registrados en Ingeniería.'
        ];


    /* ARTES */

    } elseif (strpos($n, 'artes') !== false) {

        $config = [
            'icono' => 'fa-building',
            'clase' => 'hoja-artes',
            'descripcion' => 'Equipos de los edificios Artes 1 y 2.'
        ];


    /* MALDONADO */

    } elseif (strpos($n, 'maldonado') !== false) {

        $config = [
            'icono' => 'fa-building',
            'clase' => 'hoja-maldonado',
            'descripcion' => 'Equipos registrados en Maldonado.'
        ];


    /* HOYOS */

    } elseif (strpos($n, 'hoyos') !== false) {

        $config = [
            'icono' => 'fa-building',
            'clase' => 'hoja-hoyos',
            'descripcion' => 'Equipos registrados en Hoyos.'
        ];


    /* ÁTICO */

    } elseif (strpos($n, 'atico') !== false) {

        $config = [
            'icono' => 'fa-building',
            'clase' => 'hoja-atico',
            'descripcion' => 'Equipos registrados en Ático.'
        ];


    /* EXTENSIÓN */

    } elseif (strpos($n, 'extension') !== false) {

        $config = [
            'icono' => 'fa-building',
            'clase' => 'hoja-extension',
            'descripcion' => 'Equipos registrados en Extensión.'
        ];
    }

    return $config;
}

?>


<style>

/* =========================================================
   JAVERSTOCK - INVENTARIO TÉCNICO
   DISEÑO COMPACTO + FONDO + SIN SCROLL HORIZONTAL
========================================================= */


/* =========================================================
   CONFIGURACIÓN GENERAL
========================================================= */

html,
body {

    width: 100%;
    max-width: 100%;

    margin: 0;
    padding: 0;

    overflow-x: hidden !important;

    box-sizing: border-box;
}

*,
*::before,
*::after {

    box-sizing: border-box;
}


/* =========================================================
   FONDO GENERAL
========================================================= */

body {
    margin: 0;
    padding: 0;

    background-image:
        linear-gradient(
            rgba(244, 246, 249, 0),
            rgba(244, 246, 249, 0.06)
        ),


    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    background-attachment: fixed;

    min-height: 100vh;
}

/* =========================================================
   PÁGINA PRINCIPAL
========================================================= */

.inventario-tecnico-page {

    width: 100%;
    max-width: 100%;

    min-width: 0;

    overflow-x: hidden;

    background: transparent;
}


/* =========================================================
   CONTENIDO
========================================================= */

.inventario-tecnico-page .content {

    width: 100%;
    max-width: 100%;

    min-width: 0;

    padding: 18px 22px 25px;

    overflow-x: hidden;
}


/* =========================================================
   CAJA PRINCIPAL
========================================================= */

.tecnico-hojas-box {

    width: 100%;
    max-width: 1450px;

    margin: 0 auto;

    background: rgba(255, 255, 255, 0.94);

    border: 1px solid rgba(220, 231, 245, 0.95);

    border-radius: 20px;

    padding: 21px;

    box-shadow:
        0 8px 25px rgba(6, 46, 91, 0.08);

    overflow: hidden;
}


/* =========================================================
   ENCABEZADO
========================================================= */

.tecnico-hojas-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 18px;

    min-width: 0;
}


/* =========================================================
   ENCABEZADO IZQUIERDO
========================================================= */

.tecnico-hojas-heading {

    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 0;
}


/* =========================================================
   ICONO DEL ENCABEZADO
========================================================= */

.tecnico-hojas-heading-icon {

    width: 48px;
    height: 48px;

    min-width: 48px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #e8f2ff,
            #d8eaff
        );

    color: #0068d7;

    font-size: 20px;
}


/* =========================================================
   TÍTULO
========================================================= */

.tecnico-hojas-heading h2,
.tecnico-hojas-heading h5 {

    margin: 0;

    color: #062e5b;

    font-size: 22px;

    line-height: 1.15;

    font-weight: 850;

    letter-spacing: -0.3px;
}


/* =========================================================
   SUBTÍTULO
========================================================= */

.tecnico-hojas-heading p {

    margin: 4px 0 0;

    color: #66809b;

    font-size: 12px;

    line-height: 1.3;
}


/* =========================================================
   RESUMEN
========================================================= */

.tecnico-hojas-resumen {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 12px;

    border-radius: 10px;

    background: #f1f7ff;

    color: #164a7d;

    font-size: 12px;

    font-weight: 750;

    white-space: nowrap;
}


/* =========================================================
   GRID DE TARJETAS
========================================================= */

.tecnico-hojas-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 12px;

    width: 100%;
    max-width: 100%;

    min-width: 0;

    overflow: hidden;
}


/* =========================================================
   TARJETA
========================================================= */

.tecnico-hoja-btn {

    --hoja-color: #1677d2;

    --hoja-soft: #edf6ff;

    --hoja-border: #d8eafb;


    position: relative;


    width: 100%;

    min-width: 0;

    height: 105px;

    min-height: 105px;


    display: flex;

    align-items: center;


    gap: 11px;


    padding: 12px;


    overflow: hidden;


    text-decoration: none !important;


    border: 1px solid var(--hoja-border);

    border-radius: 15px;


    background:
        linear-gradient(
            135deg,
            #ffffff 0%,
            var(--hoja-soft) 100%
        );


    box-shadow:
        0 4px 12px rgba(6, 46, 91, 0.05);


    transition:
        transform .20s ease,
        box-shadow .20s ease,
        border-color .20s ease;
}


/* =========================================================
   BARRA LATERAL DE COLOR
========================================================= */

.tecnico-hoja-btn::before {

    content: '';

    position: absolute;

    top: 0;
    bottom: 0;
    left: 0;

    width: 4px;

    background: var(--hoja-color);

    border-radius: 15px 0 0 15px;
}


/* =========================================================
   DECORACIÓN DE FONDO
========================================================= */

.tecnico-hoja-btn::after {

    content: '';

    position: absolute;

    width: 80px;
    height: 80px;

    right: -45px;
    bottom: -48px;

    border-radius: 50%;

    background: var(--hoja-color);

    opacity: .07;

    pointer-events: none;
}


/* =========================================================
   EFECTO HOVER
========================================================= */

.tecnico-hoja-btn:hover {

    transform: translateY(-3px);

    border-color: var(--hoja-color);

    box-shadow:
        0 10px 22px rgba(6, 46, 91, 0.11);
}


/* =========================================================
   FOCUS
========================================================= */

.tecnico-hoja-btn:focus-visible {

    outline: 3px solid rgba(0, 104, 215, .25);

    outline-offset: 2px;
}


/* =========================================================
   TARJETA ACTIVA
========================================================= */

.tecnico-hoja-btn.active {

    border-color: var(--hoja-color);

    box-shadow:
        0 0 0 2px rgba(0, 104, 215, .10),
        0 10px 22px rgba(6, 46, 91, .10);
}


/* =========================================================
   ICONO DE TARJETA
========================================================= */

.tecnico-hoja-icon {

    width: 52px;
    height: 52px;

    flex: 0 0 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    background: var(--hoja-color);

    color: #ffffff;

    font-size: 20px;

    box-shadow:
        0 6px 13px
        color-mix(
            in srgb,
            var(--hoja-color) 25%,
            transparent
        );
}


/* =========================================================
   INFORMACIÓN
========================================================= */

.tecnico-hoja-info {

    min-width: 0;

    flex: 1;

    overflow: hidden;
}


/* =========================================================
   NOMBRE
========================================================= */

.tecnico-hoja-name {

    display: block;

    width: 100%;

    color: #062e5b;

    font-size: 14px;

    line-height: 1.15;

    font-weight: 850;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* =========================================================
   CANTIDAD
========================================================= */

.tecnico-hoja-count {

    display: inline-block;

    max-width: 100%;

    margin-top: 5px;

    padding: 4px 7px;

    border-radius: 7px;

    background: rgba(255, 255, 255, .78);

    color: var(--hoja-color);

    font-size: 10px;

    line-height: 1;

    font-weight: 800;

    white-space: nowrap;
}


/* =========================================================
   DESCRIPCIÓN
========================================================= */

.tecnico-hoja-description {

    display: -webkit-box;

    margin-top: 5px;

    color: #66809b;

    font-size: 9.5px;

    line-height: 1.25;

    overflow: hidden;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;
}


/* =========================================================
   FLECHA
========================================================= */

.tecnico-hoja-arrow {

    width: 31px;
    height: 31px;

    flex: 0 0 31px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: rgba(255, 255, 255, .82);

    color: var(--hoja-color);

    font-size: 11px;

    transition: all .20s ease;
}


/* =========================================================
   HOVER FLECHA
========================================================= */

.tecnico-hoja-btn:hover .tecnico-hoja-arrow {

    background: var(--hoja-color);

    color: #ffffff;

    transform: translateX(2px);
}


/* =========================================================
   COLORES DE LAS HOJAS
========================================================= */

.hoja-cctv {

    --hoja-color: #1677d2;
    --hoja-soft: #edf7ff;
    --hoja-border: #cfe6fb;
}

.hoja-control {

    --hoja-color: #10a66f;
    --hoja-soft: #edfbf5;
    --hoja-border: #ccefe0;
}

.hoja-torniquete {

    --hoja-color: #7040df;
    --hoja-soft: #f4efff;
    --hoja-border: #e0d4fb;
}

.hoja-periferico {

    --hoja-color: #ed8b00;
    --hoja-soft: #fff6e9;
    --hoja-border: #f8dfb8;
}

.hoja-incendio {

    --hoja-color: #ed3347;
    --hoja-soft: #fff0f2;
    --hoja-border: #f8cfd5;
}

.hoja-intrusion {

    --hoja-color: #12aeb2;
    --hoja-soft: #edfafa;
    --hoja-border: #cceced;
}

.hoja-datacenter {

    --hoja-color: #8050d8;
    --hoja-soft: #f5f0ff;
    --hoja-border: #dfd3f5;
}

.hoja-biblioteca {

    --hoja-color: #e62f80;
    --hoja-soft: #fff0f7;
    --hoja-border: #f5cee1;
}

.hoja-bioterio {

    --hoja-color: #18a873;
    --hoja-soft: #edf9f4;
    --hoja-border: #cdeee0;
}

.hoja-ingenieria {

    --hoja-color: #2678dc;
    --hoja-soft: #edf5ff;
    --hoja-border: #d1e3fb;
}

.hoja-artes {

    --hoja-color: #3478e5;
    --hoja-soft: #edf5ff;
    --hoja-border: #d1e2fb;
}

.hoja-maldonado {

    --hoja-color: #ed9200;
    --hoja-soft: #fff7e9;
    --hoja-border: #f7dfb9;
}

.hoja-hoyos {

    --hoja-color: #13a5a9;
    --hoja-soft: #edfbfb;
    --hoja-border: #cdebed;
}

.hoja-atico {

    --hoja-color: #f05a3c;
    --hoja-soft: #fff2ee;
    --hoja-border: #f6d4ca;
}

.hoja-extension {

    --hoja-color: #763bdd;
    --hoja-soft: #f5f0ff;
    --hoja-border: #e1d5f9;
}

.hoja-default {

    --hoja-color: #2776c9;
    --hoja-soft: #eff6ff;
    --hoja-border: #d5e5f8;
}


/* =========================================================
   EVITAR DESPLAZAMIENTO HORIZONTAL
========================================================= */

.inventario-tecnico-page,
.inventario-tecnico-page .main,
.inventario-tecnico-page .content,
.tecnico-hojas-box,
.tecnico-hojas-grid {

    max-width: 100%;

    min-width: 0;

    overflow-x: hidden;
}


/* =========================================================
   RESPONSIVE - 1200px
========================================================= */

@media (max-width: 1200px) {

    .tecnico-hojas-grid {

        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }

}


/* =========================================================
   RESPONSIVE - 950px
========================================================= */

@media (max-width: 950px) {

    .tecnico-hojas-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .tecnico-hojas-box {

        padding: 18px;
    }

}


/* =========================================================
   RESPONSIVE - 650px
========================================================= */

@media (max-width: 650px) {

    .inventario-tecnico-page .content {

        padding: 12px;
    }


    .tecnico-hojas-box {

        padding: 15px;

        border-radius: 17px;
    }


    .tecnico-hojas-header {

        align-items: flex-start;

        flex-direction: column;
    }


    .tecnico-hojas-heading h2,
    .tecnico-hojas-heading h5 {

        font-size: 19px;
    }


    .tecnico-hojas-heading p {

        font-size: 11px;
    }


    .tecnico-hojas-resumen {

        display: none;
    }


    .tecnico-hojas-grid {

        grid-template-columns: 1fr;

        gap: 10px;
    }


    .tecnico-hoja-btn {

        height: 100px;

        min-height: 100px;

        padding: 12px;
    }


    .tecnico-hoja-icon {

        width: 48px;
        height: 48px;

        flex-basis: 48px;

        border-radius: 13px;

        font-size: 18px;
    }


    .tecnico-hoja-name {

        font-size: 13px;
    }


    .tecnico-hoja-description {

        font-size: 9px;
    }

}


/* =========================================================
   PANTALLAS MUY PEQUEÑAS
========================================================= */

@media (max-width: 420px) {

    .tecnico-hoja-btn {

        gap: 9px;

        padding: 10px;
    }


    .tecnico-hoja-icon {

        width: 44px;
        height: 44px;

        flex-basis: 44px;
    }


    .tecnico-hoja-arrow {

        width: 28px;
        height: 28px;

        flex-basis: 28px;
    }

}

</style>


<?php include("../includes/sidebar.php"); ?>


<div class="main inventario-tecnico-page">


    <?php include("../includes/header.php"); ?>


    <div class="content">


        <section class="tecnico-hojas-box">


            <!-- =================================================
                 ENCABEZADO
            ================================================== -->

            <div class="tecnico-hojas-header">


                <div class="tecnico-hojas-heading">


                    <div class="tecnico-hojas-heading-icon">

                        <i class="fas fa-layer-group"></i>

                    </div>


                    <div>

                        <h5>
                            Hojas del inventario
                        </h5>

                        <p>
                            Selecciona una hoja para consultar únicamente sus registros.
                        </p>

                    </div>


                </div>


                <!-- =================================================
                     RESUMEN
                ================================================== -->

                <div class="tecnico-hojas-resumen">

                    <i class="fas fa-database"></i>

                    <?= number_format(count($hojas)); ?>

                    hojas

                    <span>•</span>

                    <?= number_format($totalRegistros); ?>

                    registros

                </div>


            </div>


            <?php if (!empty($hojas)): ?>


                <!-- =================================================
                     GRID DE HOJAS
                ================================================== -->

                <div class="tecnico-hojas-grid">


                    <?php foreach ($hojas as $hoja): ?>


                        <?php

                        $nombreHoja = trim(
                            (string) $hoja['hoja_origen']
                        );

                        $config = configuracionHoja(
                            $nombreHoja
                        );

                        ?>


                        <a
                            href="hoja_inventario.php?hoja=<?= urlencode($nombreHoja); ?>"
                            class="tecnico-hoja-btn <?= htmlspecialchars(
                                $config['clase'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            title="Consultar <?= htmlspecialchars(
                                $nombreHoja,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >


                            <!-- =================================================
                                 ICONO
                            ================================================== -->

                            <span class="tecnico-hoja-icon">

                                <i class="fas <?= htmlspecialchars(
                                    $config['icono'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"></i>

                            </span>


                            <!-- =================================================
                                 INFORMACIÓN
                            ================================================== -->

                            <span class="tecnico-hoja-info">


                                <span class="tecnico-hoja-name">

                                    <?= htmlspecialchars(
                                        $nombreHoja,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </span>


                                <span class="tecnico-hoja-count">

                                    <?= number_format(
                                        (int) $hoja['total']
                                    ); ?>

                                    registros

                                </span>


                                <span class="tecnico-hoja-description">

                                    <?= htmlspecialchars(
                                        $config['descripcion'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </span>


                            </span>


                            <!-- =================================================
                                 FLECHA
                            ================================================== -->

                            <span class="tecnico-hoja-arrow">

                                <i class="fas fa-chevron-right"></i>

                            </span>


                        </a>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- =================================================
                     SIN REGISTROS
                ================================================== -->

                <div class="alert alert-info mb-0">

                    <i class="fas fa-circle-info me-2"></i>

                    Aún no hay hojas registradas en el inventario técnico.

                </div>


            <?php endif; ?>


        </section>


    </div>


</div>


<?php include("../includes/footer.php"); ?>