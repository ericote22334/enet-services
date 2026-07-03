<?php

session_start();

include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: login.php");
    exit();
}

$titulo = $_POST["titulo"];
$descripcion = $_POST["descripcion"];
$jefe = $_POST["jefe"];
$director = $_SESSION["dni"];

$archivo = NULL;

// Subir archivo
if (!empty($_FILES["archivo"]["name"])) {

    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }

    $archivo = time() . "_" . basename($_FILES["archivo"]["name"]);

    move_uploaded_file(
        $_FILES["archivo"]["tmp_name"],
        "uploads/" . $archivo
    );
}

// Insertar proyecto
$sql = "INSERT INTO proyectos
(
titulo,
descripcion,
director_dni,
jefe_dni,
estado,
archivo
)
VALUES
(
?,
?,
?,
?,
'Delegado',
?
)";

$stmt = mysqli_prepare($conexion,$sql);

mysqli_stmt_bind_param(
$stmt,
"ssiis",
$titulo,
$descripcion,
$director,
$jefe,
$archivo
);

if(mysqli_stmt_execute($stmt)){

$id = mysqli_insert_id($conexion);

// Historial

$sqlHistorial="INSERT INTO historial_proyectos
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
'Creación',
'Proyecto creado y delegado'
)";

$stmtHistorial=mysqli_prepare($conexion,$sqlHistorial);

mysqli_stmt_bind_param(
$stmtHistorial,
"ii",
$id,
$director
);

mysqli_stmt_execute($stmtHistorial);

header("Location: proyectos.php?ok=1");

}else{

echo "Error al guardar el proyecto.";

}
?>