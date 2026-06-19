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
