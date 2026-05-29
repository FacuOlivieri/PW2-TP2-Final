<?php

class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getUsuarios()
    {
        $sql = "SELECT * FROM usuarios";

        return $this->database->query($sql);
    }

    public function alta(
        $nombre,
        $anio,
        $sexo,
        $pais,
        $ciudad,
        $mail,
        $password,
        $username,
        $foto
    )
    {
        $sql = "INSERT INTO usuarios
    (
        nombre_completo,
        anio_nacimiento,
        sexo,
        pais,
        ciudad,
        mail,
        password_hash,
        username,
        foto_perfil
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->database->execute($sql, [
            $nombre,
            $anio,
            $sexo,
            $pais,
            $ciudad,
            $mail,
            $password,
            $username,
            $foto
        ]);
    }

}