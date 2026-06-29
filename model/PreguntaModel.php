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
        $sql = "SELECT p.*, c.nombre AS categoria
                FROM preguntas p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estado = 'aprobada'
                ORDER BY p.fecha_creacion DESC, p.id DESC";
        return $this->database->query($sql);
    }

    public function buscarPreguntasEditorPorEstado($estado)
    {
        $sql = "SELECT p.id,
                       p.texto,
                       p.estado,
                       p.fecha_creacion,
                       c.nombre AS categoria,
                       COALESCE(u.username, 'Editor') AS usuario
                FROM preguntas p
                INNER JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN usuarios u ON u.id = p.creada_por
                WHERE p.estado = ?
                ORDER BY p.fecha_creacion DESC, p.id DESC";

        return $this->database->query($sql, [$estado]);
    }

    public function buscarDetallePregunta($idPregunta)
    {
        $sql = "SELECT p.*, c.nombre AS categoria
                FROM preguntas p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = ?";

        $resultado = $this->database->query($sql, [$idPregunta]);
        return $resultado[0] ?? null;
    }

    public function buscarPreguntaSegunId($idPregunta)
    {
        $sql = "SELECT * FROM preguntas WHERE id = ?";
        $resultado = $this->database->query($sql, [$idPregunta]);

        return $resultado[0] ?? null;
    }

    public function buscarPreguntasPorCategoria($categoriaId)
    {
        $sql = "SELECT * FROM preguntas WHERE categoria_id = ? AND estado = 'aprobada'";
        return $this->database->query($sql, [$categoriaId]);
    }

    public function crear($texto, $categoriaId, $creadaPor = null)
    {
        $sql = "INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
                VALUES (?, ?, 'aprobada', ?)";

        $this->database->execute($sql, [$texto, $categoriaId, $creadaPor]);

        return $this->database->lastInsertId();
    }

    public function crearCompleta($texto, $categoriaId, $dificultad, $estado, $creadaPor = null)
    {
        $sql = "INSERT INTO preguntas (texto, categoria_id, dificultad, estado, creada_por)
                VALUES (?, ?, ?, ?, ?)";

        $this->database->execute($sql, [$texto, $categoriaId, $dificultad, $estado, $creadaPor]);

        return $this->database->lastInsertId();
    }

    public function actualizarCompleta($idPregunta, $texto, $categoriaId, $dificultad, $estado)
    {
        $sql = "UPDATE preguntas
                SET texto = ?, categoria_id = ?, dificultad = ?, estado = ?
                WHERE id = ?";

        $this->database->execute($sql, [$texto, $categoriaId, $dificultad, $estado, $idPregunta]);
    }

    public function darDeBaja($idPregunta)
    {
        $sql = "UPDATE preguntas SET estado = 'rechazada' WHERE id = ?";
        $this->database->execute($sql, [$idPregunta]);
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

    public function reportarPregunta($usuarioId, $preguntaId, $motivo, $comentario = "")
    {
        $sql = "SELECT id FROM preguntas_reportadas WHERE pregunta_id = ? AND usuario_id = ? LIMIT 1";
        $existente = $this->database->query($sql, [$preguntaId, $usuarioId]);

        if (!empty($existente)) {
            return false;
        }

        $sql = "INSERT INTO preguntas_reportadas (pregunta_id, usuario_id, motivo, comentario, estado, fecha_reporte)
                VALUES (?, ?, ?, ?, 'pendiente', NOW())";
        $this->database->execute($sql, [$preguntaId, $usuarioId, $motivo, $comentario]);

        $sql = "UPDATE preguntas SET estado = 'reportada' WHERE id = ?";
        $this->database->execute($sql, [$preguntaId]);

        return true;
    }

    public function obtenerReportesPendientes()
    {
        $sql = "SELECT pr.id,
                       pr.pregunta_id,
                       pr.usuario_id,
                       pr.motivo,
                       pr.comentario,
                       pr.fecha_reporte AS fecha,
                       p.texto AS pregunta,
                       u.username AS usuario
                FROM preguntas_reportadas pr
                INNER JOIN preguntas p ON p.id = pr.pregunta_id
                INNER JOIN usuarios u ON u.id = pr.usuario_id
                WHERE pr.estado = 'pendiente'
                ORDER BY pr.fecha DESC";

        return $this->database->query($sql);
    }

    public function obtenerReporteDetalle($reporteId)
    {
        $sql = "SELECT pr.id,
                       pr.pregunta_id,
                       pr.usuario_id,
                       pr.motivo,
                       pr.comentario,
                       pr.estado AS estado_reporte,
                       pr.fecha_reporte AS fecha,
                       p.texto AS pregunta,
                       p.estado AS estado_pregunta,
                       p.dificultad,
                       c.nombre AS categoria,
                       u.username AS usuario
                FROM preguntas_reportadas pr
                INNER JOIN preguntas p ON p.id = pr.pregunta_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN usuarios u ON u.id = pr.usuario_id
                WHERE pr.id = ?";

        $resultado = $this->database->query($sql, [$reporteId]);
        return $resultado[0] ?? null;
    }

    public function resolverReporte($reporteId, $estado)
    {
        $sql = "SELECT pregunta_id FROM preguntas_reportadas WHERE id = ?";
        $resultado = $this->database->query($sql, [$reporteId]);

        if (empty($resultado)) {
            return;
        }

        $preguntaId = $resultado[0]["pregunta_id"];

        $sql = "UPDATE preguntas_reportadas SET estado = ? WHERE id = ?";
        $this->database->execute($sql, [$estado, $reporteId]);

        $estadoPregunta = $estado === "aprobada" ? "rechazada" : "aprobada";
        $sql = "UPDATE preguntas SET estado = ? WHERE id = ?";
        $this->database->execute($sql, [$estadoPregunta, $preguntaId]);
    }



    public function cantidadTotalPreguntas($filtroSeleccionado)
    {
        $condicionFecha = $this->obtenerCondicionFecha($filtroSeleccionado, 'fecha_creacion');

        $sql = "SELECT COUNT(*) as cantidad 
                FROM preguntas 
                WHERE estado = 'aprobada' $condicionFecha";

        $resultado = $this->database->query($sql);
        return $resultado[0]['cantidad'] ?? 0;
    }

    // 2. Cantidad de preguntas sugeridas o enviadas al sistema (independientemente de su estado actual)
    public function cantidadPreguntasCreadas($filtroSeleccionado)
    {
        $condicionFecha = $this->obtenerCondicionFecha($filtroSeleccionado, 'fecha_creacion');


        $sql = "SELECT COUNT(*) as cantidad 
                FROM preguntas 
                WHERE 1=1 $condicionFecha";

        $resultado = $this->database->query($sql);
        return $resultado[0]['cantidad'] ?? 0;
    }

    public function preguntasCreadasPorPeriodo($filtroSeleccionado)
    {
        $condicionFecha = $this->obtenerCondicionFecha($filtroSeleccionado, 'fecha_creacion');
        $formato = $this->obtenerFormatoPeriodo($filtroSeleccionado);

        $sql = "SELECT DATE_FORMAT(fecha_creacion, '$formato') AS periodo,
                       COUNT(*) AS cantidad
                FROM preguntas
                WHERE 1=1 $condicionFecha
                GROUP BY periodo
                ORDER BY MIN(fecha_creacion) ASC";

        return $this->database->query($sql);
    }

    private function obtenerCondicionFecha($filtro, $campoFecha)
    {
        switch ($filtro) {
            case 'dia':
                return " AND $campoFecha >= DATE(NOW())";
            case 'semana':
                return " AND $campoFecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'mes':
                return " AND $campoFecha >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'anio':
                return " AND YEAR($campoFecha) = YEAR(NOW())";
            default:
                return "";
        }
    }

    private function obtenerFormatoPeriodo($filtro)
    {
        switch ($filtro) {
            case 'dia':
                return '%H:00';
            case 'semana':
            case 'mes':
                return '%d/%m';
            case 'anio':
                return '%m/%Y';
            default:
                return '%d/%m';
        }
    }
}
