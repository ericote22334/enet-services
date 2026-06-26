<?php
session_start();
include("conexion.php");

$usuario = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? '';

$sql = "SELECT * FROM usuarios
        WHERE usuario='$usuario'
        AND password='$password'";

$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0) {

    $fila = mysqli_fetch_assoc($resultado);

    if (strcasecmp($fila['rol'], $rol) !== 0) {
        header("Location: index.php?error=rol");
        exit();
    }

    $_SESSION['usuario'] = $fila['usuario'];
    $_SESSION['rol'] = $fila['rol'];

    header("Location: panel.php");
    exit();

}

header("Location: index.php?error=credenciales");
exit();
?>
