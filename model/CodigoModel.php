<?php

class CodigoModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function crear($usuarioId, $codigo)
    {
        $sql = "INSERT INTO codigos (usuario_id, codigo, usado) VALUES (?, ?, 0)";
        $this->database->execute($sql, [$usuarioId, $codigo]);
    }

    public function existeCodigo($codigo)
    {
        $sql = "SELECT id FROM codigos WHERE codigo = ? LIMIT 1";
        $resultado = $this->database->query($sql, [$codigo]);
        return !empty($resultado);
    }

    public function obtenerPendientePorUsuarioYCodigo($usuarioId, $codigo)
    {
        $sql = "SELECT * FROM codigos WHERE usuario_id = ? AND codigo = ? AND usado = 0 LIMIT 1";
        $resultado = $this->database->query($sql, [$usuarioId, $codigo]);
        return $resultado[0] ?? null;
    }

    public function marcarComoUsado($id)
    {
        $sql = "UPDATE codigos SET usado = 1 WHERE id = ?";
        $this->database->execute($sql, [$id]);
    }

    public function generarCodigoUnico()
    {
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maximo = strlen($caracteres) - 1;

        do {
            $codigo = '';

            for ($i = 0; $i < 6; $i++) {
                $codigo .= $caracteres[random_int(0, $maximo)];
            }
        } while ($this->existeCodigo($codigo));

        return $codigo;
    }
}
