-- Crear base de datos
CREATE DATABASE preguntados;

-- Usar la base de datos
USE preguntados;

-- Crear tabla usuarios
CREATE TABLE usuarios (
                          id INT AUTO_INCREMENT PRIMARY KEY,

                          nombre_completo VARCHAR(150) NOT NULL,
                          anio_nacimiento YEAR NOT NULL,

                          sexo ENUM(
        'Masculino',
        'Femenino',
        'Prefiero no cargarlo'
    ) NOT NULL DEFAULT 'Prefiero no cargarlo',

                          pais VARCHAR(100) NOT NULL,
                          ciudad VARCHAR(100) NOT NULL,

                          mail VARCHAR(150) NOT NULL UNIQUE,

                          password_hash VARCHAR(255) NOT NULL,

                          username VARCHAR(50) NOT NULL UNIQUE,

                          foto_perfil VARCHAR(255) DEFAULT NULL,

                          puntaje INT NOT NULL DEFAULT 0,

                          fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            nombre VARCHAR(100) NOT NULL,
                            color VARCHAR(20) NOT NULL
);

CREATE TABLE preguntas (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           texto VARCHAR(500) NOT NULL,
                           categoria_id INT NOT NULL,
                           estado ENUM('aprobada', 'pendiente', 'rechazada', 'reportada') DEFAULT 'pendiente',
                           creada_por INT NULL,
                           fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (categoria_id) REFERENCES categorias(id),
                           FOREIGN KEY (creada_por) REFERENCES usuarios(id)
);

CREATE TABLE respuestas (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            pregunta_id INT NOT NULL,
                            texto VARCHAR(255) NOT NULL,
                            es_correcta BOOLEAN NOT NULL DEFAULT false,
                            FOREIGN KEY (pregunta_id) REFERENCES preguntas(id)
);

CREATE TABLE partidas (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          usuario_id INT NOT NULL,
                          puntaje_total INT NOT NULL DEFAULT 0,
                          fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE partida_preguntas (
                                   id INT AUTO_INCREMENT PRIMARY KEY,
                                   partida_id INT NOT NULL,
                                   pregunta_id INT NOT NULL,
                                   FOREIGN KEY (partida_id) REFERENCES partidas(id),
                                   FOREIGN KEY (pregunta_id) REFERENCES preguntas(id)
);
