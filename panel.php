<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel — Enet Services</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body class="panel-page">

<nav class="panel-nav">
    <span class="brand">Enet Services</span>
    <a href="logout.php">Cerrar sesión</a>
</nav>

<div class="panel-content">
    <div class="panel-card">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h1>
        <p>Accediste correctamente al sistema.</p>
        <span class="rol-badge"><?php echo htmlspecialchars($_SESSION['rol']); ?></span>
    </div>
</div>

</body>
</html>
