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

/* ===========================
   ESTADÍSTICAS
=========================== */

$total = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos"));

$pendientes = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Pendiente'"));

$delegados = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Delegado'"));

$revision = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='En Revision'"));

$devueltos = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Devuelto'"));

$finalizados = mysqli_fetch_assoc(mysqli_query($conexion,
"SELECT COUNT(*) AS total FROM proyectos WHERE estado='Finalizado'"));

/* ===========================
   BUSCADOR
=========================== */

$buscar = "";

if(isset($_GET["buscar"])){
    $buscar = mysqli_real_escape_string($conexion,$_GET["buscar"]);
}

$sql = "SELECT

p.*,

d.nombre AS director_nombre,
d.apellido AS director_apellido,

j.nombre AS jefe_nombre,
j.apellido AS jefe_apellido

FROM proyectos p

INNER JOIN personal d
ON p.director_dni=d.dni

LEFT JOIN personal j
ON p.jefe_dni=j.dni

WHERE p.titulo LIKE '%$buscar%'

ORDER BY p.fecha_creacion DESC";

$proyectos = mysqli_query($conexion,$sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Panel Director - EnetServices</title>

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
            <?= htmlspecialchars($_SESSION["nombre"]." ".$_SESSION["apellido"]) ?>
            <span class="user-role"><?= htmlspecialchars($_SESSION["cargo"]) ?></span>
        </span>
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>

</nav>

<div class="director-content">

    <h1 class="director-title"><i class="fas fa-chart-line"></i> Panel de Dirección</h1>
    <p class="director-subtitle">Supervisá el estado general de todos los proyectos del área.</p>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <div class="stats-grid-director">

        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= $total["total"] ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <div class="stat-card stat-pendiente">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= $pendientes["total"] ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>

        <div class="stat-card stat-delegado">
            <div class="stat-icon"><i class="fas fa-share-nodes"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= $delegados["total"] ?></div>
                <div class="stat-label">Delegados</div>
            </div>
        </div>

        <div class="stat-card stat-revision">
            <div class="stat-icon"><i class="fas fa-magnifying-glass"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= $revision["total"] ?></div>
                <div class="stat-label">En revisión</div>
            </div>
        </div>

        <div class="stat-card stat-devuelto">
            <div class="stat-icon"><i class="fas fa-rotate-left"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= $devueltos["total"] ?></div>
                <div class="stat-label">Devueltos</div>
            </div>
        </div>

        <div class="stat-card stat-finalizado">
            <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?= $finalizados["total"] ?></div>
                <div class="stat-label">Finalizados</div>
            </div>
        </div>

    </div>

    <!-- BUSCADOR + NUEVO PROYECTO -->
    <div class="director-toolbar">

        <form method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input
                    type="text"
                    name="buscar"
                    placeholder="Buscar proyecto por título..."
                    value="<?= htmlspecialchars($buscar) ?>">
            </div>
        </form>

        <a href="nuevo-proyecto.php" class="btn-nuevo">
            <i class="fas fa-plus"></i> Nuevo Proyecto
        </a>

    </div>

    <!-- TABLA DE PROYECTOS -->
    <div class="table-card">

        <div class="table-card-header">
            <i class="fas fa-list-check"></i> Proyectos
        </div>

        <div class="table-wrap">

        <?php if(mysqli_num_rows($proyectos)>0): ?>

        <table class="tabla-director">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Director</th>
                    <th>Jefe</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

            <?php while($fila=mysqli_fetch_assoc($proyectos)): ?>

                <tr>

                    <td>#<?= $fila["id_proyecto"] ?></td>

                    <td><?= htmlspecialchars($fila["titulo"]) ?></td>

                    <td><?= htmlspecialchars($fila["director_apellido"].", ".$fila["director_nombre"]) ?></td>

                    <td>
                        <?php if($fila["jefe_nombre"]): ?>
                            <?= htmlspecialchars($fila["jefe_apellido"].", ".$fila["jefe_nombre"]) ?>
                        <?php else: ?>
                            <span class="texto-atenuado">Sin asignar</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php switch($fila["estado"]):
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
                        endswitch; ?>
                    </td>

                    <td><?= date("d/m/Y",strtotime($fila["fecha_creacion"])) ?></td>

                    <td>
                        <div class="acciones-cell">

                            <a
                                href="detalle-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
                                class="btn-icon btn-ver-icon"
                                title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>

                            <a
                                href="editar-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
                                class="btn-icon btn-editar-icon"
                                title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>

                            <a
                                href="delegar-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
                                class="btn-icon btn-delegar-icon"
                                title="Delegar">
                                <i class="fas fa-share-nodes"></i>
                            </a>

                            <a
                                href="eliminar-proyecto.php?id=<?= $fila["id_proyecto"] ?>"
                                class="btn-icon btn-eliminar-icon"
                                title="Eliminar"
                                onclick="return confirm('¿Está seguro de eliminar este proyecto?')">
                                <i class="fas fa-trash"></i>
                            </a>

                        </div>
                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="sin-resultados">
                <i class="fas fa-folder-open"></i>
                No hay proyectos registrados.
            </div>

        <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>