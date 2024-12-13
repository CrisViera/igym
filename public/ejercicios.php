<?php
include_once '../config/database.php';
include_once '../classes/Ejercicio.php';

$ejercicio = new Ejercicio($pdo);
$ejercicios = $ejercicio->obtenerEjercicios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejercicios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Ejercicios disponibles</h1>
        <ul class="list-group">
            <?php foreach ($ejercicios as $ejercicio): ?>
                <li class="list-group-item">
                    <a href="registrar_ejercicio.php?id=<?= $ejercicio['id']; ?>"><?= $ejercicio['nombre']; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
