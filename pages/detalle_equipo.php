<?php
/* =========================================================
   DETALLE DE EQUIPO - JAVERSTOCK
   ========================================================= */

require_once "../includes/auth.php";
require_once "../includes/permisos.php";
require_once "../includes/conexion.php";

exigirPermiso('ver_inventario');

/* ---------------------------------------------------------
   VALIDAR ID
--------------------------------------------------------- */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: hoja_inventario.php");
    exit;
}

/* ---------------------------------------------------------
   CONSULTAR EQUIPO
--------------------------------------------------------- */
$sql = "SELECT
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
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$equipo = $resultado->fetch_assoc();

$stmt->close();

if (!$equipo) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Equipo no encontrado - JaverStock</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background: #f4f7fb;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Arial, sans-serif;
            }
            .error-card {
                background: #fff;
                border-radius: 20px;
                padding: 45px;
                text-align: center;
                box-shadow: 0 12px 35px rgba(0,40,85,.12);
                max-width: 500px;
            }
            .error-icon {
                width: 75px;
                height: 75px;
                border-radius: 50%;
                background: #fee2e2;
                color: #dc2626;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 30px;
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon">
                <i class="fas fa-exclamation"></i>
            </div>
            <h2>Equipo no encontrado</h2>
            <p class="text-muted">
                El registro solicitado no existe o fue eliminado.
            </p>
            <a href="inventario_tecnico.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver al inventario
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/* ---------------------------------------------------------
   FUNCIONES AUXILIARES
--------------------------------------------------------- */
function valor($valor, $porDefecto = 'No registrado')
{
    $valor = trim((string)$valor);
    return $valor !== '' ? $valor : $porDefecto;
}

function esc($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$estado = trim((string)($equipo['estado'] ?? ''));
$hojaOrigen = trim((string)($equipo['hoja_origen'] ?? ''));

/* Página de retorno */
$urlVolver = 'inventario_tecnico.php';

if ($hojaOrigen !== '') {
    $urlVolver = 'hoja_inventario.php?hoja=' . urlencode($hojaOrigen);
}

$nombreEquipo = valor(
    $equipo['dispositivo'] ?? '',
    valor($equipo['item'] ?? '', 'Equipo')
);

$subtitulo = valor(
    $equipo['descripcion'] ?? '',
    'Información detallada del equipo'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= esc($nombreEquipo) ?> - JaverStock</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7fb;
            color: #17324d;
            font-family: Arial, Helvetica, sans-serif;
        }

        .detalle-page {
            min-height: 100vh;
        }

        .detalle-content {
            padding: 28px;
        }

        /* =====================================================
           ENCABEZADO
        ===================================================== */
        .detalle-header {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 24px;
            border: 1px solid #e1eaf3;
            box-shadow: 0 8px 24px rgba(0, 40, 85, .07);
        }

        .breadcrumb-custom {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            color: #7890a8;
        }

        .breadcrumb-custom a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb-custom a:hover {
            text-decoration: underline;
        }

        .breadcrumb-custom i {
            font-size: 11px;
        }

        .titulo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .titulo-info {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .equipo-icono {
            width: 66px;
            height: 66px;
            flex-shrink: 0;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e6f1ff, #d8eaff);
            color: #0066cc;
            font-size: 28px;
        }

        .titulo-info h1 {
            margin: 0 0 5px;
            color: #062e5b;
            font-size: 28px;
            font-weight: 800;
            word-break: break-word;
        }

        .titulo-info p {
            margin: 0;
            color: #6b8197;
            font-size: 14px;
        }

        .acciones-header {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-volver {
            border: 1px solid #d5e0eb;
            background: #ffffff;
            color: #31516f;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }

        .btn-volver:hover {
            background: #f0f5fa;
            color: #062e5b;
        }

        .btn-editar {
            border: none;
            background: #0066cc;
            color: white;
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
            box-shadow: 0 5px 14px rgba(0,102,204,.18);
        }

        .btn-editar:hover {
            background: #0055aa;
            color: white;
            transform: translateY(-1px);
        }

        /* =====================================================
           TARJETA DE RESUMEN
        ===================================================== */
        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .resumen-card {
            background: #ffffff;
            border: 1px solid #e1eaf3;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(0,40,85,.05);
        }

        .resumen-label {
            color: #7b91a7;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 7px;
        }

        .resumen-value {
            color: #062e5b;
            font-size: 16px;
            font-weight: 800;
            word-break: break-word;
        }

        /* =====================================================
           CONTENIDO PRINCIPAL
        ===================================================== */
        .detalle-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .detalle-card {
            background: #ffffff;
            border: 1px solid #e1eaf3;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 7px 22px rgba(0,40,85,.06);
        }

        .detalle-card.full {
            grid-column: 1 / -1;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 18px 22px;
            background: linear-gradient(135deg, #f8fbff, #eef5fc);
            border-bottom: 1px solid #e2ebf4;
            color: #062e5b;
            font-size: 16px;
            font-weight: 800;
        }

        .card-title-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dcecff;
            color: #0066cc;
        }

        .datos {
            padding: 7px 22px 18px;
        }

        .dato {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 18px;
            padding: 14px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .dato:last-child {
            border-bottom: none;
        }

        .dato-label {
            color: #6f859a;
            font-size: 13px;
            font-weight: 700;
        }

        .dato-value {
            color: #183952;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }

        .dato-value.muted {
            color: #9aaaba;
            font-weight: 500;
        }

        /* =====================================================
           ESTADO
        ===================================================== */
        .estado-ok {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            font-size: 16px;
        }

        .estado-texto {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 9px;
            background: #f1f5f9;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
        }

        /* =====================================================
           DETALLES
        ===================================================== */
        .texto-detalles {
            padding: 22px;
            color: #425b72;
            font-size: 14px;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */
        @media (max-width: 1100px) {
            .resumen-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .detalle-grid {
                grid-template-columns: 1fr;
            }

            .detalle-card.full {
                grid-column: auto;
            }
        }

        @media (max-width: 768px) {
            .detalle-content {
                padding: 18px;
            }

            .titulo-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .acciones-header {
                width: 100%;
            }

            .acciones-header a {
                flex: 1;
                text-align: center;
            }

            .titulo-info h1 {
                font-size: 22px;
            }

            .resumen-grid {
                grid-template-columns: 1fr;
            }

            .dato {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }

        @media (max-width: 480px) {
            .detalle-content {
                padding: 12px;
            }

            .detalle-header {
                padding: 18px;
            }

            .titulo-info {
                align-items: flex-start;
            }

            .equipo-icono {
                width: 54px;
                height: 54px;
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<div class="detalle-page">

    <?php include("../includes/sidebar.php"); ?>

    <div class="main">

        <?php include("../includes/header.php"); ?>

        <div class="detalle-content">

            <!-- =================================================
                 ENCABEZADO
            ================================================== -->
            <section class="detalle-header">

                <div class="breadcrumb-custom">
                    <a href="inventario_tecnico.php">
                        <i class="fas fa-microchip"></i>
                        Inventario Técnico
                    </a>

                    <i class="fas fa-chevron-right"></i>

                    <?php if ($hojaOrigen !== ''): ?>
                        <a href="<?= esc($urlVolver) ?>">
                            <?= esc($hojaOrigen) ?>
                        </a>

                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>

                    <span>Detalle del equipo</span>
                </div>

                <div class="titulo-row">

                    <div class="titulo-info">

                        <div class="equipo-icono">
                            <i class="fas fa-microchip"></i>
                        </div>

                        <div>
                            <h1><?= esc($nombreEquipo) ?></h1>

                            <p>
                                <?= esc($subtitulo) ?>
                            </p>
                        </div>

                    </div>

                    <div class="acciones-header">

                        <a href="<?= esc($urlVolver) ?>" class="btn-volver">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver
                        </a>

                        <a href="editar_equipo.php?id=<?= (int)$equipo['id'] ?>"
                           class="btn-editar">
                            <i class="fas fa-pen me-2"></i>
                            Editar
                        </a>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 RESUMEN
            ================================================== -->
            <section class="resumen-grid">

                <div class="resumen-card">
                    <div class="resumen-label">Código activo</div>
                    <div class="resumen-value">
                        <?= esc(valor($equipo['codigo_activo'])) ?>
                    </div>
                </div>

                <div class="resumen-card">
                    <div class="resumen-label">Sistema</div>
                    <div class="resumen-value">
                        <?= esc(valor($equipo['sistema'])) ?>
                    </div>
                </div>

                <div class="resumen-card">
                    <div class="resumen-label">Edificio</div>
                    <div class="resumen-value">
                        <?= esc(valor($equipo['edificio'])) ?>
                    </div>
                </div>

                <div class="resumen-card">
                    <div class="resumen-label">Estado</div>
                    <div class="resumen-value">

                        <?php if (mb_strtolower($estado, 'UTF-8') === 'registrado'): ?>

                            <span class="estado-ok" title="Registrado">
                                <i class="fas fa-check"></i>
                            </span>

                        <?php elseif ($estado !== ''): ?>

                            <span class="estado-texto">
                                <?= esc($estado) ?>
                            </span>

                        <?php else: ?>

                            <span class="estado-texto">
                                Sin estado
                            </span>

                        <?php endif; ?>

                    </div>
                </div>

            </section>


            <!-- =================================================
                 DATOS
            ================================================== -->
            <section class="detalle-grid">

                <!-- Información general -->
                <div class="detalle-card">

                    <div class="card-title">
                        <span class="card-title-icon">
                            <i class="fas fa-circle-info"></i>
                        </span>
                        Información general
                    </div>

                    <div class="datos">

                        <div class="dato">
                            <div class="dato-label">ID</div>
                            <div class="dato-value">
                                <?= (int)$equipo['id'] ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Equipo</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['dispositivo'], valor($equipo['item']))) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Ítem</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['item'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Categoría</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['categoria'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Tipo</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['tipo'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Cantidad</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['cantidad'], '0')) ?>
                            </div>
                        </div>

                    </div>

                </div>


                <!-- Ubicación -->
                <div class="detalle-card">

                    <div class="card-title">
                        <span class="card-title-icon">
                            <i class="fas fa-location-dot"></i>
                        </span>
                        Ubicación
                    </div>

                    <div class="datos">

                        <div class="dato">
                            <div class="dato-label">Edificio</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['edificio'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Ubicación</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['ubicacion'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Dirección</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['direccion'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Hoja de origen</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['hoja_origen'])) ?>
                            </div>
                        </div>

                    </div>

                </div>


                <!-- Red -->
                <div class="detalle-card">

                    <div class="card-title">
                        <span class="card-title-icon">
                            <i class="fas fa-network-wired"></i>
                        </span>
                        Información de red
                    </div>

                    <div class="datos">

                        <div class="dato">
                            <div class="dato-label">IP</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['ip'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">MAC</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['mac'])) ?>
                            </div>
                        </div>

                    </div>

                </div>


                <!-- Identificación -->
                <div class="detalle-card">

                    <div class="card-title">
                        <span class="card-title-icon">
                            <i class="fas fa-barcode"></i>
                        </span>
                        Identificación
                    </div>

                    <div class="datos">

                        <div class="dato">
                            <div class="dato-label">Código activo</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['codigo_activo'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Referencia</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['referencia'])) ?>
                            </div>
                        </div>

                        <div class="dato">
                            <div class="dato-label">Sistema</div>
                            <div class="dato-value">
                                <?= esc(valor($equipo['sistema'])) ?>
                            </div>
                        </div>

                    </div>

                </div>


                <!-- Descripción -->
                <div class="detalle-card full">

                    <div class="card-title">
                        <span class="card-title-icon">
                            <i class="fas fa-align-left"></i>
                        </span>
                        Descripción
                    </div>

                    <div class="texto-detalles">
                        <?= esc(valor($equipo['descripcion'], 'No hay una descripción registrada para este equipo.')) ?>
                    </div>

                </div>


                <!-- Detalles adicionales -->
                <div class="detalle-card full">

                    <div class="card-title">
                        <span class="card-title-icon">
                            <i class="fas fa-list-check"></i>
                        </span>
                        Detalles adicionales
                    </div>

                    <div class="texto-detalles">
                        <?= esc(valor($equipo['detalles'], 'No hay información adicional registrada.')) ?>
                    </div>

                </div>

            </section>

        </div>

        <?php include("../includes/footer.php"); ?>

    </div>

</div>

</body>
</html>
