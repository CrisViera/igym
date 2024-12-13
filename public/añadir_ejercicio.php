<?php
// Conexión
include_once '../config/database.php';

try {

    // Validar datos recibidos del formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre']);
        $grupo_muscular = trim($_POST['grupo_muscular']);

        if (!empty($nombre) && !empty($grupo_muscular)) {
            // Preparar e insertar en la base de datos
            $sql = "INSERT INTO ejercicios (nombre, grupo_muscular) VALUES (:nombre, :grupo_muscular)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':grupo_muscular', $grupo_muscular);

            if ($stmt->execute()) {
                echo "Ejercicio añadido correctamente.";
                header("Location: dashboard.php");
            } else {
                echo "Error al añadir el ejercicio.";
            }
        } else {
            echo "Todos los campos son obligatorios.";
        }
    }
} catch (PDOException $e) {
    echo "Error en la conexión: " . $e->getMessage();
}
?>
