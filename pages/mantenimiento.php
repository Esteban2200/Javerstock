<?php
/* =========================================================
   JAVERSTOCK
   MÓDULO DE MANTENIMIENTO
   DISEÑO MODERNO - TARJETAS
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('ver_mantenimiento');


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

if (!in_array($areaSeleccionada, $areasPermitidas, true)) {
    $areaSeleccionada = 'audiovisuales';
}


/* =========================================================
   INFORMACIÓN DEL ÁREA
========================================================= */

if ($areaSeleccionada === 'automatizacion') {

    $tituloArea = 'Automatización';
    $iconoArea = 'fa-gears';

} else {

    $tituloArea = 'Audiovisuales';
    $iconoArea = 'fa-video';
}


/* =========================================================
   MENSAJES
========================================================= */

$mensaje = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

$errorConsulta = '';


/* =========================================================
   FUNCIONES
========================================================= */

function limpiarTexto($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatearFecha($fecha)
{
    if (empty($fecha)) {
        return 'Pendiente';
    }

    $timestamp = strtotime($fecha);

    if (!$timestamp) {
        return $fecha;
    }

    return date('d/m/Y', $timestamp);
}


function obtenerIconoTipo($tipo)
{
    $tipo = strtolower(trim((string)$tipo));

    if (strpos($tipo, 'prevent') !== false) {
        return 'fa-shield-halved';
    }

    if (strpos($tipo, 'correct') !== false) {
        return 'fa-screwdriver-wrench';
    }

    if (strpos($tipo, 'calibr') !== false) {
        return 'fa-sliders';
    }

    if (
        strpos($tipo, 'limpieza') !== false ||
        strpos($tipo, 'limpiar') !== false
    ) {
        return 'fa-broom';
    }

    return 'fa-screwdriver-wrench';
}


function obtenerEstadoMantenimiento($mantenimiento)
{
    if (
        empty($mantenimiento['fecha_fin']) ||
        $mantenimiento['fecha_fin'] === '0000-00-00'
    ) {
        return 'activo';
    }

    return 'finalizado';
}


function obtenerTextoEstado($mantenimiento)
{
    if (
        empty($mantenimiento['fecha_fin']) ||
        $mantenimiento['fecha_fin'] === '0000-00-00'
    ) {
        return 'En mantenimiento';
    }

    return 'Finalizado';
}


function valorMantenimiento(
    $fila,
    $campo,
    $default = ''
) {
    return isset($fila[$campo])
        ? $fila[$campo]
        : $default;
}


/* =========================================================
   CONSULTAR TODOS LOS EQUIPOS
   IMPORTANTE:
   NO SE FILTRAN POR ÁREA.
   EL MODAL PUEDE SELECCIONAR CUALQUIER EQUIPO.
========================================================= */

$equipos = [];

$sqlEquipos = "
    SELECT
        e.id,
        e.codigo,
        e.nombre,
        e.marca,
        e.referencia,
        e.area,
        e.estado,
        e.edificio,
        e.piso,
        e.salon
    FROM equipos e
    ORDER BY
        e.codigo ASC,
        e.nombre ASC
";

$resultEquipos = $conn->query($sqlEquipos);

if ($resultEquipos) {

    while ($fila = $resultEquipos->fetch_assoc()) {
        $equipos[] = $fila;
    }
}


/* =========================================================
   CONSULTAR MANTENIMIENTOS
========================================================= */

$mantenimientos = [];

$sqlMantenimientos = "
    SELECT
        m.*,

        e.codigo AS equipo_codigo,
        e.nombre AS equipo_nombre,
        e.marca AS equipo_marca,
        e.referencia AS equipo_referencia,
        e.area AS equipo_area,
        e.estado AS equipo_estado,
        e.edificio AS equipo_edificio,
        e.piso AS equipo_piso,
        e.salon AS equipo_salon

    FROM mantenimientos m

    LEFT JOIN equipos e
        ON e.id = m.equipo_id

    WHERE LOWER(TRIM(COALESCE(e.area, ''))) = ?

    ORDER BY

        CASE
            WHEN m.fecha_fin IS NULL
                 OR m.fecha_fin = '0000-00-00'
            THEN 0
            ELSE 1
        END ASC,

        m.fecha_inicio DESC,
        m.id DESC
";


$stmtMantenimiento = $conn->prepare(
    $sqlMantenimientos
);


if ($stmtMantenimiento) {

    $stmtMantenimiento->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmtMantenimiento->execute()) {

        $resultado =
            $stmtMantenimiento->get_result();

        while (
            $fila = $resultado->fetch_assoc()
        ) {

            $mantenimientos[] = $fila;
        }

    } else {

        $errorConsulta =
            $stmtMantenimiento->error;
    }

    $stmtMantenimiento->close();

} else {

    $errorConsulta = $conn->error;
}


/* =========================================================
   ESTADÍSTICAS
========================================================= */

$totalMantenimientos =
    count($mantenimientos);

$mantenimientosActivos = 0;

$mantenimientosFinalizados = 0;

$mantenimientosHoy = 0;

$hoy = date('Y-m-d');


foreach ($mantenimientos as $mantenimiento) {

    $fechaFin =
        valorMantenimiento(
            $mantenimiento,
            'fecha_fin'
        );


    if (
        empty($fechaFin) ||
        $fechaFin === '0000-00-00'
    ) {

        $mantenimientosActivos++;

    } else {

        $mantenimientosFinalizados++;
    }


    $fechaInicio =
        valorMantenimiento(
            $mantenimiento,
            'fecha_inicio'
        );


    if (
        !empty($fechaInicio) &&
        date(
            'Y-m-d',
            strtotime($fechaInicio)
        ) === $hoy
    ) {

        $mantenimientosHoy++;
    }
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

    <title>
        Mantenimiento | JaverStock
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         FUENTE
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


<style>

/* =========================================================
   GENERAL
========================================================= */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background:
        linear-gradient(
            135deg,
            #f4f7fb 0%,
            #edf2f8 100%
        );

    font-family: 'Montserrat', sans-serif;

    color: #1f2937;
}


/* =========================================================
   CONTENIDO PRINCIPAL
========================================================= */

.main-content {

    min-height: 100vh;

    margin-left: 240px;

    width: calc(100% - 240px);

    min-width: 0;

    overflow-x: hidden;
}


/* =========================================================
   CONTENEDOR PRINCIPAL
========================================================= */

.maintenance-wrapper {

    padding: 28px 30px 45px;
}


/* =========================================================
   ENCABEZADO PRINCIPAL
========================================================= */

.maintenance-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 25px;

    margin-bottom: 25px;
}


.title-area {

    display: flex;

    align-items: center;

    gap: 18px;
}


.title-icon {

    width: 64px;

    height: 64px;

    min-width: 64px;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #002855,
            #0065b3
        );

    color: #fff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

    box-shadow:
        0 10px 25px
        rgba(0,40,85,.20);
}


.title-area h1 {

    margin: 0;

    font-size: 34px;

    font-weight: 800;

    color: #002855;

    line-height: 1.1;
}


.title-area p {

    margin: 7px 0 0;

    color: #66758a;

    font-size: 14px;

    font-weight: 500;
}


/* =========================================================
   BOTÓN REGISTRAR
========================================================= */

.btn-new-maintenance {

    border: none;

    border-radius: 13px;

    padding: 14px 21px;

    min-height: 48px;

    background:
        linear-gradient(
            135deg,
            #f5c400,
            #ffd52e
        );

    color: #002855;

    font-size: 12px;

    font-weight: 800;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    box-shadow:
        0 8px 20px
        rgba(245,196,0,.24);

    transition: .2s ease;

    white-space: nowrap;
}


.btn-new-maintenance i {

    font-size: 13px;
}


.btn-new-maintenance:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(245,196,0,.32);
}


/* =========================================================
   ERROR
========================================================= */

.database-error {

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #991b1b;

    border-radius: 13px;

    padding: 15px 18px;

    margin-bottom: 20px;

    font-size: 12px;

    line-height: 1.5;
}


.database-error strong {

    display: block;

    margin-bottom: 5px;

    font-size: 13px;
}


/* =========================================================
   ESTADÍSTICAS
========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;

    margin-bottom: 22px;
}


.stat-card {

    position: relative;

    overflow: hidden;

    min-height: 92px;

    background: #fff;

    border: 1px solid #dfe7f1;

    border-radius: 17px;

    padding: 18px 18px;

    display: flex;

    align-items: center;

    gap: 15px;

    box-shadow:
        0 7px 21px
        rgba(15,23,42,.065);

    transition: .2s ease;
}


.stat-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 11px 27px
        rgba(15,23,42,.10);
}


.stat-card::after {

    content: "";

    position: absolute;

    right: -25px;

    bottom: -30px;

    width: 82px;

    height: 82px;

    border-radius: 50%;

    background:
        rgba(0,80,158,.045);
}


.stat-icon {

    width: 49px;

    height: 49px;

    min-width: 49px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.stat-blue {

    background: #e8f1ff;

    color: #00509e;
}


.stat-orange {

    background: #fff0df;

    color: #d97706;
}


.stat-green {

    background: #e5f8ef;

    color: #14945d;
}


.stat-yellow {

    background: #fff7d6;

    color: #9a7500;
}


.stat-number {

    font-size: 27px;

    font-weight: 800;

    color: #002855;

    line-height: 1;
}


.stat-label {

    margin-top: 6px;

    font-size: 11px;

    font-weight: 600;

    color: #69778a;
}


/* =========================================================
   PANEL DE FILTROS
========================================================= */

.filters-panel {

    background: #fff;

    border: 1px solid #dfe7f1;

    border-radius: 18px;

    padding: 18px;

    margin-bottom: 27px;

    box-shadow:
        0 8px 23px
        rgba(15,23,42,.055);
}


/* =========================================================
   BOTONES DE ÁREA
========================================================= */

.area-tabs {

    display: flex;

    gap: 10px;

    margin-bottom: 16px;
}


.area-tab {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    text-decoration: none;

    padding: 11px 18px;

    min-height: 43px;

    border-radius: 11px;

    border: 1px solid #d5dfeb;

    color: #526174;

    background: #fff;

    font-size: 11px;

    font-weight: 700;

    transition: .2s;
}


.area-tab i {

    font-size: 12px;
}


.area-tab:hover {

    border-color: #00509e;

    color: #002855;
}


.area-tab.active {

    background:
        linear-gradient(
            135deg,
            #002855,
            #00509e
        );

    border-color: #002855;

    color: #fff;

    box-shadow:
        0 6px 14px
        rgba(0,40,85,.18);
}


/* =========================================================
   FILTROS
========================================================= */

.filters-row {

    display: grid;

    grid-template-columns:
        1fr 210px auto;

    gap: 11px;
}


.search-box {

    position: relative;
}


.search-box i {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    color: #8190a4;

    font-size: 14px;
}


.search-box input,
.filter-select {

    width: 100%;

    height: 46px;

    border: 1px solid #d4deea;

    border-radius: 11px;

    outline: none;

    background: #fff;

    font-size: 11px;

    color: #374151;
}


.search-box input {

    padding: 0 13px 0 42px;
}


.filter-select {

    padding: 0 12px;
}


.search-box input::placeholder {

    color: #8b98a9;
}


.search-box input:focus,
.filter-select:focus {

    border-color: #00509e;

    box-shadow:
        0 0 0 3px
        rgba(0,80,158,.08);
}


.btn-clear {

    height: 46px;

    border: none;

    border-radius: 11px;

    background: #f1f4f8;

    color: #465365;

    padding: 0 17px;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;
}


.btn-clear:hover {

    background: #e5eaf1;
}


/* =========================================================
   CABECERA DE MANTENIMIENTOS
========================================================= */

.cards-heading {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-left: 20px;

    margin-right: 10px;

    margin-bottom: 17px;
}


.cards-heading-left {

    display: flex;

    align-items: center;

    gap: 12px;
}


.cards-heading-icon {

    width: 43px;

    height: 43px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #e8f1ff,
            #dcecff
        );

    color: #00509e;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 17px;
}


.cards-heading h2 {

    margin: 0;

    color: #002855;

    font-size: 20px;

    font-weight: 800;

    line-height: 1.2;
}


.cards-heading small {

    color: #718096;

    font-size: 11px;

    font-weight: 500;
}


#contadorTarjetas {

    font-size: 11px;

    font-weight: 600;

    color: #718096;
}


/* =========================================================
   GRID TARJETAS
========================================================= */

.maintenance-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 20px;

    margin-left: 20px;

    margin-right: 10px;
}


/* =========================================================
   TARJETA
========================================================= */

.maintenance-card {

    position: relative;

    background: #fff;

    border: 1px solid #dce5ef;

    border-radius: 19px;

    overflow: hidden;

    box-shadow:
        0 9px 24px
        rgba(15,23,42,.07);

    transition:
        transform .22s ease,
        box-shadow .22s ease,
        border-color .22s ease;
}


.maintenance-card::before {

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
            #0072ce,
            #f5c400
        );
}


.maintenance-card:hover {

    transform: translateY(-4px);

    border-color: #c5d4e5;

    box-shadow:
        0 15px 34px
        rgba(15,23,42,.12);
}


/* =========================================================
   CABECERA TARJETA
========================================================= */

.card-header-modern {

    padding: 19px 20px 16px;

    border-bottom: 1px solid #e9eef4;

    background:
        linear-gradient(
            180deg,
            #ffffff 0%,
            #fbfcfe 100%
        );
}


.card-header-row {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 12px;
}


.type-icon {

    width: 48px;

    height: 48px;

    min-width: 48px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #eaf2ff,
            #dcecff
        );

    color: #00509e;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 11px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 800;
}


.status-badge i {

    font-size: 6px;
}


.status-badge.activo {

    color: #b45e00;

    background: #fff1df;
}


.status-badge.finalizado {

    color: #13784d;

    background: #e5f8ef;
}


.card-equipment {

    margin-top: 13px;
}


.card-equipment h3 {

    margin: 0;

    color: #002855;

    font-size: 18px;

    font-weight: 800;

    line-height: 1.35;
}


.card-code {

    margin-top: 6px;

    color: #66758a;

    font-size: 12px;

    font-weight: 700;
}


/* =========================================================
   CUERPO TARJETA
========================================================= */

.card-content {

    padding: 19px 20px;
}


.info-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 17px 16px;
}


.info-item {

    min-width: 0;
}


.info-label {

    display: flex;

    align-items: center;

    gap: 6px;

    color: #64748b;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;

    margin-bottom: 5px;
}


.info-label i {

    color: #6f8199;

    font-size: 10px;
}


.info-value {

    color: #27364a;

    font-size: 13px;

    font-weight: 600;

    word-break: break-word;

    line-height: 1.45;
}


/* =========================================================
   DIAGNÓSTICO
========================================================= */

.diagnostic {

    margin-top: 17px;

    padding: 12px 13px;

    border-radius: 11px;

    background: #f6f8fb;

    border-left: 4px solid #00509e;
}


.diagnostic-title {

    color: #64748b;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    margin-bottom: 6px;
}


.diagnostic-text {

    color: #374151;

    font-size: 12px;

    line-height: 1.55;

    font-weight: 500;
}


/* =========================================================
   ACCIONES
========================================================= */

.card-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    padding: 13px 20px;

    border-top: 1px solid #e9eef4;

    background: #fcfdff;
}


.card-action {

    border: none;

    border-radius: 9px;

    padding: 9px 12px;

    font-size: 11px;

    font-weight: 800;

    display: inline-flex;

    align-items: center;

    gap: 6px;

    cursor: pointer;

    transition: .18s;
}


.card-action:hover {

    transform: translateY(-1px);
}


.action-edit {

    background: #eaf2ff;

    color: #00509e;
}


.action-finish {

    background: #e5f8ef;

    color: #13784d;
}


.action-repair {

    background: #fff1df;

    color: #b45e00;
}


.action-delete {

    background: #ffe8eb;

    color: #c62828;
}


/* =========================================================
   ESTADO VACÍO
========================================================= */

.empty-state {

    grid-column: 1 / -1;

    background: #fff;

    border: 1px dashed #cbd6e4;

    border-radius: 19px;

    padding: 65px 25px;

    text-align: center;
}


.empty-icon {

    width: 78px;

    height: 78px;

    margin: 0 auto 16px;

    border-radius: 50%;

    background: #edf3fa;

    color: #9aaabd;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;
}


.empty-state h3 {

    margin: 0;

    color: #002855;

    font-size: 19px;

    font-weight: 800;
}


.empty-state p {

    margin: 8px 0 0;

    color: #8792a4;

    font-size: 12px;
}


/* =========================================================
   MODAL
========================================================= */

.modal-content {

    border: none;

    border-radius: 18px;

    overflow: hidden;

    box-shadow:
        0 25px 60px
        rgba(15,23,42,.20);
}


.modal-header {

    background:
        linear-gradient(
            135deg,
            #002855,
            #00509e
        );

    color: #fff;

    border: none;

    padding: 18px 22px;
}


.modal-title {

    font-size: 15px;

    font-weight: 800;
}


.modal-header .btn-close {

    filter: invert(1);
}


.modal-body {

    padding: 22px;

    max-height: 72vh;

    overflow-y: auto;
}


.form-label {

    font-size: 11px;

    font-weight: 800;

    color: #344054;

    margin-bottom: 6px;
}


.form-control,
.form-select {

    min-height: 43px;

    border-radius: 9px;

    border: 1px solid #d8e0ea;

    font-size: 11px;
}


textarea.form-control {

    min-height: 90px;
}


.form-control:focus,
.form-select:focus {

    border-color: #00509e;

    box-shadow:
        0 0 0 3px
        rgba(0,80,158,.07);
}


.modal-footer {

    border-top: 1px solid #edf1f6;

    padding: 14px 22px;
}


.btn-save {

    border: none;

    border-radius: 9px;

    background: #002855;

    color: #fff;

    padding: 11px 18px;

    font-size: 11px;

    font-weight: 800;
}


.btn-save:hover {

    background: #00509e;

    color: #fff;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1250px) {

    .maintenance-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 1000px) {

    .main-content {

        margin-left: 0;

        width: 100%;
    }

    .filters-row {

        grid-template-columns:
            1fr 180px auto;
    }
}


@media (max-width: 850px) {

    .maintenance-header {

        align-items: flex-start;

        flex-direction: column;
    }

    .btn-new-maintenance {

        width: auto;
    }

    .filters-row {

        grid-template-columns: 1fr;
    }

    .maintenance-grid {

        margin-left: 10px;

        margin-right: 5px;
    }

    .cards-heading {

        margin-left: 10px;

        margin-right: 5px;
    }
}


@media (max-width: 700px) {

    .maintenance-wrapper {

        padding: 18px 15px 35px;
    }

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .maintenance-grid {

        grid-template-columns: 1fr;

        margin-left: 0;

        margin-right: 0;
    }

    .cards-heading {

        margin-left: 0;

        margin-right: 0;
    }

    .title-area h1 {

        font-size: 29px;
    }
}


@media (max-width: 500px) {

    .stats-grid {

        grid-template-columns: 1fr;
    }

    .title-icon {

        width: 57px;

        height: 57px;

        min-width: 57px;
    }

    .title-area h1 {

        font-size: 25px;
    }

    .title-area p {

        font-size: 12px;
    }

    .area-tabs {

        flex-direction: column;
    }

    .area-tab {

        width: 100%;
    }

    .info-grid {

        grid-template-columns: 1fr;
    }

    .maintenance-wrapper {

        padding: 15px 12px 30px;
    }
}

</style>

</head>


<body>


<?php

include("../includes/sidebar.php");

?>


<div class="main-content">


<?php

include("../includes/header.php");

?>


<div class="maintenance-wrapper">


<!-- =====================================================
     ENCABEZADO
===================================================== -->

<div class="maintenance-header">

    <div class="title-area">

        <div class="title-icon">

            <i class="fa-solid fa-screwdriver-wrench"></i>

        </div>

        <div>

            <h1>
                Mantenimiento
            </h1>

            <p>
                Gestión y seguimiento del mantenimiento de equipos
            </p>

        </div>

    </div>


    <?php if (tienePermiso('crear_mantenimiento')): ?>

        <button
            type="button"
            class="btn-new-maintenance"
            data-bs-toggle="modal"
            data-bs-target="#modalNuevoMantenimiento"
        >

            <i class="fa-solid fa-plus"></i>

            Registrar mantenimiento

        </button>

    <?php endif; ?>

</div>


<!-- =====================================================
     ERROR DE CONSULTA
===================================================== -->

<?php if (!empty($errorConsulta)): ?>

    <div class="database-error">

        <strong>

            <i class="fa-solid fa-triangle-exclamation me-1"></i>

            Error al consultar los mantenimientos

        </strong>

        <?= limpiarTexto($errorConsulta) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     MENSAJES
===================================================== -->

<?php if ($mensaje === 'registrado'): ?>

    <div class="alert alert-success py-2">

        <i class="fa-solid fa-circle-check me-2"></i>

        El mantenimiento fue registrado correctamente.

    </div>

<?php endif; ?>


<?php if ($mensaje === 'finalizado'): ?>

    <div class="alert alert-success py-2">

        <i class="fa-solid fa-circle-check me-2"></i>

        El mantenimiento fue finalizado correctamente.

    </div>

<?php endif; ?>


<?php if ($mensaje === 'actualizado'): ?>

    <div class="alert alert-success py-2">

        <i class="fa-solid fa-circle-check me-2"></i>

        El mantenimiento fue actualizado correctamente.

    </div>

<?php endif; ?>


<?php if ($mensaje === 'eliminado'): ?>

    <div class="alert alert-success py-2">

        <i class="fa-solid fa-circle-check me-2"></i>

        El mantenimiento fue eliminado correctamente.

    </div>

<?php endif; ?>


<?php if ($mensaje === 'reparado'): ?>

    <div class="alert alert-success py-2">

        <i class="fa-solid fa-circle-check me-2"></i>

        El equipo fue actualizado correctamente.

    </div>

<?php endif; ?>


<?php if (!empty($error)): ?>

    <div class="alert alert-danger py-2">

        <i class="fa-solid fa-circle-exclamation me-2"></i>

        <?= limpiarTexto($error) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ESTADÍSTICAS
===================================================== -->

<div class="stats-grid">


    <div class="stat-card">

        <div class="stat-icon stat-blue">

            <i class="fa-solid fa-screwdriver-wrench"></i>

        </div>

        <div>

            <div class="stat-number">

                <?= $totalMantenimientos ?>

            </div>

            <div class="stat-label">

                Total mantenimientos

            </div>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon stat-orange">

            <i class="fa-solid fa-hourglass-half"></i>

        </div>

        <div>

            <div class="stat-number">

                <?= $mantenimientosActivos ?>

            </div>

            <div class="stat-label">

                En mantenimiento

            </div>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon stat-green">

            <i class="fa-solid fa-circle-check"></i>

        </div>

        <div>

            <div class="stat-number">

                <?= $mantenimientosFinalizados ?>

            </div>

            <div class="stat-label">

                Finalizados

            </div>

        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon stat-yellow">

            <i class="fa-solid fa-calendar-day"></i>

        </div>

        <div>

            <div class="stat-number">

                <?= $mantenimientosHoy ?>

            </div>

            <div class="stat-label">

                Registrados hoy

            </div>

        </div>

    </div>


</div>


<!-- =====================================================
     FILTROS
===================================================== -->

<div class="filters-panel">


    <div class="area-tabs">


        <a
            href="mantenimiento.php?area=audiovisuales"
            class="area-tab
            <?= $areaSeleccionada === 'audiovisuales'
                ? 'active'
                : '' ?>"
        >

            <i class="fa-solid fa-video"></i>

            Audiovisuales

        </a>


        <a
            href="mantenimiento.php?area=automatizacion"
            class="area-tab
            <?= $areaSeleccionada === 'automatizacion'
                ? 'active'
                : '' ?>"
        >

            <i class="fa-solid fa-gears"></i>

            Automatización

        </a>


    </div>


    <div class="filters-row">


        <div class="search-box">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="buscarMantenimiento"
                placeholder="Buscar equipo, código, técnico, proveedor..."
            >

        </div>


        <select
            id="filtroEstado"
            class="filter-select"
        >

            <option value="todos">
                Todos los estados
            </option>

            <option value="activo">
                En mantenimiento
            </option>

            <option value="finalizado">
                Finalizados
            </option>

        </select>


        <button
            type="button"
            class="btn-clear"
            id="btnLimpiarFiltros"
        >

            <i class="fa-solid fa-filter-circle-xmark me-1"></i>

            Limpiar

        </button>


    </div>


</div>


<!-- =====================================================
     TÍTULO DE LAS TARJETAS
===================================================== -->

<div class="cards-heading">


    <div class="cards-heading-left">


        <div class="cards-heading-icon">

            <i class="fa-solid <?= $iconoArea ?>"></i>

        </div>


        <div>

            <h2>

                Mantenimientos
                <?= limpiarTexto($tituloArea) ?>

            </h2>

            <small>

                Equipos registrados en esta área

            </small>

        </div>


    </div>


    <small id="contadorTarjetas">

        <?= $totalMantenimientos ?>

        <?= $totalMantenimientos === 1
            ? 'registro'
            : 'registros' ?>

    </small>


</div>


<!-- =====================================================
     TARJETAS
===================================================== -->

<div
    class="maintenance-grid"
    id="maintenanceGrid"
>


<?php if (empty($mantenimientos)): ?>


    <div class="empty-state">


        <div class="empty-icon">

            <i class="fa-solid fa-screwdriver-wrench"></i>

        </div>


        <h3>

            No hay mantenimientos registrados

        </h3>


        <p>

            Actualmente no existen mantenimientos
            registrados para
            <?= limpiarTexto($tituloArea) ?>.

        </p>


    </div>


<?php else: ?>


    <?php foreach ($mantenimientos as $mantenimiento): ?>


        <?php

        $estadoMantenimiento =
            obtenerEstadoMantenimiento(
                $mantenimiento
            );


        $textoEstado =
            obtenerTextoEstado(
                $mantenimiento
            );


        $equipoNombre =
            valorMantenimiento(
                $mantenimiento,
                'equipo_nombre',
                'Equipo sin nombre'
            );


        $equipoCodigo =
            valorMantenimiento(
                $mantenimiento,
                'equipo_codigo',
                'Sin código'
            );


        $tipo =
            valorMantenimiento(
                $mantenimiento,
                'tipo_mantenimiento',
                'No especificado'
            );


        $tecnico =
            valorMantenimiento(
                $mantenimiento,
                'tecnico',
                'No registrado'
            );


        $proveedor =
            valorMantenimiento(
                $mantenimiento,
                'proveedor',
                'No registrado'
            );


        $diagnostico =
            valorMantenimiento(
                $mantenimiento,
                'diagnostico',
                ''
            );


        $fechaInicio =
            valorMantenimiento(
                $mantenimiento,
                'fecha_inicio'
            );


        $fechaFin =
            valorMantenimiento(
                $mantenimiento,
                'fecha_fin'
            );


        /* =================================================
           UBICACIÓN
        ================================================= */

        $ubicacionPartes = [];


        $edificio =
            valorMantenimiento(
                $mantenimiento,
                'equipo_edificio'
            );


        $piso =
            valorMantenimiento(
                $mantenimiento,
                'equipo_piso'
            );


        $salon =
            valorMantenimiento(
                $mantenimiento,
                'equipo_salon'
            );


        $ubicacionMantenimiento =
            valorMantenimiento(
                $mantenimiento,
                'ubicacion'
            );


        if (!empty($edificio)) {

            $ubicacionPartes[] =
                'Edificio ' . $edificio;
        }


        if (!empty($piso)) {

            $ubicacionPartes[] =
                'Piso ' . $piso;
        }


        if (!empty($salon)) {

            $ubicacionPartes[] =
                'Salón ' . $salon;
        }


        if (
            empty($ubicacionPartes) &&
            !empty($ubicacionMantenimiento)
        ) {

            $ubicacionPartes[] =
                $ubicacionMantenimiento;
        }


        $ubicacion =
            !empty($ubicacionPartes)

                ? implode(
                    ' · ',
                    $ubicacionPartes
                )

                : 'Sin ubicación';


        /* =================================================
           TEXTO DE BÚSQUEDA
        ================================================= */

        $textoBusqueda = strtolower(
            implode(
                ' ',
                [
                    $equipoCodigo,
                    $equipoNombre,
                    $tecnico,
                    $proveedor,
                    $tipo,
                    $diagnostico,
                    $ubicacion,
                    valorMantenimiento(
                        $mantenimiento,
                        'equipo_area'
                    )
                ]
            )
        );

        ?>


        <article
            class="maintenance-card"
            data-search="<?= limpiarTexto($textoBusqueda) ?>"
            data-estado="<?= $estadoMantenimiento ?>"
        >


            <!-- CABECERA -->

            <div class="card-header-modern">


                <div class="card-header-row">


                    <div class="type-icon">

                        <i class="fa-solid
                            <?= obtenerIconoTipo($tipo) ?>"
                        ></i>

                    </div>


                    <span
                        class="status-badge
                        <?= $estadoMantenimiento ?>"
                    >

                        <i class="fa-solid fa-circle"></i>

                        <?= $textoEstado ?>

                    </span>


                </div>


                <div class="card-equipment">


                    <h3>

                        <?= limpiarTexto($equipoNombre) ?>

                    </h3>


                    <div class="card-code">

                        <i class="fa-solid fa-barcode me-1"></i>

                        <?= limpiarTexto($equipoCodigo) ?>

                    </div>


                </div>


            </div>


            <!-- CUERPO -->

            <div class="card-content">


                <div class="info-grid">


                    <div class="info-item">

                        <div class="info-label">

                            <i class="fa-solid fa-wrench"></i>

                            Tipo

                        </div>

                        <div class="info-value">

                            <?= limpiarTexto($tipo) ?>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">

                            <i class="fa-solid fa-calendar"></i>

                            Inicio

                        </div>

                        <div class="info-value">

                            <?= formatearFecha($fechaInicio) ?>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">

                            <i class="fa-solid fa-user-gear"></i>

                            Técnico

                        </div>

                        <div class="info-value">

                            <?= limpiarTexto($tecnico) ?>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">

                            <i class="fa-solid fa-building"></i>

                            Proveedor

                        </div>

                        <div class="info-value">

                            <?= limpiarTexto($proveedor) ?>

                        </div>

                    </div>


                    <div
                        class="info-item"
                        style="grid-column:1/-1;"
                    >

                        <div class="info-label">

                            <i class="fa-solid fa-location-dot"></i>

                            Ubicación

                        </div>

                        <div class="info-value">

                            <?= limpiarTexto($ubicacion) ?>

                        </div>

                    </div>


                    <?php if (!empty($fechaFin)): ?>

                        <div class="info-item">

                            <div class="info-label">

                                <i class="fa-solid fa-calendar-check"></i>

                                Finalización

                            </div>

                            <div class="info-value">

                                <?= formatearFecha($fechaFin) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <?php if (!empty($diagnostico)): ?>

                    <div class="diagnostic">

                        <div class="diagnostic-title">

                            Diagnóstico

                        </div>

                        <div class="diagnostic-text">

                            <?= limpiarTexto($diagnostico) ?>

                        </div>

                    </div>

                <?php endif; ?>


            </div>


            <!-- ACCIONES -->

            <div class="card-actions">


                <?php if (tienePermiso('editar_mantenimiento')): ?>

                    <button
                        type="button"
                        class="card-action action-edit"
                        onclick="abrirEditarMantenimiento(
                            <?= (int)$mantenimiento['id'] ?>
                        )"
                    >

                        <i class="fa-solid fa-pen"></i>

                        Editar

                    </button>

                <?php endif; ?>


                <?php if (
                    tienePermiso('editar_mantenimiento') &&
                    empty($fechaFin)
                ): ?>

                    <button
                        type="button"
                        class="card-action action-finish"
                        onclick="abrirFinalizarMantenimiento(
                            <?= (int)$mantenimiento['id'] ?>
                        )"
                    >

                        <i class="fa-solid fa-check"></i>

                        Finalizar

                    </button>


                    <button
                        type="button"
                        class="card-action action-repair"
                        onclick="abrirRepararEquipo(
                            <?= (int)$mantenimiento['id'] ?>
                        )"
                    >

                        <i class="fa-solid fa-screwdriver-wrench"></i>

                        Reparar

                    </button>

                <?php endif; ?>


                <?php if (
                    tienePermiso('eliminar_mantenimiento')
                ): ?>

                    <button
                        type="button"
                        class="card-action action-delete"
                        onclick="eliminarMantenimiento(
                            <?= (int)$mantenimiento['id'] ?>
                        )"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Eliminar

                    </button>

                <?php endif; ?>


            </div>


        </article>


    <?php endforeach; ?>


<?php endif; ?>


</div>


</div>

</div>


<!-- =========================================================
     MODAL NUEVO MANTENIMIENTO
========================================================= -->

<?php if (tienePermiso('crear_mantenimiento')): ?>


<div
    class="modal fade"
    id="modalNuevoMantenimiento"
    tabindex="-1"
>


    <div
        class="modal-dialog modal-xl modal-dialog-centered"
    >


        <div class="modal-content">


            <form
                method="POST"
                action="../actions/mantenimiento/registrar_mantenimiento.php"
            >


                <div class="modal-header">


                    <h5 class="modal-title">

                        <i class="fa-solid fa-screwdriver-wrench me-2"></i>

                        Registrar mantenimiento

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>


                </div>


                <div class="modal-body">


                    <div class="row g-3">


                        <div class="col-md-6">

                            <label class="form-label">

                                Equipo *

                            </label>


                            <select
                                name="equipo_id"
                                class="form-select"
                                required
                            >

                                <option value="">

                                    Seleccione un equipo

                                </option>


                                <?php foreach (
                                    $equipos
                                    as $equipo
                                ): ?>

                                    <option
                                        value="<?= (int)$equipo['id'] ?>"
                                    >

                                        <?= limpiarTexto(
                                            $equipo['codigo']
                                        ) ?>

                                        -

                                        <?= limpiarTexto(
                                            $equipo['nombre']
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $equipo['area']
                                            )
                                        ): ?>

                                            -
                                            <?= limpiarTexto(
                                                ucfirst(
                                                    $equipo['area']
                                                )
                                            ) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>


                            <small class="text-muted">

                                Puede seleccionar cualquier equipo registrado.

                            </small>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Tipo de mantenimiento *

                            </label>


                            <select
                                name="tipo_mantenimiento"
                                class="form-select"
                                required
                            >

                                <option value="">

                                    Seleccione

                                </option>

                                <option value="Preventivo">

                                    Preventivo

                                </option>

                                <option value="Correctivo">

                                    Correctivo

                                </option>

                                <option value="Calibración">

                                    Calibración

                                </option>

                                <option value="Limpieza">

                                    Limpieza

                                </option>

                                <option value="Otro">

                                    Otro

                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">

                                Fecha de inicio *

                            </label>


                            <input
                                type="date"
                                name="fecha_inicio"
                                class="form-control"
                                value="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">

                                Técnico

                            </label>


                            <input
                                type="text"
                                name="tecnico"
                                class="form-control"
                                placeholder="Nombre del técnico"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">

                                Proveedor

                            </label>


                            <input
                                type="text"
                                name="proveedor"
                                class="form-control"
                                placeholder="Proveedor"
                            >

                        </div>


                        <div class="col-md-12">

                            <label class="form-label">

                                Ubicación / lugar del mantenimiento

                            </label>


                            <input
                                type="text"
                                name="ubicacion"
                                class="form-control"
                                placeholder="Ej: Laboratorio, taller, proveedor..."
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Diagnóstico

                            </label>


                            <textarea
                                name="diagnostico"
                                class="form-control"
                                placeholder="Describa el diagnóstico encontrado..."
                            ></textarea>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Observaciones

                            </label>


                            <textarea
                                name="observaciones"
                                class="form-control"
                                placeholder="Observaciones adicionales..."
                            ></textarea>

                        </div>


                    </div>

                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <i class="fa-solid fa-floppy-disk me-1"></i>

                        Registrar mantenimiento

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<?php endif; ?>


<!-- =========================================================
     MODAL FINALIZAR
========================================================= -->

<div
    class="modal fade"
    id="modalFinalizarMantenimiento"
    tabindex="-1"
>


    <div
        class="modal-dialog modal-lg modal-dialog-centered"
    >


        <div class="modal-content">


            <form
                method="POST"
                action="../actions/mantenimiento/finalizar_mantenimiento.php"
            >


                <input
                    type="hidden"
                    name="mantenimiento_id"
                    id="finalizar_mantenimiento_id"
                >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-circle-check me-2"></i>

                        Finalizar mantenimiento

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <div class="row g-3">


                        <div class="col-md-6">

                            <label class="form-label">

                                Fecha de finalización *

                            </label>


                            <input
                                type="date"
                                name="fecha_fin"
                                class="form-control"
                                value="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Estado del equipo

                            </label>


                            <select
                                name="estado_equipo"
                                class="form-select"
                            >

                                <option value="operativo">

                                    Operativo

                                </option>

                                <option value="reparado">

                                    Reparado

                                </option>

                                <option value="mantenimiento">

                                    Mantenimiento

                                </option>

                                <option value="danado">

                                    Dañado

                                </option>

                                <option value="entregado">

                                    Entregado

                                </option>

                            </select>

                        </div>


                        <div class="col-md-12">

                            <label class="form-label">

                                Trabajo realizado

                            </label>


                            <textarea
                                name="trabajo_realizado"
                                class="form-control"
                                placeholder="Describa el trabajo realizado..."
                            ></textarea>

                        </div>


                        <div class="col-md-12">

                            <label class="form-label">

                                Observaciones de finalización

                            </label>


                            <textarea
                                name="observaciones_finalizacion"
                                class="form-control"
                                placeholder="Observaciones..."
                            ></textarea>

                        </div>


                    </div>

                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <i class="fa-solid fa-check me-1"></i>

                        Finalizar

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     MODAL REPARAR
========================================================= -->

<div
    class="modal fade"
    id="modalRepararEquipo"
    tabindex="-1"
>


    <div
        class="modal-dialog modal-lg modal-dialog-centered"
    >


        <div class="modal-content">


            <form
                method="POST"
                action="../actions/mantenimiento/reparar_equipo.php"
            >


                <input
                    type="hidden"
                    name="mantenimiento_id"
                    id="reparar_mantenimiento_id"
                >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-screwdriver-wrench me-2"></i>

                        Registrar reparación

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <div class="mb-3">

                        <label class="form-label">

                            Trabajo realizado

                        </label>


                        <textarea
                            name="trabajo_realizado"
                            class="form-control"
                            required
                            placeholder="Describa la reparación realizada..."
                        ></textarea>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">

                            Observaciones

                        </label>


                        <textarea
                            name="observaciones_finalizacion"
                            class="form-control"
                            placeholder="Observaciones..."
                        ></textarea>

                    </div>


                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <i class="fa-solid fa-floppy-disk me-1"></i>

                        Guardar reparación

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   ELEMENTOS
========================================================= */

const buscador =
    document.getElementById(
        'buscarMantenimiento'
    );


const filtroEstado =
    document.getElementById(
        'filtroEstado'
    );


const btnLimpiar =
    document.getElementById(
        'btnLimpiarFiltros'
    );


const contador =
    document.getElementById(
        'contadorTarjetas'
    );


/* =========================================================
   FILTRAR
========================================================= */

function filtrarMantenimientos()
{

    const texto =
        (
            buscador?.value || ''
        )
        .toLowerCase()
        .trim();


    const estado =
        filtroEstado?.value || 'todos';


    const tarjetas =
        document.querySelectorAll(
            '.maintenance-card'
        );


    let visibles = 0;


    tarjetas.forEach(
        function(tarjeta)
        {

            const contenido =
                (
                    tarjeta.dataset.search || ''
                )
                .toLowerCase();


            const estadoTarjeta =
                tarjeta.dataset.estado || '';


            const coincideTexto =
                contenido.includes(texto);


            const coincideEstado =
                estado === 'todos' ||
                estadoTarjeta === estado;


            if (
                coincideTexto &&
                coincideEstado
            ) {

                tarjeta.style.display = '';

                visibles++;

            } else {

                tarjeta.style.display = 'none';

            }

        }
    );


    if (contador) {

        contador.textContent =
            visibles +
            (
                visibles === 1
                    ? ' registro'
                    : ' registros'
            );

    }

}


/* =========================================================
   BUSCADOR
========================================================= */

if (buscador) {

    buscador.addEventListener(
        'input',
        filtrarMantenimientos
    );

}


/* =========================================================
   FILTRO ESTADO
========================================================= */

if (filtroEstado) {

    filtroEstado.addEventListener(
        'change',
        filtrarMantenimientos
    );

}


/* =========================================================
   LIMPIAR
========================================================= */

if (btnLimpiar) {

    btnLimpiar.addEventListener(
        'click',
        function()
        {

            if (buscador) {

                buscador.value = '';

            }


            if (filtroEstado) {

                filtroEstado.value = 'todos';

            }


            filtrarMantenimientos();

        }
    );

}


/* =========================================================
   FINALIZAR
========================================================= */

function abrirFinalizarMantenimiento(id)
{

    const campo =
        document.getElementById(
            'finalizar_mantenimiento_id'
        );


    if (campo) {

        campo.value = id;

    }


    const elemento =
        document.getElementById(
            'modalFinalizarMantenimiento'
        );


    if (elemento) {

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                elemento
            );


        modal.show();

    }

}


/* =========================================================
   REPARAR
========================================================= */

function abrirRepararEquipo(id)
{

    const campo =
        document.getElementById(
            'reparar_mantenimiento_id'
        );


    if (campo) {

        campo.value = id;

    }


    const elemento =
        document.getElementById(
            'modalRepararEquipo'
        );


    if (elemento) {

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                elemento
            );


        modal.show();

    }

}


/* =========================================================
   EDITAR
========================================================= */

function abrirEditarMantenimiento(id)
{

    window.location.href =
        '../actions/mantenimiento/editar_mantenimiento.php?id='
        + encodeURIComponent(id);

}


/* =========================================================
   ELIMINAR
========================================================= */

function eliminarMantenimiento(id)
{

    const confirmar =
        confirm(
            '¿Está seguro de que desea eliminar este mantenimiento?'
        );


    if (!confirmar) {

        return;

    }


    window.location.href =
        '../actions/mantenimiento/eliminar_mantenimiento.php?id='
        + encodeURIComponent(id);

}

</script>


</body>

</html> 