-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 31-01-2026 a las 03:06:31
-- Versión del servidor: 5.7.40
-- Versión de PHP: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mysan`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `color`) VALUES
(1, 'Electrodomésticos', 'Neveras, lavadoras, televisores, etc.', 'violeta'),
(2, 'Telefonía', 'Smartphones de alta gama', 'menta'),
(3, 'Motocicletas', 'Motos de diferentes marcas y cilindradas', 'salmon');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobantes`
--

DROP TABLE IF EXISTS `comprobantes`;
CREATE TABLE IF NOT EXISTS `comprobantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('pago','entrega') NOT NULL,
  `pago_id` int(11) DEFAULT NULL,
  `turno_id` int(11) DEFAULT NULL,
  `codigo_qr` text,
  `datos_json` text,
  `generado_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pago_id` (`pago_id`),
  KEY `turno_id` (`turno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos_san`
--

DROP TABLE IF EXISTS `grupos_san`;
CREATE TABLE IF NOT EXISTS `grupos_san` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `frecuencia` enum('quincenal','mensual') NOT NULL,
  `numero_cuotas` int(11) NOT NULL,
  `cupos_totales` int(11) NOT NULL,
  `cupos_ocupados` int(11) DEFAULT '0',
  `monto_cuota` decimal(10,2) NOT NULL,
  `estado` enum('abierto','en_curso','finalizado') DEFAULT 'abierto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `grupos_san`
--

INSERT INTO `grupos_san` (`id`, `producto_id`, `nombre`, `fecha_inicio`, `frecuencia`, `numero_cuotas`, `cupos_totales`, `cupos_ocupados`, `monto_cuota`, `estado`, `created_at`) VALUES
(1, 3, 'power band', '2026-01-30', 'mensual', 11, 2, 2, '1636.36', 'abierto', '2026-01-30 15:43:35'),
(2, 10, 'carro 2026', '2026-01-01', 'quincenal', 2, 2, 2, '6000.00', 'finalizado', '2026-01-31 02:24:07'),
(3, 11, 'tele infinix', '2026-01-31', 'quincenal', 10, 10, 0, '18000.00', 'abierto', '2026-01-31 02:38:28'),
(4, 10, 'carro 2026', '2026-01-01', 'quincenal', 2, 2, 2, '6000.00', 'abierto', '2026-01-31 03:01:47'),
(5, 3, 'tv tv', '2026-01-31', 'quincenal', 10, 10, 1, '1800.00', 'abierto', '2026-01-31 03:04:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

DROP TABLE IF EXISTS `pagos`;
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participante_id` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagado','atrasado') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `participante_id` (`participante_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `participante_id`, `numero_cuota`, `monto`, `fecha_pago`, `fecha_vencimiento`, `estado`, `metodo_pago`, `comprobante`, `notas`, `created_at`) VALUES
(1, 4, 1, '6000.00', NULL, '2026-01-01', 'pendiente', NULL, NULL, NULL, '2026-01-31 02:26:43'),
(2, 4, 2, '6000.00', NULL, '2026-01-16', 'pendiente', NULL, NULL, NULL, '2026-01-31 02:26:43'),
(3, 5, 1, '6000.00', '2026-01-31', '2026-01-01', 'pagado', 'Efectivo', NULL, '', '2026-01-31 03:02:10'),
(4, 5, 2, '6000.00', '2026-01-31', '2026-01-16', 'pagado', 'Efectivo', NULL, '', '2026-01-31 03:02:10'),
(5, 6, 1, '6000.00', '2026-01-31', '2026-01-01', 'pagado', 'Efectivo', NULL, '', '2026-01-31 03:02:37'),
(6, 6, 2, '6000.00', '2026-01-31', '2026-01-16', 'pagado', 'Efectivo', NULL, '', '2026-01-31 03:02:37'),
(7, 7, 1, '1800.00', '2026-01-31', '2026-01-31', 'pagado', 'Efectivo', NULL, 'sdsd', '2026-01-31 03:05:18'),
(8, 7, 2, '1800.00', NULL, '2026-02-15', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(9, 7, 3, '1800.00', NULL, '2026-03-02', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(10, 7, 4, '1800.00', NULL, '2026-03-17', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(11, 7, 5, '1800.00', NULL, '2026-04-01', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(12, 7, 6, '1800.00', NULL, '2026-04-16', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(13, 7, 7, '1800.00', NULL, '2026-05-01', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(14, 7, 8, '1800.00', NULL, '2026-05-16', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(15, 7, 9, '1800.00', NULL, '2026-05-31', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18'),
(16, 7, 10, '1800.00', NULL, '2026-06-15', 'pendiente', NULL, NULL, NULL, '2026-01-31 03:05:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

DROP TABLE IF EXISTS `participantes`;
CREATE TABLE IF NOT EXISTS `participantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grupo_san_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `fecha_inscripcion` date NOT NULL,
  `ha_recibido` tinyint(1) DEFAULT '0',
  `orden_turno` int(11) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `grupo_san_id` (`grupo_san_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `participantes`
--

INSERT INTO `participantes` (`id`, `grupo_san_id`, `nombre`, `apellido`, `cedula`, `telefono`, `direccion`, `fecha_inscripcion`, `ha_recibido`, `orden_turno`, `fecha_entrega`, `activo`, `created_at`) VALUES
(1, 1, 'petra', 'sojo', '1235478', '4647158', 'las mercedes', '2026-01-30', 0, 1, '2026-01-30', 1, '2026-01-30 15:43:59'),
(2, 1, 'petra', 'sojo', '587965', '4647158', 'las mercedes', '2026-01-30', 0, 2, '2026-03-02', 1, '2026-01-30 15:44:28'),
(3, 2, 'petra', 'sojo', '123547856', '4647158', 'gdfgdf', '2026-01-31', 0, NULL, NULL, 1, '2026-01-31 02:24:29'),
(4, 2, 'david', 'sojo', '951357', '4647158', 'las mercedes', '2026-01-31', 0, NULL, NULL, 1, '2026-01-31 02:26:43'),
(5, 4, 'david', 'sojo', '95135756', '4647158', 'fgdfgd', '2026-01-31', 0, NULL, NULL, 1, '2026-01-31 03:02:10'),
(6, 4, 'petra', 'sojo', '67890', '4647158', 'las mercedes', '2026-01-31', 0, NULL, NULL, 1, '2026-01-31 03:02:37'),
(7, 5, 'petra', 'sojo', '678900', '4647158', 'sss', '2026-01-31', 0, NULL, NULL, 1, '2026-01-31 03:05:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `descripcion` text,
  `valor_total` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `marca`, `modelo`, `descripcion`, `valor_total`, `imagen`, `activo`, `created_at`) VALUES
(1, 1, 'Nevera Samsung', 'Samsung', 'RT38K5930SL', NULL, '15000.00', NULL, 0, '2026-01-30 01:27:03'),
(2, 1, 'Lavadora LG', 'LG', 'WM3900HWA', NULL, '12000.00', NULL, 1, '2026-01-30 01:27:03'),
(3, 1, 'Televisor 55\"', 'Sony', 'XBR-55X900H', NULL, '18000.00', NULL, 1, '2026-01-30 01:27:03'),
(4, 2, 'iPhone 15 Pro Max', 'Apple', '256GB', NULL, '35000.00', NULL, 1, '2026-01-30 01:27:03'),
(5, 2, 'Samsung Galaxy S24 Ultra', 'Samsung', '512GB', NULL, '32000.00', NULL, 1, '2026-01-30 01:27:03'),
(6, 3, 'Yamaha FZ', 'Yamaha', 'FZ-150', NULL, '45000.00', NULL, 1, '2026-01-30 01:27:03'),
(7, 3, 'Honda CB190R', 'Honda', 'CB190R', NULL, '42000.00', NULL, 1, '2026-01-30 01:27:03'),
(8, 3, 'Suzuki Gixxer', 'Suzuki', 'Gixxer 250', NULL, '48000.00', NULL, 1, '2026-01-30 01:27:03'),
(9, 1, 'carro', 'toyota', 'rt 50', '0 km', '25000.00', NULL, 1, '2026-01-30 15:53:10'),
(10, 1, 'carro', 'toyota', 'rt 50', 'dasdasd', '12000.00', NULL, 1, '2026-01-31 02:16:57'),
(11, 2, 'infiniz¡xt 60 pro plus', 'infinix', 'pro plus', '8 gb de ram 256 de almacenamiento', '180000.00', NULL, 1, '2026-01-31 02:38:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

DROP TABLE IF EXISTS `turnos`;
CREATE TABLE IF NOT EXISTS `turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grupo_san_id` int(11) NOT NULL,
  `participante_id` int(11) DEFAULT NULL,
  `numero_turno` int(11) NOT NULL,
  `fecha_turno` date NOT NULL,
  `metodo_asignacion` enum('aleatorio','manual') NOT NULL,
  `estado` enum('pendiente','asignado','entregado') DEFAULT 'pendiente',
  `fecha_asignacion` timestamp NULL DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grupo_san_id` (`grupo_san_id`),
  KEY `participante_id` (`participante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pregunta_secreta` text,
  `respuesta_secreta` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `email`, `pregunta_secreta`, `respuesta_secreta`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$jYQ4qjSOkKgWb4VryILR6uv8xF/fT0xwC6/etSQaoyLy.RBK9v.Nu', 'Administrador', 'admin@mysan.local', '¿Cuál es tu color favorito?', 'azul', '2026-01-30 01:27:03', '2026-01-30 02:42:07');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD CONSTRAINT `comprobantes_ibfk_1` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comprobantes_ibfk_2` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupos_san`
--
ALTER TABLE `grupos_san`
  ADD CONSTRAINT `grupos_san_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD CONSTRAINT `participantes_ibfk_1` FOREIGN KEY (`grupo_san_id`) REFERENCES `grupos_san` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`grupo_san_id`) REFERENCES `grupos_san` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
