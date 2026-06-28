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
        $jugadoresPorPais = $this->usuariosModel->jugadoresPorPais($filtroSeleccionado);
        $jugadoresPorSexo = $this->usuariosModel->jugadoresPorSexo($filtroSeleccionado);
        $jugadoresPorEdad = $this->usuariosModel->jugadoresPorEdad($filtroSeleccionado);
        $partidasJugadas =  $this->partidaModel->cantidadTotalPartidas($filtroSeleccionado);
        $totalPreguntas = $this->preguntasModel->cantidadTotalPreguntas($filtroSeleccionado);
        $totalPreguntasCreadas = $this->preguntasModel->cantidadPreguntasCreadas($filtroSeleccionado);
        $porcentajeCorrectasPorUsuario = $this->usuariosModel->porcentajeRespuestasCorrectasPorUsuario($filtroSeleccionado);
        $partidasPorPeriodo = $this->partidaModel->partidasPorPeriodo($filtroSeleccionado);
        $preguntasCreadasPorPeriodo = $this->preguntasModel->preguntasCreadasPorPeriodo($filtroSeleccionado);



        $this->renderer->render("administradorDashboardView", [
            "filtro_dia"     => ($filtroSeleccionado === 'dia'),
            "filtro_semana"  => ($filtroSeleccionado === 'semana'),
            "filtro_mes"     => ($filtroSeleccionado === 'mes'),
            "filtro_anio"    => ($filtroSeleccionado === 'anio'),
            "filtroSeleccionado" => ucfirst($filtroSeleccionado),

            "totalJugadores" => $totalJugadores,
            "jugadoresNuevos" => $totalJugadoresNuevos,
            "jugadoresPorPais" => $jugadoresPorPais,
            "jugadoresPorSexo" => $jugadoresPorSexo,
            "jugadoresPorEdad" => $jugadoresPorEdad,
            "partidasJugadas" => $partidasJugadas,
            "totalPreguntas" => $totalPreguntas,
            "totalPreguntasCreadas" => $totalPreguntasCreadas,
            "porcentajeCorrectasPorUsuario" => $porcentajeCorrectasPorUsuario,
            "partidasPorPeriodo" => $partidasPorPeriodo,
            "preguntasCreadasPorPeriodo" => $preguntasCreadasPorPeriodo,
            "jugadoresPorPaisJson" => $this->crearDatasetJson($jugadoresPorPais, "pais", "cantidad"),
            "jugadoresPorSexoJson" => $this->crearDatasetJson($jugadoresPorSexo, "sexo", "cantidad"),
            "jugadoresPorEdadJson" => $this->crearDatasetJson($jugadoresPorEdad, "rango", "cantidad"),
            "porcentajeCorrectasPorUsuarioJson" => $this->crearDatasetJson($porcentajeCorrectasPorUsuario, "usuario", "porcentaje"),
            "partidasPorPeriodoJson" => $this->crearDatasetJson($partidasPorPeriodo, "periodo", "cantidad"),
            "preguntasCreadasPorPeriodoJson" => $this->crearDatasetJson($preguntasCreadasPorPeriodo, "periodo", "cantidad"),

        ]);
    }



    private function validarFiltro($filtroSeleccionado){
        $filtrosValidos = ['dia', 'semana', 'mes', 'anio'];
        if (!in_array($filtroSeleccionado, $filtrosValidos)) {
            $filtroSeleccionado = "dia";
        }
        return $filtroSeleccionado;
    }

    private function crearDatasetJson($filas, $labelKey, $valueKey)
    {
        $labels = [];
        $values = [];

        foreach ($filas as $fila) {
            $labels[] = (string)($fila[$labelKey] ?? "");
            $values[] = (float)($fila[$valueKey] ?? 0);
        }

        return json_encode([
            "labels" => $labels,
            "values" => $values
        ]);
    }

}

