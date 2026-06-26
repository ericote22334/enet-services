<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Enet Services</title>

    <link rel="stylesheet" href="estilo.css">

</head>

<body>



<div class="login-box">



    <div class="login-header">

        <div class="login-logo">ES</div>

        <h2>Enet Services</h2>

        <p class="subtitle">Sistema de gestión de proyectos escolares</p>

    </div>

    <?php if (isset($_GET['error'])): ?>
        <p class="login-error">
            <?php if ($_GET['error'] === 'rol'): ?>
                El rol seleccionado no coincide con tu usuario.
            <?php else: ?>
                Usuario o contraseña incorrectos.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="POST">



        <div class="form-group">

            <label for="usuario">Usuario</label>

            <input type="text" id="usuario" name="usuario" placeholder="Ingresá tu usuario" required>

        </div>



        <div class="form-group">

            <label for="password">Contraseña</label>

            <input type="password" id="password" name="password" placeholder="Ingresá tu contraseña" required>

        </div>



        <div class="form-group">

            <label for="rol">Rol</label>

            <select id="rol" name="rol" required>

                <option value="Direccion">Dirección</option>

                <option value="Jefe de Departamento">Jefe de Departamento</option>

            </select>

        </div>



        <button type="submit">Ingresar al sistema</button>



    </form>



</div>



</body>

</html>

