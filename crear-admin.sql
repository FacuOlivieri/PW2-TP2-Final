-- ════════════════════════════════════════════════════════════════
-- Script: Crear usuario administrador
-- ════════════════════════════════════════════════════════════════

USE preguntados;

-- OPCIÓN 1: Crear un nuevo usuario administrador
-- Cambiá los valores según necesites
-- Password: admin123 (hash bcrypt)
INSERT INTO usuarios (
    nombre_completo,
    anio_nacimiento,
    sexo,
    pais,
    ciudad,
    mail,
    password_hash,
    username,
    foto_perfil,
    puntaje,
    nivel,
    es_editor,
    es_administrador,
    mail_verificado
) VALUES (
    'Administrador',
    2000,
    'Prefiero no cargarlo',
    'Argentina',
    'Buenos Aires',
    'admin@preguntados.com',
    '$2y$10$hErT8.D2zk2CdFK9Qi8EH.RYXtjIEVgsMne0YoBc0gHrY8ZEwNw.C', -- password: admin123
    'admin',
    NULL,
    0,
    'facil',
    0,
    1,
    1
);

-- OPCIÓN 2: Convertir un usuario existente en administrador
-- Reemplaza 'tu_username' con el nombre de usuario existente
-- UPDATE usuarios SET es_administrador = 1 WHERE username = 'tu_username';
