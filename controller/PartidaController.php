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

}
