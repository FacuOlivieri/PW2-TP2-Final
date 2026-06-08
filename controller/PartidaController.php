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


    public function mostrarPregunta(){
        //Cargar pregunta al estado de la partida en BD
        $preguntas = $_SESSION['lista_preguntas'];
        $preguntaActual = $preguntas[0];
        $_SESSION['id_pregunta_actual'] = $preguntaActual['id'];
        $_SESSION['pregunta_actual'] = $preguntaActual['texto'];
        $this->estadoPartidaModel->cargarPreguntaPartidaActualALaBD($_SESSION['id_partida'], $_SESSION['id_pregunta_actual']);

        //Manejamos Respuestas de la pregunta en cuestion
        $respuestas = $this->manejoDeRepuestas($preguntaActual['id']);


        //Mostramos la pregunta
        $this->renderer->render("partidaView", [
            'usuarioNombre' => $_SESSION["usuario"],
            'usuarioPuntaje' => $_SESSION['puntaje'],
            'numeroPregunta' => $_SESSION['numero_pregunta'],
            'pregunta' => $preguntaActual['texto'],
            'categoria' => $preguntaActual["categoria_id"], //Aca se tiene que mostrar el nombre de la categoria, no el id. Crear CategoriaModel y buscar con un metodo al que le pases la pregunta
            'respuestas' => $respuestas
        ]);

    }

    public function responder()
    {
        $respuestaId = $this->request->post("respuesta_id");
        $respuesta = $this->respuestasModel->buscarRespuestaPorId($respuestaId);

        if ($respuesta === null) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        if ((int)$respuesta["pregunta_id"] !== (int)$_SESSION["id_pregunta_actual"]) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        if ((int)$respuesta["es_correcta"] === 1) {
            $_SESSION['numero_pregunta']++;
            $_SESSION['puntaje']++;
            array_shift($_SESSION['lista_preguntas']);

            Redirect::to("/partida/mostrarPregunta");
            return;
        }

        Redirect::to("/partida/partidaTerminada");
    }


    //Agarra las preguntas insertadas por BD base y las mezcla
    private function preguntas(){
        $preguntas = $this->preguntaModel->buscarTodasLasPreguntas();
        shuffle($preguntas);
        $_SESSION['lista_preguntas'] = $preguntas;
        return $preguntas;
    }


    private function manejoDeRepuestas($pregunta_id): array
    {
        $respuestaCorrecta = $this->respuestasModel->buscarRespuestaCorrectaALaPregunta($pregunta_id);
        $_SESSION['respuesta_correcta'] = $respuestaCorrecta['texto'];
        $posiblesRespuestasIncorrectasParaPregunta = $this->respuestasModel->buscarRespuestasIncorrectasParaPregunta($pregunta_id);
        $respuestasIncorrectas = $this->aleatorizarTresRespuestasIncorrectasParaLaPregunta($posiblesRespuestasIncorrectasParaPregunta);
        return $this->traerRespuestas($respuestaCorrecta, $respuestasIncorrectas);
    }


    //Agarra las respuestas Incorrectas, las mezcla y mete 3 de ellas en un array las opciones incorrectas
    private function aleatorizarTresRespuestasIncorrectasParaLaPregunta($posiblesRespuestasIncorrectasParaPregunta): array
    {

        shuffle($posiblesRespuestasIncorrectasParaPregunta);
        for ($i = 0; $i <= 2; $i++) {
            $respuestasIncorrectas[] = $posiblesRespuestasIncorrectasParaPregunta[$i];
        }
        return $respuestasIncorrectas;
    }

    //Mezcla las respuestas incorrectas y la correcta en un solo array y lo devuelve
    private function traerRespuestas(array $respuestaCorrecta, array $respuestasIncorrectas): array {
        $respuestas = $respuestasIncorrectas;
        $respuestas[] = $respuestaCorrecta;
        shuffle($respuestas);
        return $respuestas;
    }

    public function partidaTerminada()
    {
        $puntajeFinal = $_SESSION["puntaje"] ?? 0;
        $idPartida = $_SESSION["id_partida"] ?? null;
        $usuario = $_SESSION["usuario"] ?? null;

        if ($idPartida !== null) {
            $this->partidaModel->finalizarPartida($idPartida, $puntajeFinal);
        }

        if ($usuario !== null && $puntajeFinal > 0) {
            $this->usuarioModel->sumarPuntaje($usuario, $puntajeFinal);
        }

        $this->renderer->render("partidaTerminadaView", [
            'usuarioNombre' => $usuario,
            'usuarioPuntaje' => $puntajeFinal,
            'pregunta' => $_SESSION['pregunta_actual'] ?? '',
            'respuestaCorrecta' => $_SESSION['respuesta_correcta'] ?? '',
        ]);

        unset($_SESSION["id_partida"]);
        unset($_SESSION["puntaje"]);
        unset($_SESSION["numero_pregunta"]);
        unset($_SESSION["id_pregunta_actual"]);
        unset($_SESSION["lista_preguntas"]);
        unset($_SESSION["pregunta_actual"]);
        unset($_SESSION["respuesta_correcta"]);
    }




}
