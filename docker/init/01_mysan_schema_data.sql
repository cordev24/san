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

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Electrodomesticos','Neveras, lavadoras, televisores, etc.','violeta'),(2,'Telefonia','Smartphones de alta gama','menta'),(3,'Motocicletas','Motos de diferentes marcas y cilindradas','salmon');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `comprobantes` WRITE;
/*!40000 ALTER TABLE `comprobantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprobantes` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `grupos_san` WRITE;
/*!40000 ALTER TABLE `grupos_san` DISABLE KEYS */;
INSERT INTO `grupos_san` VALUES (4,1,'San de Prueba Menta','2026-05-01','quincenal',10,10,1,50.00,'abierto','2026-04-15 23:32:00',1),(5,1,'San de Prueba Menta','2026-05-01','quincenal',10,10,1,50.00,'abierto','2026-04-15 23:39:39',1),(6,5,'Grupo 1','2026-04-16','mensual',14,14,0,45.71,'abierto','2026-04-16 14:01:47',1);
/*!40000 ALTER TABLE `grupos_san` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,3,1,50.00,NULL,NULL,'2026-05-15','pendiente',NULL,NULL,NULL,'2026-04-15 23:32:00');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `participantes` WRITE;
/*!40000 ALTER TABLE `participantes` DISABLE KEYS */;
INSERT INTO `participantes` VALUES (3,4,'Juan','Perez','12345678','04141234567',NULL,'2026-05-01',0,NULL,1,'2026-04-15 23:32:00',5);
/*!40000 ALTER TABLE `participantes` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,'Nevera Samsung','Samsung','RT38K5930SL',NULL,300.00,NULL,1,'2026-04-02 20:42:10'),(2,1,'Lavadora LG','LG','WM3900HWA',NULL,240.00,NULL,1,'2026-04-02 20:42:10'),(3,1,'Televisor 55\"','Sony','XBR-55X900H',NULL,360.00,NULL,1,'2026-04-02 20:42:10'),(4,2,'iPhone 15 Pro Max','Apple','256GB',NULL,700.00,NULL,1,'2026-04-02 20:42:10'),(5,2,'Samsung Galaxy S24 Ultra','Samsung','512GB',NULL,640.00,NULL,1,'2026-04-02 20:42:10'),(6,3,'Yamaha FZ','Yamaha','FZ-150',NULL,900.00,NULL,1,'2026-04-02 20:42:10'),(7,3,'Honda CB190R','Honda','CB190R',NULL,840.00,NULL,1,'2026-04-02 20:42:10'),(8,3,'Suzuki Gixxer','Suzuki','Gixxer 250',NULL,960.00,NULL,1,'2026-04-02 20:42:10');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

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
  `origen` varchar(50) DEFAULT 'auto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasas_cambio`
--

LOCK TABLES `tasas_cambio` WRITE;
/*!40000 ALTER TABLE `tasas_cambio` DISABLE KEYS */;
INSERT INTO `tasas_cambio` VALUES (2,476.4342,'2026-04-12','api_auto','2026-04-12 22:18:05'),(3,477.6259,'2026-04-15','api_auto','2026-04-15 01:48:39'),(4,478.5811,'2026-04-16','api_auto','2026-04-16 01:23:03'),(5,480.2572,'2026-04-18','api_auto','2026-04-18 01:35:27');
/*!40000 ALTER TABLE `tasas_cambio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grupo_san_id` int NOT NULL,
  `participante_id` int DEFAULT NULL,
  `numero_turno` int NOT NULL,
  `fecha_turno` date DEFAULT NULL,
  `metodo_asignacion` enum('aleatorio','manual') NOT NULL,
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

LOCK TABLES `turnos` WRITE;
/*!40000 ALTER TABLE `turnos` DISABLE KEYS */;
INSERT INTO `turnos` VALUES (1,4,3,1,NULL,'aleatorio','asignado',NULL,NULL,'2026-04-15 23:49:06');
/*!40000 ALTER TABLE `turnos` ENABLE KEYS */;
UNLOCK TABLES;

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
INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$jYQ4qjSOkKgWb4VryILR6uv8xF/fT0xwC6/etSQaoyLy.RBK9v.Nu','Administrador','admin@mysan.local','├é┬┐Cu├â┬íl es tu color favorito?','azul','2026-04-02 20:42:14','2026-04-02 20:42:14','admin'),(5,'12345678','$2y$10$hfsYwJDqbDjIMj87yqWIJewjmROA2AbAXjEi7xJ8s034u9a5euVle','Juan Perez',NULL,NULL,NULL,'2026-04-15 23:32:00','2026-04-15 23:32:00','participante');
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
