<?php
session_start();

if(!isset($_SESSION["dni"])){
    header("Location: index.php");
    exit();
}

if($_SESSION["cargo"] != "Jefe"){
    header("Location: logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Departamento - EnetServices</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="panel-page">
    <nav class="panel-nav">
        <div class="brand">
            <i class="fas fa-building"></i>
            <span>EnetServices - Panel de Departamento</span>
        </div>
        <div class="nav-user">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["nombre"] ?? "Director"); ?></span>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </nav>

    <header class="panel-header">
        <h1><i class="fas fa-tachometer-alt"></i> Panel de Departamento</h1>
        <p class="subtitle">Gestión completa de proyectos y recursos del departamento</p>
    </header>

    <main class="dashboard">
        <section class="welcome-card">
            <h2><i class="fas fa-user-tie"></i> ¡Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"] ?? "Director"); ?>!</h2>
            <p>Desde aquí puedes gestionar los diferentes aspectos del departamento. Supervisa proyectos, asigna tareas y mantén la comunicación actualizada.</p>
        </section>

        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Resumen de Proyectos</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">12</div>
                    <div class="stat-label">Sin asignar</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">8</div>
                    <div class="stat-label">En proceso</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Finalizados</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-folder-plus"></i> Proyectos sin Asignar</h2>
            <div class="proyecto-item">
                <div class="proyecto-info">
                    <span class="proyecto-titulo">Desarrollo de App Móvil</span>
                    <span class="proyecto-fecha">Creado: 01/04/2026</span>
                </div>
                <button class="btn-asignar">
                    <i class="fas fa-user-plus"></i> Asignar
                </button>
            </div>
            <div class="proyecto-item">
                <div class="proyecto-info">
                    <span class="proyecto-titulo">Sistema de Gestión</span>
                    <span class="proyecto-fecha">Creado: 02/04/2026</span>
                </div>
                <button class="btn-asignar">
                    <i class="fas fa-user-plus"></i> Asignar
                </button>
            </div>
            <div class="proyecto-item">
                <div class="proyecto-info">
                    <span class="proyecto-titulo">Plataforma E-learning</span>
                    <span class="proyecto-fecha">Creado: 03/04/2026</span>
                </div>
                <button class="btn-asignar">
                    <i class="fas fa-user-plus"></i> Asignar
                </button>
            </div>
            <ul>
                <li>Profesor 1</li>
                <li>Profesor 2</li>
                <li>Profesor 3</li>
            </ul>
        </div>

    </main>
</body>