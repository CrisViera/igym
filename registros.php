<?php
$pdo = new PDO('mysql:host=localhost;dbname=mi_gimnasio', 'root', '1234');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener los datos del ejercicio y el usuario logueado
$ejercicioId = $_GET['ejercicio_id'] ?? null;
$usuarioLogueado = $_GET['usuario'] ?? null;

if ($ejercicioId && $usuarioLogueado) {
    // Obtener registros del ejercicio
    $stmt = $pdo->prepare("SELECT * FROM registros WHERE ejercicio_id = ? AND usuario_id = (SELECT id FROM usuarios WHERE nombre = ?)");
    $stmt->execute([$ejercicioId, $usuarioLogueado]);
    $registros = $stmt->fetchAll();

    // Obtener nombre del ejercicio
    $stmt = $pdo->prepare("SELECT nombre FROM ejercicios WHERE id = ?");
    $stmt->execute([$ejercicioId]);
    $ejercicio = $stmt->fetch();
} else {
    // Redirigir si no se pasan los parámetros necesarios
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros de Ejercicio</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Registros de <?= htmlspecialchars($ejercicio['nombre']) ?> para <?= htmlspecialchars($usuarioLogueado) ?></h1>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Repeticiones</th>
                    <th>Series</th>
                    <th>Peso</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?= htmlspecialchars($registro['repeticiones']) ?></td>
                        <td><?= htmlspecialchars($registro['series']) ?></td>
                        <td><?= htmlspecialchars($registro['peso']) ?></td>
                        <td><?= htmlspecialchars($registro['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
