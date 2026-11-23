<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Conexión
include_once '../config/database.php';
include_once '../classes/GrupoMuscular.php';

try {

    // Validar datos recibidos del formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = trim($_POST['nombre']);
        $grupo_muscular = trim($_POST['grupo_muscular']);
        $grupoCanonico = (new GrupoMuscular($pdo))->canonico($grupo_muscular);

        if ($grupo_muscular !== '' && $grupoCanonico === null) {
            $_SESSION['aviso'] = [
                'tipo' => 'danger',
                'texto' => 'Elige un grupo muscular de la lista o crea uno nuevo.',
            ];
            header('Location: dashboard.php');
            exit();
        }

        if ($grupoCanonico !== null) {
            $grupo_muscular = $grupoCanonico;
        }

        if (!empty($nombre) && !empty($grupo_muscular)) {
            // Preparar e insertar en la base de datos
            $sql = "INSERT INTO ejercicios (nombre, grupo_muscular) VALUES (:nombre, :grupo_muscular)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':grupo_muscular', $grupo_muscular);

            if ($stmt->execute()) {
                $_SESSION['aviso'] = [
                    'tipo' => 'success',
                    'texto' => 'Ejercicio añadido correctamente.',
                ];
                header("Location: dashboard.php");
                exit();
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
