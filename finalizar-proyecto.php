<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["dni"])) {
    header("Location: index.php");
    exit();
}

if (
    $_SESSION["cargo"] != "Director" &&
    $_SESSION["cargo"] != "Subdirector"
) {
    header("Location: logout.php");
    exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$action = isset($_GET["action"]) ? $_GET["action"] : "finalizar";

$sql = "SELECT p.*, d.nombre AS director_nombre, d.apellido AS director_apellido, j.nombre AS jefe_nombre, j.apellido AS jefe_apellido FROM proyectos p INNER JOIN personal d ON p.director_dni = d.dni LEFT JOIN personal j ON p.jefe_dni = j.dni WHERE p.id_proyecto = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$proyecto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$proyecto) {
    die("Proyecto no encontrado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($action === "reopen") {
        $nuevoEstado = !empty($proyecto["profesor_dni"]) ? 'En Revision' : 'Pendiente';
        $comentario = 'Proyecto reabierto por dirección.';
        $accionHistorial = 'Reapertura';
    } else {
        $nuevoEstado = 'Finalizado';
        $comentario = 'Proyecto finalizado por dirección.';
        $accionHistorial = 'Finalización';
    }

    $sqlUpdate = "UPDATE proyectos SET estado = ? WHERE id_proyecto = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "si", $nuevoEstado, $id);
    mysqli_stmt_execute($stmtUpdate);

    $sqlHistorial = "INSERT INTO historial_proyectos (proyecto, usuario, accion, comentario) VALUES (?, ?, ?, ?)";
    $stmtHistorial = mysqli_prepare($conexion, $sqlHistorial);
    mysqli_stmt_bind_param($stmtHistorial, "iiss", $id, $_SESSION["dni"], $accionHistorial, $comentario);
    mysqli_stmt_execute($stmtHistorial);

    header("Location: panel.php?updated=1");
    exit();
}

$pageTitle = $action === "reopen" ? "Reabrir Proyecto" : "Finalizar Proyecto";
$buttonLabel = $action === "reopen" ? "Reabrir" : "Finalizar";
$buttonClass = $action === "reopen" ? "btn-secondary" : "btn-warning";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="panel-page">
<div class="panel-nav">
    <div class="brand"><i class="fas fa-building-columns"></i> ENET SERVICES</div>
    <div class="nav-user">
        <span class="user-name"><?= htmlspecialchars($_SESSION["nombre"] . " " . $_SESSION["apellido"]) ?></span>
        <a href="panel.php" class="btn-logout"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</div>
<div class="director-content">
    <div class="table-card">
        <div class="table-card-header"><i class="fas fa-check"></i> <?= htmlspecialchars($pageTitle) ?></div>
        <div class="table-wrap">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Proyecto</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($proyecto["titulo"]) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estado actual</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($proyecto["estado"]) ?>" disabled>
                </div>
                <?php if ($action === "reopen"): ?>
                    <div class="mb-3">
                        <label class="form-label">Nuevo estado</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars(!empty($proyecto["profesor_dni"]) ? 'En Revision' : 'Pendiente') ?>" disabled>
                    </div>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <a href="panel.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn <?= $buttonClass ?>"><?= htmlspecialchars($buttonLabel) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
