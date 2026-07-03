<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: login.php");
    exit();
}

// Obtener solamente los jefes
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

<title>Nuevo Proyecto</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-success text-white">

<h3>Nuevo Proyecto</h3>

</div>

<div class="card-body">

<form
action="guardar-proyecto.php"
method="POST"
enctype="multipart/form-data">

<div class="mb-3">

<label class="form-label">

Título

</label>

<input
type="text"
name="titulo"
class="form-control"
maxlength="150"
required>

</div>

<div class="mb-3">

<label class="form-label">

Descripción

</label>

<textarea
name="descripcion"
rows="6"
class="form-control"
required></textarea>

</div>

<div class="mb-3">

<label class="form-label">

Jefe de Departamento

</label>

<select
name="jefe"
class="form-select"
required>

<option value="">Seleccione...</option>

<?php while($fila=mysqli_fetch_assoc($jefes)){ ?>

<option value="<?= $fila["dni"] ?>">

<?= $fila["departamento"] ?>

-

<?= $fila["apellido"] ?>

<?= $fila["nombre"] ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Adjuntar archivo

</label>

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
class="btn btn-success">

Guardar Proyecto

</button>

</div>

</form>

</div>

</div>

</div>

</body>

</html>