<?php
include_once '../config/database.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';

if ($query) {
    $stmt = $pdo->prepare("SELECT * FROM ejercicios WHERE nombre LIKE :query");
    $stmt->execute(['query' => '%' . $query . '%']);
    $ejercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ejercicios);
}
?>
