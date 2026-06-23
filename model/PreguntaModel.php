<?php

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarTodasLasPreguntas()
    {
        $sql = "SELECT * FROM preguntas";
        return $this->database->query($sql);
    }

    public function buscarPreguntaSegunId($idPregunta)
    {
        $sql = "SELECT * FROM preguntas WHERE id = ?";
        $resultado = $this->database->query($sql, [$idPregunta]);

        return $resultado[0] ?? null;
    }

    public function buscarPreguntasPorCategoria($categoriaId)
    {
        $sql = "SELECT * FROM preguntas WHERE categoria_id = ?";
        return $this->database->query($sql, [$categoriaId]);
    }

    public function crear($texto, $categoriaId, $creadaPor = null)
    {
        $sql = "INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
                VALUES (?, ?, 'aprobada', ?)";

        $this->database->execute($sql, [$texto, $categoriaId, $creadaPor]);

        return $this->database->lastInsertId();
    }

    public function sumarPreguntasEntregadas($idPregunta)
    {
        $sql = "UPDATE preguntas
                SET veces_entregada = veces_entregada + 1
                WHERE id = ?";

        $this->database->execute($sql, [$idPregunta]);
    }

    public function sumarPreguntasCorrectas($idPregunta)
    {
        $sql = "UPDATE preguntas
                SET veces_correcta = veces_correcta + 1
                WHERE id = ?";

        $this->database->execute($sql, [$idPregunta]);
    }

    public function calcularDificultad($idPregunta)
    {
        $pregunta = $this->buscarPreguntaSegunId($idPregunta);

        if (!$pregunta) {
            return;
        }

        $entregadas = (int)$pregunta["veces_entregada"];
        $correctas = (int)$pregunta["veces_correcta"];

        if ($entregadas === 0) {
            return;
        }

        $porcentaje = ($correctas / $entregadas) * 100;

        if ($porcentaje >= 70) {
            $dificultad = "facil";
        } elseif ($porcentaje >= 30) {
            $dificultad = "medio";
        } else {
            $dificultad = "dificil";
        }

        $sql = "UPDATE preguntas
                SET dificultad = ?
                WHERE id = ?";

        $this->database->execute($sql, [$dificultad, $idPregunta]);
    }

    public function buscarPreguntasPorDificultad($dificultad)
    {
        $sql = "SELECT * FROM preguntas WHERE dificultad = ? ORDER BY RAND()";
        return $this->database->query($sql, [$dificultad]);
    }



    public function cantidadTotalPreguntas($filtroSeleccionado)
    {
        $condicionFecha = $this->obtenerCondicionFecha($filtroSeleccionado, 'fecha_creacion');

        $sql = "SELECT COUNT(*) as cantidad 
                FROM preguntas 
                WHERE estado IN ('activa', 'aprobada') $condicionFecha";

        $resultado = $this->database->query($sql);
        return $resultado[0]['cantidad'] ?? 0;
    }

    // 2. Cantidad de preguntas sugeridas o enviadas al sistema (independientemente de su estado actual)
    public function cantidadPreguntasCreadas($filtroSeleccionado)
    {
        $condicionFecha = $this->obtenerCondicionFecha($filtroSeleccionado, 'fecha_creacion');


        $sql = "SELECT COUNT(*) as cantidad 
                FROM preguntas 
                WHERE 1=1 $condicionFecha";

        $resultado = $this->database->query($sql);
        return $resultado[0]['cantidad'] ?? 0;
    }

    private function obtenerCondicionFecha($filtro, $campoFecha)
    {
        switch ($filtro) {
            case 'dia':
                return " AND $campoFecha >= DATE(NOW())";
            case 'semana':
                return " AND $campoFecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'anio':
                return " AND YEAR($campoFecha) = YEAR(NOW())";
            default:
                return "";
        }
    }
}