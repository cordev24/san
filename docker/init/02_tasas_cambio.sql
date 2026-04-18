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
INSERT IGNORE INTO `tasas_cambio` (`tasa`, `fecha`, `origen`) VALUES (75.0000, CURDATE(), 'system_init');

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
