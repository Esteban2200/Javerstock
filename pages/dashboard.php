<?php

/* =========================================================
   JAVERSTOCK - DASHBOARD
   ========================================================= */

/* =========================================================
   AUTENTICACIÓN
   ========================================================= */

require_once("../includes/auth.php");
require_once("../includes/permisos.php");
require_once("../includes/conexion.php");


/* =========================================================
   VALIDAR CONEXIÓN
   ========================================================= */

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Error: No se encontró una conexión válida con la base de datos.");
}


/* =========================================================
   ESTADÍSTICAS DEL INVENTARIO
   ========================================================= */


/* ---------------------------------------------------------
   TOTAL DE PRODUCTOS
--------------------------------------------------------- */

$totalProductos = 0;

$queryTotalProductos = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM productos"
);

if ($queryTotalProductos) {

    $fila = mysqli_fetch_assoc($queryTotalProductos);

    $totalProductos = (int)($fila['total'] ?? 0);

}


/* ---------------------------------------------------------
   PRODUCTOS DISPONIBLES
--------------------------------------------------------- */

$productosDisponibles = 0;

$queryDisponibles = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM productos
     WHERE cantidad > stock_minimo"
);

if ($queryDisponibles) {

    $fila = mysqli_fetch_assoc($queryDisponibles);

    $productosDisponibles = (int)($fila['total'] ?? 0);

}


/* ---------------------------------------------------------
   PRODUCTOS CON STOCK BAJO
--------------------------------------------------------- */

$productosStockBajo = 0;

$queryStockBajo = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM productos
     WHERE cantidad <= stock_minimo
       AND cantidad > 0"
);

if ($queryStockBajo) {

    $fila = mysqli_fetch_assoc($queryStockBajo);

    $productosStockBajo = (int)($fila['total'] ?? 0);

}


/* ---------------------------------------------------------
   PRODUCTOS AGOTADOS
--------------------------------------------------------- */

$productosAgotados = 0;

$queryAgotados = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM productos
     WHERE cantidad = 0"
);

if ($queryAgotados) {

    $fila = mysqli_fetch_assoc($queryAgotados);

    $productosAgotados = (int)($fila['total'] ?? 0);

}


/* =========================================================
   MOVIMIENTOS DEL DÍA
   ========================================================= */


/* ---------------------------------------------------------
   ENTRADAS DE HOY
--------------------------------------------------------- */

$entradasHoy = 0;

$queryEntradasHoy = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(cantidad), 0) AS total
     FROM movimientos
     WHERE tipo = 'Entrada'
       AND DATE(fecha) = CURDATE()"
);

if ($queryEntradasHoy) {

    $fila = mysqli_fetch_assoc($queryEntradasHoy);

    $entradasHoy = (int)($fila['total'] ?? 0);

}


/* ---------------------------------------------------------
   SALIDAS DE HOY
--------------------------------------------------------- */

$salidasHoy = 0;

$querySalidasHoy = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(cantidad), 0) AS total
     FROM movimientos
     WHERE tipo = 'Salida'
       AND DATE(fecha) = CURDATE()"
);

if ($querySalidasHoy) {

    $fila = mysqli_fetch_assoc($querySalidasHoy);

    $salidasHoy = (int)($fila['total'] ?? 0);

}


/* =========================================================
   ÚLTIMOS MOVIMIENTOS
   ========================================================= */

$ultimosMovimientos = [];

$queryUltimosMovimientos = mysqli_query(
    $conn,
    "SELECT
        m.id,
        m.tipo,
        m.cantidad,
        m.fecha,
        p.nombre AS producto
     FROM movimientos m
     LEFT JOIN productos p
        ON p.id = m.producto_id
     ORDER BY m.fecha DESC
     LIMIT 5"
);

if ($queryUltimosMovimientos) {

    while ($fila = mysqli_fetch_assoc($queryUltimosMovimientos)) {

        $ultimosMovimientos[] = $fila;

    }

}


/* =========================================================
   DATOS PARA GRÁFICA - ÚLTIMOS 7 DÍAS
   ========================================================= */

$fechasGrafica = [];
$entradasGrafica = [];
$salidasGrafica = [];


for ($i = 6; $i >= 0; $i--) {

    $fecha = date(
        'Y-m-d',
        strtotime("-$i days")
    );

    $fechasGrafica[] = date(
        'd/m',
        strtotime($fecha)
    );

    $entradasGrafica[] = 0;
    $salidasGrafica[] = 0;

}


/* ---------------------------------------------------------
   CONSULTAR ENTRADAS Y SALIDAS
--------------------------------------------------------- */

$queryGrafica = mysqli_query(
    $conn,
    "SELECT
        DATE(fecha) AS fecha,
        tipo,
        COALESCE(SUM(cantidad), 0) AS total
     FROM movimientos
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(fecha), tipo
     ORDER BY fecha ASC"
);

if ($queryGrafica) {

    while ($fila = mysqli_fetch_assoc($queryGrafica)) {

        $fechaBD = $fila['fecha'];

        $indice = -1;

        for ($i = 6; $i >= 0; $i--) {

            $fechaComparar = date(
                'Y-m-d',
                strtotime("-$i days")
            );

            if ($fechaComparar === $fechaBD) {

                $indice = 6 - $i;

                break;

            }

        }

        if ($indice >= 0 && $indice < 7) {

            if (
                strtolower(trim($fila['tipo'])) === 'entrada'
            ) {

                $entradasGrafica[$indice] =
                    (int)$fila['total'];

            } elseif (
                strtolower(trim($fila['tipo'])) === 'salida'
            ) {

                $salidasGrafica[$indice] =
                    (int)$fila['total'];

            }

        }

    }

}


/* =========================================================
   PORCENTAJES DEL INVENTARIO
   ========================================================= */

$totalParaPorcentaje =
    $productosDisponibles +
    $productosStockBajo +
    $productosAgotados;


if ($totalParaPorcentaje > 0) {

    $porcentajeDisponible = round(
        ($productosDisponibles / $totalParaPorcentaje) * 100,
        1
    );

    $porcentajeStockBajo = round(
        ($productosStockBajo / $totalParaPorcentaje) * 100,
        1
    );

    $porcentajeAgotado = round(
        ($productosAgotados / $totalParaPorcentaje) * 100,
        1
    );

} else {

    $porcentajeDisponible = 0;
    $porcentajeStockBajo = 0;
    $porcentajeAgotado = 0;

}


/* =========================================================
   DATOS JSON PARA JAVASCRIPT
   ========================================================= */

$fechasGraficaJSON = json_encode(
    $fechasGrafica,
    JSON_UNESCAPED_UNICODE
);

$entradasGraficaJSON = json_encode(
    $entradasGrafica
);

$salidasGraficaJSON = json_encode(
    $salidasGrafica
);

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
        JaverStock | Dashboard
    </title>

    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <!-- =====================================================
         CSS GENERAL
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=12"
    >

    <!-- =====================================================
         CSS DASHBOARD
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css?v=12"
    >

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<?php include("../includes/sidebar.php"); ?>


<!-- =========================================================
     CONTENEDOR PRINCIPAL
========================================================= -->

<main class="dashboard-main">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <?php include("../includes/header.php"); ?>


    <!-- =====================================================
         CONTENIDO DEL DASHBOARD
    ====================================================== -->

    <section class="dashboard-content">


        <!-- =================================================
             ACCESOS RÁPIDOS
        ================================================== -->

        <div class="dashboard-section">


            <div class="section-heading">

                <div class="section-title">

                    <div class="section-icon lightning">
                        <i class="fas fa-bolt"></i>
                    </div>

                    <div>

                        <h2>
                            Accesos rápidos
                        </h2>

                        <p>
                            Accede rápidamente a las funciones principales del sistema
                        </p>

                    </div>

                </div>

            </div>


            <div class="quick-section">


                <div class="quick-grid">


                    <!-- =====================================
                         INVENTARIO
                    ====================================== -->

                    <?php if (tienePermiso('ver_inventario')): ?>

                        <a
                            href="inventario.php"
                            class="quick-card quick-blue"
                        >

                            <div class="quick-icon">

                                <i class="fas fa-boxes-stacked"></i>

                            </div>

                            <div class="quick-info">

                                <h3>
                                    Inventario
                                </h3>

                                <p>
                                    Consultar y gestionar productos
                                </p>

                            </div>

                            <div class="quick-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                        </a>

                    <?php endif; ?>


                    <!-- =====================================
                         NUEVO PRODUCTO
                    ====================================== -->

                    <?php if (tienePermiso('crear_producto')): ?>

                        <a
                            href="inventario.php"
                            class="quick-card quick-green"
                        >

                            <div class="quick-icon">

                                <i class="fas fa-plus"></i>

                            </div>

                            <div class="quick-info">

                                <h3>
                                    Nuevo Producto
                                </h3>

                                <p>
                                    Registrar un nuevo artículo
                                </p>

                            </div>

                            <div class="quick-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                        </a>

                    <?php endif; ?>


                    <!-- =====================================
                         MOVIMIENTOS
                    ====================================== -->

                    <?php if (tienePermiso('ver_movimientos')): ?>

                        <a
                            href="movimientos.php"
                            class="quick-card quick-purple"
                        >

                            <div class="quick-icon">

                                <i class="fas fa-right-left"></i>

                            </div>

                            <div class="quick-info">

                                <h3>
                                    Movimientos
                                </h3>

                                <p>
                                    Entradas y salidas de inventario
                                </p>

                            </div>

                            <div class="quick-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                        </a>

                    <?php endif; ?>


                    <!-- =====================================
                         REPORTES
                    ====================================== -->

                    <?php if (tienePermiso('ver_reportes')): ?>

                        <a
                            href="reportes.php"
                            class="quick-card quick-orange"
                        >

                            <div class="quick-icon">

                                <i class="fas fa-chart-column"></i>

                            </div>

                            <div class="quick-info">

                                <h3>
                                    Reportes
                                </h3>

                                <p>
                                    Consultar información del sistema
                                </p>

                            </div>

                            <div class="quick-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                        </a>

                    <?php endif; ?>


                    <!-- =====================================
                         USUARIOS
                    ====================================== -->

                    <?php if (tienePermiso('gestionar_usuarios')): ?>

                        <a
                            href="gestion_usuarios.php"
                            class="quick-card quick-cyan"
                        >

                            <div class="quick-icon">

                                <i class="fas fa-users"></i>

                            </div>

                            <div class="quick-info">

                                <h3>
                                    Usuarios
                                </h3>

                                <p>
                                    Gestionar usuarios y permisos
                                </p>

                            </div>

                            <div class="quick-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                        </a>

                    <?php endif; ?>


                    <!-- =====================================
                         CONFIGURACIÓN
                    ====================================== -->

                    <?php if (tienePermiso('configuracion')): ?>

                        <a
                            href="configuracion.php"
                            class="quick-card quick-red"
                        >

                            <div class="quick-icon">

                                <i class="fas fa-gear"></i>

                            </div>

                            <div class="quick-info">

                                <h3>
                                    Configuración
                                </h3>

                                <p>
                                    Configurar el sistema
                                </p>

                            </div>

                            <div class="quick-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                        </a>

                    <?php endif; ?>


                </div>

            </div>

        </div>


        <!-- =================================================
             ESTADÍSTICAS
        ================================================== -->

        <div class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card stat-blue">

                <div class="stat-icon">

                    <i class="fas fa-boxes-stacked"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Total productos
                    </span>

                    <strong>
                        <?= number_format($totalProductos); ?>
                    </strong>

                    <small>
                        Productos registrados
                    </small>

                </div>

            </div>


            <!-- DISPONIBLES -->

            <div class="stat-card stat-green">

                <div class="stat-icon">

                    <i class="fas fa-circle-check"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Disponibles
                    </span>

                    <strong>
                        <?= number_format($productosDisponibles); ?>
                    </strong>

                    <small>
                        Stock disponible
                    </small>

                </div>

            </div>


            <!-- STOCK BAJO -->

            <div class="stat-card stat-yellow">

                <div class="stat-icon">

                    <i class="fas fa-triangle-exclamation"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Stock bajo
                    </span>

                    <strong>
                        <?= number_format($productosStockBajo); ?>
                    </strong>

                    <small>
                        Requieren atención
                    </small>

                </div>

            </div>


            <!-- AGOTADOS -->

            <div class="stat-card stat-red">

                <div class="stat-icon">

                    <i class="fas fa-circle-xmark"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Agotados
                    </span>

                    <strong>
                        <?= number_format($productosAgotados); ?>
                    </strong>

                    <small>
                        Sin existencias
                    </small>

                </div>

            </div>


        </div>


        <!-- =================================================
             PARTE INFERIOR
        ================================================== -->

        <div class="bottom-grid">


            <!-- =================================================
                 RESUMEN DEL INVENTARIO
            ================================================== -->

            <div class="panel-card">


                <div class="panel-header">

                    <div class="panel-title">

                        <div class="panel-icon">

                            <i class="fas fa-chart-pie"></i>

                        </div>

                        <div>

                            <h2>
                                Estado del inventario
                            </h2>

                            <p>
                                Resumen actual
                            </p>

                        </div>

                    </div>


                    <div class="period-badge">

                        Actual

                        <i class="fas fa-calendar"></i>

                    </div>

                </div>


                <div class="summary-content">


                    <div class="donut-wrapper">

                        <div
                            class="donut-chart"
                            style="
                                --available: <?= $porcentajeDisponible; ?>%;
                                --low: <?= $porcentajeStockBajo; ?>%;
                            "
                        >

                            <div class="donut-center">

                                <strong>
                                    <?= number_format($totalParaPorcentaje); ?>
                                </strong>

                                <span>
                                    productos
                                </span>

                            </div>

                        </div>

                    </div>


                    <div class="summary-legend">


                        <!-- DISPONIBLES -->

                        <div class="legend-row">

                            <div class="legend-label">

                                <span class="legend-dot dot-blue"></span>

                                Disponibles

                            </div>

                            <strong>
                                <?= number_format($productosDisponibles); ?>
                            </strong>

                            <span>
                                <?= $porcentajeDisponible; ?>%
                            </span>

                        </div>


                        <!-- STOCK BAJO -->

                        <div class="legend-row">

                            <div class="legend-label">

                                <span class="legend-dot dot-yellow"></span>

                                Stock bajo

                            </div>

                            <strong>
                                <?= number_format($productosStockBajo); ?>
                            </strong>

                            <span>
                                <?= $porcentajeStockBajo; ?>%
                            </span>

                        </div>


                        <!-- AGOTADOS -->

                        <div class="legend-row">

                            <div class="legend-label">

                                <span class="legend-dot dot-red"></span>

                                Agotados

                            </div>

                            <strong>
                                <?= number_format($productosAgotados); ?>
                            </strong>

                            <span>
                                <?= $porcentajeAgotado; ?>%
                            </span>

                        </div>


                    </div>


                </div>

            </div>


            <!-- =================================================
                 ACTIVIDAD RECIENTE
            ================================================== -->

            <div class="panel-card">


                <div class="panel-header">

                    <div class="panel-title">

                        <div class="panel-icon">

                            <i class="fas fa-clock-rotate-left"></i>

                        </div>

                        <div>

                            <h2>
                                Actividad reciente
                            </h2>

                            <p>
                                Últimos movimientos
                            </p>

                        </div>

                    </div>


                    <?php if (tienePermiso('ver_movimientos')): ?>

                        <a
                            href="movimientos.php"
                            class="view-all"
                        >
                            Ver todos
                        </a>

                    <?php endif; ?>

                </div>


                <div class="activity-list">


                    <?php if (!empty($ultimosMovimientos)): ?>


                        <?php foreach ($ultimosMovimientos as $movimiento): ?>


                            <?php

                            $esEntrada =
                                strtolower(
                                    trim(
                                        $movimiento['tipo'] ?? ''
                                    )
                                ) === 'entrada';

                            ?>


                            <div class="activity-item">


                                <div
                                    class="activity-icon
                                    <?= $esEntrada
                                        ? 'activity-green'
                                        : 'activity-blue'; ?>"
                                >

                                    <i
                                        class="fas
                                        <?= $esEntrada
                                            ? 'fa-arrow-down'
                                            : 'fa-arrow-up'; ?>"
                                    ></i>

                                </div>


                                <div class="activity-info">

                                    <strong>

                                        <?= $esEntrada
                                            ? 'Entrada de inventario'
                                            : 'Salida de inventario'; ?>

                                    </strong>

                                    <span>

                                        <?= htmlspecialchars(
                                            $movimiento['producto']
                                                ?? 'Producto',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        ·

                                        Cantidad:
                                        <?= number_format(
                                            (int)(
                                                $movimiento['cantidad']
                                                    ?? 0
                                            )
                                        ); ?>

                                    </span>

                                </div>


                                <div class="activity-time">

                                    <?= !empty($movimiento['fecha'])
                                        ? date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $movimiento['fecha']
                                            )
                                        )
                                        : ''; ?>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-activity">

                            <i class="fas fa-inbox fa-2x"></i>

                            <span>
                                No hay movimientos recientes
                            </span>

                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </div>


        <!-- =================================================
             GRÁFICA DE MOVIMIENTOS
        ================================================== -->

        <?php if (tienePermiso('ver_movimientos')): ?>

            <div class="chart-panel">


                <div class="panel-header">

                    <div class="panel-title">

                        <div class="panel-icon">

                            <i class="fas fa-chart-line"></i>

                        </div>

                        <div>

                            <h2>
                                Movimientos de inventario
                            </h2>

                            <p>
                                Entradas y salidas de los últimos 7 días
                            </p>

                        </div>

                    </div>


                    <div class="period-badge">

                        Últimos 7 días

                        <i class="fas fa-calendar-days"></i>

                    </div>

                </div>


                <div class="chart-container">

                    <canvas
                        id="movimientosChart"
                    ></canvas>

                </div>


            </div>

        <?php endif; ?>


    </section>


</main>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =========================================================
     CHART.JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>


<!-- =========================================================
     GRÁFICA
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const canvas =
            document.getElementById(
                'movimientosChart'
            );


        if (!canvas) {
            return;
        }


        const fechas =
            <?= $fechasGraficaJSON ?: '[]'; ?>;


        const entradas =
            <?= $entradasGraficaJSON ?: '[]'; ?>;


        const salidas =
            <?= $salidasGraficaJSON ?: '[]'; ?>;


        new Chart(
            canvas,
            {

                type: 'line',

                data: {

                    labels: fechas,

                    datasets: [

                        {
                            label: 'Entradas',

                            data: entradas,

                            borderWidth: 3,

                            tension: 0.35,

                            fill: false,

                            pointRadius: 4,

                            pointHoverRadius: 6
                        },

                        {
                            label: 'Salidas',

                            data: salidas,

                            borderWidth: 3,

                            tension: 0.35,

                            fill: false,

                            pointRadius: 4,

                            pointHoverRadius: 6
                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    interaction: {

                        intersect: false,

                        mode: 'index'

                    },

                    plugins: {

                        legend: {

                            display: true,

                            position: 'top'

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {

                                precision: 0

                            }

                        }

                    }

                }

            }
        );


    }
);

</script>


</body>

</html>