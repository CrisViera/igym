<?php
include_once '../config/database.php';
include_once '../classes/Tabla.php';

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$tablaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tablaService = new Tabla($pdo);

try {
    $tablaService->instalarSemidioses($_SESSION['usuario_id'] ?? null);
} catch (PDOException $e) {
    // Si falla la instalación, se intenta mostrar la tabla existente.
}

$tabla = $tablaId ? $tablaService->obtenerTablaPorId($tablaId) : null;
if (!$tabla) {
    header("Location: tablas.php");
    exit();
}

$ejercicios = $tablaService->obtenerEjerciciosDeTabla($tabla['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tabla['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include_once '../includes/transiciones.php'; ?>
</head>
<body>
    <div class="container mt-5 mb-5 vista">
        <h1 class="text-center"><?= htmlspecialchars($tabla['nombre']) ?></h1>
        <p class="text-center text-muted mb-1">
            <?= htmlspecialchars($tabla['dia'] ?: '') ?>
            <?= $tabla['enfoque'] ? ' · ' . htmlspecialchars($tabla['enfoque']) : '' ?>
        </p>
        <?php if (!empty($tabla['descripcion'])): ?>
            <p class="text-center"><?= htmlspecialchars($tabla['descripcion']) ?></p>
        <?php endif; ?>
        <div class="text-center mb-4">
            <a href="tablas.php" class="btn btn-secondary">Volver a tablas</a>
            <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Bloque</th>
                        <th>Ejercicio</th>
                        <th>Series</th>
                        <th>Rango / Int.</th>
                        <th>Peso mínimo</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ejercicios as $ejercicio): ?>
                        <tr>
                            <td><?= htmlspecialchars($ejercicio['bloque'] ?: $ejercicio['grupo_muscular']) ?></td>
                            <td>
                                <a href="ejercicio_detalle.php?ejercicio_id=<?= (int) $ejercicio['ejercicio_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($ejercicio['nombre']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($ejercicio['series']) ?></td>
                            <td><?= htmlspecialchars($ejercicio['rango']) ?></td>
                            <td>
                                <?php if ($ejercicio['peso_minimo'] !== null && $ejercicio['peso_minimo'] !== ''): ?>
                                    <?= rtrim(rtrim(number_format((float) $ejercicio['peso_minimo'], 2, ',', ''), '0'), ',') ?> kg
                                <?php else: ?>
                                    <span class="text-muted">Sin dato en dioses</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($ejercicio['nota']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($tabla['coleccion'] === 'semidioses'): ?>
            <p class="small text-muted mt-3">
                El peso mínimo se copia de la tabla de los dioses cuando el ejercicio coincide o es equivalente.
                Si no hay coincidencia, se usa el peso más bajo registrado de ese movimiento.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
