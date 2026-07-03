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
        if (!$this->requiereUsuarioComun()) {
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
        unset($_SESSION['resultado_partida']);

        Redirect::to("/partida/mostrarRuleta");
        return;
    }

    public function mostrarRuleta()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_partida'])) {
            Redirect::to("/partida/iniciarPartida");
            return;
        }

        $this->renderer->render("ruletaView", [
            "categorias" => $this->categoriaModel->obtenerJugables(),
            "mensajeRuleta" => $_SESSION['mensaje_ruleta'] ?? null
        ]);

        unset($_SESSION['mensaje_ruleta']);
    }

    public function jugarCategoria()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

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

        $dificultad = $this->obtenerDificultadJugador();
        $usuarioId = $_SESSION['usuario_id'];
        $preguntas = $this->obtenerPreguntasParaCategoria($categoria['id'], $dificultad, $usuarioId);

        if (empty($preguntas)) {
            $categoriasJugables = $this->categoriaModel->obtenerJugables();
            shuffle($categoriasJugables);

            foreach ($categoriasJugables as $alternativa) {
                if ((int)$alternativa['id'] === (int)$categoria['id']) {
                    continue;
                }

                $preguntas = $this->obtenerPreguntasParaCategoria($alternativa['id'], $dificultad, $usuarioId);

                if (!empty($preguntas)) {
                    $categoria = $alternativa;
                    break;
                }
            }
        }

        if (empty($preguntas)) {
            $_SESSION['mensaje_ruleta'] = "No hay preguntas disponibles en este momento. Probá de nuevo más tarde.";
            Redirect::to("/partida/mostrarRuleta");
            return;
        }

        $_SESSION['categoria_id'] = $categoria['id'];
        $_SESSION['categoria_nombre'] = $categoria['nombre'];
        $_SESSION['lista_preguntas'] = $preguntas;

        shuffle($_SESSION['lista_preguntas']);

        Redirect::to("/partida/mostrarPregunta");
        return;
    }

    public function mostrarPregunta()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        if (!isset($_SESSION['lista_preguntas']) || empty($_SESSION['lista_preguntas'])) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $preguntaActual = $_SESSION['lista_preguntas'][0];

        if ($this->esPreguntaNueva($preguntaActual['id'])) {
            $this->inicializarPreguntaEnSesion($preguntaActual);

            $respuestas = $this->manejoDeRespuestas($preguntaActual['id']);

            if (count($respuestas) < 4) {
                $this->limpiarRespuestasPreguntaActual();
                array_shift($_SESSION['lista_preguntas']);
                $_SESSION['mensaje_ruleta'] = "La pregunta anterior no estaba completa, girá de nuevo para continuar.";
                Redirect::to("/partida/mostrarRuleta");
                return;
            }

            $this->guardarOrdenRespuestasEnSesion($respuestas);
        }

        $tiempoRestante = $this->calcularTiempoRestante();

        if ($tiempoRestante <= 0) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $this->renderer->render("partidaView", [
            "pregunta" => $preguntaActual["texto"],
            "categoria" => $_SESSION['categoria_nombre'] ?? "",
            "dificultad" => $this->formatearDificultadPregunta($preguntaActual["dificultad"] ?? "media"),
            "dificultadClase" => $this->crearClaseDificultadPregunta($preguntaActual["dificultad"] ?? "media"),
            "respuestas" => $_SESSION['respuestas_pregunta_actual'],
            "puntaje" => $_SESSION['puntaje'],
            "usuarioPuntaje" => $_SESSION['puntaje'],
            "numeroPregunta" => $_SESSION['numero_pregunta'],
            "tiempoRestante" => $tiempoRestante
        ]);
    }

    private function esPreguntaNueva($preguntaId) {
        return ($_SESSION['id_pregunta_actual'] ?? null) !== $preguntaId;
    }

    private function inicializarPreguntaEnSesion($preguntaActual) {
        $_SESSION['id_pregunta_actual'] = $preguntaActual['id'];
        $_SESSION['pregunta_actual'] = $preguntaActual['texto'];
        $_SESSION['tiempo_inicio_pregunta'] = time();
        $this->registrarPreguntaMostradaEnSesion($preguntaActual['id']);

        $this->preguntaModel->sumarPreguntasEntregadas($preguntaActual['id']);
        $this->preguntaModel->registrarPreguntaVista($_SESSION['usuario_id'], $preguntaActual['id']);

        $this->estadoPartidaModel->cargarPreguntaPartidaActualALaBD(
            $_SESSION['id_partida'],
            $_SESSION['id_pregunta_actual']
        );
    }

    private function guardarOrdenRespuestasEnSesion($respuestas) {
        $_SESSION['respuestas_pregunta_actual'] = $respuestas;
    }

    private function limpiarRespuestasPreguntaActual() {
        unset($_SESSION['respuestas_pregunta_actual']);
    }

    private function calcularTiempoRestante() {
        $tiempoLimite = 15;
        $tiempoActual = time();
        $tiempoGeneradoAlCrearLaPregunta = $_SESSION['tiempo_inicio_pregunta'];
        return max(0, $tiempoLimite - ($tiempoActual - $tiempoGeneradoAlCrearLaPregunta));
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
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        if (!isset($_SESSION["id_partida"]) ||
            !isset($_SESSION["id_pregunta_actual"]) ||
            !isset($_SESSION["tiempo_inicio_pregunta"])) {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $tiempoLimite = 15;

        if (time() - $_SESSION["tiempo_inicio_pregunta"] > $tiempoLimite) {
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
            array_shift($_SESSION['lista_preguntas']);
            $this->limpiarRespuestasPreguntaActual();

            Redirect::to("/partida/mostrarRuleta");
            return;
        }

        Redirect::to("/partida/partidaTerminada");
    }

    public function partidaTerminada()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        $puntajeFinal = $_SESSION["puntaje"] ?? 0;
        $idPartida = $_SESSION["id_partida"] ?? null;
        $usuario = $_SESSION["usuario"] ?? null;

        if ($idPartida !== null) {
            $this->partidaModel->finalizarPartida($idPartida, $puntajeFinal);
        }

        if (isset($_SESSION["id_pregunta_actual"])) {
            $this->preguntaModel->calcularDificultad($_SESSION["id_pregunta_actual"]);
        }

        if ($usuario !== null) {

            if ($puntajeFinal > 0) {
                $this->usuarioModel->sumarPuntaje($usuario, $puntajeFinal);
            }

            $usuarioData =
                $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($usuario);

            if ($usuarioData) {
                $nivelActual = $this->obtenerDificultadJugador();

                $this->usuarioModel->actualizarNivel($usuario, $nivelActual);
            }
        }

        $_SESSION['resultado_partida'] = [
            'usuarioNombre' => $usuario,
            'usuarioPuntaje' => $puntajeFinal,
            'preguntaId' => $_SESSION['id_pregunta_actual'] ?? null,
            'pregunta' => $_SESSION['pregunta_actual'] ?? "",
            'respuestaCorrecta' => $_SESSION['respuesta_correcta'] ?? "",
        ];

        $this->limpiarSesionPartida();

        Redirect::to("/partida/resultado");
        return;
    }

    public function resultado()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        if (!isset($_SESSION['resultado_partida'])) {
            Redirect::to("/usuario/mostrarUsuarioLobby");
            return;
        }

        $resultado = $_SESSION['resultado_partida'];
        $resultado["reporteMensaje"] = $_SESSION["reporte_mensaje"] ?? null;
        $resultado["reporteError"] = $_SESSION["reporte_error"] ?? null;

        unset($_SESSION["reporte_mensaje"]);
        unset($_SESSION["reporte_error"]);

        $this->renderer->render("partidaTerminadaView", $resultado);
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
        unset($_SESSION["tiempo_inicio_pregunta"]);
        unset($_SESSION["mensaje_ruleta"]);
        unset($_SESSION["respuestas_pregunta_actual"]);
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
        if (!$this->requiereUsuarioComun()) {
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
        if (!isset($_SESSION['usuario_id'])) {
            return "facil";
        }

        $ratio = $this->partidaModel->obtenerRatioCorrectasUsuario($_SESSION['usuario_id']);

        if ($ratio === null) {
            return "facil";
        }

        if ($ratio >= 70) {
            return "dificil";
        }

        if ($ratio >= 30) {
            return "medio";
        }

        return "facil";
    }

    private function obtenerPreguntasParaCategoria($categoriaId, $dificultad, $usuarioId)
    {
        $preguntasMostradasEnPartida = $_SESSION['preguntas_respondidas'] ?? [];
        $intentos = [
            [$dificultad, true, $preguntasMostradasEnPartida],
            [$dificultad, false, $preguntasMostradasEnPartida],
            [null, true, $preguntasMostradasEnPartida],
            [null, false, $preguntasMostradasEnPartida],
            [null, false, []]
        ];

        foreach ($intentos as $intento) {
            $dificultadBuscada = $intento[0];
            $excluirVistas = $intento[1];
            $excluirPartida = $intento[2];
            $preguntas = $this->preguntaModel->buscarPreguntasParaJuego(
                $categoriaId,
                $dificultadBuscada,
                $usuarioId,
                $excluirVistas,
                $excluirPartida
            );

            if (!empty($preguntas)) {
                return $preguntas;
            }
        }

        return [];
    }

    private function registrarPreguntaMostradaEnSesion($preguntaId)
    {
        if (!isset($_SESSION['preguntas_respondidas']) || !is_array($_SESSION['preguntas_respondidas'])) {
            $_SESSION['preguntas_respondidas'] = [];
        }

        $preguntaId = (int)$preguntaId;

        if (!in_array($preguntaId, $_SESSION['preguntas_respondidas'], true)) {
            $_SESSION['preguntas_respondidas'][] = $preguntaId;
        }
    }

    public function reportar()
    {
        $this->prepararReporte();
    }

    public function prepararReporte()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        $preguntaId = $this->request->post("pregunta_id", $_SESSION["id_pregunta_actual"] ?? null);

        if ($preguntaId === null || $preguntaId === "") {
            Redirect::to("/partida/partidaTerminada");
            return;
        }

        $_SESSION["reporte_pregunta_id"] = $preguntaId;
        $_SESSION["reporte_resultado"] = $_SESSION["resultado_partida"] ?? [
            "usuarioNombre" => $_SESSION["usuario"] ?? "Jugador",
            "usuarioPuntaje" => $_SESSION["puntaje"] ?? 0,
            "preguntaId" => $preguntaId,
            "pregunta" => $_SESSION["pregunta_actual"] ?? "",
            "respuestaCorrecta" => $_SESSION["respuesta_correcta"] ?? ""
        ];

        if (isset($_SESSION["id_partida"])) {
            $this->partidaModel->finalizarPartida($_SESSION["id_partida"], $_SESSION["puntaje"] ?? 0);
        }

        $this->limpiarSesionPartida();

        Redirect::to("/partida/reportePregunta");
    }

    public function reportePregunta()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        $preguntaId = $_SESSION["reporte_pregunta_id"] ?? null;

        if ($preguntaId === null) {
            Redirect::to("/usuario/renderizarLobby");
            return;
        }

        $pregunta = $this->preguntaModel->buscarDetallePregunta($preguntaId);

        if ($pregunta === null) {
            Redirect::to("/usuario/renderizarLobby");
            return;
        }

        $respuestas = $this->respuestasModel->buscarRespuestasPorPregunta($preguntaId);

        foreach ($respuestas as $indice => $respuesta) {
            $respuestas[$indice]["es_correcta_bool"] = (int)$respuesta["es_correcta"] === 1;
        }

        $this->renderer->render("reportePreguntaView", [
            "pregunta" => $pregunta,
            "respuestas" => $respuestas,
            "error" => $_SESSION["reporte_error"] ?? null
        ]);

        unset($_SESSION["reporte_error"]);
    }

    public function enviarReporte()
    {
        if (!$this->requiereUsuarioComun()) {
            return;
        }

        $preguntaId = $_SESSION["reporte_pregunta_id"] ?? null;

        if ($preguntaId === null) {
            Redirect::to("/usuario/renderizarLobby");
            return;
        }

        $motivo = trim($this->request->post("motivo", ""));
        $comentario = trim($this->request->post("comentario", ""));

        if ($motivo === "") {
            $_SESSION["reporte_error"] = "Seleccioná un motivo para reportar la pregunta.";
            Redirect::to("/partida/reportePregunta");
            return;
        }

        $reportado = $this->preguntaModel->reportarPregunta($_SESSION["usuario_id"], $preguntaId, $motivo, $comentario);

        $_SESSION["resultado_partida"] = $_SESSION["reporte_resultado"] ?? [
            "usuarioNombre" => $_SESSION["usuario"] ?? "Jugador",
            "usuarioPuntaje" => 0,
            "preguntaId" => $preguntaId,
            "pregunta" => "",
            "respuestaCorrecta" => ""
        ];
        $_SESSION["reporte_mensaje"] = $reportado
            ? "Pregunta reportada correctamente."
            : "Ya reportaste esta pregunta anteriormente.";

        unset($_SESSION["reporte_pregunta_id"]);
        unset($_SESSION["reporte_resultado"]);

        Redirect::to("/partida/resultado");
    }

    public function cancelarReporte()
    {
        unset($_SESSION["reporte_pregunta_id"]);
        unset($_SESSION["reporte_resultado"]);
        unset($_SESSION["reporte_error"]);

        Redirect::to("/usuario/renderizarLobby");
    }

    private function requiereUsuarioComun()
    {
        if (!isset($_SESSION['usuario'])) {
            Redirect::to("/usuario/iniciarSesion");
            return false;
        }

        if (($_SESSION["rol"] ?? "usuario") !== "usuario") {
            Redirect::to("/usuario/renderizarLobby");
            return false;
        }

        return true;
    }
}
