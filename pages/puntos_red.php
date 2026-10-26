<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/permisos.php";

exigirPermiso('ver_puntos_red');

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$area = strtolower(trim($_GET['area'] ?? 'audiovisuales'));
$areasPermitidas = ['audiovisuales', 'automatizacion'];

if (!in_array($area, $areasPermitidas, true)) {
    $area = 'audiovisuales';
}

$nombreArea = $area === 'audiovisuales' ? 'Audiovisuales' : 'Automatización';
$edificioSeleccionado = trim($_GET['edificio'] ?? '');

$edificios = [];
$totalPuntos = 0;

if ($area === 'audiovisuales') {
    $sql = "
        SELECT
            id,
            edificio,
            ubicacion,
            aula,
            equipo,
            marca,
            sitio_instalacion,
            tipo_puerto,
            mac,
            ip,
            mascara,
            serial,
            punto,
            switch,
            puerto,
            estado
        FROM puntos_red_audiovisuales
        ORDER BY edificio ASC, aula ASC, equipo ASC
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        die("Error consultando puntos audiovisuales: " . e($conn->error));
    }

    while ($fila = $resultado->fetch_assoc()) {
        $edificio = trim($fila['edificio'] ?? '');
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

if ($area === 'automatizacion') {
    $sql = "
        SELECT
            id,
            sistema,
            item,
            edificio,
            zona,
            ip,
            mac,
            ubicacion,
            servidor,
            administracion,
            observaciones,
            camara_server,
            gateway,
            dns,
            mascara,
            rack,
            switch,
            sw_port,
            vlan,
            subsistema,
            estado
        FROM puntos_seguridad
        ORDER BY edificio ASC, sistema ASC, item ASC
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        die("Error consultando puntos de automatización: " . e($conn->error));
    }

    while ($fila = $resultado->fetch_assoc()) {
        $edificio = trim($fila['edificio'] ?? '');
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

ksort($edificios);

include("../includes/sidebar.php");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JaverStock | Puntos de Red</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        body { background: #f4f7fb; }
        .puntos-red-page { padding-bottom: 50px; }

        .puntos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .puntos-header h2 { margin: 0; color: #002855; font-size: 32px; font-weight: 800; }
        .puntos-header p { margin: 6px 0 0; color: #64748b; }

        .acciones-puntos { display: flex; align-items: center; gap: 10px; }
        .btn-nuevo-punto {
            border: none;
            background: #002855;
            color: #fff;
            border-radius: 10px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: .2s ease;
        }
        .btn-nuevo-punto:hover { background: #064b8d; color: #fff; transform: translateY(-2px); }

        .areas-red { display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap; }
        .btn-area-red {
            border-radius: 14px;
            padding: 13px 22px;
            border: 1px solid #dbe3ec;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            transition: .2s ease;
        }
        .btn-area-red:hover { transform: translateY(-2px); }
        .btn-area-red.activo { background: #002855; color: #fff; border-color: #002855; box-shadow: 0 8px 20px rgba(0,40,85,.18); }
        .btn-area-red.inactivo { background: #fff; color: #002855; }
        .btn-area-red.inactivo:hover { background: #eef5fc; }

        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            color: #002855;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            padding: 9px 15px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .btn-volver:hover { background: #eef5fc; color: #002855; }

        .resumen-red {
            background: #fff;
            border-radius: 18px;
            padding: 20px 24px;
            margin-bottom: 28px;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .resumen-numero { font-size: 30px; font-weight: 800; color: #002855; }
        .resumen-texto { color: #64748b; font-weight: 600; }

        .edificios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px;
        }
        .edificio-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #edf1f5;
            box-shadow: 0 8px 25px rgba(0,0,0,.07);
            transition: .2s ease;
        }
        .edificio-card:hover { transform: translateY(-4px); box-shadow: 0 14px 32px rgba(0,0,0,.10); }
        .edificio-header {
            background: linear-gradient(135deg,#002855,#064b8d);
            color: #fff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .edificio-titulo { display: flex; align-items: center; gap: 10px; }
        .edificio-titulo i { font-size: 24px; }
        .edificio-titulo h4 { margin: 0; font-size: 19px; font-weight: 800; }
        .cantidad-edificio {
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 20px;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .edificio-body { padding: 20px; }
        .edificio-info { color: #64748b; font-size: 14px; margin-bottom: 16px; }
        .btn-ver-edificio {
            width: 100%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            background: #002855;
            color: #fff;
            border-radius: 10px;
            padding: 11px 15px;
            text-decoration: none;
            font-weight: 700;
        }
        .btn-ver-edificio:hover { background: #064b8d; color: #fff; }

        .tabla-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #edf1f5;
            box-shadow: 0 8px 25px rgba(0,0,0,.07);
            overflow: hidden;
        }
        .tabla-card-header {
            background: linear-gradient(135deg,#002855,#064b8d);
            color: #fff;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .tabla-card-header h4 { margin: 0; font-weight: 800; }
        .tabla-wrap { overflow-x: auto; padding: 20px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1050px; }
        thead th {
            background: #f1f5f9;
            color: #334155;
            padding: 12px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            border-bottom: 1px solid #e2e8f0;
        }
        tbody td {
            padding: 12px;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
            border-bottom: 1px solid #edf1f5;
        }
        tbody tr:hover { background: #f8fbff; }

        .ip-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #eef6ff;
            color: #0056a6;
            border: 1px solid #cfe5ff;
            border-radius: 9px;
            padding: 6px 9px;
            font-family: monospace;
            font-weight: 700;
        }
        .mac-badge {
            display: inline-flex;
            background: #f8fafc;
            color: #475569;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
            padding: 5px 8px;
            font-family: monospace;
            font-size: 12px;
        }
        .estado {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .estado-activo { background: #dcfce7; color: #166534; }
        .estado-mantenimiento { background: #fef3c7; color: #92400e; }
        .estado-inactivo { background: #fee2e2; color: #991b1b; }

        .acciones-tabla { display: flex; gap: 6px; justify-content: center; }
        .btn-accion-tabla {
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .2s ease;
        }
        .btn-editar-punto { background: #eef5fc; color: #0056a6; }
        .btn-editar-punto:hover { background: #dcecff; }
        .btn-eliminar-punto { background: #fee2e2; color: #b91c1c; }
        .btn-eliminar-punto:hover { background: #fecaca; }

        .sin-datos {
            background: #fff;
            border-radius: 20px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
            color: #64748b;
        }
        .sin-datos i { font-size: 50px; margin-bottom: 15px; color: #94a3b8; }

        .modal-header-puntos { background: #002855; color: #fff; }
        .modal-header-puntos .btn-close { filter: brightness(0) invert(1); }

        @media(max-width:768px) {
            .puntos-header h2 { font-size: 26px; }
            .resumen-red { align-items: flex-start; }
        }
    </style>
</head>
<body>

<div class="main puntos-red-page">

    <?php include("../includes/header.php"); ?>

    <div class="content">

        <div class="puntos-header">
            <div>
                <h2><i class="fas fa-network-wired"></i> Puntos de Red</h2>
                <p>Gestión independiente de los puntos de red de Audiovisuales y Automatización.</p>
            </div>

            <?php if ($edificioSeleccionado === ''): ?>
                <div class="acciones-puntos">
                    <button type="button" class="btn-nuevo-punto" data-bs-toggle="modal" data-bs-target="#modalNuevoPunto">
                        <i class="fas fa-plus"></i>
                        Nuevo punto
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="areas-red">
            <a href="puntos_red.php?area=audiovisuales" class="btn-area-red <?= $area === 'audiovisuales' ? 'activo' : 'inactivo'; ?>">
                <i class="fas fa-video"></i>
                Audiovisuales
            </a>

            <a href="puntos_red.php?area=automatizacion" class="btn-area-red <?= $area === 'automatizacion' ? 'activo' : 'inactivo'; ?>">
                <i class="fas fa-shield-halved"></i>
                Automatización
            </a>
        </div>

        <div class="resumen-red">
            <div>
                <div class="resumen-numero"><?= number_format($totalPuntos); ?></div>
                <div class="resumen-texto">Registros encontrados</div>
            </div>
            <div><strong>Área:</strong> <?= e($nombreArea); ?></div>
        </div>

        <?php if ($edificioSeleccionado !== '' && isset($edificios[$edificioSeleccionado])): ?>

            <a href="puntos_red.php?area=<?= urlencode($area); ?>" class="btn-volver">
                <i class="fas fa-arrow-left"></i>
                Volver a edificios
            </a>

            <div class="tabla-card">
                <div class="tabla-card-header">
                    <h4><i class="fas fa-building"></i> <?= e($edificioSeleccionado); ?></h4>
                    <span><?= count($edificios[$edificioSeleccionado]); ?> registros</span>
                </div>

                <div class="tabla-wrap">
                    <table>
                        <thead>
                        <?php if ($area === 'audiovisuales'): ?>
                            <tr>
                                <th>Equipo</th>
                                <th>Aula</th>
                                <th>Ubicación</th>
                                <th>Marca</th>
                                <th>IP</th>
                                <th>MAC</th>
                                <th>Punto</th>
                                <th>Switch</th>
                                <th>Puerto</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>Sistema</th>
                                <th>Item</th>
                                <th>Zona</th>
                                <th>Ubicación</th>
                                <th>IP</th>
                                <th>MAC</th>
                                <th>Rack</th>
                                <th>Switch</th>
                                <th>Puerto</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        <?php endif; ?>
                        </thead>

                        <tbody>
                        <?php foreach ($edificios[$edificioSeleccionado] as $punto): ?>
                            <?php
                            $estado = strtolower(trim($punto['estado'] ?? ''));
                            $claseEstado = ($estado === 'activo' || $estado === 'operativo' || $estado === 'reparado')
                                ? 'estado-activo'
                                : (($estado === 'mantenimiento') ? 'estado-mantenimiento' : 'estado-inactivo');
                            ?>
                            <tr>

                                <?php if ($area === 'audiovisuales'): ?>
                                    <td><strong><?= e($punto['equipo'] ?? '-'); ?></strong></td>
                                    <td><?= e($punto['aula'] ?? '-'); ?></td>
                                    <td><?= e($punto['ubicacion'] ?? '-'); ?></td>
                                    <td><?= e($punto['marca'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (!empty($punto['ip'])): ?>
                                            <span class="ip-badge"><i class="fas fa-globe"></i><?= e($punto['ip']); ?></span>
                                        <?php else: ?>-
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($punto['mac'])): ?>
                                            <span class="mac-badge"><?= e($punto['mac']); ?></span>
                                        <?php else: ?>-
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($punto['punto'] ?? '-'); ?></td>
                                    <td><?= e($punto['switch'] ?? '-'); ?></td>
                                    <td><?= e($punto['puerto'] ?? '-'); ?></td>

                                <?php else: ?>
                                    <td><strong><?= e($punto['sistema'] ?? '-'); ?></strong></td>
                                    <td><?= e($punto['item'] ?? '-'); ?></td>
                                    <td><?= e($punto['zona'] ?? '-'); ?></td>
                                    <td><?= e($punto['ubicacion'] ?? '-'); ?></td>
                                    <td>
                                        <?php if (!empty($punto['ip'])): ?>
                                            <span class="ip-badge"><i class="fas fa-globe"></i><?= e($punto['ip']); ?></span>
                                        <?php else: ?>-
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($punto['mac'])): ?>
                                            <span class="mac-badge"><?= e($punto['mac']); ?></span>
                                        <?php else: ?>-
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($punto['rack'] ?? '-'); ?></td>
                                    <td><?= e($punto['switch'] ?? '-'); ?></td>
                                    <td><?= e($punto['sw_port'] ?? '-'); ?></td>
                                <?php endif; ?>

                                <td>
                                    <span class="estado <?= $claseEstado; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= e($punto['estado'] ?? '-'); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="acciones-tabla">
                                        <?php if ($area === 'audiovisuales'): ?>
                                            <button
                                                type="button"
                                                class="btn-accion-tabla btn-editar-punto"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarAudiovisual<?= (int)$punto['id']; ?>"
                                                title="Editar"
                                            >
                                                <i class="fas fa-pen"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn-accion-tabla btn-eliminar-punto"
                                                onclick="eliminarAudiovisual(<?= (int)$punto['id']; ?>)"
                                                title="Eliminar"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn-accion-tabla btn-editar-punto"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarAutomatizacion<?= (int)$punto['id']; ?>"
                                                title="Editar"
                                            >
                                                <i class="fas fa-pen"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn-accion-tabla btn-eliminar-punto"
                                                onclick="eliminarAutomatizacion(<?= (int)$punto['id']; ?>)"
                                                title="Eliminar"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif (empty($edificios)): ?>

            <div class="sin-datos">
                <i class="fas fa-network-wired"></i>
                <h4>No hay registros</h4>
                <p>No existen puntos de red registrados para esta área.</p>
            </div>

        <?php else: ?>

            <div class="edificios-grid">
                <?php foreach ($edificios as $edificio => $puntos): ?>
                    <div class="edificio-card">
                        <div class="edificio-header">
                            <div class="edificio-titulo">
                                <i class="fas fa-building"></i>
                                <h4><?= e($edificio); ?></h4>
                            </div>
                            <span class="cantidad-edificio">
                                <?= count($puntos); ?> puntos
                            </span>
                        </div>

                        <div class="edificio-body">
                            <div class="edificio-info">
                                <i class="fas fa-network-wired"></i>
                                <?= count($puntos); ?> registros disponibles en este edificio.
                            </div>

                            <a
                                href="puntos_red.php?area=<?= urlencode($area); ?>&edificio=<?= urlencode($edificio); ?>"
                                class="btn-ver-edificio"
                            >
                                Ver edificio
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- =========================================================
     MODAL NUEVO PUNTO
========================================================= -->
<div class="modal fade" id="modalNuevoPunto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-puntos">
                <h5 class="modal-title"><i class="fas fa-network-wired"></i> Nuevo punto de red - <?= e($nombreArea); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <?php if ($area === 'audiovisuales'): ?>
                    <form method="POST" action="../actions/puntos_red/guardar_audiovisual.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Edificio *</label>
                                <input type="text" name="edificio" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Equipo *</label>
                                <input type="text" name="equipo" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Aula</label>
                                <input type="text" name="aula" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ubicación</label>
                                <input type="text" name="ubicacion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sitio de instalación</label>
                                <input type="text" name="sitio_instalacion" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de puerto</label>
                                <input type="text" name="tipo_puerto" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">IP</label>
                                <input type="text" name="ip" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">MAC</label>
                                <input type="text" name="mac" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máscara</label>
                                <input type="text" name="mascara" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Serial</label>
                                <input type="text" name="serial" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Punto</label>
                                <input type="text" name="punto" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Switch</label>
                                <input type="text" name="switch" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Puerto</label>
                                <input type="text" name="puerto" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar punto
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="POST" action="../actions/puntos_red/guardar_automatizacion.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sistema *</label>
                                <input type="text" name="sistema" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Item</label>
                                <input type="text" name="item" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Edificio *</label>
                                <input type="text" name="edificio" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Zona</label>
                                <input type="text" name="zona" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ubicación</label>
                                <input type="text" name="ubicacion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">IP</label>
                                <input type="text" name="ip" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">MAC</label>
                                <input type="text" name="mac" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Servidor</label>
                                <input type="text" name="servidor" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cámara Server</label>
                                <input type="text" name="camara_server" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Administración</label>
                                <input type="text" name="administracion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gateway</label>
                                <input type="text" name="gateway" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">DNS</label>
                                <input type="text" name="dns" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máscara</label>
                                <input type="text" name="mascara" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rack</label>
                                <input type="text" name="rack" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Switch</label>
                                <input type="text" name="switch" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Puerto Switch</label>
                                <input type="text" name="sw_port" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">VLAN</label>
                                <input type="text" name="vlan" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Subsistema</label>
                                <input type="text" name="subsistema" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar punto
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php if ($edificioSeleccionado !== '' && isset($edificios[$edificioSeleccionado])): ?>

    <?php foreach ($edificios[$edificioSeleccionado] as $punto): ?>

        <?php if ($area === 'audiovisuales'): ?>
            <div class="modal fade" id="modalEditarAudiovisual<?= (int)$punto['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header modal-header-puntos">
                            <h5 class="modal-title"><i class="fas fa-edit"></i> Editar punto audiovisual</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <form method="POST" action="../actions/puntos_red/actualizar_audiovisual.php">
                                <input type="hidden" name="id" value="<?= (int)$punto['id']; ?>">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Edificio *</label>
                                        <input type="text" name="edificio" class="form-control" value="<?= e($punto['edificio'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Equipo *</label>
                                        <input type="text" name="equipo" class="form-control" value="<?= e($punto['equipo'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aula</label>
                                        <input type="text" name="aula" class="form-control" value="<?= e($punto['aula'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ubicación</label>
                                        <input type="text" name="ubicacion" class="form-control" value="<?= e($punto['ubicacion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Marca</label>
                                        <input type="text" name="marca" class="form-control" value="<?= e($punto['marca'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sitio de instalación</label>
                                        <input type="text" name="sitio_instalacion" class="form-control" value="<?= e($punto['sitio_instalacion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo de puerto</label>
                                        <input type="text" name="tipo_puerto" class="form-control" value="<?= e($punto['tipo_puerto'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">IP</label>
                                        <input type="text" name="ip" class="form-control" value="<?= e($punto['ip'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">MAC</label>
                                        <input type="text" name="mac" class="form-control" value="<?= e($punto['mac'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Máscara</label>
                                        <input type="text" name="mascara" class="form-control" value="<?= e($punto['mascara'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Serial</label>
                                        <input type="text" name="serial" class="form-control" value="<?= e($punto['serial'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Punto</label>
                                        <input type="text" name="punto" class="form-control" value="<?= e($punto['punto'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Switch</label>
                                        <input type="text" name="switch" class="form-control" value="<?= e($punto['switch'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Puerto</label>
                                        <input type="text" name="puerto" class="form-control" value="<?= e($punto['puerto'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Estado</label>
                                        <select name="estado" class="form-select">
                                            <?php $estadoActual = $punto['estado'] ?? 'Activo'; ?>
                                            <option value="Activo" <?= $estadoActual === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                                            <option value="Inactivo" <?= $estadoActual === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                            <option value="Mantenimiento" <?= $estadoActual === 'Mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="modal fade" id="modalEditarAutomatizacion<?= (int)$punto['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header modal-header-puntos">
                            <h5 class="modal-title"><i class="fas fa-edit"></i> Editar punto de automatización</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <form method="POST" action="../actions/puntos_red/actualizar_automatizacion.php">
                                <input type="hidden" name="id" value="<?= (int)$punto['id']; ?>">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Sistema *</label>
                                        <input type="text" name="sistema" class="form-control" value="<?= e($punto['sistema'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Item</label>
                                        <input type="text" name="item" class="form-control" value="<?= e($punto['item'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Edificio *</label>
                                        <input type="text" name="edificio" class="form-control" value="<?= e($punto['edificio'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Zona</label>
                                        <input type="text" name="zona" class="form-control" value="<?= e($punto['zona'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ubicación</label>
                                        <input type="text" name="ubicacion" class="form-control" value="<?= e($punto['ubicacion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">IP</label>
                                        <input type="text" name="ip" class="form-control" value="<?= e($punto['ip'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">MAC</label>
                                        <input type="text" name="mac" class="form-control" value="<?= e($punto['mac'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Servidor</label>
                                        <input type="text" name="servidor" class="form-control" value="<?= e($punto['servidor'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cámara Server</label>
                                        <input type="text" name="camara_server" class="form-control" value="<?= e($punto['camara_server'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Administración</label>
                                        <input type="text" name="administracion" class="form-control" value="<?= e($punto['administracion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gateway</label>
                                        <input type="text" name="gateway" class="form-control" value="<?= e($punto['gateway'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">DNS</label>
                                        <input type="text" name="dns" class="form-control" value="<?= e($punto['dns'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Máscara</label>
                                        <input type="text" name="mascara" class="form-control" value="<?= e($punto['mascara'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Rack</label>
                                        <input type="text" name="rack" class="form-control" value="<?= e($punto['rack'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Switch</label>
                                        <input type="text" name="switch" class="form-control" value="<?= e($punto['switch'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Puerto Switch</label>
                                        <input type="text" name="sw_port" class="form-control" value="<?= e($punto['sw_port'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">VLAN</label>
                                        <input type="text" name="vlan" class="form-control" value="<?= e($punto['vlan'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Subsistema</label>
                                        <input type="text" name="subsistema" class="form-control" value="<?= e($punto['subsistema'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Estado</label>
                                        <?php $estadoActual = $punto['estado'] ?? 'Activo'; ?>
                                        <select name="estado" class="form-select">
                                            <option value="Activo" <?= $estadoActual === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                                            <option value="Inactivo" <?= $estadoActual === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                            <option value="Mantenimiento" <?= $estadoActual === 'Mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Observaciones</label>
                                        <textarea name="observaciones" class="form-control" rows="3"><?= e($punto['observaciones'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endforeach; ?>
<?php endif; ?>

<script>
function eliminarAudiovisual(id) {
    if (!confirm('¿Está seguro de eliminar este punto audiovisual?')) {
        return;
    }
    window.location.href = '../actions/puntos_red/eliminar_audiovisual.php?id=' + id;
}

function eliminarAutomatizacion(id) {
    if (!confirm('¿Está seguro de eliminar este punto de automatización?')) {
        return;
    }
    window.location.href = '../actions/puntos_red/eliminar_automatizacion.php?id=' + id;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
