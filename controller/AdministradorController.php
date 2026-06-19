<?php
class AdministradorController
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






}