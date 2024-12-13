<?php
include_once '../config/database.php';

// Verificar si el usuario está autenticado
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
$usuario_id = $_SESSION['usuario_id'];
if (isset($_GET['ejercicio_id'])) {
    $_SESSION['ultimo_ejercicio'] = $_GET['ejercicio_id'];
}

// Obtener los datos de la gráfica (rendimiento por día de la semana)
$stmt = $pdo->prepare("SELECT DAYOFWEEK(fecha) AS dia, AVG(peso) AS promedio FROM registro_ejercicios WHERE usuario_id = :usuario_id GROUP BY dia");
$stmt->execute(['usuario_id' => $usuario_id]);
$graficaDatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$grafica = [
    'Lunes' => 0, 'Martes' => 0, 'Miércoles' => 0, 'Jueves' => 0, 'Viernes' => 0, 'Sábado' => 0, 'Domingo' => 0
];
foreach ($graficaDatos as $dato) {
    switch ($dato['dia']) {
        case 1: $grafica['Lunes'] = $dato['promedio']; break;
        case 2: $grafica['Martes'] = $dato['promedio']; break;
        case 3: $grafica['Miércoles'] = $dato['promedio']; break;
        case 4: $grafica['Jueves'] = $dato['promedio']; break;
        case 5: $grafica['Viernes'] = $dato['promedio']; break;
        case 6: $grafica['Sábado'] = $dato['promedio']; break;
        case 7: $grafica['Domingo'] = $dato['promedio']; break;
    }
}

// Obtener los ejercicios agrupados por grupo muscular
try {
    $stmt = $pdo->prepare("SELECT id, nombre, grupo_muscular FROM ejercicios");
    $stmt->execute();
    $ejercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
}

// Agrupar ejercicios por grupo muscular
$gruposMusculares = [];
foreach ($ejercicios as $ejercicio) {
    $gruposMusculares[$ejercicio['grupo_muscular']][] = $ejercicio;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard iGym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Bienvenido, <?php echo $_SESSION['usuario']; ?></h1>
        <p class="text-center mt-3">¿Estás listo para darlo todo?</p>
        <!-- Botón de cerrar sesión -->
<form action="logout.php" method="post" class="text-center mt-4">
    <button type="submit" class="btn btn-danger">Cerrar sesión</button>
</form>

        <h3 class="mt-4 text-center mb-3">Grupos musculares</h3>

        <?php if (isset($_SESSION['ultimo_ejercicio'])): ?>
            <div class="text-center mt-4 mb-4">
                <a href="ejercicio_detalle.php?ejercicio_id=<?php echo $_SESSION['ultimo_ejercicio']; ?>" class="btn btn-primary">Volver al último ejercicio</a>
            </div>
        <?php endif; ?>

        <div class="accordion" id="accordionEjercicios">
            <?php foreach ($gruposMusculares as $grupo => $ejercicios): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?php echo $grupo; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $grupo; ?>" aria-expanded="false" aria-controls="collapse<?php echo $grupo; ?>">
                            <?php echo ucfirst($grupo); ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $grupo; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $grupo; ?>" data-bs-parent="#accordionEjercicios">
                        <div class="accordion-body">
                            <ul class="list-group">
                                <?php foreach ($ejercicios as $ejercicio): ?>
                                    <li class="list-group-item">
                                        <a href="ejercicio_detalle.php?ejercicio_id=<?php echo $ejercicio['id']; ?>" class="btn btn-link text-decoration-none text-dark">
                                            <?php echo $ejercicio['nombre']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <h2>Gestión de ejercicios</h2>
    <button type="button" class="btn btn-primary mt-4" data-bs-toggle="modal" data-bs-target="#addExerciseModal">
        Añadir Ejercicio
    </button>
</div>
        <!-- Modal para añadir un ejercicio-->
    <div class="modal fade" id="addExerciseModal" tabindex="-1" aria-labelledby="addExerciseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExerciseModalLabel">Añadir Ejercicio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addExerciseForm" action="añadir_ejercicio.php" method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del ejercicio</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="grupo_muscular" class="form-label">Grupo muscular</label>
                            <select id="grupo_muscular" name="grupo_muscular" class="form-select" required>
                                <option value="">Selecciona un grupo muscular</option>
                                <option value="Pecho">Pecho</option>
                                <option value="Espalda">Espalda</option>
                                <option value="Piernas">Piernas</option>
                                <option value="Hombros">Hombros</option>
                                <option value="Brazos">Brazos</option>
                                <option value="Abdomen">Abdomen</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Añadir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

        <h3 class="mt-5 mb-5 text-center">Rendimiento promedio por día</h3>
        <div class="row">
            <div class="col-md-6">
                <canvas id="graficaLunes"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="graficaMartes"></canvas>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <canvas id="graficaMiercoles"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="graficaJueves"></canvas>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <canvas id="graficaViernes"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="graficaSabado"></canvas>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <canvas id="graficaDomingo"></canvas>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// Graficas de avance semanal
const graficas = [
    { id: 'graficaLunes', label: 'Lunes', data: <?php echo $grafica['Lunes']; ?> },
    { id: 'graficaMartes', label: 'Martes', data: <?php echo $grafica['Martes']; ?> },
    { id: 'graficaMiercoles', label: 'Miércoles', data: <?php echo $grafica['Miércoles']; ?> },
    { id: 'graficaJueves', label: 'Jueves', data: <?php echo $grafica['Jueves']; ?> },
    { id: 'graficaViernes', label: 'Viernes', data: <?php echo $grafica['Viernes']; ?> },
    { id: 'graficaSabado', label: 'Sábado', data: <?php echo $grafica['Sábado']; ?> },
    { id: 'graficaDomingo', label: 'Domingo', data: <?php echo $grafica['Domingo']; ?> }
];

graficas.forEach(grafica => {
    const canvas = document.getElementById(grafica.id);
    if (canvas) {
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [grafica.label],
                datasets: [{
                    label: 'Peso Promedio',
                    data: [grafica.data]
                }]
            }
        });
    }
});

    </script>
</body>
</html>
