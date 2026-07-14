<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: login.php");
    exit();
}

$id = intval($_GET["id"]);

/* Guardar cambios */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $jefe = intval($_POST["jefe"]);

    $sql = "UPDATE proyectos
            SET jefe_dni=?,
                estado='Delegado'
            WHERE id_proyecto=?";

    $stmt = mysqli_prepare($conexion,$sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $jefe,
        $id
    );

    mysqli_stmt_execute($stmt);

    // Historial
    $accion="Delegación";
    $comentario="Proyecto delegado";

    $sql="INSERT INTO historial_proyectos
    (proyecto,usuario,accion,comentario)
    VALUES(?,?,?,?)";

    $stmt=mysqli_prepare($conexion,$sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iiss",
        $id,
        $_SESSION["dni"],
        $accion,
        $comentario
    );

    mysqli_stmt_execute($stmt);

    header("Location: proyectos.php");
    exit();

}

/* Obtener jefes */

$sql="SELECT

p.dni,
p.nombre,
p.apellido,
r.departamento

FROM personal p

INNER JOIN roles r
ON p.dni=r.dni

WHERE r.cargo='Jefe'

ORDER BY r.departamento";

$jefes=mysqli_query($conexion,$sql);

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Delegar Proyecto</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="estilo.css">

</head>

<body>

<div class="container mt-5">

<div class="card">

<div class="card-header bg-info text-white">

<h3>Delegar Proyecto</h3>

</div>

<div class="card-body">

<form method="POST">

<label>

Seleccione el jefe

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

<br>

<button class="btn btn-success">

Delegar

</button>

<a href="proyectos.php"
class="btn btn-secondary">

Cancelar

</a>

</form>

</div>

</div>

</div>

</body>

</html>