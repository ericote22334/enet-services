<?php
session_start();
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$dni = trim($_POST["dni"]);
$password = trim($_POST["password"]);
$rolSeleccionado = $_POST["rol"];

// Buscar usuario
$sql = "SELECT
            p.dni,
            p.nombre,
            p.apellido,
            p.pass,
            r.cargo,
            r.departamento
        FROM personal p
        INNER JOIN roles r
            ON p.dni = r.dni
        WHERE p.dni = ?";

$stmt = mysqli_prepare($conexion, $sql);

mysqli_stmt_bind_param($stmt, "i", $dni);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($resultado) == 1){

    $usuario = mysqli_fetch_assoc($resultado);

    /*
      Si las contraseñas están en texto plano
      (después podemos cambiar a password_hash)
    */

    if($password == $usuario["pass"]){

$acceso = false;

if ($rolSeleccionado == "Director") {

    if (
        $usuario["cargo"] == "Director" ||
        $usuario["cargo"] == "Subdirector"
    ) {

        $acceso = true;
        $destino = "panel.php";

    }

}

if ($rolSeleccionado == "Jefe") {

    if ($usuario["cargo"] == "Jefe") {

        $acceso = true;
        $destino = "panel-departamento.php";

    }

}

        if($acceso){

            $_SESSION["dni"] = $usuario["dni"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["apellido"] = $usuario["apellido"];
            $_SESSION["cargo"] = $usuario["cargo"];
            $_SESSION["departamento"] = $usuario["departamento"];

            header("Location: ".$destino);
            exit();

        }else{

            header("Location: index.php?error=rol");
            exit();

        }

    }else{

        header("Location: index.php?error=1");
        exit();

    }

}else{

    header("Location: index.php?error=1");
    exit();

}
?>