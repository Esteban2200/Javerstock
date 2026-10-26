<?php
include("../includes/auth.php");
include("../includes/conexion.php");

$sql = mysqli_query($conn,"

SELECT
m.*,
p.nombre

FROM movimientos m

INNER JOIN productos p
ON p.id = m.producto_id

ORDER BY m.fecha DESC

");

include("../includes/sidebar.php");

?>

<div class="main">

<?php include("../includes/header.php"); ?>

<style>
/* ===== MOVIMIENTOS: ESTILOS LOCALES ===== */
.movimientos-page{
    width:calc(100% - 40px) !important;
    max-width:1250px !important;
    margin:35px auto 40px !important;
    padding:0 !important;
}
.movimientos-page .movimientos-header{
    width:100% !important;
    min-height:80px !important;
    display:flex !important;
    flex-direction:row !important;
    justify-content:space-between !important;
    align-items:center !important;
    margin:0 0 22px !important;
    padding:0 !important;
    gap:20px !important;
    background:transparent !important;
}
.movimientos-page .movimientos-titulo{margin:0 !important; flex:1 !important;}
.movimientos-page .movimientos-titulo h2{
    margin:0 !important;
    color:#002855 !important;
    font-size:32px !important;
    line-height:1.2 !important;
    font-weight:700 !important;
}
.movimientos-page .movimientos-titulo p{
    margin:7px 0 0 !important;
    color:#718096 !important;
    font-size:15px !important;
}
.movimientos-page .movimientos-boton{
    display:flex !important;
    flex:0 0 auto !important;
    justify-content:flex-end !important;
    align-items:center !important;
}
.movimientos-page .movimientos-boton .btn{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    width:auto !important;
    min-width:190px !important;
    height:44px !important;
    padding:0 18px !important;
    margin:0 !important;
    border:0 !important;
    border-radius:10px !important;
    background:#002855 !important;
    color:#fff !important;
    font-weight:700 !important;
    white-space:nowrap !important;
}
.movimientos-page .movimientos-table-box{
    width:100% !important;
    background:#fff !important;
    border-radius:18px !important;
    overflow:hidden !important;
    box-shadow:0 8px 25px rgba(0,40,85,.08) !important;
    padding:0 !important;
}
.movimientos-page #tablaMovimientos{
    width:100% !important;
    margin:0 !important;
    border-collapse:separate !important;
    border-spacing:0 !important;
    background:#fff !important;
}
.movimientos-page #tablaMovimientos thead th{
    background:#002855 !important;
    color:#fff !important;
    border:0 !important;
    padding:13px 12px !important;
    font-size:12px !important;
    font-weight:700 !important;
    white-space:nowrap !important;
}
.movimientos-page #tablaMovimientos thead th:first-child{border-top-left-radius:18px !important;}
.movimientos-page #tablaMovimientos thead th:last-child{border-top-right-radius:18px !important;}
.movimientos-page #tablaMovimientos tbody td{
    padding:11px 12px !important;
    border-bottom:1px solid #e5e9ef !important;
    font-size:13px !important;
    vertical-align:middle !important;
}
.movimientos-page #tablaMovimientos tbody tr:last-child td{border-bottom:0 !important;}
.movimientos-page #tablaMovimientos tbody tr:hover td{background:#f8fbff !important;}
.movimientos-page #tablaMovimientos .badge{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    min-width:64px !important;
    padding:4px 8px !important;
    border-radius:14px !important;
    font-size:11px !important;
    font-weight:700 !important;
}
.movimientos-page .dataTables_wrapper{width:100% !important;}
.movimientos-page .dataTables_filter{padding:12px 16px 14px !important; text-align:left !important; margin-bottom:8px !important;}
.movimientos-page .dataTables_filter input{
    height:34px !important;
    width:200px !important;
    margin-left:5px !important;
    padding:5px 9px !important;
    border:1px solid #ccd5df !important;
    border-radius:7px !important;
}
.movimientos-page .dataTables_info{padding:9px 16px !important; font-size:12px !important;}
.movimientos-page .dataTables_paginate{padding:7px 16px 14px !important;}
.movimientos-page .dataTables_paginate .paginate_button{
    padding:4px 8px !important;
    margin:0 2px !important;
    border:1px solid #d5dce5 !important;
    border-radius:6px !important;
    background:#fff !important;
}
@media(max-width:768px){
    .movimientos-page{width:calc(100% - 20px) !important; margin:20px auto !important;}
    .movimientos-page .movimientos-header{flex-direction:column !important; align-items:flex-start !important;}
    .movimientos-page .movimientos-boton{width:100% !important; justify-content:flex-start !important;}
    .movimientos-page .movimientos-boton .btn{width:auto !important;}
    .movimientos-page .movimientos-table-box{overflow-x:auto !important;}
    .movimientos-page #tablaMovimientos{min-width:900px !important;}
}
</style>


<div class="content movimientos-page">

    <!-- Encabezado -->

    <div class="movimientos-header">

        <div class="movimientos-titulo">

            <h2>
                <i class="fas fa-history"></i>
                Historial de Movimientos
            </h2>

            <p>Entradas y salidas realizadas en el inventario.</p>

        </div>

        <div class="movimientos-boton">

            <a href="inventario.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Volver al Inventario
            </a>

        </div>

    </div>

    <!-- Tabla -->

    <div class="movimientos-table-box">

        <table
        id="tablaMovimientos"
        class="display">

            <thead>

                <tr>

                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Movimiento</th>
                    <th>Cantidad</th>
                    <th>Usuario</th>
                    <th>Dependencia</th>
                    <th>Observación</th>

                </tr>

            </thead>

            <tbody>

            <?php while($m = mysqli_fetch_assoc($sql)){ ?>

                <tr>

                    <td><?= $m['fecha']; ?></td>

                    <td><?= $m['nombre']; ?></td>

                    <td>

                    <?php

                    if($m['tipo']=="Entrada"){

                        echo "<span class='badge bg-success'>Entrada</span>";

                    }else{

                        echo "<span class='badge bg-danger'>Salida</span>";

                    }

                    ?>

                    </td>

                    <td>

                        <?= $m['cantidad']; ?>

                    </td>

                    <td>

                        <?= $m['usuario']; ?>

                    </td>

                    <td>

                        <?= $m['dependencia']; ?>

                    </td>

                    <td>

                        <?= $m['observacion']; ?>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</div>

<?php include("../includes/footer.php");?>