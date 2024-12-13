<?php
include_once '../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ejercicioId = $_POST['ejercicio_id'];
    $series = $_POST['series'];
    $repeticiones = $_POST['repeticiones'];
    $peso = $_POST['peso'];
    $usuario = $_SESSION['usuario'];

    // Verificar si ya hay datos para este ejercicio
    $stmt = $pdo->prepare("SELECT * FROM datos_ejercicio WHERE ejercicio_id = :ejercicio_id AND usuario = :usuario ORDER BY fecha DESC LIMIT 1");
    $stmt->execute(['ejercicio_id' => $ejercicioId, 'usuario' => $usuario]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Si hay datos, actualizarlos
        $stmt = $pdo->prepare("UPDATE datos_ejercicio SET series = :series, repeticiones = :repeticiones, peso = :peso, fecha = NOW() WHERE id = :id");
        $stmt->execute(['series' => $series, 'repeticiones' => $repeticiones, 'peso' => $peso, 'id' => $data['id']]);
        echo 'Datos actualizados con éxito.';
    } else {
        // Si no hay datos, insertarlos
        $stmt = $pdo->prepare("INSERT INTO datos_ejercicio (ejercicio_id, series, repeticiones, peso, fecha, usuario) VALUES (:ejercicio_id, :series, :repeticiones, :peso, NOW(), :usuario)");
        $stmt->execute(['ejercicio_id' => $ejercicioId, 'series' => $series, 'repeticiones' => $repeticiones, 'peso' => $peso, 'usuario' => $usuario]);
        echo 'Datos guardados con éxito.';
    }
}
?>
