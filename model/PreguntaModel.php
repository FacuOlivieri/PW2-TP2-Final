<?php

class PreguntaModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarTodasLasPreguntas(){
        $sql = "SELECT * FROM preguntas";
        return $this->database->query($sql);
    }

    public function buscarPreguntaSegunId($idPregunta){
        $sql = "SELECT * FROM preguntas WHERE id = ?";
        $resultado = $this->database->query($sql, [$idPregunta]);
        return $resultado ?? null;
    }


}