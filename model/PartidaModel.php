<?php

class PartidaModel {
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

    public function obtenerPartidaActual($usuarioId) {
        $sql = "INSERT INTO partidas (usuario_id) VALUES (?)";
        $this->database->execute($sql, [$jugador["id"]]);

        $sql = "SELECT id FROM partidas
                WHERE usuario_id = ?
                ORDER BY id DESC
                LIMIT 1";

        $resultado = $this->database->query($sql, [$jugador["id"]]);

        // aun no exite partida terminada view: para no romper nada, no se guarda
        //$_SESSION["id_partida"] = $resultado[0]["id"];
        //$_SESSION["puntaje"] = 0;

        return $resultado[0]["id"] ?? null;
    }

    public function obtenerHistorialPorUsuario($usuarioId) {
        $sql = "SELECT id, puntaje_total, fecha_creacion
                FROM partidas WHERE usuario_id = ?
                ORDER BY fecha_creacion DESC
        ";

        return $this->database->query($sql, [$usuarioId]);
    }

    public function actualizarPuntajePartida($idPartida, $puntaje) {
        $sql = " UPDATE partidas
                SET puntaje_total = ?
                WHERE id = ?
        ";

        $this->database->execute(
            $sql, 
            [$puntaje,
            $idPartida]
        );
    }

}