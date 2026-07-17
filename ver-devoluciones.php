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

$sql = "SELECT p.*, d.nombre AS director_nombre, d.apellido AS director_apellido, j.nombre AS jefe_nombre, j.apellido AS jefe_apellido FROM proyectos p INNER JOIN personal d ON p.director_dni = d.dni LEFT JOIN personal j ON p.jefe_dni = j.dni WHERE p.id_proyecto = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$proyecto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$proyecto) {
    die("Proyecto no encontrado.");
}

$sqlComentarios = "SELECT hp.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido FROM historial_proyectos hp LEFT JOIN personal u ON hp.usuario = u.dni WHERE hp.proyecto = ? AND hp.accion = 'Devolución' ORDER BY hp.fecha DESC";
$stmtComentarios = mysqli_prepare($conexion, $sqlComentarios);
mysqli_stmt_bind_param($stmtComentarios, "i", $id);
mysqli_stmt_execute($stmtComentarios);
$comentarios = mysqli_stmt_get_result($stmtComentarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devoluciones del Proyecto</title>
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
        <div class="table-card-header"><i class="fas fa-comments"></i> Devoluciones</div>
        <div class="table-wrap">
            <div class="mb-3">
                <label class="form-label">Proyecto</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($proyecto["titulo"]) ?>" disabled>
            </div>
            <?php if (mysqli_num_rows($comentarios) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($comentarios)): ?>
                    <div class="devolucion-card">
                        <div class="devolucion-meta">
                            <span class="devolucion-autor"><?= htmlspecialchars($fila["usuario_apellido"] . " " . $fila["usuario_nombre"]) ?></span>
                            <span class="devolucion-fecha"><?= date("d/m/Y H:i", strtotime($fila["fecha"])) ?></span>
                        </div>
                        <p><?= nl2br(htmlspecialchars($fila["comentario"])) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="sin-resultados">
                    <i class="fas fa-comments-dollar"></i>
                    No hay devoluciones registradas para este proyecto.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
