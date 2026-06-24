-- MySan Database - Esquema Unificado de Inicialización
-- Este archivo se ejecuta automáticamente al levantar el contenedor de base de datos por primera vez.

CREATE DATABASE IF NOT EXISTS `mysan` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mysan`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Tabla: categorias
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabla: usuarios
-- --------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pregunta_secreta` text,
  `respuesta_secreta` varchar(255) DEFAULT NULL,
  `pregunta_secreta_2` text DEFAULT NULL,
  `respuesta_secreta_2` varchar(255) DEFAULT NULL,
  `pregunta_secreta_3` text DEFAULT NULL,
  `respuesta_secreta_3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rol` enum('admin','participante') DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabla: productos
-- --------------------------------------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `descripcion` text,
  `valor_total` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabla: productos_imagenes (Múltiples imágenes por producto)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `productos_imagenes`;
CREATE TABLE `productos_imagenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `productos_imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabla: grupos_san
-- --------------------------------------------------------
DROP TABLE IF EXISTS `grupos_san`;
CREATE TABLE `grupos_san` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `frecuencia` enum('quincenal','mensual') NOT NULL,
  `numero_cuotas` int NOT NULL,
  `cupos_totales` int NOT NULL,
  `cupos_ocupados` int DEFAULT '0',
  `monto_cuota` decimal(10,2) NOT NULL,
  `estado` enum('en_espera','abierto','en_curso','finalizado') DEFAULT 'en_espera',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ronda_actual` int DEFAULT '1',
  `enlace_invitacion` varchar(64) DEFAULT NULL,
  `estado_enlace` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `enlace_invitacion` (`enlace_invitacion`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `grupos_san_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabla: participantes
-- --------------------------------------------------------
DROP TABLE IF EXISTS `participantes`;
CREATE TABLE `participantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grupo_san_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `fecha_inscripcion` date NOT NULL,
  `ha_recibido` tinyint DEFAULT '0',
  `fecha_entrega` date DEFAULT NULL,
  `activo` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  `tipo_registro` enum('autonomo','asistido') DEFAULT 'asistido',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `grupo_san_id` (`grupo_san_id`),
  CONSTRAINT `participantes_ibfk_1` FOREIGN KEY (`grupo_san_id`) REFERENCES `grupos_san` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Tabla: pagos
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pagos`;
CREATE TABLE `pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `participante_id` int NOT NULL,
  `numero_cuota` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tasa_aplicada` decimal(10,4) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pendiente_verificacion','pagado','atrasado') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `monto_bs_pagado` decimal(16,2) DEFAULT NULL,
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `participante_id` (`participante_id`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Tabla: turnos
-- --------------------------------------------------------
DROP TABLE IF EXISTS `turnos`;
CREATE TABLE `turnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grupo_san_id` int NOT NULL,
  `participante_id` int DEFAULT NULL,
  `numero_turno` int NOT NULL,
  `fecha_turno` date DEFAULT NULL,
  `metodo_asignacion` enum('aleatorio','manual','orden_inscripcion') NOT NULL,
  `estado` enum('pendiente','asignado','entregado') DEFAULT 'pendiente',
  `fecha_asignacion` timestamp NULL DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grupo_san_id` (`grupo_san_id`),
  KEY `participante_id` (`participante_id`),
  CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`grupo_san_id`) REFERENCES `grupos_san` (`id`) ON DELETE CASCADE,
  CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Tabla: comprobantes
-- --------------------------------------------------------
DROP TABLE IF EXISTS `comprobantes`;
CREATE TABLE `comprobantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('pago','entrega') NOT NULL,
  `pago_id` int DEFAULT NULL,
  `turno_id` int DEFAULT NULL,
  `codigo_qr` text,
  `datos_json` text,
  `generado_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pago_id` (`pago_id`),
  KEY `turno_id` (`turno_id`),
  CONSTRAINT `comprobantes_ibfk_1` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comprobantes_ibfk_2` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Tabla: notificaciones
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensaje` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Tabla: tasas_cambio
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tasas_cambio`;
CREATE TABLE `tasas_cambio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tasa` decimal(10,4) NOT NULL,
  `fecha` date NOT NULL,
  `origen` enum('auto','manual') DEFAULT 'auto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Tabla: configuracion
-- --------------------------------------------------------
DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Datos Iniciales / Semillas
-- --------------------------------------------------------

-- Categorías por defecto
INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `color`) VALUES
(1, 'Electrodomésticos', 'Neveras, lavadoras, televisores, etc.', 'violeta'),
(2, 'Telefonía', 'Smartphones de alta gama', 'menta'),
(3, 'Motocicletas', 'Motos de diferentes marcas y cilindradas', 'salmon');

-- Productos iniciales
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `marca`, `modelo`, `descripcion`, `valor_total`, `imagen`, `activo`) VALUES
(1, 1, 'Nevera Samsung', 'Samsung', 'RT38K5930SL', NULL, 300.00, NULL, 1),
(2, 1, 'Lavadora LG', 'LG', 'WM3900HWA', NULL, 240.00, NULL, 1),
(3, 1, 'Televisor 55\"', 'Sony', 'XBR-55X900H', NULL, 360.00, NULL, 1),
(4, 2, 'iPhone 15 Pro Max', 'Apple', '256GB', NULL, 700.00, NULL, 1),
(5, 2, 'Samsung Galaxy S24 Ultra', 'Samsung', '512GB', NULL, 640.00, NULL, 1),
(6, 3, 'Yamaha FZ', 'Yamaha', 'FZ-150', NULL, 900.00, NULL, 1),
(7, 3, 'Honda CB190R', 'Honda', 'CB190R', NULL, 840.00, NULL, 1),
(8, 3, 'Suzuki Gixxer', 'Suzuki', 'Gixxer 250', NULL, 960.00, NULL, 1);

-- Usuario admin por defecto (pass: 1234)
INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `email`, `pregunta_secreta`, `respuesta_secreta`, `rol`) VALUES
(1, 'admin', '$2y$10$jYQ4qjSOkKgWb4VryILR6uv8xF/fT0xwC6/etSQaoyLy.RBK9v.Nu', 'Administrador', 'admin@mysan.local', '¿Cuál es tu color favorito?', 'azul', 'admin');

-- Configuración base
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('tasa_bcv', '75', 'Tasa de cambio BCV (1 USD = X Bs)'),
('tasa_manual', '0', 'Usar tasa manual (1) o automática BCV (0)'),
('tasa_default', '75.00', 'Tasa manual por defecto');

-- Tasa inicial de referencia
INSERT IGNORE INTO `tasas_cambio` (`tasa`, `fecha`, `origen`) VALUES 
(75.0000, CURDATE(), 'auto');

SET FOREIGN_KEY_CHECKS = 1;
