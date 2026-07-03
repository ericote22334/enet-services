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

// Obtener archivo asociado
$sql = "SELECT archivo FROM proyectos WHERE id_proyecto = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$proyecto = mysqli_fetch_assoc($resultado);

if (!$proyecto) {
    die("Proyecto no encontrado.");
}

// Eliminar archivo físico
if (!empty($proyecto["archivo"])) {
    $ruta = "uploads/" . $proyecto["archivo"];

    if (file_exists($ruta)) {
        unlink($ruta);
    }
}

// Registrar historial
$sql = "INSERT INTO historial_proyectos
(proyecto,usuario,accion,comentario)
VALUES(?,?,?,?)";

$stmt = mysqli_prepare($conexion, $sql);

$accion = "Eliminación";
$comentario = "Proyecto eliminado";

mysqli_stmt_bind_param(
    $stmt,
    "iiss",
    $id,
    $_SESSION["dni"],
    $accion,
    $comentario
);

mysqli_stmt_execute($stmt);

// Eliminar historial del proyecto
$sql = "DELETE FROM historial_proyectos WHERE proyecto = ?";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

// Eliminar proyecto
$sql = "DELETE FROM proyectos WHERE id_proyecto = ?";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);

if(mysqli_stmt_execute($stmt)){
    header("Location: panel.php?eliminado=1");
    exit();
}else{
    die(mysqli_error($conexion));
}
header("Location: proyectos.php?eliminado=1");
exit();
?>