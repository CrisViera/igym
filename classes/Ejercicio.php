<?php
class Ejercicio {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function obtenerEjercicios() {
        $stmt = $this->pdo->prepare("SELECT * FROM ejercicios");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEjercicioPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ejercicios WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
