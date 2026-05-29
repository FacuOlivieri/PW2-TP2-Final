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

                          fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);