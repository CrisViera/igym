<?php
include_once '../config/database.php'; 

if (isset($_POST['ejercicio_id'])) {
    $ejercicioId = $_POST['ejercicio_id'];
    $stmt = $pdo->prepare("SELECT * FROM datos_ejercicio WHERE ejercicio_id = :ejercicio_id AND usuario = :usuario ORDER BY fecha DESC LIMIT 1");
    $stmt->execute(['ejercicio_id' => $ejercicioId, 'usuario' => $_SESSION['usuario']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['id' => '', 'series' => '', 'repeticiones' => '', 'peso' => '']);
    }
}
?>
