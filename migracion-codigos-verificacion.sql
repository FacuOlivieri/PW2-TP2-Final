USE preguntados;

ALTER TABLE usuarios
ADD mail_verificado TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE codigos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo VARCHAR(6) NOT NULL UNIQUE,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
