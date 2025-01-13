<?php
// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'igym';
$username = 'User_Igym';
$password = 'Aspire_5735z';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Configurar el modo de error a excepción
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Error de conexión: ' . $e->getMessage();
}
?>
