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

    public function buscarRespuestasPorPregunta($idPregunta)
    {
        $sql = "SELECT *
                FROM respuestas
                WHERE pregunta_id = ?";

        return $this->database->query($sql, [$idPregunta]);
    }
}