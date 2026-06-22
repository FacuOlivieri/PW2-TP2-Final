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

    public function buscarPorMail($mail)
    {
        $sql = "SELECT * FROM usuarios WHERE mail = ?";
        $resultado = $this->database->query($sql, [$mail]);

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
        $foto,
        $nivel = "facil",
        $esEditor = 0
    ) {
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
            foto_perfil,
            nivel,
            es_editor
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->database->execute($sql, [
            $nombre,
            $anio,
            $sexo,
            $pais,
            $ciudad,
            $mail,
            $password,
            $username,
            $foto,
            $nivel,
            $esEditor
        ]);

        return $this->database->lastInsertId();
    }

    public function verificarMail($usuarioId)
    {
        $sql = "UPDATE usuarios SET mail_verificado = 1 WHERE id = ?";
        $this->database->execute($sql, [$usuarioId]);
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

        foreach ($usuarios as $i => $usuario) {
            $usuarios[$i]["puesto"] = $i + 1;
        }

        return $usuarios;
    }

    public function sumarPuntaje($username, $puntaje)
    {
        $sql = "UPDATE usuarios SET puntaje = puntaje + ? WHERE username = ?";
        $this->database->execute($sql, [$puntaje, $username]);
    }

    public function obtenerPerfilUsuario($username)
    {
        $sql = "SELECT * FROM usuarios WHERE username = ?";
        $resultado = $this->database->query($sql, [$username]);

        return $resultado[0] ?? null;
    }

    public function obtenerTopRanking($limite = 10)
    {
        $sql = "SELECT username, puntaje 
                FROM usuarios
                ORDER BY puntaje DESC
                LIMIT $limite";

        return $this->database->query($sql);
    }

    public function actualizarNivel($username, $nivel)
    {
        $sql = "UPDATE usuarios SET nivel = ? WHERE username = ?";
        $this->database->execute($sql, [$nivel, $username]);
    }

    public function esEditor($username)
    {
        $sql = "SELECT es_editor FROM usuarios WHERE username = ?";
        $rol = $this->database->query($sql, [$username]);

        return !empty($rol) && (int)$rol[0]["es_editor"] === 1;
    }

    public function requireEditor($username)
    {
        if (!$this->esEditor($username)) {
            Redirect::to("/usuario/lobby");
            exit();
        }
    }

    public function esAdministrador($username)
    {
        $sql = "SELECT es_administrador FROM usuarios WHERE username = ?";
        $rol = $this->database->query($sql, [$username]);

        return !empty($rol) && (int)$rol[0]["es_administrador"] === 1;
    }

    public function requireAdministrador($username)
    {
        if (!$this->esAdministrador($username)) {
            Redirect::to("/usuario/lobby");
            exit();
        }
    }

}