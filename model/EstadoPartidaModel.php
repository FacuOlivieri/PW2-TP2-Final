<?php

class EstadoPartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function cargarPreguntaPartidaActualALaBD($idPartidaActual, $idPreguntaActual)
    {
        $sql = "INSERT INTO partida_preguntas (partida_id, pregunta_id)
                VALUES (?, ?)";

        $this->database->execute($sql, [$idPartidaActual, $idPreguntaActual]);
    }

    public function marcarRespuesta(
        $idPartida,
        $idPregunta,
        $idRespuesta,
        $esCorrecta,
        $tiempoRespuesta = null
    ) {
        $sql = "UPDATE partida_preguntas
                SET respuesta_id = ?, es_correcta = ?, tiempo_respuesta = ?
                WHERE partida_id = ? AND pregunta_id = ?";

        $this->database->execute($sql, [
            $idRespuesta,
            $esCorrecta,
            $tiempoRespuesta,
            $idPartida,
            $idPregunta
        ]);
    }

    public function obtenerHistorialPartida($idPartida)
    {
        $sql = "SELECT *
                FROM partida_preguntas
                WHERE partida_id = ?
                ORDER BY id ASC";

        return $this->database->query($sql, [$idPartida]);
    }
}