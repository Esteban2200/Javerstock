<?php

include("../../includes/auth.php");
include("../../includes/conexion.php");
require_once("../../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;


/* =========================================================
   CONFIGURACIÓN
========================================================= */

$conexion = $conn;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);


/* =========================================================
   FUNCIONES
========================================================= */

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
 * Formatea valores para PDF
 */
function valorPDF($valor)
{
    if ($valor === null || $valor === '') {
        return '-';
    }

    return e($valor);
}


/*
 * Genera gráfica compatible con Dompdf
 */
function generarGraficaHTML(
    $titulo,
    $labels,
    $values
) {

    $labels = array_values($labels);
    $values = array_values($values);

    $maximo = 0;

    foreach ($values as $valor) {
        $valor = (float)$valor;

        if ($valor > $maximo) {
            $maximo = $valor;
        }
    }

    if ($maximo <= 0) {
        $maximo = 1;
    }

    $html = '';

    $html .= '<div class="grafica">';

    $html .= '<div class="grafica-titulo">'
        . e($titulo)
        . '</div>';

    if (count($labels) === 0) {

        $html .= '<div class="sin-datos-grafica">
                    No hay datos para mostrar
                  </div>';

        $html .= '</div>';

        return $html;
    }

    foreach ($labels as $i => $label) {

        $valor = isset($values[$i])
            ? (float)$values[$i]
            : 0;

        $porcentaje =
            ($valor / $maximo) * 100;

        if ($porcentaje < 2 && $valor > 0) {
            $porcentaje = 2;
        }

        $html .= '<div class="grafica-fila">';

        $html .= '<div class="grafica-label">'
            . e($label)
            . '</div>';

        $html .= '<div class="grafica-barra-contenedor">';

        $html .= '<div class="grafica-barra"
                    style="width:' . $porcentaje . '%;">
                  </div>';

        $html .= '</div>';

        $html .= '<div class="grafica-valor">'
            . number_format(
                $valor,
                0,
                ',',
                '.'
            )
            . '</div>';

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}


/*
 * =========================================================
 * FILTRO GENERAL
 * =========================================================
 *
 * IMPORTANTE:
 * Si se selecciona un área, la fila DEBE tener área
 * y debe coincidir exactamente.
 */
function cumpleFiltros(
    $fila,
    $area,
    $edificio,
    $estado,
    $desde,
    $hasta
) {

    /* =====================================================
       ÁREA
    ===================================================== */

    if ($area !== '') {

        if (!isset($fila['area'])) {
            return false;
        }

        $areaFila = strtolower(
            trim(
                (string)$fila['area']
            )
        );

        $areaFiltro = strtolower(
            trim(
                (string)$area
            )
        );

        if ($areaFila !== $areaFiltro) {
            return false;
        }
    }


    /* =====================================================
       EDIFICIO
    ===================================================== */

    if ($edificio !== '') {

        if (!isset($fila['edificio'])) {
            return false;
        }

        if (
            strtolower(
                trim(
                    (string)$fila['edificio']
                )
            )
            !==
            strtolower(
                trim(
                    (string)$edificio
                )
            )
        ) {

            return false;
        }
    }


    /* =====================================================
       ESTADO
    ===================================================== */

    if ($estado !== '') {

        if (!isset($fila['estado'])) {
            return false;
        }

        if (
            strtolower(
                trim(
                    (string)$fila['estado']
                )
            )
            !==
            strtolower(
                trim(
                    (string)$estado
                )
            )
        ) {

            return false;
        }
    }


    /* =====================================================
       FECHAS
    ===================================================== */

    $fechaFila = null;

    if (
        isset($fila['fecha'])
        &&
        $fila['fecha'] !== ''
    ) {

        $fechaFila =
            $fila['fecha'];
    }

    elseif (
        isset($fila['fecha_registro'])
        &&
        $fila['fecha_registro'] !== ''
    ) {

        $fechaFila =
            $fila['fecha_registro'];
    }

    elseif (
        isset($fila['fecha_inicio'])
        &&
        $fila['fecha_inicio'] !== ''
    ) {

        $fechaFila =
            $fila['fecha_inicio'];
    }


    if (
        $fechaFila
        &&
        (
            $desde !== ''
            ||
            $hasta !== ''
        )
    ) {

        $timestamp =
            strtotime($fechaFila);

        if ($timestamp !== false) {

            $fecha =
                date(
                    'Y-m-d',
                    $timestamp
                );


            if (
                $desde !== ''
                &&
                $fecha < $desde
            ) {

                return false;
            }


            if (
                $hasta !== ''
                &&
                $fecha > $hasta
            ) {

                return false;
            }
        }
    }


    return true;
}


/* =========================================================
   RECIBIR FILTROS
========================================================= */

$tipo = isset($_GET['tipo'])
    ? trim($_GET['tipo'])
    : 'equipos';

$area = isset($_GET['area'])
    ? strtolower(
        trim(
            $_GET['area']
        )
    )
    : '';

$edificio = isset($_GET['edificio'])
    ? trim(
        $_GET['edificio']
    )
    : '';

$estado = isset($_GET['estado'])
    ? strtolower(
        trim(
            $_GET['estado']
        )
    )
    : '';

$desde = isset($_GET['desde'])
    ? trim(
        $_GET['desde']
    )
    : '';

$hasta = isset($_GET['hasta'])
    ? trim(
        $_GET['hasta']
    )
    : '';


/*
 * Normalizar "todas"
 */
if (
    $area === 'todas'
    ||
    $area === 'todos'
    ||
    $area === 'all'
) {

    $area = '';
}


/* =========================================================
   VARIABLES GENERALES
========================================================= */

$rows = [];

$kpis = [];

$grafica1Titulo = '';
$grafica1Labels = [];
$grafica1Values = [];

$grafica2Titulo = '';
$grafica2Labels = [];
$grafica2Values = [];

$tituloReporte = 'Reporte';

$subtituloReporte = '';


/* =========================================================
   REPORTE DE EQUIPOS
========================================================= */

if ($tipo === 'equipos') {

    $tituloReporte =
        'Reporte de Equipos';

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
            fecha_registro
        FROM equipos
        ORDER BY nombre
    ";

    $resultado =
        $conexion->query($sql);


    $total = 0;
    $audiovisuales = 0;
    $automatizacion = 0;
    $operativos = 0;
    $mantenimiento = 0;

    $estados = [];


    if ($resultado) {

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            if (
                !cumpleFiltros(
                    $fila,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            $rows[] = $fila;

            $total++;


            $areaFila =
                strtolower(
                    trim(
                        (string)$fila['area']
                    )
                );


            if (
                $areaFila ===
                'audiovisuales'
            ) {

                $audiovisuales++;
            }


            if (
                $areaFila ===
                'automatizacion'
            ) {

                $automatizacion++;
            }


            $estadoFila =
                strtolower(
                    trim(
                        (string)$fila['estado']
                    )
                );


            if (
                $estadoFila ===
                'operativo'
                ||
                $estadoFila ===
                'disponible'
                ||
                $estadoFila ===
                'activo'
            ) {

                $operativos++;
            }


            if (
                strpos(
                    $estadoFila,
                    'manten'
                ) !== false
            ) {

                $mantenimiento++;
            }


            if (
                $estadoFila === ''
            ) {

                $estadoFila =
                    'sin estado';
            }


            if (
                !isset(
                    $estados[$estadoFila]
                )
            ) {

                $estados[$estadoFila] = 0;
            }

            $estados[$estadoFila]++;
        }
    }


    /* =====================================================
       KPIs
    ===================================================== */

    $kpis = [

        [
            'label' => 'Total equipos',
            'valor' => $total
        ],

        [
            'label' => 'Operativos',
            'valor' => $operativos
        ],

        [
            'label' => 'En mantenimiento',
            'valor' => $mantenimiento
        ],

    ];


    /*
     * Solo mostrar el área seleccionada
     */
    if ($area === 'audiovisuales') {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];
    }

    elseif ($area === 'automatizacion') {

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];
    }

    else {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];
    }


    /* =====================================================
       GRÁFICA POR ÁREA
    ===================================================== */

    $grafica1Titulo =
        'Equipos por área';


    if (
        $area === 'audiovisuales'
    ) {

        $grafica1Labels = [
            'Audiovisuales'
        ];

        $grafica1Values = [
            $audiovisuales
        ];
    }

    elseif (
        $area === 'automatizacion'
    ) {

        $grafica1Labels = [
            'Automatización'
        ];

        $grafica1Values = [
            $automatizacion
        ];
    }

    else {

        $grafica1Labels = [
            'Audiovisuales',
            'Automatización'
        ];

        $grafica1Values = [
            $audiovisuales,
            $automatizacion
        ];
    }


    /* =====================================================
       GRÁFICA POR ESTADO
    ===================================================== */

    $grafica2Titulo =
        'Equipos por estado';

    $grafica2Labels = [];

    $grafica2Values = [];


    foreach (
        $estados as $nombreEstado => $cantidad
    ) {

        $grafica2Labels[] =
            ucfirst(
                $nombreEstado
            );

        $grafica2Values[] =
            $cantidad;
    }
}


/* =========================================================
   REPORTE DE MANTENIMIENTOS
========================================================= */

elseif (
    $tipo === 'mantenimientos'
) {

    $tituloReporte =
        'Reporte de Mantenimientos';


    $sql = "
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
    ";


    $resultado =
        $conexion->query($sql);


    $total = 0;
    $activos = 0;
    $finalizados = 0;
    $audiovisuales = 0;
    $automatizacion = 0;


    if ($resultado) {

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            if (
                $fila['fecha_fin']
                === null
                ||
                $fila['fecha_fin']
                === ''
            ) {

                $fila['estado'] =
                    'activo';

            } else {

                $fila['estado'] =
                    'finalizado';
            }


            if (
                !cumpleFiltros(
                    $fila,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            $rows[] = $fila;

            $total++;


            if (
                $fila['estado']
                === 'activo'
            ) {

                $activos++;

            } else {

                $finalizados++;
            }


            $areaFila =
                strtolower(
                    trim(
                        (string)$fila['area']
                    )
                );


            if (
                $areaFila ===
                'audiovisuales'
            ) {

                $audiovisuales++;
            }


            if (
                $areaFila ===
                'automatizacion'
            ) {

                $automatizacion++;
            }
        }
    }


    /* =====================================================
       KPIs
    ===================================================== */

    $kpis = [

        [
            'label' => 'Total mantenimientos',
            'valor' => $total
        ],

        [
            'label' => 'Activos',
            'valor' => $activos
        ],

        [
            'label' => 'Finalizados',
            'valor' => $finalizados
        ],

    ];


    if (
        $area === 'audiovisuales'
    ) {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];

    }

    else {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];
    }


    /* =====================================================
       GRÁFICA ESTADO
    ===================================================== */

    $grafica1Titulo =
        'Estado de mantenimientos';

    $grafica1Labels = [
        'Activos',
        'Finalizados'
    ];

    $grafica1Values = [
        $activos,
        $finalizados
    ];


    /* =====================================================
       GRÁFICA ÁREA
    ===================================================== */

    $grafica2Titulo =
        'Mantenimientos por área';


    if (
        $area === 'audiovisuales'
    ) {

        $grafica2Labels = [
            'Audiovisuales'
        ];

        $grafica2Values = [
            $audiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $grafica2Labels = [
            'Automatización'
        ];

        $grafica2Values = [
            $automatizacion
        ];

    }

    else {

        $grafica2Labels = [
            'Audiovisuales',
            'Automatización'
        ];

        $grafica2Values = [
            $audiovisuales,
            $automatizacion
        ];
    }
}


/* =========================================================
   REPORTE DE MOVIMIENTOS
========================================================= */

elseif (
    $tipo === 'movimientos'
) {

    $tituloReporte =
        'Reporte de Movimientos';


    $sql = "
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
    ";


    $resultado =
        $conexion->query($sql);


    $total = 0;

    $entradas = 0;
    $salidas = 0;

    $unidadesEntrada = 0;
    $unidadesSalida = 0;


    if ($resultado) {

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            if (
                !cumpleFiltros(
                    $fila,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            $rows[] = $fila;

            $total++;


            $tipoMovimiento =
                strtolower(
                    trim(
                        (string)$fila['tipo']
                    )
                );


            $cantidad =
                (int)$fila['cantidad'];


            if (
                $tipoMovimiento ===
                'entrada'
            ) {

                $entradas++;

                $unidadesEntrada +=
                    $cantidad;

            }

            elseif (
                $tipoMovimiento ===
                'salida'
            ) {

                $salidas++;

                $unidadesSalida +=
                    $cantidad;
            }
        }
    }


    /* =====================================================
       KPIs
    ===================================================== */

    $kpis = [

        [
            'label' => 'Movimientos',
            'valor' => $total
        ],

        [
            'label' => 'Entradas',
            'valor' => $entradas
        ],

        [
            'label' => 'Salidas',
            'valor' => $salidas
        ],

        [
            'label' => 'Unidades entrada',
            'valor' => $unidadesEntrada
        ],

        [
            'label' => 'Unidades salida',
            'valor' => $unidadesSalida
        ]

    ];


    /* =====================================================
       GRÁFICA UNIDADES
    ===================================================== */

    $grafica1Titulo =
        'Unidades de inventario';

    $grafica1Labels = [
        'Entradas',
        'Salidas'
    ];

    $grafica1Values = [
        $unidadesEntrada,
        $unidadesSalida
    ];


    /* =====================================================
       GRÁFICA MOVIMIENTOS
    ===================================================== */

    $grafica2Titulo =
        'Cantidad de movimientos';

    $grafica2Labels = [
        'Entradas',
        'Salidas'
    ];

    $grafica2Values = [
        $entradas,
        $salidas
    ];
}


/* =========================================================
   REPORTE DE INVENTARIO
========================================================= */

elseif (
    $tipo === 'inventario'
) {

    $tituloReporte =
        'Reporte de Inventario';


    $sql = "
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
    ";


    $resultado =
        $conexion->query($sql);


    $productos = 0;
    $unidades = 0;

    $disponibles = 0;
    $bajoStock = 0;
    $agotados = 0;

    $unidadesDisponibles = 0;
    $unidadesBajo = 0;
    $unidadesAgotadas = 0;


    if ($resultado) {

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            $cantidad =
                (int)$fila['cantidad'];

            $minimo =
                (int)$fila['stock_minimo'];


            /* =============================================
               DETERMINAR ESTADO PRIMERO
            ============================================= */

            if ($cantidad <= 0) {

                $fila['estado'] =
                    'agotado';

            }

            elseif (
                $cantidad <= $minimo
            ) {

                $fila['estado'] =
                    'bajo';

            }

            else {

                $fila['estado'] =
                    'disponible';
            }


            /* =============================================
               FILTRAR ANTES DE CONTAR
            ============================================= */

            if (
                !cumpleFiltros(
                    $fila,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            /*
             * Solo desde aquí se cuentan los productos.
             * Esto corrige el problema de que los KPI
             * mostraban datos de todas las áreas.
             */

            $rows[] = $fila;

            $productos++;

            $unidades += $cantidad;


            if (
                $fila['estado']
                === 'agotado'
            ) {

                $agotados++;

                $unidadesAgotadas +=
                    max(
                        0,
                        $cantidad
                    );
            }

            elseif (
                $fila['estado']
                === 'bajo'
            ) {

                $bajoStock++;

                $unidadesBajo +=
                    $cantidad;

            }

            else {

                $disponibles++;

                $unidadesDisponibles +=
                    $cantidad;
            }
        }
    }


    /* =====================================================
       KPIs
    ===================================================== */

    $kpis = [

        [
            'label' => 'Productos',
            'valor' => $productos
        ],

        [
            'label' => 'Unidades',
            'valor' => $unidades
        ],

        [
            'label' => 'Disponibles',
            'valor' => $disponibles
        ],

        [
            'label' => 'Bajo stock',
            'valor' => $bajoStock
        ],

        [
            'label' => 'Agotados',
            'valor' => $agotados
        ]

    ];


    /* =====================================================
       GRÁFICA ESTADOS
    ===================================================== */

    $grafica1Titulo =
        'Estado del inventario';

    $grafica1Labels = [
        'Disponibles',
        'Bajo stock',
        'Agotados'
    ];

    $grafica1Values = [
        $disponibles,
        $bajoStock,
        $agotados
    ];


    /*
     * =====================================================
     * ÁREA EN INVENTARIO
     * =====================================================
     *
     * La tabla ya viene filtrada.
     * Esta gráfica se calcula nuevamente desde $rows.
     */

    $cantidadAudiovisuales = 0;
    $cantidadAutomatizacion = 0;


    foreach (
        $rows as $fila
    ) {

        $areaFila =
            strtolower(
                trim(
                    (string)$fila['area']
                )
            );


        if (
            $areaFila ===
            'audiovisuales'
        ) {

            $cantidadAudiovisuales++;

        }

        elseif (
            $areaFila ===
            'automatizacion'
        ) {

            $cantidadAutomatizacion++;
        }
    }


    $grafica2Titulo =
        'Productos por área';


    if (
        $area === 'audiovisuales'
    ) {

        $grafica2Labels = [
            'Audiovisuales'
        ];

        $grafica2Values = [
            $cantidadAudiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $grafica2Labels = [
            'Automatización'
        ];

        $grafica2Values = [
            $cantidadAutomatizacion
        ];

    }

    else {

        $grafica2Labels = [
            'Audiovisuales',
            'Automatización'
        ];

        $grafica2Values = [
            $cantidadAudiovisuales,
            $cantidadAutomatizacion
        ];
    }
}


/* =========================================================
   REPORTE DE PUNTOS DE RED
========================================================= */

elseif (
    $tipo === 'puntos_red'
) {

    $tituloReporte =
        'Reporte de Puntos de Red';


    /* =====================================================
       AUDIOVISUALES
    ===================================================== */

    $sqlAudiovisuales = "
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
    ";


    $resultadoAudiovisuales =
        $conexion->query(
            $sqlAudiovisuales
        );


    if (
        $resultadoAudiovisuales
    ) {

        while (
            $fila =
            $resultadoAudiovisuales
                ->fetch_assoc()
        ) {

            $fila['area'] =
                'audiovisuales';

            $fila['ubicacion_reportada'] =
                $fila['ubicacion'];


            if (
                !cumpleFiltros(
                    $fila,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            $rows[] = $fila;
        }
    }


    /* =====================================================
       AUTOMATIZACIÓN
    ===================================================== */

    $sqlAutomatizacion = "
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
    ";


    $resultadoAutomatizacion =
        $conexion->query(
            $sqlAutomatizacion
        );


    if (
        $resultadoAutomatizacion
    ) {

        while (
            $fila =
            $resultadoAutomatizacion
                ->fetch_assoc()
        ) {

            $filaNueva = [

                'id' =>
                    $fila['id'],

                'area' =>
                    'automatizacion',

                'edificio' =>
                    $fila['edificio'],

                'ubicacion_reportada' =>
                    $fila['ubicacion'],

                'equipo' =>
                    $fila['item'],

                'marca' =>
                    $fila['sistema'],

                'mac' =>
                    $fila['mac'],

                'ip' =>
                    $fila['ip'],

                'switch' =>
                    $fila['rack'],

                'puerto' =>
                    $fila['sw_port'],

                'estado' =>
                    $fila['estado'],

                'detalle' =>
                    $fila['zona'],

                'sub_sistema' =>
                    $fila['subsistema']
            ];


            if (
                !cumpleFiltros(
                    $filaNueva,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            $rows[] =
                $filaNueva;
        }
    }


    /* =====================================================
       CONTADORES DESDE LAS FILAS YA FILTRADAS
    ===================================================== */

    $total = count($rows);

    $audiovisuales = 0;
    $automatizacion = 0;

    $estados = [];


    foreach (
        $rows as $fila
    ) {

        $areaFila =
            strtolower(
                trim(
                    (string)$fila['area']
                )
            );


        if (
            $areaFila ===
            'audiovisuales'
        ) {

            $audiovisuales++;
        }


        elseif (
            $areaFila ===
            'automatizacion'
        ) {

            $automatizacion++;
        }


        $estadoFila =
            strtolower(
                trim(
                    (string)$fila['estado']
                )
            );


        if (
            $estadoFila === ''
        ) {

            $estadoFila =
                'sin estado';
        }


        if (
            !isset(
                $estados[$estadoFila]
            )
        ) {

            $estados[$estadoFila] = 0;
        }

        $estados[$estadoFila]++;
    }


    /* =====================================================
       KPIs
    ===================================================== */

    $kpis = [

        [
            'label' => 'Puntos de red',
            'valor' => $total
        ]

    ];


    if (
        $area === 'audiovisuales'
    ) {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];

    }

    else {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];
    }


    /* =====================================================
       GRÁFICA POR ÁREA
    ===================================================== */

    $grafica1Titulo =
        'Puntos por área';


    if (
        $area === 'audiovisuales'
    ) {

        $grafica1Labels = [
            'Audiovisuales'
        ];

        $grafica1Values = [
            $audiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $grafica1Labels = [
            'Automatización'
        ];

        $grafica1Values = [
            $automatizacion
        ];

    }

    else {

        $grafica1Labels = [
            'Audiovisuales',
            'Automatización'
        ];

        $grafica1Values = [
            $audiovisuales,
            $automatizacion
        ];
    }


    /* =====================================================
       GRÁFICA ESTADOS
    ===================================================== */

    $grafica2Titulo =
        'Puntos por estado';

    $grafica2Labels = [];
    $grafica2Values = [];


    foreach (
        $estados as $nombreEstado => $cantidad
    ) {

        $grafica2Labels[] =
            ucfirst(
                $nombreEstado
            );

        $grafica2Values[] =
            $cantidad;
    }
}


/* =========================================================
   REPORTE DE UBICACIONES
========================================================= */

elseif (
    $tipo === 'ubicaciones'
) {

    $tituloReporte =
        'Reporte de Ubicaciones';


    $sql = "
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
    ";


    $resultado =
        $conexion->query($sql);


    $total = 0;
    $audiovisuales = 0;
    $automatizacion = 0;

    $estados = [];


    if ($resultado) {

        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            if (
                !cumpleFiltros(
                    $fila,
                    $area,
                    $edificio,
                    $estado,
                    $desde,
                    $hasta
                )
            ) {

                continue;
            }


            $rows[] = $fila;

            $total++;


            $areaFila =
                strtolower(
                    trim(
                        (string)$fila['area']
                    )
                );


            if (
                $areaFila ===
                'audiovisuales'
            ) {

                $audiovisuales++;
            }


            elseif (
                $areaFila ===
                'automatizacion'
            ) {

                $automatizacion++;
            }


            $estadoFila =
                strtolower(
                    trim(
                        (string)$fila['estado']
                    )
                );


            if (
                $estadoFila === ''
            ) {

                $estadoFila =
                    'sin estado';
            }


            if (
                !isset(
                    $estados[$estadoFila]
                )
            ) {

                $estados[$estadoFila] = 0;
            }

            $estados[$estadoFila]++;
        }
    }


    /* =====================================================
       KPIs
    ===================================================== */

    $kpis = [

        [
            'label' => 'Ubicaciones',
            'valor' => $total
        ]

    ];


    if (
        $area === 'audiovisuales'
    ) {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];

    }

    else {

        $kpis[] = [
            'label' => 'Audiovisuales',
            'valor' => $audiovisuales
        ];

        $kpis[] = [
            'label' => 'Automatización',
            'valor' => $automatizacion
        ];
    }


    /* =====================================================
       GRÁFICA ESTADOS
    ===================================================== */

    $grafica1Titulo =
        'Ubicaciones por estado';

    $grafica1Labels = [];
    $grafica1Values = [];


    foreach (
        $estados as $nombreEstado => $cantidad
    ) {

        $grafica1Labels[] =
            ucfirst(
                $nombreEstado
            );

        $grafica1Values[] =
            $cantidad;
    }


    /* =====================================================
       GRÁFICA ÁREA
    ===================================================== */

    $grafica2Titulo =
        'Ubicaciones por área';


    if (
        $area === 'audiovisuales'
    ) {

        $grafica2Labels = [
            'Audiovisuales'
        ];

        $grafica2Values = [
            $audiovisuales
        ];

    }

    elseif (
        $area === 'automatizacion'
    ) {

        $grafica2Labels = [
            'Automatización'
        ];

        $grafica2Values = [
            $automatizacion
        ];

    }

    else {

        $grafica2Labels = [
            'Audiovisuales',
            'Automatización'
        ];

        $grafica2Values = [
            $audiovisuales,
            $automatizacion
        ];
    }
}


/* =========================================================
   TIPO DE REPORTE NO VÁLIDO
========================================================= */

else {

    die(
        'Tipo de reporte no válido.'
    );
}


/* =========================================================
   NORMALIZACIÓN FINAL DEL FILTRO POR ÁREA
========================================================= */

/*
 * Esta parte funciona como una segunda seguridad.
 *
 * Aunque cada consulta ya filtra las filas,
 * aquí volvemos a garantizar que si se seleccionó
 * un área, no quede ninguna fila de otra área.
 */

if (
    $area !== ''
) {

    $rowsFiltradas = [];


    foreach (
        $rows as $fila
    ) {

        if (
            !isset(
                $fila['area']
            )
        ) {

            continue;
        }


        $areaFila =
            strtolower(
                trim(
                    (string)$fila['area']
                )
            );


        if (
            $areaFila !==
            strtolower(
                trim(
                    $area
                )
            )
        ) {

            continue;
        }


        $rowsFiltradas[] =
            $fila;
    }


    $rows =
        $rowsFiltradas;
}


/* =========================================================
   TEXTO DEL FILTRO DE ÁREA
========================================================= */

$nombreAreaFiltro =
    'Todas las áreas';


if (
    $area === 'audiovisuales'
) {

    $nombreAreaFiltro =
        'Audiovisuales';

}

elseif (
    $area === 'automatizacion'
) {

    $nombreAreaFiltro =
        'Automatización';
}


/* =========================================================
   SUBTÍTULO
========================================================= */

$filtrosTexto = [];

$filtrosTexto[] =
    'Área: ' .
    $nombreAreaFiltro;


if ($edificio !== '') {

    $filtrosTexto[] =
        'Edificio: ' .
        $edificio;
}


if ($estado !== '') {

    $filtrosTexto[] =
        'Estado: ' .
        ucfirst(
            $estado
        );
}


if ($desde !== '') {

    $filtrosTexto[] =
        'Desde: ' .
        $desde;
}


if ($hasta !== '') {

    $filtrosTexto[] =
        'Hasta: ' .
        $hasta;
}


$subtituloReporte =
    implode(
        ' | ',
        $filtrosTexto
    );


/* =========================================================
   GRÁFICAS
========================================================= */

$grafica1HTML =
    generarGraficaHTML(
        $grafica1Titulo,
        $grafica1Labels,
        $grafica1Values
    );


$grafica2HTML =
    generarGraficaHTML(
        $grafica2Titulo,
        $grafica2Labels,
        $grafica2Values
    );


/* =========================================================
   HTML DE KPIs
========================================================= */

$kpisHTML = '';

$kpisHTML .=
    '<div class="kpis">';


foreach (
    $kpis as $kpi
) {

    $kpisHTML .=
        '<div class="kpi">';

    $kpisHTML .=
        '<div class="kpi-valor">'
        .
        number_format(
            (float)$kpi['valor'],
            0,
            ',',
            '.'
        )
        .
        '</div>';

    $kpisHTML .=
        '<div class="kpi-label">'
        .
        e(
            $kpi['label']
        )
        .
        '</div>';

    $kpisHTML .=
        '</div>';
}


$kpisHTML .=
    '</div>';


/* =========================================================
   TABLA
========================================================= */

$tablaHTML = '';


/* =========================================================
   EQUIPOS
========================================================= */

if (
    $tipo === 'equipos'
) {

    $tablaHTML .= '
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Equipo</th>
                    <th>Categoría</th>
                    <th>Área</th>
                    <th>Edificio</th>
                    <th>Piso</th>
                    <th>Salón</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serial</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
    ';


    foreach (
        $rows as $fila
    ) {

        $tablaHTML .= '<tr>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['codigo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['nombre']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['categoria']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['area']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['edificio']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['piso']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['salon']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['marca']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['modelo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['numero_serie']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['estado']
            ) .
            '</td>';

        $tablaHTML .= '</tr>';
    }


    if (
        count($rows) === 0
    ) {

        $tablaHTML .= '
            <tr>
                <td colspan="11"
                    class="sin-datos">
                    No hay equipos que coincidan
                    con los filtros seleccionados.
                </td>
            </tr>
        ';
    }


    $tablaHTML .= '
            </tbody>
        </table>
    ';
}


/* =========================================================
   MANTENIMIENTOS
========================================================= */

elseif (
    $tipo === 'mantenimientos'
) {

    $tablaHTML .= '
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Equipo</th>
                    <th>Área</th>
                    <th>Edificio</th>
                    <th>Diagnóstico</th>
                    <th>Técnico</th>
                    <th>Proveedor</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
    ';


    foreach (
        $rows as $fila
    ) {

        $tablaHTML .= '<tr>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['codigo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['equipo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['area']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['edificio']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['diagnostico']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['tecnico']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['proveedor']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['fecha_inicio']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['fecha_fin']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['estado']
            ) .
            '</td>';

        $tablaHTML .= '</tr>';
    }


    if (
        count($rows) === 0
    ) {

        $tablaHTML .= '
            <tr>
                <td colspan="10"
                    class="sin-datos">
                    No hay mantenimientos que coincidan
                    con los filtros seleccionados.
                </td>
            </tr>
        ';
    }


    $tablaHTML .= '
            </tbody>
        </table>
    ';
}


/* =========================================================
   MOVIMIENTOS
========================================================= */

elseif (
    $tipo === 'movimientos'
) {

    $tablaHTML .= '
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Área</th>
                    <th>Marca</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Usuario</th>
                    <th>Dependencia</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
    ';


    foreach (
        $rows as $fila
    ) {

        $tablaHTML .= '<tr>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['fecha']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['codigo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['producto']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['area']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['marca']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['tipo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['cantidad']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['usuario']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['dependencia']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['observacion']
            ) .
            '</td>';

        $tablaHTML .= '</tr>';
    }


    if (
        count($rows) === 0
    ) {

        $tablaHTML .= '
            <tr>
                <td colspan="10"
                    class="sin-datos">
                    No hay movimientos que coincidan
                    con los filtros seleccionados.
                </td>
            </tr>
        ';
    }


    $tablaHTML .= '
            </tbody>
        </table>
    ';
}


/* =========================================================
   INVENTARIO
========================================================= */

elseif (
    $tipo === 'inventario'
) {

    $tablaHTML .= '
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Área</th>
                    <th>Marca</th>
                    <th>Referencia</th>
                    <th>Cantidad</th>
                    <th>Mínimo</th>
                    <th>Estado</th>
                    <th>Ubicación</th>
                </tr>
            </thead>
            <tbody>
    ';


    foreach (
        $rows as $fila
    ) {

        $tablaHTML .= '<tr>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['codigo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['nombre']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['categoria']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['area']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['marca']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['referencia']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['cantidad']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['stock_minimo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            ucfirst(
                valorPDF(
                    $fila['estado']
                )
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['ubicacion']
            ) .
            '</td>';

        $tablaHTML .= '</tr>';
    }


    if (
        count($rows) === 0
    ) {

        $tablaHTML .= '
            <tr>
                <td colspan="10"
                    class="sin-datos">
                    No hay productos que coincidan
                    con los filtros seleccionados.
                </td>
            </tr>
        ';
    }


    $tablaHTML .= '
            </tbody>
        </table>
    ';
}


/* =========================================================
   PUNTOS DE RED
========================================================= */

elseif (
    $tipo === 'puntos_red'
) {

    $tablaHTML .= '
        <table>
            <thead>
                <tr>
                    <th>Área</th>
                    <th>Edificio</th>
                    <th>Ubicación</th>
                    <th>Equipo</th>
                    <th>Marca / Sistema</th>
                    <th>MAC</th>
                    <th>IP</th>
                    <th>Switch / Rack</th>
                    <th>Puerto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
    ';


    foreach (
        $rows as $fila
    ) {

        $tablaHTML .= '<tr>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['area']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['edificio']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['ubicacion_reportada']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['equipo']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['marca']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['mac']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['ip']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['switch']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['puerto']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['estado']
            ) .
            '</td>';

        $tablaHTML .= '</tr>';
    }


    if (
        count($rows) === 0
    ) {

        $tablaHTML .= '
            <tr>
                <td colspan="10"
                    class="sin-datos">
                    No hay puntos de red que coincidan
                    con los filtros seleccionados.
                </td>
            </tr>
        ';
    }


    $tablaHTML .= '
            </tbody>
        </table>
    ';
}


/* =========================================================
   UBICACIONES
========================================================= */

elseif (
    $tipo === 'ubicaciones'
) {

    $tablaHTML .= '
        <table>
            <thead>
                <tr>
                    <th>Edificio</th>
                    <th>Piso</th>
                    <th>Salón</th>
                    <th>Área</th>
                    <th>Estado</th>
                    <th>Fecha registro</th>
                </tr>
            </thead>
            <tbody>
    ';


    foreach (
        $rows as $fila
    ) {

        $tablaHTML .= '<tr>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['edificio']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['piso']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['salon']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['area']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['estado']
            ) .
            '</td>';

        $tablaHTML .=
            '<td>' .
            valorPDF(
                $fila['fecha_registro']
            ) .
            '</td>';

        $tablaHTML .= '</tr>';
    }


    if (
        count($rows) === 0
    ) {

        $tablaHTML .= '
            <tr>
                <td colspan="6"
                    class="sin-datos">
                    No hay ubicaciones que coincidan
                    con los filtros seleccionados.
                </td>
            </tr>
        ';
    }


    $tablaHTML .= '
            </tbody>
        </table>
    ';
}


/* =========================================================
   FECHA DEL REPORTE
========================================================= */

$fechaGeneracion =
    date(
        'd/m/Y H:i:s'
    );


/* =========================================================
   HTML COMPLETO
========================================================= */

$html = '';

$html .= '
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 30px 25px 30px 25px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    color: #222;
    margin: 0;
    padding: 0;
}

.header {
    width: 100%;
    border-bottom: 2px solid #222;
    padding-bottom: 10px;
    margin-bottom: 12px;
}

.header-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 4px;
}

.header-subtitle {
    font-size: 9px;
    color: #555;
}

.header-date {
    font-size: 8px;
    color: #777;
    margin-top: 4px;
}

.kpis {
    width: 100%;
    margin-bottom: 15px;
    text-align: center;
}

.kpi {
    display: inline-block;
    width: 17.5%;
    min-height: 55px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-right: 1%;
    padding: 7px;
    box-sizing: border-box;
    vertical-align: top;
}

.kpi:last-child {
    margin-right: 0;
}

.kpi-valor {
    font-size: 17px;
    font-weight: bold;
}

.kpi-label {
    font-size: 8px;
    color: #666;
    margin-top: 5px;
}

.graficas {
    width: 100%;
    margin-bottom: 15px;
}

.grafica {
    width: 47%;
    display: inline-block;
    vertical-align: top;
    margin-right: 2%;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px;
    box-sizing: border-box;
}

.grafica:last-child {
    margin-right: 0;
}

.grafica-titulo {
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 10px;
}

.grafica-fila {
    width: 100%;
    margin-bottom: 7px;
    min-height: 14px;
}

.grafica-label {
    width: 24%;
    display: inline-block;
    font-size: 7px;
    vertical-align: middle;
    word-wrap: break-word;
}

.grafica-barra-contenedor {
    width: 62%;
    display: inline-block;
    height: 10px;
    background: #eeeeee;
    vertical-align: middle;
    border-radius: 4px;
    overflow: hidden;
}

.grafica-barra {
    height: 10px;
    background: #555555;
    border-radius: 4px;
}

.grafica-valor {
    width: 10%;
    display: inline-block;
    text-align: right;
    font-size: 7px;
    vertical-align: middle;
}

.sin-datos-grafica {
    font-size: 8px;
    color: #777;
    padding: 10px 0;
}

.seccion-titulo {
    font-size: 12px;
    font-weight: bold;
    margin-top: 8px;
    margin-bottom: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
}

thead {
    display: table-header-group;
}

tr {
    page-break-inside: avoid;
}

th {
    background: #eeeeee;
    border: 1px solid #bbbbbb;
    padding: 5px 4px;
    font-size: 7px;
    font-weight: bold;
    text-align: left;
}

td {
    border: 1px solid #dddddd;
    padding: 4px;
    font-size: 7px;
    vertical-align: top;
    word-wrap: break-word;
}

tbody tr:nth-child(even) {
    background: #f8f8f8;
}

.sin-datos {
    text-align: center;
    padding: 15px;
    color: #777;
}

.footer {
    margin-top: 15px;
    border-top: 1px solid #ddd;
    padding-top: 6px;
    font-size: 7px;
    color: #777;
    text-align: center;
}

.area-seleccionada {
    font-weight: bold;
}

</style>

</head>

<body>
';


/* =========================================================
   ENCABEZADO
========================================================= */

$html .= '
<div class="header">

    <div class="header-title">
        ' . e($tituloReporte) . '
    </div>

    <div class="header-subtitle">
        ' . e($subtituloReporte) . '
    </div>

    <div class="header-date">
        Generado el ' . e($fechaGeneracion) . '
    </div>

</div>
';


/* =========================================================
   KPIs
========================================================= */

$html .= $kpisHTML;


/* =========================================================
   GRÁFICAS
========================================================= */

$html .= '
<div class="graficas">
';

$html .= $grafica1HTML;

$html .= $grafica2HTML;

$html .= '
</div>
';


/* =========================================================
   TABLA
========================================================= */

$html .= '
<div class="seccion-titulo">
    Detalle del reporte
</div>
';


$html .= $tablaHTML;


/* =========================================================
   PIE
========================================================= */

$html .= '
<div class="footer">

    JaverStock - Sistema de Gestión de Inventario

    <br>

    Área seleccionada:
    <span class="area-seleccionada">
        ' .
        e(
            $nombreAreaFiltro
        )
        .
    '</span>

</div>
';


$html .= '
</body>
</html>
';


/* =========================================================
   GENERAR PDF
========================================================= */

$dompdf->loadHtml(
    $html,
    'UTF-8'
);

$dompdf->setPaper(
    'A4',
    'landscape'
);

$dompdf->render();


/* =========================================================
   DESCARGAR
========================================================= */

$nombreArchivo =
    'reporte_' .
    $tipo .
    '_' .
    date('Y-m-d_H-i-s') .
    '.pdf';


$dompdf->stream(
    $nombreArchivo,
    [
        'Attachment' => true
    ]
);

exit;