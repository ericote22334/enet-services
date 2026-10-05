<?php

session_start();
include("conexion.php");
include("bot.php");

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

$sql = "SELECT archivo, jefe_dni, titulo FROM proyectos WHERE id_proyecto=?";

$stmt = mysqli_prepare($conexion,$sql);

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);

$proyecto = mysqli_fetch_assoc($res);

$archivo = $proyecto["archivo"];

$jefeAnterior = $proyecto["jefe_dni"];

$tituloAnterior = $proyecto["titulo"];

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

/* Aviso por WhatsApp */

if ($jefeAnterior && $jefeAnterior != $jefe) {

    /* Cambio de jefe de departamento: aviso al jefe anterior.
       Se arma el mensaje aca mismo porque una vez guardado el cambio
       el proyecto ya no lo tiene asociado, y el bot no lo encontraria. */

    $sqlTel = "SELECT telefono FROM personal WHERE dni = ?";
    $stmtTel = mysqli_prepare($conexion, $sqlTel);
    mysqli_stmt_bind_param($stmtTel, "i", $jefeAnterior);
    mysqli_stmt_execute($stmtTel);
    $telAnterior = mysqli_stmt_get_result($stmtTel)->fetch_assoc()["telefono"] ?? null;

    if ($telAnterior) {
        avisarTelefono(
            $telAnterior,
            "🔄 *CAMBIO DE RESPONSABLE*\n\nYa no estas a cargo de:\n" . $tituloAnterior .
            "\n\nEl proyecto fue reasignado a otro jefe de departamento."
        );
    }

    // Aviso al nuevo jefe, igual que cuando se delega un proyecto nuevo
    avisarBot($id, "delegado");

} else {

    // Mismo jefe: solo cambio informacion del proyecto
    avisarBot($id, "editado");

}

header("Location: panel.php?editado=1");

?>