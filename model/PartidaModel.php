<?php

class PartidaModel
{

    private $database;

    public function __construct($dataBase)
    {
        $this->database = $dataBase;
    }


    public function alta($idJugador)
    {
        $sql = "INSERT INTO partidas (usuario_id)
                VALUES (?)";

        $this->database->execute($sql, [$idJugador]);
    }

    public function obtenerPartidaActual($usuarioId)
    {
        $sql = "SELECT id FROM partidas WHERE usuario_id = ?
        ORDER BY id DESC
        LIMIT 1
        ";

        $resultado = $this->database->query($sql, [$usuarioId]);

        return $resultado[0]["id"] ?? null;
    }

    public function finalizarPartida($idPartida, $puntaje)
    {
        $sql = "UPDATE partidas SET puntaje_total = ? WHERE id = ?";
        $this->database->execute($sql, [$puntaje, $idPartida]);
    }


}