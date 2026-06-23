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

        $filtroSeleccionado = $this->request->get("filtrar") ?? "dia";
        $filtroSeleccionado = $this->validarFiltro($filtroSeleccionado);


        $totalJugadores = $this->usuariosModel->cantidadJugadores($filtroSeleccionado);
        $totalJugadoresNuevos = $this->usuariosModel->cantidadJugadoresNuevos($filtroSeleccionado);
        $jugadoresPorPais = $this->usuariosModel-> jugadoresPorPais($filtroSeleccionado);
        $jugadoresPorSexo = $this->usuariosModel->jugadoresPorSexo($filtroSeleccionado);
        $jugadoresPorEdad = $this->usuariosModel->jugadoresPorEdad($filtroSeleccionado);
        $partidasJugadas =  $this->partidaModel->cantidadTotalPartidas($filtroSeleccionado);
        $totalPreguntas = $this->preguntasModel->cantidadTotalPreguntas($filtroSeleccionado);
        $totalPreguntasCreadas = $this->preguntasModel->cantidadPreguntasCreadas($filtroSeleccionado);


        $this->renderer->render("administradorDashboardView", [
            "filtro_dia"     => ($filtroSeleccionado === 'dia'),
            "filtro_semana"  => ($filtroSeleccionado === 'semana'),
            "filtro_anio"    => ($filtroSeleccionado === 'anio'),

            "totalJugadores" => $totalJugadores,
            "jugadoresNuevos" => $totalJugadoresNuevos,
            "jugadoresPorPais" => $jugadoresPorPais,
            "jugadoresPorSexo" => $jugadoresPorSexo,
            "jugadoresPorEdad" => $jugadoresPorEdad,
            "partidasJugadas" => $partidasJugadas,
            "totalPreguntas" => $totalPreguntas,
            "totalPreguntasCreadas" => $totalPreguntasCreadas,

        ]);
    }

    private function validarFiltro($filtroSeleccionado){
        $filtrosValidos = ['dia', 'semana', 'anio'];
        if (!in_array($filtroSeleccionado, $filtrosValidos)) {
            $filtroSeleccionado = "dia";
        }
        return $filtroSeleccionado;
    }

}

