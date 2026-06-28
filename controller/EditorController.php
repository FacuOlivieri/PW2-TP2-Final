<?php

class EditorController
{
    private $preguntaModel;
    private $categoriaModel;
    private $respuestaModel;
    private $usuarioModel;
    private $renderer;
    private $request;

    public function __construct($preguntaModel, $categoriaModel, $respuestaModel, $usuarioModel, $renderer, $request)
    {
        $this->preguntaModel = $preguntaModel;
        $this->categoriaModel = $categoriaModel;
        $this->respuestaModel = $respuestaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    public function preguntas()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $this->renderer->render("editorPreguntasView", [
            "preguntas" => $this->preguntaModel->buscarTodasLasPreguntas(),
            "categorias" => $this->categoriaModel->obtenerTodas(),
            "mensaje" => $_SESSION["editor_mensaje"] ?? null
        ]);

        unset($_SESSION["editor_mensaje"]);
    }

    public function inicio()
    {
        $this->preguntas();
    }

    public function crear()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $this->renderizarFormularioPregunta("crear");
    }

    public function guardar()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $datos = $this->obtenerDatosFormulario();
        $error = $this->validarDatosPregunta($datos);

        if ($error !== null) {
            $this->renderizarFormularioPregunta("crear", $datos, $error);
            return;
        }

        $usuario = $this->usuarioModel->buscarUsuariosPorNombreDeUsuario($_SESSION["usuario"]);
        $preguntaId = $this->preguntaModel->crearCompleta(
            $datos["texto"],
            $datos["categoria_id"],
            "facil",
            "aprobada",
            $usuario["id"] ?? null
        );

        $this->respuestaModel->reemplazarRespuestas($preguntaId, $datos["respuestas"], $datos["correcta"]);

        $_SESSION["editor_mensaje"] = "Pregunta creada correctamente.";
        Redirect::to("/editor/preguntas");
    }

    public function editar()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $id = $this->request->get("id");
        $pregunta = $this->preguntaModel->buscarDetallePregunta($id);

        if ($pregunta === null) {
            Redirect::to("/editor/preguntas");
            return;
        }

        $this->renderizarFormularioPregunta("editar", $this->datosDesdePregunta($pregunta));
    }

    public function actualizar()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $id = $this->request->post("id");
        $pregunta = $this->preguntaModel->buscarPreguntaSegunId($id);

        if ($pregunta === null) {
            Redirect::to("/editor/preguntas");
            return;
        }

        $datos = $this->obtenerDatosFormulario();
        $datos["id"] = $id;
        $error = $this->validarDatosPregunta($datos);

        if ($error !== null) {
            $this->renderizarFormularioPregunta("editar", $datos, $error);
            return;
        }

        $this->preguntaModel->actualizarCompleta(
            $id,
            $datos["texto"],
            $datos["categoria_id"],
            $datos["dificultad"],
            $datos["estado"]
        );

        $this->respuestaModel->reemplazarRespuestas($id, $datos["respuestas"], $datos["correcta"]);

        $_SESSION["editor_mensaje"] = "Pregunta actualizada correctamente.";
        Redirect::to("/editor/preguntas");
    }

    public function baja()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $id = $this->request->post("id");
        $this->preguntaModel->darDeBaja($id);

        $_SESSION["editor_mensaje"] = "Pregunta dada de baja correctamente.";
        Redirect::to("/editor/preguntas");
    }

    private function verificarEditor()
    {
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/renderizarLobby");
            return false;
        }

        if ($this->usuarioModel->esAdministrador($_SESSION["usuario"]) || !$this->usuarioModel->esEditor($_SESSION["usuario"])) {
            Redirect::to("/usuario/renderizarLobby");
            return false;
        }

        return true;
    }

    public function verReportes()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $reportes = $this->preguntaModel->obtenerReportesPendientes();

        $this->renderer->render("reportesView", ["reportes" => $reportes]);
    }

    public function reportes()
    {
        $this->verReportes();
    }

    public function resolverReporte()
    {
        if (!$this->verificarEditor()) {
            return;
        }

        $id = $this->request->post("id");
        $estado = $this->request->post("estado");

        if (!in_array($estado, ["aprobada", "rechazada"], true)) {
            Redirect::to("/editor/reportes");
            return;
        }

        $this->preguntaModel->resolverReporte($id, $estado);

        Redirect::to("/editor/reportes");
    }

    private function renderizarFormularioPregunta($modo, $datos = [], $error = null)
    {
        $esEditar = $modo === "editar";

        $this->renderer->render("editorPreguntaFormView", [
            "esEditar" => $esEditar,
            "esCrear" => !$esEditar,
            "titulo" => $esEditar ? "Editar pregunta" : "Crear pregunta",
            "accion" => $esEditar ? "/editor/actualizar" : "/editor/guardar",
            "error" => $error,
            "id" => $datos["id"] ?? null,
            "texto" => $datos["texto"] ?? "",
            "respuesta_1" => $datos["respuestas"][0] ?? "",
            "respuesta_2" => $datos["respuestas"][1] ?? "",
            "respuesta_3" => $datos["respuestas"][2] ?? "",
            "respuesta_4" => $datos["respuestas"][3] ?? "",
            "correcta_1" => (int)($datos["correcta"] ?? 1) === 1,
            "correcta_2" => (int)($datos["correcta"] ?? 1) === 2,
            "correcta_3" => (int)($datos["correcta"] ?? 1) === 3,
            "correcta_4" => (int)($datos["correcta"] ?? 1) === 4,
            "categorias" => $this->prepararCategorias($datos["categoria_id"] ?? null),
            "dificultades" => $this->prepararOpciones(["facil", "medio", "dificil"], $datos["dificultad"] ?? "facil"),
            "estados" => $this->prepararOpciones(["aprobada", "pendiente", "rechazada", "reportada"], $datos["estado"] ?? "aprobada")
        ]);
    }

    private function obtenerDatosFormulario()
    {
        return [
            "texto" => trim($this->request->post("texto", "")),
            "categoria_id" => $this->request->post("categoria_id", ""),
            "dificultad" => $this->request->post("dificultad", "facil"),
            "estado" => $this->request->post("estado", "aprobada"),
            "correcta" => (int)$this->request->post("respuesta_correcta", 1),
            "respuestas" => [
                trim($this->request->post("respuesta_1", "")),
                trim($this->request->post("respuesta_2", "")),
                trim($this->request->post("respuesta_3", "")),
                trim($this->request->post("respuesta_4", ""))
            ]
        ];
    }

    private function validarDatosPregunta($datos)
    {
        if ($datos["texto"] === "") {
            return "La pregunta no puede estar vacía.";
        }

        if ($datos["categoria_id"] === "" || $this->categoriaModel->obtenerPorId($datos["categoria_id"]) === null) {
            return "Seleccioná una categoría válida.";
        }

        if (!in_array($datos["dificultad"], ["facil", "medio", "dificil"], true)) {
            return "Seleccioná una dificultad válida.";
        }

        if (!in_array($datos["estado"], ["aprobada", "pendiente", "rechazada", "reportada"], true)) {
            return "Seleccioná un estado válido.";
        }

        foreach ($datos["respuestas"] as $respuesta) {
            if ($respuesta === "") {
                return "Todas las respuestas son obligatorias.";
            }
        }

        if (!in_array($datos["correcta"], [1, 2, 3, 4], true)) {
            return "Tenés que marcar una respuesta correcta.";
        }

        return null;
    }

    private function datosDesdePregunta($pregunta)
    {
        $respuestas = $this->respuestaModel->buscarRespuestasPorPregunta($pregunta["id"]);
        $textos = ["", "", "", ""];
        $correcta = 1;

        foreach (array_slice($respuestas, 0, 4) as $indice => $respuesta) {
            $textos[$indice] = $respuesta["texto"];

            if ((int)$respuesta["es_correcta"] === 1) {
                $correcta = $indice + 1;
            }
        }

        return [
            "id" => $pregunta["id"],
            "texto" => $pregunta["texto"],
            "categoria_id" => $pregunta["categoria_id"],
            "dificultad" => $pregunta["dificultad"] ?? "medio",
            "estado" => $pregunta["estado"] ?? "aprobada",
            "respuestas" => $textos,
            "correcta" => $correcta
        ];
    }

    private function prepararCategorias($categoriaSeleccionada)
    {
        $categorias = $this->categoriaModel->obtenerTodas();

        foreach ($categorias as $indice => $categoria) {
            $categorias[$indice]["seleccionada"] = (int)$categoria["id"] === (int)$categoriaSeleccionada;
        }

        return $categorias;
    }

    private function prepararOpciones($valores, $seleccionado)
    {
        $opciones = [];

        foreach ($valores as $valor) {
            $opciones[] = [
                "valor" => $valor,
                "label" => ucfirst($valor),
                "seleccionada" => $valor === $seleccionado
            ];
        }

        return $opciones;
    }
}
