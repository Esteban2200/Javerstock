<?php
/* =========================================================
   JAVERSTOCK - ACTUALIZAR EQUIPO TÉCNICO
   Este archivo SOLO modifica la tabla inventario_tecnico.
   ========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

exigirPermiso('ver_inventario');

/* =========================================================
   VALIDAR MÉTODO
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/inventario_tecnico.php');
    exit;
}

/* =========================================================
   VALIDAR CONEXIÓN
   ========================================================= */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('No se encontró una conexión mysqli válida.');
}

/* =========================================================
   FUNCIÓN PARA OBTENER DATOS
   ========================================================= */
function datoPost($nombre)
{
    return isset($_POST[$nombre])
        ? trim((string)$_POST[$nombre])
        : '';
}

/* =========================================================
   ID
   ========================================================= */
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: ../../pages/inventario_tecnico.php');
    exit;
}

/* =========================================================
   DATOS DEL FORMULARIO
   ========================================================= */
$sistema       = datoPost('sistema');
$categoria     = datoPost('categoria');
$item          = datoPost('item');
$dispositivo   = datoPost('dispositivo');
$edificio      = datoPost('edificio');
$ubicacion     = datoPost('ubicacion');
$direccion     = datoPost('direccion');
$ip            = datoPost('ip');
$mac           = datoPost('mac');
$referencia    = datoPost('referencia');
$cantidadTexto = datoPost('cantidad');
$tipo          = datoPost('tipo');
$codigoActivo  = datoPost('codigo_activo');
$descripcion   = datoPost('descripcion');
$detalles      = datoPost('detalles');
$estado        = datoPost('estado');

/* =========================================================
   CANTIDAD
   ========================================================= */
$cantidad = $cantidadTexto === '' ? 0 : (int)$cantidadTexto;

if ($cantidad < 0) {
    $cantidad = 0;
}

/* =========================================================
   CONSULTAR HOJA DE ORIGEN
   No se modifica.
   ========================================================= */
$stmtHoja = $conn->prepare(
    "SELECT hoja_origen
     FROM inventario_tecnico
     WHERE id = ?
     LIMIT 1"
);

if (!$stmtHoja) {
    die('Error al preparar la consulta: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$stmtHoja->bind_param('i', $id);
$stmtHoja->execute();

$stmtHoja->bind_result($hojaOrigen);

if (!$stmtHoja->fetch()) {
    $stmtHoja->close();

    header('Location: ../../pages/inventario_tecnico.php');
    exit;
}

$stmtHoja->close();

/* =========================================================
   ACTUALIZAR SOLAMENTE inventario_tecnico
   ========================================================= */
$sql = "UPDATE inventario_tecnico SET
            sistema = ?,
            categoria = ?,
            item = ?,
            edificio = ?,
            dispositivo = ?,
            descripcion = ?,
            tipo = ?,
            codigo_activo = ?,
            ubicacion = ?,
            direccion = ?,
            ip = ?,
            mac = ?,
            referencia = ?,
            cantidad = ?,
            detalles = ?,
            estado = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        'Error al preparar la actualización: ' .
        htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8')
    );
}

/*
 * 13 strings + cantidad integer + detalles string + estado string + id integer
 *
 * Tipos:
 * s s s s s s s s s s s s s i s s i
 */
$stmt->bind_param(
    'sssssssssssssissi',
    $sistema,
    $categoria,
    $item,
    $edificio,
    $dispositivo,
    $descripcion,
    $tipo,
    $codigoActivo,
    $ubicacion,
    $direccion,
    $ip,
    $mac,
    $referencia,
    $cantidad,
    $detalles,
    $estado,
    $id
);

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();

    die(
        'Error al actualizar el equipo: ' .
        htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
    );
}

$filasAfectadas = $stmt->affected_rows;
$stmt->close();

/* =========================================================
   REDIRECCIÓN
   Volvemos al detalle del mismo equipo.
   ========================================================= */
header(
    'Location: ../../pages/detalle_equipo.php?id=' .
    $id .
    '&actualizado=1'
);
exit;
?>
