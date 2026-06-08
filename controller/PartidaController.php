<?php

class PartidaController{

    private $partidaModel;
    private $usuarioModel;
    private $renderer;
    private $request;
    private $preguntaModel;
    private $estadoPartidaModel;
    private $respuestasModel;


    public function __construct($partidaModel,
                                $usuarioModel,
                                $renderer,
                                $preguntaModel,
                                $estadoPartidaModel,
                                $respuestasModel,
                                $request
                                ){

        $this->partidaModel = $partidaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->estadoPartidaModel = $estadoPartidaModel;
        $this->respuestasModel = $respuestasModel;
    }


    public function iniciarPartida(){

        if(!isset($_SESSION['usuario'])){
            Redirect::to("/usuario/iniciarSesion");
        }


        //Doy el alta inicial de la partida y el estado actual para ir cargando las preguntas a medida que aparecen
        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION['usuario']);
        $usuarioId = $usuario["id"];
        $this->partidaModel->alta($usuarioId);
        $idPartidaActual = $this->partidaModel->obtenerPartidaActual($usuarioId);

        //Inicializo Contador de puntaje
        $_SESSION['id_partida'] = $idPartidaActual;
        $_SESSION['numero_pregunta'] = 1;
        $_SESSION['puntaje'] = 0;

        //Manejo de Preguntas
        $_SESSION['lista_preguntas'] = $this->preguntaModel->buscarTodasLasPreguntas();
        shuffle($_SESSION['lista_preguntas']);

        $this->mostrarPregunta();

    }

    private function manejoDeRespuestas($preguntaId)
    {
        return $this->respuestasModel->buscarRespuestasPorPregunta($preguntaId);
    }


    public function mostrarPregunta(){
        //Cargar pregunta al estado de la partida en BD
        $preguntas = $_SESSION['lista_preguntas'];
        $preguntaActual = $preguntas[0];
        $_SESSION['id_pregunta_actual'] = $preguntaActual['id'];
        $_SESSION['pregunta_actual'] = $preguntaActual['texto'];
        $this->estadoPartidaModel->cargarPreguntaPartidaActualALaBD($_SESSION['id_partida'], $_SESSION['id_pregunta_actual']);

        //Manejamos Respuestas de la pregunta en cuestion
        $respuestas = $this->manejoDeRespuestas($preguntaActual['id']);


        //Mostramos la pregunta
        $this->renderer->render(
            "partidaView",
            ["pregunta" => $preguntaActual,
            "respuestas" => $respuestas,
            "puntaje" => $_SESSION['puntaje'],
            "numeroPregunta" => $_SESSION['numero_pregunta']]
        );
    }

}
