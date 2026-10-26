<?php
/* =========================================================
   JAVERSTOCK - TABLA DE UNA HOJA DEL INVENTARIO TÉCNICO
   Archivo: pages/hoja_inventario.php
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('ver_inventario');

/* =========================================================
   HOJA SELECCIONADA
   ========================================================= */

$hoja = trim((string) ($_GET['hoja'] ?? ''));

if ($hoja === '') {
    header('Location: inventario_tecnico.php');
    exit;
}

/* =========================================================
   CONFIGURACIÓN VISUAL
   ========================================================= */

function normalizarNombreHojaTabla($texto)
{
    $texto = trim((string) $texto);
    $texto = str_replace("\xC2\xA0", ' ', $texto);
    $texto = preg_replace('/\s+/u', ' ', $texto);

    if (function_exists('iconv')) {
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($normalizado !== false) {
            $texto = $normalizado;
        }
    }

    return strtolower(trim($texto));
}

function configuracionHojaTabla($nombre)
{
    $n = normalizarNombreHojaTabla($nombre);

    $config = [
        'icono' => 'fa-file-excel',
        'color' => '#2776c9',
        'fondo' => '#eff6ff',
        'descripcion' => 'Registros del inventario técnico.'
    ];

    if (strpos($n, 'cctv') !== false) {
        $config = ['icono'=>'fa-video', 'color'=>'#1677d2', 'fondo'=>'#edf7ff', 'descripcion'=>'Cámaras y equipos de videovigilancia.'];
    } elseif (strpos($n, 'control') !== false) {
        $config = ['icono'=>'fa-door-open', 'color'=>'#10a66f', 'fondo'=>'#edfbf5', 'descripcion'=>'Controladores, lectores y paneles de acceso.'];
    } elseif (strpos($n, 'torniquete') !== false) {
        $config = ['icono'=>'fa-arrows-left-right', 'color'=>'#7040df', 'fondo'=>'#f4efff', 'descripcion'=>'Torniquetes y accesos peatonales.'];
    } elseif (strpos($n, 'perifer') !== false) {
        $config = ['icono'=>'fa-microchip', 'color'=>'#ed8b00', 'fondo'=>'#fff6e9', 'descripcion'=>'Periféricos y accesorios de control de acceso.'];
    } elseif (strpos($n, 'incendio') !== false || strpos($n, 'deteccion') !== false) {
        $config = ['icono'=>'fa-fire', 'color'=>'#ed3347', 'fondo'=>'#fff0f2', 'descripcion'=>'Sistemas de detección y alarma.'];
    } elseif (strpos($n, 'intrusion') !== false) {
        $config = ['icono'=>'fa-shield-halved', 'color'=>'#12aeb2', 'fondo'=>'#edfafa', 'descripcion'=>'Alarmas y sistemas de intrusión.'];
    } elseif (strpos($n, 'datacenter') !== false || strpos($n, 'data center') !== false) {
        $config = ['icono'=>'fa-server', 'color'=>'#8050d8', 'fondo'=>'#f5f0ff', 'descripcion'=>'Equipos y servidores del centro de datos.'];
    } elseif (strpos($n, 'biblioteca') !== false) {
        $config = ['icono'=>'fa-book', 'color'=>'#e62f80', 'fondo'=>'#fff0f7', 'descripcion'=>'Equipos registrados en la biblioteca.'];
    } elseif (strpos($n, 'bioterio') !== false) {
        $config = ['icono'=>'fa-flask', 'color'=>'#18a873', 'fondo'=>'#edf9f4', 'descripcion'=>'Equipos del área de bioterio.'];
    } elseif (strpos($n, 'ingenier') !== false) {
        $config = ['icono'=>'fa-building', 'color'=>'#2678dc', 'fondo'=>'#edf5ff', 'descripcion'=>'Equipos registrados en Ingeniería.'];
    } elseif (strpos($n, 'artes') !== false) {
        $config = ['icono'=>'fa-building', 'color'=>'#3478e5', 'fondo'=>'#edf5ff', 'descripcion'=>'Equipos de los edificios Artes 1 y 2.'];
    } elseif (strpos($n, 'maldonado') !== false) {
        $config = ['icono'=>'fa-building', 'color'=>'#ed9200', 'fondo'=>'#fff7e9', 'descripcion'=>'Equipos registrados en Maldonado.'];
    } elseif (strpos($n, 'hoyos') !== false) {
        $config = ['icono'=>'fa-building', 'color'=>'#13a5a9', 'fondo'=>'#edfbfb', 'descripcion'=>'Equipos registrados en Hoyos.'];
    } elseif (strpos($n, 'atico') !== false) {
        $config = ['icono'=>'fa-building', 'color'=>'#f05a3c', 'fondo'=>'#fff2ee', 'descripcion'=>'Equipos registrados en Ático.'];
    } elseif (strpos($n, 'extension') !== false) {
        $config = ['icono'=>'fa-building', 'color'=>'#763bdd', 'fondo'=>'#f5f0ff', 'descripcion'=>'Equipos registrados en Extensión.'];
    }

    return $config;
}

$configHoja = configuracionHojaTabla($hoja);

/* =========================================================
   BÚSQUEDA Y PAGINACIÓN
   ========================================================= */

$buscar = trim((string) ($_GET['buscar'] ?? ''));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 25;

/*
 * La página Ubicaciones identifica cada sistema con esta prioridad:
 * hoja_origen -> sistema -> categoria -> 'Sin clasificar'.
 * Usamos el mismo criterio aquí para que el contador y la tabla
 * muestren exactamente los mismos registros que corresponden a la hoja.
 */
$campoHoja = "COALESCE(NULLIF(TRIM(hoja_origen), ''), NULLIF(TRIM(sistema), ''), NULLIF(TRIM(categoria), ''), 'Sin clasificar')";
$where = ["LOWER(TRIM(REPLACE($campoHoja, CHAR(194,160), ' '))) = LOWER(TRIM(REPLACE(?, CHAR(194,160), ' ')))"];
$params = [$hoja];
$types = 's';

if ($buscar !== '') {
    $where[] = "(
        codigo_activo LIKE ? OR
        dispositivo LIKE ? OR
        descripcion LIKE ? OR
        ubicacion LIKE ? OR
        ip LIKE ? OR
        mac LIKE ? OR
        referencia LIKE ? OR
        edificio LIKE ? OR
        tipo LIKE ? OR
        estado LIKE ?
    )";

    for ($i = 0; $i < 10; $i++) {
        $params[] = '%' . $buscar . '%';
    }
    $types .= str_repeat('s', 10);
}

$whereSql = implode(' AND ', $where);

/* =========================================================
   TOTAL DE REGISTROS
   ========================================================= */

$sqlTotal = "SELECT COUNT(*) AS total FROM inventario_tecnico WHERE {$whereSql}";
$stmtTotal = $conn->prepare($sqlTotal);
$total = 0;

if ($stmtTotal) {
    $stmtTotal->bind_param($types, ...$params);
    $stmtTotal->execute();
    $resultadoTotal = $stmtTotal->get_result();
    if ($filaTotal = $resultadoTotal->fetch_assoc()) {
        $total = (int) $filaTotal['total'];
    }
    $stmtTotal->close();
}

$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$offset = ($pagina - 1) * $porPagina;

/* =========================================================
   REGISTROS
   ========================================================= */

$sqlDatos = "
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
    WHERE {$whereSql}
    ORDER BY id DESC
    LIMIT ? OFFSET ?
";

$stmtDatos = $conn->prepare($sqlDatos);
$registros = [];

if ($stmtDatos) {
    $paramsDatos = $params;
    $typesDatos = $types . 'ii';
    $paramsDatos[] = $porPagina;
    $paramsDatos[] = $offset;

    $stmtDatos->bind_param($typesDatos, ...$paramsDatos);
    $stmtDatos->execute();
    $resultadoDatos = $stmtDatos->get_result();

    while ($fila = $resultadoDatos->fetch_assoc()) {
        $registros[] = $fila;
    }

    $stmtDatos->close();
}

function e($valor)
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function valorTabla($valor, $vacio = '—')
{
    $valor = trim((string) ($valor ?? ''));
    return $valor === '' ? $vacio : e($valor);
}

function urlPaginaHoja($hoja, $buscar, $pagina)
{
    return 'hoja_inventario.php?' . http_build_query([
        'hoja' => $hoja,
        'buscar' => $buscar,
        'pagina' => $pagina
    ]);
}
?>

<style>
/* =========================================================
   TABLA DE HOJA
   ========================================================= */

.hoja-page .content {
    padding: 24px 28px 40px;
}

.hoja-wrapper {
    width: 100%;
}

.hoja-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
}

.hoja-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #174d80;
    text-decoration: none;
    font-size: 14px;
    font-weight: 750;
}

.hoja-back:hover {
    color: #0068d7;
}

.hoja-header-card {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 22px 24px;
    background: #fff;
    border: 1px solid #dce7f5;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(6,46,91,.07);
    margin-bottom: 18px;
}

.hoja-header-icon {
    width: 64px;
    height: 64px;
    flex: 0 0 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 17px;
    color: #fff;
    font-size: 25px;
    background: <?= e($configHoja['color']); ?>;
    box-shadow: 0 8px 18px rgba(6,46,91,.12);
}

.hoja-header-info {
    min-width: 0;
    flex: 1;
}

.hoja-header-info h1 {
    margin: 0;
    color: #062e5b;
    font-size: 25px;
    font-weight: 850;
}

.hoja-header-info p {
    margin: 5px 0 0;
    color: #66809b;
    font-size: 14px;
}

.hoja-total {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 11px 15px;
    border-radius: 12px;
    background: <?= e($configHoja['fondo']); ?>;
    color: <?= e($configHoja['color']); ?>;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}

.hoja-table-card {
    background: #fff;
    border: 1px solid #dce7f5;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(6,46,91,.06);
    overflow: hidden;
}

.hoja-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 18px 20px;
    border-bottom: 1px solid #e7eef6;
}

.hoja-search {
    display: flex;
    flex: 1;
    max-width: 600px;
    gap: 9px;
}

.hoja-search input {
    min-width: 0;
    flex: 1;
    height: 43px;
    padding: 0 14px;
    border: 1px solid #d4e1ef;
    border-radius: 10px;
    color: #173b60;
    outline: none;
}

.hoja-search input:focus {
    border-color: #3d91e8;
    box-shadow: 0 0 0 3px rgba(61,145,232,.12);
}

.hoja-search button {
    height: 43px;
    border: 0;
    border-radius: 10px;
    padding: 0 19px;
    background: #062e5b;
    color: #fff;
    font-weight: 750;
    cursor: pointer;
}

.hoja-search button:hover {
    background: #0b427d;
}

.hoja-clear {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 43px;
    padding: 0 15px;
    border-radius: 10px;
    background: #f2f6fa;
    color: #315676;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.hoja-clear:hover {
    background: #e6eef6;
}

.hoja-table-scroll {
    width: 100%;
    overflow-x: auto;
}

.hoja-table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
}

.hoja-table th {
    padding: 14px 14px;
    text-align: left;
    background: #f5f8fc;
    border-bottom: 1px solid #dce7f5;
    color: #355a7d;
    font-size: 12px;
    font-weight: 850;
    white-space: nowrap;
}

.hoja-table td {
    padding: 13px 14px;
    border-bottom: 1px solid #edf2f7;
    color: #35516c;
    font-size: 13px;
    vertical-align: middle;
}

.hoja-table tbody tr:hover {
    background: #f8fbff;
}

.codigo {
    color: #062e5b !important;
    font-weight: 800;
    white-space: nowrap;
}

.equipo {
    min-width: 210px;
    max-width: 300px;
}

.equipo strong {
    display: block;
    color: #123c64;
    font-weight: 800;
}

.equipo small {
    display: block;
    margin-top: 4px;
    color: #7189a0;
    line-height: 1.3;
}

.estado {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #dcfce7;
    color: #16a34a;
    font-size: 15px;
    font-weight: 900;
    white-space: nowrap;
    box-shadow: inset 0 0 0 1px rgba(22, 163, 74, .12);
}

.estado.vacio {
    width: auto;
    height: auto;
    padding: 5px 9px;
    border-radius: 20px;
    background: #f3f5f7;
    color: #738397;
    font-size: 11px;
    font-weight: 800;
    box-shadow: none;
}

.accion-detalle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #edf5ff;
    color: #126dcb;
    text-decoration: none;
}

.accion-detalle:hover {
    background: #126dcb;
    color: #fff;
}

.hoja-empty {
    padding: 55px 20px;
    text-align: center;
    color: #7189a0;
}

.hoja-empty-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    background: #f1f6fb;
    color: #7b93aa;
    font-size: 25px;
}

.hoja-empty h3 {
    margin: 0 0 6px;
    color: #244d70;
    font-size: 18px;
}

.hoja-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 16px 20px;
    border-top: 1px solid #edf2f7;
}

.hoja-pagination-info {
    color: #70879d;
    font-size: 12px;
}

.hoja-pagination-links {
    display: flex;
    align-items: center;
    gap: 5px;
}

.hoja-pagination-links a,
.hoja-pagination-links span {
    min-width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 9px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 750;
}

.hoja-pagination-links a {
    color: #3e607d;
    background: #f3f7fb;
}

.hoja-pagination-links a:hover {
    background: #e6eff8;
}

.hoja-pagination-links .actual {
    background: <?= e($configHoja['color']); ?>;
    color: #fff;
}

.hoja-pagination-links .disabled {
    color: #a5b4c1;
    background: #f5f7f9;
}

@media (max-width: 800px) {
    .hoja-page .content {
        padding: 15px;
    }

    .hoja-header-card,
    .hoja-toolbar,
    .hoja-pagination {
        flex-direction: column;
        align-items: stretch;
    }

    .hoja-total,
    .hoja-search {
        max-width: none;
        width: 100%;
    }

    .hoja-pagination-links {
        justify-content: center;
    }
}
</style>

<?php include("../includes/sidebar.php"); ?>

<div class="main hoja-page">

    <?php include("../includes/header.php"); ?>

    <div class="content">

        <div class="hoja-wrapper">

            <div class="hoja-top">
                <a href="inventario_tecnico.php" class="hoja-back">
                    <i class="fas fa-arrow-left"></i>
                    Volver a hojas del inventario
                </a>
            </div>

            <section class="hoja-header-card">
                <div class="hoja-header-icon">
                    <i class="fas <?= e($configHoja['icono']); ?>"></i>
                </div>

                <div class="hoja-header-info">
                    <h1><?= e($hoja); ?></h1>
                    <p><?= e($configHoja['descripcion']); ?></p>
                </div>

                <div class="hoja-total">
                    <i class="fas fa-database"></i>
                    <?= number_format($total); ?> registros
                </div>
            </section>

            <section class="hoja-table-card">

                <div class="hoja-toolbar">
                    <form method="GET" class="hoja-search">
                        <input type="hidden" name="hoja" value="<?= e($hoja); ?>">
                        <input
                            type="text"
                            name="buscar"
                            value="<?= e($buscar); ?>"
                            placeholder="Buscar código, equipo, ubicación, IP..."
                            autocomplete="off"
                        >
                        <button type="submit">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </form>

                    <?php if ($buscar !== ''): ?>
                        <a href="hoja_inventario.php?<?= http_build_query(['hoja' => $hoja]); ?>" class="hoja-clear">
                            <i class="fas fa-xmark"></i>
                            Limpiar búsqueda
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($registros)): ?>

                    <div class="hoja-table-scroll">
                        <table class="hoja-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Equipo</th>
                                    <th>Sistema</th>
                                    <th>Tipo</th>
                                    <th>Edificio</th>
                                    <th>Ubicación</th>
                                    <th>IP</th>
                                    <th>MAC</th>
                                    <th>Referencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <?php $estado = trim((string) ($registro['estado'] ?? '')); ?>
                                    <tr>
                                        <td class="codigo">
                                            <?= valorTabla($registro['codigo_activo']); ?>
                                        </td>

                                        <td class="equipo">
                                            <strong><?= valorTabla($registro['dispositivo'], 'Sin nombre'); ?></strong>
                                            <?php if (trim((string) ($registro['descripcion'] ?? '')) !== ''): ?>
                                                <small><?= valorTabla($registro['descripcion']); ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= valorTabla($registro['sistema']); ?></td>
                                        <td><?= valorTabla($registro['tipo']); ?></td>
                                        <td><?= valorTabla($registro['edificio']); ?></td>
                                        <td><?= valorTabla($registro['ubicacion']); ?></td>
                                        <td><?= valorTabla($registro['ip']); ?></td>
                                        <td><?= valorTabla($registro['mac']); ?></td>
                                        <td><?= valorTabla($registro['referencia']); ?></td>

                                        <td>
                                            <?php if ($estado === ''): ?>
                                                <span class="estado vacio">Sin estado</span>
                                            <?php elseif (mb_strtolower($estado, 'UTF-8') === 'registrado'): ?>
                                                <span class="estado" title="Registrado" aria-label="Registrado">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="estado vacio" title="<?= e($estado); ?>">
                                                    <?= e($estado); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <a
                                                href="detalle_equipo.php?id=<?= (int) $registro['id']; ?>"
                                                class="accion-detalle"
                                                title="Ver detalle"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="hoja-pagination">
                        <div class="hoja-pagination-info">
                            Mostrando
                            <?= number_format($total === 0 ? 0 : $offset + 1); ?>
                            -
                            <?= number_format(min($offset + $porPagina, $total)); ?>
                            de <?= number_format($total); ?> registros
                        </div>

                        <?php if ($totalPaginas > 1): ?>
                            <div class="hoja-pagination-links">

                                <?php if ($pagina > 1): ?>
                                    <a href="<?= e(urlPaginaHoja($hoja, $buscar, $pagina - 1)); ?>" title="Página anterior">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                <?php endif; ?>

                                <?php
                                $inicio = max(1, $pagina - 2);
                                $fin = min($totalPaginas, $pagina + 2);
                                ?>

                                <?php if ($inicio > 1): ?>
                                    <a href="<?= e(urlPaginaHoja($hoja, $buscar, 1)); ?>">1</a>
                                    <?php if ($inicio > 2): ?>
                                        <span class="disabled">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $inicio; $p <= $fin; $p++): ?>
                                    <?php if ($p === $pagina): ?>
                                        <span class="actual"><?= $p; ?></span>
                                    <?php else: ?>
                                        <a href="<?= e(urlPaginaHoja($hoja, $buscar, $p)); ?>"><?= $p; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($fin < $totalPaginas): ?>
                                    <?php if ($fin < $totalPaginas - 1): ?>
                                        <span class="disabled">...</span>
                                    <?php endif; ?>
                                    <a href="<?= e(urlPaginaHoja($hoja, $buscar, $totalPaginas)); ?>"><?= $totalPaginas; ?></a>
                                <?php endif; ?>

                                <?php if ($pagina < $totalPaginas): ?>
                                    <a href="<?= e(urlPaginaHoja($hoja, $buscar, $pagina + 1)); ?>" title="Página siguiente">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <div class="hoja-empty">
                        <div class="hoja-empty-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h3>No hay registros para mostrar</h3>
                        <?php if ($buscar !== ''): ?>
                            <div>No encontramos resultados para <strong><?= e($buscar); ?></strong>.</div>
                        <?php else: ?>
                            <div>Esta hoja no tiene registros registrados actualmente.</div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </section>

        </div>

    </div>
</div>

<?php include("../includes/footer.php"); ?>
