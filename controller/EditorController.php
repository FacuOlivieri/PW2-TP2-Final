<?php

class EditorController
{
    private $preguntaModel;
    private $usuarioModel;
    private $renderer;
    private $request;

    public function __construct($preguntaModel, $usuarioModel, $renderer, $request)
    {
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
        $this->request = $request;
    }

    private function verificarEditor()
    {
        if (!isset($_SESSION["usuario"]) || !$this->usuarioModel->esEditor($_SESSION["usuario"])) {
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
}
