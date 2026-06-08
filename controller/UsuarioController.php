<?php

class UsuarioController
{
    private $model;
    private $renderer;
    private $request;
    private $partidaModel;

    public function __construct($model, $renderer, $request, $partidaModel)
    {
        $this->model    = $model;
        $this->renderer = $renderer;
        $this->request  = $request;
        $this->partidaModel = $partidaModel;
    }

    public function inicio()
    {
        $this->renderer->render("homeView");
    }

    public function registrarse()
    {
        $this->renderer->render("registroView");
    }

    public function iniciarSesion()
    {
        $this->renderer->render("loginView");
    }

    public function mostrarUsuarioLobby()
    {
        $this->renderizarLobby();
    }

    public function procesarLogin()
    {
        $usuarioIngresado = $this->request->post("username");
        $passwordIngresada = $this->request->post("password");
        $usuario = $this->model->buscarUsuariosPorNombreDeUsuario($usuarioIngresado);
        if (empty($usuario)) {
            echo "Usuario no encontrado";
            exit();
        }
        if (password_verify($passwordIngresada, $usuario["password_hash"])) {
            $_SESSION["usuario"] = $usuario["username"];
            $this->renderizarLobby();
            exit();
        }
        echo "Contraseña incorrecta";
    }

    public function renderizarLobby()
    {
        $usuario = $_SESSION["usuario"];
        $ranking = $this->model->mostrarPuntajesRanking();

        $usuarioData = $this->model->buscarUsuariosPorNombreDeUsuario($usuario);
        $partidas = $this->partidaModel->obtenerHistorialPorUsuario($usuarioData["id"]);
        
        $this->renderer->render(
            "lobbyView", 
            ["nombreUsuario" => $usuario,
            "puntajeRanking" => $this->model->mostrarPuntaje($usuario),
            "puestoRanking" => $this->buscarPuestoEnRanking(
                $ranking,
                $usuario
            ),
            "ranking" => $ranking,
            "partidasJugadas" => $partidas,
            "partidasTotales" => count($partidas)]
        );
    
    }

    private function buscarPuestoEnRanking($ranking, $usuario)
    {
        foreach ($ranking as $fila) {
            if ($fila["nombre"] === $usuario) {
                return $fila["puesto"];
            }
        }

        return null;
    }

    public function ver()
    {
        $this->registrarse();
    }

    public function procesarRegistro()
    {
        $nombre = trim($this->request->post('nombre_completo', ''));
        $anio = trim($this->request->post('anio_nacimiento', ''));
        $sexo = trim($this->request->post('sexo', ''));
        $pais = trim($this->request->post('pais', ''));
        $ciudad = trim($this->request->post('ciudad', ''));
        $mail = trim($this->request->post('mail', ''));
        $username = trim($this->request->post('username', ''));

        if ($pais === '' || $ciudad === '') {
            $this->renderer->render("registroView", $this->datosRegistroConError(
                "Seleccioná país y ciudad desde el mapa antes de registrarte.",
                $nombre,
                $anio,
                $sexo,
                $pais,
                $ciudad,
                $mail,
                $username
            ));
            return;
        }

        $password = password_hash(
            $this->request->post('password'),
            PASSWORD_DEFAULT
        );

        $foto = $_FILES['foto_perfil']['name'];

        $this->model->alta(
            $nombre,
            $anio,
            $sexo,
            $pais,
            $ciudad,
            $mail,
            $password,
            $username,
            $foto
        );

        Redirect::to("/usuario/iniciarSesion");
        return;
    }

    private function datosRegistroConError($error, $nombre, $anio, $sexo, $pais, $ciudad, $mail, $username)
    {
        return [
            "error" => $error,
            "nombre_completo" => $nombre,
            "anio_nacimiento" => $anio,
            "sexo_masculino" => $sexo === "Masculino",
            "sexo_femenino" => $sexo === "Femenino",
            "sexo_prefiero_no_cargarlo" => $sexo === "Prefiero no cargarlo",
            "pais" => $pais,
            "ciudad" => $ciudad,
            "mail" => $mail,
            "username" => $username
        ];
    }

    public function perfil() {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario = $this->model->obtenerPerfilUsuario($_SESSION["usuario"]);

        $this->renderer->render(
            "perfilView", 
            ["usuario" => $usuario]
        );
    }

    public function ranking() {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $ranking = $this->model->mostrarPuntajesRanking();

        $this->renderer->render(
            "rankingView", 
            ["ranking" => $ranking,
            "usuarioActual" => $_SESSION["usuario"]]
        );
    }

    public function historial() {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario = $this->model ->buscarUsuariosPorNombreDeUsuario(
            $_SESSION["usuario"]
        );

        $historial = $this->partidaModel->obtenerHistorialPorUsuario(
            $usuario["id"]
        );

        $this->renderer->render(
            "historialView", 
            ["historial" => $historial,
            "usuario" => $usuario]
        );
    }
}
