<?php
include_once '../config/database.php';
session_start();
// Verificar si se recibe el ID del ejercicio
if (isset($_POST['registro_id'])) {
    $registro_id = $_POST['registro_id'];

    // Eliminar el ejercicio de la base de datos
    $stmt = $pdo->prepare("DELETE FROM registro_ejercicios WHERE id = :registro_id");
    $stmt->execute(['registro_id' => $registro_id]);

    // Redirigir de vuelta al listado de ejercicios
    header("Location: ejercicio_detalle.php?ejercicio_id=" . $_SESSION['id_ejercicio']); // Ajusta la URL a tu página de listado
    exit();
} else {
    // Si no se pasa el ID, redirigir o mostrar un error
    echo "Error: No se especificó el ID del ejercicio.";
}
?>
