<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/permisos.php";

exigirPermiso('ver_inventario');


/*
|--------------------------------------------------------------------------
| ÁREA SELECCIONADA
|--------------------------------------------------------------------------
*/

$area = strtolower(
    trim($_GET['area'] ?? 'audiovisuales')
);

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

if (!in_array($area, $areasPermitidas, true)) {
    $area = 'audiovisuales';
}


/*
|--------------------------------------------------------------------------
| NOMBRE DEL ÁREA
|--------------------------------------------------------------------------
*/

$nombreArea = (
    $area === 'audiovisuales'
)
    ? 'Audiovisuales'
    : 'Automatización';


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$edificios = [];
$totalPuntos = 0;


/*
|--------------------------------------------------------------------------
| AUDIOVISUALES
|--------------------------------------------------------------------------
|
| Se consulta directamente la tabla equipos.
|
| IMPORTANTE:
| Solo usamos columnas que existen actualmente:
|
| id
| codigo
| nombre
| area
| edificio
| piso
| salon
| marca
| modelo
| numero_serie
| ip
| rack
| switch_red
| puerto_red
| estado
|
|--------------------------------------------------------------------------
*/

if ($area === 'audiovisuales') {

    $sql = "
        SELECT
            id,
            codigo,
            nombre,
            area,
            edificio,
            piso,
            salon,
            marca,
            modelo,
            numero_serie,
            ip,
            rack,
            switch_red,
            puerto_red,
            estado
        FROM equipos
        WHERE area = 'audiovisuales'
        ORDER BY
            edificio ASC,
            piso ASC,
            nombre ASC
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {

        die(
            "Error consultando equipos audiovisuales: " .
            htmlspecialchars(
                $conn->error,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }


    while ($fila = $resultado->fetch_assoc()) {

        $edificio = trim(
            $fila['edificio'] ?? ''
        );

        if ($edificio === '') {
            $edificio = 'Sin edificio';
        }


        if (!isset($edificios[$edificio])) {
            $edificios[$edificio] = [];
        }


        $edificios[$edificio][] = $fila;

        $totalPuntos++;
    }
}


/*
|--------------------------------------------------------------------------
| AUTOMATIZACIÓN
|--------------------------------------------------------------------------
|
| Automatización utiliza directamente puntos_seguridad.
|
| NOTA:
| Aquí se mantienen solamente los campos que ya utiliza tu
| estructura de puntos de seguridad.
|--------------------------------------------------------------------------
*/

if ($area === 'automatizacion') {

    $sql = "
        SELECT
            id,
            sistema,
            item,
            edificio,
            zona,
            ip,
            ubicacion,
            servidor,
            administracion,
            observaciones,
            camara_server,
            gateway,
            dns,
            mascara,
            rack,
            subsistema,
            estado
        FROM puntos_seguridad
        ORDER BY
            edificio ASC,
            sistema ASC,
            item ASC
    ";


    $resultado = $conn->query($sql);


    if (!$resultado) {

        die(
            "Error consultando puntos de seguridad: " .
            htmlspecialchars(
                $conn->error,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }


    while ($fila = $resultado->fetch_assoc()) {

        $edificio = trim(
            $fila['edificio'] ?? ''
        );


        if ($edificio === '') {
            $edificio = 'Sin edificio';
        }


        if (!isset($edificios[$edificio])) {
            $edificios[$edificio] = [];
        }


        $edificios[$edificio][] = $fila;

        $totalPuntos++;
    }
}


/*
|--------------------------------------------------------------------------
| ORDENAR EDIFICIOS
|--------------------------------------------------------------------------
*/

ksort($edificios);


include("../includes/sidebar.php");

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        JaverStock | Puntos de Red
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >


    <style>

        body {
            background: #f4f7fb;
        }


        .puntos-red-page {
            padding-bottom: 50px;
        }


        /*
        ---------------------------------------------------------
        ENCABEZADO
        ---------------------------------------------------------
        */

        .puntos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }


        .puntos-header h2 {
            margin: 0;
            color: #002855;
            font-size: 32px;
            font-weight: 800;
        }


        .puntos-header p {
            margin: 6px 0 0;
            color: #64748b;
        }


        /*
        ---------------------------------------------------------
        BOTONES DE ÁREA
        ---------------------------------------------------------
        */

        .areas-red {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }


        .btn-area-red {
            border-radius: 14px;
            padding: 13px 22px;
            border: 1px solid #dbe3ec;
            text-decoration: none;
            font-weight: 700;

            display: inline-flex;
            align-items: center;
            gap: 9px;

            transition: all .2s ease;
        }


        .btn-area-red:hover {
            transform: translateY(-2px);
        }


        .btn-area-red.activo {
            background: #002855;
            color: white;
            border-color: #002855;

            box-shadow:
                0 8px 20px rgba(0,40,85,.18);
        }


        .btn-area-red.inactivo {
            background: white;
            color: #002855;
        }


        .btn-area-red.inactivo:hover {
            background: #eef5fc;
        }


        /*
        ---------------------------------------------------------
        RESUMEN
        ---------------------------------------------------------
        */

        .resumen-red {
            background: white;
            border-radius: 18px;

            padding: 20px 24px;

            margin-bottom: 28px;

            box-shadow:
                0 8px 25px rgba(0,0,0,.06);

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            flex-wrap: wrap;
        }


        .resumen-numero {
            font-size: 30px;
            font-weight: 800;
            color: #002855;
        }


        .resumen-texto {
            color: #64748b;
            font-weight: 600;
        }


        /*
        ---------------------------------------------------------
        TARJETAS DE EDIFICIOS
        ---------------------------------------------------------
        */

        .edificio-card {

            background: white;

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 8px 25px rgba(0,0,0,.07);

            margin-bottom: 28px;

            border:
                1px solid #edf1f5;
        }


        .edificio-header {

            background:
                linear-gradient(
                    135deg,
                    #002855,
                    #064b8d
                );

            color: white;

            padding: 20px 24px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            flex-wrap: wrap;
        }


        .edificio-titulo {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .edificio-titulo i {
            font-size: 25px;
        }


        .edificio-titulo h4 {
            margin: 0;

            font-weight: 800;
        }


        .cantidad-edificio {

            background:
                rgba(255,255,255,.15);

            border:
                1px solid rgba(255,255,255,.25);

            border-radius: 20px;

            padding: 7px 13px;

            font-size: 13px;

            font-weight: 700;
        }


        /*
        ---------------------------------------------------------
        TABLA
        ---------------------------------------------------------
        */

        .tabla-red-wrap {

            padding: 20px;

            overflow-x: auto;
        }


        .tabla-red {

            width: 100%;

            border-collapse:
                separate;

            border-spacing: 0;

            min-width: 950px;
        }


        .tabla-red thead th {

            background:
                #f1f5f9;

            color:
                #334155;

            font-size:
                12px;

            font-weight:
                800;

            padding:
                13px;

            border-bottom:
                1px solid #e2e8f0;

            white-space:
                nowrap;
        }


        .tabla-red tbody td {

            padding:
                13px;

            border-bottom:
                1px solid #edf1f5;

            color:
                #334155;

            vertical-align:
                middle;

            font-size:
                13px;
        }


        .tabla-red tbody tr {

            transition:
                all .18s ease;
        }


        .tabla-red tbody tr:hover {

            background:
                #f8fbff;
        }


        /*
        ---------------------------------------------------------
        IP
        ---------------------------------------------------------
        */

        .ip-badge {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                7px;

            background:
                #eef6ff;

            color:
                #0056a6;

            border:
                1px solid #cfe5ff;

            border-radius:
                9px;

            padding:
                7px 10px;

            font-family:
                monospace;

            font-weight:
                700;
        }


        /*
        ---------------------------------------------------------
        RACK
        ---------------------------------------------------------
        */

        .rack-badge {

            display:
                inline-block;

            background:
                #f8fafc;

            border:
                1px solid #dbe3ec;

            border-radius:
                8px;

            padding:
                6px 9px;

            font-weight:
                700;

            color:
                #475569;
        }


        /*
        ---------------------------------------------------------
        SWITCH
        ---------------------------------------------------------
        */

        .switch-info {

            font-weight:
                700;

            color:
                #334155;
        }


        .switch-port {

            color:
                #64748b;

            font-size:
                12px;
        }


        /*
        ---------------------------------------------------------
        ESTADOS
        ---------------------------------------------------------
        */

        .estado {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            border-radius:
                20px;

            padding:
                6px 10px;

            font-size:
                12px;

            font-weight:
                700;
        }


        .estado-activo {

            background:
                #dcfce7;

            color:
                #166534;
        }


        .estado-inactivo {

            background:
                #fee2e2;

            color:
                #991b1b;
        }


        .estado-mantenimiento {

            background:
                #fef3c7;

            color:
                #92400e;
        }


        /*
        ---------------------------------------------------------
        SIN DATOS
        ---------------------------------------------------------
        */

        .sin-datos {

            background:
                white;

            border-radius:
                20px;

            padding:
                60px 30px;

            text-align:
                center;

            box-shadow:
                0 8px 25px rgba(0,0,0,.06);

            color:
                #64748b;
        }


        .sin-datos i {

            font-size:
                50px;

            margin-bottom:
                15px;

            color:
                #94a3b8;
        }


        /*
        ---------------------------------------------------------
        RESPONSIVE
        ---------------------------------------------------------
        */

        @media(max-width: 768px) {

            .puntos-header h2 {
                font-size: 26px;
            }


            .resumen-red {
                align-items:
                    flex-start;
            }

        }

    </style>

</head>


<body>


<div class="main puntos-red-page">


    <?php include("../includes/header.php"); ?>


    <div class="content">


        <!-- ENCABEZADO -->

        <div class="puntos-header">

            <div>

                <h2>

                    <i class="fas fa-network-wired"></i>

                    Puntos de Red

                </h2>


                <p>

                    Gestión de dispositivos y conexiones de red.

                </p>

            </div>

        </div>



        <!-- BOTONES DE ÁREA -->

        <div class="areas-red">


            <a
                href="puntos_red.php?area=audiovisuales"
                class="btn-area-red
                <?= $area === 'audiovisuales'
                    ? 'activo'
                    : 'inactivo'; ?>"
            >

                <i class="fas fa-video"></i>

                Audiovisuales

            </a>



            <a
                href="puntos_red.php?area=automatizacion"
                class="btn-area-red
                <?= $area === 'automatizacion'
                    ? 'activo'
                    : 'inactivo'; ?>"
            >

                <i class="fas fa-shield-halved"></i>

                Automatización

            </a>


        </div>



        <!-- RESUMEN -->

        <div class="resumen-red">


            <div>

                <div class="resumen-numero">

                    <?= number_format($totalPuntos); ?>

                </div>


                <div class="resumen-texto">

                    Registros encontrados

                </div>

            </div>



            <div>

                <strong>

                    Área:

                </strong>


                <?= htmlspecialchars(
                    $nombreArea,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>


        </div>



        <?php if (empty($edificios)): ?>


            <div class="sin-datos">


                <i class="fas fa-network-wired"></i>


                <h4>

                    No hay registros

                </h4>


                <p>

                    No existen puntos de red registrados
                    para esta área.

                </p>


            </div>


        <?php else: ?>


            <?php foreach (
                $edificios as $edificio => $puntos
            ): ?>


                <div class="edificio-card">


                    <!-- CABECERA EDIFICIO -->

                    <div class="edificio-header">


                        <div class="edificio-titulo">


                            <i class="fas fa-building"></i>


                            <h4>

                                <?= htmlspecialchars(
                                    $edificio,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </h4>


                        </div>


                        <span class="cantidad-edificio">

                            <?= count($puntos); ?>

                            puntos

                        </span>


                    </div>



                    <!-- TABLA -->

                    <div class="tabla-red-wrap">


                        <table class="tabla-red">


                            <thead>


                                <tr>


                                    <?php
                                    /*
                                    |--------------------------------------------------------------------------
                                    | TABLA AUDIOVISUALES
                                    |--------------------------------------------------------------------------
                                    */
                                    ?>

                                    <?php if (
                                        $area === 'audiovisuales'
                                    ): ?>


                                        <th>
                                            Código
                                        </th>


                                        <th>
                                            Equipo
                                        </th>


                                        <th>
                                            Piso
                                        </th>


                                        <th>
                                            Salón
                                        </th>


                                        <th>
                                            IP
                                        </th>


                                        <th>
                                            Rack
                                        </th>


                                        <th>
                                            Switch
                                        </th>


                                        <th>
                                            Puerto
                                        </th>


                                        <th>
                                            Estado
                                        </th>


                                    <?php else: ?>


                                        <?php
                                        /*
                                        |--------------------------------------------------------------------------
                                        | TABLA AUTOMATIZACIÓN
                                        |--------------------------------------------------------------------------
                                        */
                                        ?>


                                        <th>
                                            Sistema
                                        </th>


                                        <th>
                                            Item
                                        </th>


                                        <th>
                                            Zona
                                        </th>


                                        <th>
                                            IP
                                        </th>


                                        <th>
                                            Ubicación
                                        </th>


                                        <th>
                                            Rack
                                        </th>


                                        <th>
                                            Switch
                                        </th>


                                        <th>
                                            Estado
                                        </th>


                                    <?php endif; ?>


                                </tr>


                            </thead>



                            <tbody>


                            <?php foreach (
                                $puntos as $punto
                            ): ?>


                                <tr>


                                    <?php if (
                                        $area === 'audiovisuales'
                                    ): ?>


                                        <!-- CÓDIGO -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $punto['codigo'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </strong>

                                        </td>



                                        <!-- EQUIPO -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $punto['nombre'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </strong>


                                            <?php if (
                                                !empty(
                                                    $punto['marca']
                                                )
                                            ): ?>

                                                <br>

                                                <small class="text-muted">

                                                    <?= htmlspecialchars(
                                                        $punto['marca'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>


                                                    <?php if (
                                                        !empty(
                                                            $punto['modelo']
                                                        )
                                                    ): ?>

                                                        -

                                                        <?= htmlspecialchars(
                                                            $punto['modelo'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    <?php endif; ?>

                                                </small>

                                            <?php endif; ?>

                                        </td>



                                        <!-- PISO -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $punto['piso'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>



                                        <!-- SALÓN -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $punto['salon'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>



                                        <!-- IP -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $punto['ip']
                                                )
                                            ): ?>


                                                <span class="ip-badge">

                                                    <i
                                                        class="fas fa-globe">
                                                    </i>


                                                    <?= htmlspecialchars(
                                                        $punto['ip'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="text-muted">

                                                    Sin IP

                                                </span>


                                            <?php endif; ?>

                                        </td>



                                        <!-- RACK -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $punto['rack']
                                                )
                                            ): ?>


                                                <span class="rack-badge">

                                                    <?= htmlspecialchars(
                                                        $punto['rack'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="text-muted">

                                                    -

                                                </span>


                                            <?php endif; ?>

                                        </td>



                                        <!-- SWITCH -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $punto['switch_red']
                                                )
                                            ): ?>


                                                <span class="switch-info">

                                                    <?= htmlspecialchars(
                                                        $punto['switch_red'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="text-muted">

                                                    -

                                                </span>


                                            <?php endif; ?>

                                        </td>



                                        <!-- PUERTO -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $punto['puerto_red']
                                                )
                                            ): ?>


                                                <span class="switch-port">

                                                    <?= htmlspecialchars(
                                                        $punto['puerto_red'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="text-muted">

                                                    -

                                                </span>


                                            <?php endif; ?>

                                        </td>



                                        <!-- ESTADO -->

                                        <td>


                                            <?php

                                            $estado = strtolower(
                                                trim(
                                                    $punto['estado'] ?? ''
                                                )
                                            );

                                            ?>


                                            <span
                                                class="estado
                                                <?= (
                                                    $estado === 'operativo'
                                                    || $estado === 'activo'
                                                    || $estado === 'reparado'
                                                )
                                                    ? 'estado-activo'
                                                    : (
                                                        $estado === 'mantenimiento'
                                                        ? 'estado-mantenimiento'
                                                        : 'estado-inactivo'
                                                    ); ?>"
                                            >


                                                <i
                                                    class="fas fa-circle">
                                                </i>


                                                <?= htmlspecialchars(
                                                    $punto['estado'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>


                                            </span>


                                        </td>


                                    <?php else: ?>


                                        <!-- SISTEMA -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $punto['sistema'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </strong>

                                        </td>



                                        <!-- ITEM -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $punto['item'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>



                                        <!-- ZONA -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $punto['zona'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>



                                        <!-- IP -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $punto['ip']
                                                )
                                            ): ?>


                                                <span class="ip-badge">

                                                    <i
                                                        class="fas fa-globe">
                                                    </i>


                                                    <?= htmlspecialchars(
                                                        $punto['ip'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="text-muted">

                                                    Sin IP

                                                </span>


                                            <?php endif; ?>

                                        </td>



                                        <!-- UBICACIÓN -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $punto['ubicacion'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>



                                        <!-- RACK -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $punto['rack']
                                                )
                                            ): ?>


                                                <span class="rack-badge">

                                                    <?= htmlspecialchars(
                                                        $punto['rack'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                            <?php else: ?>


                                                <span class="text-muted">

                                                    -

                                                </span>


                                            <?php endif; ?>

                                        </td>



                                        <!-- SWITCH -->

                                        <td>

                                            <span class="switch-info">

                                                <?php

                                                /*
                                                | puntos_seguridad no
                                                | está usando switch_red
                                                | actualmente.
                                                |
                                                | Por eso mostramos
                                                | administración si
                                                | contiene información.
                                                */

                                                $switchMostrar =
                                                    $punto['administracion']
                                                    ?? '';

                                                ?>


                                                <?= htmlspecialchars(
                                                    $switchMostrar ?: '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>

                                        </td>



                                        <!-- ESTADO -->

                                        <td>


                                            <?php

                                            $estado = strtolower(
                                                trim(
                                                    $punto['estado'] ?? ''
                                                )
                                            );

                                            ?>


                                            <span
                                                class="estado
                                                <?= (
                                                    $estado === 'activo'
                                                    || $estado === 'operativo'
                                                )
                                                    ? 'estado-activo'
                                                    : (
                                                        $estado === 'mantenimiento'
                                                        ? 'estado-mantenimiento'
                                                        : 'estado-inactivo'
                                                    ); ?>"
                                            >


                                                <i
                                                    class="fas fa-circle">
                                                </i>


                                                <?= htmlspecialchars(
                                                    $punto['estado'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>


                                            </span>


                                        </td>


                                    <?php endif; ?>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>