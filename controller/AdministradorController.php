<?php
class AdministradorController{

    private $renderer;
    private $request;
    private $usuariosModel;
    private $partidaModel;
    private $preguntasModel;
    private $respuestasModel;

    public function __construct($renderer, $request, $usuariosModel, $partidaModel, $preguntasModel, $respuestasModel)
    {
        $this->renderer = $renderer;
        $this->request = $request;
        $this->usuariosModel = $usuariosModel;
        $this->partidaModel = $partidaModel;
        $this->preguntasModel = $preguntasModel;
        $this->respuestasModel = $respuestasModel;
    }


    public function verDashboard(){
        if (!isset($_SESSION["usuario"])) {
            Redirect::to("/usuario/iniciarSesion");
            return;
        }

        $this->usuariosModel->requireAdministrador($_SESSION["usuario"]);















        $this->renderer->render("administradorDashboardView", [


        ]);
    }


}
