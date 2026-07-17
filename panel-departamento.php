<?php
session_start();
include("conexion.php");

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

$buscar = "";
if (isset($_GET["buscar"])) {
    $buscar = mysqli_real_escape_string($conexion, $_GET["buscar"]);
}

$jefeId = intval($_SESSION["dni"]);

$total = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) AS total FROM proyectos WHERE jefe_dni = $jefeId"));

$sinAsignar = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) AS total FROM proyectos WHERE jefe_dni = $jefeId AND (profesor_dni IS NULL OR profesor_dni = 0) AND estado <> 'Finalizado'"));

$enProceso = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) AS total FROM proyectos WHERE jefe_dni = $jefeId AND profesor_dni IS NOT NULL AND profesor_dni <> 0 AND estado <> 'Finalizado'"));

$finalizados = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) AS total FROM proyectos WHERE jefe_dni = $jefeId AND estado = 'Finalizado'"));

$sql = "SELECT
            p.*,
            d.nombre AS director_nombre,
            d.apellido AS director_apellido,
            pf.nombre AS profesor_nombre,
            pf.apellido AS profesor_apellido
        FROM proyectos p
        INNER JOIN personal d ON p.director_dni = d.dni
        LEFT JOIN personal pf ON p.profesor_dni = pf.dni
        WHERE p.jefe_dni = ?
          AND p.titulo LIKE ?
        ORDER BY p.fecha_creacion DESC";

$stmt = mysqli_prepare($conexion, $sql);
$like = '%' . $buscar . '%';
mysqli_stmt_bind_param($stmt, "is", $jefeId, $like);
mysqli_stmt_execute($stmt);
$proyectos = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Departamento</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="panel-page">
<nav class="panel-nav">
    <div class="brand">
        <i class="fas fa-building-columns"></i>
        <span>ENET SERVICES</span>
    </div>
    <div class="nav-user">
        <span class="user-name">
            <?= htmlspecialchars($_SESSION["nombre"] . " " . $_SESSION["apellido"]) ?>
            <span class="user-role"><?= htmlspecialchars($_SESSION["cargo"]) ?></span>
        </span>
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>
</nav>

<div class="director-content">
    <h1 class="director-title"><i class="fas fa-user-tie"></i> Panel de Departamento</h1>
    <p class="director-subtitle">Recibí proyectos de dirección, asigná un profesor y devolvé los que necesiten revisión.</p>

    <div class="stats-grid-director">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= intval($total["total"]) ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        <div class="stat-card stat-pendiente">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= intval($sinAsignar["total"]) ?></div>
                <div class="stat-label">Sin asignar</div>
            </div>
        </div>
        <div class="stat-card stat-revision">
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= intval($enProceso["total"]) ?></div>
                <div class="stat-label">En proceso</div>
            </div>
        </div>
        <div class="stat-card stat-finalizado">
            <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= intval($finalizados["total"]) ?></div>
                <div class="stat-label">Finalizado</div>
            </div>
        </div>
    </div>

    <div class="director-toolbar">
        <form method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="buscar" placeholder="Buscar proyecto..." value="<?= htmlspecialchars($buscar) ?>">
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <i class="fas fa-list-check"></i> Proyectos recibidos
        </div>
        <div class="table-wrap">
            <?php if (mysqli_num_rows($proyectos) > 0): ?>
                <table class="tabla-director">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Director</th>
                            <th>Profesor</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = mysqli_fetch_assoc($proyectos)): ?>
                        <tr>
                            <td>#<?= $fila["id_proyecto"] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($fila["titulo"]) ?></strong><br>
                                <small><?= htmlspecialchars($fila["descripcion"]) ?></small>
                            </td>
                            <td><?= htmlspecialchars($fila["director_apellido"] . " " . $fila["director_nombre"]) ?></td>
                            <td>
                                <?php if (!empty($fila["profesor_nombre"])): ?>
                                    <?= htmlspecialchars($fila["profesor_apellido"] . " " . $fila["profesor_nombre"]) ?>
                                <?php else: ?>
                                    <span class="texto-atenuado">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php switch ($fila["estado"]):
                                    case "Pendiente": ?>
                                        <span class="badge-estado badge-pendiente"><i class="fas fa-hourglass-half"></i> Pendiente</span>
                                        <?php break;
                                    case "Delegado": ?>
                                        <span class="badge-estado badge-delegado"><i class="fas fa-share-nodes"></i> Delegado</span>
                                        <?php break;
                                    case "En Revision": ?>
                                        <span class="badge-estado badge-revision"><i class="fas fa-magnifying-glass"></i> En revisión</span>
                                        <?php break;
                                    case "Devuelto": ?>
                                        <span class="badge-estado badge-devuelto"><i class="fas fa-rotate-left"></i> Devuelto</span>
                                        <?php break;
                                    case "Finalizado": ?>
                                        <span class="badge-estado badge-finalizado"><i class="fas fa-circle-check"></i> Finalizado</span>
                                        <?php break;
                                    default: ?>
                                        <?= htmlspecialchars($fila["estado"]) ?>
                                <?php endswitch; ?>
                            </td>
                            <td><?= date("d/m/Y", strtotime($fila["fecha_creacion"])) ?></td>
                            <td class="acciones-cell">
                                <?php if (empty($fila["profesor_nombre"]) && $fila["estado"] !== "Finalizado"): ?>
                                    <a href="asignar-proyecto.php?id=<?= $fila["id_proyecto"] ?>" class="btn-icon btn-delegar-icon" title="Asignar profesor"><i class="fas fa-user-plus"></i></a>
                                <?php elseif ($fila["estado"] !== "Finalizado"): ?>
                                    <a href="devolver-proyecto.php?id=<?= $fila["id_proyecto"] ?>" class="btn-icon btn-ver-icon" title="Devolver a dirección"><i class="fas fa-paper-plane"></i></a>
                                <?php else: ?>
                                    <span class="texto-atenuado">Sin acciones</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="sin-resultados">
                    <i class="fas fa-folder-open"></i>
                    No hay proyectos asignados a tu departamento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
