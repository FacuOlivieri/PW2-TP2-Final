<?php

class PartidaModel
{

    private $database;

    public function __construct($dataBase)
    {
        $this->database = $dataBase;
    }


    private function alta($idJugador)
    {
        $sql = "INSERT INTO partidas (usuario_id,puntaje_total)
                VALUES (?, ?)";

        $this->database->execute($sql, [$idJugador, 0]);
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


}