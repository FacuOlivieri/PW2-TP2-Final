<?php

class PartidaController{

    private $partidaModel;
    private $usuarioModel;
    private $renderer;
    private $request;
    private $preguntaModel;
    private $estadoPartidaModel;


    public function __construct($partidaModel,
                                $usuarioModel,
                                $renderer,
                                $preguntaModel,
                                $estadoPartidaModel,
                                $request
                                ){

        $this->partidaModel = $partidaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->estadoPartidaModel = $estadoPartidaModel;
    }


    public function iniciarPartida(){

        if(!isset($_SESSION['usuario'])){
            Redirect::to("/usuario/iniciarSesion");
        }

        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION['usuario']);
        $usuarioId = $usuario["id"];

        $this->partidaModel->alta($usuarioId);

        /*
         * Obtengo el id de la partida creada para poder crear en BD las relaciones de las preguntas con la partida
         */
        //$idPartidaActual = $this->partidaModel->obtenerPartidaActual($usuarioId);
        //$this->estadoPartidaModel->alta($idPartidaActual);



        $preguntas = $this->preguntaModel->buscarTodasLasPreguntas();
        $preguntasAleatorias = shuffle($preguntas);




        $this->renderer->render("partidaView", [
                "preguntas" => $preguntasAleatorias,]);




    }

}
