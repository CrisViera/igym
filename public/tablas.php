<?php
include_once '../config/database.php';
include_once '../classes/Tabla.php';

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$tablaService = new Tabla($pdo);
$usuarioId = $_SESSION['usuario_id'] ?? null;
$errorInstalacion = null;

try {
    $tablaService->instalarSemidioses($usuarioId);
} catch (PDOException $e) {
    $errorInstalacion = $e->getMessage();
}

$tablasDioses = [];
$tablasSemidioses = [];
try {
    $tablasDioses = $tablaService->obtenerTablasPorColeccion('dioses');
    $tablasSemidioses = $tablaService->obtenerTablasPorColeccion('semidioses');
} catch (PDOException $e) {
    $errorInstalacion = $errorInstalacion ?: $e->getMessage();
}

function resumenTabla($tablaService, $tabla) {
    $ejercicios = $tablaService->obtenerEjerciciosDeTabla($tabla['id']);
    $conPeso = 0;
    foreach ($ejercicios as $ejercicio) {
        if ($ejercicio['peso_minimo'] !== null && $ejercicio['peso_minimo'] !== '') {
            $conPeso++;
        }
    }
    return [
        'total' => count($ejercicios),
        'con_peso' => $conPeso,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablas iGym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include_once '../includes/transiciones.php'; ?>
</head>
<body>
    <div class="container mt-5 mb-5 vista">
        <h1 class="text-center">Tablas de entrenamiento</h1>
        <p class="text-center text-muted">Las tablas de semidioses reutilizan los pesos mínimos de las tablas de los dioses cuando el ejercicio coincide.</p>
        <div class="text-center mb-4">
            <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>

        <?php if ($errorInstalacion): ?>
            <div class="alert alert-danger">No se pudieron preparar las tablas: <?= htmlspecialchars($errorInstalacion) ?></div>
        <?php endif; ?>

        <h2 class="h4 mt-4">Tablas de los Dioses</h2>
        <?php if (!$tablasDioses): ?>
            <p class="text-muted">Todavía no hay tablas de los dioses en la base de datos. Los pesos mínimos se copiarán automáticamente cuando existan.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($tablasDioses as $tabla): ?>
                    <?php $resumen = resumenTabla($tablaService, $tabla); ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="h5"><?= htmlspecialchars($tabla['nombre']) ?></h3>
                                <p class="mb-1"><strong><?= htmlspecialchars($tabla['dia'] ?: 'Sin día') ?></strong></p>
                                <p class="text-muted"><?= htmlspecialchars($tabla['enfoque'] ?: $tabla['descripcion']) ?></p>
                                <p class="small mb-3"><?= $resumen['total'] ?> ejercicios · <?= $resumen['con_peso'] ?> con peso mínimo</p>
                                <a href="tabla_detalle.php?id=<?= (int) $tabla['id'] ?>" class="btn btn-outline-primary">Ver tabla</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="h4 mt-4">Tablas de los Semidioses</h2>
        <?php if (!$tablasSemidioses): ?>
            <p class="text-muted">No se han podido crear las tablas de semidioses.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($tablasSemidioses as $tabla): ?>
                    <?php $resumen = resumenTabla($tablaService, $tabla); ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-danger">
                            <div class="card-body">
                                <h3 class="h5"><?= htmlspecialchars($tabla['nombre']) ?></h3>
                                <p class="mb-1"><strong><?= htmlspecialchars($tabla['dia']) ?></strong> · <?= htmlspecialchars($tabla['enfoque']) ?></p>
                                <p class="text-muted"><?= htmlspecialchars($tabla['descripcion']) ?></p>
                                <p class="small mb-3"><?= $resumen['total'] ?> ejercicios · <?= $resumen['con_peso'] ?> con peso mínimo heredado</p>
                                <a href="tabla_detalle.php?id=<?= (int) $tabla['id'] ?>" class="btn btn-danger">Ver tabla</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-body">
                <h2 class="h5">Reglas de progresión</h2>
                <ul class="mb-0">
                    <li><strong>Series planas:</strong> elige un peso exigente para el rango, quedándote a 1-2 repeticiones del fallo (RIR 1-2). Usa el mismo peso en todas las series efectivas.</li>
                    <li><strong>Progresión doble:</strong> cuando llegues al tope de repeticiones en todas las series, sube el peso en la siguiente sesión aunque las reps bajen al mínimo del rango.</li>
                    <li><strong>Calentamiento:</strong> 2 series de aproximación solo en el primer ejercicio del bloque grande (6 reps al 50% y 3 reps al 75%).</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
