<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('ver_equipos');

/* =========================================================
   FUNCIONES
========================================================= */

function e($valor)
{
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================================
   ÁREA SELECCIONADA
========================================================= */

$areaSeleccionada = strtolower(
    trim($_GET['area'] ?? 'audiovisuales')
);

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

if (!in_array($areaSeleccionada, $areasPermitidas, true)) {
    $areaSeleccionada = 'audiovisuales';
}

/* =========================================================
   DATOS DEL ÁREA
========================================================= */

if ($areaSeleccionada === 'automatizacion') {
    $nombreArea = 'Automatización';
    $iconoArea = 'fa-gears';
} else {
    $nombreArea = 'Audiovisuales';
    $iconoArea = 'fa-video';
}

/* =========================================================
   CONSULTAR EQUIPOS
========================================================= */

$sql = "
    SELECT
        id,
        codigo,
        nombre,
        categoria,
        area,
        edificio,
        piso,
        salon,
        marca,
        modelo,
        numero_serie,
        estado,
        descripcion
    FROM equipos
    WHERE LOWER(TRIM(area)) = ?
    ORDER BY nombre ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparando consulta: " . e($conn->error));
}

$stmt->bind_param('s', $areaSeleccionada);

if (!$stmt->execute()) {
    die("Error ejecutando consulta: " . e($stmt->error));
}

$resultado = $stmt->get_result();

$equipos = [];

while ($fila = $resultado->fetch_assoc()) {
    $equipos[] = $fila;
}

$stmt->close();

/* =========================================================
   TOTAL REAL DE EQUIPOS
========================================================= */

$stmtTotal = $conn->prepare(
    "SELECT COUNT(*) AS total FROM equipos WHERE LOWER(TRIM(area)) = ?"
);

$totalEquipos = 0;

if ($stmtTotal) {
    $stmtTotal->bind_param('s', $areaSeleccionada);
    $stmtTotal->execute();
    $resTotalEquipos = $stmtTotal->get_result();

    if ($resTotalEquipos) {
        $filaTotalEquipos = $resTotalEquipos->fetch_assoc();
        $totalEquipos = (int)($filaTotalEquipos['total'] ?? 0);
    }

    $stmtTotal->close();
}

/* La consulta anterior ya calculó el total por área. */

/* =========================================================
   RESUMEN VISUAL
========================================================= */

$conteoOperativos = 0;
$conteoMantenimiento = 0;
$conteoDanados = 0;

foreach ($equipos as $eq) {
    $estadoResumen = strtolower(trim($eq['estado'] ?? ''));

    if ($estadoResumen === 'operativo') {
        $conteoOperativos++;
    } elseif ($estadoResumen === 'mantenimiento') {
        $conteoMantenimiento++;
    } elseif ($estadoResumen === 'danado' || $estadoResumen === 'dañado') {
        $conteoDanados++;
    }
}

/* =========================================================
   MENSAJES
========================================================= */

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

include("../includes/sidebar.php");

?>

<style>
/* =========================================================
   JAVERSTOCK - EQUIPOS | DISEÑO MODERNO
========================================================= */

.equipos-page{
    min-height:calc(100vh - 80px);
    padding:0 0 40px;
    background:linear-gradient(180deg,#f4f8ff 0%,#f8fafc 48%,#f3f6fb 100%);
    color:#172033;
    overflow-x:hidden;
}

.equipos-page *{box-sizing:border-box;}

.equipos-page .content{
    width:100%;
    max-width:100%;
    padding:24px 28px 40px;
    margin:0 auto;
    overflow-x:hidden;
}

/* =========================
   ENCABEZADO HERO
========================= */
.equipos-header{
    position:relative;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:24px;
    padding:28px 30px;
    margin-bottom:20px;
    border-radius:22px;
    overflow:hidden;
    background:linear-gradient(135deg,#001f4d 0%,#003b78 42%,#0876e8 100%);
    box-shadow:0 14px 35px rgba(0,40,85,.16);
}

.equipos-header::before,
.equipos-header::after{
    content:"";
    position:absolute;
    border-radius:50%;
    pointer-events:none;
}

.equipos-header::before{
    width:230px;
    height:230px;
    right:-70px;
    top:-120px;
    border:1px solid rgba(255,255,255,.13);
    box-shadow:0 0 0 32px rgba(255,255,255,.035),
               0 0 0 64px rgba(255,255,255,.025);
}

.equipos-header::after{
    width:90px;
    height:90px;
    right:220px;
    bottom:-58px;
    border:1px solid rgba(255,255,255,.12);
}

.equipos-title{
    position:relative;
    z-index:2;
}

.equipos-title .titulo-icono{
    width:52px;
    height:52px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-right:12px;
    vertical-align:middle;
    border-radius:15px;
    background:rgba(255,255,255,.14);
    color:#fff;
    font-size:22px;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.1);
}

.equipos-title h2{
    display:flex;
    align-items:center;
    margin:0;
    color:#fff;
    font-size:30px;
    line-height:1.15;
    font-weight:800;
    letter-spacing:-.5px;
}

.equipos-title p{
    margin:9px 0 0 64px;
    color:rgba(255,255,255,.78);
    font-size:14px;
}

.equipos-acciones{
    position:relative;
    z-index:2;
    display:flex;
    gap:10px;
}

.equipos-acciones .btn{
    min-height:46px;
    padding:11px 18px;
    border:0;
    border-radius:13px;
    background:#fff;
    color:#002855;
    font-weight:800;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
    transition:.2s ease;
}

.equipos-acciones .btn:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 24px rgba(0,0,0,.18);
    background:#fff;
    color:#002855;
}

/* =========================
   ÁREAS
========================= */
.equipos-areas{
    display:flex;
    gap:10px;
    margin:0 0 20px;
    padding:5px;
    width:max-content;
    max-width:100%;
    border-radius:15px;
    background:#fff;
    border:1px solid #e8edf5;
    box-shadow:0 5px 18px rgba(15,23,42,.05);
}

.btn-area-equipo{
    display:inline-flex;
    align-items:center;
    gap:9px;
    min-height:44px;
    padding:10px 19px;
    border-radius:11px;
    text-decoration:none;
    font-size:14px;
    font-weight:800;
    transition:.2s ease;
}

.btn-area-equipo:hover{transform:translateY(-1px);}

.btn-area-activa{
    background:linear-gradient(135deg,#002855,#0876e8);
    color:#fff;
    box-shadow:0 8px 18px rgba(0,91,190,.25);
}

.btn-area-activa:hover{
    background:#063d78;
    color:#fff;
}

.btn-area-inactiva{
    background:transparent;
    color:#48617b;
}

.btn-area-inactiva:hover{
    background:#f1f6fb;
    color:#002855;
}

/* =========================
   ESTADÍSTICAS
========================= */
.equipos-stats{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
    margin-bottom:20px;
}

.equipo-stat{
    position:relative;
    display:flex;
    align-items:center;
    gap:14px;
    min-width:0;
    padding:17px 18px;
    background:linear-gradient(145deg,#ffffff 0%,#f8fbff 100%);
    border:1px solid #e1eaf5;
    border-radius:17px;
    box-shadow:0 6px 20px rgba(15,23,42,.045);
    overflow:hidden;
}

.equipo-stat::after{
    content:"";
    position:absolute;
    right:-28px;
    bottom:-34px;
    width:90px;
    height:90px;
    border-radius:50%;
    background:rgba(0,40,85,.035);
}

.stat-icon{
    width:44px;
    height:44px;
    flex:0 0 44px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:13px;
    font-size:18px;
}

.stat-total .stat-icon{background:linear-gradient(135deg,#dbeaff,#b9d9ff);color:#0756ae;box-shadow:0 7px 16px rgba(7,86,174,.13);}
.stat-ok .stat-icon{background:linear-gradient(135deg,#d9f8e8,#b8efd2);color:#087443;box-shadow:0 7px 16px rgba(8,116,67,.13);}
.stat-warning .stat-icon{background:linear-gradient(135deg,#fff2c9,#ffe09a);color:#a66a00;box-shadow:0 7px 16px rgba(166,106,0,.13);}
.stat-danger .stat-icon{background:linear-gradient(135deg,#ffe1e7,#ffc6cf);color:#c62839;box-shadow:0 7px 16px rgba(198,40,57,.13);}

.stat-text{
    position:relative;
    z-index:1;
    min-width:0;
}

.stat-number{
    display:block;
    color:#10233f;
    font-size:22px;
    line-height:1.1;
    font-weight:800;
}

.stat-label{
    display:block;
    margin-top:3px;
    color:#718096;
    font-size:12px;
    font-weight:600;
}

/* =========================
   TARJETA PRINCIPAL
========================= */
.equipos-card{
    background:linear-gradient(145deg,#ffffff 0%,#fbfdff 100%);
    border:1px solid #dfe8f3;
    border-radius:22px;
    padding:22px;
    box-shadow:0 10px 30px rgba(15,23,42,.055);
    overflow:hidden;
}

.equipos-card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    margin-bottom:18px;
}

.equipos-card-header h4{
    display:flex;
    align-items:center;
    gap:10px;
    margin:0;
    color:#002855;
    font-size:20px;
    font-weight:800;
}

.equipos-card-header h4 i{
    width:38px;
    height:38px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:11px;
    background:#edf4ff;
    color:#0b63ce;
    font-size:16px;
}

#contadorEquipos{
    display:inline-flex;
    align-items:center;
    min-height:34px;
    padding:8px 13px;
    border-radius:30px;
    background:#eaf2ff !important;
    color:#0756ae !important;
    font-size:12px;
    font-weight:800;
    white-space:nowrap;
}

/* =========================
   BUSCADOR
========================= */
.equipos-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    margin-bottom:22px;
    flex-wrap:wrap;
}

.equipos-search{
    position:relative;
    flex:1 1 420px;
    max-width:560px;
}

.equipos-search i{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:#8b9aad;
    font-size:15px;
    pointer-events:none;
}

.equipos-search input{
    width:100%;
    height:48px;
    padding:0 18px 0 45px;
    border:1px solid #dfe7f0;
    border-radius:14px;
    background:linear-gradient(145deg,#f8fbff,#eef5fc);
    color:#172033;
    outline:none;
    font-size:14px;
    transition:.2s ease;
}

.equipos-search input:focus{
    background:#fff;
    border-color:#0b63ce;
    box-shadow:0 0 0 4px rgba(11,99,206,.09);
}

.equipos-search input::placeholder{color:#9aa8b8;}

.equipos-results{
    display:flex;
    align-items:center;
    gap:8px;
    color:#718096;
    font-size:13px;
    font-weight:700;
}

/* =========================
   GRID DE EQUIPOS
========================= */
.equipos-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:18px;
}

.equipo-item{
    min-width:0;
}

.equipo-card{
    position:relative;
    height:100%;
    min-height:285px;
    display:flex;
    flex-direction:column;
    padding:20px;
    background:linear-gradient(145deg,#ffffff 0%,#f9fbff 100%);
    border:1px solid #dce7f4;
    border-radius:20px;
    box-shadow:0 8px 25px rgba(15,23,42,.055);
    overflow:hidden;
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}

.equipo-card::before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    width:100%;
    height:4px;
    background:linear-gradient(90deg,#002855 0%,#0876e8 45%,#00a6d6 100%);
}

.equipo-card:hover{
    transform:translateY(-5px);
    border-color:#cddff3;
    box-shadow:0 16px 35px rgba(0,40,85,.11);
}

/* Acentos de color según estado */
.equipo-item:has(.estado-operativo) .equipo-card::before{
    background:linear-gradient(90deg,#00a66a,#27d17f,#00b4d8);
}
.equipo-item:has(.estado-mantenimiento) .equipo-card::before{
    background:linear-gradient(90deg,#f59e0b,#fbbf24,#ffd166);
}
.equipo-item:has(.estado-danado) .equipo-card::before{
    background:linear-gradient(90deg,#e11d48,#fb7185,#f97316);
}
.equipo-item:has(.estado-operativo) .equipo-icon{
    background:linear-gradient(145deg,#d8f9e8,#b7efd1);
    color:#087443;
}
.equipo-item:has(.estado-mantenimiento) .equipo-icon{
    background:linear-gradient(145deg,#fff2ca,#ffe09a);
    color:#a66a00;
}
.equipo-item:has(.estado-danado) .equipo-icon{
    background:linear-gradient(145deg,#ffe1e7,#ffc5cf);
    color:#c62839;
}

.equipo-card-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:17px;
}

.equipo-identidad{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:0;
}

.equipo-icon{
    width:48px;
    height:48px;
    flex:0 0 48px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    background:linear-gradient(145deg,#dceeff,#b9dcff);
    color:#0756ae;
    box-shadow:0 8px 18px rgba(11,99,206,.14);
    font-size:19px;
}

.equipo-titulo-wrap{
    min-width:0;
}

.equipo-card-code{
    display:block;
    margin-bottom:4px;
    color:#0876d1;
    font-size:11px;
    font-weight:800;
    letter-spacing:.35px;
    text-transform:uppercase;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.equipo-card-title{
    margin:0;
    color:#10233f;
    font-size:16px;
    line-height:1.25;
    font-weight:800;
    overflow:hidden;
    text-overflow:ellipsis;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
}

.equipo-card-category{
    display:inline-flex;
    align-items:center;
    gap:6px;
    flex:0 0 auto;
    max-width:130px;
    padding:6px 9px;
    border-radius:9px;
    background:linear-gradient(135deg,#fff4d7,#ffe9a8);
    color:#946200;
    border:1px solid #f8d77d;
    font-size:10px;
    font-weight:800;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.equipo-info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-bottom:17px;
}

.equipo-info-box{
    min-width:0;
    padding:11px 12px;
    border:1px solid #dfeaf6;
    border-radius:12px;
    background:linear-gradient(145deg,#f7fbff,#eef6ff);
}

.equipo-info-label{
    display:flex;
    align-items:center;
    gap:6px;
    margin-bottom:5px;
    color:#4e76a0;
    font-size:9px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.45px;
}

.equipo-info-label i{font-size:10px;color:#0876c9;}

.equipo-info-value{
    display:block;
    color:#334155;
    font-size:12px;
    font-weight:750;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.equipo-info-sub{
    display:block;
    margin-top:3px;
    color:#94a3b8;
    font-size:10px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.equipo-card-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-top:auto;
    padding-top:14px;
    border-top:1px solid #dfe8f3;
}

.estado-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 10px;
    border-radius:20px;
    font-size:10px;
    font-weight:800;
    white-space:nowrap;
}

.estado-operativo{background:linear-gradient(135deg,#d9f8e8,#bdeed4);color:#087443;box-shadow:0 5px 12px rgba(8,116,67,.10);}
.estado-mantenimiento{background:linear-gradient(135deg,#fff4d1,#ffe5a3);color:#a66a00;box-shadow:0 5px 12px rgba(166,106,0,.10);}
.estado-danado{background:linear-gradient(135deg,#ffe2e7,#ffc9d1);color:#c62839;box-shadow:0 5px 12px rgba(198,40,57,.10);}

.acciones-equipo{
    display:flex;
    align-items:center;
    gap:6px;
}

.btn-accion-equipo{
    width:36px;
    height:36px;
    padding:0 !important;
    border-radius:10px !important;
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    border-width:1px !important;
    transition:.18s ease;
}

.btn-accion-equipo:hover{transform:translateY(-2px);}

.equipos-empty{
    grid-column:1/-1;
    padding:55px 20px;
    text-align:center;
    border:1px dashed #d6e0eb;
    border-radius:18px;
    background:#fafcfe;
    color:#718096;
}

.equipos-empty i{
    display:block;
    margin-bottom:12px;
    color:#a4b2c2;
    font-size:42px;
}

/* =========================
   DATATABLES - OCULTAR SI QUEDA CARGADO
========================= */
.dataTables_empty{display:none !important;}
.dataTables_info,.dataTables_paginate,.dataTables_length{display:none !important;}

/* =========================
   MODALES - SOBRE EL SIDEBAR
========================= */
/*
   El sidebar de JaverStock puede tener un z-index alto.
   Bootstrap normalmente usa 1050/1055 para backdrop/modal,
   por eso en algunos equipos el menú queda encima del modal.
*/
.modal-backdrop{
    z-index:1990 !important;
}

.modal{
    z-index:2000 !important;
    padding-left:0 !important;
    padding-right:0 !important;
}

.modal-dialog{
    width:calc(100% - 30px);
    margin-left:auto !important;
    margin-right:auto !important;
}

.modal-content{
    border:0;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 25px 70px rgba(15,23,42,.22);
}

.modal-header{
    padding:18px 22px;
}

.modal-body{padding:22px;}

.modal-footer{padding:16px 22px;}

.form-label{
    font-weight:700;
    color:#243b53;
}

.form-control,
.form-select{
    min-height:44px;
    border-color:#dce3ec;
    border-radius:11px;
}

.form-control:focus,
.form-select:focus{
    border-color:#0b63ce;
    box-shadow:0 0 0 3px rgba(11,99,206,.1);
}

.modal-registro-equipo{
    max-width:1100px;
    width:calc(100% - 40px);
    margin:25px auto !important;
}

.modal-registro-equipo.modal-dialog{
    margin-left:auto !important;
    margin-right:auto !important;
}

.modal-registro-equipo .modal-content{min-height:82vh;}

.modal-registro-equipo .modal-body{
    max-height:72vh;
    overflow-y:auto;
}

/* =========================
   QUITAR SCROLL HORIZONTAL
========================= */
html,
body{
    max-width:100%;
    overflow-x:hidden !important;
}

.main,
.content,
.equipos-page,
.equipos-card{
    max-width:100%;
}

/* =========================
   RESPONSIVE
========================= */
@media(max-width:1100px){
    .equipos-page .content{padding:20px;}
    .equipos-stats{grid-template-columns:repeat(2,minmax(0,1fr));}
    .equipos-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
}

@media(max-width:768px){
    .equipos-page .content{padding:15px 12px 30px;}

    .equipos-header{
        align-items:flex-start;
        flex-direction:column;
        padding:22px;
        border-radius:18px;
    }

    .equipos-title h2{font-size:24px;}
    .equipos-title p{margin-left:0;}
    .equipos-title .titulo-icono{width:44px;height:44px;}

    .equipos-acciones,
    .equipos-acciones .btn{width:100%;}

    .equipos-areas{width:100%;overflow:hidden;}
    .btn-area-equipo{flex:1;padding:10px 12px;font-size:12px;}

    .equipos-stats{
        grid-template-columns:1fr 1fr;
        gap:10px;
    }

    .equipo-stat{padding:13px;}
    .stat-icon{width:38px;height:38px;flex-basis:38px;}
    .stat-number{font-size:19px;}

    .equipos-card{
        padding:14px;
        border-radius:17px;
    }

    .equipos-card-header{
        align-items:flex-start;
        flex-direction:column;
    }

    .equipos-toolbar{align-items:stretch;}
    .equipos-search{max-width:none;flex-basis:100%;}
    .equipos-results{justify-content:space-between;}

    .equipos-grid{grid-template-columns:1fr;gap:13px;}
    .equipo-card{min-height:0;padding:17px;}

    .equipo-card-category{max-width:105px;}

    .modal-registro-equipo{
        max-width:none;
        width:calc(100% - 20px);
        margin:10px auto !important;
    }

    .modal-registro-equipo .modal-content{min-height:auto;}
    .modal-registro-equipo .modal-body{max-height:75vh;}
}
</style>


<div class="main equipos-page">

    <?php include("../includes/header.php"); ?>

    <div class="content">

        <!-- =====================================================
             ENCABEZADO MODERNO
        ====================================================== -->
        <div class="equipos-header">

            <div class="equipos-title">
                <h2>
                    <span class="titulo-icono">
                        <i class="fas <?= e($iconoArea); ?>"></i>
                    </span>
                    Todos los equipos
                </h2>

                <p>
                    Gestión, consulta y control de los equipos registrados.
                </p>
            </div>

            <div class="equipos-acciones">
                <?php if (tienePermiso('crear_equipo')): ?>
                    <button
                        type="button"
                        class="btn"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevoEquipo">
                        <i class="fas fa-plus"></i>
                        Registrar equipo
                    </button>
                <?php endif; ?>
            </div>

        </div>

        <!-- =====================================================
             ÁREAS
        ====================================================== -->
        <div class="equipos-areas">

            <a
                href="equipos.php?area=audiovisuales"
                class="btn-area-equipo <?=
                    $areaSeleccionada === 'audiovisuales'
                    ? 'btn-area-activa'
                    : 'btn-area-inactiva';
                ?>">
                <i class="fas fa-video"></i>
                Audiovisuales
            </a>

            <a
                href="equipos.php?area=automatizacion"
                class="btn-area-equipo <?=
                    $areaSeleccionada === 'automatizacion'
                    ? 'btn-area-activa'
                    : 'btn-area-inactiva';
                ?>">
                <i class="fas fa-gears"></i>
                Automatización
            </a>

        </div>

        <!-- =====================================================
             ESTADÍSTICAS
        ====================================================== -->
        <div class="equipos-stats">

            <div class="equipo-stat stat-total">
                <div class="stat-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-text">
                    <span class="stat-number"><?= $totalEquipos; ?></span>
                    <span class="stat-label">Total registrados</span>
                </div>
            </div>

            <div class="equipo-stat stat-ok">
                <div class="stat-icon">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="stat-text">
                    <span class="stat-number"><?= $conteoOperativos; ?></span>
                    <span class="stat-label">Equipos operativos</span>
                </div>
            </div>

            <div class="equipo-stat stat-warning">
                <div class="stat-icon">
                    <i class="fas fa-screwdriver-wrench"></i>
                </div>
                <div class="stat-text">
                    <span class="stat-number"><?= $conteoMantenimiento; ?></span>
                    <span class="stat-label">En mantenimiento</span>
                </div>
            </div>

            <div class="equipo-stat stat-danger">
                <div class="stat-icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="stat-text">
                    <span class="stat-number"><?= $conteoDanados; ?></span>
                    <span class="stat-label">Equipos dañados</span>
                </div>
            </div>

        </div>

        <!-- =====================================================
             LISTADO VISUAL DE EQUIPOS
        ====================================================== -->
        <div class="equipos-card">

            <div class="equipos-card-header">
                <h4>
                    <i class="fas <?= e($iconoArea); ?>"></i>
                    Equipos de <?= e($nombreArea); ?>
                </h4>

                <span class="badge" id="contadorEquipos">
                    <?= $totalEquipos; ?> equipos registrados
                </span>
            </div>

            <div class="equipos-toolbar">
                <div class="equipos-search">
                    <i class="fas fa-search"></i>
                    <input
                        type="search"
                        id="buscarEquipo"
                        placeholder="Buscar código, equipo, marca, modelo o ubicación..."
                        autocomplete="off">
                </div>

                <div class="equipos-results">
                    <i class="fas fa-layer-group"></i>
                    <span id="resultadoBusqueda"><?= $totalEquipos; ?> equipos visibles</span>
                </div>
            </div>

            <div class="equipos-grid" id="equiposGrid">

                <?php if (count($equipos) > 0): ?>

                    <?php foreach ($equipos as $equipo): ?>

                        <?php
                        $estado = strtolower(trim($equipo['estado'] ?? ''));

                        if ($estado === 'operativo') {
                            $estadoClase = 'estado-operativo';
                            $estadoIcono = 'fa-circle-check';
                            $estadoTexto = 'Operativo';
                        } elseif ($estado === 'mantenimiento') {
                            $estadoClase = 'estado-mantenimiento';
                            $estadoIcono = 'fa-screwdriver-wrench';
                            $estadoTexto = 'Mantenimiento';
                        } elseif ($estado === 'danado' || $estado === 'dañado') {
                            $estadoClase = 'estado-danado';
                            $estadoIcono = 'fa-triangle-exclamation';
                            $estadoTexto = 'Dañado';
                        } else {
                            $estadoClase = 'estado-danado';
                            $estadoIcono = 'fa-circle-question';
                            $estadoTexto = ucfirst($estado ?: 'Sin estado');
                        }

                        $textoBusqueda = strtolower(trim(
                            ($equipo['codigo'] ?? '') . ' ' .
                            ($equipo['nombre'] ?? '') . ' ' .
                            ($equipo['categoria'] ?? '') . ' ' .
                            ($equipo['marca'] ?? '') . ' ' .
                            ($equipo['modelo'] ?? '') . ' ' .
                            ($equipo['numero_serie'] ?? '') . ' ' .
                            ($equipo['edificio'] ?? '') . ' ' .
                            ($equipo['piso'] ?? '') . ' ' .
                            ($equipo['salon'] ?? '') . ' ' .
                            ($equipo['estado'] ?? '')
                        ));
                        ?>

                        <article class="equipo-item" data-search="<?= e($textoBusqueda); ?>">
                            <div class="equipo-card">

                                <div class="equipo-card-top">
                                    <div class="equipo-identidad">
                                        <div class="equipo-icon">
                                            <i class="fas <?= e($iconoArea); ?>"></i>
                                        </div>

                                        <div class="equipo-titulo-wrap">
                                            <span class="equipo-card-code">
                                                <?= e($equipo['codigo']); ?>
                                            </span>
                                            <h5 class="equipo-card-title">
                                                <?= e($equipo['nombre']); ?>
                                            </h5>
                                        </div>
                                    </div>

                                    <span class="equipo-card-category" title="<?= e($equipo['categoria']); ?>">
                                        <i class="fas fa-tag"></i>
                                        <?= e($equipo['categoria'] ?: 'Sin categoría'); ?>
                                    </span>
                                </div>

                                <div class="equipo-info-grid">

                                    <div class="equipo-info-box">
                                        <span class="equipo-info-label">
                                            <i class="fas fa-microchip"></i>
                                            Información técnica
                                        </span>
                                        <span class="equipo-info-value">
                                            <?= e($equipo['marca'] ?: 'Sin marca'); ?>
                                            <?php if (!empty($equipo['modelo'])): ?>
                                                · <?= e($equipo['modelo']); ?>
                                            <?php endif; ?>
                                        </span>
                                        <span class="equipo-info-sub">
                                            Serie: <?= e($equipo['numero_serie'] ?: 'No registrada'); ?>
                                        </span>
                                    </div>

                                    <div class="equipo-info-box">
                                        <span class="equipo-info-label">
                                            <i class="fas fa-location-dot"></i>
                                            Ubicación
                                        </span>
                                        <span class="equipo-info-value">
                                            <?= e($equipo['edificio'] ?: 'Sin edificio'); ?>
                                        </span>
                                        <span class="equipo-info-sub">
                                            Piso <?= e($equipo['piso'] ?: '—'); ?> · Salón <?= e($equipo['salon'] ?: '—'); ?>
                                        </span>
                                    </div>

                                </div>

                                <?php if (!empty($equipo['descripcion'])): ?>
                                    <div style="margin:-3px 0 14px;color:#7b8da1;font-size:11px;line-height:1.4;">
                                        <?= e($equipo['descripcion']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="equipo-card-footer">
                                    <span class="estado-badge <?= e($estadoClase); ?>">
                                        <i class="fas <?= e($estadoIcono); ?>"></i>
                                        <?= e($estadoTexto); ?>
                                    </span>

                                    <div class="acciones-equipo">

                                        <?php if (tienePermiso('editar_equipo')): ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-accion-equipo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarEquipo<?= (int)$equipo['id']; ?>"
                                                title="Editar equipo">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (tienePermiso('eliminar_equipo')): ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-accion-equipo"
                                                onclick="eliminarEquipo(<?= (int)$equipo['id']; ?>)"
                                                title="Eliminar equipo">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </div>

                            </div>
                        </article>

                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="equipos-empty">
                        <i class="fas <?= e($iconoArea); ?>"></i>
                        <strong>No hay equipos registrados.</strong>
                        <div style="margin-top:6px;">Los equipos de esta área aparecerán aquí.</div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<!-- =========================================================
     MODAL NUEVO EQUIPO
========================================================= -->

<?php if (tienePermiso('crear_equipo')): ?>

<div
    class="modal fade"
    id="modalNuevoEquipo"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-registro-equipo">

        <div class="modal-content">

            <form
                action="../actions/equipos/guardar_equipo.php"
                method="POST">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title">

                        <i class="fas fa-plus-circle"></i>

                        Registrar equipo

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">

                        <!-- CÓDIGO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Código
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                class="form-control"
                                placeholder="Ej: AUDIO-001"
                                required>

                        </div>


                        <!-- NOMBRE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Equipo
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                placeholder="Ej: Proyector Epson"
                                required>

                        </div>


                        <!-- ÁREA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Área
                            </label>

                            <select
                                name="area"
                                class="form-select"
                                required>

                                <option value="">
                                    Seleccionar área
                                </option>

                                <option
                                    value="audiovisuales"
                                    <?= $areaSeleccionada === 'audiovisuales'
                                        ? 'selected'
                                        : ''; ?>>

                                    Audiovisuales

                                </option>

                                <option
                                    value="automatizacion"
                                    <?= $areaSeleccionada === 'automatizacion'
                                        ? 'selected'
                                        : ''; ?>>

                                    Automatización

                                </option>

                            </select>

                        </div>


                        <!-- CATEGORÍA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Categoría
                            </label>

                            <select
                                name="categoria"
                                class="form-select"
                                required>

                                <option value="">
                                    Seleccionar categoría
                                </option>

                                <option value="audiovisuales">
                                    Audiovisuales
                                </option>

                                <option value="automatizacion">
                                    Automatización
                                </option>

                            </select>

                        </div>


                        <!-- MARCA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Marca
                            </label>

                            <input
                                type="text"
                                name="marca"
                                class="form-control"
                                placeholder="Ej: Epson">

                        </div>


                        <!-- MODELO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Modelo
                            </label>

                            <input
                                type="text"
                                name="modelo"
                                class="form-control">

                        </div>


                        <!-- NÚMERO DE SERIE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Número de serie
                            </label>

                            <input
                                type="text"
                                name="numero_serie"
                                class="form-control">

                        </div>


                        <!-- EDIFICIO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Edificio
                            </label>

                            <input
                                type="text"
                                name="edificio"
                                class="form-control"
                                placeholder="Ej: Edificio José Celestino Mutis">

                        </div>


                        <!-- PISO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Piso
                            </label>

                            <input
                                type="text"
                                name="piso"
                                class="form-control"
                                placeholder="Ej: 1">

                        </div>


                        <!-- SALÓN -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Salón
                            </label>

                            <input
                                type="text"
                                name="salon"
                                class="form-control"
                                placeholder="Ej: Auditorio">

                        </div>


                        <!-- ESTADO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Estado
                            </label>

                            <select
                                name="estado"
                                class="form-select"
                                required>

                                <option value="operativo">
                                    Operativo
                                </option>

                                <option value="mantenimiento">
                                    Mantenimiento
                                </option>

                                <option value="danado">
                                    Dañado
                                </option>

                                <option value="reparado">
                                    Reparado
                                </option>

                                <option value="entregado">
                                    Entregado
                                </option>

                            </select>

                        </div>


                        <!-- DESCRIPCIÓN -->

                        <div class="col-12">

                            <label class="form-label">
                                Descripción
                            </label>

                            <textarea
                                name="descripcion"
                                class="form-control"
                                rows="3"
                                placeholder="Descripción del equipo"></textarea>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fas fa-save"></i>

                        Guardar equipo

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     MODALES EDITAR
========================================================= -->

<?php foreach ($equipos as $equipo): ?>

<div
    class="modal fade"
    id="modalEditarEquipo<?= (int)$equipo['id']; ?>"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                action="../actions/equipos/actualizar_equipo.php"
                method="POST">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title">

                        <i class="fas fa-edit"></i>

                        Editar equipo

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$equipo['id']; ?>">


                    <div class="row g-3">

                        <!-- CÓDIGO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Código
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                class="form-control"
                                value="<?= e($equipo['codigo']); ?>"
                                required>

                        </div>


                        <!-- EQUIPO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Equipo
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                value="<?= e($equipo['nombre']); ?>"
                                required>

                        </div>


                        <!-- ÁREA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Área
                            </label>

                            <select
                                name="area"
                                class="form-select"
                                required>

                                <option
                                    value="audiovisuales"
                                    <?= strtolower(trim($equipo['area'] ?? '')) === 'audiovisuales'
                                        ? 'selected'
                                        : ''; ?>>

                                    Audiovisuales

                                </option>

                                <option
                                    value="automatizacion"
                                    <?= strtolower(trim($equipo['area'] ?? '')) === 'automatizacion'
                                        ? 'selected'
                                        : ''; ?>>

                                    Automatización

                                </option>

                            </select>

                        </div>


                        <!-- CATEGORÍA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Categoría
                            </label>

                            <select
                                name="categoria"
                                class="form-select"
                                required>

                                <option value="">
                                    Seleccionar categoría
                                </option>

                                <option
                                    value="audiovisuales"
                                    <?= strtolower(trim($equipo['categoria'] ?? '')) === 'audiovisuales'
                                        ? 'selected'
                                        : ''; ?>>

                                    Audiovisuales

                                </option>

                                <option
                                    value="automatizacion"
                                    <?= strtolower(trim($equipo['categoria'] ?? '')) === 'automatizacion'
                                        ? 'selected'
                                        : ''; ?>>

                                    Automatización

                                </option>

                            </select>

                        </div>


                        <!-- MARCA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Marca
                            </label>

                            <input
                                type="text"
                                name="marca"
                                class="form-control"
                                value="<?= e($equipo['marca']); ?>">

                        </div>


                        <!-- MODELO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Modelo
                            </label>

                            <input
                                type="text"
                                name="modelo"
                                class="form-control"
                                value="<?= e($equipo['modelo']); ?>">

                        </div>


                        <!-- NÚMERO DE SERIE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Número de serie
                            </label>

                            <input
                                type="text"
                                name="numero_serie"
                                class="form-control"
                                value="<?= e($equipo['numero_serie']); ?>">

                        </div>


                        <!-- EDIFICIO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Edificio
                            </label>

                            <input
                                type="text"
                                name="edificio"
                                class="form-control"
                                value="<?= e($equipo['edificio']); ?>"
                                placeholder="Ej: Edificio José Celestino Mutis">

                        </div>


                        <!-- PISO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Piso
                            </label>

                            <input
                                type="text"
                                name="piso"
                                class="form-control"
                                value="<?= e($equipo['piso']); ?>"
                                placeholder="Ej: 1">

                        </div>


                        <!-- SALÓN -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Salón
                            </label>

                            <input
                                type="text"
                                name="salon"
                                class="form-control"
                                value="<?= e($equipo['salon']); ?>"
                                placeholder="Ej: Auditorio">

                        </div>


                        <!-- ESTADO -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Estado
                            </label>

                            <select
                                name="estado"
                                class="form-select"
                                required>

                                <option
                                    value="operativo"
                                    <?= ($equipo['estado'] ?? '') === 'operativo'
                                        ? 'selected'
                                        : ''; ?>>

                                    Operativo

                                </option>

                                <option
                                    value="mantenimiento"
                                    <?= ($equipo['estado'] ?? '') === 'mantenimiento'
                                        ? 'selected'
                                        : ''; ?>>

                                    Mantenimiento

                                </option>

                                <option
                                    value="danado"
                                    <?= ($equipo['estado'] ?? '') === 'danado'
                                        ? 'selected'
                                        : ''; ?>>

                                    Dañado

                                </option>

                                <option
                                    value="reparado"
                                    <?= ($equipo['estado'] ?? '') === 'reparado'
                                        ? 'selected'
                                        : ''; ?>>

                                    Reparado

                                </option>

                                <option
                                    value="entregado"
                                    <?= ($equipo['estado'] ?? '') === 'entregado'
                                        ? 'selected'
                                        : ''; ?>>

                                    Entregado

                                </option>

                            </select>

                        </div>


                        <!-- DESCRIPCIÓN -->

                        <div class="col-12">

                            <label class="form-label">
                                Descripción
                            </label>

                            <textarea
                                name="descripcion"
                                class="form-control"
                                rows="3"><?= e($equipo['descripcion']); ?></textarea>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fas fa-save"></i>

                        Guardar cambios

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endforeach; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

function eliminarEquipo(id)
{
    Swal.fire({

        icon: 'warning',

        title: '¿Eliminar equipo?',

        text: 'Esta acción no se puede deshacer.',

        showCancelButton: true,

        confirmButtonText: 'Sí, eliminar',

        cancelButtonText: 'Cancelar',

        confirmButtonColor: '#dc3545',

        cancelButtonColor: '#6c757d'

    }).then(function(resultado) {

        if (resultado.isConfirmed) {

            window.location.href =
                '../actions/equipos/eliminar_equipo.php?id=' +
                encodeURIComponent(id);

        }

    });
}


document.addEventListener(
    'DOMContentLoaded',
    function()
    {

        const buscador = document.getElementById('buscarEquipo');
        const items = Array.from(document.querySelectorAll('.equipo-item'));
        const contador = document.getElementById('contadorEquipos');
        const resultadoBusqueda = document.getElementById('resultadoBusqueda');

        function actualizarEquipos() {
            const termino = (buscador ? buscador.value : '').trim().toLowerCase();
            let visibles = 0;

            items.forEach(function(item) {
                const texto = item.getAttribute('data-search') || '';
                const coincide = termino === '' || texto.includes(termino);

                item.style.display = coincide ? '' : 'block';
                if (coincide) visibles++;
            });

            if (contador) {
                contador.textContent = termino !== ''
                    ? visibles + (visibles === 1 ? ' equipo encontrado' : ' equipos encontrados')
                    : <?= (int)$totalEquipos; ?> + (<?= (int)$totalEquipos; ?> === 1 ? ' equipo registrado' : ' equipos registrados');
            }

            if (resultadoBusqueda) {
                resultadoBusqueda.textContent = visibles +
                    (visibles === 1 ? ' equipo visible' : ' equipos visibles');
            }
        }

        if (buscador) {
            buscador.addEventListener('input', actualizarEquipos);
        }

        <?php if ($msg === 'equipo_registrado'): ?>

        Swal.fire({
            icon: 'success',
            title: 'Equipo registrado',
            text: 'El equipo fue registrado correctamente.',
            confirmButtonColor: '#002855'
        });

        <?php elseif ($msg === 'equipo_actualizado'): ?>

        Swal.fire({
            icon: 'success',
            title: 'Equipo actualizado',
            text: 'El equipo fue actualizado correctamente.',
            confirmButtonColor: '#002855'
        });

        <?php elseif ($msg === 'equipo_eliminado'): ?>

        Swal.fire({
            icon: 'success',
            title: 'Equipo eliminado',
            text: 'El equipo fue eliminado correctamente.',
            confirmButtonColor: '#002855'
        });

        <?php elseif ($error === 'equipo_tiene_mantenimiento'): ?>

        Swal.fire({
            icon: 'warning',
            title: 'No se puede eliminar',
            text: 'Este equipo tiene registros de mantenimiento asociados.',
            confirmButtonColor: '#f59e0b'
        });

        <?php elseif ($error !== ''): ?>

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?= json_encode(
                $error,
                JSON_UNESCAPED_UNICODE
            ); ?>,
            confirmButtonColor: '#dc3545'
        });

        <?php endif; ?>

    }
);

</script>

<?php include("../includes/footer.php"); ?>