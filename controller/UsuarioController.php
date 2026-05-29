<?php

class UsuarioController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model    = $model;
        $this->renderer = $renderer;
        $this->request  = $request;
    }

    public function inicio()
    {
        $this->renderer->render("homeView");
    }

    public function registrarse()
    {
        $this->renderer->render("registroView");
    }

    public function iniciarSesion()
    {
        $this->renderer->render("loginView");
    }

    public function procesarLogin()
    {
        $this->renderer->render("loginView", [
            'mensaje' => 'El inicio de sesión todavía no está implementado.'
        ]);
    }

    public function ver()
    {
        $this->registrarse();
    }

    public function procesarRegistro()
    {
        $nombre = $this->request->post('nombre_completo');
        $anio = $this->request->post('anio_nacimiento');
        $sexo = $this->request->post('sexo');
        $pais = $this->request->post('pais');
        $ciudad = $this->request->post('ciudad');
        $mail = $this->request->post('mail');
        $username = $this->request->post('username');

        $password = password_hash(
            $this->request->post('password'),
            PASSWORD_DEFAULT
        );

        $foto = $_FILES['foto_perfil']['name'];

        $this->model->alta(
            $nombre,
            $anio,
            $sexo,
            $pais,
            $ciudad,
            $mail,
            $password,
            $username,
            $foto
        );

        Redirect::to("/usuarios/iniciarSesion");
    }

}
