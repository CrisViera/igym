<?php
include_once '../config/database.php'; 

if (isset($_POST['ejercicio_id'])) {
    $ejercicioId = $_POST['ejercicio_id'];
    $stmt = $pdo->prepare("
        SELECT 
            DAYOFWEEK(fecha) AS dia_semana,
            AVG(peso) AS promedio_peso
        FROM datos_ejercicio
        WHERE ejercicio_id = :ejercicio_id AND usuario = :usuario
        GROUP BY dia_semana
    ");
    $stmt->execute(['ejercicio_id' => $ejercicioId, 'usuario' => $_SESSION['usuario']]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pesos = array_fill(0, 7, 0); // Inicializar con ceros (para cada día de la semana)

    foreach ($result as $row) {
        $pesos[$row['dia_semana'] - 1] = $row['promedio_peso']; // Asignar el promedio al día correspondiente
    }

    echo json_encode(['pesos' => $pesos]);
}
?>
