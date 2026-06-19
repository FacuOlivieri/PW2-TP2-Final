<?php

class PreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buscarTodasLasPreguntas()
    {
        $sql = "SELECT * FROM preguntas";
        return $this->database->query($sql);
    }

    public function buscarPreguntaSegunId($idPregunta)
    {
        $sql = "SELECT * FROM preguntas WHERE id = ?";
        $resultado = $this->database->query($sql, [$idPregunta]);

        return $resultado[0] ?? null;
    }

    public function sumarPreguntasEntregadas($idPregunta)
    {
        $sql = "UPDATE preguntas
                SET veces_entregada = veces_entregada + 1
                WHERE id = ?";

        $this->database->execute($sql, [$idPregunta]);
    }

    public function sumarPreguntasCorrectas($idPregunta)
    {
        $sql = "UPDATE preguntas
                SET veces_correcta = veces_correcta + 1
                WHERE id = ?";

        $this->database->execute($sql, [$idPregunta]);
    }

    public function calcularDificultad($idPregunta)
    {
        $pregunta = $this->buscarPreguntaSegunId($idPregunta);

        if (!$pregunta) {
            return;
        }

        $entregadas = (int)$pregunta["veces_entregada"];
        $correctas = (int)$pregunta["veces_correcta"];

        if ($entregadas === 0) {
            return;
        }
        $porcentaje = ($correctas / $entregadas) * 100;

        if ($porcentaje >= 70) {
            $dificultad = "facil";
        } elseif ($porcentaje >= 30) {
            $dificultad = "medio";
        } else {
            $dificultad = "dificil";
        }

        $sql = "UPDATE preguntas
                SET dificultad = ?
                WHERE id = ?";

        $this->database->execute($sql, [$dificultad, $idPregunta]);
    }

    public function buscarPreguntasPorDificultad($dificultad)
    {
        $sql = "SELECT * FROM preguntas WHERE dificultad = ? ORDER BY RAND()";
        return $this->database->query($sql, [$dificultad]);
    }
}