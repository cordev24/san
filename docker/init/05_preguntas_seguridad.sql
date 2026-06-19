-- MySan - Migración para agregar 3 preguntas de seguridad por usuario
-- Agrega columnas adicionales a la tabla usuarios para la segunda y tercera pregunta de seguridad

ALTER TABLE usuarios
    ADD COLUMN pregunta_secreta_2 text DEFAULT NULL,
    ADD COLUMN respuesta_secreta_2 varchar(255) DEFAULT NULL,
    ADD COLUMN pregunta_secreta_3 text DEFAULT NULL,
    ADD COLUMN respuesta_secreta_3 varchar(255) DEFAULT NULL;
