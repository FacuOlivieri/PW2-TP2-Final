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



}