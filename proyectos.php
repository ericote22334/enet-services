<?php
session_start();
include("conexion.php");

// Si no inició sesión
if (!isset($_SESSION['dni'])) {
    header("Location: login.php");
    exit();
}

// Buscador
$buscar = "";

if (isset($_GET['buscar'])) {
    $buscar = mysqli_real_escape_string($conexion, $_GET['buscar']);
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

$resultado = mysqli_query($conexion,$sql);

?>
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Proyectos</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>Gestión de Proyectos</h2>

<div>

<a href="panel.php" class="btn btn-secondary">
Volver
</a>

<a href="nuevo-proyecto.php" class="btn btn-success">
Nuevo Proyecto
</a>

</div>

</div>

<form method="GET" class="mb-4">

<div class="row">

<div class="col-md-10">

<input
type="text"
name="buscar"
class="form-control"
placeholder="Buscar proyecto..."
value="<?= htmlspecialchars($buscar) ?>">

</div>

<div class="col-md-2 d-grid">

<button class="btn btn-primary">

Buscar

</button>

</div>

</div>

</form>

<div class="table-responsive">

<table class="table table-hover table-bordered align-middle">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Título</th>

<th>Director</th>

<th>Jefe</th>

<th>Estado</th>

<th>Fecha</th>

<th width="280">Acciones</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($resultado)>0){

while($fila=mysqli_fetch_assoc($resultado)){

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

default:

echo $fila["estado"];

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
onclick="return confirm('¿Seguro que desea eliminar el proyecto?')">

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

No existen proyectos registrados.

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>

</body>

</html>