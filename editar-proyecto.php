<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: proyectos.php");
    exit();
}

$id = intval($_GET["id"]);

/* Obtener proyecto */
$sql = "SELECT * FROM proyectos WHERE id_proyecto = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$proyecto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$proyecto) {
    die("Proyecto no encontrado.");
}

/* Obtener jefes */
$sql = "SELECT
            p.dni,
            p.nombre,
            p.apellido,
            r.departamento

        FROM personal p

        INNER JOIN roles r
            ON p.dni = r.dni

        WHERE r.cargo='Jefe'

        ORDER BY r.departamento,p.apellido";

$jefes = mysqli_query($conexion, $sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Editar Proyecto</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="container mt-5">

<div class="card shadow">   

<div class="card-header bg-warning">

<h3>Editar Proyecto</h3>

</div>

<div class="card-body">

<form
action="actualizar-proyecto.php"
method="POST"
enctype="multipart/form-data">

<input
type="hidden"
name="id"
value="<?= $proyecto["id_proyecto"] ?>">

<div class="mb-3">

<label>Título</label>

<input
type="text"
name="titulo"
class="form-control"
required
value="<?= htmlspecialchars($proyecto["titulo"]) ?>">

</div>

<div class="mb-3">

<label>Descripción</label>

<textarea
name="descripcion"
rows="6"
class="form-control"
required><?= htmlspecialchars($proyecto["descripcion"]) ?></textarea>

</div>

<div class="mb-3">

<label>Jefe</label>

<select
name="jefe"
class="form-select">

<?php while($fila=mysqli_fetch_assoc($jefes)){ ?>

<option
value="<?= $fila["dni"] ?>"

<?= ($fila["dni"]==$proyecto["jefe_dni"]) ? "selected" : "" ?>

>

<?= $fila["departamento"] ?>

-

<?= $fila["apellido"] ?>

<?= $fila["nombre"] ?>

</option>

<?php } ?>

</select>

</div>

<?php if($proyecto["archivo"]!=""){ ?>

<div class="alert alert-info">

Archivo actual:

<b><?= $proyecto["archivo"] ?></b>

</div>

<?php } ?>

<div class="mb-3">

<label>Reemplazar archivo</label>

<input
type="file"
name="archivo"
class="form-control">

</div>

<div class="d-flex justify-content-between">

<a
href="proyectos.php"
class="btn btn-secondary">

Cancelar

</a>

<button
class="btn btn-warning">

Actualizar

</button>

</div>

</form>

</div>

</div>

</div>

</body>

</html>