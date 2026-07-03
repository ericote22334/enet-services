<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: index.php");
    exit();
}

if (
    $_SESSION["cargo"] != "Director" &&
    $_SESSION["cargo"] != "Subdirector"
) {
    header("Location: logout.php");
    exit();
}

/* ===========================
   ESTADÍSTICAS
=========================== */

$total = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos"));

$pendientes = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Pendiente'"));

$delegados = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Delegado'"));

$revision = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='En Revision'"));

$devueltos = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Devuelto'"));

$finalizados = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Finalizado'"));

/* ===========================
   BUSCADOR
=========================== */

$buscar = "";

if(isset($_GET["buscar"])){
    $buscar = mysqli_real_escape_string($conexion,$_GET["buscar"]);
}

$sql = "SELECT

p.*,

d.nombre AS director_nombre,
d.apellido AS director_apellido,

j.nombre AS jefe_nombre,
j.apellido AS jefe_apellido

FROM proyectos p

INNER JOIN personal d
ON p.director_dni=d.dni

LEFT JOIN personal j
ON p.jefe_dni=j.dni

WHERE p.titulo LIKE '%$buscar%'

ORDER BY p.fecha_creacion DESC";

$proyectos = mysqli_query($conexion,$sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Panel Director</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="estilo.css">

</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">

<div class="container-fluid">

<span class="navbar-brand">

ENET SERVICES

</span>

<div class="text-white">

<?= htmlspecialchars($_SESSION["nombre"]." ".$_SESSION["apellido"]) ?>

|

<?= $_SESSION["cargo"] ?>

<a href="logout.php" class="btn btn-danger btn-sm ms-3">

Cerrar sesión

</a>

</div>

</div>

</nav>

<div class="container mt-4">

<h2 class="mb-4">

Panel de Dirección

</h2>

<!-- TARJETAS -->

<!-- TARJETAS -->

<div class="row g-3 mb-4">

    <div class="col-md-2">
        <div class="card shadow text-center">
            <div class="card-body">
                <h6 class="text-muted">Total de proyectos</h6>
                <h2><?= $total["total"] ?></h2>
                <small class="text-secondary">Proyectos registrados</small>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow border-warning text-center">
            <div class="card-body">
                <h6 class="text-warning">Pendientes</h6>
                <h2><?= $pendientes["total"] ?></h2>
                <small class="text-secondary">Aún sin asignar</small>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow border-primary text-center">
            <div class="card-body">
                <h6 class="text-primary">Delegados</h6>
                <h2><?= $delegados["total"] ?></h2>
                <small class="text-secondary">En poder de un jefe</small>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow border-info text-center">
            <div class="card-body">
                <h6 class="text-info">En revisión</h6>
                <h2><?= $revision["total"] ?></h2>
                <small class="text-secondary">Esperando revisión</small>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow border-danger text-center">
            <div class="card-body">
                <h6 class="text-danger">Devueltos</h6>
                <h2><?= $devueltos["total"] ?></h2>
                <small class="text-secondary">Requieren cambios</small>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow border-success text-center">
            <div class="card-body">
                <h6 class="text-success">Finalizados</h6>
                <h2><?= $finalizados["total"] ?></h2>
                <small class="text-secondary">Proyectos completados</small>
            </div>
        </div>
    </div>

</div>
<!-- BUSCADOR -->

<div class="row mb-4">

<div class="col-md-8">

<form method="GET">

<div class="input-group">

<input
type="text"
class="form-control"
name="buscar"
placeholder="Buscar proyecto..."
value="<?= htmlspecialchars($buscar) ?>">

<button class="btn btn-primary">

Buscar

</button>

</div>

</form>

</div>

<div class="col-md-4 text-end">

<a href="nuevo-proyecto.php" class="btn btn-success">

+ Nuevo Proyecto

</a>

</div>

</div>

<!-- TABLA -->

<div class="card shadow">

<div class="card-header bg-dark text-white">

Proyectos

</div>

<div class="card-body p-0">

<div class="table-responsive">

<table class="table table-hover table-bordered m-0">

<thead class="table-secondary">

<tr>

<th>ID</th>

<th>Título</th>

<th>Director</th>

<th>Jefe</th>

<th>Estado</th>

<th>Fecha</th>

<th width="320">Acciones</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($proyectos)>0){

while($fila=mysqli_fetch_assoc($proyectos)){

?>

<tr>

<td><?= $fila["id_proyecto"] ?></td>

<td><?= htmlspecialchars($fila["titulo"]) ?></td>

<td>

<?= $fila["director_apellido"].", ".$fila["director_nombre"] ?>

</td>

<td>

<?php

if($fila["jefe_nombre"]){

echo $fila["jefe_apellido"].", ".$fila["jefe_nombre"];

}else{

echo "<span class='text-muted'>Sin asignar</span>";

}

?>

</td>

<td>

<?php

switch($fila["estado"]){

case "Pendiente":

echo "<span class='badge bg-warning text-dark'>Pendiente</span>";

break;

case "Delegado":

echo "<span class='badge bg-primary'>Delegado</span>";

break;

case "En Revision":

echo "<span class='badge bg-info text-dark'>En revisión</span>";

break;

case "Devuelto":

echo "<span class='badge bg-danger'>Devuelto</span>";

break;

case "Finalizado":

echo "<span class='badge bg-success'>Finalizado</span>";

break;

}

?>

</td>

<td>

<?= date("d/m/Y",strtotime($fila["fecha_creacion"])) ?>

</td>

<td>

<a
href="detalle-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
class="btn btn-primary btn-sm">

Ver

</a>

<a
href="editar-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
class="btn btn-warning btn-sm">

Editar

</a>

<a
href="delegar-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
class="btn btn-info btn-sm">

Delegar

</a>

<a
href="eliminar-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Está seguro de eliminar este proyecto?')">

Eliminar

</a>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="7" class="text-center">

No hay proyectos registrados.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>



</body>

</html>