<?php

class PreguntaCreadaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function crear($texto, $categoriaId, $usuarioId)
    {
        $sql = "INSERT INTO preguntas_creadas (texto, categoria_id, usuario_id, estado)
                VALUES (?, ?, ?, 'pendiente')";

        $this->database->execute($sql, [$texto, $categoriaId, $usuarioId]);

        return $this->database->lastInsertId();
    }

    public function guardarRespuesta($preguntaCreadaId, $texto, $esCorrecta)
    {
        $sql = "INSERT INTO respuestas_creadas (pregunta_creada_id, texto, es_correcta)
                VALUES (?, ?, ?)";

        $this->database->execute($sql, [$preguntaCreadaId, $texto, $esCorrecta ? 1 : 0]);
    }

    public function obtenerPorEstado($estado = null)
    {
        $sql = "SELECT pc.id,
                       pc.texto,
                       pc.estado,
                       pc.fecha_creacion,
                       c.nombre AS categoria,
                       u.username AS usuario
                FROM preguntas_creadas pc
                INNER JOIN categorias c ON c.id = pc.categoria_id
                INNER JOIN usuarios u ON u.id = pc.usuario_id";

        $params = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " WHERE pc.estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY pc.fecha_creacion DESC";

        return $this->database->query($sql, $params);
    }

    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM preguntas_creadas WHERE id = ?";
        $resultado = $this->database->query($sql, [$id]);
        return $resultado[0] ?? null;
    }

    public function obtenerDetallePorId($id)
    {
        $sql = "SELECT pc.id,
                       pc.texto,
                       pc.categoria_id,
                       pc.usuario_id,
                       pc.estado,
                       pc.fecha_creacion,
                       c.nombre AS categoria,
                       u.username AS usuario
                FROM preguntas_creadas pc
                INNER JOIN categorias c ON c.id = pc.categoria_id
                INNER JOIN usuarios u ON u.id = pc.usuario_id
                WHERE pc.id = ?";

        $resultado = $this->database->query($sql, [$id]);
        return $resultado[0] ?? null;
    }

    public function obtenerRespuestas($preguntaCreadaId)
    {
        $sql = "SELECT * FROM respuestas_creadas WHERE pregunta_creada_id = ?";
        return $this->database->query($sql, [$preguntaCreadaId]);
    }

    public function cambiarEstado($id, $estado)
    {
        $sql = "UPDATE preguntas_creadas SET estado = ? WHERE id = ?";
        $this->database->execute($sql, [$estado, $id]);
    }
}
