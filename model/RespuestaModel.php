<?php

class RespuestaModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarRespuestaCorrectaALaPregunta($idPregunta){
        $sql = "SELECT * FROM respuestas WHERE pregunta_id = ? AND es_correcta = ?";
        $resultado = $this->database->query($sql, [$idPregunta, 1]);
        return !empty($resultado) ? $resultado[0] : null;
    }

    public function buscarRespuestasIncorrectasParaPregunta($idPregunta){
        $sql = "SELECT * FROM respuestas WHERE pregunta_id = ? AND es_correcta = ?";
        $resultado = $this->database->query($sql, [$idPregunta, 0]);
        return $resultado ?? null;
    }

    public function buscarRespuestaPorId($idRespuesta)
    {
        $sql = "SELECT * FROM respuestas WHERE id = ?";
        $resultado = $this->database->query($sql, [$idRespuesta]);

        return $resultado[0] ?? null;
    }


}