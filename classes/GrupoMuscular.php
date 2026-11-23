<?php
class GrupoMuscular {
    private $pdo;

    private $predeterminados = [
        'Pecho',
        'Espalda',
        'Piernas',
        'Hombros',
        'Bíceps',
        'Tríceps',
        'Abdomen',
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function asegurarEsquema() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS grupos_musculares (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                UNIQUE KEY unique_nombre (nombre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $insert = $this->pdo->prepare("INSERT IGNORE INTO grupos_musculares (nombre) VALUES (:nombre)");
        foreach ($this->predeterminados as $nombre) {
            $insert->execute(['nombre' => $nombre]);
        }

        try {
            $existentes = $this->pdo->query("
                SELECT DISTINCT TRIM(grupo_muscular) AS nombre
                FROM ejercicios
                WHERE grupo_muscular IS NOT NULL AND TRIM(grupo_muscular) <> ''
            ");
            foreach ($existentes->fetchAll(PDO::FETCH_COLUMN) as $nombre) {
                $nombre = $this->limpiar($nombre);
                if ($nombre === '') {
                    continue;
                }
                $insert->execute(['nombre' => $nombre]);
            }
        } catch (PDOException $e) {
            // La tabla de ejercicios puede no existir todavía en una instalación nueva.
        }
    }

    public function listar() {
        $this->asegurarEsquema();
        $stmt = $this->pdo->query("SELECT id, nombre FROM grupos_musculares ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function canonico($nombre) {
        $this->asegurarEsquema();
        $nombre = $this->limpiar($nombre);
        if ($nombre === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT nombre FROM grupos_musculares WHERE nombre = :nombre LIMIT 1");
        $stmt->execute(['nombre' => $nombre]);
        $encontrado = $stmt->fetchColumn();

        return $encontrado !== false ? $encontrado : null;
    }

    public function crear($nombre) {
        $this->asegurarEsquema();
        $nombre = $this->limpiar($nombre);

        if ($nombre === '') {
            return ['ok' => false, 'error' => 'vacio'];
        }

        $longitud = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
        if ($longitud > 100) {
            return ['ok' => false, 'error' => 'largo'];
        }

        if ($this->canonico($nombre) !== null) {
            return ['ok' => false, 'error' => 'existe'];
        }

        try {
            $insert = $this->pdo->prepare("INSERT INTO grupos_musculares (nombre) VALUES (:nombre)");
            $insert->execute(['nombre' => $nombre]);
        } catch (PDOException $e) {
            $codigo = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
            if ($codigo === 1062) {
                return ['ok' => false, 'error' => 'existe'];
            }
            throw $e;
        }

        return ['ok' => true, 'nombre' => $nombre];
    }

    public function agruparEjercicios(array $ejercicios) {
        $grupos = [];
        $indice = [];

        foreach ($this->listar() as $grupo) {
            $grupos[$grupo['nombre']] = [];
            $indice[$this->clave($grupo['nombre'])] = $grupo['nombre'];
        }

        foreach ($ejercicios as $ejercicio) {
            $bruto = $this->limpiar($ejercicio['grupo_muscular'] ?? '');
            $clave = $this->clave($bruto);
            if ($clave !== '' && isset($indice[$clave])) {
                $grupos[$indice[$clave]][] = $ejercicio;
                continue;
            }

            if ($bruto === '') {
                $bruto = 'Sin grupo';
            }
            if (!isset($grupos[$bruto])) {
                $grupos[$bruto] = [];
            }
            $grupos[$bruto][] = $ejercicio;
        }

        foreach ($grupos as $nombre => $items) {
            if ($items === [] && $this->esPredeterminado($nombre)) {
                unset($grupos[$nombre]);
            }
        }

        return $grupos;
    }

    private function esPredeterminado($nombre) {
        $clave = $this->clave($nombre);
        foreach ($this->predeterminados as $base) {
            if ($this->clave($base) === $clave) {
                return true;
            }
        }
        return false;
    }

    private function limpiar($nombre) {
        $nombre = trim((string) $nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);
        return $nombre === null ? '' : $nombre;
    }

    private function clave($nombre) {
        $nombre = $this->limpiar($nombre);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($nombre, 'UTF-8');
        }
        return strtolower($nombre);
    }
}
