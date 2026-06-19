-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: mysan
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;

--
-- Table structure for table `comprobantes`
--

DROP TABLE IF EXISTS `comprobantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobantes`
--

/*!40000 ALTER TABLE `comprobantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprobantes` ENABLE KEYS */;

--
-- Table structure for table `grupos_san`
--

DROP TABLE IF EXISTS `grupos_san`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `enlace_invitacion` varchar(64) UNIQUE NULL COMMENT 'Token único para auto-registro de participantes',
  `estado_enlace` enum('activo','inactivo') DEFAULT 'activo' COMMENT 'Habilitar/deshabilitar el link de invitación',
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `grupos_san_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos_san`
--

/*!40000 ALTER TABLE `grupos_san` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupos_san` ENABLE KEYS */;

--
-- Table structure for table `mensajes`
--

-- Tabla mensajes eliminada: la comunicación externa se realiza vía WhatsApp (ver informe sección 6.1.4)

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensaje` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;

--
-- @deprecated: El sistema de turnos fue reemplazado por asignacion por orden de inscripcion (leivis-pg2.md)
-- Table structure for table `turnos`
--

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `participante_id` int NOT NULL,
  `numero_cuota` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tasa_aplicada` decimal(10,4) DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pendiente_verificacion','pagado','atrasado') DEFAULT 'pendiente'
    COMMENT 'pendiente=sin pago, pendiente_verificacion=participante reportó comprobante, pagado=admin aprobó, atrasado=vencido sin pago',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `comprobante` varchar(255) DEFAULT NULL COMMENT 'Path relativo al archivo de comprobante subido por participante',
  `referencia_pago` varchar(100) DEFAULT NULL COMMENT 'Número de referencia / confirmación del pago',
  `notas` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `participante_id` (`participante_id`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;

--
-- Table structure for table `participantes`
--

DROP TABLE IF EXISTS `participantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  `tipo_registro` enum('autonomo','asistido') DEFAULT 'asistido'
    COMMENT 'autonomo=se registró via enlace único, asistido=registrado manualmente por el admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `grupo_san_id` (`grupo_san_id`),
  CONSTRAINT `participantes_ibfk_1` FOREIGN KEY (`grupo_san_id`) REFERENCES `grupos_san` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participantes`
--

/*!40000 ALTER TABLE `participantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `participantes` ENABLE KEYS */;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;

--
-- Table structure for table `tasas_cambio`
--

DROP TABLE IF EXISTS `tasas_cambio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasas_cambio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tasa` decimal(10,4) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('pendiente','asignado','entregado') DEFAULT 'pendiente',
  `fecha_asignacion` timestamp NULL DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grupo_san_id` (`grupo_san_id`),
  KEY `participante_id` (`participante_id`),
  CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`grupo_san_id`) REFERENCES `grupos_san` (`id`) ON DELETE CASCADE,
  CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turnos`
--

/*!40000 ALTER TABLE `turnos` DISABLE KEYS */;
/*!40000 ALTER TABLE `turnos` ENABLE KEYS */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pregunta_secreta` text,
  `respuesta_secreta` varchar(255) DEFAULT NULL,
  `pregunta_secreta_2` text,
  `respuesta_secreta_2` varchar(255) DEFAULT NULL,
  `pregunta_secreta_3` text,
  `respuesta_secreta_3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rol` enum('admin','participante') DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$jYQ4qjSOkKgWb4VryILR6uv8xF/fT0xwC6/etSQaoyLy.RBK9v.Nu','Administrador','admin@mysan.local','¿Cuál es tu color favorito?','azul',NULL,NULL,NULL,NULL,'2026-01-30 01:27:03','2026-01-30 02:42:07','admin');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-18  8:29:04


-- MySan - Tabla de tasas de cambio BCV
-- Ejecutado después del schema principal (02)

CREATE TABLE IF NOT EXISTS `tasas_cambio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasa` decimal(10,4) NOT NULL,
  `fecha` date NOT NULL,
  `origen` varchar(50) DEFAULT 'auto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tasa inicial de referencia (fallback hasta que la API la actualice)
INSERT IGNORE INTO `tasas_cambio` (`tasa`, `fecha`, `origen`) VALUES (75.0000, CURDATE(), 'auto');

-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('tasa_bcv', '75', 'Tasa de cambio BCV (1 USD = X Bs)'),
('tasa_manual', '0', 'Usar tasa manual (1) o automática BCV (0)'),
('tasa_default', '75.00', 'Tasa manual por defecto');


-- ============================================================
-- MySan — Migración 04: Módulo Financiero Multimoneda BCV
-- Agrega los campos requeridos por el informe técnico §6.2.1
-- ============================================================

-- 1. pagos: monto pagado en Bolívares (informado por el participante)
ALTER TABLE pagos
    ADD COLUMN monto_bs_pagado DECIMAL(16,2) DEFAULT NULL
    COMMENT 'Monto en Bs (VES) que el participante transfirió, informado al reportar el pago';

-- 2. tasas_cambio: permitir origen 'manual' (ingresado por admin) además de 'auto'
ALTER TABLE tasas_cambio
    MODIFY COLUMN origen ENUM('auto','manual') DEFAULT 'auto'
    COMMENT 'auto=obtenida via API BCV, manual=ingresada por el administrador';

SELECT 'Migracion 04 completada' AS resultado;


-- Migration: Multiple images per product
-- Creates productos_imagenes table and migrates existing single images

CREATE TABLE IF NOT EXISTS `productos_imagenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `orden` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `productos_imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Migrate existing single images to the new table
INSERT INTO productos_imagenes (producto_id, ruta, orden, created_at)
SELECT p.id, p.imagen, 0, p.created_at
FROM productos p
WHERE p.imagen IS NOT NULL AND p.imagen != '';
-- Migration 003: Auto-asignación de turnos por orden de inscripción
-- Agrega 'orden_inscripcion' al ENUM de metodo_asignacion
ALTER TABLE turnos MODIFY COLUMN metodo_asignacion ENUM('aleatorio','manual','orden_inscripcion') NOT NULL;

-- Backfill: eliminar turnos existentes (solo hay 1, de Juan Perez en grupo 4)

-- Asignar turnos por orden de ID (orden de registro) para cada grupo
    p.grupo_san_id,
    p.id,
    ROW_NUMBER() OVER (PARTITION BY p.grupo_san_id ORDER BY p.id) as numero_turno,
    'orden_inscripcion',
    'asignado',
    NOW()
