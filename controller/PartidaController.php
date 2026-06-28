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
    private $categoriaModel;

    public function __construct(
        $partidaModel,
        $usuarioModel,
        $renderer,
        $preguntaModel,
        $estadoPartidaModel,
        $respuestasModel,
        $categoriaModel,
        $request
    ) {
        $this->partidaModel = $partidaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->preguntaModel = $preguntaModel;
        $this->estadoPartidaModel = $estadoPartidaModel;
        $this->respuestasModel = $respuestasModel;
        $this->categoriaModel = $categoriaModel;
    }

    public function iniciarPartida()
    {
        if (!isset($_SESSION['usuario'])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION['usuario']);

        if (!$usuario) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $_SESSION["nivel_usuario"] = $usuario["nivel"];

        $usuarioId = $usuario["id"];

        $idPartidaActual = $this->partidaModel->alta($usuarioId);

        $_SESSION['id_partida'] = $idPartidaActual;
        $_SESSION['numero_pregunta'] = 1;
        $_SESSION['puntaje'] = 0;
        $_SESSION['preguntas_respondidas'] = [];

        $this->mostrarRuleta();
    }

    public function mostrarRuleta()
    {
        if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_partida'])) {
            Redirect::to("/partida/iniciarPartida");
            return;
        }

        $this->renderer->render("ruletaView", [
            "categorias" => $this->categoriaModel->obtenerTodas(),
            "mensajeRuleta" => $_SESSION['mensaje_ruleta'] ?? null
        ]);

        unset($_SESSION['mensaje_ruleta']);
    }

    public function jugarCategoria()
    {
        if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_partida'])) {
            Redirect::to("/partida/iniciarPartida");
            return;
        }

        $categoriaId = $this->request->post("categoria_id");
        $categoria = $this->categoriaModel->obtenerPorId($categoriaId);

        if ($categoria === null) {
            Redirect::to("/partida/iniciarPartida");
            return;
        }

        $_SESSION['categoria_id'] = $categoria['id'];
        $_SESSION['categoria_nombre'] = $categoria['nombre'];

        $preguntas = $this->preguntaModel->buscarPreguntasPorCategoria($categoria['id']);
        $preguntasRespondidas = $_SESSION['preguntas_respondidas'] ?? [];

        $preguntasDisponibles = array_values(array_filter($preguntas, function ($pregunta) use ($preguntasRespondidas) {
            return !in_array((int)$pregunta['id'], $preguntasRespondidas, true);
        }));

        $dificultad = $this->obtenerDificultadJugador();

        $_SESSION['lista_preguntas'] = array_values(array_filter(
            $preguntasDisponibles,
            function ($pregunta) use ($dificultad) {
                return !isset($pregunta['dificultad']) || $pregunta['dificultad'] === $dificultad;
            }
        ));

        if (empty($_SESSION['lista_preguntas'])) {
            $_SESSION['lista_preguntas'] = $preguntasDisponibles;
        }

        if (empty($_SESSION['lista_preguntas'])) {
            $_SESSION['mensaje_ruleta'] = "No hay preguntas disponibles para " . $categoria['nombre'] . ". Probá con otra categoría.";
            Redirect::to("/partida/mostrarRuleta");
            return;
        }

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
        $_SESSION['inicio_pregunta'] = time();

        $this->preguntaModel->sumarPreguntasEntregadas($preguntaActual['id']);

        $this->estadoPartidaModel->cargarPreguntaPartidaActualALaBD(
            $_SESSION['id_partida'],
            $_SESSION['id_pregunta_actual']
        );

        $respuestas = $this->manejoDeRespuestas($preguntaActual['id']);

        if (count($respuestas) < 4) {
            $_SESSION['preguntas_respondidas'][] = (int)$preguntaActual['id'];
            array_shift($_SESSION['lista_preguntas']);
            Redirect::to("/partida/mostrarRuleta");
            return;
        }

        $this->renderer->render("partidaView", [
            "pregunta" => $preguntaActual["texto"],
            "categoria" => $_SESSION['categoria_nombre'] ?? "",
            "dificultad" => $this->formatearDificultadPregunta($preguntaActual["dificultad"] ?? "media"),
            "dificultadClase" => $this->crearClaseDificultadPregunta($preguntaActual["dificultad"] ?? "media"),
            "respuestas" => $respuestas,
            "puntaje" => $_SESSION['puntaje'],
            "usuarioPuntaje" => $_SESSION['puntaje'],
            "numeroPregunta" => $_SESSION['numero_pregunta']
        ]);
    }

    private function formatearDificultadPregunta($dificultad)
    {
        $dificultad = strtolower((string)$dificultad);

        if ($dificultad === "facil") {
            return "Fácil";
        }

        if ($dificultad === "dificil") {
            return "Difícil";
        }

        return "Media";
    }

    private function crearClaseDificultadPregunta($dificultad)
    {
        $dificultad = strtolower((string)$dificultad);

        if ($dificultad === "facil") {
            return "facil";
        }

        if ($dificultad === "dificil") {
            return "dificil";
        }

        return "media";
    }

    public function responder()
    {
        if (!isset($_SESSION["id_partida"]) || !isset($_SESSION["id_pregunta_actual"]) || !isset($_SESSION["inicio_pregunta"])) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $tiempoLimite = 15;

        if (time() - $_SESSION["inicio_pregunta"] > $tiempoLimite) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

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

            $this->preguntaModel->sumarPreguntasCorrectas($_SESSION["id_pregunta_actual"]);
            $this->preguntaModel->calcularDificultad($_SESSION["id_pregunta_actual"]);

            $_SESSION['numero_pregunta']++;
            $_SESSION['puntaje']++;
            $_SESSION['preguntas_respondidas'][] = (int)$_SESSION["id_pregunta_actual"];
            array_shift($_SESSION['lista_preguntas']);

            Redirect::to("/partida/mostrarRuleta");
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

        if ($usuario !== null) {

            if ($puntajeFinal > 0) {
                $this->usuarioModel->sumarPuntaje($usuario, $puntajeFinal);
            }

            $usuarioData =
                $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($usuario);

            if ($usuarioData) {
                $totalPuntaje =
                    ($usuarioData["puntaje"] ?? 0) + $puntajeFinal;

                $nivelActual = $this->calcularNivelGlobal($totalPuntaje);

                $this->usuarioModel->actualizarNivel($usuario, $nivelActual);
            }
        }

        $this->renderer->render("partidaTerminadaView", [
            'usuarioNombre' => $usuario,
            'usuarioPuntaje' => $puntajeFinal,
            'pregunta' => $_SESSION['pregunta_actual'] ?? "",
            'respuestaCorrecta' => $_SESSION['respuesta_correcta'] ?? "",
        ]);

        $this->limpiarSesionPartida();
    }

    private function limpiarSesionPartida()
    {
        unset($_SESSION["id_partida"]);
        unset($_SESSION["puntaje"]);
        unset($_SESSION["numero_pregunta"]);
        unset($_SESSION["id_pregunta_actual"]);
        unset($_SESSION["lista_preguntas"]);
        unset($_SESSION["pregunta_actual"]);
        unset($_SESSION["respuesta_correcta"]);
        unset($_SESSION["categoria_id"]);
        unset($_SESSION["categoria_nombre"]);
        unset($_SESSION["preguntas_respondidas"]);
        unset($_SESSION["inicio_pregunta"]);
        unset($_SESSION["mensaje_ruleta"]);
    }

    private function manejoDeRespuestas($preguntaId): array
    {
        $respuestaCorrecta =
            $this->respuestasModel->buscarRespuestaCorrectaALaPregunta($preguntaId);

        if ($respuestaCorrecta === null) {
            return [];
        }

        $_SESSION['respuesta_correcta'] = $respuestaCorrecta['texto'];

        $respuestasIncorrectas =
            $this->respuestasModel->buscarRespuestasIncorrectasParaPregunta($preguntaId);

        if (count($respuestasIncorrectas) < 3) {
            return [];
        }

        $respuestas = $respuestasIncorrectas;
        $respuestas[] = $respuestaCorrecta;

        shuffle($respuestas);

        return $respuestas;
    }

    public function timeout()
    {
        if (!isset($_SESSION['usuario'])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        if (!isset($_SESSION['id_partida'])) {
            Redirect::to("/usuario/mostrarUsuarioLobby");
            return;
        }

        Redirect::to("/partida/partidaTerminada");
    }

    private function obtenerDificultadJugador()
    {
        if (!isset($_SESSION['usuario'])) {
            return "facil";
        }

        $usuario =
            $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION['usuario']);

        if (!$usuario) {
            return "facil";
        }

        $base = $usuario["nivel"] ?? "facil";
        $puntaje = $_SESSION["puntaje"] ?? 0;

        if ($base === "facil" && $puntaje >= 5) {
            return "medio";
        }

        if ($base === "medio" && $puntaje >= 8) {
            return "dificil";
        }

        return $base;
    }

    private function calcularNivelGlobal($puntajeTotal)
    {
        if ($puntajeTotal < 10) {
            return "facil";
        }
        if ($puntajeTotal < 25) {
            return "medio";
        }
        return "dificil";
    }

    public function reportar()
    {
        if (!isset($_SESSION["usuario_id"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        if (!isset($_SESSION["id_pregunta_actual"])) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $usuario = $_SESSION["usuario_id"];
        $preguntaId = $_SESSION["id_pregunta_actual"];
        $motivo = $this->request->post("motivo");

        $this->preguntaModel->reportarPregunta($usuario, $preguntaId, $motivo);

        Redirect::to("/partida/mostrarPregunta");
    }
}
