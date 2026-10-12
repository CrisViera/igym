<?php
class Tabla {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function asegurarEsquema() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS tablas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                coleccion VARCHAR(50) NOT NULL,
                dia VARCHAR(50) NULL,
                enfoque VARCHAR(255) NULL,
                descripcion TEXT NULL,
                UNIQUE KEY unique_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS tabla_ejercicios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tabla_id INT NOT NULL,
                ejercicio_id INT NOT NULL,
                bloque VARCHAR(100) NULL,
                orden INT NOT NULL DEFAULT 0,
                series INT NULL,
                rango VARCHAR(50) NULL,
                nota TEXT NULL,
                peso_minimo DECIMAL(8,2) NULL,
                KEY idx_tabla (tabla_id),
                KEY idx_ejercicio (ejercicio_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function instalarSemidioses($usuarioId = null) {
        $this->asegurarEsquema();
        $this->sembrarSemidioses();
        $this->copiarPesosMinimosDesdeDioses($usuarioId);
    }

    public function obtenerTablasPorColeccion($coleccion) {
        $stmt = $this->pdo->prepare("SELECT * FROM tablas WHERE coleccion = :coleccion ORDER BY id ASC");
        $stmt->execute(['coleccion' => $coleccion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTablaPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tablas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerEjerciciosDeTabla($tablaId) {
        $stmt = $this->pdo->prepare("
            SELECT te.*, e.nombre, e.grupo_muscular
            FROM tabla_ejercicios te
            INNER JOIN ejercicios e ON e.id = te.ejercicio_id
            WHERE te.tabla_id = :tabla_id
            ORDER BY te.orden ASC, te.id ASC
        ");
        $stmt->execute(['tabla_id' => $tablaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function copiarPesosMinimosDesdeDioses($usuarioId = null) {
        $pesosPorClave = $this->mapaPesosMinimosDioses($usuarioId);
        $semidioses = $this->obtenerTablasPorColeccion('semidioses');

        $update = $this->pdo->prepare("UPDATE tabla_ejercicios SET peso_minimo = :peso WHERE id = :id");

        foreach ($semidioses as $tabla) {
            $ejercicios = $this->obtenerEjerciciosDeTabla($tabla['id']);
            foreach ($ejercicios as $ejercicio) {
                $peso = $this->resolverPesoMinimo($ejercicio, $pesosPorClave, $usuarioId);
                if ($peso === null) {
                    continue;
                }
                $update->execute([
                    'peso' => $peso,
                    'id' => $ejercicio['id'],
                ]);
            }
        }
    }

    private function sembrarSemidioses() {
        foreach ($this->definicionSemidioses() as $tablaDef) {
            $stmt = $this->pdo->prepare("SELECT id FROM tablas WHERE slug = :slug");
            $stmt->execute(['slug' => $tablaDef['slug']]);
            $tablaId = $stmt->fetchColumn();

            if (!$tablaId) {
                $insert = $this->pdo->prepare("
                    INSERT INTO tablas (nombre, slug, coleccion, dia, enfoque, descripcion)
                    VALUES (:nombre, :slug, 'semidioses', :dia, :enfoque, :descripcion)
                ");
                $insert->execute([
                    'nombre' => $tablaDef['nombre'],
                    'slug' => $tablaDef['slug'],
                    'dia' => $tablaDef['dia'],
                    'enfoque' => $tablaDef['enfoque'],
                    'descripcion' => $tablaDef['descripcion'],
                ]);
                $tablaId = (int) $this->pdo->lastInsertId();
            } else {
                $update = $this->pdo->prepare("
                    UPDATE tablas
                    SET nombre = :nombre, dia = :dia, enfoque = :enfoque, descripcion = :descripcion
                    WHERE id = :id
                ");
                $update->execute([
                    'nombre' => $tablaDef['nombre'],
                    'dia' => $tablaDef['dia'],
                    'enfoque' => $tablaDef['enfoque'],
                    'descripcion' => $tablaDef['descripcion'],
                    'id' => $tablaId,
                ]);
            }

            $this->reemplazarEjerciciosDeTabla($tablaId, $tablaDef['ejercicios']);
        }
    }

    private function reemplazarEjerciciosDeTabla($tablaId, $ejercicios) {
        $this->pdo->prepare("DELETE FROM tabla_ejercicios WHERE tabla_id = :tabla_id")
            ->execute(['tabla_id' => $tablaId]);

        $insert = $this->pdo->prepare("
            INSERT INTO tabla_ejercicios (tabla_id, ejercicio_id, bloque, orden, series, rango, nota)
            VALUES (:tabla_id, :ejercicio_id, :bloque, :orden, :series, :rango, :nota)
        ");

        foreach ($ejercicios as $orden => $ejercicio) {
            $ejercicioId = $this->obtenerOCrearEjercicio($ejercicio['nombre'], $ejercicio['grupo']);
            $insert->execute([
                'tabla_id' => $tablaId,
                'ejercicio_id' => $ejercicioId,
                'bloque' => $ejercicio['bloque'],
                'orden' => $orden + 1,
                'series' => $ejercicio['series'],
                'rango' => $ejercicio['rango'],
                'nota' => $ejercicio['nota'],
            ]);
        }
    }

    private function obtenerOCrearEjercicio($nombre, $grupo) {
        $existente = $this->buscarEjercicio($nombre);
        if ($existente) {
            return (int) $existente['id'];
        }

        $insert = $this->pdo->prepare("INSERT INTO ejercicios (nombre, grupo_muscular) VALUES (:nombre, :grupo)");
        $insert->execute([
            'nombre' => $nombre,
            'grupo' => $grupo,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function buscarEjercicio($nombre) {
        $stmt = $this->pdo->query("SELECT id, nombre, grupo_muscular FROM ejercicios");
        $ejercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $objetivo = $this->normalizar($nombre);
        $alias = $this->aliasDe($nombre);

        foreach ($ejercicios as $ejercicio) {
            $candidato = $this->normalizar($ejercicio['nombre']);
            if ($candidato === $objetivo || in_array($candidato, $alias, true)) {
                return $ejercicio;
            }
        }

        foreach ($ejercicios as $ejercicio) {
            $candidato = $this->normalizar($ejercicio['nombre']);
            if ($this->nombresParecidos($objetivo, $candidato)) {
                return $ejercicio;
            }
        }

        return null;
    }

    private function mapaPesosMinimosDioses($usuarioId = null) {
        $mapa = [];
        $tablasDioses = $this->obtenerTablasDioses();

        foreach ($tablasDioses as $tabla) {
            $ejercicios = $this->obtenerEjerciciosDeTabla($tabla['id']);
            foreach ($ejercicios as $ejercicio) {
                $peso = $ejercicio['peso_minimo'];
                if ($peso === null || $peso === '') {
                    $peso = $this->pesoMinimoDeRegistros($ejercicio['ejercicio_id'], $usuarioId);
                }
                if ($peso === null) {
                    continue;
                }

                $claves = array_merge(
                    [$this->normalizar($ejercicio['nombre'])],
                    $this->aliasDe($ejercicio['nombre']),
                    ['id:' . $ejercicio['ejercicio_id']]
                );
                foreach ($claves as $clave) {
                    if (!isset($mapa[$clave])) {
                        $mapa[$clave] = (float) $peso;
                    }
                }
            }
        }

        return $mapa;
    }

    private function obtenerTablasDioses() {
        $stmt = $this->pdo->query("
            SELECT * FROM tablas
            WHERE coleccion = 'dioses'
               OR (
                    coleccion <> 'semidioses'
                    AND (
                        slug LIKE '%dioses%'
                        OR nombre LIKE '%dioses%'
                    )
                    AND slug NOT LIKE '%semidioses%'
                    AND nombre NOT LIKE '%semidioses%'
               )
            ORDER BY id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function resolverPesoMinimo($ejercicio, $pesosPorClave, $usuarioId = null) {
        $claves = array_merge(
            ['id:' . $ejercicio['ejercicio_id']],
            [$this->normalizar($ejercicio['nombre'])],
            $this->aliasDe($ejercicio['nombre'])
        );

        foreach ($claves as $clave) {
            if (isset($pesosPorClave[$clave])) {
                return $pesosPorClave[$clave];
            }
        }

        foreach ($pesosPorClave as $clave => $peso) {
            if (strpos($clave, 'id:') === 0) {
                continue;
            }
            if ($this->nombresParecidos($this->normalizar($ejercicio['nombre']), $clave)) {
                return $peso;
            }
        }

        return $this->pesoMinimoDeRegistros($ejercicio['ejercicio_id'], $usuarioId);
    }

    private function pesoMinimoDeRegistros($ejercicioId, $usuarioId = null) {
        $sql = "SELECT MIN(peso) FROM registro_ejercicios WHERE ejercicio_id = :ejercicio_id AND peso > 0";
        $params = ['ejercicio_id' => $ejercicioId];
        if ($usuarioId) {
            $sql .= " AND usuario_id = :usuario_id";
            $params['usuario_id'] = $usuarioId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $peso = $stmt->fetchColumn();
        return $peso !== false && $peso !== null ? (float) $peso : null;
    }

    private function nombresParecidos($a, $b) {
        if ($a === '' || $b === '') {
            return false;
        }

        $tokensA = array_values(array_filter(explode(' ', $a), function ($token) {
            return strlen($token) > 3;
        }));
        $tokensB = array_values(array_filter(explode(' ', $b), function ($token) {
            return strlen($token) > 3;
        }));
        if (count($tokensA) < 2 || count($tokensB) < 2) {
            return false;
        }

        $comunes = array_intersect($tokensA, $tokensB);
        $umbral = min(count($tokensA), count($tokensB));
        return count($comunes) >= max(2, (int) ceil($umbral * 0.7));
    }

    private function aliasDe($nombre) {
        $mapa = [
            'press inclinado con mancuernas' => ['inclinado con mancuernas', 'press de banca inclinado'],
            'press plano con mancuernas' => ['plano con mancuernas', 'press banca con mancuernas'],
            'dominadas pronas agarre ancho' => ['dominadas'],
            'remo con barra prono agarre ancho' => ['remo con barra'],
            'curl martillo en polea con cuerda' => ['martillo en polea', 'curl con cuerda', 'martillo'],
            'extensiones polea alta con cuerda' => ['extensiones en polea', 'extensiones polea 1', 'extensiones polea desde atras'],
            'elevaciones de piernas colgado' => ['elevaciones de piernas en banco', 'elevaciones de pies en maquina'],
            'crunches abdominales en polea' => ['crunches', 'crunches abajo en maquina'],
            'plancha isometrica' => ['plancha'],
            'sentadilla con barra libre' => ['sentadillas con barra', 'sentadilla en multi power'],
            'sentadilla jaca hack squat' => ['jacas', 'sentadillas en maquina'],
            'elevaciones laterales con mancuernas' => ['elevaciones laterales'],
            'extensiones de cuadriceps' => ['extensiones'],
            'pajaros con mancuernas' => ['pajaros con mancuernas'],
            'curl de piernas tumbado' => ['curl femoral acostado', 'curl de piernas'],
            'gemelos de pie en maquina' => ['gemelos de pie', 'elevaciones de talones de pie'],
            'gemelos sentado en maquina' => ['gemelos sentado', 'elevaciones de talones sentado'],
            'press inclinado en multipower' => ['press de banca inclinado con barra', 'inclinado en maquina', 'plano en multi power'],
            'press de banca plano con barra libre' => ['press de banca con barra', 'plano en multi power'],
            'jalon al pecho con barra ancha' => ['jalon al pecho agarre abierto'],
            'remo sentado en polea con triangulo' => ['remo en polea agarre cerrado', 'remo en maquina agarre cerrado'],
            'press frances con barra z' => ['press frances barra z'],
            'curl con barra z agarre ancho' => ['curl barra z', 'curls con barra ez'],
            'elevaciones de pelvis en banco' => ['hip trust'],
            'crunches banco declinado con disco' => ['crunches'],
            'rodillo abdominal ab wheel' => ['rodillo'],
            'peso muerto rumano con barra' => ['peso muerto rumano', 'peso muerto en barra'],
            'prensa de piernas de 45' => ['prensa'],
            'elevaciones laterales en polea baja' => ['elevaciones unilaterales en polea', 'elevaciones laterales polea con ambos brazos'],
            'curl de piernas sentado' => ['curl femoral sentado'],
            'pajaros en maquina peck deck inverso' => ['posterior en maquina', 'posterior en polea'],
            'gemelos burro donkey calf raises' => ['gemelos burrito'],
        ];

        $clave = $this->normalizar($nombre);
        return $mapa[$clave] ?? [];
    }

    private function normalizar($texto) {
        $texto = trim((string) $texto);
        $texto = function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
        $reemplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        ];
        $texto = strtr($texto, $reemplazos);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    private function definicionSemidioses() {
        return [
            [
                'nombre' => 'Semidioses — Lunes: Torso A',
                'slug' => 'semidioses-lunes-torso-a',
                'dia' => 'Lunes',
                'enfoque' => 'Libertad de movimiento y poleas altas',
                'descripcion' => 'Torso A. Prioridad de pecho inclinado, tirón vertical y horizontal, y core.',
                'ejercicios' => [
                    ['nombre' => 'Press Inclinado con Mancuernas (15-30°)', 'grupo' => 'Pecho', 'bloque' => 'Pecho', 'series' => 3, 'rango' => '8-12 rep', 'nota' => 'Prioridad superior. Máximo estiramiento abajo.'],
                    ['nombre' => 'Press Plano con Mancuernas', 'grupo' => 'Pecho', 'bloque' => 'Pecho', 'series' => 2, 'rango' => '8-12 rep', 'nota' => 'Masa global del pectoral.'],
                    ['nombre' => 'Dominadas Pronas (Agarre Ancho)', 'grupo' => 'Espalda', 'bloque' => 'Espalda', 'series' => 2, 'rango' => '8-12 rep', 'nota' => 'Tirón vertical ancho. Expansión y amplitud.'],
                    ['nombre' => 'Remo con Barra Prono (Agarre Ancho)', 'grupo' => 'Espalda', 'bloque' => 'Espalda', 'series' => 3, 'rango' => '8-10 rep', 'nota' => 'Tirón horizontal. Barra a la boca del estómago para grosor.'],
                    ['nombre' => 'Curl Martillo en Polea con Cuerda', 'grupo' => 'Bíceps', 'bloque' => 'Brazos', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Braquial.'],
                    ['nombre' => 'Extensiones Polea Alta con Cuerda', 'grupo' => 'Tríceps', 'bloque' => 'Brazos', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Tríceps (cabeza lateral).'],
                    ['nombre' => 'Elevaciones de Piernas Colgado', 'grupo' => 'Abdomen', 'bloque' => 'Abdomen', 'series' => 2, 'rango' => '8-12 rep', 'nota' => 'Foco Inferior. Piernas rectas; frena bajada 3s.'],
                    ['nombre' => 'Crunches Abdominales en polea', 'grupo' => 'Abdomen', 'bloque' => 'Abdomen', 'series' => 1, 'rango' => '15-20 rep', 'nota' => 'Foco Superior.'],
                    ['nombre' => 'Plancha Isométrica', 'grupo' => 'Abdomen', 'bloque' => 'Abdomen', 'series' => 1, 'rango' => '30-45 seg', 'nota' => 'Foco Core/Estabilidad.'],
                ],
            ],
            [
                'nombre' => 'Semidioses — Martes: Pierna + Core A',
                'slug' => 'semidioses-martes-pierna-core-a',
                'dia' => 'Martes',
                'enfoque' => 'Día dominante de cuádriceps',
                'descripcion' => 'Pierna + Core A. Cuádriceps como prioridad, hombro lateral/posterior y gemelos.',
                'ejercicios' => [
                    ['nombre' => 'Sentadilla con barra libre', 'grupo' => 'Piernas', 'bloque' => 'Cuádriceps', 'series' => 2, 'rango' => '8-12 rep', 'nota' => 'Baja profundo para cuádriceps y glúteo.'],
                    ['nombre' => 'Sentadilla Jaca (Hack Squat)', 'grupo' => 'Piernas', 'bloque' => 'Cuádriceps', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Base pesada. Baja profundo para cuádriceps y glúteo.'],
                    ['nombre' => 'Elevaciones Laterales con Mancuernas', 'grupo' => 'Hombros', 'bloque' => 'Hombro', 'series' => 3, 'rango' => '10-12 rep', 'nota' => 'Anchura directa (forma en V).'],
                    ['nombre' => 'Extensiones de Cuádriceps', 'grupo' => 'Piernas', 'bloque' => 'Cuádriceps', 'series' => 2, 'rango' => '12-15 rep', 'nota' => 'Aislamiento puro y remate metabólico.'],
                    ['nombre' => 'Pájaros con Mancuernas', 'grupo' => 'Hombros', 'bloque' => 'Hombro', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Aislamiento puro del deltoides posterior.'],
                    ['nombre' => 'Curl de Piernas Tumbado', 'grupo' => 'Piernas', 'bloque' => 'Isquios', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Aislamiento por flexión de rodilla. Contracción 1s.'],
                    ['nombre' => 'Gemelos de Pie en Máquina', 'grupo' => 'Piernas', 'bloque' => 'Gemelos', 'series' => 3, 'rango' => '10-12 rep', 'nota' => 'Primero de pie (Gastrocnemio). Estira 1 seg abajo.'],
                    ['nombre' => 'Gemelos Sentado en Máquina', 'grupo' => 'Piernas', 'bloque' => 'Gemelos', 'series' => 2, 'rango' => '12 rep', 'nota' => 'Segundo sentado (Sóleo). Máxima comodidad.'],
                ],
            ],
            [
                'nombre' => 'Semidioses — Jueves: Torso B',
                'slug' => 'semidioses-jueves-torso-b',
                'dia' => 'Jueves',
                'enfoque' => 'Sobrecarga pesada con barras y poleas bajas',
                'descripcion' => 'Torso B. Prioridad de press inclinado en multipower, jalón y remo estrecho.',
                'ejercicios' => [
                    ['nombre' => 'Press Inclinado en Multipower', 'grupo' => 'Pecho', 'bloque' => 'Pecho', 'series' => 3, 'rango' => '8-10 rep', 'nota' => 'Máxima estabilidad en tu prioridad. Barra a pecho superior.'],
                    ['nombre' => 'Press de Banca Plano con Barra Libre', 'grupo' => 'Pecho', 'bloque' => 'Pecho', 'series' => 2, 'rango' => '8-10 rep', 'nota' => 'Constructor de masa global y trabajo de estabilizadores.'],
                    ['nombre' => 'Jalón al Pecho con Barra Ancha', 'grupo' => 'Espalda', 'bloque' => 'Espalda', 'series' => 3, 'rango' => '8-12 rep', 'nota' => 'Aislamiento estricto del dorsal ancho.'],
                    ['nombre' => 'Remo Sentado en polea con Triángulo', 'grupo' => 'Espalda', 'bloque' => 'Espalda', 'series' => 2, 'rango' => '8-10 rep', 'nota' => 'Tirón horizontal estrecho. Codos pegados para densidad baja.'],
                    ['nombre' => 'Press Francés con Barra Z', 'grupo' => 'Tríceps', 'bloque' => 'Brazos', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Tríceps (cabeza larga).'],
                    ['nombre' => 'Curl con Barra Z (Agarre Ancho)', 'grupo' => 'Bíceps', 'bloque' => 'Brazos', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Bícep (cabeza corta).'],
                    ['nombre' => 'Elevaciones de Pelvis en Banco', 'grupo' => 'Abdomen', 'bloque' => 'Abdomen', 'series' => 2, 'rango' => '12-15 rep', 'nota' => 'Foco Inferior. Despega zona lumbar de forma controlada.'],
                    ['nombre' => 'Crunches Banco Declinado con Disco', 'grupo' => 'Abdomen', 'bloque' => 'Abdomen', 'series' => 1, 'rango' => '12-15 rep', 'nota' => 'Foco Superior. Disco ligero al pecho para sobrecarga.'],
                    ['nombre' => 'Rodillo Abdominal (Ab Wheel)', 'grupo' => 'Abdomen', 'bloque' => 'Abdomen', 'series' => 1, 'rango' => 'Al fallo', 'nota' => 'Foco Core/Estabilidad. No arquees la espalda baja.'],
                ],
            ],
            [
                'nombre' => 'Semidioses — Viernes: Pierna + Core B',
                'slug' => 'semidioses-viernes-pierna-core-b',
                'dia' => 'Viernes',
                'enfoque' => 'Día dominante de isquiotibiales',
                'descripcion' => 'Pierna + Core B. Bisagra de cadera, prensa profunda y remate de hombro y gemelos.',
                'ejercicios' => [
                    ['nombre' => 'Peso Muerto Rumano con Barra', 'grupo' => 'Piernas', 'bloque' => 'Isquios', 'series' => 3, 'rango' => '8-10 rep', 'nota' => 'Bisagra libre. Estiramiento masivo de isquios y glúteos.'],
                    ['nombre' => 'Prensa de Piernas de 45°', 'grupo' => 'Piernas', 'bloque' => 'Cuádriceps', 'series' => 3, 'rango' => '10-12 rep', 'nota' => 'Baja profundo sin despegar el glúteo del asiento.'],
                    ['nombre' => 'Elevaciones Laterales en Polea Baja', 'grupo' => 'Hombros', 'bloque' => 'Hombro', 'series' => 3, 'rango' => '12-15 rep', 'nota' => 'Polea al muslo por detrás del cuerpo. Tensión constante.'],
                    ['nombre' => 'Curl de Piernas Sentado', 'grupo' => 'Piernas', 'bloque' => 'Isquios', 'series' => 2, 'rango' => '10-12 rep', 'nota' => 'Aislamiento de isquiotibiales por flexión en máquina.'],
                    ['nombre' => 'Pájaros en Máquina (Peck Deck Inverso)', 'grupo' => 'Hombros', 'bloque' => 'Hombro', 'series' => 2, 'rango' => '12 rep', 'nota' => 'Aislamiento puro de la parte posterior del hombro.'],
                    ['nombre' => 'Gemelos Burro (Donkey Calf Raises)', 'grupo' => 'Piernas', 'bloque' => 'Gemelos', 'series' => 3, 'rango' => '10-12 rep', 'nota' => 'Primero de pie e inclinado. El rey del estiramiento alto.'],
                    ['nombre' => 'Gemelos Sentado en Máquina', 'grupo' => 'Piernas', 'bloque' => 'Gemelos', 'series' => 2, 'rango' => '12-15 rep', 'nota' => 'Segundo sentado para rematar el sóleo.'],
                ],
            ],
        ];
    }
}
