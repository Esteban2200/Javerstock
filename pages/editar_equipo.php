<?php
/* =========================================================
   JAVERSTOCK - EDITAR EQUIPO TÉCNICO
   ========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../includes/conexion.php';

exigirPermiso('ver_inventario');

function esc($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function valorCampo($valor)
{
    return trim((string)$valor);
}

/* =========================================================
   VALIDAR CONEXIÓN
   ========================================================= */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('No se encontró una conexión mysqli válida.');
}

/* =========================================================
   OBTENER ID
   ========================================================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: inventario_tecnico.php');
    exit;
}

/* =========================================================
   CONSULTAR EQUIPO
   ========================================================= */
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
    die('Error al preparar la consulta: ' . esc($conn->error));
}

$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    die('Error al consultar el equipo: ' . esc($stmt->error));
}

$stmt->bind_result(
    $db_id,
    $db_sistema,
    $db_categoria,
    $db_hoja_origen,
    $db_item,
    $db_edificio,
    $db_dispositivo,
    $db_descripcion,
    $db_tipo,
    $db_codigo_activo,
    $db_ubicacion,
    $db_direccion,
    $db_ip,
    $db_mac,
    $db_referencia,
    $db_cantidad,
    $db_detalles,
    $db_estado
);

if (!$stmt->fetch()) {
    $stmt->close();

    http_response_code(404);
    die('El equipo solicitado no existe.');
}

$stmt->close();

/* =========================================================
   ARRAY DEL EQUIPO
   ========================================================= */
$equipo = [
    'id'            => $db_id,
    'sistema'       => $db_sistema,
    'categoria'     => $db_categoria,
    'hoja_origen'   => $db_hoja_origen,
    'item'          => $db_item,
    'edificio'      => $db_edificio,
    'dispositivo'   => $db_dispositivo,
    'descripcion'   => $db_descripcion,
    'tipo'          => $db_tipo,
    'codigo_activo' => $db_codigo_activo,
    'ubicacion'     => $db_ubicacion,
    'direccion'     => $db_direccion,
    'ip'            => $db_ip,
    'mac'           => $db_mac,
    'referencia'    => $db_referencia,
    'cantidad'      => $db_cantidad,
    'detalles'      => $db_detalles,
    'estado'        => $db_estado
];

$hojaOrigen = valorCampo($equipo['hoja_origen']);

$urlVolver = 'detalle_equipo.php?id=' . (int)$equipo['id'];

if ($hojaOrigen !== '') {
    $urlVolver = 'detalle_equipo.php?id=' . (int)$equipo['id'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar equipo - JaverStock</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        rel="stylesheet"
    >

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

        .main {
            min-height: 100vh;
        }

        .editar-content {
            padding: 25px;
        }

        .page-header {
            background: #fff;
            border: 1px solid #e1eaf3;
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 22px;
            box-shadow: 0 8px 24px rgba(0,40,85,.07);
        }

        .breadcrumb-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            color: #8295a8;
            font-size: 13px;
        }

        .breadcrumb-custom a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 700;
        }

        .breadcrumb-custom i {
            font-size: 10px;
        }

        .title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .title-info {
            display: flex;
            align-items: center;
            gap: 17px;
            min-width: 0;
        }

        .title-icon {
            width: 65px;
            height: 65px;
            flex: 0 0 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: linear-gradient(135deg, #e6f1ff, #d8eaff);
            color: #0066cc;
            font-size: 27px;
        }

        .title-info h1 {
            margin: 0 0 5px;
            color: #062e5b;
            font-size: 27px;
            font-weight: 800;
        }

        .title-info p {
            margin: 0;
            color: #71869b;
            font-size: 14px;
        }

        .form-card {
            background: #fff;
            border: 1px solid #e1eaf3;
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 8px 24px rgba(0,40,85,.06);
        }

        .section {
            margin-bottom: 28px;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e6edf4;
            color: #062e5b;
            font-size: 17px;
            font-weight: 800;
        }

        .section-title-icon {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #dcecff;
            color: #0066cc;
        }

        .form-label {
            margin-bottom: 7px;
            color: #48627a;
            font-size: 13px;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            min-height: 44px;
            border: 1px solid #d6e2ec;
            border-radius: 10px;
            color: #183952;
            font-size: 14px;
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0,102,204,.10);
        }

        .readonly-field {
            background: #f3f6f9 !important;
            color: #708398 !important;
            cursor: not-allowed;
        }

        .help-text {
            margin-top: 5px;
            color: #8a9bac;
            font-size: 11px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 22px;
            border-top: 1px solid #e6edf4;
        }

        .btn-cancelar,
        .btn-guardar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 19px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn-cancelar {
            color: #31516f;
            background: #fff;
            border: 1px solid #d6e1eb;
        }

        .btn-cancelar:hover {
            background: #f1f5f9;
            color: #062e5b;
        }

        .btn-guardar {
            color: #fff;
            background: #0066cc;
            border: 1px solid #0066cc;
            box-shadow: 0 5px 14px rgba(0,102,204,.18);
        }

        .btn-guardar:hover {
            background: #0055aa;
            border-color: #0055aa;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .editar-content {
                padding: 17px;
            }

            .title-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .title-info h1 {
                font-size: 22px;
            }

            .form-card {
                padding: 18px;
            }

            .actions {
                flex-direction: column-reverse;
            }

            .actions a,
            .actions button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="editar-content">

        <section class="page-header">

            <div class="breadcrumb-custom">
                <a href="inventario_tecnico.php">
                    <i class="fas fa-microchip"></i>
                    Inventario Técnico
                </a>

                <i class="fas fa-chevron-right"></i>

                <a href="<?= esc($urlVolver) ?>">
                    Detalle del equipo
                </a>

                <i class="fas fa-chevron-right"></i>

                <span>Editar</span>
            </div>

            <div class="title-row">

                <div class="title-info">

                    <div class="title-icon">
                        <i class="fas fa-pen-to-square"></i>
                    </div>

                    <div>
                        <h1>Editar equipo</h1>
                        <p>
                            Modifica la información del equipo técnico seleccionado.
                        </p>
                    </div>

                </div>

            </div>

        </section>


        <form
            action="../actions/inventario/actualizar_equipo.php"
            method="POST"
            class="form-card"
            autocomplete="off"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$equipo['id'] ?>"
            >

            <!-- =================================================
                 INFORMACIÓN GENERAL
            ================================================== -->
            <section class="section">

                <div class="section-title">
                    <span class="section-title-icon">
                        <i class="fas fa-circle-info"></i>
                    </span>
                    Información general
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Sistema</label>
                        <input
                            type="text"
                            name="sistema"
                            class="form-control"
                            value="<?= esc($equipo['sistema']) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Categoría</label>
                        <input
                            type="text"
                            name="categoria"
                            class="form-control"
                            value="<?= esc($equipo['categoria']) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Ítem</label>
                        <input
                            type="text"
                            name="item"
                            class="form-control"
                            value="<?= esc($equipo['item']) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Dispositivo / Equipo</label>
                        <input
                            type="text"
                            name="dispositivo"
                            class="form-control"
                            value="<?= esc($equipo['dispositivo']) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo</label>
                        <input
                            type="text"
                            name="tipo"
                            class="form-control"
                            value="<?= esc($equipo['tipo']) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cantidad</label>
                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            min="0"
                            value="<?= esc($equipo['cantidad']) ?>"
                        >
                    </div>

                </div>

            </section>


            <!-- =================================================
                 UBICACIÓN
            ================================================== -->
            <section class="section">

                <div class="section-title">
                    <span class="section-title-icon">
                        <i class="fas fa-location-dot"></i>
                    </span>
                    Ubicación
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Edificio</label>
                        <input
                            type="text"
                            name="edificio"
                            class="form-control"
                            value="<?= esc($equipo['edificio']) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Ubicación</label>
                        <input
                            type="text"
                            name="ubicacion"
                            class="form-control"
                            value="<?= esc($equipo['ubicacion']) ?>"
                        >
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Dirección</label>
                        <input
                            type="text"
                            name="direccion"
                            class="form-control"
                            value="<?= esc($equipo['direccion']) ?>"
                        >
                    </div>

                </div>

            </section>


            <!-- =================================================
                 RED
            ================================================== -->
            <section class="section">

                <div class="section-title">
                    <span class="section-title-icon">
                        <i class="fas fa-network-wired"></i>
                    </span>
                    Información de red
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Dirección IP</label>
                        <input
                            type="text"
                            name="ip"
                            class="form-control"
                            value="<?= esc($equipo['ip']) ?>"
                            placeholder="Ej. 192.168.1.100"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Dirección MAC</label>
                        <input
                            type="text"
                            name="mac"
                            class="form-control"
                            value="<?= esc($equipo['mac']) ?>"
                            placeholder="Ej. AA:BB:CC:DD:EE:FF"
                        >
                    </div>

                </div>

            </section>


            <!-- =================================================
                 IDENTIFICACIÓN
            ================================================== -->
            <section class="section">

                <div class="section-title">
                    <span class="section-title-icon">
                        <i class="fas fa-barcode"></i>
                    </span>
                    Identificación
                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Código de activo
                        </label>

                        <input
                            type="text"
                            name="codigo_activo"
                            class="form-control"
                            value="<?= esc($equipo['codigo_activo']) ?>"
                        >

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Referencia
                        </label>

                        <input
                            type="text"
                            name="referencia"
                            class="form-control"
                            value="<?= esc($equipo['referencia']) ?>"
                        >

                    </div>

                </div>

            </section>


            <!-- =================================================
                 ESTADO
            ================================================== -->
            <section class="section">

                <div class="section-title">
                    <span class="section-title-icon">
                        <i class="fas fa-circle-check"></i>
                    </span>
                    Estado
                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Estado del equipo
                        </label>

                        <select
                            name="estado"
                            class="form-select"
                        >

                            <option
                                value=""
                                <?= $equipo['estado'] === '' ? 'selected' : '' ?>
                            >
                                Seleccionar estado
                            </option>

                            <option
                                value="Registrado"
                                <?= strcasecmp((string)$equipo['estado'], 'Registrado') === 0 ? 'selected' : '' ?>
                            >
                                Registrado
                            </option>

                            <option
                                value="Activo"
                                <?= strcasecmp((string)$equipo['estado'], 'Activo') === 0 ? 'selected' : '' ?>
                            >
                                Activo
                            </option>

                            <option
                                value="Inactivo"
                                <?= strcasecmp((string)$equipo['estado'], 'Inactivo') === 0 ? 'selected' : '' ?>
                            >
                                Inactivo
                            </option>

                            <option
                                value="Mantenimiento"
                                <?= strcasecmp((string)$equipo['estado'], 'Mantenimiento') === 0 ? 'selected' : '' ?>
                            >
                                Mantenimiento
                            </option>

                            <option
                                value="Fuera de servicio"
                                <?= strcasecmp((string)$equipo['estado'], 'Fuera de servicio') === 0 ? 'selected' : '' ?>
                            >
                                Fuera de servicio
                            </option>

                        </select>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 DESCRIPCIÓN Y DETALLES
            ================================================== -->
            <section class="section">

                <div class="section-title">
                    <span class="section-title-icon">
                        <i class="fas fa-align-left"></i>
                    </span>
                    Descripción y detalles
                </div>

                <div class="row g-3">

                    <div class="col-12">

                        <label class="form-label">
                            Descripción
                        </label>

                        <textarea
                            name="descripcion"
                            class="form-control"
                            rows="4"
                            placeholder="Descripción del equipo..."
                        ><?= esc($equipo['descripcion']) ?></textarea>

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Detalles adicionales
                        </label>

                        <textarea
                            name="detalles"
                            class="form-control"
                            rows="5"
                            placeholder="Información adicional del equipo..."
                        ><?= esc($equipo['detalles']) ?></textarea>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 BOTONES
            ================================================== -->
            <div class="actions">

                <a
                    href="<?= esc($urlVolver) ?>"
                    class="btn-cancelar"
                >
                    <i class="fas fa-xmark me-2"></i>
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn-guardar"
                >
                    <i class="fas fa-floppy-disk me-2"></i>
                    Guardar cambios
                </button>

            </div>

        </form>

    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</div>

</body>
</html>
