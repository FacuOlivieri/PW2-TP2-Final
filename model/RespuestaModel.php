<?php

class RespuestaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarRespuestaCorrectaALaPregunta($idPregunta)
    {
        $sql = "SELECT *
                FROM respuestas 
                WHERE pregunta_id = ? AND es_correcta = 1
                LIMIT 1";

        $resultado = $this->database->query($sql, [$idPregunta]);

        return $resultado[0] ?? null;
    }

    public function buscarRespuestasIncorrectasParaPregunta($idPregunta)
    {
        $sql = "SELECT *
                FROM respuestas 
                WHERE pregunta_id = ? AND es_correcta = 0
                ORDER BY RAND()
                LIMIT 3";

        return $this->database->query($sql, [$idPregunta]);
    }

    public function buscarRespuestaPorId($idRespuesta)
    {
        $sql = "SELECT *
                FROM respuestas 
                WHERE id = ?";

        $resultado = $this->database->query($sql, [$idRespuesta]);

        return $resultado[0] ?? null;
    }

    public function crear($preguntaId, $texto, $esCorrecta)
    {
        $sql = "INSERT INTO respuestas (pregunta_id, texto, es_correcta)
                VALUES (?, ?, ?)";

        $this->database->execute($sql, [$preguntaId, $texto, $esCorrecta ? 1 : 0]);
    }

    public function buscarRespuestasPorPregunta($preguntaId)
    {
        $sql = "SELECT *
                FROM respuestas
                WHERE pregunta_id = ?
                ORDER BY id ASC";

        return $this->database->query($sql, [$preguntaId]);
    }

    public function reemplazarRespuestas($preguntaId, $respuestas, $indiceCorrecta)
    {
        $sql = "DELETE FROM respuestas WHERE pregunta_id = ?";
        $this->database->execute($sql, [$preguntaId]);

        foreach ($respuestas as $indice => $texto) {
            $this->crear($preguntaId, $texto, ($indice + 1) === $indiceCorrecta);
        }
    }
}
