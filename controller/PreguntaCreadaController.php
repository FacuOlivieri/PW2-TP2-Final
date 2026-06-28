<?php

class PreguntaCreadaController
{
    private $preguntaCreadaModel;
    private $categoriaModel;
    private $usuarioModel;
    private $preguntaModel;
    private $respuestaModel;
    private $renderer;
    private $request;

    public function __construct(
        $preguntaCreadaModel,
        $categoriaModel,
        $usuarioModel,
        $preguntaModel,
        $respuestaModel,
        $renderer,
        $request
    ) {
        $this->preguntaCreadaModel = $preguntaCreadaModel;
        $this->categoriaModel = $categoriaModel;
        $this->usuarioModel = $usuarioModel;
        $this->preguntaModel = $preguntaModel;
        $this->respuestaModel = $respuestaModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function crear()
    {
        if (!$this->requiereLogin()) {
            return;
        }

        if ($this->esAdmin()) {
            Redirect::to("/preguntaCreada/propuestas");
            return;
        }

        $this->renderer->render("crearPreguntaView", [
            "categorias" => $this->categoriaModel->obtenerTodas(),
            "exito" => $_SESSION["pregunta_creada_exito"] ?? null
        ]);

        unset($_SESSION["pregunta_creada_exito"]);
    }

    public function guardar()
    {
        if (!$this->requiereLogin()) {
            return;
        }

        if ($this->esAdmin()) {
            Redirect::to("/preguntaCreada/propuestas");
            return;
        }

        $texto = trim($this->request->post("texto", ""));
        $categoriaId = $this->request->post("categoria_id", "");
        $respuestas = [
            trim($this->request->post("respuesta_1", "")),
            trim($this->request->post("respuesta_2", "")),
            trim($this->request->post("respuesta_3", "")),
            trim($this->request->post("respuesta_4", ""))
        ];
        $correcta = $this->request->post("respuesta_correcta", null);

        $error = $this->validar($texto, $categoriaId, $respuestas, $correcta);

        if ($error !== null) {
            $this->renderer->render("crearPreguntaView", [
                "categorias" => $this->categoriaModel->obtenerTodas(),
                "error" => $error,
                "texto" => $texto,
                "respuesta_1" => $respuestas[0],
                "respuesta_2" => $respuestas[1],
                "respuesta_3" => $respuestas[2],
                "respuesta_4" => $respuestas[3]
            ]);
            return;
        }

        $texto = $this->normalizarSignosPregunta($texto);

        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION["usuario"]);

        $preguntaCreadaId = $this->preguntaCreadaModel->crear($texto, $categoriaId, $usuario["id"]);

        $indiceCorrecta = (int)$correcta;

        foreach ($respuestas as $indice => $textoRespuesta) {
            $this->preguntaCreadaModel->guardarRespuesta(
                $preguntaCreadaId,
                $textoRespuesta,
                ($indice + 1) === $indiceCorrecta
            );
        }

        $_SESSION["pregunta_creada_exito"] = "Tu pregunta fue enviada y quedó pendiente de revisión. ¡Gracias por colaborar!";
        Redirect::to("/preguntaCreada/crear");
        return;
    }

    private function normalizarSignosPregunta($texto)
    {
        $texto = trim($texto);

        if (substr($texto, 0, 2) !== '¿') {
            $texto = '¿' . $texto;
        }

        if (substr($texto, -1) !== '?') {
            $texto .= '?';
        }

        return $texto;
    }

    public function propuestas()
    {
        if (!$this->requiereAdmin()) {
            return;
        }

        $estado = $this->request->get("estado", "pendiente");
        $estadosValidos = ["pendiente", "aceptada", "rechazada"];

        if (!in_array($estado, $estadosValidos, true)) {
            $estado = "pendiente";
        }

        $this->renderer->render("preguntasPropuestasView", [
            "propuestas" => $this->preguntaCreadaModel->obtenerPorEstado($estado),
            "estado" => $estado,
            "filtroPendiente" => $estado === "pendiente",
            "filtroAceptada" => $estado === "aceptada",
            "filtroRechazada" => $estado === "rechazada"
        ]);
    }

    public function ver()
    {
        if (!$this->requiereAdmin()) {
            return;
        }

        $id = $this->request->get("id");
        $propuesta = $this->preguntaCreadaModel->obtenerDetallePorId($id);

        if ($propuesta === null) {
            Redirect::to("/preguntaCreada/propuestas");
            return;
        }

        $respuestas = $this->preguntaCreadaModel->obtenerRespuestas($propuesta["id"]);

        foreach ($respuestas as $indice => $respuesta) {
            $respuestas[$indice]["es_correcta_bool"] = (int)$respuesta["es_correcta"] === 1;
        }

        $this->renderer->render("preguntaPropuestaDetalleView", [
            "propuesta" => $propuesta,
            "respuestas" => $respuestas,
            "esPendiente" => $propuesta["estado"] === "pendiente"
        ]);
    }

    public function aprobar()
    {
        if (!$this->requiereAdmin()) {
            return;
        }

        $id = $this->request->post("pregunta_creada_id");
        $propuesta = $this->preguntaCreadaModel->obtenerPorId($id);

        if ($propuesta === null || $propuesta["estado"] !== "pendiente") {
            Redirect::to("/preguntaCreada/propuestas");
            return;
        }

        $preguntaId = $this->preguntaModel->crear(
            $propuesta["texto"],
            $propuesta["categoria_id"],
            $propuesta["usuario_id"]
        );

        $respuestas = $this->preguntaCreadaModel->obtenerRespuestas($propuesta["id"]);

        foreach ($respuestas as $respuesta) {
            $this->respuestaModel->crear(
                $preguntaId,
                $respuesta["texto"],
                (int)$respuesta["es_correcta"] === 1
            );
        }

        $this->preguntaCreadaModel->cambiarEstado($propuesta["id"], "aceptada");

        Redirect::to("/preguntaCreada/propuestas");
    }

    public function rechazar()
    {
        if (!$this->requiereAdmin()) {
            return;
        }

        $id = $this->request->post("pregunta_creada_id");
        $propuesta = $this->preguntaCreadaModel->obtenerPorId($id);

        if ($propuesta === null || $propuesta["estado"] !== "pendiente") {
            Redirect::to("/preguntaCreada/propuestas");
            return;
        }

        $this->preguntaCreadaModel->cambiarEstado($propuesta["id"], "rechazada");

        Redirect::to("/preguntaCreada/propuestas");
    }

    private function validar($texto, $categoriaId, $respuestas, $correcta)
    {
        if ($texto === "") {
            return "La pregunta no puede estar vacía.";
        }

        if ($categoriaId === "" || $this->categoriaModel->obtenerPorId($categoriaId) === null) {
            return "Seleccioná una categoría válida.";
        }

        foreach ($respuestas as $respuesta) {
            if ($respuesta === "") {
                return "Todas las respuestas son obligatorias.";
            }
        }

        if ($correcta === null || !in_array((int)$correcta, [1, 2, 3, 4], true)) {
            return "Tenés que marcar una única respuesta correcta.";
        }

        return null;
    }

    private function requiereLogin()
    {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return false;
        }

        return true;
    }

    private function requiereAdmin()
    {
        if (!$this->requiereLogin()) {
            return false;
        }

        if (!$this->esAdmin()) {
            Redirect::to("/usuario/renderizarLobby");
            return false;
        }

        return true;
    }

    private function esAdmin()
    {
        return $this->usuarioModel->esAdministrador($_SESSION["usuario"]);
    }
}
