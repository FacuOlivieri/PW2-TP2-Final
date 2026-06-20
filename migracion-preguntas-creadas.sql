-- ════════════════════════════════════════════════════════════════
-- Migración: Propuesta de Preguntas por Usuarios
-- Ejecutar sobre una base de datos `preguntados` ya existente.
-- ════════════════════════════════════════════════════════════════

USE preguntados;

-- 1) Preguntas propuestas por usuarios (espejo de `preguntas`)
CREATE TABLE preguntas_creadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(500) NOT NULL,
    categoria_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 2) Respuestas de las preguntas propuestas (espejo de `respuestas`)
CREATE TABLE respuestas_creadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_creada_id INT NOT NULL,
    texto VARCHAR(255) NOT NULL,
    es_correcta BOOLEAN NOT NULL DEFAULT false,
    FOREIGN KEY (pregunta_creada_id) REFERENCES preguntas_creadas(id)
);
