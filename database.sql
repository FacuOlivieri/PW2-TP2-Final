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

                          nivel ENUM('facil','medio','dificil') DEFAULT 'facil',
                          es_editor TINYINT(1) NOT NULL DEFAULT 0,
                          es_administrador TINYINT(1) NOT NULL DEFAULT 0,
                          mail_verificado TINYINT(1) NOT NULL DEFAULT 0,

                          fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

    CREATE TABLE codigos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        codigo VARCHAR(6) NOT NULL UNIQUE,
        usado TINYINT(1) NOT NULL DEFAULT 0,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
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
                           veces_entregada INT NOT NULL DEFAULT 0,
                           veces_correcta INT NOT NULL DEFAULT 0,
                           dificultad ENUM('facil','medio','dificil') DEFAULT 'medio',
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

CREATE TABLE preguntas_reportadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT,
    usuario_id INT,
    motivo TEXT,
    estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    fecha DATETIME DEFAULT NOW()
);

CREATE TABLE preguntas_sugeridas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    texto_pregunta TEXT,
    categoria_id INT,
    estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    fecha DATETIME DEFAULT NOW()
);

-- Preguntas propuestas por los usuarios (espejo de `preguntas`, pendientes de revisión)
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

-- Respuestas de las preguntas propuestas (espejo de `respuestas`)
CREATE TABLE respuestas_creadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_creada_id INT NOT NULL,
    texto VARCHAR(255) NOT NULL,
    es_correcta BOOLEAN NOT NULL DEFAULT false,
    FOREIGN KEY (pregunta_creada_id) REFERENCES preguntas_creadas(id)
);



ALTER TABLE preguntas
ADD veces_entregada INT NOT NULL DEFAULT 0,
ADD veces_correcta INT NOT NULL DEFAULT 0,
ADD dificultad ENUM('facil','medio','dificil') DEFAULT 'medio';

ALTER TABLE usuarios 
ADD nivel ENUM('facil','medio','dificil') DEFAULT 'facil',
ADD es_editor TINYINT(1) NOT NULL DEFAULT 0,
ADD es_administrador TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE preguntas
ADD estado ENUM('activa','pendiente','rechazada') DEFAULT 'activa',
ADD creada_por INT NULL;

ALTER TABLE usuarios
    ADD mail_verificado TINYINT(1) NOT NULL DEFAULT 0;


-- Datos iniciales
INSERT INTO categorias (nombre, color) VALUES
                                           ('Geografia', 'azul'),
                                           ('Arte', 'rojo'),
                                           ('Historia', 'amarillo'),
                                           ('Ciencia', 'verde'),
                                           ('Entretenimiento', 'rosa'),
                                           ('Deportes', 'naranja');

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Cuál es la capital de Australia?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Geografia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Canberra', true),
                                                             (@pregunta_id, 'Sídney', false),
                                                             (@pregunta_id, 'Melbourne', false),
                                                             (@pregunta_id, 'Brisbane', false),
                                                             (@pregunta_id, 'Perth', false),
                                                             (@pregunta_id, 'Adelaida', false),
                                                             (@pregunta_id, 'Hobart', false),
                                                             (@pregunta_id, 'Darwin', false),
                                                             (@pregunta_id, 'Auckland', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué país posee la mayor cantidad de husos horarios?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Geografia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Francia', true),
                                                             (@pregunta_id, 'Rusia', false),
                                                             (@pregunta_id, 'Estados Unidos', false),
                                                             (@pregunta_id, 'China', false),
                                                             (@pregunta_id, 'Canadá', false),
                                                             (@pregunta_id, 'Reino Unido', false),
                                                             (@pregunta_id, 'Australia', false),
                                                             (@pregunta_id, 'Brasil', false),
                                                             (@pregunta_id, 'India', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Cuál es el lago más profundo del mundo?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Geografia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Lago Baikal', true),
                                                             (@pregunta_id, 'Lago Superior', false),
                                                             (@pregunta_id, 'Lago Victoria', false),
                                                             (@pregunta_id, 'Lago Titicaca', false),
                                                             (@pregunta_id, 'Mar Caspio', false),
                                                             (@pregunta_id, 'Lago Ontario', false),
                                                             (@pregunta_id, 'Lago Michigan', false),
                                                             (@pregunta_id, 'Lago Malawi', false),
                                                             (@pregunta_id, 'Lago Erie', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿En qué país se encuentra la región de Transilvania?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Geografia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Rumania', true),
                                                             (@pregunta_id, 'Hungría', false),
                                                             (@pregunta_id, 'Bulgaria', false),
                                                             (@pregunta_id, 'Serbia', false),
                                                             (@pregunta_id, 'Ucrania', false),
                                                             (@pregunta_id, 'Croacia', false),
                                                             (@pregunta_id, 'Eslovaquia', false),
                                                             (@pregunta_id, 'Moldavia', false),
                                                             (@pregunta_id, 'Polonia', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué estrecho separa Asia de América del Norte?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Geografia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Estrecho de Bering', true),
                                                             (@pregunta_id, 'Estrecho de Gibraltar', false),
                                                             (@pregunta_id, 'Estrecho de Magallanes', false),
                                                             (@pregunta_id, 'Canal de Panamá', false),
                                                             (@pregunta_id, 'Estrecho de Ormuz', false),
                                                             (@pregunta_id, 'Canal de Suez', false),
                                                             (@pregunta_id, 'Estrecho de Malaca', false),
                                                             (@pregunta_id, 'Canal de la Mancha', false),
                                                             (@pregunta_id, 'Estrecho de Torres', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Quién pintó "La persistencia de la memoria"?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Arte';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Salvador Dalí', true),
                                                             (@pregunta_id, 'Pablo Picasso', false),
                                                             (@pregunta_id, 'Joan Miró', false),
                                                             (@pregunta_id, 'Claude Monet', false),
                                                             (@pregunta_id, 'Vincent van Gogh', false),
                                                             (@pregunta_id, 'René Magritte', false),
                                                             (@pregunta_id, 'Francisco Goya', false),
                                                             (@pregunta_id, 'Edvard Munch', false),
                                                             (@pregunta_id, 'Diego Velázquez', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué arquitecto diseñó la Sagrada Familia?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Arte';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Antoni Gaudí', true),
                                                             (@pregunta_id, 'Frank Lloyd Wright', false),
                                                             (@pregunta_id, 'Le Corbusier', false),
                                                             (@pregunta_id, 'Oscar Niemeyer', false),
                                                             (@pregunta_id, 'Santiago Calatrava', false),
                                                             (@pregunta_id, 'Mies van der Rohe', false),
                                                             (@pregunta_id, 'Rafael Moneo', false),
                                                             (@pregunta_id, 'Antoni Bonet', false),
                                                             (@pregunta_id, 'Norman Foster', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿A qué movimiento artístico pertenece Claude Monet?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Arte';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Impresionismo', true),
                                                             (@pregunta_id, 'Cubismo', false),
                                                             (@pregunta_id, 'Surrealismo', false),
                                                             (@pregunta_id, 'Barroco', false),
                                                             (@pregunta_id, 'Renacimiento', false),
                                                             (@pregunta_id, 'Expresionismo', false),
                                                             (@pregunta_id, 'Rococó', false),
                                                             (@pregunta_id, 'Futurismo', false),
                                                             (@pregunta_id, 'Romanticismo', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Quién pintó "Las Meninas"?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Arte';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Diego Velázquez', true),
                                                             (@pregunta_id, 'Francisco Goya', false),
                                                             (@pregunta_id, 'El Greco', false),
                                                             (@pregunta_id, 'Murillo', false),
                                                             (@pregunta_id, 'Picasso', false),
                                                             (@pregunta_id, 'Dalí', false),
                                                             (@pregunta_id, 'Sorolla', false),
                                                             (@pregunta_id, 'Rubens', false),
                                                             (@pregunta_id, 'Rembrandt', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿En qué ciudad se encuentra el Museo del Louvre?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Arte';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'París', true),
                                                             (@pregunta_id, 'Londres', false),
                                                             (@pregunta_id, 'Roma', false),
                                                             (@pregunta_id, 'Madrid', false),
                                                             (@pregunta_id, 'Berlín', false),
                                                             (@pregunta_id, 'Viena', false),
                                                             (@pregunta_id, 'Bruselas', false),
                                                             (@pregunta_id, 'Ámsterdam', false),
                                                             (@pregunta_id, 'Lisboa', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué batalla marcó el final definitivo del Imperio napoleónico?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Historia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Batalla de Waterloo', true),
                                                             (@pregunta_id, 'Batalla de Austerlitz', false),
                                                             (@pregunta_id, 'Batalla de Trafalgar', false),
                                                             (@pregunta_id, 'Batalla de Leipzig', false),
                                                             (@pregunta_id, 'Batalla de Borodino', false),
                                                             (@pregunta_id, 'Batalla de Marengo', false),
                                                             (@pregunta_id, 'Batalla de Sedán', false),
                                                             (@pregunta_id, 'Batalla del Somme', false),
                                                             (@pregunta_id, 'Batalla de Verdún', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Quién fue el último zar de Rusia?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Historia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Nicolás II', true),
                                                             (@pregunta_id, 'Alejandro III', false),
                                                             (@pregunta_id, 'Pedro I', false),
                                                             (@pregunta_id, 'Iván IV', false),
                                                             (@pregunta_id, 'Catalina II', false),
                                                             (@pregunta_id, 'Miguel I', false),
                                                             (@pregunta_id, 'Boris Godunov', false),
                                                             (@pregunta_id, 'Alejandro I', false),
                                                             (@pregunta_id, 'Pablo I', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿En qué año comenzó la Primera Guerra Mundial?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Historia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, '1914', true),
                                                             (@pregunta_id, '1912', false),
                                                             (@pregunta_id, '1913', false),
                                                             (@pregunta_id, '1915', false),
                                                             (@pregunta_id, '1916', false),
                                                             (@pregunta_id, '1908', false),
                                                             (@pregunta_id, '1918', false),
                                                             (@pregunta_id, '1920', false),
                                                             (@pregunta_id, '1939', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué civilización construyó la ciudad de Petra?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Historia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Nabatea', true),
                                                             (@pregunta_id, 'Egipcia', false),
                                                             (@pregunta_id, 'Romana', false),
                                                             (@pregunta_id, 'Persa', false),
                                                             (@pregunta_id, 'Fenicia', false),
                                                             (@pregunta_id, 'Asiria', false),
                                                             (@pregunta_id, 'Sumeria', false),
                                                             (@pregunta_id, 'Hitita', false),
                                                             (@pregunta_id, 'Inca', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Quién fue el primer presidente de Estados Unidos?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Historia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'George Washington', true),
                                                             (@pregunta_id, 'Thomas Jefferson', false),
                                                             (@pregunta_id, 'John Adams', false),
                                                             (@pregunta_id, 'Abraham Lincoln', false),
                                                             (@pregunta_id, 'James Madison', false),
                                                             (@pregunta_id, 'Andrew Jackson', false),
                                                             (@pregunta_id, 'Benjamin Franklin', false),
                                                             (@pregunta_id, 'Ulysses Grant', false),
                                                             (@pregunta_id, 'Theodore Roosevelt', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué elemento químico tiene el número atómico 26?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Ciencia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Hierro', true),
                                                             (@pregunta_id, 'Cobre', false),
                                                             (@pregunta_id, 'Zinc', false),
                                                             (@pregunta_id, 'Plata', false),
                                                             (@pregunta_id, 'Níquel', false),
                                                             (@pregunta_id, 'Cobalto', false),
                                                             (@pregunta_id, 'Oro', false),
                                                             (@pregunta_id, 'Estaño', false),
                                                             (@pregunta_id, 'Aluminio', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué científico propuso las tres leyes del movimiento?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Ciencia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Isaac Newton', true),
                                                             (@pregunta_id, 'Galileo Galilei', false),
                                                             (@pregunta_id, 'Nikola Tesla', false),
                                                             (@pregunta_id, 'Albert Einstein', false),
                                                             (@pregunta_id, 'Johannes Kepler', false),
                                                             (@pregunta_id, 'Niels Bohr', false),
                                                             (@pregunta_id, 'Michael Faraday', false),
                                                             (@pregunta_id, 'Max Planck', false),
                                                             (@pregunta_id, 'Stephen Hawking', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Cuál es el hueso más largo del cuerpo humano?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Ciencia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Fémur', true),
                                                             (@pregunta_id, 'Tibia', false),
                                                             (@pregunta_id, 'Peroné', false),
                                                             (@pregunta_id, 'Húmero', false),
                                                             (@pregunta_id, 'Radio', false),
                                                             (@pregunta_id, 'Cúbito', false),
                                                             (@pregunta_id, 'Clavícula', false),
                                                             (@pregunta_id, 'Esternón', false),
                                                             (@pregunta_id, 'Escápula', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué planeta posee la mayor cantidad de lunas conocidas?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Ciencia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Saturno', true),
                                                             (@pregunta_id, 'Júpiter', false),
                                                             (@pregunta_id, 'Marte', false),
                                                             (@pregunta_id, 'Urano', false),
                                                             (@pregunta_id, 'Neptuno', false),
                                                             (@pregunta_id, 'Venus', false),
                                                             (@pregunta_id, 'Mercurio', false),
                                                             (@pregunta_id, 'Tierra', false),
                                                             (@pregunta_id, 'Plutón', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Cómo se denomina el proceso por el cual una célula se divide en dos células hijas idénticas?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Ciencia';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Mitosis', true),
                                                             (@pregunta_id, 'Meiosis', false),
                                                             (@pregunta_id, 'Fotosíntesis', false),
                                                             (@pregunta_id, 'Ósmosis', false),
                                                             (@pregunta_id, 'Difusión', false),
                                                             (@pregunta_id, 'Transcripción', false),
                                                             (@pregunta_id, 'Traducción', false),
                                                             (@pregunta_id, 'Mutación', false),
                                                             (@pregunta_id, 'Fecundación', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Quién dirigió la película "Pulp Fiction"?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Entretenimiento';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Quentin Tarantino', true),
                                                             (@pregunta_id, 'Martin Scorsese', false),
                                                             (@pregunta_id, 'Steven Spielberg', false),
                                                             (@pregunta_id, 'Ridley Scott', false),
                                                             (@pregunta_id, 'Christopher Nolan', false),
                                                             (@pregunta_id, 'James Cameron', false),
                                                             (@pregunta_id, 'David Fincher', false),
                                                             (@pregunta_id, 'Tim Burton', false),
                                                             (@pregunta_id, 'Francis Ford Coppola', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué actor interpretó a Maximus en Gladiador?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Entretenimiento';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Russell Crowe', true),
                                                             (@pregunta_id, 'Mel Gibson', false),
                                                             (@pregunta_id, 'Brad Pitt', false),
                                                             (@pregunta_id, 'Hugh Jackman', false),
                                                             (@pregunta_id, 'Gerard Butler', false),
                                                             (@pregunta_id, 'Tom Cruise', false),
                                                             (@pregunta_id, 'Liam Neeson', false),
                                                             (@pregunta_id, 'Matt Damon', false),
                                                             (@pregunta_id, 'Ben Affleck', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué banda lanzó el álbum "The Dark Side of the Moon"?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Entretenimiento';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Pink Floyd', true),
                                                             (@pregunta_id, 'Queen', false),
                                                             (@pregunta_id, 'Led Zeppelin', false),
                                                             (@pregunta_id, 'Genesis', false),
                                                             (@pregunta_id, 'The Beatles', false),
                                                             (@pregunta_id, 'Deep Purple', false),
                                                             (@pregunta_id, 'Eagles', false),
                                                             (@pregunta_id, 'AC/DC', false),
                                                             (@pregunta_id, 'Yes', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Cómo se llama el reino donde transcurre gran parte de Game of Thrones?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Entretenimiento';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Westeros', true),
                                                             (@pregunta_id, 'Narnia', false),
                                                             (@pregunta_id, 'Mordor', false),
                                                             (@pregunta_id, 'Gondor', false),
                                                             (@pregunta_id, 'Essos', false),
                                                             (@pregunta_id, 'Valyria', false),
                                                             (@pregunta_id, 'Rohan', false),
                                                             (@pregunta_id, 'Rivendel', false),
                                                             (@pregunta_id, 'Midgard', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Quién compuso "Las cuatro estaciones"?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Entretenimiento';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Antonio Vivaldi', true),
                                                             (@pregunta_id, 'Mozart', false),
                                                             (@pregunta_id, 'Beethoven', false),
                                                             (@pregunta_id, 'Bach', false),
                                                             (@pregunta_id, 'Chopin', false),
                                                             (@pregunta_id, 'Tchaikovsky', false),
                                                             (@pregunta_id, 'Verdi', false),
                                                             (@pregunta_id, 'Haydn', false),
                                                             (@pregunta_id, 'Schubert', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué tenista ganó más títulos de Grand Slam en la rama masculina?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Deportes';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Novak Djokovic', true),
                                                             (@pregunta_id, 'Roger Federer', false),
                                                             (@pregunta_id, 'Rafael Nadal', false),
                                                             (@pregunta_id, 'Pete Sampras', false),
                                                             (@pregunta_id, 'Andre Agassi', false),
                                                             (@pregunta_id, 'Björn Borg', false),
                                                             (@pregunta_id, 'John McEnroe', false),
                                                             (@pregunta_id, 'Andy Murray', false),
                                                             (@pregunta_id, 'Jimmy Connors', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿En qué país se originó el rugby?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Deportes';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Inglaterra', true),
                                                             (@pregunta_id, 'Escocia', false),
                                                             (@pregunta_id, 'Irlanda', false),
                                                             (@pregunta_id, 'Gales', false),
                                                             (@pregunta_id, 'Francia', false),
                                                             (@pregunta_id, 'Australia', false),
                                                             (@pregunta_id, 'Nueva Zelanda', false),
                                                             (@pregunta_id, 'Sudáfrica', false),
                                                             (@pregunta_id, 'Argentina', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Cuántos metros mide una maratón oficial?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Deportes';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, '42,195 km', true),
                                                             (@pregunta_id, '40 km', false),
                                                             (@pregunta_id, '41 km', false),
                                                             (@pregunta_id, '42 km', false),
                                                             (@pregunta_id, '43 km', false),
                                                             (@pregunta_id, '44 km', false),
                                                             (@pregunta_id, '45 km', false),
                                                             (@pregunta_id, '38 km', false),
                                                             (@pregunta_id, '50 km', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué piloto tiene más campeonatos mundiales de Fórmula 1?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Deportes';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Lewis Hamilton', true),
                                                             (@pregunta_id, 'Michael Schumacher', false),
                                                             (@pregunta_id, 'Ayrton Senna', false),
                                                             (@pregunta_id, 'Alain Prost', false),
                                                             (@pregunta_id, 'Sebastian Vettel', false),
                                                             (@pregunta_id, 'Max Verstappen', false),
                                                             (@pregunta_id, 'Niki Lauda', false),
                                                             (@pregunta_id, 'Nelson Piquet', false),
                                                             (@pregunta_id, 'Fernando Alonso', false);

INSERT INTO preguntas (texto, categoria_id, estado, creada_por)
SELECT '¿Qué selección ganó la primera Copa Mundial de Fútbol en 1930?', id, 'aprobada', NULL FROM categorias WHERE nombre = 'Deportes';
SET @pregunta_id = LAST_INSERT_ID();
INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES
                                                             (@pregunta_id, 'Uruguay', true),
                                                             (@pregunta_id, 'Argentina', false),
                                                             (@pregunta_id, 'Brasil', false),
                                                             (@pregunta_id, 'Italia', false),
                                                             (@pregunta_id, 'Alemania', false),
                                                             (@pregunta_id, 'Inglaterra', false),
                                                             (@pregunta_id, 'Francia', false),
                                                             (@pregunta_id, 'España', false),
                                                             (@pregunta_id, 'Chile', false);