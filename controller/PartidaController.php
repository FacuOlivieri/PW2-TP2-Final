<?php

class PartidaController{

    private $partidaModel;
    private $usuarioModel;
    private $renderer;
    private $request;

    public function __construct($partidaModel ,$usuarioModel, $renderer, $request){

        $this->partidaModel = $partidaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }


    public function iniciarPartida(){

        if(!isset($_SESSION['usuario'])){
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION['usuario']);
        $partida = $this->partidaModel->crearPartida($usuario);

        $this->renderer->render("partidaView", [
                "partida" => $partida]);


    }

    public function partidaTerminada() {
    //Aun no exixte parttida terminada: no se guarda el puntaje

//        if (isset($_SESSION["id_partida"])) {
//            $this->partidaModel->actualizarPuntajePartida(
//                $_SESSION["id_partida"],
//                $_SESSION["puntaje"] ?? 0
//            );
//        }
        $this->renderer->render(
            "partidaTerminadaView",
            ['usuarioNombre' => $_SESSION["usuario"],
            'usuarioPuntaje' => $_SESSION["puntaje"] ?? 0,
            'pregunta' => $_SESSION['pregunta_actual'] ?? "",
            'respuestaCorrecta' => $_SESSION['respuesta_correcta'] ?? "",]
        );
    }

}
