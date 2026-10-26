<?php

/* =========================================================
   JAVERSTOCK
   PÁGINA: UBICACIONES
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('ver_ubicaciones');


/* =========================================================
   FUNCIONES AUXILIARES
   ========================================================= */

function e($valor)
{
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/* ---------------------------------------------------------
   Normalizar texto
--------------------------------------------------------- */

function normalizarTexto($texto)
{
    $texto = trim((string)$texto);

    if ($texto === '') {
        return '';
    }

    /* Normaliza espacios normales y espacios especiales. */
    $texto = str_replace("\xC2\xA0", ' ', $texto);
    $texto = preg_replace('/\\s+/u', ' ', $texto);

    $texto = mb_strtolower($texto, 'UTF-8');

    $buscar = [
        'á',
        'é',
        'í',
        'ó',
        'ú',
        'ü',
        'ñ'
    ];

    $reemplazar = [
        'a',
        'e',
        'i',
        'o',
        'u',
        'u',
        'n'
    ];

    return trim(str_replace(
        $buscar,
        $reemplazar,
        $texto
    ));
}


/* ---------------------------------------------------------
   Clave normalizada de edificio
--------------------------------------------------------- */

function claveEdificio($edificio)
{
    return normalizarTexto($edificio);
}


/* ---------------------------------------------------------
   Color visual del edificio
--------------------------------------------------------- */

function claseColorEdificio($edificio)
{
    $clave = claveEdificio($edificio);

    $colores = [
        'atico'      => 'azul',
        'biblioteca' => 'verde',
        'bioterio'   => 'morado',
        'ingenieria' => 'naranja',
        'artes'      => 'rosa',
        'maldonado'  => 'turquesa',
        'hoyos'      => 'rojo',
        'extension'  => 'amarillo'
    ];

    if (isset($colores[$clave])) {
        return $colores[$clave];
    }

    $paleta = [
        'azul', 'verde', 'morado', 'naranja',
        'rosa', 'turquesa', 'rojo', 'amarillo'
    ];

    $indice = abs(crc32($clave)) % count($paleta);
    return $paleta[$indice];
}


/* ---------------------------------------------------------
   Nombre visual del edificio
--------------------------------------------------------- */

function nombreEdificioVisual($edificio)
{
    $edificio = trim((string)$edificio);

    if ($edificio === '') {
        return 'Sin edificio';
    }

    $clave = claveEdificio($edificio);

    $nombres = [

        'atico' => 'Ático',
        'biblioteca' => 'Biblioteca',
        'bioterio' => 'Bioterio',
        'ingenieria' => 'Ingeniería',
        'artes' => 'Artes',
        'maldonado' => 'Maldonado',
        'hoyos' => 'Hoyos',
        'extension' => 'Extensión'

    ];

    if (isset($nombres[$clave])) {
        return $nombres[$clave];
    }

    return $edificio;
}


/* ---------------------------------------------------------
   Icono para edificio
--------------------------------------------------------- */

function iconoEdificio($edificio)
{
    $clave = claveEdificio($edificio);

    $iconos = [

        'atico' => 'fa-building',
        'biblioteca' => 'fa-book',
        'bioterio' => 'fa-flask',
        'ingenieria' => 'fa-gears',
        'artes' => 'fa-palette',
        'maldonado' => 'fa-building-columns',
        'hoyos' => 'fa-school',
        'extension' => 'fa-building'

    ];

    return $iconos[$clave] ?? 'fa-building';
}


/* ---------------------------------------------------------
   Icono para sistema
--------------------------------------------------------- */

function iconoSistema($sistema)
{
    $clave = normalizarTexto($sistema);

    if (
        strpos($clave, 'torniquete') !== false
    ) {
        return 'fa-arrows-left-right';
    }

    if (
        strpos($clave, 'control') !== false &&
        strpos($clave, 'acceso') !== false
    ) {
        return 'fa-door-open';
    }

    if (
        strpos($clave, 'cctv') !== false ||
        strpos($clave, 'camara') !== false
    ) {
        return 'fa-video';
    }

    if (
        strpos($clave, 'incendio') !== false ||
        strpos($clave, 'deteccion') !== false
    ) {
        return 'fa-fire';
    }

    if (
        strpos($clave, 'intrusion') !== false
    ) {
        return 'fa-shield-halved';
    }

    if (
        strpos($clave, 'datacenter') !== false ||
        strpos($clave, 'data center') !== false
    ) {
        return 'fa-server';
    }

    if (
        strpos($clave, 'red') !== false ||
        strpos($clave, 'network') !== false
    ) {
        return 'fa-network-wired';
    }

    return 'fa-microchip';
}


/* ---------------------------------------------------------
   Clase visual sistema
--------------------------------------------------------- */

function claseSistema($sistema)
{
    $clave = normalizarTexto($sistema);

    if (strpos($clave, 'torniquete') !== false) {
        return 'sistema-torniquetes';
    }

    if (
        strpos($clave, 'control') !== false &&
        strpos($clave, 'acceso') !== false
    ) {
        return 'sistema-control';
    }

    if (
        strpos($clave, 'cctv') !== false ||
        strpos($clave, 'camara') !== false
    ) {
        return 'sistema-cctv';
    }

    if (
        strpos($clave, 'incendio') !== false ||
        strpos($clave, 'deteccion') !== false
    ) {
        return 'sistema-incendio';
    }

    return 'sistema-general';
}


/* ---------------------------------------------------------
   Estado
--------------------------------------------------------- */

function claseEstado($estado)
{
    $clave = normalizarTexto($estado);

    if (
        strpos($clave, 'operativo') !== false ||
        strpos($clave, 'registrado') !== false
    ) {
        return 'estado-operativo';
    }

    if (
        strpos($clave, 'mantenimiento') !== false
    ) {
        return 'estado-mantenimiento';
    }

    if (
        strpos($clave, 'danado') !== false ||
        strpos($clave, 'dañado') !== false
    ) {
        return 'estado-danado';
    }

    if (
        strpos($clave, 'reparado') !== false
    ) {
        return 'estado-reparado';
    }

    return 'estado-general';
}


/* =========================================================
   CONFIGURACIÓN
   ========================================================= */

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

$areaSeleccionada = strtolower(
    trim($_GET['area'] ?? 'audiovisuales')
);

if (
    !in_array(
        $areaSeleccionada,
        $areasPermitidas,
        true
    )
) {
    $areaSeleccionada = 'audiovisuales';
}


$edificioSeleccionado = trim(
    $_GET['edificio'] ?? ''
);

$pisoSeleccionado = trim(
    $_GET['piso'] ?? ''
);

$sistemaSeleccionado = trim(
    $_GET['sistema'] ?? ''
);

$mostrarEquipos = (
    $sistemaSeleccionado !== '' ||
    (
        $areaSeleccionada === 'audiovisuales' &&
        $edificioSeleccionado !== ''
    )
);


/* =========================================================
   DATOS
   ========================================================= */

$edificios = [];
$edificiosMapa = [];

$equipos = [];
$sistemas = [];
$pisos = [];


/* =========================================================
   OBTENER EDIFICIOS
   ========================================================= */

if ($areaSeleccionada === 'automatizacion') {

    /*
     * Automatización utiliza inventario_tecnico.
     */

    $sqlEdificios = "
        SELECT DISTINCT edificio
        FROM inventario_tecnico
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        ORDER BY edificio ASC
    ";

    $resultadoEdificios = $conn->query(
        $sqlEdificios
    );

    if ($resultadoEdificios) {

        while (
            $fila = $resultadoEdificios->fetch_assoc()
        ) {

            $edificio = trim(
                $fila['edificio'] ?? ''
            );

            if ($edificio === '') {
                continue;
            }

            $clave = claveEdificio(
                $edificio
            );

            if (!isset($edificiosMapa[$clave])) {

                $edificiosMapa[$clave] = [
                    'nombre' => nombreEdificioVisual(
                        $edificio
                    ),
                    'original' => $edificio,
                    'cantidad' => 0
                ];
            }
        }
    }


    /*
     * Cantidad de registros por edificio.
     */

    $sqlConteo = "
        SELECT edificio, COUNT(*) AS total
        FROM inventario_tecnico
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        GROUP BY edificio
    ";

    $resultadoConteo = $conn->query(
        $sqlConteo
    );

    if ($resultadoConteo) {

        while (
            $fila = $resultadoConteo->fetch_assoc()
        ) {

            $edificio = trim(
                $fila['edificio'] ?? ''
            );

            $clave = claveEdificio(
                $edificio
            );

            if (isset($edificiosMapa[$clave])) {

                $edificiosMapa[$clave]['cantidad'] +=
                    (int)$fila['total'];
            }
        }
    }

} else {

    /*
     * Audiovisuales utiliza equipos.
     */

    $sqlEdificios = "
        SELECT DISTINCT edificio
        FROM equipos
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        ORDER BY edificio ASC
    ";

    $resultadoEdificios = $conn->query(
        $sqlEdificios
    );

    if ($resultadoEdificios) {

        while (
            $fila = $resultadoEdificios->fetch_assoc()
        ) {

            $edificio = trim(
                $fila['edificio'] ?? ''
            );

            if ($edificio === '') {
                continue;
            }

            $clave = claveEdificio(
                $edificio
            );

            if (!isset($edificiosMapa[$clave])) {

                $edificiosMapa[$clave] = [
                    'nombre' => nombreEdificioVisual(
                        $edificio
                    ),
                    'original' => $edificio,
                    'cantidad' => 0
                ];
            }
        }
    }


    $sqlConteo = "
        SELECT edificio, COUNT(*) AS total
        FROM equipos
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        GROUP BY edificio
    ";

    $resultadoConteo = $conn->query(
        $sqlConteo
    );

    if ($resultadoConteo) {

        while (
            $fila = $resultadoConteo->fetch_assoc()
        ) {

            $edificio = trim(
                $fila['edificio'] ?? ''
            );

            $clave = claveEdificio(
                $edificio
            );

            if (isset($edificiosMapa[$clave])) {

                $edificiosMapa[$clave]['cantidad'] +=
                    (int)$fila['total'];
            }
        }
    }
}


$edificios = array_values(
    $edificiosMapa
);


/* =========================================================
   OBTENER PISOS PARA AUDIOVISUALES
   ========================================================= */

if (
    $areaSeleccionada === 'audiovisuales' &&
    $edificioSeleccionado !== ''
) {

    $sqlPisos = "
        SELECT DISTINCT edificio, piso
        FROM equipos
        WHERE piso IS NOT NULL
        AND TRIM(piso) <> ''
    ";

    $resultadoPisos = $conn->query(
        $sqlPisos
    );

    if ($resultadoPisos) {

        while (
            $fila = $resultadoPisos->fetch_assoc()
        ) {

            $edificioFila = trim(
                $fila['edificio'] ?? ''
            );

            if (
                claveEdificio($edificioFila)
                !==
                claveEdificio($edificioSeleccionado)
            ) {
                continue;
            }

            $piso = trim(
                $fila['piso'] ?? ''
            );

            if ($piso !== '') {
                $pisos[] = $piso;
            }
        }
    }

    $pisos = array_values(
        array_unique($pisos)
    );

    sort(
        $pisos,
        SORT_NATURAL | SORT_FLAG_CASE
    );
}


/* =========================================================
   OBTENER SISTEMAS DE AUTOMATIZACIÓN
   ========================================================= */

if (
    $areaSeleccionada === 'automatizacion' &&
    $edificioSeleccionado !== ''
) {

    $sqlSistemas = "
        SELECT
            hoja_origen,
            sistema,
            categoria,
            COUNT(*) AS total
        FROM inventario_tecnico
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        GROUP BY
            hoja_origen,
            sistema,
            categoria
        ORDER BY
            hoja_origen ASC,
            sistema ASC,
            categoria ASC
    ";

    $resultadoSistemas = $conn->query(
        $sqlSistemas
    );

    $sistemasMapa = [];

    if ($resultadoSistemas) {

        while (
            $fila = $resultadoSistemas->fetch_assoc()
        ) {

            /*
             * Como el GROUP BY se hace sobre todos
             * los edificios, debemos comprobar
             * nuevamente el edificio consultando
             * los registros reales.
             *
             * Esta información se vuelve a construir
             * más abajo para el edificio seleccionado.
             */
        }
    }


    /*
     * Consulta específica del edificio.
     */

    $sqlSistemasEdificio = "
        SELECT
            edificio,
            hoja_origen,
            sistema,
            categoria
        FROM inventario_tecnico
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        ORDER BY
            hoja_origen ASC,
            sistema ASC,
            categoria ASC
    ";

    $resultadoSistemasEdificio =
        $conn->query($sqlSistemasEdificio);

    if ($resultadoSistemasEdificio) {

        while (
            $fila =
            $resultadoSistemasEdificio->fetch_assoc()
        ) {

            if (
                claveEdificio($fila['edificio'] ?? '')
                !==
                claveEdificio($edificioSeleccionado)
            ) {
                continue;
            }

            $sistema = trim($fila['hoja_origen'] ?? '');

            if ($sistema === '') {
                $sistema = trim($fila['sistema'] ?? '');
            }

            if ($sistema === '') {
                $sistema = trim($fila['categoria'] ?? '');
            }

            if ($sistema === '') {
                $sistema = 'Sin clasificar';
            }

            $clave = normalizarTexto($sistema);

            if (!isset($sistemasMapa[$clave])) {
                $sistemasMapa[$clave] = [
                    'nombre' => $sistema,
                    'cantidad' => 0
                ];
            }

            $sistemasMapa[$clave]['cantidad']++;
        }
    }

    $sistemas = array_values(
        $sistemasMapa
    );
}


/* =========================================================
   OBTENER EQUIPOS SOLO CUANDO SE SELECCIONA SISTEMA
   La comparación se hace con texto normalizado en PHP para
   soportar tildes, mayúsculas y espacios especiales.
   ========================================================= */

if (
    $areaSeleccionada === 'automatizacion' &&
    $edificioSeleccionado !== '' &&
    $sistemaSeleccionado !== ''
) {

    $sqlEquipos = "
        SELECT
            id,
            sistema,
            categoria,
            hoja_origen,
            item,
            edificio,
            dispositivo,
            descripcion,
            tipo,
            codigo_activo,
            ubicacion,
            direccion,
            ip,
            mac,
            referencia,
            cantidad,
            detalles,
            estado
        FROM inventario_tecnico
        WHERE edificio IS NOT NULL
          AND TRIM(edificio) <> ''
        ORDER BY dispositivo ASC, item ASC
    ";

    $resultadoEquipos = $conn->query($sqlEquipos);
    $claveEdificioSeleccionado = claveEdificio($edificioSeleccionado);
    $claveSistemaSeleccionado = normalizarTexto($sistemaSeleccionado);

    if ($resultadoEquipos) {
        while ($fila = $resultadoEquipos->fetch_assoc()) {
            // El edificio debe coincidir con el seleccionado.
            if (claveEdificio($fila['edificio'] ?? '') !== $claveEdificioSeleccionado) {
                continue;
            }

            // Mantener la misma prioridad que usan las tarjetas.
            $sistemaRegistro = trim((string)($fila['hoja_origen'] ?? ''));
            if ($sistemaRegistro === '') {
                $sistemaRegistro = trim((string)($fila['sistema'] ?? ''));
            }
            if ($sistemaRegistro === '') {
                $sistemaRegistro = trim((string)($fila['categoria'] ?? ''));
            }
            if ($sistemaRegistro === '') {
                $sistemaRegistro = 'Sin clasificar';
            }

            if (normalizarTexto($sistemaRegistro) !== $claveSistemaSeleccionado) {
                continue;
            }

            $fila['sistema_visual'] = $sistemaRegistro;
            $equipos[] = $fila;
        }
    }
}


/* =========================================================
   OBTENER EQUIPOS AUDIOVISUALES
   ========================================================= */

if (
    $areaSeleccionada === 'audiovisuales' &&
    $edificioSeleccionado !== ''
) {

    $sqlEquipos = "
        SELECT
            id,
            codigo,
            nombre,
            categoria,
            marca,
            modelo,
            numero_serie,
            referencia,
            serial,
            tipo,
            edificio,
            piso,
            salon,
            estado,
            descripcion
        FROM equipos
        WHERE edificio IS NOT NULL
        AND TRIM(edificio) <> ''
        ORDER BY nombre ASC
    ";

    $resultadoEquipos = $conn->query($sqlEquipos);

    if ($resultadoEquipos) {

        while ($fila = $resultadoEquipos->fetch_assoc()) {

            if (
                claveEdificio($fila['edificio'] ?? '')
                !==
                claveEdificio($edificioSeleccionado)
            ) {
                continue;
            }

            if (
                $pisoSeleccionado !== '' &&
                normalizarTexto($fila['piso'] ?? '')
                !==
                normalizarTexto($pisoSeleccionado)
            ) {
                continue;
            }

            $equipos[] = $fila;
        }
    }
}


/* =========================================================
   NOMBRE DEL EDIFICIO ACTUAL
   ========================================================= */

$nombreEdificioActual =
    $edificioSeleccionado !== ''
        ? nombreEdificioVisual(
            $edificioSeleccionado
        )
        : '';


/* =========================================================
   NOMBRE DEL SISTEMA ACTUAL
   ========================================================= */

$nombreSistemaActual =
    $sistemaSeleccionado !== ''
        ? $sistemaSeleccionado
        : '';


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
        Ubicaciones | JaverStock
    </title>


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         FUENTES
    ====================================================== -->

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
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =================================================
           GENERAL
        ================================================= */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            background: #f5f7fb;
            color: #102a43;
        }


        /* =================================================
           CONTENIDO PRINCIPAL
        ================================================= */

        .contenido-ubicaciones {
            margin-left: 264px;
            padding: 30px 34px 50px;
            min-height: 100vh;
        }


        /* =================================================
           CABECERA
        ================================================= */

        .ubicaciones-header {
            background: linear-gradient(
                135deg,
                #062d59,
                #0a4c8f
            );

            border-radius: 22px;

            padding: 26px 30px;

            color: white;

            margin-bottom: 28px;

            box-shadow:
                0 14px 35px rgba(
                    0,
                    40,
                    85,
                    0.18
                );

            position: relative;

            overflow: hidden;
        }


        .ubicaciones-header::after {
            content: "";

            position: absolute;

            width: 240px;
            height: 240px;

            border-radius: 50%;

            background: rgba(
                255,
                255,
                255,
                0.08
            );

            right: -70px;
            top: -100px;
        }


        .ubicaciones-header-content {
            position: relative;
            z-index: 2;
        }


        .ubicaciones-header h1 {
            margin: 0 0 8px;
            font-size: 27px;
            font-weight: 800;
        }


        .ubicaciones-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }


        /* =================================================
           SELECTOR DE ÁREA
        ================================================= */

        .area-selector {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }


        .area-btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;

            padding: 12px 20px;

            border-radius: 13px;

            background: white;

            color: #0b2e59;

            font-size: 13px;
            font-weight: 800;

            border: 1px solid #dce5ef;

            box-shadow:
                0 5px 15px rgba(
                    0,
                    40,
                    85,
                    0.07
                );

            transition: 0.2s ease;
        }


        .area-btn:hover {
            transform: translateY(-2px);
            color: #0b2e59;
        }


        .area-btn.active {
            background: #0b2e59;
            color: white;
            border-color: #0b2e59;
        }


        .area-btn i {
            font-size: 15px;
        }


        /* =================================================
           TITULOS DE SECCIÓN
        ================================================= */

        .seccion-titulo {
            display: flex;
            align-items: center;
            gap: 11px;

            margin: 0 0 17px;

            color: #082f5f;

            font-size: 19px;

            font-weight: 800;
        }


        .seccion-titulo i {
            color: #0877c9;
        }


        /* =================================================
           TARJETAS DE EDIFICIOS
        ================================================= */

        .grid-edificios {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        220px,
                        1fr
                    )
                );

            gap: 18px;

            margin-bottom: 32px;
        }


        .edificio-card {
            position: relative;

            display: block;

            text-decoration: none;

            min-height: 150px;

            padding: 23px;

            border-radius: 19px;

            color: white;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #06366c,
                    #0c5da5
                );

            box-shadow:
                0 12px 24px rgba(
                    4,
                    45,
                    87,
                    0.16
                );

            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease;
        }


        /* =================================================
           COLORES DIFERENTES PARA CADA EDIFICIO
        ================================================= */

        .edificio-card.edificio-azul {
            background: linear-gradient(135deg, #0754a0, #1581d1);
        }

        .edificio-card.edificio-verde {
            background: linear-gradient(135deg, #087443, #19a56a);
        }

        .edificio-card.edificio-morado {
            background: linear-gradient(135deg, #6538a8, #9258d2);
        }

        .edificio-card.edificio-naranja {
            background: linear-gradient(135deg, #c65d0b, #ef8a24);
        }

        .edificio-card.edificio-rosa {
            background: linear-gradient(135deg, #b72e72, #e05a9a);
        }

        .edificio-card.edificio-turquesa {
            background: linear-gradient(135deg, #087d88, #16b2b5);
        }

        .edificio-card.edificio-rojo {
            background: linear-gradient(135deg, #b52c35, #e45b5f);
        }

        .edificio-card.edificio-amarillo {
            background: linear-gradient(135deg, #b77a00, #e2aa20);
        }


        .edificio-card:hover {
            transform: translateY(-5px);
            color: white;

            box-shadow:
                0 17px 32px rgba(
                    4,
                    45,
                    87,
                    0.24
                );
        }


        .edificio-card::after {
            content: "";

            position: absolute;

            width: 145px;
            height: 145px;

            border-radius: 50%;

            right: -48px;
            bottom: -65px;

            background: rgba(
                255,
                255,
                255,
                0.11
            );
        }


        .edificio-icon {
            width: 46px;
            height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 13px;

            background: rgba(
                255,
                255,
                255,
                0.17
            );

            margin-bottom: 18px;

            font-size: 19px;
        }


        .edificio-card h3 {
            margin: 0 0 7px;

            font-size: 17px;

            font-weight: 800;

            position: relative;
            z-index: 2;
        }


        .edificio-card p {
            margin: 0;

            font-size: 12px;

            font-weight: 600;

            opacity: 0.88;

            position: relative;
            z-index: 2;
        }


        /* =================================================
           BOTÓN REGRESAR
        ================================================= */

        .btn-regresar {
            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 10px 15px;

            border-radius: 11px;

            background: white;

            border: 1px solid #dbe5ef;

            color: #0b4f8a;

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;

            margin-bottom: 22px;

            box-shadow:
                0 5px 13px rgba(
                    0,
                    30,
                    70,
                    0.06
                );
        }


        .btn-regresar:hover {
            color: #073763;
            background: #f6faff;
        }


        /* =================================================
           ENCABEZADO EDIFICIO
        ================================================= */

        .edificio-heading {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 23px;

            flex-wrap: wrap;
        }


        .edificio-heading-left {
            display: flex;

            align-items: center;

            gap: 14px;
        }


        .edificio-heading-icon {
            width: 51px;
            height: 51px;

            border-radius: 15px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #e7f2fc;

            color: #0866a9;

            font-size: 20px;
        }


        .edificio-heading h2 {
            margin: 0;

            font-size: 23px;

            font-weight: 800;

            color: #073763;
        }


        .edificio-heading p {
            margin: 4px 0 0;

            color: #718096;

            font-size: 12px;

            font-weight: 600;
        }


        /* =================================================
           SISTEMAS
        ================================================= */

        .grid-sistemas {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(
                        280px,
                        1fr
                    )
                );

            gap: 16px;

            margin-bottom: 32px;
        }


        .sistema-card {
            position: relative;

            border: none;

            width: 100%;

            min-height: 142px;

            padding: 20px;

            border-radius: 18px;

            text-align: left;

            color: white;

            cursor: pointer;

            overflow: hidden;

            box-shadow:
                0 12px 23px rgba(
                    20,
                    30,
                    70,
                    0.15
                );

            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease;
        }


        .sistema-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 17px 28px rgba(
                    20,
                    30,
                    70,
                    0.22
                );
        }


        .sistema-card::after {
            content: "";

            position: absolute;

            width: 130px;
            height: 130px;

            border-radius: 50%;

            right: -54px;
            bottom: -63px;

            background: rgba(
                255,
                255,
                255,
                0.14
            );
        }


        .sistema-torniquetes {
            background:
                linear-gradient(
                    135deg,
                    #9229a8,
                    #b33fc1
                );
        }


        .sistema-control {
            background:
                linear-gradient(
                    135deg,
                    #6340b5,
                    #7953c4
                );
        }


        .sistema-cctv {
            background:
                linear-gradient(
                    135deg,
                    #1468a3,
                    #2584c2
                );
        }


        .sistema-incendio {
            background:
                linear-gradient(
                    135deg,
                    #bd3c3c,
                    #df5757
                );
        }


        .sistema-general {
            background:
                linear-gradient(
                    135deg,
                    #34536f,
                    #4c7295
                );
        }


        .sistema-icon {
            width: 45px;
            height: 45px;

            border-radius: 13px;

            background: rgba(
                255,
                255,
                255,
                0.18
            );

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 18px;

            margin-bottom: 17px;

            position: relative;

            z-index: 2;
        }


        .sistema-card h3 {
            margin: 0 0 6px;

            font-size: 14px;

            font-weight: 800;

            text-transform: uppercase;

            position: relative;

            z-index: 2;
        }


        .sistema-card p {
            margin: 0;

            font-size: 11px;

            font-weight: 700;

            opacity: 0.9;

            position: relative;

            z-index: 2;
        }


        /* =================================================
           INDICACIÓN DE CLICK
        ================================================= */

        .sistema-click {
            position: absolute;

            right: 15px;
            top: 17px;

            z-index: 3;

            font-size: 10px;

            font-weight: 800;

            padding: 6px 9px;

            border-radius: 20px;

            background: rgba(
                255,
                255,
                255,
                0.14
            );
        }


        /* =================================================
           PANEL DE INFORMACIÓN
           OCULTO HASTA SELECCIONAR SISTEMA
        ================================================= */

        .panel-informacion {
            margin-top: 25px;

            display: block;
        }


        .panel-informacion-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            flex-wrap: wrap;

            margin-bottom: 18px;
        }


        .panel-titulo {
            display: flex;

            align-items: center;

            gap: 11px;
        }


        .panel-titulo i {
            color: #0877c9;
        }


        .panel-titulo h2 {
            margin: 0;

            font-size: 19px;

            font-weight: 800;

            color: #073763;
        }


        .panel-titulo span {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            margin-left: 4px;

            padding: 6px 10px;

            border-radius: 20px;

            background: #e9f4fc;

            color: #0965a7;

            font-size: 10px;

            font-weight: 800;
        }


        /* =================================================
           BUSCADOR
        ================================================= */

        .buscador-equipos {
            background: white;

            border: 1px solid #e1e8f0;

            border-radius: 15px;

            padding: 13px 15px;

            margin-bottom: 18px;

            box-shadow:
                0 6px 18px rgba(
                    0,
                    40,
                    80,
                    0.05
                );
        }


        .buscador-contenido {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .buscador-icono {
            width: 37px;
            height: 37px;

            min-width: 37px;

            border-radius: 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #edf6fd;

            color: #0871b7;
        }


        .buscador-input {
            width: 100%;

            border: none;

            outline: none;

            background: transparent;

            font-family: 'Montserrat', sans-serif;

            font-size: 12px;

            color: #17324d;
        }


        .buscador-input::placeholder {
            color: #91a0b1;
        }


        /* =================================================
           GRID DE EQUIPOS PEQUEÑOS
        ================================================= */

        .grid-equipos {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(
                        275px,
                        1fr
                    )
                );

            gap: 14px;
        }


        /* =================================================
           TARJETA DE EQUIPO
        ================================================= */

        .equipo-card {
            background: white;

            border: 1px solid #e1e8ef;

            border-radius: 16px;

            overflow: hidden;

            cursor: pointer;

            box-shadow:
                0 6px 17px rgba(
                    0,
                    35,
                    70,
                    0.06
                );

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }


        .equipo-card:hover {
            transform: translateY(-3px);

            border-color: #b9d6ec;

            box-shadow:
                0 12px 25px rgba(
                    0,
                    45,
                    85,
                    0.11
                );
        }


        .equipo-top {
            display: flex;

            align-items: center;

            gap: 11px;

            padding: 14px 15px;

            border-bottom: 1px solid #edf1f5;
        }


        .equipo-icon {
            width: 39px;
            height: 39px;

            min-width: 39px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: #eaf4fd;

            color: #0767a9;

            font-size: 15px;
        }


        .equipo-titulo {
            min-width: 0;

            flex: 1;
        }


        .equipo-titulo h3 {
            margin: 0 0 3px;

            font-size: 13px;

            line-height: 1.35;

            font-weight: 800;

            color: #082f5f;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .equipo-codigo {
            display: inline-block;

            padding: 4px 7px;

            border-radius: 7px;

            background: #f2f6fa;

            color: #6b7b8c;

            font-size: 9px;

            font-weight: 800;
        }


        .estado-badge {
            display: inline-flex;

            align-items: center;

            padding: 5px 8px;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 800;

            text-transform: uppercase;

            white-space: nowrap;
        }


        .estado-operativo {
            background: #e7f7ed;
            color: #188447;
        }


        .estado-mantenimiento {
            background: #fff5d9;
            color: #9a7000;
        }


        .estado-danado {
            background: #ffe8e8;
            color: #b52d2d;
        }


        .estado-reparado {
            background: #e7f1ff;
            color: #2467a8;
        }


        .estado-general {
            background: #eef1f4;
            color: #667381;
        }


        .equipo-body {
            padding: 13px 15px;
        }


        .equipo-mini-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 8px;
        }


        .mini-dato {
            background: #f7f9fb;

            border-radius: 9px;

            padding: 9px 10px;

            min-width: 0;
        }


        .mini-dato-label {
            display: flex;

            align-items: center;

            gap: 5px;

            color: #7790a6;

            font-size: 8px;

            font-weight: 800;

            text-transform: uppercase;

            margin-bottom: 4px;
        }


        .mini-dato-label i {
            color: #1174b9;

            font-size: 8px;
        }


        .mini-dato-value {
            color: #163653;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .equipo-footer {
            padding: 10px 15px;

            background: #fbfcfd;

            border-top: 1px solid #edf1f5;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .ver-informacion {
            color: #086bb0;

            font-size: 9px;

            font-weight: 800;

            display: flex;

            align-items: center;

            gap: 6px;
        }


        .ver-informacion i {
            font-size: 9px;
        }


        /* =================================================
           SIN RESULTADOS
        ================================================= */

        .sin-resultados {
            background: white;

            border: 1px dashed #ccd8e4;

            border-radius: 16px;

            padding: 40px 20px;

            text-align: center;

            grid-column: 1 / -1;
        }


        .sin-resultados i {
            font-size: 30px;

            color: #b4c2cf;

            margin-bottom: 12px;
        }


        .sin-resultados h3 {
            margin: 0 0 6px;

            font-size: 15px;

            color: #365269;
        }


        .sin-resultados p {
            margin: 0;

            color: #8495a5;

            font-size: 11px;
        }


        /* =================================================
           MODAL
        ================================================= */

        .modal-ubicacion {
            position: fixed;

            inset: 0;

            z-index: 9999;

            display: none;

            align-items: center;

            justify-content: center;

            padding: 20px;

            background: rgba(
                3,
                25,
                48,
                0.63
            );

            backdrop-filter: blur(4px);
        }


        .modal-ubicacion.show {
            display: flex;
        }


        .modal-contenido {
            width: 100%;

            max-width: 720px;

            max-height: 90vh;

            overflow-y: auto;

            background: white;

            border-radius: 20px;

            box-shadow:
                0 25px 70px rgba(
                    0,
                    0,
                    0,
                    0.25
                );

            animation:
                modalEntrada 0.2s ease;
        }


        @keyframes modalEntrada {

            from {
                opacity: 0;
                transform: translateY(15px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }


        .modal-header-ubicacion {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding: 19px 22px;

            background:
                linear-gradient(
                    135deg,
                    #073763,
                    #0a609e
                );

            color: white;
        }


        .modal-header-info {
            display: flex;

            align-items: center;

            gap: 12px;

            min-width: 0;
        }


        .modal-header-icon {
            width: 43px;
            height: 43px;

            min-width: 43px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: rgba(
                255,
                255,
                255,
                0.16
            );
        }


        .modal-header-info h2 {
            margin: 0 0 4px;

            font-size: 16px;

            font-weight: 800;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .modal-header-info p {
            margin: 0;

            font-size: 10px;

            opacity: 0.82;
        }


        .modal-cerrar {
            width: 35px;
            height: 35px;

            border: none;

            border-radius: 10px;

            background: rgba(
                255,
                255,
                255,
                0.13
            );

            color: white;

            cursor: pointer;

            font-size: 15px;
        }


        .modal-cerrar:hover {
            background: rgba(
                255,
                255,
                255,
                0.23
            );
        }


        .modal-body-ubicacion {
            padding: 21px;
        }


        .modal-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 11px;
        }


        .modal-dato {
            padding: 12px 13px;

            background: #f7f9fb;

            border-radius: 11px;

            border: 1px solid #edf1f5;
        }


        .modal-dato.full {
            grid-column: 1 / -1;
        }


        .modal-dato-label {
            color: #72879a;

            font-size: 8px;

            font-weight: 800;

            text-transform: uppercase;

            margin-bottom: 5px;

            display: flex;

            align-items: center;

            gap: 6px;
        }


        .modal-dato-label i {
            color: #0870b6;
        }


        .modal-dato-value {
            color: #173b59;

            font-size: 11px;

            font-weight: 700;

            line-height: 1.45;

            word-break: break-word;
        }


        .modal-footer-ubicacion {
            display: flex;

            justify-content: flex-end;

            gap: 9px;

            padding: 14px 21px;

            border-top: 1px solid #edf1f5;

            background: #fbfcfd;
        }


        .btn-modal {
            border: none;

            border-radius: 9px;

            padding: 9px 14px;

            font-family: 'Montserrat', sans-serif;

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;
        }


        .btn-modal-cerrar {
            background: #edf1f5;

            color: #506273;
        }


        .btn-modal-detalle {
            background: #0b5f99;

            color: white;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 6px;
        }


        /* =================================================
           MENSAJE INICIAL
        ================================================= */

        .mensaje-seleccionar {
            background: white;

            border: 1px dashed #ccd9e5;

            border-radius: 18px;

            padding: 35px 25px;

            text-align: center;

            margin-top: 5px;
        }


        .mensaje-seleccionar-icono {
            width: 52px;
            height: 52px;

            margin: 0 auto 13px;

            border-radius: 15px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #edf6fd;

            color: #0870b6;

            font-size: 20px;
        }


        .mensaje-seleccionar h3 {
            margin: 0 0 6px;

            color: #24435e;

            font-size: 15px;

            font-weight: 800;
        }


        .mensaje-seleccionar p {
            margin: 0;

            color: #8293a3;

            font-size: 11px;

            font-weight: 600;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 1100px) {

            .contenido-ubicaciones {
                margin-left: 250px;
            }

        }


        @media (max-width: 850px) {

            .contenido-ubicaciones {
                margin-left: 0;
                padding: 20px 16px 40px;
            }

            .grid-edificios {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }

        }


        @media (max-width: 600px) {

            .ubicaciones-header {
                padding: 21px;
            }

            .ubicaciones-header h1 {
                font-size: 21px;
            }

            .grid-edificios,
            .grid-sistemas,
            .grid-equipos {
                grid-template-columns: 1fr;
            }

            .modal-grid {
                grid-template-columns: 1fr;
            }

            .modal-dato.full {
                grid-column: auto;
            }

        }

    </style>

</head>


<body>


<?php include("../includes/sidebar.php"); ?>


<div class="contenido-ubicaciones">


    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="ubicaciones-header">

        <div class="ubicaciones-header-content">

            <h1>
                <i class="fas fa-location-dot"></i>
                Ubicaciones
            </h1>

            <p>
                Consulta los sistemas y equipos organizados
                por edificio y ubicación.
            </p>

        </div>

    </div>


    <!-- =====================================================
         SELECTOR DE ÁREA
    ====================================================== -->

    <div class="area-selector">

        <a
            href="ubicaciones.php?area=audiovisuales"
            class="area-btn
            <?= $areaSeleccionada === 'audiovisuales'
                ? 'active'
                : ''; ?>"
        >

            <i class="fas fa-video"></i>

            Audiovisuales

        </a>


        <a
            href="ubicaciones.php?area=automatizacion"
            class="area-btn
            <?= $areaSeleccionada === 'automatizacion'
                ? 'active'
                : ''; ?>"
        >

            <i class="fas fa-gears"></i>

            Automatización

        </a>

    </div>


    <?php if ($edificioSeleccionado === ''): ?>


        <!-- =================================================
             EDIFICIOS
        ================================================== -->

        <div class="seccion-titulo">

            <i class="fas fa-building"></i>

            Edificios encontrados

        </div>


        <div class="grid-edificios">

            <?php if (empty($edificios)): ?>

                <div class="sin-resultados">

                    <i class="fas fa-building-circle-exclamation"></i>

                    <h3>
                        No hay edificios registrados
                    </h3>

                    <p>
                        No encontramos ubicaciones
                        para esta área.
                    </p>

                </div>

            <?php else: ?>


                <?php foreach ($edificios as $edificio): ?>


                    <a
                        class="edificio-card edificio-<?= e(
                            claseColorEdificio(
                                $edificio['original']
                            )
                        ); ?>"
                        href="ubicaciones.php?area=<?= e($areaSeleccionada); ?>&edificio=<?= rawurlencode($edificio['original']); ?>"
                    >

                        <div class="edificio-icon">

                            <i
                                class="fas <?= e(
                                    iconoEdificio(
                                        $edificio['original']
                                    )
                                ); ?>"
                            ></i>

                        </div>


                        <h3>
                            <?= e(
                                $edificio['nombre']
                            ); ?>
                        </h3>


                        <p>

                            <i class="fas fa-layer-group"></i>

                            <?= (int)$edificio['cantidad']; ?>

                            registros

                        </p>

                    </a>


                <?php endforeach; ?>


            <?php endif; ?>

        </div>


    <?php else: ?>


        <!-- =================================================
             REGRESAR
        ================================================== -->

        <a
            href="ubicaciones.php?area=<?= e($areaSeleccionada); ?>"
            class="btn-regresar"
        >

            <i class="fas fa-arrow-left"></i>

            Volver a edificios

        </a>


        <!-- =================================================
             ENCABEZADO DEL EDIFICIO
        ================================================== -->

        <div class="edificio-heading">

            <div class="edificio-heading-left">

                <div class="edificio-heading-icon">

                    <i
                        class="fas <?= e(
                            iconoEdificio(
                                $edificioSeleccionado
                            )
                        ); ?>"
                    ></i>

                </div>


                <div>

                    <h2>
                        <?= e(
                            $nombreEdificioActual
                        ); ?>
                    </h2>

                    <p>
                        Área:
                        <?= $areaSeleccionada === 'automatizacion'
                            ? 'Automatización'
                            : 'Audiovisuales'; ?>
                    </p>

                </div>

            </div>

        </div>


        <?php if ($areaSeleccionada === 'automatizacion'): ?>


            <!-- =============================================
                 SISTEMAS ENCONTRADOS
            ============================================== -->

            <div class="seccion-titulo">

                <i class="fas fa-layer-group"></i>

                Sistemas encontrados

            </div>


            <?php if (!empty($sistemas)): ?>


                <div class="grid-sistemas">

                    <?php foreach ($sistemas as $sistema): ?>


                        <button
                            type="button"
                            class="sistema-card <?= e(
                                claseSistema(
                                    $sistema['nombre']
                                )
                            ); ?>"
                            onclick="seleccionarSistema(
                                <?= htmlspecialchars(
                                    json_encode(
                                        $sistema['nombre'],
                                        JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            )"
                        >

                            <span class="sistema-click">
                                Ver equipos
                            </span>


                            <div class="sistema-icon">

                                <i
                                    class="fas <?= e(
                                        iconoSistema(
                                            $sistema['nombre']
                                        )
                                    ); ?>"
                                ></i>

                            </div>


                            <h3>
                                <?= e(
                                    $sistema['nombre']
                                ); ?>
                            </h3>


                            <p>

                                <i class="fas fa-cubes"></i>

                                <?= (int)$sistema['cantidad']; ?>

                                equipos

                            </p>

                        </button>


                    <?php endforeach; ?>

                </div>


            <?php else: ?>


                <div class="sin-resultados">

                    <i class="fas fa-layer-group"></i>

                    <h3>
                        No se encontraron sistemas
                    </h3>

                    <p>
                        Este edificio todavía no tiene
                        sistemas de automatización registrados.
                    </p>

                </div>


            <?php endif; ?>


            <?php if ($sistemaSeleccionado !== ''): ?>


                <!-- =========================================
                     INFORMACIÓN DEL SISTEMA
                ========================================== -->

                <div
                    class="panel-informacion"
                    id="panelInformacion"
                >


                    <div class="panel-informacion-header">

                        <div class="panel-titulo">

                            <i
                                class="fas <?= e(
                                    iconoSistema(
                                        $sistemaSeleccionado
                                    )
                                ); ?>"
                            ></i>

                            <h2>
                                <?= e(
                                    $sistemaSeleccionado
                                ); ?>
                            </h2>

                            <span>

                                <i class="fas fa-cubes"></i>

                                <?= count($equipos); ?>

                            </span>

                        </div>

                    </div>


                    <!-- =====================================
                         BUSCADOR
                    ====================================== -->

                    <div class="buscador-equipos">

                        <div class="buscador-contenido">

                            <div class="buscador-icono">

                                <i class="fas fa-search"></i>

                            </div>


                            <input
                                type="text"
                                id="buscarEquipos"
                                class="buscador-input"
                                placeholder="Buscar por equipo, código, IP, MAC, ubicación, dirección, referencia..."
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    <!-- =====================================
                         TARJETAS DE EQUIPOS
                    ====================================== -->

                    <div
                        class="grid-equipos"
                        id="gridEquipos"
                    >


                        <?php if (!empty($equipos)): ?>


                            <?php foreach ($equipos as $equipo): ?>


                                <?php

                                $nombreEquipo =
                                    trim(
                                        $equipo['dispositivo']
                                        ??
                                        $equipo['item']
                                        ??
                                        $equipo['descripcion']
                                        ??
                                        'Equipo'
                                    );

                                if (
                                    $nombreEquipo === ''
                                ) {
                                    $nombreEquipo =
                                        'Equipo sin nombre';
                                }


                                $codigo =
                                    trim(
                                        $equipo['codigo_activo']
                                        ??
                                        $equipo['item']
                                        ??
                                        ''
                                    );


                                $estado =
                                    trim(
                                        $equipo['estado']
                                        ??
                                        'Registrado'
                                    );

                                ?>


                                <article
                                    class="equipo-card"
                                    tabindex="0"
                                    role="button"

                                    data-search="
                                        <?= e(
                                            implode(
                                                ' ',
                                                [
                                                    $nombreEquipo,
                                                    $codigo,
                                                    $equipo['tipo'] ?? '',
                                                    $equipo['edificio'] ?? '',
                                                    $equipo['ubicacion'] ?? '',
                                                    $equipo['direccion'] ?? '',
                                                    $equipo['ip'] ?? '',
                                                    $equipo['mac'] ?? '',
                                                    $equipo['referencia'] ?? '',
                                                    $equipo['descripcion'] ?? '',
                                                    $equipo['detalles'] ?? '',
                                                    $equipo['categoria'] ?? ''
                                                ]
                                            )
                                        ); ?>
                                    "

                                    onclick="abrirModalEquipo(
                                        <?= htmlspecialchars(
                                            json_encode(
                                                $equipo,
                                                JSON_UNESCAPED_UNICODE |
                                                JSON_UNESCAPED_SLASHES
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    )"
                                >


                                    <!-- =================================
                                         CABECERA TARJETA
                                    ================================== -->

                                    <div class="equipo-top">

                                        <div class="equipo-icon">

                                            <i
                                                class="fas <?= e(
                                                    iconoSistema(
                                                        $sistemaSeleccionado
                                                    )
                                                ); ?>"
                                            ></i>

                                        </div>


                                        <div class="equipo-titulo">

                                            <h3
                                                title="<?= e(
                                                    $nombreEquipo
                                                ); ?>"
                                            >

                                                <?= e(
                                                    $nombreEquipo
                                                ); ?>

                                            </h3>


                                            <?php if ($codigo !== ''): ?>

                                                <span class="equipo-codigo">

                                                    <i class="fas fa-barcode"></i>

                                                    <?= e(
                                                        $codigo
                                                    ); ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <span
                                            class="estado-badge <?= e(
                                                claseEstado(
                                                    $estado
                                                )
                                            ); ?>"
                                        >

                                            <?= e(
                                                $estado
                                            ); ?>

                                        </span>

                                    </div>


                                    <!-- =================================
                                         DATOS PEQUEÑOS
                                    ================================== -->

                                    <div class="equipo-body">

                                        <div class="equipo-mini-grid">


                                            <div class="mini-dato">

                                                <div class="mini-dato-label">

                                                    <i class="fas fa-tag"></i>

                                                    Tipo

                                                </div>

                                                <div
                                                    class="mini-dato-value"
                                                    title="<?= e(
                                                        $equipo['tipo'] ?? ''
                                                    ); ?>"
                                                >

                                                    <?= e(
                                                        $equipo['tipo']
                                                        ??
                                                        'No especificado'
                                                    ); ?>

                                                </div>

                                            </div>


                                            <div class="mini-dato">

                                                <div class="mini-dato-label">

                                                    <i class="fas fa-location-dot"></i>

                                                    Ubicación

                                                </div>

                                                <div
                                                    class="mini-dato-value"
                                                    title="<?= e(
                                                        $equipo['ubicacion'] ?? ''
                                                    ); ?>"
                                                >

                                                    <?= e(
                                                        $equipo['ubicacion']
                                                        ??
                                                        'No registrada'
                                                    ); ?>

                                                </div>

                                            </div>


                                            <div class="mini-dato">

                                                <div class="mini-dato-label">

                                                    <i class="fas fa-building"></i>

                                                    Edificio

                                                </div>

                                                <div
                                                    class="mini-dato-value"
                                                    title="<?= e(
                                                        $equipo['edificio'] ?? ''
                                                    ); ?>"
                                                >

                                                    <?= e(
                                                        $equipo['edificio']
                                                        ??
                                                        'No registrado'
                                                    ); ?>

                                                </div>

                                            </div>


                                            <div class="mini-dato">

                                                <div class="mini-dato-label">

                                                    <i class="fas fa-route"></i>

                                                    Dirección

                                                </div>

                                                <div
                                                    class="mini-dato-value"
                                                    title="<?= e(
                                                        $equipo['direccion'] ?? ''
                                                    ); ?>"
                                                >

                                                    <?= e(
                                                        $equipo['direccion']
                                                        ??
                                                        'No registrada'
                                                    ); ?>

                                                </div>

                                            </div>


                                            <?php if (
                                                !empty(
                                                    $equipo['ip']
                                                )
                                            ): ?>

                                                <div class="mini-dato">

                                                    <div class="mini-dato-label">

                                                        <i class="fas fa-network-wired"></i>

                                                        IP

                                                    </div>

                                                    <div
                                                        class="mini-dato-value"
                                                        title="<?= e(
                                                            $equipo['ip']
                                                        ); ?>"
                                                    >

                                                        <?= e(
                                                            $equipo['ip']
                                                        ); ?>

                                                    </div>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty(
                                                    $equipo['mac']
                                                )
                                            ): ?>

                                                <div class="mini-dato">

                                                    <div class="mini-dato-label">

                                                        <i class="fas fa-ethernet"></i>

                                                        MAC

                                                    </div>

                                                    <div
                                                        class="mini-dato-value"
                                                        title="<?= e(
                                                            $equipo['mac']
                                                        ); ?>"
                                                    >

                                                        <?= e(
                                                            $equipo['mac']
                                                        ); ?>

                                                    </div>

                                                </div>

                                            <?php endif; ?>


                                        </div>

                                    </div>


                                    <!-- =================================
                                         PIE
                                    ================================== -->

                                    <div class="equipo-footer">

                                        <span class="ver-informacion">

                                            <i class="fas fa-circle-info"></i>

                                            Ver información

                                        </span>


                                        <i
                                            class="fas fa-chevron-right"
                                            style="
                                                color:#8ba0b3;
                                                font-size:9px;
                                            "
                                        ></i>

                                    </div>


                                </article>


                            <?php endforeach; ?>


                            <div
                                class="sin-resultados"
                                id="sinResultadosBusqueda"
                                style="display:none;"
                            >

                                <i class="fas fa-search"></i>

                                <h3>
                                    No encontramos resultados
                                </h3>

                                <p>
                                    Intenta con otro nombre,
                                    código, IP, MAC o ubicación.
                                </p>

                            </div>


                        <?php else: ?>


                            <div class="sin-resultados">

                                <i class="fas fa-box-open"></i>

                                <h3>
                                    No hay equipos en este sistema
                                </h3>

                                <p>
                                    No encontramos registros
                                    para este sistema.
                                </p>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>


            <?php else: ?>


                <!-- =========================================
                     MENSAJE INICIAL
                     NO MUESTRA EQUIPOS TODAVÍA
                ========================================== -->

                <div class="mensaje-seleccionar">

                    <div class="mensaje-seleccionar-icono">

                        <i class="fas fa-hand-pointer"></i>

                    </div>


                    <h3>
                        Selecciona un sistema
                    </h3>


                    <p>
                        Haz clic en una tarjeta de sistema
                        para consultar sus equipos.
                    </p>

                </div>


            <?php endif; ?>


        <?php else: ?>


            <!-- =============================================
                 AUDIOVISUALES
            ============================================== -->

            <div class="seccion-titulo">

                <i class="fas fa-video"></i>

                Equipos audiovisuales

            </div>


            <?php if (!empty($pisos)): ?>


                <div class="area-selector">

                    <a
                        href="ubicaciones.php?area=audiovisuales&edificio=<?= urlencode($edificioSeleccionado); ?>"
                        class="area-btn
                        <?= $pisoSeleccionado === ''
                            ? 'active'
                            : ''; ?>"
                    >

                        Todos los pisos

                    </a>


                    <?php foreach ($pisos as $piso): ?>

                        <a
                            href="ubicaciones.php?area=audiovisuales&edificio=<?= urlencode($edificioSeleccionado); ?>&piso=<?= urlencode($piso); ?>"
                            class="area-btn
                            <?= $pisoSeleccionado === $piso
                                ? 'active'
                                : ''; ?>"
                        >

                            <i class="fas fa-layer-group"></i>

                            <?= e($piso); ?>

                        </a>

                    <?php endforeach; ?>

                </div>


            <?php endif; ?>


            <div class="buscador-equipos">

                <div class="buscador-contenido">

                    <div class="buscador-icono">

                        <i class="fas fa-search"></i>

                    </div>


                    <input
                        type="text"
                        id="buscarEquipos"
                        class="buscador-input"
                        placeholder="Buscar equipo, código, marca, modelo, salón o ubicación..."
                        autocomplete="off"
                    >

                </div>

            </div>


            <div
                class="grid-equipos"
                id="gridEquipos"
            >


                <?php if (!empty($equipos)): ?>


                    <?php foreach ($equipos as $equipo): ?>


                        <?php

                        $nombreEquipo =
                            trim(
                                $equipo['nombre']
                                ??
                                'Equipo audiovisual'
                            );

                        $codigo =
                            trim(
                                $equipo['codigo']
                                ?? ''
                            );

                        $estado =
                            trim(
                                $equipo['estado']
                                ??
                                'Registrado'
                            );

                        ?>


                        <article
                            class="equipo-card"
                            tabindex="0"
                            role="button"

                            data-search="
                                <?= e(
                                    implode(
                                        ' ',
                                        [
                                            $nombreEquipo,
                                            $codigo,
                                            $equipo['categoria'] ?? '',
                                            $equipo['marca'] ?? '',
                                            $equipo['modelo'] ?? '',
                                            $equipo['numero_serie'] ?? '',
                                            $equipo['referencia'] ?? '',
                                            $equipo['serial'] ?? '',
                                            $equipo['tipo'] ?? '',
                                            $equipo['edificio'] ?? '',
                                            $equipo['piso'] ?? '',
                                            $equipo['salon'] ?? '',
                                            $equipo['descripcion'] ?? ''
                                        ]
                                    )
                                ); ?>
                            "

                            onclick="abrirModalEquipo(
                                <?= htmlspecialchars(
                                    json_encode(
                                        $equipo,
                                        JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            )"
                        >


                            <div class="equipo-top">

                                <div class="equipo-icon">

                                    <i class="fas fa-video"></i>

                                </div>


                                <div class="equipo-titulo">

                                    <h3
                                        title="<?= e(
                                            $nombreEquipo
                                        ); ?>"
                                    >

                                        <?= e(
                                            $nombreEquipo
                                        ); ?>

                                    </h3>


                                    <?php if (
                                        $codigo !== ''
                                    ): ?>

                                        <span class="equipo-codigo">

                                            <i class="fas fa-barcode"></i>

                                            <?= e(
                                                $codigo
                                            ); ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <span
                                    class="estado-badge <?= e(
                                        claseEstado(
                                            $estado
                                        )
                                    ); ?>"
                                >

                                    <?= e(
                                        $estado
                                    ); ?>

                                </span>

                            </div>


                            <div class="equipo-body">

                                <div class="equipo-mini-grid">


                                    <div class="mini-dato">

                                        <div class="mini-dato-label">

                                            <i class="fas fa-tag"></i>

                                            Marca

                                        </div>

                                        <div
                                            class="mini-dato-value"
                                            title="<?= e(
                                                $equipo['marca'] ?? ''
                                            ); ?>"
                                        >

                                            <?= e(
                                                $equipo['marca']
                                                ??
                                                'No registrada'
                                            ); ?>

                                        </div>

                                    </div>


                                    <div class="mini-dato">

                                        <div class="mini-dato-label">

                                            <i class="fas fa-cube"></i>

                                            Modelo

                                        </div>

                                        <div
                                            class="mini-dato-value"
                                            title="<?= e(
                                                $equipo['modelo'] ?? ''
                                            ); ?>"
                                        >

                                            <?= e(
                                                $equipo['modelo']
                                                ??
                                                'No registrado'
                                            ); ?>

                                        </div>

                                    </div>


                                    <div class="mini-dato">

                                        <div class="mini-dato-label">

                                            <i class="fas fa-building"></i>

                                            Edificio

                                        </div>

                                        <div
                                            class="mini-dato-value"
                                            title="<?= e(
                                                $equipo['edificio'] ?? ''
                                            ); ?>"
                                        >

                                            <?= e(
                                                $equipo['edificio']
                                                ??
                                                'No registrado'
                                            ); ?>

                                        </div>

                                    </div>


                                    <div class="mini-dato">

                                        <div class="mini-dato-label">

                                            <i class="fas fa-door-open"></i>

                                            Salón

                                        </div>

                                        <div
                                            class="mini-dato-value"
                                            title="<?= e(
                                                $equipo['salon'] ?? ''
                                            ); ?>"
                                        >

                                            <?= e(
                                                $equipo['salon']
                                                ??
                                                'No registrado'
                                            ); ?>

                                        </div>

                                    </div>


                                </div>

                            </div>


                            <div class="equipo-footer">

                                <span class="ver-informacion">

                                    <i class="fas fa-circle-info"></i>

                                    Ver información

                                </span>


                                <i
                                    class="fas fa-chevron-right"
                                    style="
                                        color:#8ba0b3;
                                        font-size:9px;
                                    "
                                ></i>

                            </div>


                        </article>


                    <?php endforeach; ?>


                    <div
                        class="sin-resultados"
                        id="sinResultadosBusqueda"
                        style="display:none;"
                    >

                        <i class="fas fa-search"></i>

                        <h3>
                            No encontramos resultados
                        </h3>

                        <p>
                            Intenta con otro criterio de búsqueda.
                        </p>

                    </div>


                <?php else: ?>


                    <div class="sin-resultados">

                        <i class="fas fa-video-slash"></i>

                        <h3>
                            No hay equipos registrados
                        </h3>

                        <p>
                            No encontramos equipos audiovisuales
                            en esta ubicación.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        <?php endif; ?>


    <?php endif; ?>


</div>


<!-- =========================================================
     MODAL DE INFORMACIÓN DEL EQUIPO
========================================================= -->

<div
    class="modal-ubicacion"
    id="modalEquipo"
    aria-hidden="true"
>


    <div class="modal-contenido">


        <div class="modal-header-ubicacion">


            <div class="modal-header-info">


                <div
                    class="modal-header-icon"
                    id="modalIcono"
                >

                    <i class="fas fa-microchip"></i>

                </div>


                <div>

                    <h2 id="modalTitulo">
                        Información del equipo
                    </h2>

                    <p id="modalSubtitulo">
                        Detalle del registro
                    </p>

                </div>


            </div>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalEquipo()"
                aria-label="Cerrar"
            >

                <i class="fas fa-times"></i>

            </button>


        </div>


        <div
            class="modal-body-ubicacion"
            id="modalDatos"
        >

        </div>


        <div class="modal-footer-ubicacion">

            <a
                href="#"
                id="btnDetalleEquipo"
                class="btn-modal btn-modal-detalle"
                style="display:none;"
            >

                <i class="fas fa-up-right-from-square"></i>

                Abrir detalle

            </a>


            <button
                type="button"
                class="btn-modal btn-modal-cerrar"
                onclick="cerrarModalEquipo()"
            >

                Cerrar

            </button>

        </div>


    </div>

</div>


<script>


/* =========================================================
   SELECCIONAR SISTEMA
========================================================= */

function seleccionarSistema(sistema)
{
    const url = new URL(window.location.href);

    // Conservar edificio y área; seleccionar el sistema pulsado.
    url.searchParams.set('area', 'automatizacion');
    url.searchParams.set('sistema', sistema);

    // Evitar que un filtro de piso anterior interfiera.
    url.searchParams.delete('piso');

    window.location.href = url.toString();
}


/* =========================================================
   MODAL
========================================================= */

function abrirModalEquipo(equipo)
{

    const modal =
        document.getElementById(
            'modalEquipo'
        );


    const modalTitulo =
        document.getElementById(
            'modalTitulo'
        );


    const modalSubtitulo =
        document.getElementById(
            'modalSubtitulo'
        );


    const modalDatos =
        document.getElementById(
            'modalDatos'
        );


    const modalIcono =
        document.getElementById(
            'modalIcono'
        );


    const btnDetalle =
        document.getElementById(
            'btnDetalleEquipo'
        );


    const esAutomatizacion =
        <?= $areaSeleccionada === 'automatizacion'
            ? 'true'
            : 'false'; ?>;


    let nombre = 'Equipo';


    if (esAutomatizacion) {

        nombre =
            equipo.dispositivo ||
            equipo.item ||
            equipo.descripcion ||
            'Equipo de automatización';

    } else {

        nombre =
            equipo.nombre ||
            'Equipo audiovisual';

    }


    modalTitulo.textContent =
        nombre;


    if (esAutomatizacion) {

        modalSubtitulo.textContent =
            equipo.sistema_visual ||
            equipo.sistema ||
            equipo.hoja_origen ||
            'Automatización';

        modalIcono.innerHTML =
            '<i class="fas fa-microchip"></i>';

    } else {

        modalSubtitulo.textContent =
            'Equipo audiovisual';

        modalIcono.innerHTML =
            '<i class="fas fa-video"></i>';
    }


    modalDatos.innerHTML = '';


    if (esAutomatizacion) {

        agregarDatoModal(
            modalDatos,
            'fas fa-barcode',
            'Código',
            equipo.codigo_activo
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-layer-group',
            'Sistema',
            equipo.sistema_visual ||
            equipo.sistema ||
            equipo.hoja_origen
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-tags',
            'Categoría',
            equipo.categoria
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-tag',
            'Tipo',
            equipo.tipo
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-building',
            'Edificio',
            equipo.edificio
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-location-dot',
            'Ubicación',
            equipo.ubicacion
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-route',
            'Dirección',
            equipo.direccion
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-network-wired',
            'IP',
            equipo.ip
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-ethernet',
            'MAC',
            equipo.mac
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-bookmark',
            'Referencia',
            equipo.referencia
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-cubes',
            'Cantidad',
            equipo.cantidad
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-circle-check',
            'Estado',
            equipo.estado
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-align-left',
            'Descripción',
            equipo.descripcion,
            true
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-list',
            'Detalles',
            equipo.detalles,
            true
        );


        /*
         * En automatización dejamos disponible
         * el detalle antiguo cuando existe ID.
         */

        if (
            equipo.id &&
            String(equipo.id).trim() !== ''
        ) {

            btnDetalle.href =
                'detalle_equipo.php?id=' +
                encodeURIComponent(
                    equipo.id
                );

            btnDetalle.style.display =
                'inline-flex';

        } else {

            btnDetalle.style.display =
                'none';
        }


    } else {


        agregarDatoModal(
            modalDatos,
            'fas fa-barcode',
            'Código',
            equipo.codigo
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-tag',
            'Categoría',
            equipo.categoria
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-industry',
            'Marca',
            equipo.marca
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-cube',
            'Modelo',
            equipo.modelo
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-barcode',
            'Número de serie',
            equipo.numero_serie
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-bookmark',
            'Referencia',
            equipo.referencia
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-hashtag',
            'Serial',
            equipo.serial
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-tag',
            'Tipo',
            equipo.tipo
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-building',
            'Edificio',
            equipo.edificio
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-layer-group',
            'Piso',
            equipo.piso
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-door-open',
            'Salón',
            equipo.salon
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-circle-check',
            'Estado',
            equipo.estado
        );


        agregarDatoModal(
            modalDatos,
            'fas fa-align-left',
            'Descripción',
            equipo.descripcion,
            true
        );


        btnDetalle.style.display =
            'none';
    }


    modal.classList.add(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );


    document.body.style.overflow =
        'hidden';
}


/* =========================================================
   AGREGAR DATO AL MODAL
========================================================= */

function agregarDatoModal(
    contenedor,
    icono,
    etiqueta,
    valor,
    completo
)
{

    if (
        valor === null ||
        valor === undefined
    ) {
        return;
    }


    valor =
        String(valor).trim();


    if (valor === '') {
        return;
    }


    const dato =
        document.createElement(
            'div'
        );


    dato.className =
        'modal-dato';


    if (completo === true) {

        dato.classList.add(
            'full'
        );
    }


    const label =
        document.createElement(
            'div'
        );


    label.className =
        'modal-dato-label';


    label.innerHTML =
        '<i class="' +
        icono +
        '"></i> ' +
        escapeHtml(
            etiqueta
        );


    const contenido =
        document.createElement(
            'div'
        );


    contenido.className =
        'modal-dato-value';


    contenido.textContent =
        valor;


    dato.appendChild(
        label
    );


    dato.appendChild(
        contenido
    );


    contenedor.appendChild(
        dato
    );
}


/* =========================================================
   ESCAPAR HTML
========================================================= */

function escapeHtml(texto)
{

    const div =
        document.createElement(
            'div'
        );

    div.textContent =
        texto;

    return div.innerHTML;
}


/* =========================================================
   CERRAR MODAL
========================================================= */

function cerrarModalEquipo()
{

    const modal =
        document.getElementById(
            'modalEquipo'
        );


    modal.classList.remove(
        'show'
    );


    modal.setAttribute(
        'aria-hidden',
        'true'
    );


    document.body.style.overflow =
        '';
}


/* =========================================================
   CERRAR CON CLICK FUERA
========================================================= */

document.addEventListener(
    'click',
    function(event)
    {

        const modal =
            document.getElementById(
                'modalEquipo'
            );


        if (
            event.target === modal
        ) {

            cerrarModalEquipo();
        }

    }
);


/* =========================================================
   CERRAR CON ESC
========================================================= */

document.addEventListener(
    'keydown',
    function(event)
    {

        if (
            event.key === 'Escape'
        ) {

            cerrarModalEquipo();
        }

    }
);


/* =========================================================
   ABRIR TARJETA CON ENTER
========================================================= */

document.addEventListener(
    'keydown',
    function(event)
    {

        if (
            event.key !== 'Enter' &&
            event.key !== ' '
        ) {
            return;
        }


        const tarjeta =
            event.target.closest(
                '.equipo-card'
            );


        if (!tarjeta) {
            return;
        }


        event.preventDefault();

        tarjeta.click();

    }
);


/* =========================================================
   BUSCADOR DE EQUIPOS
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function()
    {

        const buscador =
            document.getElementById(
                'buscarEquipos'
            );


        const grid =
            document.getElementById(
                'gridEquipos'
            );


        const sinResultados =
            document.getElementById(
                'sinResultadosBusqueda'
            );


        if (
            !buscador ||
            !grid
        ) {
            return;
        }


        buscador.addEventListener(
            'input',
            function()
            {

                const texto =
                    this.value
                        .toLowerCase()
                        .trim();


                const tarjetas =
                    grid.querySelectorAll(
                        '.equipo-card'
                    );


                let visibles = 0;


                tarjetas.forEach(
                    function(tarjeta)
                    {

                        const contenido =
                            (
                                tarjeta.dataset.search
                                || ''
                            ).toLowerCase();


                        if (
                            texto === '' ||
                            contenido.includes(
                                texto
                            )
                        ) {

                            tarjeta.style.display =
                                '';

                            visibles++;

                        } else {

                            tarjeta.style.display =
                                'none';
                        }

                    }
                );


                if (sinResultados) {

                    if (
                        texto !== '' &&
                        visibles === 0
                    ) {

                        sinResultados.style.display =
                            'block';

                    } else {

                        sinResultados.style.display =
                            'none';
                    }
                }

            }
        );
        );

    }
);


</script>


</body>

</html>