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

    public function buscarUsuariosPorNombreDeUsuario($nombre)
    {
        $sql = "SELECT * FROM usuarios WHERE username = ?";
        $resultado = $this->database->query($sql, [$nombre]);
        return $resultado[0] ?? null;
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

    public function mostrarPuntaje($usuario)
    {
        $sql = "SELECT puntaje FROM usuarios WHERE username = ?";
        $resultado = $this->database->query($sql, [$usuario]);

        return $resultado[0]["puntaje"] ?? 0;
    }

    public function mostrarPuntajesRanking()
    {
        $sql = "SELECT username AS nombre, puntaje AS puntajeTotal
                FROM usuarios
                ORDER BY puntaje DESC, username ASC";

        $usuarios = $this->database->query($sql);

        foreach ($usuarios as $indice => $usuario) {
            $usuarios[$indice]["puesto"] = $indice + 1;
        }

        return $usuarios;
    }

    public function sumarPuntaje($username, $puntaje)
    {
        $sql = "UPDATE usuarios SET puntaje = puntaje + ? WHERE username = ?";
        $this->database->execute($sql, [$puntaje, $username]);
    }

}
