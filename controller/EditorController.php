<?php

class EditorController
{
    private $preguntaModel;
    private $usuarioModel;
    private $renderer;

    public function __construct($preguntaModel, $usuarioModel, $renderer)
    {
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->renderer = $renderer;
    }

    private function verificarEditor()
    {
        if (!$this->usuarioModel->esEditor($_SESSION["usuario"])) {
            Redirect::to("/usuario/lobby");
            exit();
        }
    }

    public function verReportes()
    {
        $this->checkEditor();

        $sql = "SELECT * FROM preguntas_reportadas 
            WHERE estado = 'pendiente' 
        ";

        $reportes = $this->preguntaModel->query($sql);

        $this->renderer->render("reportesView", ["reportes"=>$reportes]);
    }

    public function resolverReporte()
    {
        $this->checkEditor();

        $id = $_POST["id"];
        $estado = $_POST["estado"];

        $sql = "UPDATE preguntas_reportadas 
            SET estado = ? 
            WHERE id = ?";
            
        $this->preguntaModel->execute($sql, [$estado, $id]);

        Redirect::to("/editor/reportes");
    }
}