```php
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('gestionar_mantenimiento');


/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

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

$vista = strtolower(
    trim($_GET['vista'] ?? 'mantenimiento')
);

if (!in_array($vista, ['mantenimiento', 'estadisticas'], true)) {
    $vista = 'mantenimiento';
}

$nombreArea = (
    $areaSeleccionada === 'audiovisuales'
)
    ? 'Audiovisuales'
    : 'Automatización';


/*
|--------------------------------------------------------------------------
| VERIFICAR COLUMNAS DE EQUIPOS
|--------------------------------------------------------------------------
|
| Se verifica que existan:
|
| edificio
| piso
| salon
| area
|
| Esto evita errores SQL si alguna columna todavía no existe.
|--------------------------------------------------------------------------
*/

$columnasEquipos = [];

$resultadoColumnas = mysqli_query(
    $conn,
    "SHOW COLUMNS FROM equipos"
);

if ($resultadoColumnas) {

    while ($columna = mysqli_fetch_assoc($resultadoColumnas)) {

        $columnasEquipos[] =
            strtolower(
                $columna['Field']
            );
    }
}


/*
|--------------------------------------------------------------------------
| CAMPOS DE UBICACIÓN
|--------------------------------------------------------------------------
*/

$campoEdificio = in_array(
    'edificio',
    $columnasEquipos,
    true
)
    ? "e.`edificio`"
    : "''";

$campoPiso = in_array(
    'piso',
    $columnasEquipos,
    true
)
    ? "e.`piso`"
    : "''";

$campoSalon = in_array(
    'salon',
    $columnasEquipos,
    true
)
    ? "e.`salon`"
    : "''";


/*
|--------------------------------------------------------------------------
| DETECTAR ÁREA
|--------------------------------------------------------------------------
*/

$tieneAreaEquipos = in_array(
    'area',
    $columnasEquipos,
    true
);


/*
|--------------------------------------------------------------------------
| EXPRESIÓN DE ÁREA
|--------------------------------------------------------------------------
*/

if ($tieneAreaEquipos) {

    $expresionAreaEquipo = "
        CASE
            WHEN LOWER(
                TRIM(
                    COALESCE(e.area, '')
                )
            ) IN (
                'audiovisuales',
                'automatizacion'
            )
            THEN LOWER(
                TRIM(e.area)
            )

            WHEN UPPER(
                COALESCE(e.codigo, '')
            ) LIKE 'AUTO-%'
            THEN 'automatizacion'

            WHEN UPPER(
                COALESCE(e.codigo, '')
            ) LIKE 'AUDIO-%'
            THEN 'audiovisuales'

            ELSE 'audiovisuales'
        END
    ";

} else {

    $expresionAreaEquipo = "
        CASE

            WHEN UPPER(
                COALESCE(e.codigo, '')
            ) LIKE 'AUTO-%'
            THEN 'automatizacion'

            WHEN UPPER(
                COALESCE(e.codigo, '')
            ) LIKE 'AUDIO-%'
            THEN 'audiovisuales'

            ELSE 'audiovisuales'

        END
    ";
}


/*
|--------------------------------------------------------------------------
| CONDICIÓN DE ÁREA
|--------------------------------------------------------------------------
*/

$condicionArea = "
    (
        $expresionAreaEquipo
    ) = ?
";


/*
|--------------------------------------------------------------------------
| EQUIPOS QUE REQUIEREN ATENCIÓN
|--------------------------------------------------------------------------
*/

$sqlEquipos = "
    SELECT

        e.id,
        e.codigo,
        e.nombre,
        e.marca,

        $campoEdificio AS equipo_edificio,
        $campoPiso AS equipo_piso,
        $campoSalon AS equipo_salon,

        e.estado,

        $expresionAreaEquipo AS area_calculada

    FROM equipos e

    WHERE

        e.estado IN (
            'mantenimiento',
            'dañado'
        )

        AND $condicionArea

    ORDER BY

        CASE
            WHEN e.estado = 'dañado'
            THEN 0
            ELSE 1
        END,

        e.nombre ASC
";


$stmtEquipos = $conn->prepare(
    $sqlEquipos
);

if (!$stmtEquipos) {

    die(
        "Error preparando consulta de equipos: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$stmtEquipos->bind_param(
    "s",
    $areaSeleccionada
);

if (!$stmtEquipos->execute()) {

    die(
        "Error ejecutando consulta de equipos: " .
        htmlspecialchars(
            $stmtEquipos->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$resultadoEquipos =
    $stmtEquipos->get_result();

$equiposAtencion = [];

while (
    $equipo =
    $resultadoEquipos->fetch_assoc()
) {

    $equiposAtencion[] =
        $equipo;
}

$stmtEquipos->close();


/*
|--------------------------------------------------------------------------
| TODOS LOS EQUIPOS
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| El formulario puede seleccionar cualquier equipo.
| No se restringe por estado ni por área.
|--------------------------------------------------------------------------
*/

$sqlTodosEquipos = "
    SELECT

        e.id,
        e.codigo,
        e.nombre,
        e.marca,
        e.estado,

        $campoEdificio AS equipo_edificio,
        $campoPiso AS equipo_piso,
        $campoSalon AS equipo_salon,

        $expresionAreaEquipo AS area_calculada

    FROM equipos e

    ORDER BY

        CASE

            WHEN (
                $expresionAreaEquipo
            ) = 'audiovisuales'

            THEN 0

            ELSE 1

        END,

        e.nombre ASC
";


$resultadoTodosEquipos =
    mysqli_query(
        $conn,
        $sqlTodosEquipos
    );

if (!$resultadoTodosEquipos) {

    die(
        "Error al consultar todos los equipos: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$todosEquipos = [];

while (
    $equipo =
    mysqli_fetch_assoc(
        $resultadoTodosEquipos
    )
) {

    $todosEquipos[] =
        $equipo;
}


/*
|--------------------------------------------------------------------------
| HISTORIAL DE MANTENIMIENTOS
|--------------------------------------------------------------------------
*/

$sqlMantenimientos = "
    SELECT

        m.id,
        m.equipo_id,
        m.diagnostico,
        m.trabajo_realizado,
        m.tecnico,
        m.proveedor,
        m.fecha_inicio,
        m.fecha_fin,
        m.costo,
        m.observaciones,
        m.fecha_registro,

        e.codigo,
        e.nombre AS equipo_nombre,
        e.marca,

        $campoEdificio AS equipo_edificio,
        $campoPiso AS equipo_piso,
        $campoSalon AS equipo_salon,

        e.estado AS estado_equipo,

        $expresionAreaEquipo AS area_calculada

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        $condicionArea

    ORDER BY

        CASE

            WHEN m.fecha_fin IS NULL
            THEN 0

            ELSE 1

        END ASC,

        m.fecha_inicio DESC,
        m.id DESC
";


$stmtMantenimientos =
    $conn->prepare(
        $sqlMantenimientos
    );

if (!$stmtMantenimientos) {

    die(
        "Error preparando historial: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$stmtMantenimientos->bind_param(
    "s",
    $areaSeleccionada
);

if (!$stmtMantenimientos->execute()) {

    die(
        "Error ejecutando historial: " .
        htmlspecialchars(
            $stmtMantenimientos->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$resultadoMantenimientos =
    $stmtMantenimientos->get_result();

$mantenimientos = [];

while (
    $mantenimiento =
    $resultadoMantenimientos->fetch_assoc()
) {

    $mantenimientos[] =
        $mantenimiento;
}

$stmtMantenimientos->close();


/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

$estadisticas = [

    'equipos_danados' => 0,

    'equipos_mantenimiento' => 0,

    'mantenimientos_activos' => 0,

    'mantenimientos_finalizados' => 0

];


/*
|--------------------------------------------------------------------------
| EQUIPOS DAÑADOS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*) AS total

    FROM equipos e

    WHERE

        e.estado = 'dañado'

        AND $condicionArea
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmt->execute()) {

        $resultado =
            $stmt->get_result();

        if ($fila =
            $resultado->fetch_assoc()
        ) {

            $estadisticas[
                'equipos_danados'
            ] =
                (int)$fila['total'];
        }
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| EQUIPOS EN MANTENIMIENTO
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*) AS total

    FROM equipos e

    WHERE

        e.estado = 'mantenimiento'

        AND $condicionArea
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmt->execute()) {

        $resultado =
            $stmt->get_result();

        if ($fila =
            $resultado->fetch_assoc()
        ) {

            $estadisticas[
                'equipos_mantenimiento'
            ] =
                (int)$fila['total'];
        }
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| MANTENIMIENTOS ACTIVOS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*) AS total

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        m.fecha_fin IS NULL

        AND $condicionArea
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmt->execute()) {

        $resultado =
            $stmt->get_result();

        if ($fila =
            $resultado->fetch_assoc()
        ) {

            $estadisticas[
                'mantenimientos_activos'
            ] =
                (int)$fila['total'];
        }
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| MANTENIMIENTOS FINALIZADOS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT COUNT(*) AS total

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        m.fecha_fin IS NOT NULL

        AND $condicionArea
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmt->execute()) {

        $resultado =
            $stmt->get_result();

        if ($fila =
            $resultado->fetch_assoc()
        ) {

            $estadisticas[
                'mantenimientos_finalizados'
            ] =
                (int)$fila['total'];
        }
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| GRÁFICA DIARIA
|--------------------------------------------------------------------------
*/

$labelsDias = [];

$datosMantenimiento = [];

$datosDanios = [];


for (
    $i = 13;
    $i >= 0;
    $i--
) {

    $fecha =
        date(
            'Y-m-d',
            strtotime("-$i days")
        );

    $labelsDias[] =
        date(
            'd/m',
            strtotime($fecha)
        );

    $datosMantenimiento[$fecha] =
        0;

    $datosDanios[$fecha] =
        0;
}


/*
|--------------------------------------------------------------------------
| MANTENIMIENTOS POR DÍA
|--------------------------------------------------------------------------
*/

$sqlGrafica = "

    SELECT

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        ) AS fecha,

        COUNT(*) AS total

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        ) >= DATE_SUB(
            CURDATE(),
            INTERVAL 13 DAY
        )

        AND $condicionArea

    GROUP BY

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        )

    ORDER BY fecha ASC
";


$stmtGrafica =
    $conn->prepare(
        $sqlGrafica
    );

if ($stmtGrafica) {

    $stmtGrafica->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmtGrafica->execute()) {

        $resultado =
            $stmtGrafica->get_result();

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            if (
                isset(
                    $datosMantenimiento[
                        $fila['fecha']
                    ]
                )
            ) {

                $datosMantenimiento[
                    $fila['fecha']
                ] =
                    (int)$fila['total'];
            }
        }
    }

    $stmtGrafica->close();
}


/*
|--------------------------------------------------------------------------
| DAÑOS POR DÍA
|--------------------------------------------------------------------------
*/

$sqlDanios = "

    SELECT

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        ) AS fecha,

        COUNT(*) AS total

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        ) >= DATE_SUB(
            CURDATE(),
            INTERVAL 13 DAY
        )

        AND $condicionArea

        AND (

            LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%dañ%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%danad%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%daño%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%falla%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%avería%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%averia%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%defect%'
        )

    GROUP BY

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        )

    ORDER BY fecha ASC
";


$stmtDanios =
    $conn->prepare(
        $sqlDanios
    );

if ($stmtDanios) {

    $stmtDanios->bind_param(
        "s",
        $areaSeleccionada
    );

    if ($stmtDanios->execute()) {

        $resultado =
            $stmtDanios->get_result();

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            if (
                isset(
                    $datosDanios[
                        $fila['fecha']
                    ]
                )
            ) {

                $datosDanios[
                    $fila['fecha']
                ] =
                    (int)$fila['total'];
            }
        }
    }

    $stmtDanios->close();
}


$valoresMantenimiento =
    array_values(
        $datosMantenimiento
    );

$valoresDanios =
    array_values(
        $datosDanios
    );


/*
|--------------------------------------------------------------------------
| COMPARATIVA POR ÁREA
|--------------------------------------------------------------------------
*/

$comparativaAreas = [

    'audiovisuales' => [

        'mantenimientos' => 0,

        'danios' => 0

    ],

    'automatizacion' => [

        'mantenimientos' => 0,

        'danios' => 0

    ]

];


/*
|--------------------------------------------------------------------------
| MANTENIMIENTOS POR ÁREA
|--------------------------------------------------------------------------
*/

$sqlComparativa = "

    SELECT

        $expresionAreaEquipo
        AS area_calculada,

        COUNT(*) AS total

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        ) >= DATE_SUB(
            CURDATE(),
            INTERVAL 13 DAY
        )

    GROUP BY

        $expresionAreaEquipo
";


$resultadoComparativa =
    mysqli_query(
        $conn,
        $sqlComparativa
    );

if ($resultadoComparativa) {

    while (
        $fila =
        mysqli_fetch_assoc(
            $resultadoComparativa
        )
    ) {

        $area =
            $fila['area_calculada'];

        if (
            isset(
                $comparativaAreas[$area]
            )
        ) {

            $comparativaAreas[$area][
                'mantenimientos'
            ] =
                (int)$fila['total'];
        }
    }
}


/*
|--------------------------------------------------------------------------
| DAÑOS POR ÁREA
|--------------------------------------------------------------------------
*/

$sqlComparativaDanios = "

    SELECT

        $expresionAreaEquipo
        AS area_calculada,

        COUNT(*) AS total

    FROM mantenimientos m

    INNER JOIN equipos e
        ON e.id = m.equipo_id

    WHERE

        DATE(
            COALESCE(
                m.fecha_registro,
                m.fecha_inicio
            )
        ) >= DATE_SUB(
            CURDATE(),
            INTERVAL 13 DAY
        )

        AND (

            LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%dañ%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%danad%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%daño%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%falla%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%avería%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%averia%'

            OR LOWER(
                COALESCE(
                    m.diagnostico,
                    ''
                )
            ) LIKE '%defect%'
        )

    GROUP BY

        $expresionAreaEquipo
";


$resultadoComparativaDanios =
    mysqli_query(
        $conn,
        $sqlComparativaDanios
    );

if ($resultadoComparativaDanios) {

    while (
        $fila =
        mysqli_fetch_assoc(
            $resultadoComparativaDanios
        )
    ) {

        $area =
            $fila['area_calculada'];

        if (
            isset(
                $comparativaAreas[$area]
            )
        ) {

            $comparativaAreas[$area][
                'danios'
            ] =
                (int)$fila['total'];
        }
    }
}


/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

include("../includes/sidebar.php");

?>

<style>

.mantenimiento-page {
    padding-bottom: 50px;
}

.mantenimiento-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}

.mantenimiento-title h2 {
    margin: 0;
    color: #002855;
    font-size: 32px;
    font-weight: 700;
}

.mantenimiento-title p {
    margin: 6px 0 0;
    color: #64748b;
}

.mantenimiento-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mantenimiento-actions .btn {
    border-radius: 10px;
    font-weight: 700;
}


/* BOTONES DE ÁREA */

.mantenimiento-filtros {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.btn-filtro-mantenimiento {
    border-radius: 12px;
    padding: 13px 22px;
    text-decoration: none;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: .2s ease;
    border: 1px solid #dbe3ec;
}

.btn-filtro-mantenimiento:hover {
    transform: translateY(-2px);
}

.btn-filtro-activo {
    background: #002855;
    color: #fff;
    border-color: #002855;
}

.btn-filtro-activo:hover {
    color: #fff;
    background: #003d7a;
}

.btn-filtro-inactivo {
    background: #fff;
    color: #002855;
}

.btn-filtro-inactivo:hover {
    color: #002855;
    background: #f1f6fb;
}

.btn-filtro-estadisticas {
    background: #6f42c1;
    color: #fff;
    border-color: #6f42c1;
}

.btn-filtro-estadisticas:hover {
    color: #fff;
    background: #59339d;
}


/* TARJETAS */

.mantenimiento-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,.07);
}

.mantenimiento-card h4 {
    color: #002855;
    font-weight: 700;
    margin-bottom: 18px;
}


/* TABLAS */

.tabla-mantenimiento {
    width: 100%;
    border-collapse: collapse;
}

.tabla-mantenimiento thead th {
    background: #002855;
    color: #fff;
    padding: 14px;
    font-size: 13px;
    white-space: nowrap;
}

.tabla-mantenimiento tbody td {
    padding: 14px;
    border-bottom: 1px solid #edf0f4;
    vertical-align: middle;
}

.tabla-mantenimiento tbody tr:hover {
    background: #f8fbff;
}

.codigo-equipo {
    color: #64748b;
    font-size: 13px;
}

.equipo-nombre {
    font-weight: 700;
    color: #0f172a;
}

.texto-mantenimiento {
    max-width: 280px;
}

.ubicacion-equipo {
    line-height: 1.5;
}

.ubicacion-edificio {
    font-weight: 700;
    color: #0f172a;
}

.ubicacion-piso {
    color: #475569;
    font-size: 13px;
}

.ubicacion-salon {
    color: #64748b;
    font-size: 13px;
}


/* ESTADOS */

.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.estado-mantenimiento {
    background: #fff3cd;
    color: #856404;
}

.estado-danado {
    background: #f8d7da;
    color: #842029;
}

.estado-proceso {
    background: #cfe2ff;
    color: #084298;
}

.estado-finalizado {
    background: #d1e7dd;
    color: #0f5132;
}

.estado-operativo {
    background: #d1e7dd;
    color: #0f5132;
}


/* ACCIONES */

.btn-accion {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}


/* ESTADÍSTICAS */

.estadistica-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(0,0,0,.07);
    height: 100%;
}

.estadistica-icono {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    margin-bottom: 14px;
}

.estadistica-numero {
    font-size: 30px;
    font-weight: 800;
    color: #002855;
    line-height: 1;
}

.estadistica-label {
    margin-top: 8px;
    color: #64748b;
    font-weight: 600;
}

.icono-danado {
    background: #f8d7da;
    color: #842029;
}

.icono-mantenimiento {
    background: #fff3cd;
    color: #856404;
}

.icono-proceso {
    background: #cfe2ff;
    color: #084298;
}

.icono-finalizado {
    background: #d1e7dd;
    color: #0f5132;
}

.grafica-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 8px 25px rgba(0,0,0,.07);
    margin-bottom: 25px;
}

.grafica-card h4 {
    color: #002855;
    font-weight: 700;
    margin-bottom: 20px;
}

.grafica-contenedor {
    position: relative;
    height: 330px;
}


/* MODALES */

.modal-content {
    border: 0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,.18);
}

.modal-header {
    padding: 18px 22px;
}

.modal-body {
    padding: 22px;
}

.modal-footer {
    padding: 16px 22px;
}


/* RESPONSIVE */

@media(max-width: 768px) {

    .mantenimiento-header {
        align-items: flex-start;
    }

    .mantenimiento-actions {
        width: 100%;
    }

    .mantenimiento-actions .btn {
        flex: 1;
    }

    .btn-filtro-mantenimiento {
        flex: 1;
        justify-content: center;
    }

    .tabla-mantenimiento {
        min-width: 1100px;
    }

}

</style>


<div class="main mantenimiento-page">

    <?php include("../includes/header.php"); ?>

    <div class="content">


        <!-- ENCABEZADO -->

        <div class="mantenimiento-header">

            <div class="mantenimiento-title">

                <h2>

                    <?php if ($vista === 'estadisticas'): ?>

                        <i class="fas fa-chart-line"></i>

                        Estadísticas de Mantenimiento

                    <?php else: ?>

                        <i class="fas fa-screwdriver-wrench"></i>

                        Mantenimiento

                    <?php endif; ?>

                </h2>

                <p>
                    Gestión de equipos, daños y mantenimientos.
                </p>

            </div>


            <div class="mantenimiento-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalNuevoMantenimiento">

                    <i class="fas fa-plus"></i>

                    Registrar mantenimiento

                </button>

            </div>

        </div>


        <!-- FILTROS -->

        <div class="mantenimiento-filtros">

            <a
                href="mantenimiento.php?area=audiovisuales&vista=mantenimiento"
                class="btn-filtro-mantenimiento
                <?= (
                    $vista === 'mantenimiento' &&
                    $areaSeleccionada === 'audiovisuales'
                )
                    ? 'btn-filtro-activo'
                    : 'btn-filtro-inactivo';
                ?>">

                <i class="fas fa-video"></i>

                Audiovisuales

            </a>


            <a
                href="mantenimiento.php?area=automatizacion&vista=mantenimiento"
                class="btn-filtro-mantenimiento
                <?= (
                    $vista === 'mantenimiento' &&
                    $areaSeleccionada === 'automatizacion'
                )
                    ? 'btn-filtro-activo'
                    : 'btn-filtro-inactivo';
                ?>">

                <i class="fas fa-gears"></i>

                Automatización

            </a>


            <a
                href="mantenimiento.php?area=<?= urlencode($areaSeleccionada); ?>&vista=estadisticas"
                class="btn-filtro-mantenimiento btn-filtro-estadisticas">

                <i class="fas fa-chart-column"></i>

                Estadísticas

            </a>

        </div>


        <!-- MENSAJES -->

        <?php if (isset($_GET['msg'])): ?>

            <?php if ($_GET['msg'] === 'registrado'): ?>

                <script>

                document.addEventListener(
                    "DOMContentLoaded",
                    function () {

                        if (
                            typeof Swal !== "undefined"
                        ) {

                            Swal.fire({

                                icon: "success",

                                title:
                                    "Mantenimiento registrado",

                                text:
                                    "El mantenimiento fue registrado correctamente.",

                                confirmButtonColor:
                                    "#002855"

                            });

                        }

                    }
                );

                </script>

            <?php elseif ($_GET['msg'] === 'finalizado'): ?>

                <script>

                document.addEventListener(
                    "DOMContentLoaded",
                    function () {

                        if (
                            typeof Swal !== "undefined"
                        ) {

                            Swal.fire({

                                icon: "success",

                                title:
                                    "Mantenimiento finalizado",

                                text:
                                    "El mantenimiento fue finalizado correctamente.",

                                confirmButtonColor:
                                    "#002855"

                            });

                        }

                    }
                );

                </script>

            <?php elseif ($_GET['msg'] === 'error'): ?>

                <script>

                document.addEventListener(
                    "DOMContentLoaded",
                    function () {

                        if (
                            typeof Swal !== "undefined"
                        ) {

                            Swal.fire({

                                icon: "error",

                                title:
                                    "No se pudo registrar",

                                text:
                                    "Ocurrió un error al procesar el mantenimiento.",

                                confirmButtonColor:
                                    "#002855"

                            });

                        }

                    }
                );

                </script>

            <?php endif; ?>

        <?php endif; ?>


        <?php if ($vista === 'mantenimiento'): ?>


            <!-- EQUIPOS QUE REQUIEREN ATENCIÓN -->

            <div class="mantenimiento-card">

                <h4>

                    <?php if (
                        $areaSeleccionada ===
                        'audiovisuales'
                    ): ?>

                        <i
                            class="fas fa-video text-primary">
                        </i>

                    <?php else: ?>

                        <i
                            class="fas fa-gears text-primary">
                        </i>

                    <?php endif; ?>

                    Equipos de
                    <?= htmlspecialchars(
                        $nombreArea,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                    que requieren atención

                </h4>


                <div class="table-responsive">

                    <table class="tabla-mantenimiento">

                        <thead>

                            <tr>

                                <th>Código</th>

                                <th>Equipo</th>

                                <th>Marca</th>

                                <th>Edificio</th>

                                <th>Piso</th>

                                <th>Salón</th>

                                <th>Estado</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (
                            count(
                                $equiposAtencion
                            ) > 0
                        ): ?>


                            <?php foreach (
                                $equiposAtencion
                                as $equipo
                            ): ?>

                                <tr>

                                    <td>

                                        <span
                                            class="codigo-equipo">

                                            <?= htmlspecialchars(
                                                $equipo['codigo'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="equipo-nombre">

                                            <?= htmlspecialchars(
                                                $equipo['nombre'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $equipo['marca'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $equipo[
                                                'equipo_edificio'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $equipo[
                                                'equipo_piso'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $equipo[
                                                'equipo_salon'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $equipo['estado'] ===
                                            'mantenimiento'
                                        ): ?>

                                            <span
                                                class="
                                                estado-badge
                                                estado-mantenimiento
                                                ">

                                                <i
                                                    class="
                                                    fas
                                                    fa-screwdriver-wrench
                                                    ">
                                                </i>

                                                Mantenimiento

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                estado-badge
                                                estado-danado
                                                ">

                                                <i
                                                    class="
                                                    fas
                                                    fa-triangle-exclamation
                                                    ">
                                                </i>

                                                Dañado

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="
                                    text-center
                                    text-muted
                                    py-4
                                    ">

                                    <i
                                        class="
                                        fas
                                        fa-circle-check
                                        text-success
                                        ">
                                    </i>

                                    No hay equipos de

                                    <?= htmlspecialchars(
                                        $nombreArea,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    que requieran atención.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- HISTORIAL -->

            <div class="mantenimiento-card">

                <div
                    class="
                    d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-2
                    ">

                    <h4 class="mb-0">

                        <i class="fas fa-history"></i>

                        Historial de

                        <?= htmlspecialchars(
                            $nombreArea,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </h4>


                    <span class="badge bg-primary">

                        <?= count(
                            $mantenimientos
                        ); ?>

                        registros

                    </span>

                </div>


                <div class="table-responsive mt-3">

                    <table
                        class="tabla-mantenimiento">

                        <thead>

                            <tr>

                                <th>Equipo</th>

                                <th>Fecha ingreso</th>

                                <th>Técnico</th>

                                <th>Proveedor</th>

                                <th>Edificio</th>

                                <th>Piso</th>

                                <th>Salón</th>

                                <th>Diagnóstico</th>

                                <th>Estado</th>

                                <th>Acciones</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            count(
                                $mantenimientos
                            ) > 0
                        ): ?>


                            <?php foreach (
                                $mantenimientos
                                as $m
                            ): ?>

                                <?php

                                $finalizado =
                                    !empty(
                                        $m['fecha_fin']
                                    );

                                ?>


                                <tr>


                                    <!-- EQUIPO -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $m[
                                                    'equipo_nombre'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </strong>

                                        <br>

                                        <small
                                            class="text-muted">

                                            <?= htmlspecialchars(
                                                $m[
                                                    'codigo'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </small>

                                    </td>


                                    <!-- FECHA -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $m[
                                                'fecha_inicio'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- TÉCNICO -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $m[
                                                'tecnico'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- PROVEEDOR -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $m[
                                                'proveedor'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- EDIFICIO -->

                                    <td>

                                        <span
                                            class="
                                            ubicacion-edificio
                                            ">

                                            <?= htmlspecialchars(
                                                $m[
                                                    'equipo_edificio'
                                                ] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- PISO -->

                                    <td>

                                        <span
                                            class="
                                            ubicacion-piso
                                            ">

                                            <?= htmlspecialchars(
                                                $m[
                                                    'equipo_piso'
                                                ] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- SALÓN -->

                                    <td>

                                        <span
                                            class="
                                            ubicacion-salon
                                            ">

                                            <?= htmlspecialchars(
                                                $m[
                                                    'equipo_salon'
                                                ] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- DIAGNÓSTICO -->

                                    <td
                                        class="
                                        texto-mantenimiento
                                        ">

                                        <?= htmlspecialchars(
                                            $m[
                                                'diagnostico'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ESTADO -->

                                    <td>

                                        <?php if (
                                            $finalizado
                                        ): ?>

                                            <span
                                                class="
                                                estado-badge
                                                estado-finalizado
                                                ">

                                                <i
                                                    class="
                                                    fas
                                                    fa-check-circle
                                                    ">
                                                </i>

                                                Finalizado

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="
                                                estado-badge
                                                estado-proceso
                                                ">

                                                <i
                                                    class="
                                                    fas
                                                    fa-spinner
                                                    ">
                                                </i>

                                                En proceso

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACCIONES -->

                                    <td>

                                        <?php if (
                                            !$finalizado
                                        ): ?>

                                            <button
                                                type="button"
                                                class="
                                                btn
                                                btn-success
                                                btn-accion
                                                "
                                                title="
                                                Finalizar mantenimiento
                                                "
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalFinalizar<?= (int)$m['id']; ?>">

                                                <i
                                                    class="
                                                    fas
                                                    fa-check
                                                    ">
                                                </i>

                                            </button>

                                        <?php else: ?>

                                            <div>

                                                <span
                                                    class="
                                                    estado-badge
                                                    estado-finalizado
                                                    ">

                                                    <i
                                                        class="
                                                        fas
                                                        fa-check
                                                        ">
                                                    </i>

                                                    Finalizado

                                                </span>

                                                <br>

                                                <small
                                                    class="text-muted">

                                                    <?= htmlspecialchars(
                                                        $m[
                                                            'fecha_fin'
                                                        ],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </small>

                                            </div>

                                        <?php endif; ?>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="10"
                                    class="
                                    text-center
                                    text-muted
                                    py-4
                                    ">

                                    No hay registros de mantenimiento
                                    para

                                    <?= htmlspecialchars(
                                        $nombreArea,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>.

                                </td>

                            </tr>

                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>


        <?php else: ?>


            <!-- ESTADÍSTICAS -->

            <div class="mantenimiento-card">

                <div
                    class="
                    d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-2
                    ">

                    <div>

                        <h4 class="mb-1">

                            <i
                                class="
                                fas
                                fa-chart-column
                                ">
                            </i>

                            Estadísticas de

                            <?= htmlspecialchars(
                                $nombreArea,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </h4>

                        <p
                            class="
                            text-muted
                            mb-0
                            ">

                            Comportamiento de los últimos 14 días.

                        </p>

                    </div>


                    <span class="badge bg-primary">

                        Últimos 14 días

                    </span>

                </div>

            </div>


            <!-- TARJETAS -->

            <div
                class="
                row
                g-4
                mb-4
                ">


                <div
                    class="
                    col-md-6
                    col-xl-3
                    ">

                    <div
                        class="
                        estadistica-card
                        ">

                        <div
                            class="
                            estadistica-icono
                            icono-danado
                            ">

                            <i
                                class="
                                fas
                                fa-triangle-exclamation
                                ">
                            </i>

                        </div>


                        <div
                            class="
                            estadistica-numero
                            ">

                            <?= $estadisticas[
                                'equipos_danados'
                            ]; ?>

                        </div>


                        <div
                            class="
                            estadistica-label
                            ">

                            Equipos dañados

                        </div>

                    </div>

                </div>


                <div
                    class="
                    col-md-6
                    col-xl-3
                    ">

                    <div
                        class="
                        estadistica-card
                        ">

                        <div
                            class="
                            estadistica-icono
                            icono-mantenimiento
                            ">

                            <i
                                class="
                                fas
                                fa-screwdriver-wrench
                                ">
                            </i>

                        </div>


                        <div
                            class="
                            estadistica-numero
                            ">

                            <?= $estadisticas[
                                'equipos_mantenimiento'
                            ]; ?>

                        </div>


                        <div
                            class="
                            estadistica-label
                            ">

                            Equipos en mantenimiento

                        </div>

                    </div>

                </div>


                <div
                    class="
                    col-md-6
                    col-xl-3
                    ">

                    <div
                        class="
                        estadistica-card
                        ">

                        <div
                            class="
                            estadistica-icono
                            icono-proceso
                            ">

                            <i
                                class="
                                fas
                                fa-spinner
                                ">
                            </i>

                        </div>


                        <div
                            class="
                            estadistica-numero
                            ">

                            <?= $estadisticas[
                                'mantenimientos_activos'
                            ]; ?>

                        </div>


                        <div
                            class="
                            estadistica-label
                            ">

                            Mantenimientos activos

                        </div>

                    </div>

                </div>


                <div
                    class="
                    col-md-6
                    col-xl-3
                    ">

                    <div
                        class="
                        estadistica-card
                        ">

                        <div
                            class="
                            estadistica-icono
                            icono-finalizado
                            ">

                            <i
                                class="
                                fas
                                fa-check-circle
                                ">
                            </i>

                        </div>


                        <div
                            class="
                            estadistica-numero
                            ">

                            <?= $estadisticas[
                                'mantenimientos_finalizados'
                            ]; ?>

                        </div>


                        <div
                            class="
                            estadistica-label
                            ">

                            Mantenimientos finalizados

                        </div>

                    </div>

                </div>

            </div>


            <!-- GRÁFICA MANTENIMIENTOS -->

            <div class="grafica-card">

                <h4>

                    <i
                        class="
                        fas
                        fa-chart-line
                        ">
                    </i>

                    Mantenimientos registrados por día

                </h4>


                <div
                    class="
                    grafica-contenedor
                    ">

                    <canvas
                        id="graficaMantenimientos">
                    </canvas>

                </div>

            </div>


            <!-- GRÁFICA DAÑOS -->

            <div class="grafica-card">

                <h4>

                    <i
                        class="
                        fas
                        fa-triangle-exclamation
                        ">
                    </i>

                    Daños y fallas reportadas por día

                </h4>


                <div
                    class="
                    grafica-contenedor
                    ">

                    <canvas
                        id="graficaDanios">
                    </canvas>

                </div>

            </div>


            <!-- COMPARATIVA -->

            <div class="grafica-card">

                <h4>

                    <i
                        class="
                        fas
                        fa-chart-column
                        ">
                    </i>

                    Comparativa por área

                </h4>


                <div
                    class="
                    grafica-contenedor
                    ">

                    <canvas
                        id="graficaComparativa">
                    </canvas>

                </div>

            </div>


        <?php endif; ?>

    </div>

</div>


<!-- =========================================================
     MODAL NUEVO MANTENIMIENTO
========================================================= -->

<div
    class="modal fade"
    id="modalNuevoMantenimiento"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                action="../actions/mantenimiento/registrar_mantenimiento.php"
                method="POST">


                <div
                    class="
                    modal-header
                    bg-primary
                    text-white
                    ">

                    <h5 class="modal-title">

                        <i
                            class="
                            fas
                            fa-screwdriver-wrench
                            ">
                        </i>

                        Registrar mantenimiento

                    </h5>


                    <button
                        type="button"
                        class="
                        btn-close
                        btn-close-white
                        "
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">


                        <!-- ÁREA -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

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
                                    value="audiovisuales">

                                    🎥 Audiovisuales

                                </option>


                                <option
                                    value="automatizacion">

                                    ⚙️ Automatización

                                </option>

                            </select>

                        </div>


                        <!-- EQUIPO -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

                                Equipo

                            </label>


                            <select
                                name="equipo_id"
                                class="form-select"
                                required>

                                <option value="">

                                    Seleccionar equipo

                                </option>


                                <?php foreach (
                                    $todosEquipos
                                    as $equipoModal
                                ): ?>


                                    <option
                                        value="<?= (int)$equipoModal['id']; ?>">

                                        <?= htmlspecialchars(
                                            $equipoModal['codigo'] .
                                            ' - ' .
                                            $equipoModal['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        -

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $equipoModal[
                                                    'area_calculada'
                                                ]
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        |

                                        <?= htmlspecialchars(
                                            $equipoModal[
                                                'equipo_edificio'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        -

                                        <?= htmlspecialchars(
                                            $equipoModal[
                                                'equipo_piso'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        -

                                        <?= htmlspecialchars(
                                            $equipoModal[
                                                'equipo_salon'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </option>


                                <?php endforeach; ?>

                            </select>


                            <small
                                class="text-muted">

                                Puedes seleccionar cualquier equipo
                                registrado.

                            </small>

                        </div>


                        <!-- TIPO -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

                                Tipo de reporte

                            </label>


                            <select
                                name="tipo_mantenimiento"
                                class="form-select"
                                required>

                                <option value="">

                                    Seleccionar

                                </option>


                                <option
                                    value="mantenimiento">

                                    🔧 Mantenimiento

                                </option>


                                <option
                                    value="danado">

                                    ⚠️ Dañado

                                </option>

                            </select>

                        </div>


                        <!-- FECHA -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

                                Fecha de ingreso

                            </label>


                            <input
                                type="date"
                                name="fecha_inicio"
                                class="form-control"
                                value="<?= date('Y-m-d'); ?>"
                                required>

                        </div>


                        <!-- TÉCNICO -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

                                Técnico / Responsable

                            </label>


                            <input
                                type="text"
                                name="tecnico"
                                class="form-control"
                                placeholder="
                                Nombre del técnico
                                ">

                        </div>


                        <!-- PROVEEDOR -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

                                Proveedor

                            </label>


                            <input
                                type="text"
                                name="proveedor"
                                class="form-control"
                                placeholder="
                                Nombre del proveedor
                                ">

                        </div>


                        <!-- UBICACIÓN DEL MANTENIMIENTO -->

                        <div class="col-md-6">

                            <label
                                class="form-label">

                                Lugar del mantenimiento

                            </label>


                            <input
                                type="text"
                                name="ubicacion"
                                class="form-control"
                                placeholder="
                                Lugar donde se realiza el mantenimiento
                                ">

                        </div>


                        <!-- DIAGNÓSTICO -->

                        <div class="col-12">

                            <label
                                class="form-label">

                                Diagnóstico / Falla

                            </label>


                            <textarea
                                name="diagnostico"
                                class="form-control"
                                rows="3"
                                placeholder="
                                Describe el diagnóstico o falla
                                "
                                required></textarea>

                        </div>


                        <!-- OBSERVACIONES -->

                        <div class="col-12">

                            <label
                                class="form-label">

                                Observaciones

                            </label>


                            <textarea
                                name="observaciones"
                                class="form-control"
                                rows="3"
                                placeholder="
                                Observaciones adicionales
                                "></textarea>

                        </div>


                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="
                        btn
                        btn-secondary
                        "
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="
                        btn
                        btn-primary
                        ">

                        <i
                            class="
                            fas
                            fa-save
                            ">
                        </i>

                        Registrar

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     MODALES PARA FINALIZAR
========================================================= -->

<?php foreach (
    $mantenimientos
    as $mantenimientoModal
): ?>


    <?php if (
        empty(
            $mantenimientoModal[
                'fecha_fin'
            ]
        )
    ): ?>


        <div
            class="modal fade"
            id="modalFinalizar<?= (int)$mantenimientoModal['id']; ?>"
            tabindex="-1"
            aria-hidden="true">


            <div class="modal-dialog modal-lg">

                <div class="modal-content">


                    <form
                        action="../actions/mantenimiento/finalizar_mantenimiento.php"
                        method="POST">


                        <div
                            class="
                            modal-header
                            bg-success
                            text-white
                            ">

                            <h5
                                class="modal-title">

                                <i
                                    class="
                                    fas
                                    fa-check-circle
                                    ">
                                </i>

                                Finalizar mantenimiento

                            </h5>


                            <button
                                type="button"
                                class="
                                btn-close
                                btn-close-white
                                "
                                data-bs-dismiss="modal">
                            </button>

                        </div>


                        <div class="modal-body">


                            <input
                                type="hidden"
                                name="mantenimiento_id"
                                value="<?= (int)$mantenimientoModal['id']; ?>">


                            <div
                                class="
                                alert
                                alert-light
                                border
                                ">

                                <strong>

                                    <?= htmlspecialchars(
                                        $mantenimientoModal[
                                            'equipo_nombre'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>


                                <br>


                                <small>

                                    <?= htmlspecialchars(
                                        $mantenimientoModal[
                                            'codigo'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </small>


                                <br>


                                <small>

                                    Edificio:

                                    <?= htmlspecialchars(
                                        $mantenimientoModal[
                                            'equipo_edificio'
                                        ] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    |

                                    Piso:

                                    <?= htmlspecialchars(
                                        $mantenimientoModal[
                                            'equipo_piso'
                                        ] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    |

                                    Salón:

                                    <?= htmlspecialchars(
                                        $mantenimientoModal[
                                            'equipo_salon'
                                        ] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </small>

                            </div>


                            <div class="row g-3">


                                <!-- FECHA -->

                                <div
                                    class="col-md-6">

                                    <label
                                        class="form-label">

                                        Fecha de finalización

                                    </label>


                                    <input
                                        type="date"
                                        name="fecha_fin"
                                        class="form-control"
                                        value="<?= date('Y-m-d'); ?>"
                                        min="<?= htmlspecialchars(
                                            $mantenimientoModal[
                                                'fecha_inicio'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        required>

                                </div>


                                <!-- ESTADO -->

                                <div
                                    class="col-md-6">

                                    <label
                                        class="form-label">

                                        Estado final del equipo

                                    </label>


                                    <select
                                        name="estado_equipo"
                                        class="form-select"
                                        required>


                                        <option
                                            value="operativo">

                                            Operativo

                                        </option>


                                        <option
                                            value="mantenimiento">

                                            Mantenimiento

                                        </option>


                                        <option
                                            value="dañado">

                                            Dañado

                                        </option>


                                    </select>

                                </div>


                                <!-- TRABAJO -->

                                <div
                                    class="col-12">

                                    <label
                                        class="form-label">

                                        Trabajo realizado

                                    </label>


                                    <textarea
                                        name="trabajo_realizado"
                                        class="form-control"
                                        rows="4"
                                        placeholder="
                                        Describe qué se hizo para solucionar el problema
                                        "
                                        required><?= htmlspecialchars(
                                            $mantenimientoModal[
                                                'trabajo_realizado'
                                            ] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?></textarea>

                                </div>


                                <!-- OBSERVACIONES -->

                                <div
                                    class="col-12">

                                    <label
                                        class="form-label">

                                        Observaciones de finalización

                                    </label>


                                    <textarea
                                        name="observaciones_finalizacion"
                                        class="form-control"
                                        rows="3"
                                        placeholder="
                                        Observaciones finales
                                        "></textarea>

                                </div>


                            </div>

                        </div>


                        <div
                            class="modal-footer">


                            <button
                                type="button"
                                class="
                                btn
                                btn-secondary
                                "
                                data-bs-dismiss="modal">

                                Cancelar

                            </button>


                            <button
                                type="submit"
                                class="
                                btn
                                btn-success
                                ">

                                <i
                                    class="
                                    fas
                                    fa-check
                                    ">
                                </i>

                                Finalizar mantenimiento

                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>


    <?php endif; ?>

<?php endforeach; ?>


<!-- SWEETALERT -->

<script
    src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
</script>


<?php if (
    $vista === 'estadisticas'
): ?>


<script
    src="
    https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js
    ">
</script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        if (
            typeof Chart === "undefined"
        ) {

            return;

        }


        const etiquetasDias =
            <?= json_encode(
                $labelsDias,
                JSON_UNESCAPED_UNICODE
            ); ?>;


        const datosMantenimiento =
            <?= json_encode(
                $valoresMantenimiento
            ); ?>;


        const datosDanios =
            <?= json_encode(
                $valoresDanios
            ); ?>;


        /*
        |--------------------------------------------------------------------------
        | MANTENIMIENTOS
        |--------------------------------------------------------------------------
        */

        const canvasMantenimientos =
            document.getElementById(
                "graficaMantenimientos"
            );


        if (
            canvasMantenimientos
        ) {

            new Chart(
                canvasMantenimientos,
                {

                    type: "line",

                    data: {

                        labels:
                            etiquetasDias,

                        datasets: [

                            {

                                label:
                                    "Mantenimientos",

                                data:
                                    datosMantenimiento,

                                borderColor:
                                    "#0d6efd",

                                backgroundColor:
                                    "rgba(13,110,253,.12)",

                                fill:
                                    true,

                                tension:
                                    .35,

                                borderWidth:
                                    3,

                                pointRadius:
                                    4,

                                pointHoverRadius:
                                    6

                            }

                        ]

                    },

                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,

                        plugins: {

                            legend: {

                                display:
                                    true

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    precision:
                                        0

                                }

                            }

                        }

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | DAÑOS
        |--------------------------------------------------------------------------
        */

        const canvasDanios =
            document.getElementById(
                "graficaDanios"
            );


        if (
            canvasDanios
        ) {

            new Chart(
                canvasDanios,
                {

                    type: "bar",

                    data: {

                        labels:
                            etiquetasDias,

                        datasets: [

                            {

                                label:
                                    "Daños / fallas",

                                data:
                                    datosDanios,

                                backgroundColor:
                                    "rgba(220,53,69,.75)",

                                borderColor:
                                    "#dc3545",

                                borderWidth:
                                    1,

                                borderRadius:
                                    8

                            }

                        ]

                    },

                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,

                        plugins: {

                            legend: {

                                display:
                                    true

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    precision:
                                        0

                                }

                            }

                        }

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | COMPARATIVA
        |--------------------------------------------------------------------------
        */

        const canvasComparativa =
            document.getElementById(
                "graficaComparativa"
            );


        if (
            canvasComparativa
        ) {

            new Chart(
                canvasComparativa,
                {

                    type: "bar",

                    data: {

                        labels: [

                            "Audiovisuales",

                            "Automatización"

                        ],

                        datasets: [

                            {

                                label:
                                    "Mantenimientos",

                                data: [

                                    <?= (int)$comparativaAreas[
                                        'audiovisuales'
                                    ]['mantenimientos']; ?>,

                                    <?= (int)$comparativaAreas[
                                        'automatizacion'
                                    ]['mantenimientos']; ?>

                                ],

                                backgroundColor:
                                    "rgba(13,110,253,.75)",

                                borderColor:
                                    "#0d6efd",

                                borderWidth:
                                    1,

                                borderRadius:
                                    8

                            },


                            {

                                label:
                                    "Daños / fallas",

                                data: [

                                    <?= (int)$comparativaAreas[
                                        'audiovisuales'
                                    ]['danios']; ?>,

                                    <?= (int)$comparativaAreas[
                                        'automatizacion'
                                    ]['danios']; ?>

                                ],

                                backgroundColor:
                                    "rgba(220,53,69,.75)",

                                borderColor:
                                    "#dc3545",

                                borderWidth:
                                    1,

                                borderRadius:
                                    8

                            }

                        ]

                    },

                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,

                        plugins: {

                            legend: {

                                display:
                                    true

                            }

                        },

                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    precision:
                                        0

                                }

                            }

                        }

                    }

                }
            );

        }

    }
);

</script>


<?php endif; ?>


<?php include("../includes/footer.php"); ?>

