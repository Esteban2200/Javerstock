<?php

include("../includes/conexion.php");

$id = intval($_POST['producto']);
$cantidad = intval($_POST['cantidad']);
$usuario = $_POST['usuario'];
$dependencia = $_POST['dependencia'];
$observacion = $_POST['observacion'];

// Consultar stock actual
$sql = mysqli_query($conn,"SELECT cantidad, stock_minimo FROM productos WHERE id=$id");
$producto = mysqli_fetch_assoc($sql);

if($cantidad > $producto['cantidad']){
    die("No hay suficiente stock disponible.");
}

// Calcular nuevo stock
$nuevoStock = $producto['cantidad'] - $cantidad;

// Determinar estado
if($nuevoStock <= 0){
    $estado = "Agotado";
}elseif($nuevoStock <= $producto['stock_minimo']){
    $estado = "Bajo Stock";
}else{
    $estado = "Disponible";
}

// Actualizar producto
mysqli_query($conn,"
UPDATE productos
SET cantidad = $nuevoStock,
    estado = '$estado'
WHERE id=$id
");

// Registrar movimiento
mysqli_query($conn,"
INSERT INTO movimientos
(producto_id,tipo,cantidad,usuario,dependencia,observacion)

VALUES

($id,'Salida',$cantidad,'$usuario','$dependencia','$observacion')
");

header("Location: ../pages/inventario.php");

?>