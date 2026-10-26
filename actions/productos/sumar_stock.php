<?php

include("../../includes/conexion.php");

$id = $_GET['id'];

$sql = mysqli_query($conn,"
SELECT cantidad, stock_minimo
FROM productos
WHERE id='$id'
");

$p = mysqli_fetch_assoc($sql);

$nuevaCantidad = $p['cantidad'] + 1;

if($nuevaCantidad <= 0){

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
'Entrada',
1,
'Administrador',
'Inventario',
'Aumento de stock'
)
");

header("Location: ../../pages/inventario.php");
exit();

?>