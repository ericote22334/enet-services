<?php
session_start();
include("conexion.php");

$usuario = $_POST['usuario'];
$password = $_POST['password'];
$rol = $_POST['rol'];

$sql = "SELECT * FROM usuarios
        WHERE usuario='$usuario'
        AND password='$password'
        AND rol='$rol'";

$resultado = mysqli_query($conexion, $sql);

if(mysqli_num_rows($resultado) > 0){

    $_SESSION['usuario'] = $usuario;
    $_SESSION['rol'] = $rol;

    // Según el rol, va a un panel distinto
    if($rol == "Direccion"){
        header("Location: direccion/dashboard.php");
    }else if($rol == "Jefe de Departamento"){
        header("Location: panel-departamento.php");
    }

    exit();

}else{
    header("Location: index.php?error=1");
    exit();
}
?>

