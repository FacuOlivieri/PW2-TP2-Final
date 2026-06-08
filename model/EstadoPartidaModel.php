<?php

class EstadoPartidaModel
{

    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function cargarPreguntaPartidaActualALaBD($idPartidaActual, $idPreguntaActual){
        $sql = "INSERT INTO partida_preguntas (partida_id, pregunta_id) VALUES (?, ?)";
        $this->database->execute($sql, [$idPartidaActual,$idPreguntaActual]);
    }
}