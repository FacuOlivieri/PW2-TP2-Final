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
        $sql = "INSERT INTO partidas (usuario_id, puntaje_total, fecha_creacion)
                VALUES (?, 0, NOW())";

        $this->database->execute($sql, [$idJugador]);

        // Mejor práctica: obtener último insert id si tu DB lo soporta
        $sql = "SELECT id
                FROM partidas
                WHERE usuario_id = ?
                ORDER BY id DESC
                LIMIT 1";

        $resultado = $this->database->query($sql, [$idJugador]);

        return $resultado[0]["id"] ?? null;
    }

    public function obtenerPartidasPorUsuario($usuarioId)
    {
        $sql = "SELECT 
                    p.id,
                    p.puntaje_total,
                    COUNT(DISTINCT pp.pregunta_id) AS preguntas_jugadas,
                    p.fecha_creacion
                FROM partidas p
                LEFT JOIN partida_preguntas pp ON pp.partida_id = p.id
                WHERE p.usuario_id = ?
                GROUP BY p.id, p.puntaje_total, p.fecha_creacion
                ORDER BY p.fecha_creacion DESC";

        return $this->database->query($sql, [$usuarioId]);
    }

    public function finalizarPartida($idPartida, $puntaje)
    {
        $sql = "UPDATE partidas
                SET puntaje_total = ?
                WHERE id = ?";

        $this->database->execute($sql, [$puntaje, $idPartida]);
    }

    public function obtenerPartidaActual($usuarioId)
    {
        $sql = "SELECT id
                FROM partidas
                WHERE usuario_id = ?
                ORDER BY id DESC
                LIMIT 1";

        $resultado = $this->database->query($sql, [$usuarioId]);

        return $resultado[0]["id"] ?? null;
    }


    public function cantidadTotalPartidas($filtroSeleccionado)
    {
        $condicionFecha = $this->obtenerCondicionFecha($filtroSeleccionado, 'fecha_creacion');

        $sql = "SELECT COUNT(*) as cantidad 
            FROM partidas 
            WHERE 1=1 $condicionFecha";

        $resultado = $this->database->query($sql);
        return $resultado[0]['cantidad'] ?? 0;
    }


    private function obtenerCondicionFecha($filtro, $campoFecha)
    {
        switch ($filtro) {
            case 'dia':
                return " AND $campoFecha >= DATE(NOW())";
            case 'semana':
                return " AND $campoFecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'anio':
                return " AND YEAR($campoFecha) = YEAR(NOW())";
            default:
                return "";
        }
    }
}