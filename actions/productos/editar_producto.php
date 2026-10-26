<?php
include("../../includes/conexion.php");

$id = $_POST['id'];
$codigo = $_POST['codigo'];
$nombre = $_POST['nombre'];
$categoria = $_POST['categoria'];
$marca = $_POST['marca'];
$referencia = $_POST['referencia'];
$cantidad = $_POST['cantidad'];
$stock_minimo = $_POST['stock_minimo'];
$ubicacion = $_POST['ubicacion'];

if($cantidad <= 0){
    $estado="Agotado";
}elseif($cantidad <= $stock_minimo){
    $estado="Bajo Stock";
}else{
    $estado="Disponible";
}

$sql="UPDATE productos SET

codigo='$codigo',
nombre='$nombre',
categoria='$categoria',
marca='$marca',
referencia='$referencia',
cantidad='$cantidad',
stock_minimo='$stock_minimo',
ubicacion='$ubicacion',
estado='$estado'

WHERE id='$id'";

mysqli_query($conn,$sql);

header("Location: ../../pages/inventario.php");
exit;
?>