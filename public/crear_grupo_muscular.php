<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

include_once '../config/database.php';
include_once '../classes/GrupoMuscular.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$servicio = new GrupoMuscular($pdo);
$resultado = $servicio->crear($_POST['nombre'] ?? '');

if ($resultado['ok']) {
    $_SESSION['aviso'] = [
        'tipo' => 'success',
        'texto' => 'Se creó el grupo muscular ' . $resultado['nombre'] . '.',
    ];
} else {
    $mensajes = [
        'vacio' => 'Escribe un nombre para el grupo muscular.',
        'largo' => 'El nombre del grupo muscular es demasiado largo (máximo 100 caracteres).',
        'existe' => 'Ese grupo muscular ya existe.',
    ];
    $_SESSION['aviso'] = [
        'tipo' => 'danger',
        'texto' => $mensajes[$resultado['error']] ?? 'No se pudo crear el grupo muscular.',
    ];
}

header('Location: dashboard.php');
exit();
