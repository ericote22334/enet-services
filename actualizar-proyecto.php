<?php

session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: login.php");
    exit();
}

$id = intval($_POST["id"]);

$titulo = $_POST["titulo"];
$descripcion = $_POST["descripcion"];
$jefe = intval($_POST["jefe"]);

$archivoNuevo = "";

/* Buscar archivo actual */

$sql = "SELECT archivo FROM proyectos WHERE id_proyecto=?";

$stmt = mysqli_prepare($conexion,$sql);

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);

$proyecto = mysqli_fetch_assoc($res);

$archivo = $proyecto["archivo"];

/* Subir nuevo archivo */

if(!empty($_FILES["archivo"]["name"])){

    if(!is_dir("uploads")){

        mkdir("uploads");

    }

    $archivo=time()."_".$_FILES["archivo"]["name"];

    move_uploaded_file(

        $_FILES["archivo"]["tmp_name"],

        "uploads/".$archivo

    );

}

/* Actualizar */

$sql="UPDATE proyectos

SET

titulo=?,

descripcion=?,

jefe_dni=?,

archivo=?

WHERE id_proyecto=?";

$stmt=mysqli_prepare($conexion,$sql);

mysqli_stmt_bind_param(

$stmt,

"ssisi",

$titulo,

$descripcion,

$jefe,

$archivo,

$id

);

mysqli_stmt_execute($stmt);

/* Historial */

$sql="INSERT INTO historial_proyectos

(

proyecto,

usuario,

accion,

comentario

)

VALUES

(

?,

?,

'Edición',

'Proyecto actualizado'

)";

$stmt=mysqli_prepare($conexion,$sql);

mysqli_stmt_bind_param(

$stmt,

"ii",

$id,

$_SESSION["dni"]

);

mysqli_stmt_execute($stmt);

header("Location: proyectos.php?editado=1");

?>