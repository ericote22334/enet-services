<?php
session_start();
include("conexion.php");
include("bot.php");

if (!isset($_SESSION["dni"])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION["cargo"] != "Jefe") {
    header("Location: logout.php");
    exit();
}

$checkColumn = mysqli_query($conexion, "SHOW COLUMNS FROM proyectos LIKE 'profesor_dni'");
if (mysqli_num_rows($checkColumn) === 0) {
    mysqli_query($conexion, "ALTER TABLE proyectos ADD COLUMN profesor_dni INT NULL DEFAULT NULL");
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

$sql = "SELECT p.* FROM proyectos p WHERE p.id_proyecto = ? AND p.jefe_dni = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $_SESSION["dni"]);
mysqli_stmt_execute($stmt);
$proyecto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$proyecto) {
    die("Proyecto no encontrado o no autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $motivo = trim($_POST["motivo"]);

    $sqlUpdate = "UPDATE proyectos SET estado = 'Devuelto' WHERE id_proyecto = ? AND jefe_dni = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "ii", $id, $_SESSION["dni"]);
    mysqli_stmt_execute($stmtUpdate);

    $comentario = $motivo !== "" ? $motivo : 'Devolución de proyecto a dirección.';

    $sqlHistorial = "INSERT INTO historial_proyectos (proyecto, usuario, accion, comentario) VALUES (?, ?, 'Devolución', ?)";
    $stmtHistorial = mysqli_prepare($conexion, $sqlHistorial);
    mysqli_stmt_bind_param($stmtHistorial, "iis", $id, $_SESSION["dni"], $comentario);
    mysqli_stmt_execute($stmtHistorial);

    // Aviso por WhatsApp a direccion
    avisarBot($id, "devuelto");

    header("Location: panel-departamento.php?devuelto=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devolver Proyecto</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="panel-page">
<div class="panel-nav">
    <div class="brand"><i class="fas fa-building-columns"></i> ENET SERVICES</div>
    <div class="nav-user">
        <span class="user-name"><?= htmlspecialchars($_SESSION["nombre"] . " " . $_SESSION["apellido"]) ?></span>
        <a href="panel-departamento.php" class="btn-logout"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</div>
<div class="director-content">
    <div class="table-card">
        <div class="table-card-header">Devolver proyecto a Dirección</div>
        <div class="table-wrap">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Proyecto</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($proyecto["titulo"]) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo de la devolución</label>
                    <textarea name="motivo" rows="5" class="form-control" required placeholder="Explicá el motivo y el estado actual para dirección..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <a href="panel-departamento.php" class="btn btn-secondary">Cancelar</a>
                    <button class="btn btn-danger">Devolver</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>