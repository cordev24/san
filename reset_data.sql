-- Script para resetear la base de datos mysan
-- Elimina todos los datos manteniendo la estructura (tablas, columnas, relaciones)
-- Inserta nuevamente los datos iniciales necesarios para el sistema

SET FOREIGN_KEY_CHECKS = 0;

-- Vaciar todas las tablas
TRUNCATE TABLE comprobantes;
TRUNCATE TABLE turnos;
TRUNCATE TABLE pagos;
TRUNCATE TABLE notificaciones;
TRUNCATE TABLE participantes;
TRUNCATE TABLE grupos_san;
TRUNCATE TABLE productos_imagenes;
TRUNCATE TABLE productos;
TRUNCATE TABLE tasas_cambio;
TRUNCATE TABLE configuracion;
TRUNCATE TABLE categorias;
TRUNCATE TABLE usuarios;

-- Reinsertar datos base
INSERT INTO categorias (id, nombre, descripcion, color) VALUES
(1, 'Electrodomésticos', 'Neveras, lavadoras, televisores, etc.', 'violeta'),
(2, 'Telefonía', 'Smartphones de alta gama', 'menta'),
(3, 'Motocicletas', 'Motos de diferentes marcas y cilindradas', 'salmon');

-- Reinsertar usuario admin por defecto (pass: 1234)
INSERT INTO usuarios (id, username, password, nombre, email, pregunta_secreta, respuesta_secreta, rol) VALUES
(1, 'admin', '$2y$10$jYQ4qjSOkKgWb4VryILR6uv8xF/fT0xwC6/etSQaoyLy.RBK9v.Nu', 'Administrador', 'admin@mysan.local', '¿Cuál es tu color favorito?', 'azul', 'admin');

-- Reinsertar configuración base
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('tasa_bcv', '75', 'Tasa de cambio BCV (1 USD = X Bs)'),
('tasa_manual', '0', 'Usar tasa manual (1) o automática BCV (0)'),
('tasa_default', '75.00', 'Tasa manual por defecto');

-- Tasa inicial de referencia
INSERT IGNORE INTO tasas_cambio (tasa, fecha, origen) VALUES 
(75.0000, CURDATE(), 'auto');

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Base de datos reseteada con exito' AS resultado;
