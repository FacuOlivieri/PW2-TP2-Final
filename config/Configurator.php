<?php
class Configurator {

    private $config;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");
    }

    public function getUsuariosController()
    {
        $mailHelper = null;
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') && class_exists('MailHelper')) {
            $mailHelper = new MailHelper();
        }
        return new UsuarioController(
            $this->getUsuarios(),
            $this->getRenderer(),
            new Request(),
            $this->getPartida(),
            $this->getCodigo(),
            $mailHelper
        );
    }

    public function getUsuarioController()
    {
        return $this->getUsuariosController();
    }

    public function getAdministradorController()
    {
        return new AdministradorController(
            $this->getRenderer(),
            new Request(),
            $this->getUsuarios(),
            $this->getPartida(),
            $this->getPreguntas(),
            $this->getRespuestaModel()
        );
    }

    public function getEditorController()
    {
        return new EditorController(
            $this->getPreguntas(),
            $this->getUsuarios(),
            $this->getRenderer(),
            new Request()
        );
    }

    public function getPartidaController()
    {
        return new PartidaController($this->getPartida(),
                                     $this->getUsuarios(),
                                     $this->getRenderer(),
                                     $this->getPreguntas(),
                                     $this->getEstadoPartida(),
                                     $this->getRespuestaModel(),
                                     $this->getCategoria(),
                                     new Request());
        //Para no repetir Request tal vez podriamos compaginarlo en un metodo.
    }

    public function getPreguntaCreadaController()
    {
        return new PreguntaCreadaController(
            $this->getPreguntaCreada(),
            $this->getCategoria(),
            $this->getUsuarios(),
            $this->getPreguntas(),
            $this->getRespuestaModel(),
            $this->getRenderer(),
            new Request()
        );
    }

    private function getDatabase()
    {
        return new MyDatabase(
            $this->config['hostname'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );
    }

    private function getRenderer()
    {
        return new MustacheRenderer(__DIR__ . '/../view');
    }

    private function getUsuarios()
    {
        return new UsuarioModel($this->getDatabase());
    }

    public function getRouter()
    {
        return new Router($this, 'usuarios', 'inicio');
    }

    public function getOrDefault($controllerName, $defaultControllerName)
    {
        $getter = 'get' . ucfirst($controllerName) . 'Controller';
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        $defaultGetter = 'get' . ucfirst($defaultControllerName) . 'Controller';
        return $this->{$defaultGetter}();
    }

    private function getPartida()
    {
        return new PartidaModel($this->getDatabase());
    }

    private function getPreguntas()
    {
        return new PreguntaModel($this->getDatabase());
    }

    private function getEstadoPartida()
    {
        return new EstadoPartidaModel($this->getDatabase());
    }

    private function getRespuestaModel()
    {
        return new RespuestaModel($this->getDatabase());
    }

    private function getCategoria()
    {
        return new CategoriaModel($this->getDatabase());
    }

    private function getPreguntaCreada()
    {
        return new PreguntaCreadaModel($this->getDatabase());
    }

    private function getCodigo()
    {
        return new CodigoModel($this->getDatabase());
    }
}
