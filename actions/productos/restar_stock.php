<?php

include("../../includes/conexion.php");

$id = $_GET['id'];

$sql = mysqli_query($conn,"
SELECT cantidad, stock_minimo
FROM productos
WHERE id='$id'
");

$p = mysqli_fetch_assoc($sql);

$nuevaCantidad = $p['cantidad'] - 1;

// Evitar cantidades negativas
if($nuevaCantidad < 0){
    $nuevaCantidad = 0;
}

// Calcular estado
if($nuevaCantidad == 0){

    $estado = "Agotado";

}elseif($nuevaCantidad <= $p['stock_minimo']){

    $estado = "Bajo Stock";

}else{

    $estado = "Disponible";

}

// Actualizar producto
mysqli_query($conn,"
UPDATE productos
SET
cantidad='$nuevaCantidad',
estado='$estado'
WHERE id='$id'
");

// Registrar movimiento
mysqli_query($conn,"
INSERT INTO movimientos
(
producto_id,
tipo,
cantidad,
usuario,
dependencia,
observacion
)
VALUES
(
'$id',
'Salida',
1,
'Administrador',
'Inventario',
'Disminución de stock'
)
");

header("Location: ../../pages/inventario.php");
exit();

?>