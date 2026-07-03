<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: login.php");
    exit();
}

$id = intval($_GET["id"]);

$sql = "SELECT

p.*,

d.nombre director_nombre,
d.apellido director_apellido,

j.nombre jefe_nombre,
j.apellido jefe_apellido

FROM proyectos p

INNER JOIN personal d
ON p.director_dni=d.dni

LEFT JOIN personal j
ON p.jefe_dni=j.dni

WHERE p.id_proyecto=?";

$stmt = mysqli_prepare($conexion,$sql);

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);

$proyecto = mysqli_fetch_assoc($res);

if(!$proyecto){

die("Proyecto inexistente.");

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Detalle del Proyecto</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<div class="container mt-5">

<div class="card">

<div class="card-header bg-primary text-white">

<h3><?= htmlspecialchars($proyecto["titulo"]) ?></h3>

</div>

<div class="card-body">

<p>

<strong>Descripción</strong>

</p>

<p>

<?= nl2br(htmlspecialchars($proyecto["descripcion"])) ?>

</p>

<hr>

<p>

<strong>Director:</strong>

<?= $proyecto["director_apellido"] ?>

<?= $proyecto["director_nombre"] ?>

</p>

<p>

<strong>Jefe:</strong>

<?php

if($proyecto["jefe_nombre"]){

echo $proyecto["jefe_apellido"]." ".$proyecto["jefe_nombre"];

}else{

echo "Sin asignar";

}

?>

</p>

<p>

<strong>Estado:</strong>

<?= $proyecto["estado"] ?>

</p>

<p>

<strong>Fecha:</strong>

<?= $proyecto["fecha_creacion"] ?>

</p>

<?php

if($proyecto["archivo"]!=""){

?>

<p>

<strong>Archivo:</strong>

<a

href="uploads/<?= $proyecto["archivo"] ?>"

target="_blank">

Descargar

</a>

</p>

<?php

}

?>

</div>

<div class="card-footer">

<a href="proyectos.php" class="btn btn-secondary">

Volver

</a>

</div>

</div>

</div>

</body>

</html>