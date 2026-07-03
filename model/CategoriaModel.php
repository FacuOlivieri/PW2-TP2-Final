<?php

class CategoriaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerTodas()
    {
        $sql = "SELECT id, nombre, color FROM categorias ORDER BY id ASC";
        return $this->database->query($sql);
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT id, nombre, color FROM categorias WHERE id = ?";
        $resultado = $this->database->query($sql, [$id]);
        return $resultado[0] ?? null;
    }

    public function obtenerJugables()
    {
        $sql = "SELECT c.id, c.nombre, c.color
                FROM categorias c
                WHERE EXISTS (
                    SELECT 1 FROM preguntas p
                    WHERE p.categoria_id = c.id
                      AND p.estado = 'aprobada'
                      AND (SELECT COUNT(*) FROM respuestas r WHERE r.pregunta_id = p.id) >= 4
                )
                ORDER BY c.id ASC";
        return $this->database->query($sql);
    }
}
