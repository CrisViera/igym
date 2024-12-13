<?php

// Conectar a la base de datos
include_once '../config/database.php';

// Comprobar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Consulta para verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre = :usuario AND password = :password");
    $stmt->execute(['usuario' => $usuario, 'password' => $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // Si el usuario y la contraseña coinciden
    if ($user) {
        // Iniciar sesión
        session_start();
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario'] = $usuario;

        // Redirigir al dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        // Si las credenciales son incorrectas
        $error = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Iniciar Sesión</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de login -->
        <form method="POST">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
