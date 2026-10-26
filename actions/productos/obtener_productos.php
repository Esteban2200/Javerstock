<?php
include("../../includes/conexion.php");

$id = intval($_GET['id']);

$sql = mysqli_query($conn, "SELECT * FROM productos WHERE id = $id");

echo json_encode(mysqli_fetch_assoc($sql));
?>