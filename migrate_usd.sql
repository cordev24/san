-- 1. Create table for exchange rates
CREATE TABLE IF NOT EXISTS `tasas_cambio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tasa` decimal(10,4) NOT NULL,
  `fecha` date NOT NULL,
  `origen` varchar(50) DEFAULT 'auto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 2. Insert an initial rate for today (fallback)
INSERT IGNORE INTO `tasas_cambio` (`tasa`, `fecha`, `origen`) VALUES (75.0000, CURDATE(), 'system_init');

-- 3. Add column to pagos for historic rate
SET @dbname = DATABASE();
SET @tablename = 'pagos';
SET @columnname = 'tasa_aplicada';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " decimal(10,4) DEFAULT NULL AFTER monto;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. Convert all existing Bs values to USD (Assume conversion rate used in past data was ~50 Bs/USD for display purposes)
-- NOTE: We only do this once.
UPDATE `productos` SET `valor_total` = `valor_total` / 50.00 WHERE `valor_total` > 1000;
UPDATE `grupos_san` SET `monto_cuota` = `monto_cuota` / 50.00 WHERE `monto_cuota` > 100;
UPDATE `pagos` SET `tasa_aplicada` = 50.00 WHERE `monto` > 100;
UPDATE `pagos` SET `monto` = `monto` / 50.00 WHERE `monto` > 100;
