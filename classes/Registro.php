<?php
class Registro {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function registrarEjercicio($usuario_id, $ejercicio_id, $series, $repeticiones, $peso, $fecha) {
        $stmt = $this->pdo->prepare("INSERT INTO registro_ejercicios (usuario_id, ejercicio_id, series, repeticiones, peso, fecha) VALUES (:usuario_id, :ejercicio_id, :series, :repeticiones, :peso, :fecha)");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':ejercicio_id', $ejercicio_id);
        $stmt->bindParam(':series', $series);
        $stmt->bindParam(':repeticiones', $repeticiones);
        $stmt->bindParam(':peso', $peso);
        $stmt->bindParam(':fecha', $fecha);
        return $stmt->execute();
    }

    public function obtenerRegistros($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM registro_ejercicios WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
