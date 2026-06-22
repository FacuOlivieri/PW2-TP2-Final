<?php

class UsuarioController
{
    private $model;
    private $renderer;
    private $request;
    private $partidaModel;
    private $codigoModel;
    private $mailHelper;

    public function __construct($model, $renderer, $request, $partidaModel, $codigoModel, $mailHelper = null)
    {
        $this->model = $model;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->partidaModel = $partidaModel;
        $this->codigoModel = $codigoModel;
        $this->mailHelper = $mailHelper;
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

    public function cerrarSesion()
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        Redirect::to("/usuario/iniciarSesion");
    }

    public function verificarCodigo()
    {
        if (!isset($_SESSION["usuario_id_pendiente"]) || !isset($_SESSION["mail_pendiente"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $this->renderer->render("verificarCodigoView", [
            "mail" => $_SESSION["mail_pendiente"]
        ]);
    }

    public function procesarVerificacion()
    {
        if (!isset($_SESSION["usuario_id_pendiente"]) || !isset($_SESSION["mail_pendiente"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $codigoIngresado = trim($this->request->post("codigo", ""));
        $usuarioId = $_SESSION["usuario_id_pendiente"];

        $codigo = $this->codigoModel->obtenerPendientePorUsuarioYCodigo($usuarioId, $codigoIngresado);

        if ($codigo === null) {
            $this->renderer->render("verificarCodigoView", [
                "mail" => $_SESSION["mail_pendiente"],
                "error" => "Código incorrecto o expirado."
            ]);
            return;
        }

        $this->codigoModel->marcarComoUsado($codigo["id"]);
        $this->model->verificarMail($usuarioId);

        unset($_SESSION["usuario_id_pendiente"]);
        unset($_SESSION["mail_pendiente"]);

        $usuario = $this->model->buscarUsuariosPorNombreDeUsuario($_SESSION["usuario"] ?? "");

        if ($usuario) {
            $_SESSION["usuario"] = $usuario["username"];
            $_SESSION["usuario_id"] = $usuario["id"];

            if ($usuario["es_administrador"] == 1) {
                $_SESSION["rol"] = "administrador";
            }

            $this->renderizarLobby();
            return;
        }

        Redirect::to("/usuario/iniciarSesion");
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

        if (!$usuario) {
            echo "Usuario no encontrado";
            return;
        }

        if (password_verify($passwordIngresada, $usuario["password_hash"])) {
            $_SESSION["usuario"] = $usuario["username"];
            $_SESSION["usuario_id"] = $usuario["id"];


            if ($usuario["es_administrador"] == 1) {
                $_SESSION["rol"] = "administrador";
            }

            $this->renderizarLobby();
            return;
        }

        echo "Contraseña incorrecta";
    }

    public function renderizarLobby()
    {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario = $_SESSION["usuario"];

        $ranking = $this->model->mostrarPuntajesRanking();

        $usuarioData =
            $this->model->buscarUsuariosPorNombreDeUsuario($usuario);

        if (!$usuarioData) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $partidas =
            $this->partidaModel->obtenerPartidasPorUsuario($usuarioData["id"]);

        $rol = $_SESSION["rol"] ?? null;

        $this->renderer->render("lobbyView", [
            "nombreUsuario" => $usuario,
            "puntajeRanking" => $this->model->mostrarPuntaje($usuario),
            "puestoRanking" => $this->buscarPuestoEnRanking($ranking, $usuario),
            "ranking" => $ranking,
            "partidas" => $partidas,
            "partidasJugadas" => $partidas,
            "partidasTotales" => count($partidas),
            "es_admin" => ($rol === "administrador")
        ]);
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

        $nivel = "facil";
        $esEditor = 0;

        $foto = $_FILES['foto_perfil']['name'] ?? null;

        if ($pais === '' || $ciudad === '') {
            $this->renderer->render(
                "registroView",
                $this->datosRegistroConError(
                    "Seleccioná país y ciudad desde el mapa antes de registrarte.",
                    $nombre,
                    $anio,
                    $sexo,
                    $pais,
                    $ciudad,
                    $mail,
                    $username
                )
            );
            return;
        }

        if ($this->model->buscarPorMail($mail)) {
            $this->renderer->render(
                "registroView",
                $this->datosRegistroConError(
                    "Este email ya está registrado. Usá otro o iniciá sesión.",
                    $nombre,
                    $anio,
                    $sexo,
                    $pais,
                    $ciudad,
                    $mail,
                    $username
                )
            );
            return;
        }

        $password = password_hash(
            $this->request->post('password'),
            PASSWORD_DEFAULT
        );

        $usuarioId = $this->model->alta(
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
        );

        $codigo = $this->codigoModel->generarCodigoUnico();
        $this->codigoModel->crear($usuarioId, $codigo);

        $logMensaje = "[" . date('Y-m-d H:i:s') . "] Código generado para usuario $usuarioId: $codigo\n";
        file_put_contents(__DIR__ . '/../log/email_debug.log', $logMensaje, FILE_APPEND);

        if ($this->mailHelper) {
            $enviado = $this->mailHelper->enviarCodigoVerificacion($mail, $codigo);
            $logResultado = "[" . date('Y-m-d H:i:s') . "] Email enviado a $mail: " . ($enviado ? 'EXITOSO' : 'FALLÓ') . "\n";
            file_put_contents(__DIR__ . '/../log/email_debug.log', $logResultado, FILE_APPEND);
        } else {
            $logError = "[" . date('Y-m-d H:i:s') . "] MailHelper no está disponible\n";
            file_put_contents(__DIR__ . '/../log/email_debug.log', $logError, FILE_APPEND);
        }

        $_SESSION["usuario_id_pendiente"] = $usuarioId;
        $_SESSION["mail_pendiente"] = $mail;

        Redirect::to("/usuario/verificarCodigo");
    }

    private function datosRegistroConError(
        $error,
        $nombre,
        $anio,
        $sexo,
        $pais,
        $ciudad,
        $mail,
        $username
    ) {
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

    public function perfil()
    {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $username =
            trim($this->request->get("username", $_SESSION["usuario"]));

        $usuario =
            $this->model->obtenerPerfilUsuario($username);

        if (!$usuario) {
            Redirect::to("/usuario/ranking");
            return;
        }

        $perfilUrl = $this->crearUrlPerfilPublico($usuario["username"]);

        $this->renderer->render("perfilView", [
            "usuario" => $usuario,
            "mapaUrl" => $this->crearUrlMapaUsuario($usuario),
            "perfilUrl" => $perfilUrl,
            "qrUrl" => $this->crearUrlQr($perfilUrl),
            "fotoPerfilUrl" => $this->crearUrlFotoPerfil($usuario),
            "tieneFotoPerfil" => !empty($usuario["foto_perfil"]),
            "perfilPublico" => $username !== $_SESSION["usuario"],
            "partidas" => $this->partidaModel->obtenerPartidasPorUsuario($usuario["id"])
        ]);
    }

    private function crearUrlPerfilPublico($username)
    {
        $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";

        return $scheme . "://" . $host . "/usuario/perfil?username=" . rawurlencode($username);
    }

    private function crearUrlQr($url)
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . rawurlencode($url);
    }

    private function crearUrlFotoPerfil($usuario)
    {
        $foto = trim($usuario["foto_perfil"] ?? "");

        if ($foto === "") {
            return "";
        }

        if (preg_match('/^https?:\/\//', $foto) === 1 || strpos($foto, '/') === 0) {
            return $foto;
        }

        return "/" . ltrim($foto, "/");
    }

    private function crearUrlMapaUsuario($usuario)
    {
        $latitud = $usuario["latitud"] ?? null;
        $longitud = $usuario["longitud"] ?? null;

        if ($latitud && $longitud) {
            return "https://maps.google.com/maps?q="
                . rawurlencode($latitud . "," . $longitud)
                . "&z=11&output=embed";
        }

        $ubicacion = trim(
            ($usuario["ciudad"] ?? "") . ", " . ($usuario["pais"] ?? "")
        );

        return "https://maps.google.com/maps?q="
            . rawurlencode($ubicacion)
            . "&z=11&output=embed";
    }

    public function ranking()
    {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $ranking = $this->model->mostrarPuntajesRanking();

        $this->renderer->render("rankingView", [
            "ranking" => $ranking,
            "usuarioActual" => $_SESSION["usuario"]
        ]);
    }

    public function historial()
    {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario =
            $this->model->buscarUsuariosPorNombreDeUsuario($_SESSION["usuario"]);

        if (!$usuario) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $historial =
            $this->partidaModel->obtenerPartidasPorUsuario($usuario["id"]);

        $this->renderer->render("historialView", [
            "historial" => $historial,
            "usuario" => $usuario
        ]);
    }

}