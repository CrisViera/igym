<?php 
include_once '../config/database.php'; 

// Verificar si el usuario está autenticado
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$_SESSION['ultimo_ejercicio'] = $_GET['ejercicio_id'];

$usuario_id = $_SESSION['usuario_id'];
$ejercicio_id = $_GET['ejercicio_id']; // Obtenemos el ID del ejercicio desde la URL

// Obtener los datos del ejercicio
$stmt = $pdo->prepare("SELECT * FROM ejercicios WHERE id = :ejercicio_id");
$stmt->execute(['ejercicio_id' => $ejercicio_id]);
$ejercicio = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener los registros de este ejercicio
$fechaHoy = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM registro_ejercicios WHERE usuario_id = :usuario_id AND ejercicio_id = :ejercicio_id AND fecha = :fecha");
$stmt->execute(['usuario_id' => $usuario_id, 'ejercicio_id' => $ejercicio_id, 'fecha' => $fechaHoy]);
$ejercicioSeleccionado = $stmt->fetch(PDO::FETCH_ASSOC);

// Guardar o actualizar los datos de repeticiones, series y peso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardarDatos'])) {
    $series = $_POST['series'];
    $repeticiones = $_POST['repeticiones'];
    $peso = $_POST['peso'];

    $stmt = $pdo->prepare("INSERT INTO registro_ejercicios (usuario_id, ejercicio_id, series, repeticiones, peso, fecha) VALUES (:usuario_id, :ejercicio_id, :series, :repeticiones, :peso, :fecha)");
    $stmt->execute(['usuario_id' => $usuario_id, 'ejercicio_id' => $ejercicio_id, 'series' => $series, 'repeticiones' => $repeticiones, 'peso' => $peso, 'fecha' => $fechaHoy]);
    $_SESSION['id_ejercicio'] = $ejercicio_id;

}
    // Obtener los registros de este ejercicio ordenados por fecha
    $stmt = $pdo->prepare("SELECT id, fecha, series, repeticiones, peso FROM registro_ejercicios WHERE usuario_id = :usuario_id AND ejercicio_id = :ejercicio_id ORDER BY fecha ASC");
    $stmt->execute(['usuario_id' => $usuario_id, 'ejercicio_id' => $ejercicio_id]);
    $registrosEjercicio = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener los datos de los pesos del ejercicio en los últimos 30 días
    $stmt = $pdo->prepare("SELECT fecha, peso FROM registro_ejercicios WHERE usuario_id = :usuario_id AND ejercicio_id = :ejercicio_id AND fecha >= CURDATE() - INTERVAL 30 DAY");
    $stmt->execute(['usuario_id' => $usuario_id, 'ejercicio_id' => $ejercicio_id]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar los datos para la gráfica
    $fechas = [];
    $pesos = [];

    foreach ($registros as $registro) {
        // Formatear la fecha a "d/m/Y" (día/mes/año)
        $fechas[] = date('j/n/Y', strtotime($registro['fecha']));
        $pesos[] = $registro['peso'];
    }
    
    


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Ejercicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Registro de <?php echo $ejercicio['nombre']; ?></h1>
        <!-- Botón para regresar al dashboard -->
        <a href="dashboard.php" class="btn btn-secondary mb-3 d-block mx-auto text-center">Volver al Dashboard</a>
        
        <!-- Formulario de registro -->
        <form method="POST" action="ejercicio_detalle.php?ejercicio_id=<?php echo $ejercicio_id; ?>">
            <input type="hidden" name="ejercicio_id" value="<?php echo $ejercicio_id; ?>">
            <div class="mb-3">
                <label for="series" class="form-label">Series</label>
                <input type="number" class="form-control" id="series" name="series" required>
            </div>
            <div class="mb-3">
                <label for="repeticiones" class="form-label">Repeticiones</label>
                <input type="number" class="form-control" id="repeticiones" name="repeticiones" required>
            </div>
            <div class="mb-3">
                <label for="peso" class="form-label">Peso</label>
                <input type="number" class="form-control" id="peso" name="peso" required autocomplete="off">
            </div>
            <button type="submit" name="guardarDatos" class="btn btn-primary d-block mx-auto text-center">Guardar</button>
        </form>

        <!-- Tabla de registros de ejercicios -->
        <h2 class="mt-5 mb-5 text-center">Rendimiento</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Series</th>
                    <th>Repeticiones</th>
                    <th>Peso (kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrosEjercicio as $registro): ?>
                    <tr>
                        <td><?php echo $registro['fecha']; ?></td>
                        <td><?php echo $registro['series']; ?></td>
                        <td><?php echo $registro['repeticiones']; ?></td>
                        <td><?php echo $registro['peso']; ?> kg</td>
                        <td>
                    <form action="eliminar_registro.php" method="post" class="d-inline">
                        <input type="hidden" name="registro_id" value="<?php echo $registro['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?')">Eliminar</button>
                    </form>
                </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Gráfica de progreso -->
        <canvas id="pesoChart" width="400" height="200"></canvas>
        <script>
            // Datos para la gráfica
            const fechas = <?php echo json_encode($fechas); ?>;
            const pesos = <?php echo json_encode($pesos); ?>;

            // Crear la gráfica con Chart.js
            const ctx = document.getElementById('pesoChart').getContext('2d');
            const pesoChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: fechas, // Fechas obtenidas de la base de datos
                    datasets: [{
                        label: 'Peso (kg)',
                        data: pesos, // Pesos obtenidos de la base de datos
                        borderColor: 'rgba(75, 192, 192, 1)', // Color de la línea
                        fill: false, // No rellenar el área bajo la línea
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Fecha'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Peso (kg)'
                            },
                            beginAtZero: false
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>
