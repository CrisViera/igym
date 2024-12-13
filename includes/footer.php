<?php
include_once '../config/database.php';
include_once '../classes/Registro.php';
include_once '../classes/Ejercicio.php';
include_once '../classes/Usuario.php';

$registro = new Registro($pdo);
$ejercicio = new Ejercicio($pdo);
$usuario = new Usuario($pdo);

$ejercicio_id = $_GET['id'];
$ejercicio_data = $ejercicio->obtenerEjercicioPorId($ejercicio_id);
$usuarios = $usuario->obtenerUsuarios();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $series = $_POST['series'];
    $repeticiones = $_POST['repeticiones'];
    $peso = $_POST['peso'];
    $fecha = date('Y-m-d');
    
    $registro->registrarEjercicio($usuario_id, $ejercicio_id, $series, $repeticiones, $peso, $fecha);
    echo "Ejercicio registrado correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Ejercicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Registrar ejercicio: <?= $ejercicio_data['nombre']; ?></h1>
        <form method="POST">
            <div class="mb-3">
                <label for="usuario_id" class="form-label">Usuario</label>
                <select class="form-select" id="usuario_id" name="usuario_id">
                    <?php foreach ($usuarios as $usuario_data): ?>
                        <option value="<?= $usuario_data['id']; ?>"><?= $usuario_data['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="series" class="form-label">Series</label>
                <input type="number" class="form-control" id="series" name="series" required>
            </div>
            <div class="mb-3">
                <label for="repeticiones" class="form-label">Repeticiones</label>
                <input type="number" class="form-control" id="repeticiones" name="repeticiones" required>
            </div>
            <div class="mb-3">
                <label for="peso" class="form-label">Peso (kg)</label>
                <input type="number" step="0.01" class="form-control" id="peso" name="peso" required>
            </div>
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
    </div>
</body>
</html>
