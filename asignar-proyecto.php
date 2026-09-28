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

$sql = "SELECT p.*, d.nombre AS director_nombre, d.apellido AS director_apellido FROM proyectos p INNER JOIN personal d ON p.director_dni = d.dni WHERE p.id_proyecto = ? AND p.jefe_dni = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $_SESSION["dni"]);
mysqli_stmt_execute($stmt);
$proyecto = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$proyecto) {
    die("Proyecto no encontrado o no autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $profesor = intval($_POST["profesor"]);
    $comentario = trim($_POST["comentario"]);

    $sqlUpdate = "UPDATE proyectos SET profesor_dni = ?, estado = 'En Revision' WHERE id_proyecto = ? AND jefe_dni = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "iii", $profesor, $id, $_SESSION["dni"]);
    mysqli_stmt_execute($stmtUpdate);

    $sqlProfesor = "SELECT nombre, apellido FROM personal WHERE dni = ?";
    $stmtProfesor = mysqli_prepare($conexion, $sqlProfesor);
    mysqli_stmt_bind_param($stmtProfesor, "i", $profesor);
    mysqli_stmt_execute($stmtProfesor);
    $profesorData = mysqli_stmt_get_result($stmtProfesor)->fetch_assoc();

    $nombreProfesor = $profesorData ? $profesorData["apellido"] . " " . $profesorData["nombre"] : "profesor";
    $comentarioHistorial = $comentario !== "" ? "Asignado a $nombreProfesor. $comentario" : "Asignado a $nombreProfesor.";

    $sqlHistorial = "INSERT INTO historial_proyectos (proyecto, usuario, accion, comentario) VALUES (?, ?, 'Asignación', ?)";
    $stmtHistorial = mysqli_prepare($conexion, $sqlHistorial);
    mysqli_stmt_bind_param($stmtHistorial, "iis", $id, $_SESSION["dni"], $comentarioHistorial);
    mysqli_stmt_execute($stmtHistorial);

    // Aviso por WhatsApp a direccion: el proyecto vuelve con un profesor asignado
    avisarBot($id, "asignado");

    header("Location: panel-departamento.php?asignado=1");
    exit();
}

$sqlProfesores = "SELECT p.dni, p.nombre, p.apellido, r.departamento FROM personal p INNER JOIN roles r ON p.dni = r.dni WHERE r.cargo = 'Profesor' AND r.departamento = ? ORDER BY p.apellido, p.nombre";
$stmtProfesores = mysqli_prepare($conexion, $sqlProfesores);
mysqli_stmt_bind_param($stmtProfesores, "s", $_SESSION["departamento"]);
mysqli_stmt_execute($stmtProfesores);
$profesores = mysqli_stmt_get_result($stmtProfesores);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asignar Proyecto</title>
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
        <div class="table-card-header">Asignar Profesor</div>
        <div class="table-wrap">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Proyecto</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($proyecto["titulo"]) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profesor</label>
                    <select name="profesor" class="form-select" required>
                        <option value="">Seleccione un profesor...</option>
                        <?php while ($fila = mysqli_fetch_assoc($profesores)): ?>
                        <option value="<?= $fila["dni"] ?>"><?= htmlspecialchars($fila["departamento"] . ' - ' . $fila["apellido"] . ' ' . $fila["nombre"]) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Comentario</label>
                    <textarea name="comentario" rows="4" class="form-control" placeholder="Agregar observación para el profesor..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <a href="panel-departamento.php" class="btn btn-secondary">Cancelar</a>
                    <button class="btn btn-warning">Asignar</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
