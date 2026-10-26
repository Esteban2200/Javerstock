
<?php

include("../includes/auth.php");
include("../includes/conexion.php");


/* =========================================================
   FUNCIONES
========================================================= */

function e($v)
{
    return htmlspecialchars(
        (string)($v ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   FILTROS
========================================================= */

$tipo = strtolower(
    trim($_GET['tipo'] ?? 'equipos')
);

$permitidos = [
    'equipos',
    'mantenimientos',
    'movimientos',
    'inventario',
    'puntos_red',
    'ubicaciones'
];

if (!in_array($tipo, $permitidos, true)) {
    $tipo = 'equipos';
}

$area = strtolower(
    trim($_GET['area'] ?? '')
);

$edificio = trim(
    $_GET['edificio'] ?? ''
);

$estado = strtolower(
    trim($_GET['estado'] ?? '')
);

$desde = trim(
    $_GET['desde'] ?? ''
);

$hasta = trim(
    $_GET['hasta'] ?? ''
);


/* =========================================================
   VARIABLES
========================================================= */

$rows = [];
$kpis = [];
$labels = [];
$data = [];

$title = '';
$subtitle = '';
$columns = [];

$edificios = [];


/* =========================================================
   OBTENER EDIFICIOS
   Se buscan en las diferentes tablas
========================================================= */

$consultasEdificios = [

    "SELECT DISTINCT edificio
     FROM equipos
     WHERE edificio IS NOT NULL
     AND TRIM(edificio) <> ''",

    "SELECT DISTINCT edificio
     FROM puntos_red_audiovisuales
     WHERE edificio IS NOT NULL
     AND TRIM(edificio) <> ''",

    "SELECT DISTINCT edificio
     FROM puntos_seguridad
     WHERE edificio IS NOT NULL
     AND TRIM(edificio) <> ''",

    "SELECT DISTINCT edificio
     FROM ubicaciones
     WHERE edificio IS NOT NULL
     AND TRIM(edificio) <> ''"
];

foreach ($consultasEdificios as $sqlEdificio) {

    $r = $conn->query($sqlEdificio);

    if ($r) {

        while ($x = $r->fetch_assoc()) {

            $ed = trim(
                $x['edificio'] ?? ''
            );

            if (
                $ed !== '' &&
                !in_array(
                    $ed,
                    $edificios,
                    true
                )
            ) {
                $edificios[] = $ed;
            }
        }
    }
}

natcasesort($edificios);

$edificios = array_values(
    $edificios
);


/* =========================================================
   FUNCIÓN DE FILTROS
========================================================= */

function ok(
    $r,
    $area,
    $edificio,
    $estado,
    $desde,
    $hasta,
    $dateKey = ''
) {

    /* Área */

    if (
        $area !== '' &&
        strtolower(
            trim($r['area'] ?? '')
        ) !== $area
    ) {
        return false;
    }


    /* Edificio */

    if (
        $edificio !== '' &&
        mb_strtolower(
            trim($r['edificio'] ?? ''),
            'UTF-8'
        ) !== mb_strtolower(
            $edificio,
            'UTF-8'
        )
    ) {
        return false;
    }


    /* Estado */

    if (
        $estado !== '' &&
        strtolower(
            trim($r['estado'] ?? '')
        ) !== $estado
    ) {
        return false;
    }


    /* Fechas */

    if ($dateKey !== '') {

        $f = substr(
            (string)($r[$dateKey] ?? ''),
            0,
            10
        );

        if (
            $desde !== '' &&
            $f !== '' &&
            $f < $desde
        ) {
            return false;
        }

        if (
            $hasta !== '' &&
            $f !== '' &&
            $f > $hasta
        ) {
            return false;
        }
    }

    return true;
}


/* =========================================================
   EQUIPOS
========================================================= */

if ($tipo === 'equipos') {

    $title = 'Reporte de Equipos';

    $subtitle =
        'Equipos registrados por área, edificio y estado.';


    $q = $conn->query("
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
            fecha_registro
        FROM equipos
        ORDER BY nombre
    ");


    if (!$q) {

        die(
            'Error consultando equipos: ' .
            e($conn->error)
        );
    }


    while ($x = $q->fetch_assoc()) {

        if (
            ok(
                $x,
                $area,
                $edificio,
                $estado,
                $desde,
                $hasta,
                'fecha_registro'
            )
        ) {

            $rows[] = $x;
        }
    }


    $a = 0;
    $au = 0;
    $op = 0;
    $man = 0;

    $by = [];


    foreach ($rows as $x) {

        $ar = strtolower(
            trim($x['area'] ?? '')
        );

        $st = strtolower(
            trim($x['estado'] ?? '')
        );

        $b = trim(
            $x['edificio'] ?? ''
        );

        if ($b === '') {
            $b = 'Sin edificio';
        }


        if ($ar === 'audiovisuales') {
            $a++;
        }

        if ($ar === 'automatizacion') {
            $au++;
        }

        if ($st === 'operativo') {
            $op++;
        }

        if ($st === 'mantenimiento') {
            $man++;
        }


        $by[$b] =
            ($by[$b] ?? 0) + 1;
    }


    arsort($by);


    $kpis = [

        [
            'Total equipos',
            count($rows),
            'Según filtros'
        ],

        [
            'Audiovisuales',
            $a,
            'Equipos del área'
        ],

        [
            'Automatización',
            $au,
            'Equipos del área'
        ],

        [
            'Operativos',
            $op,
            'Estado operativo'
        ],

        [
            'En mantenimiento',
            $man,
            'Requieren atención'
        ]

    ];


    $labels =
        array_keys(
            array_slice(
                $by,
                0,
                10,
                true
            )
        );


    $data =
        array_values(
            array_slice(
                $by,
                0,
                10
            )
        );


    $columns = [

        'codigo' =>
            'Código',

        'nombre' =>
            'Equipo',

        'area' =>
            'Área',

        'categoria' =>
            'Categoría',

        'marca' =>
            'Marca',

        'modelo' =>
            'Modelo',

        'numero_serie' =>
            'N.º Serie',

        'edificio' =>
            'Edificio',

        'piso' =>
            'Piso',

        'salon' =>
            'Salón',

        'estado' =>
            'Estado',

        'fecha_registro' =>
            'Fecha registro'

    ];
}


/* =========================================================
   MANTENIMIENTOS
========================================================= */

elseif ($tipo === 'mantenimientos') {

    $title =
        'Reporte de Mantenimientos';

    $subtitle =
        'Historial de mantenimientos de los equipos.';


    $q = $conn->query("
        SELECT
            m.id,
            e.codigo,
            e.nombre AS equipo,
            e.area,
            e.edificio,
            e.piso,
            e.salon,
            m.diagnostico,
            m.tecnico,
            m.proveedor,
            m.ubicacion,
            m.fecha_inicio,
            m.fecha_fin,
            m.observaciones
        FROM mantenimientos m
        INNER JOIN equipos e
            ON e.id = m.equipo_id
        ORDER BY
            m.fecha_inicio DESC,
            m.id DESC
    ");


    if (!$q) {

        die(
            'Error consultando mantenimientos: ' .
            e($conn->error)
        );
    }


    while ($x = $q->fetch_assoc()) {

        $x['estado'] =
            empty($x['fecha_fin'])
            ? 'activo'
            : 'finalizado';


        if (
            ok(
                $x,
                $area,
                $edificio,
                $estado,
                $desde,
                $hasta,
                'fecha_inicio'
            )
        ) {

            $rows[] = $x;
        }
    }


    $act = 0;
    $fin = 0;
    $aa = 0;
    $zz = 0;


    foreach ($rows as $x) {

        if (
            $x['estado'] === 'activo'
        ) {

            $act++;

        } else {

            $fin++;
        }


        if (
            strtolower(
                trim($x['area'] ?? '')
            ) === 'automatizacion'
        ) {

            $zz++;

        } else {

            $aa++;
        }
    }


    $kpis = [

        [
            'Total',
            count($rows),
            'Mantenimientos'
        ],

        [
            'Activos',
            $act,
            'Sin finalizar'
        ],

        [
            'Finalizados',
            $fin,
            'Con fecha de cierre'
        ],

        [
            'Audiovisuales',
            $aa,
            'Del área'
        ],

        [
            'Automatización',
            $zz,
            'Del área'
        ]

    ];


    $labels = [
        'Activos',
        'Finalizados'
    ];

    $data = [
        $act,
        $fin
    ];


    $columns = [

        'codigo' =>
            'Código',

        'equipo' =>
            'Equipo',

        'area' =>
            'Área',

        'edificio' =>
            'Edificio',

        'piso' =>
            'Piso',

        'salon' =>
            'Salón',

        'diagnostico' =>
            'Diagnóstico',

        'tecnico' =>
            'Técnico',

        'proveedor' =>
            'Proveedor',

        'fecha_inicio' =>
            'Inicio',

        'fecha_fin' =>
            'Fin',

        'estado' =>
            'Estado',

        'observaciones' =>
            'Observaciones'

    ];
}


/* =========================================================
   MOVIMIENTOS
========================================================= */

elseif ($tipo === 'movimientos') {

    $title =
        'Reporte de Movimientos';

    $subtitle =
        'Entradas y salidas del inventario de productos.';


    $q = $conn->query("
        SELECT
            m.id,
            p.codigo,
            p.nombre AS producto,
            p.area,
            p.marca,
            m.tipo,
            m.cantidad,
            m.usuario,
            m.dependencia,
            m.observacion,
            m.fecha
        FROM movimientos m
        INNER JOIN productos p
            ON p.id = m.producto_id
        ORDER BY
            m.fecha DESC,
            m.id DESC
    ");


    if (!$q) {

        die(
            'Error consultando movimientos: ' .
            e($conn->error)
        );
    }


    while ($x = $q->fetch_assoc()) {

        if (
            $area !== '' &&
            strtolower(
                trim($x['area'] ?? '')
            ) !== $area
        ) {
            continue;
        }


        $f = substr(
            (string)$x['fecha'],
            0,
            10
        );


        if (
            $desde !== '' &&
            $f !== '' &&
            $f < $desde
        ) {
            continue;
        }


        if (
            $hasta !== '' &&
            $f !== '' &&
            $f > $hasta
        ) {
            continue;
        }


        $rows[] = $x;
    }


    $en = 0;
    $sa = 0;
    $uen = 0;
    $usa = 0;


    foreach ($rows as $x) {

        if (
            strtolower(
                trim($x['tipo'] ?? '')
            ) === 'entrada'
        ) {

            $en++;

            $uen +=
                (int)$x['cantidad'];

        } else {

            $sa++;

            $usa +=
                (int)$x['cantidad'];
        }
    }


    $kpis = [

        [
            'Movimientos',
            count($rows),
            'Según filtros'
        ],

        [
            'Entradas',
            $en,
            $uen . ' unidades'
        ],

        [
            'Salidas',
            $sa,
            $usa . ' unidades'
        ],

        [
            'Unidades entrada',
            $uen,
            'Total movido'
        ],

        [
            'Unidades salida',
            $usa,
            'Total entregado'
        ]

    ];


    $labels = [
        'Entradas',
        'Salidas'
    ];

    $data = [
        $uen,
        $usa
    ];


    $columns = [

        'codigo' =>
            'Código',

        'producto' =>
            'Producto',

        'area' =>
            'Área',

        'marca' =>
            'Marca',

        'tipo' =>
            'Tipo',

        'cantidad' =>
            'Cantidad',

        'usuario' =>
            'Usuario',

        'dependencia' =>
            'Dependencia',

        'observacion' =>
            'Observación',

        'fecha' =>
            'Fecha'

    ];
}


/* =========================================================
   INVENTARIO
========================================================= */

elseif ($tipo === 'inventario') {

    $title =
        'Reporte de Inventario';

    $subtitle =
        'Existencias, stock mínimo y estado de productos.';


    $q = $conn->query("
        SELECT
            p.id,
            p.codigo,
            p.nombre,
            p.area,
            c.nombre AS categoria,
            p.marca,
            p.referencia,
            p.cantidad,
            p.stock_minimo,
            p.ubicacion
        FROM productos p
        LEFT JOIN categorias c
            ON c.id = p.categoria
        ORDER BY p.nombre
    ");


    if (!$q) {

        die(
            'Error consultando inventario: ' .
            e($conn->error)
        );
    }


    while ($x = $q->fetch_assoc()) {

        $cantidad =
            (int)$x['cantidad'];

        $minimo =
            (int)$x['stock_minimo'];


        if ($cantidad <= 0) {

            $x['estado'] =
                'agotado';

        } elseif (
            $cantidad <= $minimo
        ) {

            $x['estado'] =
                'bajo stock';

        } else {

            $x['estado'] =
                'disponible';
        }


        if (
            $area !== '' &&
            strtolower(
                trim($x['area'] ?? '')
            ) !== $area
        ) {
            continue;
        }


        if (
            $estado !== '' &&
            $x['estado'] !== $estado
        ) {
            continue;
        }


        $rows[] = $x;
    }


    $di = 0;
    $bs = 0;
    $ag = 0;
    $un = 0;


    foreach ($rows as $x) {

        $un +=
            (int)$x['cantidad'];


        if (
            $x['estado'] === 'disponible'
        ) {

            $di++;

        } elseif (
            $x['estado'] === 'bajo stock'
        ) {

            $bs++;

        } else {

            $ag++;
        }
    }


    $kpis = [

        [
            'Productos',
            count($rows),
            'Según filtros'
        ],

        [
            'Disponibles',
            $di,
            'Sobre mínimo'
        ],

        [
            'Bajo stock',
            $bs,
            'En nivel mínimo'
        ],

        [
            'Agotados',
            $ag,
            'Sin existencias'
        ],

        [
            'Unidades',
            $un,
            'Existencia total'
        ]

    ];


    $labels = [
        'Disponibles',
        'Bajo stock',
        'Agotados'
    ];

    $data = [
        $di,
        $bs,
        $ag
    ];


    $columns = [

        'codigo' =>
            'Código',

        'nombre' =>
            'Producto',

        'area' =>
            'Área',

        'categoria' =>
            'Categoría',

        'marca' =>
            'Marca',

        'referencia' =>
            'Referencia',

        'cantidad' =>
            'Cantidad',

        'stock_minimo' =>
            'Stock mínimo',

        'ubicacion' =>
            'Ubicación',

        'estado' =>
            'Estado'

    ];
}


/* =========================================================
   PUNTOS DE RED
========================================================= */

elseif ($tipo === 'puntos_red') {

    $title =
        'Reporte de Puntos de Red';

    $subtitle =
        'Puntos de red de Audiovisuales y Automatización.';


    /* AUDIOVISUALES */

    if (
        $area === '' ||
        $area === 'audiovisuales'
    ) {

        $q = $conn->query("
            SELECT
                id,
                edificio,
                ubicacion,
                aula,
                equipo,
                marca,
                mac,
                ip,
                mascara,
                serial,
                punto,
                switch,
                puerto,
                estado
            FROM puntos_red_audiovisuales
            ORDER BY
                edificio,
                aula,
                equipo
        ");


        if ($q) {

            while (
                $x = $q->fetch_assoc()
            ) {

                $x['area'] =
                    'audiovisuales';


                $x['ubicacion_reportada'] =
                    trim(
                        ($x['ubicacion'] ?? '') .
                        (
                            ($x['aula'] ?? '')
                            ? ' | ' . $x['aula']
                            : ''
                        )
                    );


                if (
                    ok(
                        $x,
                        'audiovisuales',
                        $edificio,
                        $estado,
                        '',
                        '',
                        ''
                    )
                ) {

                    $rows[] = $x;
                }
            }
        }
    }


    /* AUTOMATIZACIÓN */

    if (
        $area === '' ||
        $area === 'automatizacion'
    ) {

        $q = $conn->query("
            SELECT
                id,
                sistema,
                item,
                edificio,
                zona,
                ip,
                mac,
                ubicacion,
                rack,
                sw_port,
                subsistema,
                estado
            FROM puntos_seguridad
            ORDER BY
                edificio,
                sistema,
                item
        ");


        if ($q) {

            while (
                $x = $q->fetch_assoc()
            ) {

                $y = [

                    'id' =>
                        $x['id'],

                    'area' =>
                        'automatizacion',

                    'edificio' =>
                        $x['edificio'],

                    'ubicacion_reportada' =>
                        $x['ubicacion'] ?? '',

                    'equipo' =>
                        $x['item'] ?? '',

                    'marca' =>
                        $x['sistema'] ?? '',

                    'mac' =>
                        $x['mac'] ?? '',

                    'ip' =>
                        $x['ip'] ?? '',

                    'switch' =>
                        $x['rack'] ?? '',

                    'puerto' =>
                        $x['sw_port'] ?? '',

                    'estado' =>
                        $x['estado'] ?? '',

                    'detalle' =>
                        $x['zona'] ?? '',

                    'sub_sistema' =>
                        $x['subsistema'] ?? ''

                ];


                if (
                    ok(
                        $y,
                        'automatizacion',
                        $edificio,
                        $estado,
                        '',
                        '',
                        ''
                    )
                ) {

                    $rows[] = $y;
                }
            }
        }
    }


    $au = 0;
    $az = 0;
    $sv = 0;


    foreach ($rows as $x) {

        if (
            ($x['area'] ?? '') ===
            'audiovisuales'
        ) {

            $au++;

        } else {

            $az++;
        }


        if (
            str_contains(
                strtolower(
                    (string)($x['estado'] ?? '')
                ),
                'sin verificar'
            )
        ) {

            $sv++;
        }
    }


    $kpis = [

        [
            'Total puntos',
            count($rows),
            'Según filtros'
        ],

        [
            'Audiovisuales',
            $au,
            'Puntos Extron'
        ],

        [
            'Automatización',
            $az,
            'Puntos de seguridad'
        ],

        [
            'Sin verificar',
            $sv,
            'Requieren revisión'
        ],

        [
            'Con estado',
            max(
                0,
                count($rows) - $sv
            ),
            'Registrados'
        ]

    ];


    $labels = [
        'Audiovisuales',
        'Automatización'
    ];

    $data = [
        $au,
        $az
    ];


    $columns = [

        'id' =>
            'ID',

        'equipo' =>
            'Equipo / Ítem',

        'area' =>
            'Área',

        'marca' =>
            'Sistema / Marca',

        'edificio' =>
            'Edificio',

        'ubicacion_reportada' =>
            'Ubicación',

        'ip' =>
            'IP',

        'mac' =>
            'MAC',

        'switch' =>
            'Switch / Rack',

        'puerto' =>
            'Puerto',

        'estado' =>
            'Estado'

    ];
}


/* =========================================================
   UBICACIONES
========================================================= */

else {

    $title =
        'Reporte de Ubicaciones';

    $subtitle =
        'Espacios configurados para la gestión de equipos.';


    $q = $conn->query("
        SELECT
            id,
            edificio,
            piso,
            salon,
            area,
            estado,
            fecha_registro
        FROM ubicaciones
        ORDER BY
            edificio,
            piso,
            salon
    ");


    if (!$q) {

        die(
            'Error consultando ubicaciones: ' .
            e($conn->error)
        );
    }


    while ($x = $q->fetch_assoc()) {

        if (
            $area !== '' &&
            strtolower(
                trim($x['area'] ?? '')
            ) !== $area
        ) {
            continue;
        }


        if (
            $edificio !== '' &&
            mb_strtolower(
                trim($x['edificio'] ?? ''),
                'UTF-8'
            ) !== mb_strtolower(
                $edificio,
                'UTF-8'
            )
        ) {
            continue;
        }


        if (
            $estado !== '' &&
            strtolower(
                trim($x['estado'] ?? '')
            ) !== $estado
        ) {
            continue;
        }


        $rows[] = $x;
    }


    $ac = 0;
    $in = 0;
    $au = 0;
    $az = 0;


    foreach ($rows as $x) {

        if (
            strtolower(
                trim($x['estado'] ?? '')
            ) === 'activo'
        ) {

            $ac++;

        } else {

            $in++;
        }


        if (
            strtolower(
                trim($x['area'] ?? '')
            ) === 'audiovisuales'
        ) {

            $au++;

        } elseif (
            strtolower(
                trim($x['area'] ?? '')
            ) === 'automatizacion'
        ) {

            $az++;
        }
    }


    $kpis = [

        [
            'Total ubicaciones',
            count($rows),
            'Según filtros'
        ],

        [
            'Activas',
            $ac,
            'Disponibles'
        ],

        [
            'Inactivas',
            $in,
            'No disponibles'
        ],

        [
            'Audiovisuales',
            $au,
            'Asignadas al área'
        ],

        [
            'Automatización',
            $az,
            'Asignadas al área'
        ]

    ];


    $labels = [
        'Audiovisuales',
        'Automatización',
        'Otras'
    ];


    $data = [

        $au,

        $az,

        max(
            0,
            count($rows) -
            $au -
            $az
        )

    ];


    $columns = [

        'id' =>
            'ID',

        'edificio' =>
            'Edificio',

        'piso' =>
            'Piso',

        'salon' =>
            'Salón',

        'area' =>
            'Área',

        'estado' =>
            'Estado',

        'fecha_registro' =>
            'Fecha registro'

    ];
}


include("../includes/sidebar.php");

?>

<style>

.reportes-page{
    padding-bottom:45px;
}

.reportes-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:20px;
    margin-bottom:22px;
    flex-wrap:wrap;
}

.reportes-title h2{
    margin:0;
    color:#002855;
    font-size:32px;
    font-weight:800;
}

.reportes-title p{
    margin:7px 0 0;
    color:#64748b;
}

.reportes-acciones{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.reportes-acciones .btn{
    border-radius:10px;
    font-weight:700;
}

.reportes-tabs{
    display:flex;
    flex-wrap:wrap;
    gap:7px;
    background:#fff;
    border:1px solid #e6ecf2;
    border-radius:16px;
    padding:8px;
    margin-bottom:18px;
}

.reportes-tab{
    display:inline-flex;
    align-items:center;
    gap:8px;
    border-radius:10px;
    color:#64748b;
    padding:11px 15px;
    text-decoration:none;
    font-size:13px;
    font-weight:800;
}

.reportes-tab:hover{
    background:#f1f6fb;
    color:#002855;
}

.reportes-tab.active{
    background:#002855;
    color:#fff;
}

.reportes-filtros{
    background:#fff;
    border:1px solid #e6ecf2;
    border-radius:18px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 7px 24px rgba(15,23,42,.05);
}

.reportes-filtros h5{
    margin:0 0 16px;
    color:#0f172a;
    font-weight:800;
}

.reportes-filtros .form-label{
    color:#334155;
    font-size:13px;
    font-weight:700;
}

.reportes-filtros .form-control,
.reportes-filtros .form-select{
    min-height:43px;
    border-radius:10px;
    border-color:#d8e0ea;
}

.reportes-filtros .btn{
    min-height:43px;
    border-radius:10px;
    font-weight:700;
}

.reportes-kpis{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:15px;
    margin-bottom:20px;
}

.reporte-kpi{
    background:#fff;
    border:1px solid #e6ecf2;
    border-radius:16px;
    padding:18px;
    box-shadow:0 7px 20px rgba(15,23,42,.05);
}

.reporte-kpi .label{
    color:#64748b;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
}

.reporte-kpi .numero{
    margin-top:10px;
    color:#0f172a;
    font-size:29px;
    font-weight:900;
}

.reporte-kpi .sub{
    margin-top:6px;
    color:#94a3b8;
    font-size:12px;
}

.reportes-resumen{
    display:grid;
    grid-template-columns:1.15fr .85fr;
    gap:16px;
    margin-bottom:20px;
}

.reportes-grafica-card,
.reportes-info-card,
.reportes-tabla-card{
    background:#fff;
    border:1px solid #e6ecf2;
    border-radius:17px;
    box-shadow:0 7px 20px rgba(15,23,42,.05);
}

.reportes-grafica-card{
    padding:18px;
    min-height:330px;
}

.reportes-grafica-card h5,
.reportes-info-card h5,
.reportes-tabla-card h5{
    color:#0f172a;
    font-size:16px;
    font-weight:800;
    margin:0 0 15px;
}

.chart-wrap{
    position:relative;
    height:260px;
}

.reportes-info-card{
    padding:22px;
}

.info-item{
    display:flex;
    gap:12px;
    padding:12px 0;
    border-bottom:1px solid #edf1f5;
}

.info-item:last-child{
    border-bottom:0;
}

.info-icon{
    width:38px;
    height:38px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    background:#eef5ff;
    color:#0b2e59;
    flex:0 0 auto;
}

.info-label{
    color:#64748b;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
}

.info-value{
    margin-top:3px;
    color:#0f172a;
    font-size:13px;
    font-weight:700;
}

.reportes-tabla-card{
    padding:18px;
}

.reportes-tabla-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.reporte-contador{
    background:#eef5ff;
    color:#0b2e59;
    padding:7px 11px;
    border-radius:9px;
    font-size:12px;
    font-weight:800;
}

.table-reportes{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
}

.table-reportes thead th{
    background:#002855;
    color:#fff;
    border:0;
    padding:12px 10px;
    font-size:11px;
    white-space:nowrap;
}

.table-reportes tbody td{
    padding:11px 10px;
    border-bottom:1px solid #edf1f5;
    color:#334155;
    font-size:12px;
    vertical-align:middle;
}

.table-reportes tbody tr:hover{
    background:#f8fbff;
}

.badge-reporte{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:6px 9px;
    border-radius:18px;
    font-size:10px;
    font-weight:800;
    white-space:nowrap;
}

.badge-verde{
    background:#d1e7dd;
    color:#0f5132;
}

.badge-amarillo{
    background:#fff3cd;
    color:#856404;
}

.badge-rojo{
    background:#f8d7da;
    color:#842029;
}

.badge-azul{
    background:#dbeafe;
    color:#1d4ed8;
}

.badge-gris{
    background:#e2e8f0;
    color:#475569;
}

@media(max-width:1200px){

    .reportes-kpis{
        grid-template-columns:repeat(3,1fr);
    }

    .reportes-resumen{
        grid-template-columns:1fr;
    }
}

@media(max-width:768px){

    .reportes-kpis{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:520px){

    .reportes-kpis{
        grid-template-columns:1fr;
    }

    .reportes-tab{
        flex:1 1 100%;
        justify-content:center;
    }
}

@media print{

    .sidebar,
    .header,
    .reportes-acciones,
    .reportes-tabs,
    .reportes-filtros{
        display:none!important;
    }

    .main{
        margin-left:0!important;
        width:100%!important;
    }
}

</style>


<div class="main reportes-page">

<div class="content">

<?php include("../includes/header.php"); ?>


<!-- =====================================================
     ENCABEZADO
====================================================== -->

<div class="reportes-header">

    <div class="reportes-title">

        <h2>

            <i class="fas fa-chart-column"></i>

            Reportes

        </h2>

        <p>
            <?=e($subtitle)?>
        </p>

    </div>

<?php
$pdfParams = [
    'tipo' => $tipo
];

if ($area !== '') {
    $pdfParams['area'] = $area;
}

if ($edificio !== '') {
    $pdfParams['edificio'] = $edificio;
}

if ($estado !== '') {
    $pdfParams['estado'] = $estado;
}

if ($desde !== '') {
    $pdfParams['desde'] = $desde;
}

if ($hasta !== '') {
    $pdfParams['hasta'] = $hasta;
}

$pdfUrl = '../actions/reportes/generar_pdf.php?' . http_build_query($pdfParams);
?>

<div class="reportes-acciones">

    <a
        href="<?= e($pdfUrl) ?>"
        target="_blank"
        class="btn btn-danger"
    >
        <i class="fas fa-file-pdf"></i>
        Generar PDF
    </a>

    <button
        type="button"
        class="btn btn-outline-primary"
        onclick="window.print()"
    >
        <i class="fas fa-print"></i>
        Imprimir
    </button>

    <button
        type="button"
        class="btn btn-primary"
        onclick="exportarCSV()"
    >
        <i class="fas fa-file-csv"></i>
        Exportar CSV
    </button>

</div>
</div>


<!-- =====================================================
     PESTAÑAS
====================================================== -->

<div class="reportes-tabs">

<?php

$tabs = [

    [
        'equipos',
        'fa-tv',
        'Equipos'
    ],

    [
        'mantenimientos',
        'fa-screwdriver-wrench',
        'Mantenimientos'
    ],

    [
        'movimientos',
        'fa-right-left',
        'Movimientos'
    ],

    [
        'inventario',
        'fa-boxes-stacked',
        'Inventario'
    ],

    [
        'puntos_red',
        'fa-network-wired',
        'Puntos de Red'
    ],

    [
        'ubicaciones',
        'fa-location-dot',
        'Ubicaciones'
    ]

];

foreach ($tabs as $t):

?>

<a
    class="reportes-tab <?=$tipo === $t[0] ? 'active' : ''?>"
    href="reportes.php?tipo=<?=urlencode($t[0])?>"
>

    <i class="fas <?=$t[1]?>"></i>

    <?=$t[2]?>

</a>

<?php endforeach; ?>

</div>


<!-- =====================================================
     FILTROS
====================================================== -->

<form
    method="GET"
    class="reportes-filtros"
>

<input
    type="hidden"
    name="tipo"
    value="<?=e($tipo)?>"
>


<h5>

    <i class="fas fa-filter"></i>

    Filtros

</h5>


<div class="row g-3 align-items-end">


<?php if ($tipo !== 'movimientos'): ?>

<div class="col-md-3">

<label class="form-label">
    Área
</label>

<select
    name="area"
    class="form-select"
>

<option value="">
    Todas
</option>

<option
    value="audiovisuales"
    <?=$area === 'audiovisuales' ? 'selected' : ''?>
>
    Audiovisuales
</option>

<option
    value="automatizacion"
    <?=$area === 'automatizacion' ? 'selected' : ''?>
>
    Automatización
</option>

</select>

</div>

<?php endif; ?>


<?php

if (
    in_array(
        $tipo,
        [
            'equipos',
            'mantenimientos',
            'puntos_red',
            'ubicaciones'
        ],
        true
    )
):

?>

<div class="col-md-3">

<label class="form-label">
    Edificio
</label>

<select
    name="edificio"
    class="form-select"
>

<option value="">
    Todos
</option>


<?php foreach ($edificios as $ed): ?>

<option
    value="<?=e($ed)?>"
    <?=$edificio !== '' &&
      mb_strtolower(
          $edificio,
          'UTF-8'
      ) ===
      mb_strtolower(
          $ed,
          'UTF-8'
      )
      ? 'selected'
      : ''?>
>

<?=e($ed)?>

</option>

<?php endforeach; ?>

</select>

</div>

<?php endif; ?>


<?php

if (
    in_array(
        $tipo,
        [
            'equipos',
            'mantenimientos',
            'inventario',
            'puntos_red',
            'ubicaciones'
        ],
        true
    )
):


if ($tipo === 'equipos') {

    $estados = [
        'operativo',
        'mantenimiento',
        'danado',
        'reparado',
        'entregado'
    ];

}
elseif ($tipo === 'mantenimientos') {

    $estados = [
        'activo',
        'finalizado'
    ];

}
elseif ($tipo === 'inventario') {

    $estados = [
        'disponible',
        'bajo stock',
        'agotado'
    ];

}
elseif ($tipo === 'ubicaciones') {

    $estados = [
        'activo',
        'inactivo'
    ];

}
else {

    $estados = [
        'sin verificar'
    ];
}

?>

<div class="col-md-2">

<label class="form-label">
    Estado
</label>

<select
    name="estado"
    class="form-select"
>

<option value="">
    Todos
</option>


<?php foreach ($estados as $st): ?>

<option
    value="<?=e($st)?>"
    <?=$estado === strtolower($st) ? 'selected' : ''?>
>

<?=e(ucfirst($st))?>

</option>

<?php endforeach; ?>

</select>

</div>

<?php endif; ?>


<?php

if (
    in_array(
        $tipo,
        [
            'equipos',
            'mantenimientos',
            'movimientos'
        ],
        true
    )
):

?>

<div class="col-md-2">

<label class="form-label">
    Desde
</label>

<input
    type="date"
    name="desde"
    value="<?=e($desde)?>"
    class="form-control"
>

</div>


<div class="col-md-2">

<label class="form-label">
    Hasta
</label>

<input
    type="date"
    name="hasta"
    value="<?=e($hasta)?>"
    class="form-control"
>

</div>

<?php endif; ?>


<div class="col-md-auto ms-auto d-flex gap-2">

<a
    href="reportes.php?tipo=<?=urlencode($tipo)?>"
    class="btn btn-light border"
>

    Limpiar

</a>


<button
    class="btn btn-primary"
    type="submit"
>

    <i class="fas fa-filter"></i>

    Filtrar

</button>

</div>


</div>

</form>


<!-- =====================================================
     KPIS
====================================================== -->

<div class="reportes-kpis">

<?php foreach ($kpis as $k): ?>

<div class="reporte-kpi">

    <div class="label">
        <?=e($k[0])?>
    </div>

    <div class="numero">
        <?=intval($k[1])?>
    </div>

    <div class="sub">
        <?=e($k[2])?>
    </div>

</div>

<?php endforeach; ?>

</div>


<!-- =====================================================
     RESUMEN
====================================================== -->

<div class="reportes-resumen">


<div class="reportes-grafica-card">

<h5>

    <i class="fas fa-chart-pie"></i>

    Resumen

</h5>


<div class="chart-wrap">

<canvas id="graficaReporte"></canvas>

</div>

</div>


<div class="reportes-info-card">

<h5>

    <i class="fas fa-circle-info"></i>

    Información del reporte

</h5>


<div class="info-item">

<div class="info-icon">

    <i class="fas fa-file-lines"></i>

</div>

<div>

<div class="info-label">
    Reporte
</div>

<div class="info-value">
    <?=e($title)?>
</div>

</div>

</div>


<div class="info-item">

<div class="info-icon">

    <i class="fas fa-filter"></i>

</div>

<div>

<div class="info-label">
    Filtros
</div>

<div class="info-value">

<?=(
    $area ||
    $edificio ||
    $estado ||
    $desde ||
    $hasta
)
    ? 'Filtros aplicados'
    : 'Sin filtros adicionales'
?>

</div>

</div>

</div>


<div class="info-item">

<div class="info-icon">

    <i class="fas fa-database"></i>

</div>

<div>

<div class="info-label">
    Fuente
</div>

<div class="info-value">
    Base de datos JaverStock
</div>

</div>

</div>


<div class="info-item">

<div class="info-icon">

    <i class="fas fa-clock"></i>

</div>

<div>

<div class="info-label">
    Generado
</div>

<div class="info-value">
    <?=date('d/m/Y H:i')?>
</div>

</div>

</div>

</div>

</div>


<!-- =====================================================
     TABLA
====================================================== -->

<div class="reportes-tabla-card">


<div class="reportes-tabla-header">

<h5>

    <i class="fas fa-list"></i>

    <?=e($title)?>

</h5>


<span class="reporte-contador">

    <?=count($rows)?>
    registros

</span>

</div>


<div class="table-responsive">

<table
    id="tablaReporte"
    class="table table-hover table-reportes"
>

<thead>

<tr>

<?php foreach ($columns as $c => $txt): ?>

<th>
    <?=e($txt)?>
</th>

<?php endforeach; ?>

</tr>

</thead>


<tbody>


<?php if ($rows): ?>


<?php foreach ($rows as $row): ?>

<tr>


<?php foreach ($columns as $c => $txt): ?>

<td>

<?php

$v =
    $row[$c] ?? '';


/* ÁREA */

if ($c === 'area') {

    $v =
        strtolower(
            trim((string)$v)
        ) === 'automatizacion'

        ? 'Automatización'

        : 'Audiovisuales';


    echo e($v);

}


/* ESTADO */

elseif ($c === 'estado') {

    $s =
        strtolower(
            (string)$v
        );


    $cl =
        in_array(
            $s,
            [
                'operativo',
                'disponible',
                'activo'
            ],
            true
        )

        ? 'badge-verde'

        : (

            in_array(
                $s,
                [
                    'mantenimiento',
                    'bajo stock',
                    'finalizado'
                ],
                true
            )

            ? 'badge-amarillo'

            : (

                in_array(
                    $s,
                    [
                        'danado',
                        'dañado',
                        'agotado',
                        'sin verificar'
                    ],
                    true
                )

                ? 'badge-rojo'

                : 'badge-azul'
            )
        );


    echo
        '<span class="badge-reporte ' .
        $cl .
        '">' .
        e(
            ucfirst(
                (string)$v
            )
        ) .
        '</span>';

}


/* FECHAS */

elseif (
    str_contains(
        $c,
        'fecha'
    ) ||
    $c === 'fecha'
) {

    echo e(
        $v
        ? date(
            'd/m/Y H:i',
            strtotime($v)
        )
        : '-'
    );

}


/* NORMAL */

else {

    echo nl2br(
        e($v)
    );
}

?>

</td>

<?php endforeach; ?>


</tr>

<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="<?=count($columns)?>"
    class="text-center py-5"
>

    No hay registros que coincidan
    con los filtros.

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


</div>

</div>


<!-- =====================================================
     CHART.JS
====================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function()
    {

        const canvas =
            document.getElementById(
                'graficaReporte'
            );


        if (!canvas) {
            return;
        }


        new Chart(
            canvas,
            {

                type: 'doughnut',

                data: {

                    labels:
                        <?=json_encode(
                            $labels,
                            JSON_UNESCAPED_UNICODE
                        )?>,

                    datasets: [

                        {

                            data:
                                <?=json_encode(
                                    $data
                                )?>,

                            borderWidth: 2

                        }

                    ]

                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            position:
                                'bottom'

                        }

                    }

                }

            }
        );

    }
);


/* =====================================================
   EXPORTAR CSV
====================================================== */

function exportarCSV()
{

    const tabla =
        document.getElementById(
            'tablaReporte'
        );


    if (!tabla) {
        return;
    }


    let filas = [];


    tabla
        .querySelectorAll('tr')
        .forEach(
            function(fila)
            {

                let columnas = [];


                fila
                    .querySelectorAll(
                        'th,td'
                    )
                    .forEach(
                        function(celda)
                        {

                            let texto =
                                celda.innerText
                                .replace(
                                    /\r?\n|\r/g,
                                    ' '
                                )
                                .replace(
                                    /"/g,
                                    '""'
                                )
                                .trim();


                            columnas.push(
                                '"' +
                                texto +
                                '"'
                            );

                        }
                    );


                filas.push(
                    columnas.join(',')
                );

            }
        );


    const blob =
        new Blob(
            [
                '\ufeff' +
                filas.join('\n')
            ],
            {
                type:
                    'text/csv;charset=utf-8;'
            }
        );


    const url =
        URL.createObjectURL(
            blob
        );


    const enlace =
        document.createElement(
            'a'
        );


    enlace.href = url;


    enlace.download =
        'reporte_<?=e($tipo)?>.csv';


    document.body.appendChild(
        enlace
    );


    enlace.click();


    enlace.remove();


    URL.revokeObjectURL(
        url
    );

}

</script>


<!-- =====================================================
     MENSAJES
====================================================== -->

<?php if (($_GET['msg'] ?? '') === 'correo_enviado'): ?>

<script>
Swal.fire({
    icon: 'success',
    title: 'Reporte enviado',
    text: 'El PDF fue enviado correctamente por correo.',
    confirmButtonColor: '#002855'
});
</script>

<?php endif; ?>


<?php if (!empty($_GET['error'])): ?>

<script>
Swal.fire({
    icon: 'error',
    title: 'Error al enviar',
    text: <?= json_encode(
        $_GET['error'],
        JSON_UNESCAPED_UNICODE
    ); ?>,
    confirmButtonColor: '#dc3545'
});
</script>

<?php endif; ?>


<?php if (
    !empty($_GET['error'])
): ?>

<script>

Swal.fire({

    icon:
        'error',

    title:
        'Error al enviar',

    text:
        <?=json_encode(
            $_GET['error'],
            JSON_UNESCAPED_UNICODE
        )?>,

    confirmButtonColor:
        '#dc3545'

});

</script>

<?php endif; ?>


<?php include("../includes/footer.php"); ?>

