<?php

class PartidaController
{
    private $partidaModel;
    private $usuarioModel;
    private $renderer;
    private $request;
    private $preguntaModel;
    private $estadoPartidaModel;
    private $respuestasModel;

    public function __construct(
        $partidaModel,
        $usuarioModel,
        $renderer,
        $preguntaModel,
        $estadoPartidaModel,
        $respuestasModel,
        $request
    ) {
        $this->partidaModel = $partidaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->estadoPartidaModel = $estadoPartidaModel;
        $this->respuestasModel = $respuestasModel;
    }

    public function iniciarPartida()
    {
        if (!isset($_SESSION['usuario'])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION['usuario']);
        $usuarioId = $usuario["id"];

        $idPartidaActual = $this->partidaModel->alta($usuarioId);

        $_SESSION['id_partida'] = $idPartidaActual;
        $_SESSION['numero_pregunta'] = 1;
        $_SESSION['puntaje'] = 0;

        $_SESSION['lista_preguntas'] = $this->preguntaModel->buscarTodasLasPreguntas();
        shuffle($_SESSION['lista_preguntas']);

        $this->mostrarPregunta();
    }

    public function mostrarPregunta()
    {
        if (!isset($_SESSION['lista_preguntas']) || empty($_SESSION['lista_preguntas'])) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $preguntaActual = $_SESSION['lista_preguntas'][0];

        $_SESSION['id_pregunta_actual'] = $preguntaActual['id'];
        $_SESSION['pregunta_actual'] = $preguntaActual['texto'];

        $this->estadoPartidaModel->cargarPreguntaPartidaActualALaBD(
            $_SESSION['id_partida'],
            $_SESSION['id_pregunta_actual']
        );

        $respuestas = $this->manejoDeRespuestas($preguntaActual['id']);

        if (count($respuestas) < 4) {
            array_shift($_SESSION['lista_preguntas']);
            Redirect::to("/partida/mostrarPregunta");
            return;
        }

        $this->renderer->render("partidaView", [
            "pregunta" => $preguntaActual["texto"],
            "categoria" => $preguntaActual["categoria_id"] ?? "",
            "respuestas" => $respuestas,
            "puntaje" => $_SESSION['puntaje'],
            "usuarioPuntaje" => $_SESSION['puntaje'],
            "numeroPregunta" => $_SESSION['numero_pregunta']
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
            'pregunta' => $_SESSION['pregunta_actual'] ?? "",
            'respuestaCorrecta' => $_SESSION['respuesta_correcta'] ?? "",
        ]);

        unset($_SESSION["id_partida"]);
        unset($_SESSION["puntaje"]);
        unset($_SESSION["numero_pregunta"]);
        unset($_SESSION["id_pregunta_actual"]);
        unset($_SESSION["lista_preguntas"]);
        unset($_SESSION["pregunta_actual"]);
        unset($_SESSION["respuesta_correcta"]);
    }

    private function manejoDeRespuestas($preguntaId): array
    {
        $respuestaCorrecta = $this->respuestasModel->buscarRespuestaCorrectaALaPregunta($preguntaId);

        if ($respuestaCorrecta === null) {
            return [];
        }

        $_SESSION['respuesta_correcta'] = $respuestaCorrecta['texto'];

        $respuestasIncorrectas = $this->respuestasModel->buscarRespuestasIncorrectasParaPregunta($preguntaId);

        if (count($respuestasIncorrectas) < 3) {
            return [];
        }

        $respuestas = $respuestasIncorrectas;
        $respuestas[] = $respuestaCorrecta;

        shuffle($respuestas);

        return $respuestas;
    }
}