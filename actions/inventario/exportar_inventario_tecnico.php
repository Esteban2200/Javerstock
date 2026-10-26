<?php
/**
 * JaverStock - Exportar Inventario Técnico a Excel
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/auth.php';
require_once '../../includes/conexion.php';
require_once '../../includes/permisos.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

exigirPermiso('ver_inventario');

// ---------------------------------------------------------
// FILTROS
// ---------------------------------------------------------
$busqueda = trim($_GET['buscar'] ?? '');
$sistema  = trim($_GET['sistema'] ?? '');
$edificio = trim($_GET['edificio'] ?? '');
$tipo     = trim($_GET['tipo'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($busqueda !== '') {
    $where[] = '(codigo_activo LIKE ? OR dispositivo LIKE ? OR descripcion LIKE ? OR ubicacion LIKE ? OR ip LIKE ?)';
    $buscarLike = '%' . $busqueda . '%';

    for ($i = 0; $i < 5; $i++) {
        $params[] = $buscarLike;
        $types .= 's';
    }
}

if ($sistema !== '') {
    $where[] = 'sistema = ?';
    $params[] = $sistema;
    $types .= 's';
}

if ($edificio !== '') {
    $where[] = 'edificio = ?';
    $params[] = $edificio;
    $types .= 's';
}

if ($tipo !== '') {
    $where[] = 'tipo = ?';
    $params[] = $tipo;
    $types .= 's';
}

$whereSQL = !empty($where)
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

// ---------------------------------------------------------
// CONSULTA COMPLETA
// ---------------------------------------------------------
$sql = "
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
        estado,
        datos_extra
    FROM inventario_tecnico
    $whereSQL
    ORDER BY id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    exit('Error preparando la exportación: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();

// ---------------------------------------------------------
// CREAR EXCEL
// ---------------------------------------------------------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventario Técnico');

$encabezados = [
    'Código activo',
    'Equipo',
    'Descripción',
    'Sistema',
    'Categoría',
    'Hoja origen',
    'Ítem',
    'Tipo',
    'Edificio',
    'Ubicación',
    'Dirección',
    'IP',
    'MAC',
    'Referencia',
    'Cantidad',
    'Detalles',
    'Estado'
];

// Encabezados
$sheet->fromArray($encabezados, null, 'A1');

$ultimaColumna = 'Q';

$sheet->getStyle("A1:$ultimaColumna" . '1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '002855'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

$sheet->getRowDimension(1)->setRowHeight(25);

$fila = 2;

while ($row = $resultado->fetch_assoc()) {
    $sheet->fromArray([
        $row['codigo_activo'] ?? '',
        $row['dispositivo'] ?? '',
        $row['descripcion'] ?? '',
        $row['sistema'] ?? '',
        $row['categoria'] ?? '',
        $row['hoja_origen'] ?? '',
        $row['item'] ?? '',
        $row['tipo'] ?? '',
        $row['edificio'] ?? '',
        $row['ubicacion'] ?? '',
        $row['direccion'] ?? '',
        $row['ip'] ?? '',
        $row['mac'] ?? '',
        $row['referencia'] ?? '',
        $row['cantidad'] ?? '',
        $row['detalles'] ?? '',
        $row['estado'] ?? 'Registrado'
    ], null, 'A' . $fila);

    $fila++;
}

$stmt->close();

// ---------------------------------------------------------
// FORMATO
// ---------------------------------------------------------
$ultimaFila = max(1, $fila - 1);

$sheet->freezePane('A2');
$sheet->setAutoFilter("A1:$ultimaColumna$ultimaFila");

$anchos = [
    'A' => 20,
    'B' => 30,
    'C' => 40,
    'D' => 24,
    'E' => 22,
    'F' => 25,
    'G' => 20,
    'H' => 24,
    'I' => 22,
    'J' => 30,
    'K' => 20,
    'L' => 18,
    'M' => 20,
    'N' => 25,
    'O' => 12,
    'P' => 40,
    'Q' => 18,
];

foreach ($anchos as $columna => $ancho) {
    $sheet->getColumnDimension($columna)->setWidth($ancho);
}

if ($ultimaFila >= 2) {
    $sheet->getStyle("A2:$ultimaColumna$ultimaFila")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle("A2:$ultimaColumna$ultimaFila")->getAlignment()->setWrapText(true);
}

// ---------------------------------------------------------
// DESCARGA
// ---------------------------------------------------------
$fecha = date('Y-m-d_H-i-s');
$nombreArchivo = 'inventario_tecnico_' . $fecha . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
