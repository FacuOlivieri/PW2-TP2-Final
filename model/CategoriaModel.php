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
}
